<?php
/**
 * Orders Page Real-Time Updates API
 * Returns current orders list with filters
 * Hotel Corintel
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'delivery_datetime';
$sortOrder = $_GET['order'] ?? 'ASC';
$deliveryDateFilter = $_GET['delivery_date'] ?? null;

$response = [
    'success' => true,
    'timestamp' => time(),
    'data' => []
];

try {
    $pdo = getDatabase();

    // Get badge counts
    $response['data']['unreadMessages'] = getUnreadMessagesCount();
    $response['data']['pendingOrders'] = getPendingOrdersCount();

    // Get stats
    $stats = getRoomServiceStats();
    $response['data']['stats'] = $stats;

    // Get status labels
    $statuses = getRoomServiceOrderStatuses();
    $paymentMethods = getRoomServicePaymentMethods();

    // Get orders with filters
    $orders = getRoomServiceOrders($statusFilter, $sortBy, $sortOrder, $deliveryDateFilter);

    $response['data']['orders'] = array_map(function($order) use ($statuses, $paymentMethods) {
        // Check urgency
        $deliveryTime = strtotime($order['delivery_datetime'] ?? '');
        $isUrgent = $deliveryTime && $deliveryTime <= time() + (2 * 60 * 60) && $deliveryTime > time() && $order['status'] !== 'delivered' && $order['status'] !== 'cancelled';
        $isPast = $deliveryTime && $deliveryTime < time() && $order['status'] !== 'delivered' && $order['status'] !== 'cancelled';

        return [
            'id' => (int)$order['id'],
            'room_number' => $order['room_number'],
            'guest_name' => $order['guest_name'] ?? '-',
            'total_amount' => number_format($order['total_amount'], 2, ',', ' '),
            'delivery_datetime' => $order['delivery_datetime'] ? date('d/m/Y H:i', strtotime($order['delivery_datetime'])) : '-',
            'delivery_raw' => $order['delivery_datetime'],
            'status' => $order['status'],
            'status_label' => $statuses[$order['status']] ?? $order['status'],
            'created_at' => date('d/m/Y H:i', strtotime($order['created_at'])),
            'is_urgent' => $isUrgent,
            'is_past' => $isPast
        ];
    }, $orders);

    $response['data']['orderCount'] = count($orders);

} catch (PDOException $e) {
    $response['success'] = false;
    $response['error'] = 'Database error';
}

echo json_encode($response);
