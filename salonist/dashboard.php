<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/appointments.php';

$user = require_role('salonist');
$pdo  = db();
$today = date('Y-m-d');

// Statistics: only appointments assigned to this salonist
$stmt = $pdo->prepare(
    "SELECT SUM(appointment_date = ? AND status IN ('pending','confirmed','in_progress')) AS today,
            SUM(appointment_date > ? AND status IN ('pending','confirmed')) AS upcoming,
            SUM(status = 'in_progress') AS in_progress,
            SUM(status = 'completed') AS completed
     FROM appointments WHERE salonist_id = ?"
);
$stmt->execute([$today, $today, $user['id']]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare(appointment_select_sql() . "WHERE a.salonist_id = ? AND a.appointment_date = ? AND a.status <> 'cancelled' ORDER BY a.start_time");
$stmt->execute([$user['id'], $today]);
$schedule = $stmt->fetchAll();

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Your schedule for ' . date('l, j F Y') . '.';
$activePage   = 'dashboard';
require __DIR__ . '/../includes/dashboard_header.php';

$cards = [
    ['Today',       (int) $stats['today'],       'clock'],
    ['Upcoming',    (int) $stats['upcoming'],    'calendar'],
    ['In progress', (int) $stats['in_progress'], 'sparkles'],
    ['Completed',   (int) $stats['completed'],   'check'],
];
?>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <?php foreach ($cards as [$label, $value, $ico]): ?>
        <div class="rounded-xl border border-line bg-white p-5">
            <div class="flex items-center justify-between text-muted">
                <p class="text-sm"><?= e($label) ?></p>
                <?= icon($ico, 'h-5 w-5 text-primary') ?>
            </div>
            <p class="mt-3 font-serif text-3xl font-semibold text-secondary"><?= $value ?></p>
        </div>
    <?php endforeach; ?>
</div>

<section class="mt-10">
    <div class="flex items-center justify-between">
        <h2 class="font-serif text-xl font-semibold">Today's schedule</h2>
        <a href="<?= e(url('salonist/appointments.php')) ?>" class="text-sm font-medium text-secondary hover:underline">All appointments</a>
    </div>

    <?php if ($schedule): ?>
        <ul class="mt-4 divide-y divide-line rounded-xl border border-line bg-white">
            <?php foreach ($schedule as $a): ?>
                <li class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4">
                        <p class="w-20 shrink-0 font-medium text-secondary"><?= e(format_time($a['start_time'])) ?></p>
                        <div>
                            <p class="font-medium"><?= e($a['service_name']) ?></p>
                            <p class="text-sm text-muted"><?= e($a['customer_name']) ?> &middot; <?= e($a['customer_phone']) ?></p>
                        </div>
                    </div>
                    <?= status_badge($a['status']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="mt-4 rounded-xl border border-dashed border-line bg-white px-6 py-10 text-center">
            <p class="font-medium">Nothing scheduled for today</p>
            <p class="mt-1 text-sm text-muted">Appointments assigned to you will appear here.</p>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
