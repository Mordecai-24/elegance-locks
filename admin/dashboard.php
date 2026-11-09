<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/appointments.php';

$user  = require_role('admin');
$pdo   = db();
$today = date('Y-m-d');

$count = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();
$totals = [
    ['Total customers',     $count("SELECT COUNT(*) FROM users WHERE role = 'customer' AND email_verified_at IS NOT NULL"), 'users',    'admin/customers.php'],
    ['Total salonists',     $count("SELECT COUNT(*) FROM users WHERE role = 'salonist'"),                                   'user',     'admin/salonists.php'],
    ['Total appointments',  $count('SELECT COUNT(*) FROM appointments'),                                                    'calendar', 'admin/appointments.php'],
    ['Pending appointments', $count("SELECT COUNT(*) FROM appointments WHERE status = 'pending'"),                          'clock',    'admin/appointments.php?status=pending'],
];

$stmt = $pdo->prepare(appointment_select_sql() . "WHERE a.appointment_date = ? AND a.status <> 'cancelled' ORDER BY a.start_time");
$stmt->execute([$today]);
$todayList = $stmt->fetchAll();

$pendingList = $pdo->query(appointment_select_sql() . "WHERE a.status = 'pending' ORDER BY a.appointment_date, a.start_time LIMIT 5")->fetchAll();

$pageTitle    = 'Dashboard';
$pageSubtitle = 'An overview of the salon today.';
$activePage   = 'dashboard';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <?php foreach ($totals as [$label, $value, $ico, $link]): ?>
        <a href="<?= e(url($link)) ?>" class="rounded-xl border border-line bg-white p-5 transition-colors hover:border-primary">
            <div class="flex items-center justify-between text-muted">
                <p class="text-sm"><?= e($label) ?></p>
                <?= icon($ico, 'h-5 w-5 text-primary') ?>
            </div>
            <p class="mt-3 font-serif text-3xl font-semibold text-secondary"><?= $value ?></p>
        </a>
    <?php endforeach; ?>
</div>

<div class="mt-10 grid gap-10 xl:grid-cols-2">
    <section>
        <h2 class="font-serif text-xl font-semibold">Today's appointments</h2>
        <?php if ($todayList): ?>
            <ul class="mt-4 divide-y divide-line rounded-xl border border-line bg-white">
                <?php foreach ($todayList as $a): ?>
                    <li class="flex items-center justify-between gap-3 px-5 py-4">
                        <div class="flex items-start gap-4">
                            <p class="w-20 shrink-0 font-medium text-secondary"><?= e(format_time($a['start_time'])) ?></p>
                            <div>
                                <p class="font-medium"><?= e($a['service_name']) ?></p>
                                <p class="text-sm text-muted"><?= e($a['customer_name']) ?> &middot; <?= e($a['salonist_name'] ?? 'Unassigned') ?></p>
                            </div>
                        </div>
                        <?= status_badge($a['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="mt-3 text-sm text-muted">No appointments are scheduled for today.</p>
        <?php endif; ?>
    </section>

    <section>
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-xl font-semibold">Waiting for approval</h2>
            <a href="<?= e(url('admin/appointments.php?status=pending')) ?>" class="text-sm font-medium text-secondary hover:underline">Manage</a>
        </div>
        <?php if ($pendingList): ?>
            <ul class="mt-4 divide-y divide-line rounded-xl border border-line bg-white">
                <?php foreach ($pendingList as $a): ?>
                    <li class="px-5 py-4">
                        <p class="font-medium"><?= e($a['service_name']) ?> <span class="font-normal text-muted">for <?= e($a['customer_name']) ?></span></p>
                        <p class="text-sm text-muted"><?= e(format_date($a['appointment_date'])) ?> at <?= e(format_time($a['start_time'])) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="mt-3 text-sm text-muted">No pending appointments. You are all caught up.</p>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
