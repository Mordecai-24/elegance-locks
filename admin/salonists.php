<?php
require_once __DIR__ . '/../includes/init.php';

$user = require_role('admin');
$pdo  = db();

// ---- Activate / deactivate ----
if (is_post() && post('action') === 'toggle') {
    csrf_verify();
    $id = (int) ($_POST['salonist_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'salonist'");
    $stmt->execute([$id]);
    $salonist = $stmt->fetch();

    if (!$salonist) {
        flash('error', 'Salonist not found.');
    } else {
        $newState = (int) $salonist['is_active'] === 1 ? 0 : 1;
        $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$newState, $id]);
        flash('success', $salonist['full_name'] . ($newState ? ' has been activated.' : ' has been deactivated.'));
    }
    redirect('admin/salonists.php');
}

$salonists = $pdo->query(
    "SELECT u.*, (SELECT COUNT(*) FROM appointments a WHERE a.salonist_id = u.id AND a.status IN ('pending','confirmed','in_progress')) AS active_count
     FROM users u WHERE u.role = 'salonist' ORDER BY u.full_name"
)->fetchAll();

$pageTitle    = 'Salonists';
$pageSubtitle = 'Create and manage salonist accounts.';
$activePage   = 'salonists';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="mb-5 flex justify-end">
    <a href="<?= e(url('admin/salonist_form.php')) ?>" class="inline-flex items-center gap-2 rounded-lg bg-secondary px-4 py-2.5 text-sm font-semibold text-white hover:bg-ink">
        <?= icon('plus', 'h-4 w-4') ?> Add salonist
    </a>
</div>

<?php if ($salonists): ?>
    <div class="table-wrap rounded-xl border border-line bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-line text-xs uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Specialty</th>
                    <th class="px-4 py-3">Open appointments</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($salonists as $s): ?>
                    <tr class="align-top">
                        <td class="px-4 py-3 font-medium"><?= e($s['full_name']) ?></td>
                        <td class="px-4 py-3"><?= e($s['email']) ?><br><span class="text-xs text-muted"><?= e($s['phone']) ?></span></td>
                        <td class="px-4 py-3"><?= $s['specialty'] ? e($s['specialty']) : '<span class="text-muted">&mdash;</span>' ?></td>
                        <td class="px-4 py-3"><?= (int) $s['active_count'] ?></td>
                        <td class="px-4 py-3">
                            <?php if ((int) $s['is_active'] === 1): ?>
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">Active</span>
                            <?php else: ?>
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-4">
                                <a href="<?= e(url('admin/salonist_form.php?id=' . (int) $s['id'])) ?>" class="font-medium text-secondary hover:underline">Edit</a>
                                <form method="post" action="<?= e(url('admin/salonists.php')) ?>"
                                      data-confirm="<?= (int) $s['is_active'] === 1 ? 'Deactivate this salonist? They will not be able to log in.' : 'Activate this salonist?' ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="salonist_id" value="<?= (int) $s['id'] ?>">
                                    <button type="submit" class="font-medium <?= (int) $s['is_active'] === 1 ? 'text-red-600' : 'text-emerald-700' ?> hover:underline">
                                        <?= (int) $s['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="rounded-xl border border-dashed border-line bg-white px-6 py-12 text-center">
        <p class="font-medium">No salonists yet</p>
        <p class="mt-1 text-sm text-muted">Create an account so appointments can be assigned to a stylist.</p>
        <a href="<?= e(url('admin/salonist_form.php')) ?>" class="mt-4 inline-block rounded-lg bg-secondary px-5 py-2.5 text-sm font-semibold text-white hover:bg-ink">Add the first salonist</a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
