<?php
/**
 * API: Server monitoring metrics (real + simulated)
 * GET — returns JSON
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

$metrics = getMonitoringMetrics();
echo json_encode(['success' => true, 'data' => $metrics]);
