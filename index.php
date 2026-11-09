<?php
require_once __DIR__ . '/includes/init.php';

// The home page simply sends visitors to the right place.
$user = current_user();
redirect($user ? dashboard_path($user['role']) : 'login.php');
