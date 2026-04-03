<?php
/**
 * Room Service Functions
 * Items, orders, categories CRUD, statuses, availability
 */

function getRoomServiceItems(bool $activeOnly = false): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM room_service_items WHERE hotel_id = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = TRUE';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([getHotelId()]);
    return $stmt->fetchAll();
}

/**
 * Get a room service item by ID
 */
function getRoomServiceItemById(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM room_service_items WHERE id = ? AND hotel_id = ?');
    $stmt->execute([$id, getHotelId()]);
    return $stmt->fetch() ?: null;
}

/**
 * Create a room service item
 */
function createRoomServiceItem(array $data): int|false {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        INSERT INTO room_service_items (name, description, price, image, category, is_active, position, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $success = $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['price'],
        $data['image'] ?? null,
        $data['category'] ?? 'general',
        $data['is_active'] ?? 1,
        $data['position'] ?? 0,
        getHotelId()
    ]);
    return $success ? (int)$pdo->lastInsertId() : false;
}

/**
 * Update a room service item
 */
function updateRoomServiceItem(int $id, array $data): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE room_service_items
        SET name = ?, description = ?, price = ?, image = ?, category = ?, is_active = ?, position = ?
        WHERE id = ? AND hotel_id = ?
    ');
    return $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['price'],
        $data['image'] ?? null,
        $data['category'] ?? 'general',
        $data['is_active'] ?? 1,
        $data['position'] ?? 0,
        $id,
        getHotelId()
    ]);
}

/**
 * Toggle room service item status
 */
function toggleRoomServiceItemStatus(int $id): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE room_service_items SET is_active = NOT is_active WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$id, getHotelId()]);
}

/**
 * Delete a room service item
 */
function deleteRoomServiceItem(int $id): bool {
    $pdo = getDatabase();
    // Check if item is used in any order
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_service_order_items WHERE item_id = ? AND hotel_id = ?');
    $stmt->execute([$id, getHotelId()]);
    if ($stmt->fetchColumn() > 0) {
        return false; // Cannot delete, item is used in orders
    }
    $stmt = $pdo->prepare('DELETE FROM room_service_items WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$id, getHotelId()]);
}

/**
 * Handle room service item image upload
 */
function handleRoomServiceItemImageUpload(array $file, int $itemId): array {
    $validation = validateUploadedFile($file);
    if (!$validation['valid']) {
        return $validation;
    }

    $item = getRoomServiceItemById($itemId);
    if (!$item) {
        return ['valid' => false, 'message' => 'Article non trouvé.'];
    }

    $extension = $validation['extension'];
    $newFilename = sprintf('room_service_%d_%d.%s', $itemId, time(), $extension);
    $uploadPath = UPLOAD_DIR . $newFilename;

    if (!is_dir(UPLOAD_DIR)) {
        if (!@mkdir(UPLOAD_DIR, 0755, true)) {
            return ['valid' => false, 'message' => 'Impossible de créer le dossier uploads.'];
        }
    }

    if (!is_writable(UPLOAD_DIR)) {
        return ['valid' => false, 'message' => 'Le dossier uploads n\'est pas accessible en écriture.'];
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['valid' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier.'];
    }

    // Delete old image if exists
    if (!empty($item['image']) && strpos($item['image'], 'uploads/') === 0) {
        $oldPath = __DIR__ . '/../' . $item['image'];
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    $relativePath = 'uploads/' . $newFilename;
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE room_service_items SET image = ? WHERE id = ? AND hotel_id = ?');
    if (!$stmt->execute([$relativePath, $itemId, getHotelId()])) {
        @unlink($uploadPath);
        return ['valid' => false, 'message' => 'Erreur lors de la mise à jour de la base de données.'];
    }

    return ['valid' => true, 'message' => 'Image mise à jour avec succès.', 'filename' => $relativePath];
}

/**
 * Get all room service orders
 */
function getRoomServiceOrders(string $status = '', string $sortBy = 'created_at', string $sortOrder = 'DESC', ?string $deliveryDate = null): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM room_service_orders';
    $params = [];
    $conditions = ['hotel_id = ?'];
    $params[] = getHotelId();

    if ($status && $status !== 'all') {
        $conditions[] = 'status = ?';
        $params[] = $status;
    }

    if ($deliveryDate) {
        $conditions[] = 'DATE(delivery_datetime) = ?';
        $params[] = $deliveryDate;
    }

    $sql .= ' WHERE ' . implode(' AND ', $conditions);

    // Validate sort column
    $allowedSortColumns = ['created_at', 'delivery_datetime', 'total_amount', 'room_number'];
    if (!in_array($sortBy, $allowedSortColumns)) {
        $sortBy = 'created_at';
    }
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

    $sql .= ' ORDER BY ' . $sortBy . ' ' . $sortOrder;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get a room service order by ID
 */
function getRoomServiceOrderById(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM room_service_orders WHERE id = ? AND hotel_id = ?');
    $stmt->execute([$id, getHotelId()]);
    return $stmt->fetch() ?: null;
}

/**
 * Get order items for a room service order
 */
function getRoomServiceOrderItems(int $orderId): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM room_service_order_items WHERE order_id = ? AND hotel_id = ? ORDER BY id ASC');
    $stmt->execute([$orderId, getHotelId()]);
    return $stmt->fetchAll();
}

/**
 * Create a room service order
 */
function createRoomServiceOrder(array $orderData, array $items): int|false {
    $pdo = getDatabase();

    try {
        $pdo->beginTransaction();

        // Calculate total
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Create order
        $stmt = $pdo->prepare('
            INSERT INTO room_service_orders (room_number, guest_name, phone, total_amount, payment_method, delivery_datetime, notes, hotel_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $orderData['room_number'],
            $orderData['guest_name'] ?? null,
            $orderData['phone'] ?? null,
            $total,
            $orderData['payment_method'] ?? 'room_charge',
            $orderData['delivery_datetime'],
            $orderData['notes'] ?? null,
            getHotelId()
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // Create order items
        $stmt = $pdo->prepare('
            INSERT INTO room_service_order_items (order_id, item_id, item_name, item_price, quantity, subtotal, hotel_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        foreach ($items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $stmt->execute([
                $orderId,
                $item['id'],
                $item['name'],
                $item['price'],
                $item['quantity'],
                $subtotal,
                getHotelId()
            ]);
        }

        $pdo->commit();
        return $orderId;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Room service order creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Validate delivery datetime
 */
function validateDeliveryDatetime(string $datetime): array {
    if (empty($datetime)) {
        return ['valid' => false, 'message' => 'La date et heure de livraison sont obligatoires.'];
    }

    $deliveryTime = strtotime($datetime);
    if ($deliveryTime === false) {
        return ['valid' => false, 'message' => 'Format de date/heure invalide.'];
    }

    // Must be at least 30 minutes in the future
    $minTime = time() + (30 * 60);
    if ($deliveryTime < $minTime) {
        return ['valid' => false, 'message' => 'La livraison doit être prévue au moins 30 minutes à l\'avance.'];
    }

    // Cannot be more than 7 days in the future
    $maxTime = time() + (7 * 24 * 60 * 60);
    if ($deliveryTime > $maxTime) {
        return ['valid' => false, 'message' => 'La livraison ne peut pas être prévue plus de 7 jours à l\'avance.'];
    }

    return ['valid' => true, 'datetime' => date('Y-m-d H:i:s', $deliveryTime)];
}

/**
 * Update room service order status
 */
function updateRoomServiceOrderStatus(int $id, string $status): bool {
    $validStatuses = ['pending', 'confirmed', 'preparing', 'delivered', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        return false;
    }
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE room_service_orders SET status = ? WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$status, $id, getHotelId()]);
}

/**
 * Get room service statistics
 */
function getRoomServiceStats(): array {
    $pdo = getDatabase();
    $stats = [];

    // Total items
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_service_items WHERE hotel_id = ?');
    $stmt->execute([getHotelId()]);
    $stats['total_items'] = $stmt->fetchColumn();

    // Active items
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_service_items WHERE is_active = TRUE AND hotel_id = ?');
    $stmt->execute([getHotelId()]);
    $stats['active_items'] = $stmt->fetchColumn();

    // Total orders
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_service_orders WHERE hotel_id = ?');
    $stmt->execute([getHotelId()]);
    $stats['total_orders'] = $stmt->fetchColumn();

    // Pending orders
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_service_orders WHERE status = \'pending\' AND hotel_id = ?');
    $stmt->execute([getHotelId()]);
    $stats['pending_orders'] = $stmt->fetchColumn();

    // Today's orders
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_service_orders WHERE DATE(created_at) = CURRENT_DATE AND hotel_id = ?');
    $stmt->execute([getHotelId()]);
    $stats['today_orders'] = $stmt->fetchColumn();

    // Today's revenue
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) FROM room_service_orders WHERE DATE(created_at) = CURRENT_DATE AND status != \'cancelled\' AND hotel_id = ?');
    $stmt->execute([getHotelId()]);
    $stats['today_revenue'] = $stmt->fetchColumn();

    return $stats;
}

/**
 * Get room service categories from database
 * Falls back to hardcoded values if table doesn't exist
 */
function getRoomServiceCategories(): array {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT code, name FROM room_service_categories WHERE is_active = TRUE AND hotel_id = ? ORDER BY position ASC');
        $stmt->execute([getHotelId()]);
        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[$row['code']] = $row['name'];
        }
        if (!empty($categories)) {
            return $categories;
        }
    } catch (PDOException $e) {
        // Table doesn't exist, use fallback
    }
    // Fallback to hardcoded categories
    return [
        'breakfast' => 'Petit-déjeuner',
        'lunch' => 'Déjeuner',
        'dinner' => 'Dîner',
        'snacks' => 'Snacks',
        'drinks' => 'Boissons',
        'desserts' => 'Desserts',
        'general' => 'Général'
    ];
}

/**
 * Get all room service categories with full details (for admin)
 */
function getRoomServiceCategoriesAll(): array {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT * FROM room_service_categories WHERE hotel_id = ? ORDER BY position ASC');
        $stmt->execute([getHotelId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get a single category by code
 */
function getRoomServiceCategoryByCode(string $code): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM room_service_categories WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$code, getHotelId()]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Update category time window
 */
function updateCategoryTimeWindow(string $code, ?string $timeStart, ?string $timeEnd): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE room_service_categories
        SET time_start = ?, time_end = ?, updated_at = NOW()
        WHERE code = ? AND hotel_id = ?
    ');
    return $stmt->execute([
        $timeStart ?: null,
        $timeEnd ?: null,
        $code,
        getHotelId()
    ]);
}

/**
 * Create a new room service category
 * @param array $data Category data (code, name, time_start, time_end, position, is_active)
 * @return int|false The new category ID or false on failure
 */
function createRoomServiceCategory(array $data): int|false {
    $pdo = getDatabase();

    // Check if code already exists
    $stmt = $pdo->prepare('SELECT id FROM room_service_categories WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$data['code'], getHotelId()]);
    if ($stmt->fetch()) {
        return false; // Code already exists
    }

    $stmt = $pdo->prepare('
        INSERT INTO room_service_categories (code, name, time_start, time_end, position, is_active, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $success = $stmt->execute([
        $data['code'],
        $data['name'],
        $data['time_start'] ?? null,
        $data['time_end'] ?? null,
        $data['position'] ?? 0,
        $data['is_active'] ?? 1,
        getHotelId()
    ]);
    return $success ? (int)$pdo->lastInsertId() : false;
}

/**
 * Update a room service category
 * @param string $code Category code
 * @param array $data Category data to update
 * @return bool Success status
 */
function updateRoomServiceCategory(string $code, array $data): bool {
    $pdo = getDatabase();

    // If code is being changed, check if new code already exists
    if (isset($data['new_code']) && $data['new_code'] !== $code) {
        $stmt = $pdo->prepare('SELECT id FROM room_service_categories WHERE code = ? AND code != ? AND hotel_id = ?');
        $stmt->execute([$data['new_code'], $code, getHotelId()]);
        if ($stmt->fetch()) {
            return false; // New code already exists
        }
    }

    $stmt = $pdo->prepare('
        UPDATE room_service_categories
        SET code = ?, name = ?, time_start = ?, time_end = ?, position = ?, is_active = ?
        WHERE code = ? AND hotel_id = ?
    ');
    return $stmt->execute([
        $data['new_code'] ?? $code,
        $data['name'],
        $data['time_start'] ?? null,
        $data['time_end'] ?? null,
        $data['position'] ?? 0,
        $data['is_active'] ?? 1,
        $code,
        getHotelId()
    ]);
}

/**
 * Delete a room service category
 * @param string $code Category code
 * @param string|null $reassignTo Code of category to reassign items to (null to prevent deletion if items exist)
 * @return array ['success' => bool, 'message' => string, 'items_count' => int]
 */
function deleteRoomServiceCategory(string $code, ?string $reassignTo = null): array {
    $pdo = getDatabase();

    // Don't allow deletion of 'general' category
    if ($code === 'general') {
        return [
            'success' => false,
            'message' => 'La catégorie "Général" ne peut pas être supprimée.',
            'items_count' => 0
        ];
    }

    // Check if category exists
    $category = getRoomServiceCategoryByCode($code);
    if (!$category) {
        return [
            'success' => false,
            'message' => 'Catégorie non trouvée.',
            'items_count' => 0
        ];
    }

    // Count items in this category
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM room_service_items WHERE category = ? AND hotel_id = ?');
    $stmt->execute([$code, getHotelId()]);
    $itemsCount = (int)$stmt->fetch()['count'];

    if ($itemsCount > 0) {
        if ($reassignTo === null) {
            return [
                'success' => false,
                'message' => "Cette catégorie contient $itemsCount article(s). Veuillez d'abord les réassigner.",
                'items_count' => $itemsCount
            ];
        }

        // Check if reassign category exists
        $reassignCategory = getRoomServiceCategoryByCode($reassignTo);
        if (!$reassignCategory) {
            return [
                'success' => false,
                'message' => 'La catégorie de réassignation n\'existe pas.',
                'items_count' => $itemsCount
            ];
        }

        // Reassign items
        $stmt = $pdo->prepare('UPDATE room_service_items SET category = ? WHERE category = ? AND hotel_id = ?');
        $stmt->execute([$reassignTo, $code, getHotelId()]);
    }

    // Delete category translations
    try {
        $stmt = $pdo->prepare('DELETE FROM room_service_category_translations WHERE category_code = ? AND hotel_id = ?');
        $stmt->execute([$code, getHotelId()]);
    } catch (PDOException $e) {
        // Table might not exist
    }

    // Delete the category
    $stmt = $pdo->prepare('DELETE FROM room_service_categories WHERE code = ? AND hotel_id = ?');
    $success = $stmt->execute([$code, getHotelId()]);

    return [
        'success' => $success,
        'message' => $success ? 'Catégorie supprimée avec succès.' : 'Erreur lors de la suppression.',
        'items_count' => $itemsCount
    ];
}

/**
 * Toggle category active status
 * @param string $code Category code
 * @return bool Success status
 */
function toggleRoomServiceCategoryStatus(string $code): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE room_service_categories SET is_active = NOT is_active WHERE code = ? AND hotel_id = ?');
    return $stmt->execute([$code, getHotelId()]);
}

/**
 * Get count of items in a category
 * @param string $code Category code
 * @return int Number of items
 */
function getCategoryItemsCount(string $code): int {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM room_service_items WHERE category = ? AND hotel_id = ?');
    $stmt->execute([$code, getHotelId()]);
    return (int)$stmt->fetch()['count'];
}

/**
 * Get next position for a new category
 * @return int Next position value
 */
function getNextCategoryPosition(): int {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT MAX(position) as max_pos FROM room_service_categories WHERE hotel_id = ?');
    $stmt->execute([getHotelId()]);
    $result = $stmt->fetch();
    return ($result['max_pos'] ?? 0) + 1;
}

/**
 * Check if a category is currently available based on time window
 * @param string $code Category code
 * @param string|null $checkTime Time to check (HH:MM format), defaults to current time
 * @return bool True if available, false otherwise
 */
function isCategoryAvailable(string $code, ?string $checkTime = null): bool {
    $category = getRoomServiceCategoryByCode($code);

    if (!$category || !$category['is_active']) {
        return false;
    }

    // If no time window set, always available
    if (empty($category['time_start']) || empty($category['time_end'])) {
        return true;
    }

    $time = $checkTime ? strtotime($checkTime) : time();
    $currentTime = date('H:i', $time);

    $start = substr($category['time_start'], 0, 5); // HH:MM
    $end = substr($category['time_end'], 0, 5);

    // Handle overnight windows (e.g., 22:00 - 02:00)
    if ($start > $end) {
        return $currentTime >= $start || $currentTime <= $end;
    }

    return $currentTime >= $start && $currentTime <= $end;
}

/**
 * Check if a category will be available at a given datetime
 * @param string $code Category code
 * @param string $datetime Datetime to check (Y-m-d H:i:s format)
 * @return bool True if available, false otherwise
 */
function isCategoryAvailableAt(string $code, string $datetime): bool {
    $time = date('H:i', strtotime($datetime));
    return isCategoryAvailable($code, $time);
}

/**
 * Get category availability info for display
 * @param string $code Category code
 * @return array ['available' => bool, 'time_start' => string|null, 'time_end' => string|null, 'message' => string]
 */
function getCategoryAvailabilityInfo(string $code): array {
    $category = getRoomServiceCategoryByCode($code);

    if (!$category) {
        return [
            'available' => true,
            'time_start' => null,
            'time_end' => null,
            'message' => 'Disponible'
        ];
    }

    $isAvailable = isCategoryAvailable($code);
    $timeStart = $category['time_start'] ? substr($category['time_start'], 0, 5) : null;
    $timeEnd = $category['time_end'] ? substr($category['time_end'], 0, 5) : null;

    if (!$timeStart || !$timeEnd) {
        $message = 'Disponible 24h/24';
    } elseif ($isAvailable) {
        $message = "Disponible ({$timeStart} - {$timeEnd})";
    } else {
        $message = "Disponible de {$timeStart} à {$timeEnd}";
    }

    return [
        'available' => $isAvailable,
        'time_start' => $timeStart,
        'time_end' => $timeEnd,
        'message' => $message
    ];
}

/**
 * Validate order items availability at delivery time
 * @param array $items Array of items with item_id
 * @param string $deliveryDatetime Delivery datetime
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateOrderItemsAvailability(array $items, string $deliveryDatetime): array {
    $errors = [];
    $pdo = getDatabase();

    foreach ($items as $item) {
        $stmt = $pdo->prepare('SELECT name, category FROM room_service_items WHERE id = ? AND hotel_id = ?');
        $stmt->execute([$item['item_id'], getHotelId()]);
        $itemData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($itemData && !isCategoryAvailableAt($itemData['category'], $deliveryDatetime)) {
            $categoryInfo = getCategoryAvailabilityInfo($itemData['category']);
            $errors[] = sprintf(
                '"%s" n\'est pas disponible à l\'heure demandée. %s',
                $itemData['name'],
                $categoryInfo['message']
            );
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get room service order statuses
 */
function getRoomServiceOrderStatuses(): array {
    return [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'preparing' => 'En préparation',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée'
    ];
}

/**
 * Get payment methods
 */
function getRoomServicePaymentMethods(): array {
    return [
        'room_charge' => 'Facturer à la chambre',
        'card' => 'Carte bancaire',
        'cash' => 'Espèces'
    ];
}
