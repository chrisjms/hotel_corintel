<?php
require_once __DIR__ . '/../../shared/bootstrap.php';
/**
 * Push Notification Subscription API
 * Handle push subscription registration
 */

require_once HOTEL_ROOT . '/shared/includes/functions.php';

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get room from session
$session = getRoomServiceSession();
if (!$session) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$roomId = $session['room_id'];

// Handle different methods
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Subscribe
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['endpoint']) || !isset($input['keys'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid subscription data']);
        exit;
    }

    $success = savePushSubscription($roomId, $input);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Subscription saved' : 'Failed to save subscription'
    ]);

} elseif ($method === 'DELETE') {
    // Unsubscribe
    $input = json_decode(file_get_contents('php://input'), true);
    $endpoint = $input['endpoint'] ?? '';

    if ($endpoint) {
        $pdo = getDatabase();
        $stmt = $pdo->prepare("
            UPDATE push_subscriptions
            SET is_active = FALSE
            WHERE room_id = :room_id AND endpoint = :endpoint AND hotel_id = :hotel_id
        ");
        $stmt->execute(['room_id' => $roomId, 'endpoint' => $endpoint, 'hotel_id' => getHotelId()]);
    }

    echo json_encode(['success' => true, 'message' => 'Unsubscribed']);

} elseif ($method === 'GET') {
    // Get VAPID public key
    $vapidKeys = getVapidKeys();

    echo json_encode([
        'success' => true,
        'publicKey' => $vapidKeys['publicKey']
    ]);

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
