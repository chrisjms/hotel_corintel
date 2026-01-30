<?php
/**
 * Helper Functions
 * Hotel Corintel - Admin System
 */

require_once __DIR__ . '/../config/database.php';

// Define upload constants if not already defined in config
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/../uploads/');
}
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
}
if (!defined('ALLOWED_TYPES')) {
    define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
}
if (!defined('ALLOWED_EXTENSIONS')) {
    define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
}

/**
 * Get all images for a section
 */
function getImagesBySection(string $section): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM images WHERE section = ? ORDER BY position ASC');
    $stmt->execute([$section]);
    return $stmt->fetchAll();
}

/**
 * Get a single image by section and position
 */
function getImage(string $section, int $position): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM images WHERE section = ? AND position = ?');
    $stmt->execute([$section, $position]);
    return $stmt->fetch() ?: null;
}

/**
 * Get a single image by ID
 */
function getImageById(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM images WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get image URL (handles both database and fallback)
 */
function getImageUrl(string $section, int $position, string $fallback = ''): string {
    $image = getImage($section, $position);

    if ($image && !empty($image['filename'])) {
        // Check if it's an uploaded file or original path
        if (strpos($image['filename'], 'uploads/') === 0) {
            return $image['filename'];
        }
        return $image['filename'];
    }

    return $fallback;
}

/**
 * Update image record
 */
function updateImage(int $id, string $filename, ?string $title = null, ?string $altText = null): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE images SET filename = ?, title = ?, alt_text = ?, updated_at = NOW() WHERE id = ?');
    return $stmt->execute([$filename, $title, $altText, $id]);
}

/**
 * Get all sections
 */
function getAllSections(): array {
    return [
        'home' => 'Accueil',
        'services' => 'Services',
        'activities' => 'À découvrir',
        'contact' => 'Contact'
    ];
}

/**
 * Validate uploaded file
 */
function validateUploadedFile(array $file): array {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur.',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier.',
            UPLOAD_ERR_EXTENSION => 'Upload bloqué par une extension PHP.'
        ];
        return [
            'valid' => false,
            'message' => $errors[$file['error']] ?? 'Erreur de téléchargement inconnue.'
        ];
    }

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return [
            'valid' => false,
            'message' => 'Le fichier est trop volumineux. Taille maximale: ' . (MAX_FILE_SIZE / 1024 / 1024) . ' Mo.'
        ];
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return [
            'valid' => false,
            'message' => 'Type de fichier non autorisé. Types acceptés: JPG, PNG, WEBP.'
        ];
    }

    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return [
            'valid' => false,
            'message' => 'Extension de fichier non autorisée.'
        ];
    }

    // Additional security: verify it's actually an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return [
            'valid' => false,
            'message' => 'Le fichier ne semble pas être une image valide.'
        ];
    }

    return [
        'valid' => true,
        'mime_type' => $mimeType,
        'extension' => $extension
    ];
}

/**
 * Handle file upload
 */
function handleFileUpload(array $file, int $imageId): array {
    // Validate file
    $validation = validateUploadedFile($file);
    if (!$validation['valid']) {
        return $validation;
    }

    // Get existing image info
    $existingImage = getImageById($imageId);
    if (!$existingImage) {
        return [
            'valid' => false,
            'message' => 'Image non trouvée dans la base de données.'
        ];
    }

    // Generate unique filename
    $extension = $validation['extension'];
    $newFilename = sprintf(
        '%s_%s_%d.%s',
        $existingImage['section'],
        $existingImage['position'],
        time(),
        $extension
    );

    $uploadPath = UPLOAD_DIR . $newFilename;

    // Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        if (!@mkdir(UPLOAD_DIR, 0755, true)) {
            return [
                'valid' => false,
                'message' => 'Impossible de créer le dossier uploads. Créez-le manuellement via FTP avec les permissions 755.'
            ];
        }
    }

    // Check if directory is writable
    if (!is_writable(UPLOAD_DIR)) {
        return [
            'valid' => false,
            'message' => 'Le dossier uploads n\'est pas accessible en écriture. Vérifiez les permissions (755).'
        ];
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'valid' => false,
            'message' => 'Erreur lors de l\'enregistrement du fichier. Vérifiez les permissions du dossier uploads.'
        ];
    }

    // Delete old uploaded file if it exists in uploads folder
    $oldFilename = $existingImage['filename'];
    if (strpos($oldFilename, 'uploads/') === 0) {
        $oldPath = __DIR__ . '/../' . $oldFilename;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    // Update database
    $relativePath = 'uploads/' . $newFilename;
    if (!updateImage($imageId, $relativePath, $existingImage['title'], $existingImage['alt_text'])) {
        // Rollback: delete uploaded file
        @unlink($uploadPath);
        return [
            'valid' => false,
            'message' => 'Erreur lors de la mise à jour de la base de données.'
        ];
    }

    return [
        'valid' => true,
        'message' => 'Image mise à jour avec succès.',
        'filename' => $relativePath
    ];
}

/**
 * Sanitize output for HTML
 */
function h(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 */
function formatDate(?string $date): string {
    if (empty($date)) {
        return '-';
    }
    return date('d/m/Y H:i', strtotime($date));
}

// =====================================================
// ROOM SERVICE FUNCTIONS
// =====================================================

/**
 * Get all room service items
 */
function getRoomServiceItems(bool $activeOnly = false): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM room_service_items';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Get a room service item by ID
 */
function getRoomServiceItemById(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM room_service_items WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Create a room service item
 */
function createRoomServiceItem(array $data): int|false {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        INSERT INTO room_service_items (name, description, price, image, category, is_active, position)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $success = $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['price'],
        $data['image'] ?? null,
        $data['category'] ?? 'general',
        $data['is_active'] ?? 1,
        $data['position'] ?? 0
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
        WHERE id = ?
    ');
    return $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['price'],
        $data['image'] ?? null,
        $data['category'] ?? 'general',
        $data['is_active'] ?? 1,
        $data['position'] ?? 0,
        $id
    ]);
}

/**
 * Toggle room service item status
 */
function toggleRoomServiceItemStatus(int $id): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE room_service_items SET is_active = NOT is_active WHERE id = ?');
    return $stmt->execute([$id]);
}

/**
 * Delete a room service item
 */
function deleteRoomServiceItem(int $id): bool {
    $pdo = getDatabase();
    // Check if item is used in any order
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_service_order_items WHERE item_id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        return false; // Cannot delete, item is used in orders
    }
    $stmt = $pdo->prepare('DELETE FROM room_service_items WHERE id = ?');
    return $stmt->execute([$id]);
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
    $stmt = $pdo->prepare('UPDATE room_service_items SET image = ? WHERE id = ?');
    if (!$stmt->execute([$relativePath, $itemId])) {
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
    $conditions = [];

    if ($status && $status !== 'all') {
        $conditions[] = 'status = ?';
        $params[] = $status;
    }

    if ($deliveryDate) {
        $conditions[] = 'DATE(delivery_datetime) = ?';
        $params[] = $deliveryDate;
    }

    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

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
    $stmt = $pdo->prepare('SELECT * FROM room_service_orders WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get order items for a room service order
 */
function getRoomServiceOrderItems(int $orderId): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM room_service_order_items WHERE order_id = ? ORDER BY id ASC');
    $stmt->execute([$orderId]);
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
            INSERT INTO room_service_orders (room_number, guest_name, phone, total_amount, payment_method, delivery_datetime, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $orderData['room_number'],
            $orderData['guest_name'] ?? null,
            $orderData['phone'] ?? null,
            $total,
            $orderData['payment_method'] ?? 'room_charge',
            $orderData['delivery_datetime'],
            $orderData['notes'] ?? null
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // Create order items
        $stmt = $pdo->prepare('
            INSERT INTO room_service_order_items (order_id, item_id, item_name, item_price, quantity, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        foreach ($items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $stmt->execute([
                $orderId,
                $item['id'],
                $item['name'],
                $item['price'],
                $item['quantity'],
                $subtotal
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
    $stmt = $pdo->prepare('UPDATE room_service_orders SET status = ? WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

/**
 * Get room service statistics
 */
function getRoomServiceStats(): array {
    $pdo = getDatabase();
    $stats = [];

    // Total items
    $stmt = $pdo->query('SELECT COUNT(*) FROM room_service_items');
    $stats['total_items'] = $stmt->fetchColumn();

    // Active items
    $stmt = $pdo->query('SELECT COUNT(*) FROM room_service_items WHERE is_active = 1');
    $stats['active_items'] = $stmt->fetchColumn();

    // Total orders
    $stmt = $pdo->query('SELECT COUNT(*) FROM room_service_orders');
    $stats['total_orders'] = $stmt->fetchColumn();

    // Pending orders
    $stmt = $pdo->query('SELECT COUNT(*) FROM room_service_orders WHERE status = "pending"');
    $stats['pending_orders'] = $stmt->fetchColumn();

    // Today's orders
    $stmt = $pdo->query('SELECT COUNT(*) FROM room_service_orders WHERE DATE(created_at) = CURDATE()');
    $stats['today_orders'] = $stmt->fetchColumn();

    // Today's revenue
    $stmt = $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM room_service_orders WHERE DATE(created_at) = CURDATE() AND status != "cancelled"');
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
        $stmt = $pdo->query('SELECT code, name FROM room_service_categories WHERE is_active = 1 ORDER BY position ASC');
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
        $stmt = $pdo->query('SELECT * FROM room_service_categories ORDER BY position ASC');
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
    $stmt = $pdo->prepare('SELECT * FROM room_service_categories WHERE code = ?');
    $stmt->execute([$code]);
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
        WHERE code = ?
    ');
    return $stmt->execute([
        $timeStart ?: null,
        $timeEnd ?: null,
        $code
    ]);
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
        $stmt = $pdo->prepare('SELECT name, category FROM room_service_items WHERE id = ?');
        $stmt->execute([$item['item_id']]);
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

// =====================================================
// ROOM SERVICE STATISTICS FUNCTIONS
// =====================================================

/**
 * Get comprehensive room service statistics for a given period
 * @param string $period 'day', 'week', 'month', 'year'
 * @param string|null $date Reference date (defaults to today)
 * @return array
 */
function getRoomServicePeriodStats(string $period = 'day', ?string $date = null): array {
    $pdo = getDatabase();
    $refDate = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');

    // Calculate date ranges based on period
    switch ($period) {
        case 'week':
            $startDate = date('Y-m-d', strtotime('monday this week', strtotime($refDate)));
            $endDate = date('Y-m-d', strtotime('sunday this week', strtotime($refDate)));
            $prevStartDate = date('Y-m-d', strtotime('-1 week', strtotime($startDate)));
            $prevEndDate = date('Y-m-d', strtotime('-1 week', strtotime($endDate)));
            break;
        case 'month':
            $startDate = date('Y-m-01', strtotime($refDate));
            $endDate = date('Y-m-t', strtotime($refDate));
            $prevStartDate = date('Y-m-01', strtotime('-1 month', strtotime($refDate)));
            $prevEndDate = date('Y-m-t', strtotime('-1 month', strtotime($refDate)));
            break;
        case 'year':
            $startDate = date('Y-01-01', strtotime($refDate));
            $endDate = date('Y-12-31', strtotime($refDate));
            $prevStartDate = date('Y-01-01', strtotime('-1 year', strtotime($refDate)));
            $prevEndDate = date('Y-12-31', strtotime('-1 year', strtotime($refDate)));
            break;
        default: // day
            $startDate = $refDate;
            $endDate = $refDate;
            $prevStartDate = date('Y-m-d', strtotime('-1 day', strtotime($refDate)));
            $prevEndDate = $prevStartDate;
    }

    // Current period stats
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue,
            COALESCE(AVG(CASE WHEN status != 'cancelled' THEN total_amount END), 0) as avg_order_value,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
        FROM room_service_orders
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    // Previous period stats (for comparison)
    $stmt->execute([$prevStartDate, $prevEndDate]);
    $previous = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate percentage changes
    $revenueChange = $previous['revenue'] > 0
        ? (($current['revenue'] - $previous['revenue']) / $previous['revenue']) * 100
        : ($current['revenue'] > 0 ? 100 : 0);
    $ordersChange = $previous['total_orders'] > 0
        ? (($current['total_orders'] - $previous['total_orders']) / $previous['total_orders']) * 100
        : ($current['total_orders'] > 0 ? 100 : 0);

    return [
        'period' => $period,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'current' => [
            'total_orders' => (int)$current['total_orders'],
            'revenue' => (float)$current['revenue'],
            'avg_order_value' => (float)$current['avg_order_value'],
            'delivered_orders' => (int)$current['delivered_orders'],
            'cancelled_orders' => (int)$current['cancelled_orders'],
            'delivery_rate' => $current['total_orders'] > 0
                ? round(($current['delivered_orders'] / $current['total_orders']) * 100, 1)
                : 0
        ],
        'previous' => [
            'total_orders' => (int)$previous['total_orders'],
            'revenue' => (float)$previous['revenue'],
            'start_date' => $prevStartDate,
            'end_date' => $prevEndDate
        ],
        'changes' => [
            'revenue_percent' => round($revenueChange, 1),
            'orders_percent' => round($ordersChange, 1)
        ]
    ];
}

/**
 * Get daily revenue data for charts
 * @param int $days Number of days to fetch
 * @param string|null $endDate End date (defaults to today)
 * @return array
 */
function getRoomServiceDailyRevenue(int $days = 30, ?string $endDate = null): array {
    $pdo = getDatabase();
    $end = $endDate ? date('Y-m-d', strtotime($endDate)) : date('Y-m-d');
    $start = date('Y-m-d', strtotime("-{$days} days", strtotime($end)));

    $stmt = $pdo->prepare("
        SELECT
            DATE(created_at) as date,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$start, $end]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fill in missing dates with zero values
    $data = [];
    $current = strtotime($start);
    $endTime = strtotime($end);

    $resultsByDate = [];
    foreach ($results as $row) {
        $resultsByDate[$row['date']] = $row;
    }

    while ($current <= $endTime) {
        $dateStr = date('Y-m-d', $current);
        $data[] = [
            'date' => $dateStr,
            'label' => date('d/m', $current),
            'orders' => isset($resultsByDate[$dateStr]) ? (int)$resultsByDate[$dateStr]['orders'] : 0,
            'revenue' => isset($resultsByDate[$dateStr]) ? (float)$resultsByDate[$dateStr]['revenue'] : 0
        ];
        $current = strtotime('+1 day', $current);
    }

    return $data;
}

/**
 * Get weekly revenue data for charts
 * @param int $weeks Number of weeks to fetch
 * @return array
 */
function getRoomServiceWeeklyRevenue(int $weeks = 12): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            YEARWEEK(created_at, 1) as year_week,
            MIN(DATE(created_at)) as week_start,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)
        GROUP BY YEARWEEK(created_at, 1)
        ORDER BY year_week ASC
    ");
    $stmt->execute([$weeks]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(function($row) {
        return [
            'week' => $row['year_week'],
            'week_start' => $row['week_start'],
            'label' => 'S' . substr($row['year_week'], -2),
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue']
        ];
    }, $results);
}

/**
 * Get monthly revenue data for charts
 * @param int $months Number of months to fetch
 * @return array
 */
function getRoomServiceMonthlyRevenue(int $months = 12): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$months]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    return array_map(function($row) use ($monthNames) {
        $monthNum = (int)substr($row['month'], 5, 2);
        return [
            'month' => $row['month'],
            'label' => $monthNames[$monthNum - 1],
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue']
        ];
    }, $results);
}

/**
 * Get peak hours analysis
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServicePeakHours(int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            HOUR(delivery_datetime) as hour,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY HOUR(delivery_datetime)
        ORDER BY hour ASC
    ");
    $stmt->execute([$days]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fill all 24 hours
    $data = array_fill(0, 24, ['orders' => 0, 'revenue' => 0]);
    foreach ($results as $row) {
        $data[(int)$row['hour']] = [
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue']
        ];
    }

    $hourlyData = [];
    for ($h = 0; $h < 24; $h++) {
        $hourlyData[] = [
            'hour' => $h,
            'label' => sprintf('%02d:00', $h),
            'orders' => $data[$h]['orders'],
            'revenue' => $data[$h]['revenue']
        ];
    }

    // Find peak hour
    $peakHour = 0;
    $maxOrders = 0;
    foreach ($hourlyData as $item) {
        if ($item['orders'] > $maxOrders) {
            $maxOrders = $item['orders'];
            $peakHour = $item['hour'];
        }
    }

    return [
        'data' => $hourlyData,
        'peak_hour' => $peakHour,
        'peak_hour_label' => sprintf('%02d:00 - %02d:00', $peakHour, ($peakHour + 1) % 24)
    ];
}

/**
 * Get peak days analysis
 * @param int $weeks Number of weeks to analyze
 * @return array
 */
function getRoomServicePeakDays(int $weeks = 8): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            DAYOFWEEK(created_at) as day_num,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)
        GROUP BY DAYOFWEEK(created_at)
        ORDER BY day_num ASC
    ");
    $stmt->execute([$weeks]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    $fullDayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    // Fill all 7 days
    $data = array_fill(1, 7, ['orders' => 0, 'revenue' => 0]);
    foreach ($results as $row) {
        $data[(int)$row['day_num']] = [
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue']
        ];
    }

    $dailyData = [];
    $peakDay = 1;
    $maxOrders = 0;
    for ($d = 1; $d <= 7; $d++) {
        $dailyData[] = [
            'day' => $d,
            'label' => $dayNames[$d - 1],
            'full_name' => $fullDayNames[$d - 1],
            'orders' => $data[$d]['orders'],
            'revenue' => $data[$d]['revenue']
        ];
        if ($data[$d]['orders'] > $maxOrders) {
            $maxOrders = $data[$d]['orders'];
            $peakDay = $d;
        }
    }

    return [
        'data' => $dailyData,
        'peak_day' => $peakDay,
        'peak_day_name' => $fullDayNames[$peakDay - 1]
    ];
}

/**
 * Get top selling items
 * @param int $limit Number of items to return
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServiceTopItems(int $limit = 10, int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            oi.item_name,
            oi.item_id,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.subtotal) as total_revenue,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM room_service_order_items oi
        JOIN room_service_orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND o.status != 'cancelled'
        GROUP BY oi.item_id, oi.item_name
        ORDER BY total_quantity DESC
        LIMIT ?
    ");
    $stmt->execute([$days, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get revenue by category
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServiceRevenueByCategory(int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(i.category, 'general') as category,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.subtotal) as total_revenue,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM room_service_order_items oi
        JOIN room_service_orders o ON oi.order_id = o.id
        LEFT JOIN room_service_items i ON oi.item_id = i.id
        WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND o.status != 'cancelled'
        GROUP BY COALESCE(i.category, 'general')
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$days]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = getRoomServiceCategories();
    return array_map(function($row) use ($categories) {
        return [
            'category' => $row['category'],
            'category_name' => $categories[$row['category']] ?? ucfirst($row['category']),
            'total_quantity' => (int)$row['total_quantity'],
            'total_revenue' => (float)$row['total_revenue'],
            'order_count' => (int)$row['order_count']
        ];
    }, $results);
}

/**
 * Get payment method breakdown
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServicePaymentBreakdown(int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            payment_method,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_revenue
        FROM room_service_orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND status != 'cancelled'
        GROUP BY payment_method
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$days]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $methods = getRoomServicePaymentMethods();
    return array_map(function($row) use ($methods) {
        return [
            'method' => $row['payment_method'],
            'method_name' => $methods[$row['payment_method']] ?? $row['payment_method'],
            'order_count' => (int)$row['order_count'],
            'total_revenue' => (float)$row['total_revenue']
        ];
    }, $results);
}

/**
 * Get order status breakdown
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServiceStatusBreakdown(int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            status,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_amount
        FROM room_service_orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY status
        ORDER BY order_count DESC
    ");
    $stmt->execute([$days]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $statuses = getRoomServiceOrderStatuses();
    return array_map(function($row) use ($statuses) {
        return [
            'status' => $row['status'],
            'status_name' => $statuses[$row['status']] ?? $row['status'],
            'order_count' => (int)$row['order_count'],
            'total_amount' => (float)$row['total_amount']
        ];
    }, $results);
}

/**
 * Get best performing period
 * @param string $periodType 'day', 'week', 'month'
 * @param int $lookback Number of periods to look back
 * @return array|null
 */
function getRoomServiceBestPeriod(string $periodType = 'day', int $lookback = 30): ?array {
    $pdo = getDatabase();

    switch ($periodType) {
        case 'week':
            $groupBy = 'YEARWEEK(created_at, 1)';
            $dateFormat = '%Y-W%u';
            break;
        case 'month':
            $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
            $dateFormat = '%Y-%m';
            break;
        default:
            $groupBy = 'DATE(created_at)';
            $dateFormat = '%Y-%m-%d';
    }

    $stmt = $pdo->prepare("
        SELECT
            {$groupBy} as period,
            MIN(DATE(created_at)) as period_start,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? {$periodType})
        GROUP BY {$groupBy}
        ORDER BY revenue DESC
        LIMIT 1
    ");
    $stmt->execute([$lookback]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return null;
    }

    return [
        'period' => $result['period'],
        'period_start' => $result['period_start'],
        'orders' => (int)$result['orders'],
        'revenue' => (float)$result['revenue']
    ];
}

/**
 * Get room statistics (which rooms order the most)
 * @param int $limit Number of rooms to return
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServiceTopRooms(int $limit = 10, int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            room_number,
            COUNT(*) as order_count,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as total_revenue,
            COALESCE(AVG(CASE WHEN status != 'cancelled' THEN total_amount END), 0) as avg_order_value
        FROM room_service_orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY room_number
        ORDER BY total_revenue DESC
        LIMIT ?
    ");
    $stmt->execute([$days, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =====================================================
// GUEST MESSAGES FUNCTIONS
// =====================================================

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
        INSERT INTO guest_messages (room_number, guest_name, category, subject, message)
        VALUES (?, ?, ?, ?, ?)
    ');

    $success = $stmt->execute([
        $data['room_number'],
        $data['guest_name'] ?? null,
        $data['category'] ?? 'general',
        $data['subject'] ?? null,
        $data['message']
    ]);

    return $success ? (int)$pdo->lastInsertId() : false;
}

/**
 * Get all guest messages with optional filters
 */
function getGuestMessages(string $status = '', string $sortBy = 'created_at', string $sortOrder = 'DESC'): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM guest_messages';
    $params = [];

    if ($status && $status !== 'all') {
        $sql .= ' WHERE status = ?';
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
}

/**
 * Get a guest message by ID
 */
function getGuestMessageById(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM guest_messages WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
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
    $stmt = $pdo->prepare('UPDATE guest_messages SET status = ? WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

/**
 * Update guest message admin notes
 */
function updateGuestMessageNotes(int $id, string $notes): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE guest_messages SET admin_notes = ? WHERE id = ?');
    return $stmt->execute([$notes, $id]);
}

/**
 * Delete a guest message
 */
function deleteGuestMessage(int $id): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('DELETE FROM guest_messages WHERE id = ?');
    return $stmt->execute([$id]);
}

/**
 * Get guest messages statistics
 */
function getGuestMessagesStats(): array {
    $pdo = getDatabase();
    $stats = [];

    // Total messages
    $stmt = $pdo->query('SELECT COUNT(*) FROM guest_messages');
    $stats['total'] = $stmt->fetchColumn();

    // New (unread) messages
    $stmt = $pdo->query('SELECT COUNT(*) FROM guest_messages WHERE status = "new"');
    $stats['new'] = $stmt->fetchColumn();

    // In progress messages
    $stmt = $pdo->query('SELECT COUNT(*) FROM guest_messages WHERE status = "in_progress"');
    $stats['in_progress'] = $stmt->fetchColumn();

    // Today's messages
    $stmt = $pdo->query('SELECT COUNT(*) FROM guest_messages WHERE DATE(created_at) = CURDATE()');
    $stats['today'] = $stmt->fetchColumn();

    return $stats;
}

/**
 * Get unread guest messages count (lightweight for sidebar badge)
 */
function getUnreadMessagesCount(): int {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->query('SELECT COUNT(*) FROM guest_messages WHERE status = "new"');
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
        $stmt = $pdo->query('SELECT COUNT(*) FROM room_service_orders WHERE status = "pending"');
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
