<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/appointments.php';

$user = require_role('customer');
$pdo  = db();

// ---- Cancel an appointment (ownership and rules are checked on the server) ----
if (is_post() && post('action') === 'cancel') {
    csrf_verify();

    $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ? AND customer_id = ?');
    $stmt->execute([(int) ($_POST['appointment_id'] ?? 0), $user['id']]);
    $appt = $stmt->fetch();

    if (!$appt) {
        flash('error', 'Appointment not found.');
    } elseif (!customer_can_cancel($appt)) {
        flash('error', 'This appointment can no longer be cancelled online. Please contact the salon.');
    } else {
        $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND customer_id = ?")
            ->execute([$appt['id'], $user['id']]);
        flash('success', 'Your appointment has been cancelled.');
    }
    redirect('customer/appointments.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

// ---- Filter ----
$labels = status_labels();
$filter = $_GET['status'] ?? 'all';
if ($filter !== 'all' && !isset($labels[$filter])) {
    $filter = 'all';
}

$sql    = appointment_select_sql() . 'WHERE a.customer_id = ?';
$params = [$user['id']];
if ($filter !== 'all') {
    $sql .= ' AND a.status = ?';
    $params[] = $filter;
}
$stmt = $pdo->prepare($sql . ' ORDER BY a.appointment_date DESC, a.start_time DESC');
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle    = 'My Appointments';
$pageSubtitle = 'Your appointment history and current status.';
$activePage   = 'appointments';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="mb-5 flex flex-wrap items-center gap-2 text-sm">
    <?php foreach (['all' => 'All'] + $labels as $key => $label): ?>
        <a href="<?= e(url('customer/appointments.php?status=' . $key)) ?>"
           class="rounded-full px-3 py-1.5 font-medium <?= $filter === $key ? 'bg-secondary text-white' : 'border border-line bg-white text-muted hover:text-secondary' ?>">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
    <a href="<?= e(url('customer/book.php')) ?>" class="ml-auto rounded-lg bg-secondary px-4 py-2 font-semibold text-white hover:bg-ink">Book appointment</a>
</div>

<?php if ($appointments): ?>
    <div class="table-wrap rounded-xl border border-line bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-line text-xs uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Date and time</th>
                    <th class="px-4 py-3">Stylist</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($appointments as $a): ?>
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <p class="font-medium"><?= e($a['service_name']) ?></p>
                            <?php if ($a['notes']): ?><p class="mt-0.5 max-w-xs text-xs text-muted"><?= e($a['notes']) ?></p><?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3"><?= e(format_date($a['appointment_date'])) ?><br><span class="text-muted"><?= e(format_time($a['start_time'])) ?></span></td>
                        <td class="px-4 py-3"><?= e($a['salonist_name'] ?? 'To be assigned') ?></td>
                        <td class="whitespace-nowrap px-4 py-3"><?= e(format_price($a['service_price'])) ?></td>
                        <td class="px-4 py-3"><?= status_badge($a['status']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if (customer_can_cancel($a)): ?>
                                <form method="post" action="<?= e(url('customer/appointments.php' . ($filter !== 'all' ? '?status=' . $filter : ''))) ?>" data-confirm="Cancel this appointment?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                                    <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-muted">Appointments can be cancelled online up to <?= CANCEL_MIN_HOURS ?> hours before the start time while they are pending or confirmed.</p>
<?php else: ?>
    <div class="rounded-xl border border-dashed border-line bg-white px-6 py-12 text-center">
        <p class="font-medium">No appointments found</p>
        <p class="mt-1 text-sm text-muted"><?= $filter === 'all' ? 'You have not booked anything yet.' : 'You have no appointments with this status.' ?></p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
