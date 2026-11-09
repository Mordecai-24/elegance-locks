<?php
/**
 * Authentication and authorization.
 * The role always comes from the database, never from the browser.
 */

function dashboard_path(string $role): string
{
    return match ($role) {
        'admin'    => 'admin/dashboard.php',
        'salonist' => 'salonist/dashboard.php',
        default    => 'customer/dashboard.php',
    };
}

/** Returns the logged-in user row, or null. Re-checks the database on every request. */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }

    $user = null;
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    // Log out after a period of inactivity.
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_IDLE_SECONDS) {
        logout_user();
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $row = $stmt->fetch();

    $valid = $row
        && (int) $row['is_active'] === 1
        && ($row['role'] !== 'customer' || $row['email_verified_at'] !== null);

    if (!$valid) {
        logout_user();
        return null;
    }

    $_SESSION['last_activity'] = time();
    $user = $row;
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['last_activity'] = time();
    unset($_SESSION['pending_user_id']);
    db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')
        ->execute([date('Y-m-d H:i:s'), $user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

/** Protects a page: the visitor must be logged in with the given role. */
function require_role(string $role): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
    if ($user['role'] !== $role) {
        redirect(dashboard_path($user['role']));
    }
    return $user;
}

/** For login/register pages: send logged-in visitors to their dashboard. */
function guest_only(): void
{
    $user = current_user();
    if ($user) {
        redirect(dashboard_path($user['role']));
    }
}

// ---------- Login throttling ----------

function login_is_locked(string $email): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_LOCK_MINUTES * 60);

    // Too many failures for this email address
    $stmt = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at >= ?');
    $stmt->execute([$email, $since]);
    if ((int) $stmt->fetchColumn() >= LOGIN_MAX_FAILURES) {
        return true;
    }

    // Very many failures from one IP address (higher limit, because many people can share one IP)
    $stmt = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?');
    $stmt->execute([client_ip(), $since]);
    return (int) $stmt->fetchColumn() >= LOGIN_MAX_FAILURES_PER_IP;
}

function login_record_failure(string $email): void
{
    db()->prepare('INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?, ?, ?)')
        ->execute([$email, client_ip(), date('Y-m-d H:i:s')]);
}

function login_clear_failures(string $email): void
{
    db()->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$email]);
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}
