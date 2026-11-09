<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/otp.php';

guest_only();

// This page is only for someone who just registered (or tried to log in unverified).
$pendingId = (int) ($_SESSION['pending_user_id'] ?? 0);
if ($pendingId === 0) {
    flash('error', 'Please register or log in first.');
    redirect('login.php');
}

$stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer' AND email_verified_at IS NULL");
$stmt->execute([$pendingId]);
$pendingUser = $stmt->fetch();

if (!$pendingUser) {
    unset($_SESSION['pending_user_id']);
    flash('info', 'That account is already verified. Please log in.');
    redirect('login.php');
}

$errors = [];

if (is_post()) {
    csrf_verify();
    $action = post('action');

    if ($action === 'resend') {
        $error = otp_issue($pendingUser);
        if ($error === null) {
            flash('success', 'A new code has been sent to ' . $pendingUser['email'] . '.');
        } else {
            flash('error', $error);
        }
        redirect('verify-otp.php');
    }

    if ($action === 'verify') {
        $code = post('otp');

        if (!preg_match('/^[0-9]{6}$/', $code)) {
            $errors[] = 'Enter the 6-digit code from your email.';
        } else {
            $result = otp_verify($pendingId, $code);
            switch ($result['status']) {
                case 'ok':
                    unset($_SESSION['pending_user_id']);
                    flash('success', 'Email verified. You can now log in.');
                    redirect('login.php');
                    break;
                case 'invalid':
                    $errors[] = $result['remaining'] > 0
                        ? 'That code is incorrect. Attempts left: ' . $result['remaining'] . '.'
                        : 'That code is incorrect and you have no attempts left. Please request a new code.';
                    break;
                case 'locked':
                    $errors[] = 'Too many incorrect attempts. Please request a new code.';
                    break;
                case 'expired':
                    $errors[] = 'This code has expired. Please request a new code.';
                    break;
                default:
                    $errors[] = 'No active code found. Please request a new code.';
            }
        }
    }
}

$cooldown = otp_cooldown_remaining($pendingId);

$documentTitle = 'Verify email';
$authHeading   = 'Verify your email';
$authSubtitle  = 'Enter the 6-digit code we sent to ' . $pendingUser['email'] . '. It expires in ' . OTP_EXPIRY_MINUTES . ' minutes.';
require __DIR__ . '/includes/auth_header.php';
?>

<?= errors_render($errors) ?>

<form method="post" action="<?= e(url('verify-otp.php')) ?>" class="space-y-5" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="verify">

    <div>
        <label for="otp" class="mb-1 block text-sm font-medium">Verification code</label>
        <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
               data-digits-only class="field text-center text-2xl tracking-[0.5em]" placeholder="000000" required autofocus>
    </div>

    <button type="submit" class="w-full rounded-lg bg-secondary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-ink focus:outline-none focus:ring-4 focus:ring-primary/40">
        Verify email
    </button>
</form>

<form method="post" action="<?= e(url('verify-otp.php')) ?>" class="mt-6 text-center">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="resend">
    <p class="mb-2 text-sm text-muted">Did not receive the code?</p>
    <button type="submit" data-countdown="<?= (int) $cooldown ?>" data-label="Resend code"
            class="text-sm font-medium text-secondary hover:underline">Resend code</button>
</form>

<p class="mt-6 text-center text-sm text-muted">
    <a href="<?= e(url('login.php')) ?>" class="font-medium text-secondary hover:underline">Back to log in</a>
</p>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>
