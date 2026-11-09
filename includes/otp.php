<?php
/**
 * Email OTP: creation, resend cooldown, expiry, attempt limits, one-time use.
 * Only a hash of each OTP is stored in the database.
 */

require_once __DIR__ . '/mailer.php';

/** Seconds the user still has to wait before a new OTP may be sent (0 = may send now). */
function otp_cooldown_remaining(int $userId): int
{
    $stmt = db()->prepare('SELECT last_sent_at FROM email_verifications WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId]);
    $last = $stmt->fetchColumn();
    if (!$last) {
        return 0;
    }
    return max(0, strtotime($last) + OTP_RESEND_COOLDOWN_SECONDS - time());
}

/**
 * Creates a fresh OTP, invalidates older ones and emails it.
 * Returns null on success or an error message.
 */
function otp_issue(array $user): ?string
{
    $wait = otp_cooldown_remaining((int) $user['id']);
    if ($wait > 0) {
        return "Please wait {$wait} seconds before requesting another code.";
    }

    $pdo = db();
    $now = date('Y-m-d H:i:s');

    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $pdo->prepare('UPDATE email_verifications SET used_at = ? WHERE user_id = ? AND used_at IS NULL')
        ->execute([$now, $user['id']]);

    $pdo->prepare('INSERT INTO email_verifications (user_id, otp_hash, expires_at, attempts, last_sent_at) VALUES (?, ?, ?, 0, ?)')
        ->execute([
            $user['id'],
            password_hash($otp, PASSWORD_DEFAULT),
            date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60),
            $now,
        ]);
    $verificationId = (int) $pdo->lastInsertId();

    $error = send_otp_email($user['email'], $user['full_name'], $otp);
    if ($error !== null) {
        // Sending failed: remove the row so the user can retry without waiting.
        $pdo->prepare('DELETE FROM email_verifications WHERE id = ?')->execute([$verificationId]);
        return $error;
    }
    return null;
}

/**
 * Checks a submitted code.
 * Returns ['status' => 'ok'|'invalid'|'expired'|'locked'|'none', 'remaining' => int].
 */
function otp_verify(int $userId, string $code): array
{
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM email_verifications WHERE user_id = ? AND used_at IS NULL ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['status' => 'none', 'remaining' => 0];
    }
    if (strtotime($row['expires_at']) < time()) {
        return ['status' => 'expired', 'remaining' => 0];
    }
    if ((int) $row['attempts'] >= OTP_MAX_ATTEMPTS) {
        return ['status' => 'locked', 'remaining' => 0];
    }

    // Count the attempt before checking the code.
    $pdo->prepare('UPDATE email_verifications SET attempts = attempts + 1 WHERE id = ?')->execute([$row['id']]);
    $remaining = OTP_MAX_ATTEMPTS - ((int) $row['attempts'] + 1);

    if (!password_verify($code, $row['otp_hash'])) {
        return ['status' => 'invalid', 'remaining' => $remaining];
    }

    $now = date('Y-m-d H:i:s');
    $pdo->prepare('UPDATE email_verifications SET used_at = ? WHERE id = ?')->execute([$now, $row['id']]);
    $pdo->prepare('UPDATE users SET email_verified_at = ? WHERE id = ?')->execute([$now, $userId]);

    return ['status' => 'ok', 'remaining' => $remaining];
}
