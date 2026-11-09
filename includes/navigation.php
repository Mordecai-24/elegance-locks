<?php
/**
 * Sidebar links for each role. Every link is a separate PHP page.
 * 'key' is matched against $activePage to highlight the current page.
 */

function nav_items(string $role): array
{
    switch ($role) {
        case 'admin':
            return [
                ['key' => 'dashboard',    'label' => 'Dashboard',    'path' => 'admin/dashboard.php',    'icon' => 'home'],
                ['key' => 'customers',    'label' => 'Customers',    'path' => 'admin/customers.php',    'icon' => 'users'],
                ['key' => 'salonists',    'label' => 'Salonists',    'path' => 'admin/salonists.php',    'icon' => 'user'],
                ['key' => 'appointments', 'label' => 'Appointments', 'path' => 'admin/appointments.php', 'icon' => 'calendar'],
                ['key' => 'services',     'label' => 'Services',     'path' => 'admin/services.php',     'icon' => 'sparkles'],
                ['key' => 'profile',      'label' => 'My Profile',   'path' => 'admin/profile.php',      'icon' => 'pencil'],
            ];
        case 'salonist':
            return [
                ['key' => 'dashboard',    'label' => 'Dashboard',    'path' => 'salonist/dashboard.php',    'icon' => 'home'],
                ['key' => 'appointments', 'label' => 'Appointments', 'path' => 'salonist/appointments.php', 'icon' => 'calendar'],
                ['key' => 'profile',      'label' => 'My Profile',   'path' => 'salonist/profile.php',      'icon' => 'pencil'],
            ];
        default:
            return [
                ['key' => 'dashboard',    'label' => 'Dashboard',       'path' => 'customer/dashboard.php',    'icon' => 'home'],
                ['key' => 'book',         'label' => 'Book Appointment', 'path' => 'customer/book.php',        'icon' => 'plus'],
                ['key' => 'appointments', 'label' => 'My Appointments', 'path' => 'customer/appointments.php', 'icon' => 'calendar'],
                ['key' => 'profile',      'label' => 'My Profile',      'path' => 'customer/profile.php',      'icon' => 'pencil'],
            ];
    }
}

/** Notification bell: [count, tooltip text, link]. */
function notification_info(array $user): array
{
    $pdo = db();
    $today = date('Y-m-d');

    if ($user['role'] === 'admin') {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
        return [$count, $count . ' pending appointment(s)', 'admin/appointments.php?status=pending'];
    }

    if ($user['role'] === 'salonist') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE salonist_id = ? AND appointment_date = ? AND status IN ('pending','confirmed','in_progress')");
        $stmt->execute([$user['id'], $today]);
        $count = (int) $stmt->fetchColumn();
        return [$count, $count . ' appointment(s) today', 'salonist/appointments.php'];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE customer_id = ? AND appointment_date >= ? AND status IN ('pending','confirmed','in_progress')");
    $stmt->execute([$user['id'], $today]);
    $count = (int) $stmt->fetchColumn();
    return [$count, $count . ' upcoming appointment(s)', 'customer/appointments.php'];
}
