<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/appointments.php';

$user = require_role('customer');
$pdo  = db();

// Statistics (only this customer's own appointments)
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(status IN ('pending','confirmed','in_progress') AND appointment_date >= CURDATE()) AS upcoming,
            SUM(status = 'pending') AS pending,
            SUM(status = 'completed') AS completed
     FROM appointments WHERE customer_id = ?"
);
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

// Next appointment
$stmt = $pdo->prepare(
    appointment_select_sql() .
    "WHERE a.customer_id = ? AND a.status IN ('pending','confirmed','in_progress')
       AND (a.appointment_date > ? OR (a.appointment_date = ? AND a.end_time > ?))
     ORDER BY a.appointment_date, a.start_time LIMIT 1"
);
$stmt->execute([$user['id'], date('Y-m-d'), date('Y-m-d'), date('H:i:s')]);
$next = $stmt->fetch();

// Recent appointments
$stmt = $pdo->prepare(appointment_select_sql() . 'WHERE a.customer_id = ? ORDER BY a.appointment_date DESC, a.start_time DESC LIMIT 5');
$stmt->execute([$user['id']]);
$recent = $stmt->fetchAll();

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . explode(' ', $user['full_name'])[0] . '.';
$activePage   = 'dashboard';
require __DIR__ . '/../includes/dashboard_header.php';

$cards = [
    ['Upcoming',  (int) $stats['upcoming'],  'calendar'],
    ['Pending',   (int) $stats['pending'],   'clock'],
    ['Completed', (int) $stats['completed'], 'check'],
    ['Total booked', (int) $stats['total'],  'sparkles'],
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
    <h2 class="font-serif text-xl font-semibold">Your next appointment</h2>
    <?php if ($next): ?>
        <div class="mt-4 flex flex-col gap-4 border-l-4 border-primary bg-white py-4 pl-5 pr-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-medium"><?= e($next['service_name']) ?></p>
                <p class="mt-1 text-sm text-muted">
                    <?= e(format_date($next['appointment_date'])) ?> at <?= e(format_time($next['start_time'])) ?>
                    &middot; Stylist: <?= e($next['salonist_name'] ?? 'To be assigned') ?>
                </p>
            </div>
            <?= status_badge($next['status']) ?>
        </div>
    <?php else: ?>
        <p class="mt-3 text-sm text-muted">You have no upcoming appointments.
            <a href="<?= e(url('customer/book.php')) ?>" class="font-medium text-secondary hover:underline">Book one now</a>.</p>
    <?php endif; ?>
</section>

<section class="mt-10">
    <div class="flex items-center justify-between">
        <h2 class="font-serif text-xl font-semibold">Recent appointments</h2>
        <a href="<?= e(url('customer/appointments.php')) ?>" class="text-sm font-medium text-secondary hover:underline">View all</a>
    </div>

    <?php if ($recent): ?>
        <div class="table-wrap mt-4 rounded-xl border border-line bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-xs uppercase tracking-wide text-muted">
                    <tr><th class="px-4 py-3">Service</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <?php foreach ($recent as $a): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium"><?= e($a['service_name']) ?></td>
                            <td class="px-4 py-3"><?= e(format_date($a['appointment_date'])) ?></td>
                            <td class="px-4 py-3"><?= e(format_time($a['start_time'])) ?></td>
                            <td class="px-4 py-3"><?= status_badge($a['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="mt-4 rounded-xl border border-dashed border-line bg-white px-6 py-10 text-center">
            <p class="font-medium">No appointments yet</p>
            <p class="mt-1 text-sm text-muted">When you book a service it will appear here.</p>
            <a href="<?= e(url('customer/book.php')) ?>" class="mt-4 inline-block rounded-lg bg-secondary px-5 py-2.5 text-sm font-semibold text-white hover:bg-ink">Book an appointment</a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
