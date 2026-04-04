<?php
/**
 * API: Toggle a feature for an establishment
 * POST: hotel_id, feature_key, enabled (0/1), csrf_token
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
    exit;
}

$hotelId = (int)($_POST['hotel_id'] ?? 0);
$featureKey = trim($_POST['feature_key'] ?? '');
$enabled = (bool)(int)($_POST['enabled'] ?? 0);

if (!$hotelId || !$featureKey) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

if (!isset(AVAILABLE_FEATURES[$featureKey])) {
    echo json_encode(['success' => false, 'message' => 'Feature inconnue']);
    exit;
}

$hotel = getHotelById($hotelId);
if (!$hotel) {
    echo json_encode(['success' => false, 'message' => 'Établissement introuvable']);
    exit;
}

$result = setFeatureToggle($hotelId, $featureKey, $enabled);

if ($result) {
    logAudit(
        $_SESSION['super_admin_id'],
        'feature_toggled',
        $hotelId,
        json_encode([
            'feature' => $featureKey,
            'enabled' => $enabled,
            'label' => AVAILABLE_FEATURES[$featureKey]['label'],
        ])
    );
    echo json_encode(['success' => true, 'message' => 'Feature mise à jour']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
