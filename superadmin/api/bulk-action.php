<?php
/**
 * API: Bulk actions on hotels (activate, deactivate, export)
 * POST: action (activate|deactivate|export), hotel_ids[], csrf_token
 */

require_once __DIR__ . '/../../shared/bootstrap.php';
require_once __DIR__ . '/../includes/super-auth.php';
require_once __DIR__ . '/../includes/super-functions.php';

if (!superIsLoggedIn()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$hotelIds = $_POST['hotel_ids'] ?? [];

if (!is_array($hotelIds)) {
    $hotelIds = [$hotelIds];
}
$hotelIds = array_filter(array_map('intval', $hotelIds));

// Export all (no selection needed)
if ($action === 'export_all') {
    $hotels = getAllHotels();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="etablissements_' . date('Y-m-d') . '.csv"');
    exportHotelsCsv($hotels);
    exit;
}

if (empty($hotelIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Aucun établissement sélectionné']);
    exit;
}

if ($action === 'export') {
    // Export selected hotels
    $hotels = [];
    foreach ($hotelIds as $id) {
        $h = getHotelById($id);
        if ($h) $hotels[] = $h;
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="etablissements_selection_' . date('Y-m-d') . '.csv"');
    exportHotelsCsv($hotels);
    exit;
}

header('Content-Type: application/json');

if ($action === 'activate') {
    $count = bulkUpdateHotelStatus($hotelIds, true);
    logAudit($_SESSION['super_admin_id'], 'bulk_activate', null, json_encode(['count' => $count, 'ids' => $hotelIds]));
    echo json_encode(['success' => true, 'message' => $count . ' établissement(s) activé(s)', 'count' => $count]);
} elseif ($action === 'deactivate') {
    $count = bulkUpdateHotelStatus($hotelIds, false);
    logAudit($_SESSION['super_admin_id'], 'bulk_deactivate', null, json_encode(['count' => $count, 'ids' => $hotelIds]));
    echo json_encode(['success' => true, 'message' => $count . ' établissement(s) désactivé(s)', 'count' => $count]);
} else {
    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
}
