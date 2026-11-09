<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/appointments.php';

$user   = require_role('admin');
$pdo    = db();
$labels = status_labels();

// Keep the current filters when the page reloads after an action.
$filterStatus = $_GET['status'] ?? 'all';
if ($filterStatus !== 'all' && !isset($labels[$filterStatus])) {
    $filterStatus = 'all';
}
$filterDate = valid_date((string) ($_GET['date'] ?? '')) ? $_GET['date'] : '';
$query = http_build_query(array_filter(['status' => $filterStatus !== 'all' ? $filterStatus : '', 'date' => $filterDate]));
$selfUrl = 'admin/appointments.php' . ($query ? '?' . $query : '');

// ---- Assign a salonist and/or change the status ----
if (is_post() && post('action') === 'update') {
    csrf_verify();

    $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
    $stmt->execute([(int) ($_POST['appointment_id'] ?? 0)]);
    $appt = $stmt->fetch();

    $newStatus   = post('status');
    $salonistId  = (int) ($_POST['salonist_id'] ?? 0);
    $error = null;

    if (!$appt) {
        $error = 'Appointment not found.';
    } elseif (!isset($labels[$newStatus])) {
        $error = 'Please choose a valid status.';
    } else {
        if ($salonistId > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'salonist' AND is_active = 1");
            $stmt->execute([$salonistId]);
            if ((int) $stmt->fetchColumn() === 0) {
                $error = 'The selected salonist is not available.';
            }
        }

        $isActiveStatus = in_array($newStatus, ACTIVE_STATUSES, true);

        if (!$error && $newStatus !== 'pending' && $newStatus !== 'cancelled' && $salonistId === 0) {
            $error = 'Assign a salonist before confirming, starting or completing an appointment.';
        }
        if (!$error && $salonistId > 0 && $isActiveStatus
            && salonist_has_conflict($salonistId, $appt['appointment_date'], $appt['start_time'], $appt['end_time'], (int) $appt['id'])) {
            $error = 'That salonist already has an appointment that overlaps with this time.';
        }
    }

    if ($error) {
        flash('error', $error);
    } else {
        $pdo->prepare('UPDATE appointments SET salonist_id = ?, status = ? WHERE id = ?')
            ->execute([$salonistId > 0 ? $salonistId : null, $newStatus, $appt['id']]);
        flash('success', 'Appointment updated.');
    }
    redirect($selfUrl);
}

// ---- Quick cancel ----
if (is_post() && post('action') === 'cancel') {
    csrf_verify();
    $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?")->execute([(int) ($_POST['appointment_id'] ?? 0)]);
    flash('success', 'Appointment cancelled.');
    redirect($selfUrl);
}

// ---- List ----
$sql = appointment_select_sql() . 'WHERE 1 = 1';
$params = [];
if ($filterStatus !== 'all') {
    $sql .= ' AND a.status = ?';
    $params[] = $filterStatus;
}
if ($filterDate !== '') {
    $sql .= ' AND a.appointment_date = ?';
    $params[] = $filterDate;
}
$stmt = $pdo->prepare($sql . ' ORDER BY a.appointment_date DESC, a.start_time DESC');
$stmt->execute($params);
$appointments = $stmt->fetchAll();
$salonists = active_salonists();

$pageTitle    = 'Appointments';
$pageSubtitle = 'Assign stylists and keep every booking up to date.';
$activePage   = 'appointments';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<form method="get" action="<?= e(url('admin/appointments.php')) ?>" class="mb-5 flex flex-wrap items-end gap-3">
    <div>
        <label for="status" class="mb-1 block text-xs font-medium text-muted">Status</label>
        <select id="status" name="status" class="field">
            <option value="all">All statuses</option>
            <?php foreach ($labels as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $filterStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="date" class="mb-1 block text-xs font-medium text-muted">Date</label>
        <input type="date" id="date" name="date" value="<?= e($filterDate) ?>" class="field">
    </div>
    <button type="submit" class="rounded-lg bg-secondary px-5 py-2.5 text-sm font-semibold text-white hover:bg-ink">Filter</button>
    <?php if ($query): ?>
        <a href="<?= e(url('admin/appointments.php')) ?>" class="py-2.5 text-sm font-medium text-muted hover:text-secondary">Clear</a>
    <?php endif; ?>
</form>

<?php if ($appointments): ?>
    <div class="table-wrap rounded-xl border border-line bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-line text-xs uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-4 py-3">Date and time</th>
                    <th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Assign salonist and status</th>
                    <th class="px-4 py-3"><span class="sr-only">Cancel</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($appointments as $a): ?>
                    <?php $formId = 'appt-' . (int) $a['id']; ?>
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-4 py-3"><?= e(format_date($a['appointment_date'])) ?><br><span class="text-muted"><?= e(format_time($a['start_time'])) ?> &ndash; <?= e(format_time($a['end_time'])) ?></span></td>
                        <td class="px-4 py-3">
                            <p class="font-medium"><?= e($a['service_name']) ?></p>
                            <p class="text-xs text-muted"><?= e(format_price($a['service_price'])) ?></p>
                            <?php if ($a['notes']): ?><p class="mt-1 max-w-[14rem] text-xs text-muted">Note: <?= e($a['notes']) ?></p><?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?= e($a['customer_name']) ?><br><span class="text-xs text-muted"><?= e($a['customer_phone']) ?></span></td>
                        <td class="px-4 py-3">
                            <form id="<?= e($formId) ?>" method="post" action="<?= e(url($selfUrl)) ?>" class="flex flex-wrap items-center gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                                <label class="sr-only" for="sal-<?= (int) $a['id'] ?>">Salonist</label>
                                <select id="sal-<?= (int) $a['id'] ?>" name="salonist_id" class="field w-44 py-1.5">
                                    <option value="0">Unassigned</option>
                                    <?php foreach ($salonists as $s): ?>
                                        <option value="<?= (int) $s['id'] ?>" <?= (int) $a['salonist_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="sr-only" for="st-<?= (int) $a['id'] ?>">Status</label>
                                <select id="st-<?= (int) $a['id'] ?>" name="status" class="field w-36 py-1.5">
                                    <?php foreach ($labels as $key => $label): ?>
                                        <option value="<?= e($key) ?>" <?= $a['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="rounded-lg border border-secondary px-3 py-1.5 text-xs font-semibold text-secondary hover:bg-secondary hover:text-white">Save</button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if (in_array($a['status'], ACTIVE_STATUSES, true)): ?>
                                <form method="post" action="<?= e(url($selfUrl)) ?>" data-confirm="Cancel this appointment?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                                    <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="rounded-xl border border-dashed border-line bg-white px-6 py-12 text-center">
        <p class="font-medium">No appointments found</p>
        <p class="mt-1 text-sm text-muted">Try changing the filters.</p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
