<?php
/**
 * API: Quick stats for a single hotel
 * GET: ?id=X
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

$hotelId = (int)($_GET['id'] ?? 0);
if (!$hotelId) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

$stats = getHotelQuickStats($hotelId);
echo json_encode(['success' => true, 'data' => $stats]);
