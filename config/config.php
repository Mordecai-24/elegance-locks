<?php
/**
 * Application settings.
 * Change BASE_URL if you do not serve the project from /elegance_locks.
 */

define('APP_NAME', 'Elegance Locks');
define('APP_TAGLINE', 'Your Beauty, Our Passion.');
define('BASE_URL', '/elegance_locks');

// Needed once, to create the first administrator at /setup-admin.php.
// Replace it with your own private value before you open that page.
define('SETUP_KEY', 'CHANGE-THIS-SETUP-KEY');

// Email OTP rules
define('OTP_EXPIRY_MINUTES', 10);
define('OTP_RESEND_COOLDOWN_SECONDS', 60);
define('OTP_MAX_ATTEMPTS', 5);

// Login protection
define('LOGIN_MAX_FAILURES', 5);          // per email address
define('LOGIN_MAX_FAILURES_PER_IP', 30);  // per IP address (shared networks)
define('LOGIN_LOCK_MINUTES', 15);
define('SESSION_IDLE_SECONDS', 1800);

// Appointment rules
define('OPENING_TIME', '09:00');
define('CLOSING_TIME', '18:00');
define('SLOT_MINUTES', 30);
define('CANCEL_MIN_HOURS', 2);

// Currency label shown next to service prices
define('CURRENCY', 'KES');

// Images used on login/registration pages. Replace with files in assets/images/ if you prefer.
define('AUTH_IMAGE_URL', 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1400&q=80');

date_default_timezone_set('Africa/Nairobi');
