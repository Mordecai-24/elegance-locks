<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/otp.php';

guest_only();

$errors = [];

if (is_post()) {
    csrf_verify();

    $fullName = preg_replace('/\s+/', ' ', post('full_name'));
    $email    = strtolower(post('email'));
    $phone    = normalize_phone(post('phone'));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');

    // ---- Server-side validation ----
    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
        $errors[] = 'Please enter your full name (2 to 120 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!valid_phone($phone)) {
        $errors[] = 'Please enter a valid phone number (9 to 15 digits, optional + at the start).';
    }
    if ($problem = password_problem($password)) {
        $errors[] = $problem;
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, role, email_verified_at FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing && ($existing['role'] !== 'customer' || $existing['email_verified_at'] !== null)) {
            $errors[] = 'An account with this email already exists. Please log in instead.';
        } elseif ($existing) {
            // Registered earlier but never verified: refresh the details and send a new code.
            db()->prepare("UPDATE users SET full_name = ?, phone = ?, password_hash = ? WHERE id = ? AND role = 'customer' AND email_verified_at IS NULL")
                ->execute([$fullName, $phone, password_hash($password, PASSWORD_DEFAULT), $existing['id']]);
            $userId = (int) $existing['id'];
        } else {
            db()->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'customer')")
                ->execute([$fullName, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int) db()->lastInsertId();
        }

        if (!$errors) {
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $newUser = $stmt->fetch();

            session_regenerate_id(true);
            $_SESSION['pending_user_id'] = $userId;

            $mailError = otp_issue($newUser);
            if ($mailError === null) {
                flash('success', 'Account created. We sent a 6-digit code to ' . $email . '.');
            } else {
                flash('error', $mailError . ' You can request a new code on the next page.');
            }
            redirect('verify-otp.php');
        }
    }
}

$documentTitle = 'Create account';
$authHeading   = 'Create your account';
$authSubtitle  = 'Register as a customer to book appointments online.';
require __DIR__ . '/includes/auth_header.php';
?>

<?= errors_render($errors) ?>

<form method="post" action="<?= e(url('register.php')) ?>" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <div>
        <label for="full_name" class="mb-1 block text-sm font-medium">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?= old('full_name') ?>" class="field" autocomplete="name" maxlength="120" required autofocus>
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-medium">Email address</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" class="field" autocomplete="email" maxlength="190" required>
    </div>

    <div>
        <label for="phone" class="mb-1 block text-sm font-medium">Phone number</label>
        <input type="tel" id="phone" name="phone" value="<?= old('phone') ?>" class="field" autocomplete="tel" placeholder="e.g. 0712345678" required>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input type="password" id="password" name="password" class="field" autocomplete="new-password" required>
        </div>
        <div>
            <label for="confirm_password" class="mb-1 block text-sm font-medium">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="field" autocomplete="new-password" required>
        </div>
    </div>
    <p class="text-xs text-muted">At least 8 characters, with letters and numbers.</p>

    <button type="submit" class="w-full rounded-lg bg-secondary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-ink focus:outline-none focus:ring-4 focus:ring-primary/40">
        Create account
    </button>
</form>

<p class="mt-6 text-center text-sm text-muted">
    Already have an account?
    <a href="<?= e(url('login.php')) ?>" class="font-medium text-secondary hover:underline">Log in</a>
</p>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>
