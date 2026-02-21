<?php
/**
 * Rooms Management API
 * Handles AJAX operations for room management
 * Hotel Corintel
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    $pdo = getDatabase();

    switch ($method) {
        case 'GET':
            handleGetRequest($action, $response);
            break;
        case 'POST':
            handlePostRequest($action, $response);
            break;
        default:
            http_response_code(405);
            $response['message'] = 'Méthode non autorisée';
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Erreur de base de données';
    error_log('Rooms API Error: ' . $e->getMessage());
} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

/**
 * Handle GET requests
 */
function handleGetRequest(string $action, array &$response): void
{
    switch ($action) {
        case 'list':
            getList($response);
            break;
        case 'get':
            getRoom($response);
            break;
        case 'stats':
            getStats($response);
            break;
        case 'floors':
            getFloors($response);
            break;
        default:
            getList($response);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest(string $action, array &$response): void
{
    switch ($action) {
        case 'create':
            createRoom($response);
            break;
        case 'update':
            updateRoom($response);
            break;
        case 'delete':
            deleteRoom($response);
            break;
        case 'update_status':
            updateStatus($response);
            break;
        case 'update_housekeeping':
            updateHousekeeping($response);
            break;
        default:
            throw new Exception('Action non reconnue');
    }
}

/**
 * Get rooms list with filters
 */
function getList(array &$response): void
{
    $filters = [
        'status' => $_GET['status'] ?? null,
        'housekeeping_status' => $_GET['housekeeping'] ?? null,
        'floor' => $_GET['floor'] ?? null,
        'room_type' => $_GET['type'] ?? null,
        'search' => $_GET['search'] ?? null,
        'is_active' => true
    ];

    $rooms = getRooms($filters);
    $statuses = getRoomStatuses();
    $housekeepingStatuses = getHousekeepingStatuses();
    $roomTypes = getRoomTypes();

    $response['success'] = true;
    $response['data'] = [
        'rooms' => array_map(function($room) use ($statuses, $housekeepingStatuses, $roomTypes) {
            $amenities = $room['amenities'] ? json_decode($room['amenities'], true) : [];
            return [
                'id' => (int)$room['id'],
                'room_number' => $room['room_number'],
                'floor' => $room['floor'] !== null ? (int)$room['floor'] : null,
                'room_type' => $room['room_type'],
                'room_type_label' => $roomTypes[$room['room_type']] ?? $room['room_type'],
                'capacity' => (int)$room['capacity'],
                'bed_count' => (int)$room['bed_count'],
                'surface_area' => $room['surface_area'] ? (float)$room['surface_area'] : null,
                'status' => $room['status'],
                'status_label' => $statuses[$room['status']] ?? $room['status'],
                'status_badge_class' => getRoomStatusBadgeClass($room['status']),
                'housekeeping_status' => $room['housekeeping_status'],
                'housekeeping_label' => $housekeepingStatuses[$room['housekeeping_status']] ?? $room['housekeeping_status'],
                'housekeeping_badge_class' => getHousekeepingBadgeClass($room['housekeeping_status']),
                'last_cleaned_at' => $room['last_cleaned_at'] ? date('d/m/Y H:i', strtotime($room['last_cleaned_at'])) : null,
                'amenities' => $amenities,
                'notes' => $room['notes'],
                'is_active' => (bool)$room['is_active']
            ];
        }, $rooms),
        'count' => count($rooms)
    ];
}

/**
 * Get single room by ID
 */
function getRoom(array &$response): void
{
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID de chambre requis');
    }

    $room = getRoomById($id);
    if (!$room) {
        http_response_code(404);
        throw new Exception('Chambre non trouvée');
    }

    $statuses = getRoomStatuses();
    $housekeepingStatuses = getHousekeepingStatuses();
    $roomTypes = getRoomTypes();
    $amenities = $room['amenities'] ? json_decode($room['amenities'], true) : [];

    $response['success'] = true;
    $response['data'] = [
        'id' => (int)$room['id'],
        'room_number' => $room['room_number'],
        'floor' => $room['floor'] !== null ? (int)$room['floor'] : null,
        'room_type' => $room['room_type'],
        'room_type_label' => $roomTypes[$room['room_type']] ?? $room['room_type'],
        'capacity' => (int)$room['capacity'],
        'bed_count' => (int)$room['bed_count'],
        'surface_area' => $room['surface_area'] ? (float)$room['surface_area'] : null,
        'status' => $room['status'],
        'status_label' => $statuses[$room['status']] ?? $room['status'],
        'housekeeping_status' => $room['housekeeping_status'],
        'housekeeping_label' => $housekeepingStatuses[$room['housekeeping_status']] ?? $room['housekeeping_status'],
        'last_cleaned_at' => $room['last_cleaned_at'],
        'last_inspection_at' => $room['last_inspection_at'],
        'last_checkout_at' => $room['last_checkout_at'],
        'amenities' => $amenities,
        'notes' => $room['notes'],
        'is_active' => (bool)$room['is_active'],
        'created_at' => $room['created_at'],
        'updated_at' => $room['updated_at']
    ];
}

/**
 * Get room statistics
 */
function getStats(array &$response): void
{
    $stats = getRoomStatistics();
    $response['success'] = true;
    $response['data'] = $stats;
}

/**
 * Get available floors
 */
function getFloors(array &$response): void
{
    $floors = getRoomFloors();
    $response['success'] = true;
    $response['data'] = $floors;
}

/**
 * Create new room
 */
function createRoom(array &$response): void
{
    $data = getPostData();
    validateRoomData($data);

    // Check if room number already exists
    if (roomNumberExists($data['room_number'])) {
        throw new Exception('Ce numéro de chambre existe déjà');
    }

    $id = \createRoom($data);
    if ($id) {
        $response['success'] = true;
        $response['message'] = 'Chambre créée avec succès';
        $response['data'] = ['id' => $id];
    } else {
        throw new Exception('Erreur lors de la création de la chambre');
    }
}

/**
 * Update existing room
 */
function updateRoom(array &$response): void
{
    $data = getPostData();
    $id = (int)($data['id'] ?? 0);

    if (!$id) {
        throw new Exception('ID de chambre requis');
    }

    $existingRoom = getRoomById($id);
    if (!$existingRoom) {
        http_response_code(404);
        throw new Exception('Chambre non trouvée');
    }

    validateRoomData($data, $id);

    // Check if room number already exists (excluding current room)
    if (roomNumberExists($data['room_number'], $id)) {
        throw new Exception('Ce numéro de chambre existe déjà');
    }

    $result = \updateRoom($id, $data);
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Chambre mise à jour avec succès';
    } else {
        throw new Exception('Erreur lors de la mise à jour de la chambre');
    }
}

/**
 * Delete (soft delete) room
 */
function deleteRoom(array &$response): void
{
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID de chambre requis');
    }

    $room = getRoomById($id);
    if (!$room) {
        http_response_code(404);
        throw new Exception('Chambre non trouvée');
    }

    $result = \deleteRoom($id);
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Chambre supprimée avec succès';
    } else {
        throw new Exception('Erreur lors de la suppression de la chambre');
    }
}

/**
 * Update room status
 */
function updateStatus(array &$response): void
{
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!$id) {
        throw new Exception('ID de chambre requis');
    }

    $validStatuses = array_keys(getRoomStatuses());
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Statut invalide');
    }

    $result = updateRoomStatus($id, $status);
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Statut mis à jour';
        $response['data'] = [
            'status' => $status,
            'status_label' => getRoomStatuses()[$status],
            'badge_class' => getRoomStatusBadgeClass($status)
        ];
    } else {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }
}

/**
 * Update housekeeping status
 */
function updateHousekeeping(array &$response): void
{
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['housekeeping_status'] ?? '';

    if (!$id) {
        throw new Exception('ID de chambre requis');
    }

    $validStatuses = array_keys(getHousekeepingStatuses());
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Statut de ménage invalide');
    }

    $result = updateRoomHousekeepingStatus($id, $status);
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Statut de ménage mis à jour';
        $response['data'] = [
            'housekeeping_status' => $status,
            'housekeeping_label' => getHousekeepingStatuses()[$status],
            'badge_class' => getHousekeepingBadgeClass($status)
        ];
    } else {
        throw new Exception('Erreur lors de la mise à jour du statut de ménage');
    }
}

/**
 * Get POST data (supports both form and JSON)
 */
function getPostData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON invalide');
        }
        return $data;
    }

    return $_POST;
}

/**
 * Validate room data
 */
function validateRoomData(array $data, ?int $excludeId = null): void
{
    if (empty($data['room_number'])) {
        throw new Exception('Le numéro de chambre est requis');
    }

    if (strlen($data['room_number']) > 20) {
        throw new Exception('Le numéro de chambre ne doit pas dépasser 20 caractères');
    }

    $validTypes = array_keys(getRoomTypes());
    if (!empty($data['room_type']) && !in_array($data['room_type'], $validTypes)) {
        throw new Exception('Type de chambre invalide');
    }

    $validStatuses = array_keys(getRoomStatuses());
    if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
        throw new Exception('Statut invalide');
    }

    $validHousekeeping = array_keys(getHousekeepingStatuses());
    if (!empty($data['housekeeping_status']) && !in_array($data['housekeeping_status'], $validHousekeeping)) {
        throw new Exception('Statut de ménage invalide');
    }

    if (isset($data['capacity']) && ($data['capacity'] < 1 || $data['capacity'] > 20)) {
        throw new Exception('La capacité doit être entre 1 et 20');
    }

    if (isset($data['bed_count']) && ($data['bed_count'] < 1 || $data['bed_count'] > 10)) {
        throw new Exception('Le nombre de lits doit être entre 1 et 10');
    }

    if (isset($data['floor']) && $data['floor'] !== '' && ($data['floor'] < 0 || $data['floor'] > 100)) {
        throw new Exception('L\'étage doit être entre 0 et 100');
    }
}
