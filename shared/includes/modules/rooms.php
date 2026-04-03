<?php
/**
 * Rooms Functions
 * Room CRUD, statuses, housekeeping, room types
 */

function getRoomStatuses(): array {
    return [
        ROOM_STATUS_AVAILABLE => 'Disponible',
        ROOM_STATUS_OCCUPIED => 'Occupée',
        ROOM_STATUS_MAINTENANCE => 'Maintenance',
        ROOM_STATUS_OUT_OF_ORDER => 'Hors service'
    ];
}

/**
 * Get all housekeeping statuses with labels
 * @return array Associative array of status => label
 */
function getHousekeepingStatuses(): array {
    return [
        HOUSEKEEPING_PENDING => 'En attente',
        HOUSEKEEPING_IN_PROGRESS => 'En cours',
        HOUSEKEEPING_CLEANED => 'Nettoyée',
        HOUSEKEEPING_INSPECTED => 'Inspectée'
    ];
}

/**
 * Get all room types with labels
 * @return array Associative array of type => label
 */
function getRoomTypes(): array {
    return [
        ROOM_TYPE_SINGLE => 'Chambre Simple',
        ROOM_TYPE_DOUBLE => 'Chambre Double',
        ROOM_TYPE_TWIN => 'Chambre Twin',
        ROOM_TYPE_SUITE => 'Suite',
        ROOM_TYPE_FAMILY => 'Chambre Familiale',
        ROOM_TYPE_ACCESSIBLE => 'Chambre Accessible'
    ];
}

/**
 * Get status badge CSS class
 * @param string $status Room status
 * @return string CSS class
 */
function getRoomStatusBadgeClass(string $status): string {
    return match($status) {
        ROOM_STATUS_AVAILABLE => 'badge-success',
        ROOM_STATUS_OCCUPIED => 'badge-info',
        ROOM_STATUS_MAINTENANCE => 'badge-warning',
        ROOM_STATUS_OUT_OF_ORDER => 'badge-error',
        default => 'badge-default'
    };
}

/**
 * Get housekeeping status badge CSS class
 * @param string $status Housekeeping status
 * @return string CSS class
 */
function getHousekeepingStatusBadgeClass(string $status): string {
    return match($status) {
        HOUSEKEEPING_PENDING => 'badge-warning',
        HOUSEKEEPING_IN_PROGRESS => 'badge-info',
        HOUSEKEEPING_CLEANED => 'badge-success',
        HOUSEKEEPING_INSPECTED => 'badge-primary',
        default => 'badge-default'
    };
}

/**
 * Get all rooms with optional filtering
 * @param array $filters Optional filters: status, housekeeping_status, floor, room_type, is_active
 * @param string $orderBy Order by column
 * @param string $orderDir Order direction (ASC/DESC)
 * @return array List of rooms
 */
function getRooms(array $filters = [], string $orderBy = 'room_number', string $orderDir = 'ASC'): array {
    $pdo = getDatabase();

    $sql = "SELECT * FROM rooms WHERE hotel_id = ?";
    $params = [getHotelId()];

    if (isset($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }

    if (isset($filters['housekeeping_status'])) {
        $sql .= " AND housekeeping_status = ?";
        $params[] = $filters['housekeeping_status'];
    }

    if (isset($filters['floor'])) {
        $sql .= " AND floor = ?";
        $params[] = $filters['floor'];
    }

    if (isset($filters['room_type'])) {
        $sql .= " AND room_type = ?";
        $params[] = $filters['room_type'];
    }

    if (isset($filters['is_active'])) {
        $sql .= " AND is_active = ?";
        $params[] = $filters['is_active'] ? true : false;
    } else {
        // Default: only show active rooms
        $sql .= " AND is_active = TRUE";
    }

    if (isset($filters['search']) && !empty($filters['search'])) {
        $sql .= " AND (room_number LIKE ? OR notes LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Validate order by column
    $allowedColumns = ['room_number', 'floor', 'room_type', 'status', 'housekeeping_status', 'created_at', 'updated_at'];
    if (!in_array($orderBy, $allowedColumns)) {
        $orderBy = 'room_number';
    }
    $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

    $sql .= " ORDER BY $orderBy $orderDir";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll();

        // Decode JSON amenities
        foreach ($rooms as &$room) {
            $room['amenities'] = $room['amenities'] ? json_decode($room['amenities'], true) : [];
        }

        return $rooms;
    } catch (PDOException $e) {
        error_log('Error fetching rooms: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a single room by ID
 * @param int $id Room ID
 * @return array|null Room data or null if not found
 */
function getRoomById(int $id): ?array {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND hotel_id = ?");
        $stmt->execute([$id, getHotelId()]);
        $room = $stmt->fetch();

        if ($room) {
            $room['amenities'] = $room['amenities'] ? json_decode($room['amenities'], true) : [];
        }

        return $room ?: null;
    } catch (PDOException $e) {
        error_log('Error fetching room: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get a single room by room number
 * @param string $roomNumber Room number
 * @return array|null Room data or null if not found
 */
function getRoomByNumber(string $roomNumber): ?array {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_number = ? AND hotel_id = ?");
        $stmt->execute([$roomNumber, getHotelId()]);
        $room = $stmt->fetch();

        if ($room) {
            $room['amenities'] = $room['amenities'] ? json_decode($room['amenities'], true) : [];
        }

        return $room ?: null;
    } catch (PDOException $e) {
        error_log('Error fetching room: ' . $e->getMessage());
        return null;
    }
}

/**
 * Create a new room
 * @param array $data Room data
 * @param string|null $error Error message reference
 * @return int|false Room ID on success, false on failure
 */
function createRoom(array $data, ?string &$error = null): int|false {
    $pdo = getDatabase();

    $sql = "INSERT INTO rooms (
        room_number, floor, room_type, capacity, bed_count, surface_area,
        status, housekeeping_status, amenities, notes, is_active, hotel_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['room_number'],
            $data['floor'] ?? null,
            $data['room_type'] ?? ROOM_TYPE_DOUBLE,
            $data['capacity'] ?? 2,
            $data['bed_count'] ?? 1,
            $data['surface_area'] ?? null,
            $data['status'] ?? ROOM_STATUS_AVAILABLE,
            $data['housekeeping_status'] ?? HOUSEKEEPING_CLEANED,
            isset($data['amenities']) ? json_encode($data['amenities']) : null,
            $data['notes'] ?? null,
            $data['is_active'] ?? 1,
            getHotelId()
        ]);

        return (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error creating room: ' . $e->getMessage());
        if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Table") !== false) {
            $error = "La table 'rooms' n'existe pas. Veuillez exécuter la migration 005_create_rooms_table.sql";
        } else {
            $error = $e->getMessage();
        }
        return false;
    }
}

/**
 * Update a room
 * @param int $id Room ID
 * @param array $data Room data to update
 * @return bool Success status
 */
function updateRoom(int $id, array $data): bool {
    $pdo = getDatabase();

    $fields = [];
    $params = [];

    $allowedFields = [
        'room_number', 'floor', 'room_type', 'capacity', 'bed_count',
        'surface_area', 'status', 'housekeeping_status', 'notes', 'is_active'
    ];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    // Handle amenities separately (JSON)
    if (array_key_exists('amenities', $data)) {
        $fields[] = "amenities = ?";
        $params[] = is_array($data['amenities']) ? json_encode($data['amenities']) : $data['amenities'];
    }

    // Handle timestamp updates
    if (isset($data['housekeeping_status'])) {
        if ($data['housekeeping_status'] === HOUSEKEEPING_CLEANED) {
            $fields[] = "last_cleaned_at = NOW()";
        } elseif ($data['housekeeping_status'] === HOUSEKEEPING_INSPECTED) {
            $fields[] = "last_inspection_at = NOW()";
        }
    }

    if (empty($fields)) {
        return false;
    }

    $sql = "UPDATE rooms SET " . implode(', ', $fields) . " WHERE id = ? AND hotel_id = ?";
    $params[] = $id;
    $params[] = getHotelId();

    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log('Error updating room: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a room (soft delete by setting is_active = 0)
 * @param int $id Room ID
 * @param bool $hardDelete If true, permanently delete the room
 * @return bool Success status
 */
function deleteRoom(int $id, bool $hardDelete = false): bool {
    $pdo = getDatabase();

    try {
        if ($hardDelete) {
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ? AND hotel_id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE rooms SET is_active = FALSE WHERE id = ? AND hotel_id = ?");
        }
        return $stmt->execute([$id, getHotelId()]);
    } catch (PDOException $e) {
        error_log('Error deleting room: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update room status
 * @param int $id Room ID
 * @param string $status New status
 * @return bool Success status
 */
function updateRoomStatus(int $id, string $status): bool {
    $validStatuses = array_keys(getRoomStatuses());
    if (!in_array($status, $validStatuses)) {
        return false;
    }

    return updateRoom($id, ['status' => $status]);
}

/**
 * Update room housekeeping status
 * @param int $id Room ID
 * @param string $status New housekeeping status
 * @return bool Success status
 */
function updateRoomHousekeepingStatus(int $id, string $status): bool {
    $validStatuses = array_keys(getHousekeepingStatuses());
    if (!in_array($status, $validStatuses)) {
        return false;
    }

    return updateRoom($id, ['housekeeping_status' => $status]);
}

/**
 * Get room statistics summary
 * @return array Statistics including counts by status
 */
function getRoomStatistics(): array {
    $pdo = getDatabase();

    try {
        // Total rooms
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rooms WHERE is_active = TRUE AND hotel_id = ?");
        $stmt->execute([getHotelId()]);
        $total = $stmt->fetch()['total'];

        // By status
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as count
            FROM rooms
            WHERE is_active = TRUE AND hotel_id = ?
            GROUP BY status
        ");
        $stmt->execute([getHotelId()]);
        $byStatus = [];
        while ($row = $stmt->fetch()) {
            $byStatus[$row['status']] = (int) $row['count'];
        }

        // By housekeeping status
        $stmt = $pdo->prepare("
            SELECT housekeeping_status, COUNT(*) as count
            FROM rooms
            WHERE is_active = TRUE AND hotel_id = ?
            GROUP BY housekeeping_status
        ");
        $stmt->execute([getHotelId()]);
        $byHousekeeping = [];
        while ($row = $stmt->fetch()) {
            $byHousekeeping[$row['housekeeping_status']] = (int) $row['count'];
        }

        // By floor
        $stmt = $pdo->prepare("
            SELECT COALESCE(floor, 0) as floor, COUNT(*) as count
            FROM rooms
            WHERE is_active = TRUE AND hotel_id = ?
            GROUP BY floor
            ORDER BY floor
        ");
        $stmt->execute([getHotelId()]);
        $byFloor = [];
        while ($row = $stmt->fetch()) {
            $byFloor[$row['floor']] = (int) $row['count'];
        }

        // By room type
        $stmt = $pdo->prepare("
            SELECT room_type, COUNT(*) as count
            FROM rooms
            WHERE is_active = TRUE AND hotel_id = ?
            GROUP BY room_type
        ");
        $stmt->execute([getHotelId()]);
        $byType = [];
        while ($row = $stmt->fetch()) {
            $byType[$row['room_type']] = (int) $row['count'];
        }

        return [
            'total' => (int) $total,
            'by_status' => $byStatus,
            'by_housekeeping' => $byHousekeeping,
            'by_floor' => $byFloor,
            'by_type' => $byType
        ];
    } catch (PDOException $e) {
        error_log('Error getting room statistics: ' . $e->getMessage());
        return [
            'total' => 0,
            'by_status' => [],
            'by_housekeeping' => [],
            'by_floor' => [],
            'by_type' => []
        ];
    }
}

/**
 * Get list of unique floors
 * @return array List of floor numbers
 */
function getRoomFloors(): array {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT COALESCE(floor, 0) as floor
            FROM rooms
            WHERE is_active = TRUE AND hotel_id = ?
            ORDER BY floor
        ");
        $stmt->execute([getHotelId()]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log('Error fetching floors: ' . $e->getMessage());
        return [];
    }
}

/**
 * Check if room number exists
 * @param string $roomNumber Room number to check
 * @param int|null $excludeId Room ID to exclude (for updates)
 * @return bool True if exists
 */
function roomNumberExists(string $roomNumber, ?int $excludeId = null): bool {
    $pdo = getDatabase();

    try {
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ? AND id != ? AND hotel_id = ?");
            $stmt->execute([$roomNumber, $excludeId, getHotelId()]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ? AND hotel_id = ?");
            $stmt->execute([$roomNumber, getHotelId()]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Log housekeeping activity
 * @param int $roomId Room ID
 * @param string $action Action type
 * @param array $data Additional data
 * @return bool Success status
 */
function logHousekeepingActivity(int $roomId, string $action, array $data = []): bool {
    $pdo = getDatabase();

    $sql = "INSERT INTO housekeeping_logs (room_id, action, previous_status, new_status, performed_by, notes, hotel_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $roomId,
            $action,
            $data['previous_status'] ?? null,
            $data['new_status'] ?? null,
            $data['performed_by'] ?? null,
            $data['notes'] ?? null,
            getHotelId()
        ]);
    } catch (PDOException $e) {
        error_log('Error logging housekeeping activity: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get total room count (for sidebar badge)
 * @return int Total active rooms
 */
function getTotalRoomCount(): int {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE is_active = TRUE AND hotel_id = ?");
        $stmt->execute([getHotelId()]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
