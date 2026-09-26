<?php
// index.php — redirect sesuai status login
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Location: ' . (current_user() ? 'dashboard.php' : 'login.php'));
exit;