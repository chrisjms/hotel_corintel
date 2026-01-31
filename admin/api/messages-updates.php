<?php
/**
 * Messages Page Real-Time Updates API
 * Returns current messages list with filters
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
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';

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
    $stats = getGuestMessagesStats();
    $response['data']['stats'] = $stats;

    // Get status and category labels
    $statuses = getGuestMessageStatuses();
    $categories = getGuestMessageCategories();

    // Get messages with filters
    $messages = getGuestMessages($statusFilter, $sortBy, $sortOrder);

    $response['data']['messages'] = array_map(function($msg) use ($statuses, $categories) {
        return [
            'id' => (int)$msg['id'],
            'room_number' => $msg['room_number'],
            'guest_name' => $msg['guest_name'] ?? '-',
            'category' => $msg['category'],
            'category_label' => $categories[$msg['category']] ?? $msg['category'],
            'subject' => $msg['subject'] ?? '',
            'message_preview' => mb_substr($msg['message'], 0, 100) . (mb_strlen($msg['message']) > 100 ? '...' : ''),
            'status' => $msg['status'],
            'status_label' => $statuses[$msg['status']] ?? $msg['status'],
            'created_at' => formatDate($msg['created_at']),
            'is_new' => $msg['status'] === 'new'
        ];
    }, $messages);

    $response['data']['messageCount'] = count($messages);

} catch (PDOException $e) {
    $response['success'] = false;
    $response['error'] = 'Database error';
}

echo json_encode($response);
