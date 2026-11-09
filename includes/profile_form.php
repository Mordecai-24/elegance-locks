<?php
/**
 * Profile page content (details form + password form).
 * Expects: $user, $errors, $selfPath.
 */
$val = fn(string $key, ?string $fallback) => isset($_POST[$key]) ? old($key) : e($fallback);
?>
<?= errors_render($errors) ?>

<div class="grid gap-10 xl:grid-cols-2">

    <section>
        <h2 class="font-serif text-xl font-semibold">Personal details</h2>
        <p class="mb-5 mt-1 text-sm text-muted">Your email address is used to log in and cannot be changed here.</p>

        <form method="post" action="<?= e(url($selfPath)) ?>" class="max-w-md space-y-4" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="details">

            <div>
                <label for="full_name" class="mb-1 block text-sm font-medium">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?= $val('full_name', $user['full_name']) ?>" class="field" maxlength="120" required>
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email address</label>
                <input type="email" id="email" value="<?= e($user['email']) ?>" class="field bg-surface text-muted" disabled>
            </div>
            <div>
                <label for="phone" class="mb-1 block text-sm font-medium">Phone number</label>
                <input type="tel" id="phone" name="phone" value="<?= $val('phone', $user['phone']) ?>" class="field" required>
            </div>
            <?php if ($user['role'] === 'salonist'): ?>
                <div>
                    <label for="specialty" class="mb-1 block text-sm font-medium">Specialty</label>
                    <input type="text" id="specialty" name="specialty" value="<?= $val('specialty', $user['specialty']) ?>" class="field" maxlength="120" placeholder="e.g. Braids and natural hair">
                </div>
            <?php endif; ?>

            <button type="submit" class="rounded-lg bg-secondary px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-ink">Save changes</button>
        </form>
    </section>

    <section>
        <h2 class="font-serif text-xl font-semibold">Change password</h2>
        <p class="mb-5 mt-1 text-sm text-muted">Use at least 8 characters with letters and numbers.</p>

        <form method="post" action="<?= e(url($selfPath)) ?>" class="max-w-md space-y-4" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">

            <div>
                <label for="current_password" class="mb-1 block text-sm font-medium">Current password</label>
                <input type="password" id="current_password" name="current_password" class="field" autocomplete="current-password" required>
            </div>
            <div>
                <label for="new_password" class="mb-1 block text-sm font-medium">New password</label>
                <input type="password" id="new_password" name="new_password" class="field" autocomplete="new-password" required>
            </div>
            <div>
                <label for="confirm_password" class="mb-1 block text-sm font-medium">Confirm new password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="field" autocomplete="new-password" required>
            </div>

            <button type="submit" class="rounded-lg border border-secondary px-5 py-2.5 text-sm font-semibold text-secondary transition-colors hover:bg-secondary hover:text-white">Update password</button>
        </form>
    </section>
</div>
