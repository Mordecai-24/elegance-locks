<?php
/**
 * Appointment rules shared by customer, salonist and admin pages.
 */

const ACTIVE_STATUSES = ['pending', 'confirmed', 'in_progress'];

/** All bookable start times, e.g. ['09:00', '09:30', ...]. */
function time_slots(): array
{
    $slots = [];
    $t = strtotime(OPENING_TIME);
    $close = strtotime(CLOSING_TIME);
    while ($t < $close) {
        $slots[] = date('H:i', $t);
        $t += SLOT_MINUTES * 60;
    }
    return $slots;
}

function end_time_for(string $start, int $durationMinutes): string
{
    return date('H:i:s', strtotime($start) + $durationMinutes * 60);
}

function active_services(): array
{
    return db()->query('SELECT * FROM services WHERE is_active = 1 ORDER BY name')->fetchAll();
}

function find_service(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM services WHERE id = ? AND is_active = 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function active_salonists(): array
{
    return db()->query("SELECT id, full_name, specialty FROM users WHERE role = 'salonist' AND is_active = 1 ORDER BY full_name")->fetchAll();
}

/** Does this salonist already have an active appointment overlapping the given time? */
function salonist_has_conflict(int $salonistId, string $date, string $start, string $end, int $ignoreId = 0): bool
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM appointments
         WHERE salonist_id = ? AND appointment_date = ? AND id <> ?
           AND status IN ('pending','confirmed','in_progress')
           AND start_time < ? AND end_time > ?"
    );
    $stmt->execute([$salonistId, $date, $ignoreId, $end, $start]);
    return (int) $stmt->fetchColumn() > 0;
}

function customer_has_conflict(int $customerId, string $date, string $start, string $end, int $ignoreId = 0): bool
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM appointments
         WHERE customer_id = ? AND appointment_date = ? AND id <> ?
           AND status IN ('pending','confirmed','in_progress')
           AND start_time < ? AND end_time > ?"
    );
    $stmt->execute([$customerId, $date, $ignoreId, $end, $start]);
    return (int) $stmt->fetchColumn() > 0;
}

/** True when every active salonist is already busy during this time. */
function slot_is_full(string $date, string $start, string $end): bool
{
    $staff = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'salonist' AND is_active = 1")->fetchColumn();
    if ($staff === 0) {
        return false; // no staff yet: do not block bookings
    }
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM appointments
         WHERE appointment_date = ? AND status IN ('pending','confirmed','in_progress')
           AND start_time < ? AND end_time > ?"
    );
    $stmt->execute([$date, $end, $start]);
    return (int) $stmt->fetchColumn() >= $staff;
}

/**
 * Validates a booking request. Returns a list of error messages (empty = valid).
 * $service must already be loaded from the database.
 */
function validate_booking(int $customerId, ?array $service, string $date, string $time): array
{
    $errors = [];

    if (!$service) {
        $errors[] = 'Please choose a valid service.';
    }
    if (!valid_date($date)) {
        $errors[] = 'Please choose a valid appointment date.';
    }
    if (!in_array($time, time_slots(), true)) {
        $errors[] = 'Please choose a valid appointment time.';
    }
    if ($errors) {
        return $errors;
    }

    $now = time();
    if ($date < date('Y-m-d')) {
        return ['The appointment date cannot be in the past.'];
    }
    if ($date === date('Y-m-d') && strtotime($date . ' ' . $time) <= $now) {
        return ['That time has already passed today. Please choose a later time.'];
    }

    $end = end_time_for($time, (int) $service['duration_minutes']);
    if ($end > CLOSING_TIME . ':00' || $end < $time) {
        return ['This service would finish after closing time (' . format_time(CLOSING_TIME) . '). Please choose an earlier time.'];
    }
    if (customer_has_conflict($customerId, $date, $time . ':00', $end)) {
        return ['You already have an appointment that overlaps with this time.'];
    }
    if (slot_is_full($date, $time . ':00', $end)) {
        return ['That time is fully booked. Please choose another time.'];
    }
    return [];
}

/** Which statuses may a salonist move an appointment to, from the current status? */
function salonist_next_statuses(string $current): array
{
    return match ($current) {
        'pending'     => ['confirmed'],
        'confirmed'   => ['in_progress'],
        'in_progress' => ['completed'],
        default       => [],
    };
}

/** Customers may cancel pending/confirmed appointments that start at least CANCEL_MIN_HOURS from now. */
function customer_can_cancel(array $appt): bool
{
    if (!in_array($appt['status'], ['pending', 'confirmed'], true)) {
        return false;
    }
    $start = strtotime($appt['appointment_date'] . ' ' . $appt['start_time']);
    return $start - time() >= CANCEL_MIN_HOURS * 3600;
}

/** Shared SELECT used by all appointment lists. Add a WHERE clause after it. */
function appointment_select_sql(): string
{
    return "SELECT a.*, s.name AS service_name, s.price AS service_price, s.duration_minutes,
                   c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
                   st.full_name AS salonist_name
            FROM appointments a
            JOIN services s ON s.id = a.service_id
            JOIN users c ON c.id = a.customer_id
            LEFT JOIN users st ON st.id = a.salonist_id ";
}
