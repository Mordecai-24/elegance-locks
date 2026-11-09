<?php
require_once __DIR__ . '/includes/init.php';

// Logging out changes state, so it only works through a POST form with a valid CSRF token.
if (!is_post()) {
    redirect('login.php');
}

csrf_verify();
logout_user();
flash('success', 'You have been logged out.');
redirect('login.php');
