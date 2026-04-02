<?php
/**
 * Generate Cross-Login Token for Super Admin → Hotel Admin access
 * Returns a signed URL that auto-logs into the hotel admin panel
 */

require_once __DIR__ . '/../includes/super-auth.php';
require_once __DIR__ . '/../includes/super-functions.php';

header('Content-Type: application/json');

if (!superIsLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide.']);
    exit;
}

$hotelId = (int)($_POST['hotel_id'] ?? 0);
if ($hotelId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID hôtel invalide.']);
    exit;
}

$hotel = getHotelById($hotelId);
if (!$hotel) {
    echo json_encode(['success' => false, 'message' => 'Hôtel introuvable.']);
    exit;
}

if (empty($hotel['admin_url'])) {
    echo json_encode(['success' => false, 'message' => 'URL admin non configurée pour cet hôtel.']);
    exit;
}

$superAdminId = $_SESSION['super_admin_id'];
$url = getCrossLoginUrl($hotel, $superAdminId);

if (!$url) {
    echo json_encode(['success' => false, 'message' => 'Impossible de générer le lien.']);
    exit;
}

// Log the cross-login attempt
logAudit($superAdminId, 'cross_login', $hotelId, json_encode([
    'hotel_name' => $hotel['name'],
    'admin_url' => $hotel['admin_url']
]));

echo json_encode(['success' => true, 'url' => $url]);
