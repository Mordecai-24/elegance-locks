<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/appointments.php';

$user     = require_role('customer');
$services = active_services();
$errors   = [];

if (is_post()) {
    csrf_verify();

    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $date      = post('appointment_date');
    $time      = post('appointment_time');
    $notes     = mb_substr(post('notes'), 0, 500);

    $service = find_service($serviceId);
    $errors  = validate_booking((int) $user['id'], $service, $date, $time);

    if (!$errors) {
        $end = end_time_for($time, (int) $service['duration_minutes']);
        db()->prepare(
            "INSERT INTO appointments (customer_id, service_id, appointment_date, start_time, end_time, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        )->execute([$user['id'], $serviceId, $date, $time . ':00', $end, $notes !== '' ? $notes : null]);

        flash('success', 'Your appointment request has been sent. We will confirm it shortly.');
        redirect('customer/appointments.php');
    }
}

$pageTitle    = 'Book Appointment';
$pageSubtitle = 'Choose a service and your preferred date and time.';
$activePage   = 'book';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<?= errors_render($errors) ?>

<div class="grid gap-10 lg:grid-cols-3">

    <form method="post" action="<?= e(url('customer/book.php')) ?>" class="space-y-5 lg:col-span-2 lg:max-w-xl" novalidate>
        <?= csrf_field() ?>

        <div>
            <label for="service_id" class="mb-1 block text-sm font-medium">Salon service</label>
            <select id="service_id" name="service_id" class="field" required>
                <option value="">Select a service</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (int) ($_POST['service_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?> (<?= e(format_price($s['price'])) ?>, <?= (int) $s['duration_minutes'] ?> min)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="appointment_date" class="mb-1 block text-sm font-medium">Preferred date</label>
                <input type="date" id="appointment_date" name="appointment_date" value="<?= old('appointment_date') ?>" min="<?= date('Y-m-d') ?>" class="field" required>
            </div>
            <div>
                <label for="appointment_time" class="mb-1 block text-sm font-medium">Preferred time</label>
                <select id="appointment_time" name="appointment_time" class="field" required>
                    <option value="">Select a time</option>
                    <?php foreach (time_slots() as $slot): ?>
                        <option value="<?= e($slot) ?>" <?= ($_POST['appointment_time'] ?? '') === $slot ? 'selected' : '' ?>><?= e(format_time($slot)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label for="notes" class="mb-1 block text-sm font-medium">Additional notes <span class="font-normal text-muted">(optional)</span></label>
            <textarea id="notes" name="notes" rows="4" maxlength="500" class="field" placeholder="Anything the stylist should know?"><?= old('notes') ?></textarea>
        </div>

        <button type="submit" class="rounded-lg bg-secondary px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-ink">Request appointment</button>
    </form>

    <aside>
        <h2 class="font-serif text-xl font-semibold">Our services</h2>
        <p class="mt-1 text-sm text-muted">Open daily, <?= e(format_time(OPENING_TIME)) ?> to <?= e(format_time(CLOSING_TIME)) ?>.</p>
        <ul class="mt-4 divide-y divide-line text-sm">
            <?php foreach ($services as $s): ?>
                <li class="flex items-start justify-between gap-4 py-3">
                    <div>
                        <p class="font-medium"><?= e($s['name']) ?></p>
                        <p class="text-xs text-muted"><?= (int) $s['duration_minutes'] ?> minutes</p>
                    </div>
                    <p class="shrink-0 font-medium text-secondary"><?= e(format_price($s['price'])) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
