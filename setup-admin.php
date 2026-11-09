<?php
/**
 * One-time creation of the first administrator.
 *
 * Safety rules:
 *  - Works only while NO administrator exists. Afterwards the page is locked.
 *  - Requires SETUP_KEY from config/config.php, so strangers cannot use it.
 */
require_once __DIR__ . '/includes/init.php';

$adminExists = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;

if ($adminExists) {
    http_response_code(403);
    $documentTitle = 'Setup locked';
    $authHeading   = 'Setup is locked';
    $authSubtitle  = 'An administrator already exists. Log in, or create more administrators directly in the database if needed.';
    require __DIR__ . '/includes/auth_header.php';
    echo '<a href="' . e(url('login.php')) . '" class="font-medium text-secondary hover:underline">Go to log in</a>';
    require __DIR__ . '/includes/auth_footer.php';
    exit;
}

$keyIsDefault = SETUP_KEY === 'CHANGE-THIS-SETUP-KEY';
$errors = [];

if ($keyIsDefault) {
    $errors[] = 'Before using this page, open config/config.php and change SETUP_KEY to your own private value.';
}

if (is_post() && !$keyIsDefault) {
    csrf_verify();

    $setupKey = (string) ($_POST['setup_key'] ?? '');
    $fullName = preg_replace('/\s+/', ' ', post('full_name'));
    $email    = strtolower(post('email'));
    $phone    = normalize_phone(post('phone'));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');

    if (!hash_equals(SETUP_KEY, $setupKey)) {
        $errors[] = 'The setup key is incorrect.';
    }
    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
        $errors[] = 'Please enter the administrator\'s full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!valid_phone($phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if ($problem = password_problem($password)) {
        $errors[] = $problem;
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errors[] = 'That email address is already in use.';
        }
    }

    if (!$errors) {
        db()->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, email_verified_at) VALUES (?, ?, ?, ?, 'admin', ?)")
            ->execute([$fullName, $email, $phone, password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
        flash('success', 'Administrator created. You can now log in. This setup page is now locked.');
        redirect('login.php');
    }
}

$documentTitle = 'First administrator';
$authHeading   = 'Create the first administrator';
$authSubtitle  = 'This page works only once and needs your private setup key.';
require __DIR__ . '/includes/auth_header.php';
?>

<?= errors_render($errors) ?>

<?php if (!$keyIsDefault): ?>
<form method="post" action="<?= e(url('setup-admin.php')) ?>" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <div>
        <label for="setup_key" class="mb-1 block text-sm font-medium">Setup key</label>
        <input type="password" id="setup_key" name="setup_key" class="field" autocomplete="off" required>
    </div>
    <div>
        <label for="full_name" class="mb-1 block text-sm font-medium">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?= old('full_name') ?>" class="field" required>
    </div>
    <div>
        <label for="email" class="mb-1 block text-sm font-medium">Email address</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" class="field" required>
    </div>
    <div>
        <label for="phone" class="mb-1 block text-sm font-medium">Phone number</label>
        <input type="tel" id="phone" name="phone" value="<?= old('phone') ?>" class="field" required>
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

    <button type="submit" class="w-full rounded-lg bg-secondary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-ink">
        Create administrator
    </button>
</form>
<?php endif; ?>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>
