<?php
require_once __DIR__ . '/../shared/bootstrap.php';
/**
 * Admin Logout
 * Hotel Corintel
 */

require_once HOTEL_ROOT . '/shared/includes/auth.php';

logout();

header('Location: login.php');
exit;
