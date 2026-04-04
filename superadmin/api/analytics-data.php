<?php
/**
 * API: Global analytics data
 * GET: ?days=7|30|90
 */

require_once __DIR__ . '/../../shared/bootstrap.php';
require_once __DIR__ . '/../includes/super-auth.php';
require_once __DIR__ . '/../includes/super-functions.php';

header('Content-Type: application/json');

if (!superIsLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$days = (int)($_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90])) $days = 30;

$global = getGlobalAnalytics($days);
$perHotel = getPerHotelAnalytics($days);
$trend = getAnalyticsTrend($days);

echo json_encode([
    'success'   => true,
    'global'    => $global,
    'per_hotel' => $perHotel,
    'trend'     => $trend,
]);
