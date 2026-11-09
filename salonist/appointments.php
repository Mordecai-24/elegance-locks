<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/appointments.php';

$user  = require_role('salonist');
$pdo   = db();
$today = date('Y-m-d');
$labels = status_labels();

// ---- Update status (only on appointments assigned to this salonist) ----
if (is_post() && post('action') === 'status') {
    csrf_verify();

    $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ? AND salonist_id = ?');
    $stmt->execute([(int) ($_POST['appointment_id'] ?? 0), $user['id']]);
    $appt = $stmt->fetch();
    $new  = post('new_status');

    if (!$appt) {
        flash('error', 'Appointment not found.');
    } elseif (!in_array($new, salonist_next_statuses($appt['status']), true)) {
        flash('error', 'That status change is not allowed.');
    } elseif (in_array($new, ['in_progress', 'completed'], true) && $appt['appointment_date'] > $today) {
        flash('error', 'You cannot start or complete an appointment before its date.');
    } else {
        $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ? AND salonist_id = ?')
            ->execute([$new, $appt['id'], $user['id']]);
        flash('success', 'Appointment marked as ' . strtolower($labels[$new]) . '.');
    }
    redirect('salonist/appointments.php' . (isset($_GET['view']) ? '?view=' . urlencode($_GET['view']) : ''));
}

// ---- Filter ----
$view = $_GET['view'] ?? 'upcoming';
if (!in_array($view, ['today', 'upcoming', 'past', 'all'], true)) {
    $view = 'upcoming';
}

$sql = appointment_select_sql() . 'WHERE a.salonist_id = ?';
$params = [$user['id']];
$order = 'ORDER BY a.appointment_date, a.start_time';
if ($view === 'today') {
    $sql .= ' AND a.appointment_date = ?';
    $params[] = $today;
} elseif ($view === 'upcoming') {
    $sql .= " AND a.appointment_date >= ? AND a.status NOT IN ('cancelled','completed')";
    $params[] = $today;
} elseif ($view === 'past') {
    $sql .= " AND (a.appointment_date < ? OR a.status IN ('cancelled','completed'))";
    $params[] = $today;
    $order = 'ORDER BY a.appointment_date DESC, a.start_time DESC';
} else {
    $order = 'ORDER BY a.appointment_date DESC, a.start_time DESC';
}
$stmt = $pdo->prepare("$sql $order");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle    = 'Appointments';
$pageSubtitle = 'Appointments assigned to you.';
$activePage   = 'appointments';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="mb-5 flex flex-wrap gap-2 text-sm">
    <?php foreach (['today' => 'Today', 'upcoming' => 'Upcoming', 'past' => 'Past and closed', 'all' => 'All'] as $key => $label): ?>
        <a href="<?= e(url('salonist/appointments.php?view=' . $key)) ?>"
           class="rounded-full px-3 py-1.5 font-medium <?= $view === $key ? 'bg-secondary text-white' : 'border border-line bg-white text-muted hover:text-secondary' ?>">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($appointments): ?>
    <div class="table-wrap rounded-xl border border-line bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-line text-xs uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-4 py-3">Date and time</th>
                    <th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Notes</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"><span class="sr-only">Update</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($appointments as $a): ?>
                    <?php $next = salonist_next_statuses($a['status']); ?>
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-4 py-3"><?= e(format_date($a['appointment_date'])) ?><br><span class="text-muted"><?= e(format_time($a['start_time'])) ?> &ndash; <?= e(format_time($a['end_time'])) ?></span></td>
                        <td class="px-4 py-3 font-medium"><?= e($a['service_name']) ?></td>
                        <td class="px-4 py-3">
                            <p><?= e($a['customer_name']) ?></p>
                            <p class="text-xs text-muted"><?= e($a['customer_phone']) ?><br><?= e($a['customer_email']) ?></p>
                        </td>
                        <td class="max-w-xs px-4 py-3 text-muted"><?= $a['notes'] ? e($a['notes']) : '&mdash;' ?></td>
                        <td class="px-4 py-3"><?= status_badge($a['status']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php foreach ($next as $n): ?>
                                <form method="post" action="<?= e(url('salonist/appointments.php?view=' . $view)) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= e($n) ?>">
                                    <button type="submit" class="whitespace-nowrap rounded-lg border border-secondary px-3 py-1.5 text-xs font-semibold text-secondary hover:bg-secondary hover:text-white">
                                        Mark <?= e(strtolower($labels[$n])) ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="rounded-xl border border-dashed border-line bg-white px-6 py-12 text-center">
        <p class="font-medium">No appointments here</p>
        <p class="mt-1 text-sm text-muted">Nothing is assigned to you for this view yet.</p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
