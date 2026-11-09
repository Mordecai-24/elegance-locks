<?php
/**
 * Profile editing shared by customers, salonists and administrators.
 * handle_profile_post() processes the two forms (details, password).
 */

/** Processes a profile POST. Returns a list of errors; on success it flashes a message and redirects. */
function handle_profile_post(array $user, string $selfPath): array
{
    csrf_verify();
    $errors = [];
    $action = post('action');

    if ($action === 'details') {
        $fullName = preg_replace('/\s+/', ' ', post('full_name'));
        $phone    = normalize_phone(post('phone'));
        $specialty = $user['role'] === 'salonist' ? mb_substr(post('specialty'), 0, 120) : null;

        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
            $errors[] = 'Please enter your full name (2 to 120 characters).';
        }
        if (!valid_phone($phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }

        if (!$errors) {
            if ($user['role'] === 'salonist') {
                db()->prepare('UPDATE users SET full_name = ?, phone = ?, specialty = ? WHERE id = ?')
                    ->execute([$fullName, $phone, $specialty !== '' ? $specialty : null, $user['id']]);
            } else {
                db()->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?')
                    ->execute([$fullName, $phone, $user['id']]);
            }
            flash('success', 'Your profile has been updated.');
            redirect($selfPath);
        }
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Your current password is incorrect.';
        }
        if ($problem = password_problem($new)) {
            $errors[] = $problem;
        }
        if ($new !== $confirm) {
            $errors[] = 'The new passwords do not match.';
        }

        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('success', 'Your password has been changed.');
            redirect($selfPath);
        }
    }

    return $errors;
}
