<?php
/** Create a new salonist, or edit one when ?id= is given. */
require_once __DIR__ . '/../includes/init.php';

$user = require_role('admin');
$pdo  = db();

$editId   = (int) ($_GET['id'] ?? 0);
$salonist = null;

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'salonist'");
    $stmt->execute([$editId]);
    $salonist = $stmt->fetch();
    if (!$salonist) {
        flash('error', 'Salonist not found.');
        redirect('admin/salonists.php');
    }
}
$isEdit = $salonist !== null;
$errors = [];

if (is_post()) {
    csrf_verify();

    $fullName  = preg_replace('/\s+/', ' ', post('full_name'));
    $email     = strtolower(post('email'));
    $phone     = normalize_phone(post('phone'));
    $specialty = mb_substr(post('specialty'), 0, 120);
    $password  = (string) ($_POST['password'] ?? '');
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
        $errors[] = 'Please enter the salonist\'s full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!valid_phone($phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (!$isEdit || $password !== '') {
        if ($problem = password_problem($password)) {
            $errors[] = $problem;
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([$email, $isEdit ? $salonist['id'] : 0]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errors[] = 'That email address is already used by another account.';
        }
    }

    if (!$errors) {
        $spec = $specialty !== '' ? $specialty : null;

        if ($isEdit) {
            $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, specialty = ?, is_active = ? WHERE id = ? AND role = \'salonist\'')
                ->execute([$fullName, $email, $phone, $spec, $isActive, $salonist['id']]);
            if ($password !== '') {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $salonist['id']]);
            }
            flash('success', 'Salonist account updated.');
        } else {
            // Staff accounts are created by the administrator, so the email counts as verified.
            $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, specialty, is_active, email_verified_at) VALUES (?, ?, ?, ?, 'salonist', ?, ?, ?)")
                ->execute([$fullName, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $spec, $isActive, date('Y-m-d H:i:s')]);
            flash('success', 'Salonist account created. Share the login details with them securely.');
        }
        redirect('admin/salonists.php');
    }
}

// Values shown in the form: submitted values first, then saved values, then blanks.
$val = function (string $key, string $column) use ($salonist) {
    if (is_post()) {
        return old($key);
    }
    return e($salonist[$column] ?? '');
};
$activeChecked = is_post() ? isset($_POST['is_active']) : (!$isEdit || (int) $salonist['is_active'] === 1);

$pageTitle    = $isEdit ? 'Edit Salonist' : 'Add Salonist';
$pageSubtitle = $isEdit ? 'Update this salonist\'s account.' : 'Create an account for a new stylist.';
$activePage   = 'salonists';
require __DIR__ . '/../includes/dashboard_header.php';
$formAction = 'admin/salonist_form.php' . ($isEdit ? '?id=' . (int) $salonist['id'] : '');
?>

<?= errors_render($errors) ?>

<form method="post" action="<?= e(url($formAction)) ?>" class="max-w-xl space-y-5" novalidate>
    <?= csrf_field() ?>

    <div>
        <label for="full_name" class="mb-1 block text-sm font-medium">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?= $val('full_name', 'full_name') ?>" class="field" maxlength="120" required>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email address</label>
            <input type="email" id="email" name="email" value="<?= $val('email', 'email') ?>" class="field" maxlength="190" required>
        </div>
        <div>
            <label for="phone" class="mb-1 block text-sm font-medium">Phone number</label>
            <input type="tel" id="phone" name="phone" value="<?= $val('phone', 'phone') ?>" class="field" required>
        </div>
    </div>

    <div>
        <label for="specialty" class="mb-1 block text-sm font-medium">Specialty <span class="font-normal text-muted">(optional)</span></label>
        <input type="text" id="specialty" name="specialty" value="<?= $val('specialty', 'specialty') ?>" class="field" maxlength="120" placeholder="e.g. Braids and natural hair">
    </div>

    <div>
        <label for="password" class="mb-1 block text-sm font-medium"><?= $isEdit ? 'New password' : 'Temporary password' ?><?= $isEdit ? ' <span class="font-normal text-muted">(leave blank to keep the current one)</span>' : '' ?></label>
        <input type="password" id="password" name="password" class="field" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
        <p class="mt-1 text-xs text-muted">At least 8 characters, with letters and numbers. The salonist can change it from their profile.</p>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" <?= $activeChecked ? 'checked' : '' ?> class="h-4 w-4 rounded border-line text-secondary focus:ring-primary">
        Account is active (can log in and receive appointments)
    </label>

    <div class="flex items-center gap-4">
        <button type="submit" class="rounded-lg bg-secondary px-6 py-2.5 text-sm font-semibold text-white hover:bg-ink"><?= $isEdit ? 'Save changes' : 'Create account' ?></button>
        <a href="<?= e(url('admin/salonists.php')) ?>" class="text-sm font-medium text-muted hover:text-secondary">Cancel</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
