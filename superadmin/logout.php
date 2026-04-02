<?php
/**
 * Super Admin Logout
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';

superLogout();

header('Location: login.php');
exit;
