<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/profile.php';

$user = require_role('salonist');

$selfPath = 'salonist/profile.php';
$errors = is_post() ? handle_profile_post($user, $selfPath) : [];

$pageTitle    = 'My Profile';
$pageSubtitle = 'Update your details and password.';
$activePage   = 'profile';
require __DIR__ . '/../includes/dashboard_header.php';
require __DIR__ . '/../includes/profile_form.php';
require __DIR__ . '/../includes/dashboard_footer.php';
