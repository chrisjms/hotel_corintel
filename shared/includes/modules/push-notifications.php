<?php
/**
 * Push Notifications Functions
 * VAPID keys, subscriptions, send/queue notifications
 */

function getVapidKeys(): array {
    // Generate with: npx web-push generate-vapid-keys
    // These are example keys - replace with your own in production
    return [
        'publicKey' => getSettingValue('vapid_public_key', 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U'),
        'privateKey' => getSettingValue('vapid_private_key', 'UUxI4O8-FbRouAevSmBQ6o18hgE4nSG3qwvJTfKc-ls')
    ];
}

/**
 * Save push subscription for a room
 */
function savePushSubscription(int $roomId, array $subscription): bool {
    $pdo = getDatabase();

    try {
        // Check if subscription already exists
        $stmt = $pdo->prepare("
            SELECT id FROM push_subscriptions
            WHERE room_id = :room_id AND endpoint = :endpoint AND hotel_id = :hotel_id
        ");
        $stmt->execute([
            'room_id' => $roomId,
            'endpoint' => $subscription['endpoint'],
            'hotel_id' => getHotelId()
        ]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing subscription
            $stmt = $pdo->prepare("
                UPDATE push_subscriptions
                SET p256dh_key = :p256dh,
                    auth_key = :auth,
                    user_agent = :ua,
                    is_active = TRUE,
                    last_used_at = NOW()
                WHERE id = :id AND hotel_id = :hotel_id
            ");
            return $stmt->execute([
                'p256dh' => $subscription['keys']['p256dh'],
                'auth' => $subscription['keys']['auth'],
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'id' => $existing['id'],
                'hotel_id' => getHotelId()
            ]);
        } else {
            // Insert new subscription
            $stmt = $pdo->prepare("
                INSERT INTO push_subscriptions (room_id, endpoint, p256dh_key, auth_key, user_agent, hotel_id)
                VALUES (:room_id, :endpoint, :p256dh, :auth, :ua, :hotel_id)
            ");
            return $stmt->execute([
                'room_id' => $roomId,
                'endpoint' => $subscription['endpoint'],
                'p256dh' => $subscription['keys']['p256dh'],
                'auth' => $subscription['keys']['auth'],
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'hotel_id' => getHotelId()
            ]);
        }
    } catch (PDOException $e) {
        error_log('Push subscription save error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get push subscriptions for a room
 */
function getRoomPushSubscriptions(int $roomId): array {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM push_subscriptions
            WHERE room_id = :room_id AND is_active = TRUE AND hotel_id = :hotel_id
        ");
        $stmt->execute(['room_id' => $roomId, 'hotel_id' => getHotelId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Send push notification to a room
 * Requires web-push library or external service
 */
function sendPushNotification(int $roomId, array $payload): array {
    $subscriptions = getRoomPushSubscriptions($roomId);
    $results = ['sent' => 0, 'failed' => 0];

    if (empty($subscriptions)) {
        return $results;
    }

    $vapidKeys = getVapidKeys();

    foreach ($subscriptions as $sub) {
        try {
            // Build the notification payload
            $data = json_encode([
                'title' => $payload['title'] ?? 'Room Service',
                'body' => $payload['body'] ?? '',
                'data' => $payload['data'] ?? [],
                'actions' => $payload['actions'] ?? []
            ]);

            // In production, use a proper web-push library
            // For now, we'll store the notification for polling
            $sent = queuePushNotification($sub['id'], $data);

            if ($sent) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        } catch (Exception $e) {
            $results['failed']++;
            error_log('Push notification error: ' . $e->getMessage());
        }
    }

    return $results;
}

/**
 * Queue a push notification (fallback when web-push is not available)
 */
function queuePushNotification(int $subscriptionId, string $payload): bool {
    // This is a simple polling-based fallback
    // In production, implement actual web-push
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("
            UPDATE push_subscriptions
            SET last_used_at = NOW()
            WHERE id = :id AND hotel_id = :hotel_id
        ");
        return $stmt->execute(['id' => $subscriptionId, 'hotel_id' => getHotelId()]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Remove invalid push subscription
 */
function removePushSubscription(int $subscriptionId): bool {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("
            UPDATE push_subscriptions SET is_active = FALSE WHERE id = :id AND hotel_id = :hotel_id
        ");
        return $stmt->execute(['id' => $subscriptionId, 'hotel_id' => getHotelId()]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Send order status notification
 */
function sendOrderStatusNotification(int $orderId, string $newStatus): bool {
    $pdo = getDatabase();

    try {
        // Get order details
        $stmt = $pdo->prepare("SELECT room_id, room_number FROM room_service_orders WHERE id = :id AND hotel_id = :hotel_id");
        $stmt->execute(['id' => $orderId, 'hotel_id' => getHotelId()]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order || !$order['room_id']) {
            return false;
        }

        // Build notification message
        $messages = [
            'confirmed' => [
                'title' => 'Commande confirmée',
                'body' => 'Votre commande a été confirmée et est en préparation.'
            ],
            'preparing' => [
                'title' => 'En préparation',
                'body' => 'Votre commande est en cours de préparation en cuisine.'
            ],
            'ready' => [
                'title' => 'Commande prête',
                'body' => 'Votre commande est prête et arrive bientôt !'
            ],
            'delivered' => [
                'title' => 'Livré !',
                'body' => 'Votre commande a été livrée. Bon appétit !'
            ],
            'cancelled' => [
                'title' => 'Commande annulée',
                'body' => 'Votre commande a été annulée. Contactez la réception pour plus d\'informations.'
            ]
        ];

        $notification = $messages[$newStatus] ?? null;
        if (!$notification) {
            return false;
        }

        $notification['data'] = [
            'orderId' => $orderId,
            'status' => $newStatus,
            'url' => '/room-service.php'
        ];

        $result = sendPushNotification($order['room_id'], $notification);
        return $result['sent'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}
