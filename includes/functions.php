<?php
/**
 * General helpers: escaping, URLs, CSRF, flash messages, validation, formatting.
 */

// ---------- Output and navigation ----------

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function post(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

// ---------- CSRF protection ----------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Stops the request if the submitted CSRF token does not match the session. */
function csrf_verify(): void
{
    $sent = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session expired or the request was invalid. Go back, refresh the page and try again.');
    }
}

// ---------- Flash messages ----------

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $styles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'error'   => 'border-red-200 bg-red-50 text-red-800',
        'info'    => 'border-line bg-white text-ink',
    ];
    $html = '';
    foreach ($_SESSION['flash'] as $item) {
        $class = $styles[$item['type']] ?? $styles['info'];
        $html .= '<div role="alert" class="mb-4 rounded-lg border px-4 py-3 text-sm ' . $class . '">'
               . e($item['message']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function errors_render(array $errors): string
{
    if (!$errors) {
        return '';
    }
    $html = '<div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc space-y-1 pl-5">';
    foreach ($errors as $error) {
        $html .= '<li>' . e($error) . '</li>';
    }
    return $html . '</ul></div>';
}

// ---------- Validation ----------

function normalize_phone(string $phone): string
{
    return preg_replace('/[\s\-()]/', '', $phone);
}

function valid_phone(string $phone): bool
{
    return (bool) preg_match('/^\+?[0-9]{9,15}$/', $phone);
}

/** Returns an error message, or null when the password is acceptable. */
function password_problem(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one letter and one number.';
    }
    return null;
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ---------- Formatting ----------

function format_date(string $date): string
{
    return date('D, j M Y', strtotime($date));
}

function format_time(string $time): string
{
    return date('g:i A', strtotime($time));
}

function format_price($amount): string
{
    return CURRENCY . ' ' . number_format((float) $amount, 0);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $letters;
}

// ---------- Appointment statuses ----------

function status_labels(): array
{
    return [
        'pending'     => 'Pending',
        'confirmed'   => 'Confirmed',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];
}

function status_badge(string $status): string
{
    $classes = [
        'pending'     => 'bg-amber-50 text-amber-700 ring-amber-200',
        'confirmed'   => 'bg-sky-50 text-sky-700 ring-sky-200',
        'in_progress' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'completed'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled'   => 'bg-gray-100 text-gray-600 ring-gray-200',
    ];
    $label = status_labels()[$status] ?? ucfirst($status);
    $class = $classes[$status] ?? $classes['pending'];
    return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ' . $class . '">' . e($label) . '</span>';
}

// ---------- Icons (inline SVG, outline style) ----------

function icon(string $name, string $class = 'h-5 w-5'): string
{
    static $paths = [
        'home'     => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'users'    => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'user'     => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
        'calendar' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'plus'     => 'M12 4.5v15m7.5-7.5h-15',
        'logout'   => 'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9',
        'menu'     => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
        'bell'     => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
        'chevron'  => 'M15.75 19.5L8.25 12l7.5-7.5',
        'sparkles' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z',
        'clock'    => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'x'        => 'M6 18L18 6M6 6l12 12',
        'check'    => 'M4.5 12.75l6 6 9-13.5',
        'pencil'   => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.862 4.487zm0 0L19.5 7.125',
    ];
    $d = $paths[$name] ?? $paths['home'];
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="' . e($class) . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '"/></svg>';
}
