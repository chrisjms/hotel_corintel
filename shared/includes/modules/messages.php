<?php
/**
 * Guest Messages Functions
 * Messages CRUD, statistics, dashboard counters
 */

/**
 * Get guest message categories
 */
function getGuestMessageCategories(): array {
    return [
        'general' => 'Question générale',
        'heating' => 'Chauffage',
        'ac' => 'Climatisation',
        'tv' => 'Télévision',
        'wifi' => 'Wi-Fi / Internet',
        'plumbing' => 'Plomberie',
        'cleaning' => 'Nettoyage',
        'noise' => 'Bruit',
        'other' => 'Autre'
    ];
}

/**
 * Get guest message statuses
 */
function getGuestMessageStatuses(): array {
    return [
        'new' => 'Nouveau',
        'read' => 'Lu',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu'
    ];
}

/**
 * Create a guest message
 */
function createGuestMessage(array $data): int|false {
    $pdo = getDatabase();

    $stmt = $pdo->prepare('
        INSERT INTO guest_messages (room_number, guest_name, category, subject, message, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ');

    $success = $stmt->execute([
        $data['room_number'],
        $data['guest_name'] ?? null,
        $data['category'] ?? 'general',
        $data['subject'] ?? null,
        $data['message'],
        getHotelId()
    ]);

    return $success ? (int)$pdo->lastInsertId() : false;
}

/**
 * Get all guest messages with optional filters
 */
function getGuestMessages(string $status = '', string $sortBy = 'created_at', string $sortOrder = 'DESC'): array {
    try {
        $pdo = getDatabase();
        $sql = 'SELECT * FROM guest_messages WHERE hotel_id = ?';
        $params = [getHotelId()];

        if ($status && $status !== 'all') {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        $allowedSortColumns = ['created_at', 'room_number', 'category', 'status'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= ' ORDER BY ' . $sortBy . ' ' . $sortOrder;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get a guest message by ID
 */
function getGuestMessageById(int $id): ?array {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT * FROM guest_messages WHERE id = ? AND hotel_id = ?');
        $stmt->execute([$id, getHotelId()]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Update guest message status
 */
function updateGuestMessageStatus(int $id, string $status): bool {
    $validStatuses = ['new', 'read', 'in_progress', 'resolved'];
    if (!in_array($status, $validStatuses)) {
        return false;
    }
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE guest_messages SET status = ? WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$status, $id, getHotelId()]);
}

/**
 * Update guest message admin notes
 */
function updateGuestMessageNotes(int $id, string $notes): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE guest_messages SET admin_notes = ? WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$notes, $id, getHotelId()]);
}

/**
 * Delete a guest message
 */
function deleteGuestMessage(int $id): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('DELETE FROM guest_messages WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$id, getHotelId()]);
}

/**
 * Get guest messages statistics
 */
function getGuestMessagesStats(): array {
    try {
        $pdo = getDatabase();
        $stats = [];

        // Total messages
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM guest_messages WHERE hotel_id = ?');
        $stmt->execute([getHotelId()]);
        $stats['total'] = (int)$stmt->fetchColumn();

        // New (unread) messages
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM guest_messages WHERE status = \'new\' AND hotel_id = ?');
        $stmt->execute([getHotelId()]);
        $stats['new'] = (int)$stmt->fetchColumn();

        // In progress messages
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM guest_messages WHERE status = \'in_progress\' AND hotel_id = ?');
        $stmt->execute([getHotelId()]);
        $stats['in_progress'] = (int)$stmt->fetchColumn();

        // Today's messages
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM guest_messages WHERE DATE(created_at) = CURRENT_DATE AND hotel_id = ?');
        $stmt->execute([getHotelId()]);
        $stats['today'] = (int)$stmt->fetchColumn();

        return $stats;
    } catch (PDOException $e) {
        return ['total' => 0, 'new' => 0, 'in_progress' => 0, 'today' => 0];
    }
}

/**
 * Get unread guest messages count (lightweight for sidebar badge)
 */
function getUnreadMessagesCount(): int {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM guest_messages WHERE status = \'new\' AND hotel_id = ?');
        $stmt->execute([getHotelId()]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get pending room service orders count (lightweight for sidebar badge)
 */
function getPendingOrdersCount(): int {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_service_orders WHERE status = \'pending\' AND hotel_id = ?');
        $stmt->execute([getHotelId()]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
