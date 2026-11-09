<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/otp.php';

guest_only();

$errors = [];

if (is_post()) {
    csrf_verify();

    $email    = strtolower(post('email'));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter your email and password.';
    } elseif (login_is_locked($email)) {
        $errors[] = 'Too many failed attempts. Please try again in ' . LOGIN_LOCK_MINUTES . ' minutes.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $found = $stmt->fetch();

        // Same message for "no such user" and "wrong password" so emails cannot be guessed.
        if (!$found || !password_verify($password, $found['password_hash'])) {
            login_record_failure($email);
            $errors[] = 'Incorrect email or password.';
        } elseif ((int) $found['is_active'] !== 1) {
            $errors[] = 'This account has been deactivated. Please contact the salon.';
        } elseif ($found['role'] === 'customer' && $found['email_verified_at'] === null) {
            // Correct password, but the email is not verified yet: continue to OTP verification.
            session_regenerate_id(true);
            $_SESSION['pending_user_id'] = (int) $found['id'];
            flash('info', 'Please verify your email address before logging in.');
            redirect('verify-otp.php');
        } else {
            login_clear_failures($email);
            login_user($found);
            redirect(dashboard_path($found['role']));
        }
    }
}

$documentTitle = 'Log in';
$authHeading   = 'Welcome back';
$authSubtitle  = 'Log in to manage your appointments.';
require __DIR__ . '/includes/auth_header.php';
?>

<?= errors_render($errors) ?>

<form method="post" action="<?= e(url('login.php')) ?>" class="space-y-5" novalidate>
    <?= csrf_field() ?>

    <div>
        <label for="email" class="mb-1 block text-sm font-medium">Email address</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" class="field" autocomplete="email" required autofocus>
    </div>

    <div>
        <div class="mb-1 flex items-center justify-between">
            <label for="password" class="block text-sm font-medium">Password</label>
            <button type="button" data-toggle-password="#password" class="text-xs font-medium text-secondary hover:underline">Show</button>
        </div>
        <input type="password" id="password" name="password" class="field" autocomplete="current-password" required>
    </div>

    <button type="submit" class="w-full rounded-lg bg-secondary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-ink focus:outline-none focus:ring-4 focus:ring-primary/40">
        Log in
    </button>
</form>

<p class="mt-6 text-center text-sm text-muted">
    New to <?= e(APP_NAME) ?>?
    <a href="<?= e(url('register.php')) ?>" class="font-medium text-secondary hover:underline">Create a customer account</a>
</p>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>
