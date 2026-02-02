<?php
/**
 * Dashboard Real-Time Updates API
 * Returns current counts and recent items for live updates
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

$response = [
    'success' => true,
    'timestamp' => time(),
    'data' => []
];

try {
    $pdo = getDatabase();

    // Get counts for badges
    $response['data']['unreadMessages'] = getUnreadMessagesCount();
    $response['data']['pendingOrders'] = getPendingOrdersCount();

    // Get today's stats
    $today = date('Y-m-d');

    // Orders today count
    $stmtOrdersToday = $pdo->prepare("SELECT COUNT(*) FROM room_service_orders WHERE DATE(created_at) = ?");
    $stmtOrdersToday->execute([$today]);
    $response['data']['ordersToday'] = (int)$stmtOrdersToday->fetchColumn();

    // Messages today count
    $stmtMsgToday = $pdo->prepare("SELECT COUNT(*) FROM guest_messages WHERE DATE(created_at) = ?");
    $stmtMsgToday->execute([$today]);
    $response['data']['messagesToday'] = (int)$stmtMsgToday->fetchColumn();

    // Messages today - new/unread
    $stmtMsgTodayNew = $pdo->prepare("SELECT COUNT(*) FROM guest_messages WHERE DATE(created_at) = ? AND status = 'new'");
    $stmtMsgTodayNew->execute([$today]);
    $response['data']['messagesTodayNew'] = (int)$stmtMsgTodayNew->fetchColumn();

    // Get status labels
    $statusLabels = [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'preparing' => 'En préparation',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée'
    ];

    $msgCategoryLabels = [
        'general' => 'Général',
        'room_issue' => 'Problème chambre',
        'housekeeping' => 'Ménage',
        'maintenance' => 'Maintenance',
        'room_service' => 'Room Service',
        'complaint' => 'Réclamation',
        'other' => 'Autre'
    ];

    // Get 3 most urgent orders (for dashboard)
    $stmtUrgent = $pdo->prepare("
        SELECT id, room_number, delivery_datetime, status, created_at
        FROM room_service_orders
        WHERE delivery_datetime >= NOW() AND status NOT IN ('delivered', 'cancelled')
        ORDER BY delivery_datetime ASC
        LIMIT 3
    ");
    $stmtUrgent->execute();
    $urgentOrders = $stmtUrgent->fetchAll(PDO::FETCH_ASSOC);

    $response['data']['urgentOrders'] = array_map(function($order) use ($statusLabels) {
        return [
            'id' => $order['id'],
            'room_number' => $order['room_number'],
            'delivery_datetime' => date('d/m/Y H:i', strtotime($order['delivery_datetime'])),
            'delivery_time' => date('H:i', strtotime($order['delivery_datetime'])),
            'delivery_relative' => timeAgo($order['delivery_datetime']),
            'status' => $order['status'],
            'status_label' => $statusLabels[$order['status']] ?? $order['status']
        ];
    }, $urgentOrders);

    // Get 3 most recent messages (for dashboard)
    $stmtRecentMsg = $pdo->prepare("
        SELECT id, room_number, guest_name, category, subject, message, status, created_at
        FROM guest_messages
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmtRecentMsg->execute();
    $recentMessages = $stmtRecentMsg->fetchAll(PDO::FETCH_ASSOC);

    $response['data']['recentMessages'] = array_map(function($msg) use ($msgCategoryLabels) {
        return [
            'id' => $msg['id'],
            'room_number' => $msg['room_number'],
            'subject' => $msg['subject'] ?: ($msgCategoryLabels[$msg['category']] ?? $msg['category']),
            'status' => $msg['status'],
            'is_new' => $msg['status'] === 'new',
            'created_at' => date('d/m/Y H:i', strtotime($msg['created_at'])),
            'created_relative' => timeAgo($msg['created_at'])
        ];
    }, $recentMessages);

    // Get orders by status counts (for dashboard stats)
    $stmtStatusCounts = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM room_service_orders
        WHERE DATE(created_at) = CURDATE()
        GROUP BY status
    ");
    $statusCounts = ['pending' => 0, 'confirmed' => 0, 'preparing' => 0, 'delivered' => 0, 'cancelled' => 0];
    while ($row = $stmtStatusCounts->fetch(PDO::FETCH_ASSOC)) {
        $statusCounts[$row['status']] = (int)$row['count'];
    }
    $response['data']['orderStatusCounts'] = $statusCounts;

    // Upcoming deliveries count
    $stmtUpcoming = $pdo->prepare("
        SELECT COUNT(*) FROM room_service_orders
        WHERE delivery_datetime >= NOW() AND status NOT IN ('delivered', 'cancelled')
    ");
    $stmtUpcoming->execute();
    $response['data']['upcomingDeliveries'] = (int)$stmtUpcoming->fetchColumn();

} catch (PDOException $e) {
    $response['success'] = false;
    $response['error'] = 'Database error';
}

echo json_encode($response);
