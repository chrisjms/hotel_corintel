<?php
/**
 * QR Access & Room Service Session Functions
 * QR tokens, sessions, scan logs, delivery time estimation, predictive suggestions
 */

function generateRoomServiceToken(int $roomId, string $roomNumber): string {
    $data = $roomId . ':' . $roomNumber;
    $hash = hash_hmac('sha256', $data, ROOM_SERVICE_SECRET_KEY);
    // Return first 32 chars for shorter URLs while maintaining security
    return substr($hash, 0, 32);
}

/**
 * Validate a room service access token
 *
 * @param int $roomId The room ID from URL
 * @param string $token The token from URL
 * @return array ['valid' => bool, 'room' => array|null, 'error' => string|null]
 */
function validateRoomServiceAccess(int $roomId, string $token): array {
    // Get room from database
    $room = getRoomById($roomId);

    if (!$room) {
        return [
            'valid' => false,
            'room' => null,
            'error' => 'room_not_found'
        ];
    }

    // Check if room is active
    if (!$room['is_active']) {
        return [
            'valid' => false,
            'room' => null,
            'error' => 'room_inactive'
        ];
    }

    // Validate token
    $expectedToken = generateRoomServiceToken($roomId, $room['room_number']);

    if (!hash_equals($expectedToken, $token)) {
        return [
            'valid' => false,
            'room' => null,
            'error' => 'invalid_token'
        ];
    }

    return [
        'valid' => true,
        'room' => $room,
        'error' => null
    ];
}

/**
 * Generate the full room service URL for a room (for QR codes)
 *
 * @param int $roomId The room ID
 * @param string $roomNumber The room number
 * @param string|null $baseUrl Optional base URL (defaults to current server)
 * @return string The full URL
 */
function generateRoomServiceUrl(int $roomId, string $roomNumber, ?string $baseUrl = null): string {
    $token = generateRoomServiceToken($roomId, $roomNumber);

    if ($baseUrl === null) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host;
    }

    return $baseUrl . '/scan.php?room=' . $roomId . '&token=' . $token;
}

/**
 * Store room access in session
 *
 * @param array $room The room data
 */
function setRoomServiceSession(array $room): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['room_service_access'] = [
        'room_id' => $room['id'],
        'room_number' => $room['room_number'],
        'room_name' => $room['name'] ?? null,
        'floor' => $room['floor'] ?? null,
        'accessed_at' => time()
    ];
}

/**
 * Get current room service session
 *
 * @return array|null Room access data or null
 */
function getRoomServiceSession(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['room_service_access'] ?? null;
}

/**
 * Check if room service access is valid (via URL params or session)
 *
 * @return array ['valid' => bool, 'room' => array|null, 'error' => string|null]
 */
function checkRoomServiceAccess(): array {
    // First, check if there are URL parameters
    $roomId = isset($_GET['room']) ? (int)$_GET['room'] : null;
    $token = $_GET['token'] ?? null;

    if ($roomId && $token) {
        // Validate URL-based access
        $validation = validateRoomServiceAccess($roomId, $token);

        if ($validation['valid']) {
            // Store in session for subsequent requests
            setRoomServiceSession($validation['room']);
            return $validation;
        }

        return $validation;
    }

    // Check session-based access
    $session = getRoomServiceSession();
    if ($session) {
        // Verify room still exists and is active
        $room = getRoomById($session['room_id']);
        if ($room && $room['is_active']) {
            return [
                'valid' => true,
                'room' => $room,
                'error' => null
            ];
        }
    }

    // No valid access
    return [
        'valid' => false,
        'room' => null,
        'error' => 'no_access'
    ];
}

/**
 * Clear room service session
 */
function clearRoomServiceSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['room_service_access']);
}

// =====================================================
// ROOM SESSION ACCESS GUARDS
// =====================================================

/**
 * Returns true if the current visitor has an active room session.
 * Use this for conditional rendering in templates.
 */
function hasRoomSession(): bool {
    return getRoomServiceSession() !== null;
}

/**
 * Enforce that a valid room session exists.
 * Call at the top of any handler restricted to QR-authenticated guests.
 *
 * - AJAX / JSON requests: returns 403 JSON and exits.
 * - Regular requests: redirects to home page and exits.
 */
function requireRoomSession(bool $isAjax = false): void {
    if (!hasRoomSession()) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'room_session_required']);
            exit;
        }
        header('Location: index.php');
        exit;
    }
}

// =====================================================
// QR CODE SCAN TRACKING
// =====================================================

/**
 * Log a QR code scan for analytics
 */
function logQrScan(int $roomId): bool {
    $pdo = getDatabase();

    try {
        // Insert scan record
        $stmt = $pdo->prepare("
            INSERT INTO qr_scans (room_id, scanned_at, ip_address, user_agent, hotel_id)
            VALUES (:room_id, NOW(), :ip, :ua, :hotel_id)
        ");
        $stmt->execute([
            'room_id' => $roomId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'hotel_id' => getHotelId()
        ]);

        // Update room's last_scan_at and total_scans
        $stmt = $pdo->prepare("
            UPDATE rooms
            SET last_scan_at = NOW(),
                total_scans = COALESCE(total_scans, 0) + 1
            WHERE id = :room_id AND hotel_id = :hotel_id
        ");
        $stmt->execute(['room_id' => $roomId, 'hotel_id' => getHotelId()]);

        return true;
    } catch (PDOException $e) {
        // Table might not exist yet, silently fail
        return false;
    }
}

/**
 * Get QR scan statistics for all rooms
 */
function getQrScanStatistics(): array {
    $pdo = getDatabase();

    try {
        $hotelId = getHotelId();

        // Total scans
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM qr_scans WHERE hotel_id = ?");
        $stmt->execute([$hotelId]);
        $totalScans = (int) $stmt->fetchColumn();

        // Scans today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM qr_scans WHERE DATE(scanned_at) = CURRENT_DATE AND hotel_id = ?");
        $stmt->execute([$hotelId]);
        $scansToday = (int) $stmt->fetchColumn();

        // Scans this week
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM qr_scans WHERE scanned_at >= NOW() - INTERVAL '7 days' AND hotel_id = ?");
        $stmt->execute([$hotelId]);
        $scansThisWeek = (int) $stmt->fetchColumn();

        // Scans this month
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM qr_scans WHERE scanned_at >= NOW() - INTERVAL '30 days' AND hotel_id = ?");
        $stmt->execute([$hotelId]);
        $scansThisMonth = (int) $stmt->fetchColumn();

        // Most scanned rooms
        $stmt = $pdo->prepare("
            SELECT r.id, r.room_number, r.total_scans, r.last_scan_at
            FROM rooms r
            WHERE r.total_scans > 0 AND r.hotel_id = ?
            ORDER BY r.total_scans DESC
            LIMIT 10
        ");
        $stmt->execute([$hotelId]);
        $topRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Scans per day (last 30 days)
        $stmt = $pdo->prepare("
            SELECT DATE(scanned_at) as scan_date, COUNT(*) as count
            FROM qr_scans
            WHERE scanned_at >= NOW() - INTERVAL '30 days' AND hotel_id = ?
            GROUP BY DATE(scanned_at)
            ORDER BY scan_date ASC
        ");
        $stmt->execute([$hotelId]);
        $dailyScans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Scans per hour (distribution)
        $stmt = $pdo->prepare("
            SELECT EXTRACT(HOUR FROM scanned_at) as hour, COUNT(*) as count
            FROM qr_scans
            WHERE hotel_id = ?
            GROUP BY EXTRACT(HOUR FROM scanned_at)
            ORDER BY hour ASC
        ");
        $stmt->execute([$hotelId]);
        $hourlyDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent scans
        $stmt = $pdo->prepare("
            SELECT qs.*, r.room_number
            FROM qr_scans qs
            JOIN rooms r ON qs.room_id = r.id
            WHERE qs.hotel_id = ?
            ORDER BY qs.scanned_at DESC
            LIMIT 20
        ");
        $stmt->execute([$hotelId]);
        $recentScans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_scans' => $totalScans,
            'scans_today' => $scansToday,
            'scans_this_week' => $scansThisWeek,
            'scans_this_month' => $scansThisMonth,
            'top_rooms' => $topRooms,
            'daily_scans' => $dailyScans,
            'hourly_distribution' => $hourlyDistribution,
            'recent_scans' => $recentScans
        ];
    } catch (PDOException $e) {
        // Tables might not exist
        return [
            'total_scans' => 0,
            'scans_today' => 0,
            'scans_this_week' => 0,
            'scans_this_month' => 0,
            'top_rooms' => [],
            'daily_scans' => [],
            'hourly_distribution' => [],
            'recent_scans' => []
        ];
    }
}

/**
 * Get scan history for a specific room
 */
function getRoomScanHistory(int $roomId, int $limit = 50): array {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM qr_scans
            WHERE room_id = :room_id AND hotel_id = :hotel_id
            ORDER BY scanned_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue('room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue('hotel_id', getHotelId(), PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// =====================================================
// DELIVERY TIME & ANALYTICS
// =====================================================

/**
 * Get estimated delivery time based on current kitchen load
 * Returns time in minutes
 */
function getEstimatedDeliveryTime(): array {
    $pdo = getDatabase();

    try {
        // Count pending/preparing orders
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_count
            FROM room_service_orders
            WHERE status IN ('pending', 'confirmed', 'preparing')
            AND created_at >= NOW() - INTERVAL '2 hours'
            AND hotel_id = ?
        ");
        $stmt->execute([getHotelId()]);
        $pendingOrders = (int) $stmt->fetchColumn();

        // Get average preparation time from recent delivered orders
        $stmt = $pdo->prepare("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_time
            FROM room_service_orders
            WHERE status = 'delivered'
            AND updated_at >= NOW() - INTERVAL '7 days'
            AND hotel_id = ?
        ");
        $stmt->execute([getHotelId()]);
        $avgPrepTime = (float) $stmt->fetchColumn() ?: 20; // Default 20 min

        // Calculate estimated time based on queue
        $baseTime = max(15, min($avgPrepTime, 45)); // Between 15-45 min base
        $queueTime = $pendingOrders * 5; // Add 5 min per order in queue
        $estimatedTime = round($baseTime + $queueTime);

        // Cap at reasonable maximum
        $estimatedTime = min($estimatedTime, 90);

        // Determine load level
        $loadLevel = 'normal';
        if ($pendingOrders >= 5) {
            $loadLevel = 'high';
        } elseif ($pendingOrders >= 10) {
            $loadLevel = 'very_high';
        } elseif ($pendingOrders <= 1) {
            $loadLevel = 'low';
        }

        return [
            'estimated_minutes' => $estimatedTime,
            'min_minutes' => max(10, $estimatedTime - 10),
            'max_minutes' => $estimatedTime + 15,
            'pending_orders' => $pendingOrders,
            'load_level' => $loadLevel,
            'message' => getDeliveryTimeMessage($estimatedTime, $loadLevel)
        ];
    } catch (PDOException $e) {
        return [
            'estimated_minutes' => 25,
            'min_minutes' => 15,
            'max_minutes' => 40,
            'pending_orders' => 0,
            'load_level' => 'normal',
            'message' => 'Livraison estimée : 15-40 min'
        ];
    }
}

/**
 * Get delivery time message based on estimate and load
 */
function getDeliveryTimeMessage(int $minutes, string $loadLevel): string {
    $messages = [
        'fr' => [
            'low' => "Livraison rapide : ~{$minutes} min",
            'normal' => "Livraison estimée : {$minutes}-" . ($minutes + 10) . " min",
            'high' => "Cuisine chargée : {$minutes}-" . ($minutes + 15) . " min",
            'very_high' => "Forte affluence : " . ($minutes + 10) . "-" . ($minutes + 20) . " min"
        ]
    ];

    $lang = getCurrentLanguage();
    return $messages[$lang][$loadLevel] ?? $messages['fr'][$loadLevel];
}

/**
 * Get average time from QR scan to order placement
 */
function getAverageTimeFromScanToOrder(int $days = 30): array {
    $pdo = getDatabase();

    try {
        // Find orders that have a matching scan before them
        $stmt = $pdo->prepare("
            SELECT
                AVG(TIMESTAMPDIFF(MINUTE, qs.scanned_at, o.created_at)) as avg_minutes,
                MIN(TIMESTAMPDIFF(MINUTE, qs.scanned_at, o.created_at)) as min_minutes,
                MAX(TIMESTAMPDIFF(MINUTE, qs.scanned_at, o.created_at)) as max_minutes,
                COUNT(*) as sample_count
            FROM room_service_orders o
            INNER JOIN qr_scans qs ON o.room_id = qs.room_id
                AND qs.scanned_at <= o.created_at
                AND qs.scanned_at >= o.created_at - INTERVAL '2 hours'
            WHERE o.created_at >= NOW() - CAST(:days AS INTEGER) * INTERVAL '1 day'
            AND o.status != 'cancelled'
            AND o.hotel_id = :hotel_id
        ");
        $stmt->execute(['days' => $days, 'hotel_id' => getHotelId()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get total scans in the period
        $stmtScans = $pdo->prepare("
            SELECT COUNT(*) as total_scans
            FROM qr_scans
            WHERE scanned_at >= NOW() - CAST(:days AS INTEGER) * INTERVAL '1 day'
            AND hotel_id = :hotel_id
        ");
        $stmtScans->execute(['days' => $days, 'hotel_id' => getHotelId()]);
        $totalScans = (int) $stmtScans->fetchColumn();

        // Get total orders in the period
        $stmtOrders = $pdo->prepare("
            SELECT COUNT(*) as total_orders
            FROM room_service_orders
            WHERE created_at >= NOW() - CAST(:days AS INTEGER) * INTERVAL '1 day'
            AND status != 'cancelled'
            AND hotel_id = :hotel_id
        ");
        $stmtOrders->execute(['days' => $days, 'hotel_id' => getHotelId()]);
        $totalOrders = (int) $stmtOrders->fetchColumn();

        // Calculate conversion rate
        $conversionRate = $totalScans > 0 ? round(($totalOrders / $totalScans) * 100) : 0;

        return [
            'average_minutes' => round($result['avg_minutes'] ?? 0),
            'min_minutes' => round($result['min_minutes'] ?? 0),
            'max_minutes' => round($result['max_minutes'] ?? 0),
            'sample_count' => (int) ($result['sample_count'] ?? 0),
            'total_scans' => $totalScans,
            'total_orders' => $totalOrders,
            'conversion_rate' => $conversionRate
        ];
    } catch (PDOException $e) {
        return [
            'average_minutes' => 0,
            'min_minutes' => 0,
            'max_minutes' => 0,
            'sample_count' => 0,
            'total_scans' => 0,
            'total_orders' => 0,
            'conversion_rate' => 0
        ];
    }
}

/**
 * Get predictive preparation suggestions based on historical patterns
 * Returns a structured array for dashboard display
 */
function getPredictivePreparationSuggestions(): array {
    $pdo = getDatabase();
    $currentHour = (int) date('H');
    $pgDay = (int)date('w'); // PostgreSQL DOW: 0=Sunday, 1=Monday, etc.

    $result = [
        'popular_items' => [],
        'peak_warnings' => [],
        'predicted_volume' => null,
        'time_range' => sprintf('%02d:00 - %02d:00', $currentHour, ($currentHour + 2) % 24)
    ];

    try {
        // Get popular items for this week (simpler query without JSON_TABLE for compatibility)
        $stmt = $pdo->prepare("
            SELECT
                SUBSTRING_INDEX(SUBSTRING_INDEX(items, '\"name\":\"', -1), '\"', 1) as name,
                COUNT(*) as order_count
            FROM room_service_orders
            WHERE status IN ('delivered', 'preparing', 'confirmed')
            AND created_at >= NOW() - INTERVAL '7 days'
            AND hotel_id = ?
            GROUP BY name
            ORDER BY order_count DESC
            LIMIT 6
        ");
        $stmt->execute([getHotelId()]);
        $result['popular_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get average orders for this day of week
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_orders,
                COUNT(*) / 4.0 as avg_per_day
            FROM room_service_orders
            WHERE status = 'delivered'
            AND created_at >= NOW() - INTERVAL '28 days'
            AND EXTRACT(DOW FROM created_at) = :day
            AND hotel_id = :hotel_id
        ");
        $stmt->execute(['day' => $pgDay, 'hotel_id' => getHotelId()]);
        $volumeData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($volumeData && $volumeData['avg_per_day'] > 0) {
            $result['predicted_volume'] = [
                'today' => round($volumeData['avg_per_day']),
                'same_day_avg' => round($volumeData['avg_per_day'])
            ];
        }

        // Peak hours warning
        $peakHours = [12, 13, 19, 20, 21];
        $nextHour = ($currentHour + 1) % 24;
        if (in_array($nextHour, $peakHours)) {
            $result['peak_warnings'][] = sprintf(
                "⚠️ Heure de pointe approchante (%02d:00) - Préparez-vous pour une période chargée",
                $nextHour
            );
        }

    } catch (PDOException $e) {
        // Silently fail - suggestions are not critical
    }

    return $result;
}

/**
 * Get order history for a specific room
 */
function getRoomOrderHistory(int $roomId, int $limit = 10): array {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("
            SELECT
                id,
                room_number,
                items,
                total_amount,
                status,
                special_instructions,
                created_at,
                updated_at
            FROM room_service_orders
            WHERE room_id = :room_id AND hotel_id = :hotel_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue('room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue('hotel_id', getHotelId(), PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse items JSON
        foreach ($orders as &$order) {
            $order['items'] = json_decode($order['items'], true) ?: [];
            $order['created_at_formatted'] = date('d/m/Y H:i', strtotime($order['created_at']));
        }

        return $orders;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get frequently ordered items for a room (for quick reorder)
 */
function getRoomFrequentItems(int $roomId, int $limit = 5): array {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("
            SELECT
                oi.item_id,
                oi.item_name,
                SUM(oi.quantity) as total_qty,
                COUNT(DISTINCT o.id) as order_count,
                MAX(o.created_at) as last_ordered
            FROM room_service_orders o
            CROSS JOIN JSON_TABLE(o.items, '\$[*]' COLUMNS (
                item_id INT PATH '\$.id',
                item_name VARCHAR(255) PATH '\$.name',
                quantity INT PATH '\$.quantity'
            )) as oi
            WHERE o.room_id = :room_id
            AND o.status = 'delivered'
            AND o.hotel_id = :hotel_id
            GROUP BY oi.item_id, oi.item_name
            ORDER BY order_count DESC, total_qty DESC
            LIMIT :limit
        ");
        $stmt->bindValue('room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue('hotel_id', getHotelId(), PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
