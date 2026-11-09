<?php
/**
 * Loaded at the top of every page: settings, database, helpers, session.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Session cookie hardening, then start the session.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => !empty($_SERVER['HTTPS']),
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
