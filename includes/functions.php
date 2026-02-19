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
function h(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
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

/**
 * Format date/time as relative time (e.g., "5 minutes ago", "in 2 hours")
 * Supports both past and future dates
 */
function timeAgo(?string $datetime): string {
    if (empty($datetime)) {
        return '-';
    }

    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    $isFuture = $diff < 0;
    $diff = abs($diff);

    // Define time intervals in seconds
    $intervals = [
        ['value' => 31536000, 'singular' => 'an', 'plural' => 'ans'],
        ['value' => 2592000, 'singular' => 'mois', 'plural' => 'mois'],
        ['value' => 604800, 'singular' => 'semaine', 'plural' => 'semaines'],
        ['value' => 86400, 'singular' => 'jour', 'plural' => 'jours'],
        ['value' => 3600, 'singular' => 'heure', 'plural' => 'heures'],
        ['value' => 60, 'singular' => 'minute', 'plural' => 'minutes'],
    ];

    // Less than a minute
    if ($diff < 60) {
        return $isFuture ? "dans moins d'une minute" : "à l'instant";
    }

    foreach ($intervals as $interval) {
        $count = floor($diff / $interval['value']);
        if ($count >= 1) {
            $unit = $count === 1 ? $interval['singular'] : $interval['plural'];
            if ($isFuture) {
                return "dans $count $unit";
            } else {
                return "il y a $count $unit";
            }
        }
    }

    return formatDate($datetime);
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
 * Create a new room service category
 * @param array $data Category data (code, name, time_start, time_end, position, is_active)
 * @return int|false The new category ID or false on failure
 */
function createRoomServiceCategory(array $data): int|false {
    $pdo = getDatabase();

    // Check if code already exists
    $stmt = $pdo->prepare('SELECT id FROM room_service_categories WHERE code = ?');
    $stmt->execute([$data['code']]);
    if ($stmt->fetch()) {
        return false; // Code already exists
    }

    $stmt = $pdo->prepare('
        INSERT INTO room_service_categories (code, name, time_start, time_end, position, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $success = $stmt->execute([
        $data['code'],
        $data['name'],
        $data['time_start'] ?? null,
        $data['time_end'] ?? null,
        $data['position'] ?? 0,
        $data['is_active'] ?? 1
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
        $stmt = $pdo->prepare('SELECT id FROM room_service_categories WHERE code = ? AND code != ?');
        $stmt->execute([$data['new_code'], $code]);
        if ($stmt->fetch()) {
            return false; // New code already exists
        }
    }

    $stmt = $pdo->prepare('
        UPDATE room_service_categories
        SET code = ?, name = ?, time_start = ?, time_end = ?, position = ?, is_active = ?
        WHERE code = ?
    ');
    return $stmt->execute([
        $data['new_code'] ?? $code,
        $data['name'],
        $data['time_start'] ?? null,
        $data['time_end'] ?? null,
        $data['position'] ?? 0,
        $data['is_active'] ?? 1,
        $code
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
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM room_service_items WHERE category = ?');
    $stmt->execute([$code]);
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
        $stmt = $pdo->prepare('UPDATE room_service_items SET category = ? WHERE category = ?');
        $stmt->execute([$reassignTo, $code]);
    }

    // Delete category translations
    try {
        $stmt = $pdo->prepare('DELETE FROM room_service_category_translations WHERE category_code = ?');
        $stmt->execute([$code]);
    } catch (PDOException $e) {
        // Table might not exist
    }

    // Delete the category
    $stmt = $pdo->prepare('DELETE FROM room_service_categories WHERE code = ?');
    $success = $stmt->execute([$code]);

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
    $stmt = $pdo->prepare('UPDATE room_service_categories SET is_active = NOT is_active WHERE code = ?');
    return $stmt->execute([$code]);
}

/**
 * Get count of items in a category
 * @param string $code Category code
 * @return int Number of items
 */
function getCategoryItemsCount(string $code): int {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM room_service_items WHERE category = ?');
    $stmt->execute([$code]);
    return (int)$stmt->fetch()['count'];
}

/**
 * Get next position for a new category
 * @return int Next position value
 */
function getNextCategoryPosition(): int {
    $pdo = getDatabase();
    $stmt = $pdo->query('SELECT MAX(position) as max_pos FROM room_service_categories');
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
// MULTI-LANGUAGE TRANSLATION FUNCTIONS
// =====================================================

/**
 * Get supported languages
 */
function getSupportedLanguages(): array {
    return ['fr', 'en', 'es', 'it'];
}

/**
 * Get default language code
 */
function getDefaultLanguage(): string {
    return 'fr';
}

/**
 * Get current language from cookie/session or default
 */
function getCurrentLanguage(): string {
    // Check cookie first (set by JS i18n system)
    if (isset($_COOKIE['hotel_corintel_lang'])) {
        $lang = $_COOKIE['hotel_corintel_lang'];
        if (in_array($lang, getSupportedLanguages())) {
            return $lang;
        }
    }
    // Check query parameter
    if (isset($_GET['lang'])) {
        $lang = $_GET['lang'];
        if (in_array($lang, getSupportedLanguages())) {
            return $lang;
        }
    }
    return getDefaultLanguage();
}

/**
 * Save item translations
 * @param int $itemId Item ID
 * @param array $translations Array of ['language_code' => ['name' => '', 'description' => '']]
 */
function saveItemTranslations(int $itemId, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $data) {
        if (!in_array($langCode, getSupportedLanguages())) {
            continue;
        }

        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');

        // Skip if name is empty
        if (empty($name)) {
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO room_service_item_translations (item_id, language_code, name, description)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)
            ');
            $success = $stmt->execute([$itemId, $langCode, $name, $description]) && $success;
        } catch (PDOException $e) {
            error_log('Error saving item translation: ' . $e->getMessage());
            $success = false;
        }
    }

    return $success;
}

/**
 * Get item translations for all languages
 * @param int $itemId Item ID
 * @return array Translations keyed by language code
 */
function getItemTranslations(int $itemId): array {
    $pdo = getDatabase();
    $translations = [];

    try {
        $stmt = $pdo->prepare('
            SELECT language_code, name, description
            FROM room_service_item_translations
            WHERE item_id = ?
        ');
        $stmt->execute([$itemId]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $translations[$row['language_code']] = [
                'name' => $row['name'],
                'description' => $row['description']
            ];
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    return $translations;
}

/**
 * Get translated item name with fallback
 * @param int $itemId Item ID
 * @param string|null $langCode Language code (defaults to current language)
 * @return string|null Translated name or null if not found
 */
function getItemTranslatedName(int $itemId, ?string $langCode = null): ?string {
    $langCode = $langCode ?? getCurrentLanguage();
    $defaultLang = getDefaultLanguage();
    $pdo = getDatabase();

    try {
        // Try requested language first
        $stmt = $pdo->prepare('
            SELECT name FROM room_service_item_translations
            WHERE item_id = ? AND language_code = ?
        ');
        $stmt->execute([$itemId, $langCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['name'])) {
            return $result['name'];
        }

        // Fallback to default language
        if ($langCode !== $defaultLang) {
            $stmt->execute([$itemId, $defaultLang]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['name'])) {
                return $result['name'];
            }
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    return null;
}

/**
 * Get translated item with fallback to base item
 * @param array $item Base item array
 * @param string|null $langCode Language code
 * @return array Item with translated name and description
 */
function getItemWithTranslation(array $item, ?string $langCode = null): array {
    $langCode = $langCode ?? getCurrentLanguage();
    $translations = getItemTranslations($item['id']);

    // Try current language
    if (isset($translations[$langCode]) && !empty($translations[$langCode]['name'])) {
        $item['name'] = $translations[$langCode]['name'];
        $item['description'] = $translations[$langCode]['description'] ?? $item['description'];
    }
    // Fallback to default language
    elseif ($langCode !== getDefaultLanguage() && isset($translations[getDefaultLanguage()])) {
        $item['name'] = $translations[getDefaultLanguage()]['name'] ?? $item['name'];
        $item['description'] = $translations[getDefaultLanguage()]['description'] ?? $item['description'];
    }

    return $item;
}

/**
 * Get room service items with translations
 * @param bool $activeOnly Only return active items
 * @param string|null $langCode Language code
 * @return array Items with translations applied
 */
function getRoomServiceItemsTranslated(bool $activeOnly = false, ?string $langCode = null): array {
    $items = getRoomServiceItems($activeOnly);
    $langCode = $langCode ?? getCurrentLanguage();

    return array_map(function($item) use ($langCode) {
        return getItemWithTranslation($item, $langCode);
    }, $items);
}

/**
 * Save category translations
 * @param string $categoryCode Category code
 * @param array $translations Array of ['language_code' => 'name']
 */
function saveCategoryTranslations(string $categoryCode, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $name) {
        if (!in_array($langCode, getSupportedLanguages())) {
            continue;
        }

        $name = trim($name);
        if (empty($name)) {
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO room_service_category_translations (category_code, language_code, name)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE name = VALUES(name)
            ');
            $success = $stmt->execute([$categoryCode, $langCode, $name]) && $success;
        } catch (PDOException $e) {
            error_log('Error saving category translation: ' . $e->getMessage());
            $success = false;
        }
    }

    return $success;
}

/**
 * Get category translations for all languages
 * @param string $categoryCode Category code
 * @return array Translations keyed by language code
 */
function getCategoryTranslations(string $categoryCode): array {
    $pdo = getDatabase();
    $translations = [];

    try {
        $stmt = $pdo->prepare('
            SELECT language_code, name
            FROM room_service_category_translations
            WHERE category_code = ?
        ');
        $stmt->execute([$categoryCode]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $translations[$row['language_code']] = $row['name'];
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    return $translations;
}

/**
 * Get translated category name with fallback
 * @param string $categoryCode Category code
 * @param string|null $langCode Language code
 * @return string|null Translated name or null
 */
function getCategoryTranslatedName(string $categoryCode, ?string $langCode = null): ?string {
    $langCode = $langCode ?? getCurrentLanguage();
    $defaultLang = getDefaultLanguage();
    $pdo = getDatabase();

    try {
        // Try requested language first
        $stmt = $pdo->prepare('
            SELECT name FROM room_service_category_translations
            WHERE category_code = ? AND language_code = ?
        ');
        $stmt->execute([$categoryCode, $langCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['name'])) {
            return $result['name'];
        }

        // Fallback to default language
        if ($langCode !== $defaultLang) {
            $stmt->execute([$categoryCode, $defaultLang]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['name'])) {
                return $result['name'];
            }
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    return null;
}

/**
 * Get room service categories with translations
 * @param string|null $langCode Language code
 * @return array Categories with translated names
 */
function getRoomServiceCategoriesTranslated(?string $langCode = null): array {
    $langCode = $langCode ?? getCurrentLanguage();
    $defaultLang = getDefaultLanguage();

    try {
        $pdo = getDatabase();
        $stmt = $pdo->query('SELECT code, name FROM room_service_categories WHERE is_active = 1 ORDER BY position ASC');
        $categories = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $translatedName = getCategoryTranslatedName($row['code'], $langCode);
            $categories[$row['code']] = $translatedName ?? $row['name'];
        }

        if (!empty($categories)) {
            return $categories;
        }
    } catch (PDOException $e) {
        // Table doesn't exist, use fallback
    }

    // Fallback to hardcoded categories
    return getRoomServiceCategories();
}

/**
 * Get all categories with full details including translations (for admin)
 * @return array Categories with translations for each language
 */
function getRoomServiceCategoriesAllWithTranslations(): array {
    $categories = getRoomServiceCategoriesAll();

    foreach ($categories as &$category) {
        $category['translations'] = getCategoryTranslations($category['code']);
    }

    return $categories;
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
 * Get yearly revenue data for charts (monthly breakdown for a specific year)
 * Returns all 12 months with revenue data, including empty months as 0
 * @param int|null $year The year to fetch (defaults to current year)
 * @return array
 */
function getRoomServiceYearlyRevenue(?int $year = null): array {
    $pdo = getDatabase();
    $year = $year ?? (int)date('Y');

    // Query monthly data for the specified year
    $stmt = $pdo->prepare("
        SELECT
            MONTH(created_at) as month_num,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE YEAR(created_at) = ?
        GROUP BY MONTH(created_at)
        ORDER BY month_num ASC
    ");
    $stmt->execute([$year]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Index results by month number for easy lookup
    $resultsByMonth = [];
    foreach ($results as $row) {
        $resultsByMonth[(int)$row['month_num']] = $row;
    }

    // French month names
    $monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    // Build array with all 12 months (including empty ones as 0)
    $data = [];
    for ($month = 1; $month <= 12; $month++) {
        $data[] = [
            'month' => $month,
            'month_str' => sprintf('%04d-%02d', $year, $month),
            'label' => $monthNames[$month - 1],
            'orders' => isset($resultsByMonth[$month]) ? (int)$resultsByMonth[$month]['orders'] : 0,
            'revenue' => isset($resultsByMonth[$month]) ? (float)$resultsByMonth[$month]['revenue'] : 0
        ];
    }

    return $data;
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
    try {
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
        $stmt = $pdo->prepare('SELECT * FROM guest_messages WHERE id = ?');
        $stmt->execute([$id]);
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
    try {
        $pdo = getDatabase();
        $stats = [];

        // Total messages
        $stmt = $pdo->query('SELECT COUNT(*) FROM guest_messages');
        $stats['total'] = (int)$stmt->fetchColumn();

        // New (unread) messages
        $stmt = $pdo->query('SELECT COUNT(*) FROM guest_messages WHERE status = "new"');
        $stats['new'] = (int)$stmt->fetchColumn();

        // In progress messages
        $stmt = $pdo->query('SELECT COUNT(*) FROM guest_messages WHERE status = "in_progress"');
        $stats['in_progress'] = (int)$stmt->fetchColumn();

        // Today's messages
        $stmt = $pdo->query('SELECT COUNT(*) FROM guest_messages WHERE DATE(created_at) = CURDATE()');
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

// =====================================================
// SETTINGS & THEME FUNCTIONS
// =====================================================

/**
 * Initialize settings table if it doesn't exist
 */
function initSettingsTable(): void {
    $pdo = getDatabase();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * Get a setting value
 */
function getSetting(string $key, $default = null) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Set a setting value
 */
function setSetting(string $key, string $value): bool {
    try {
        initSettingsTable();
        $pdo = getDatabase();
        $stmt = $pdo->prepare('
            INSERT INTO settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ');
        return $stmt->execute([$key, $value]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get the hotel description for footer
 * Returns empty string if not set
 */
/**
 * Get the hotel description for footer (with translation support)
 * @param string|null $langCode Language code (defaults to current language)
 * @return string Description in requested language or fallback to default
 */
function getHotelDescription(?string $langCode = null): string {
    $langCode = $langCode ?? getCurrentLanguage();
    $defaultLang = getDefaultLanguage();

    // Try requested language first (non-default languages stored with suffix)
    if ($langCode !== $defaultLang) {
        $translated = getSetting('hotel_description_' . $langCode, '');
        if (!empty($translated)) {
            return $translated;
        }
    }

    // Fallback to default language (French)
    return (string) getSetting('hotel_description', '');
}

/**
 * Set the hotel description for footer (default language)
 */
function setHotelDescription(string $description): bool {
    return setSetting('hotel_description', $description);
}

/**
 * Get all hotel description translations
 * @return array Translations keyed by language code
 */
function getHotelDescriptionTranslations(): array {
    $translations = [];
    $defaultLang = getDefaultLanguage();

    // Default language (French)
    $translations[$defaultLang] = getSetting('hotel_description', '');

    // Other languages
    foreach (getSupportedLanguages() as $langCode) {
        if ($langCode !== $defaultLang) {
            $translations[$langCode] = getSetting('hotel_description_' . $langCode, '');
        }
    }

    return $translations;
}

/**
 * Save hotel description translations
 * @param array $translations Array of ['language_code' => 'description']
 * @return bool Success status
 */
function setHotelDescriptionTranslations(array $translations): bool {
    $success = true;
    $defaultLang = getDefaultLanguage();

    foreach ($translations as $langCode => $description) {
        if (!in_array($langCode, getSupportedLanguages())) {
            continue;
        }

        $description = trim($description);

        if ($langCode === $defaultLang) {
            // Default language uses base key
            $success = setSetting('hotel_description', $description) && $success;
        } else {
            // Other languages use suffixed keys
            $success = setSetting('hotel_description_' . $langCode, $description) && $success;
        }
    }

    return $success;
}

/**
 * Default theme colors (matching style.css)
 */
function getDefaultThemeColors(): array {
    return [
        'color_primary' => '#8B6F47',
        'color_primary_dark' => '#6B5635',
        'color_secondary' => '#D4A574',
        'color_accent' => '#5C7C5E',
        'color_accent_light' => '#7A9B7C',
        'color_cream' => '#FAF6F0',
        'color_beige' => '#F0E6D8',
        'color_text' => '#3D3D3D',
        'color_text_light' => '#6B6B6B',
        'color_gold' => '#C9A962',
    ];
}

/**
 * Get all theme settings
 */
function getThemeSettings(): array {
    $defaults = getDefaultThemeColors();
    $settings = [];

    foreach ($defaults as $key => $default) {
        $settings[$key] = getSetting('theme_' . $key, $default);
    }

    return $settings;
}

/**
 * Save theme settings
 */
function saveThemeSettings(array $colors): bool {
    $defaults = getDefaultThemeColors();
    $success = true;

    foreach ($defaults as $key => $default) {
        if (isset($colors[$key])) {
            // Validate hex color format
            $color = $colors[$key];
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                if (!setSetting('theme_' . $key, $color)) {
                    $success = false;
                }
            }
        }
    }

    return $success;
}

/**
 * Reset theme to default colors
 */
function resetThemeSettings(): bool {
    $defaults = getDefaultThemeColors();
    $success = true;

    foreach ($defaults as $key => $default) {
        if (!setSetting('theme_' . $key, $default)) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Generate CSS variables from theme settings
 * Returns inline CSS to override :root variables
 */
function getThemeCSS(): string {
    $settings = getThemeSettings();
    $defaults = getDefaultThemeColors();

    $css = [];
    $cssVarMap = [
        'color_primary' => '--color-primary',
        'color_primary_dark' => '--color-primary-dark',
        'color_secondary' => '--color-secondary',
        'color_accent' => '--color-accent',
        'color_accent_light' => '--color-accent-light',
        'color_cream' => '--color-cream',
        'color_beige' => '--color-beige',
        'color_text' => '--color-text',
        'color_text_light' => '--color-text-light',
        'color_gold' => '--color-gold',
    ];

    foreach ($cssVarMap as $key => $cssVar) {
        $value = $settings[$key] ?? $defaults[$key];
        $css[] = "{$cssVar}: {$value};";
    }

    if (empty($css)) {
        return '';
    }

    return '<style id="theme-override">:root { ' . implode(' ', $css) . ' }</style>';
}

// =====================================================
// HOTEL IDENTITY SETTINGS
// =====================================================

/**
 * Default hotel identity constants
 */
define('DEFAULT_HOTEL_NAME', 'Hôtel Corintel');
define('DEFAULT_LOGO_TEXT', 'Bordeaux Est');

/**
 * Get the configured hotel name
 * @param bool $withPrefix If true, returns "Hôtel Name", if false returns just "Name"
 * @return string The hotel name
 */
function getHotelName(bool $withPrefix = true): string {
    $name = getSetting('hotel_name', DEFAULT_HOTEL_NAME);

    if (!$withPrefix) {
        // Remove "Hôtel " or "Hotel " prefix if present
        $name = preg_replace('/^(Hôtel|Hotel)\s+/i', '', $name);
    }

    return $name;
}

/**
 * Save the hotel name setting
 * @param string $name The hotel name to save
 * @return bool True on success
 */
function setHotelName(string $name): bool {
    $name = trim($name);
    if (empty($name)) {
        return false;
    }
    return setSetting('hotel_name', $name);
}

/**
 * Get the configured logo text (subtitle under hotel name)
 * @return string The logo text
 */
function getLogoText(): string {
    return getSetting('logo_text', DEFAULT_LOGO_TEXT);
}

/**
 * Save the logo text setting
 * @param string $text The logo text to save
 * @return bool True on success
 */
function setLogoText(string $text): bool {
    $text = trim($text);
    if (empty($text)) {
        return false;
    }
    return setSetting('logo_text', $text);
}

/**
 * Get hotel identity settings for use in templates
 * Returns an array with various forms of the hotel name and branding
 * @return array
 */
function getHotelIdentity(): array {
    $fullName = getHotelName(true);
    $shortName = getHotelName(false);
    $logoText = getLogoText();

    return [
        'full_name' => $fullName,                  // "Hôtel Corintel"
        'short_name' => $shortName,                // "Corintel"
        'logo_text' => $logoText,                  // "Bordeaux Est"
        'name_possessive_fr' => "l'" . $fullName,  // "l'Hôtel Corintel"
        'name_at_fr' => "à l'" . $fullName,        // "à l'Hôtel Corintel"
    ];
}

/**
 * Output hotel name JavaScript variable for client-side use
 * Call this in the <head> section before other scripts
 * @return string
 */
function getHotelNameJS(): string {
    $identity = getHotelIdentity();
    return '<script>window.hotelIdentity = ' . json_encode($identity, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ';</script>';
}

// =====================================================
// CONTACT INFORMATION SETTINGS
// =====================================================

/**
 * Default contact information
 */
define('DEFAULT_CONTACT_INFO', [
    'phone' => '',
    'email' => '',
    'address' => '',
    'postal_code' => '',
    'city' => '',
    'country' => 'France',
    'maps_url' => '',
]);

/**
 * Get a single contact setting
 * @param string $key Contact field key
 * @param string $default Default value if not set
 * @return string
 */
function getContactSetting(string $key, string $default = ''): string {
    $value = getSetting('contact_' . $key, null);
    if ($value === null) {
        return DEFAULT_CONTACT_INFO[$key] ?? $default;
    }
    return $value;
}

/**
 * Get all contact information
 * @return array
 */
function getContactInfo(): array {
    return [
        'phone' => getContactSetting('phone'),
        'email' => getContactSetting('email'),
        'address' => getContactSetting('address'),
        'postal_code' => getContactSetting('postal_code'),
        'city' => getContactSetting('city'),
        'country' => getContactSetting('country', 'France'),
        'maps_url' => getContactSetting('maps_url'),
    ];
}

/**
 * Save all contact information
 * @param array $data Contact data array
 * @return bool True if all settings saved successfully
 */
function saveContactInfo(array $data): bool {
    $fields = ['phone', 'email', 'address', 'postal_code', 'city', 'country', 'maps_url'];
    $success = true;

    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $value = trim($data[$field]);
            if (!setSetting('contact_' . $field, $value)) {
                $success = false;
            }
        }
    }

    return $success;
}

/**
 * Get formatted full address
 * @param bool $html Whether to format with HTML line breaks
 * @return string
 */
function getFormattedAddress(bool $html = true): string {
    $contact = getContactInfo();
    $parts = [];

    if (!empty($contact['address'])) {
        $parts[] = $contact['address'];
    }

    $cityLine = '';
    if (!empty($contact['postal_code'])) {
        $cityLine .= $contact['postal_code'];
    }
    if (!empty($contact['city'])) {
        $cityLine .= (!empty($cityLine) ? ' ' : '') . strtoupper($contact['city']);
    }
    if (!empty($cityLine)) {
        $parts[] = $cityLine;
    }

    if (!empty($contact['country'])) {
        $parts[] = $contact['country'];
    }

    $separator = $html ? '<br>' : ', ';
    return implode($separator, $parts);
}

/**
 * Get contact phone number, optionally formatted for tel: link
 * @param bool $forLink If true, returns number suitable for tel: link
 * @return string
 */
function getContactPhone(bool $forLink = false): string {
    $phone = getContactSetting('phone');
    if ($forLink && !empty($phone)) {
        // Remove spaces, dashes, dots for tel: link
        return preg_replace('/[\s\-\.]/', '', $phone);
    }
    return $phone;
}

/**
 * Get contact email
 * @return string
 */
function getContactEmail(): string {
    return getContactSetting('email');
}

/**
 * Get Google Maps URL
 * @return string
 */
function getContactMapsUrl(): string {
    return getContactSetting('maps_url');
}

/**
 * Check if contact info has required fields filled
 * @return bool
 */
function hasContactInfo(): bool {
    $contact = getContactInfo();
    return !empty($contact['phone']) || !empty($contact['email']) || !empty($contact['address']);
}

// =====================================================
// CONTENT MANAGEMENT FUNCTIONS
// =====================================================

/**
 * Image requirement modes for content sections
 */
define('IMAGE_REQUIRED', 'required');
define('IMAGE_OPTIONAL', 'optional');
define('IMAGE_FORBIDDEN', 'forbidden');

/**
 * Initialize content management tables
 */
function initContentTables(): void {
    $pdo = getDatabase();

    // Content sections configuration table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            page VARCHAR(50) NOT NULL,
            image_mode ENUM('required', 'optional', 'forbidden') DEFAULT 'optional',
            max_blocks INT DEFAULT NULL,
            has_title TINYINT(1) DEFAULT 1,
            has_description TINYINT(1) DEFAULT 1,
            has_link TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Content blocks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_blocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            title VARCHAR(255),
            description TEXT,
            image_filename VARCHAR(255),
            image_alt VARCHAR(255),
            link_url VARCHAR(500),
            link_text VARCHAR(100),
            position INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_section (section_code),
            INDEX idx_position (position),
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Add overlay columns to content_sections if not exist
    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN overlay_subtitle VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN overlay_title VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN overlay_description TEXT DEFAULT NULL");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN has_overlay TINYINT(1) DEFAULT 0");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Section overlay translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_overlay_translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            overlay_subtitle VARCHAR(255),
            overlay_title VARCHAR(255),
            overlay_description TEXT,
            UNIQUE KEY unique_section_lang (section_code, language_code),
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Add has_features flag to content_sections if not exist
    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN has_features TINYINT(1) DEFAULT 0");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Section features table (reusable for any section)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_features (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            icon_code VARCHAR(50) NOT NULL,
            label VARCHAR(100) NOT NULL,
            position INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_section (section_code),
            INDEX idx_position (position),
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Section feature translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_feature_translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            feature_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            label VARCHAR(100) NOT NULL,
            UNIQUE KEY unique_feature_lang (feature_id, language_code),
            FOREIGN KEY (feature_id) REFERENCES section_features(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Add columns for dynamic sections support
    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN is_dynamic TINYINT(1) DEFAULT 0");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN template_type VARCHAR(50) DEFAULT NULL");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN custom_name VARCHAR(100) DEFAULT NULL");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN has_services TINYINT(1) DEFAULT 0");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN has_gallery TINYINT(1) DEFAULT 0");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Add background_color column for customizable section backgrounds
    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN background_color VARCHAR(30) DEFAULT 'cream'");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Add image_position column for sections with image + text layout
    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN image_position VARCHAR(10) DEFAULT 'left'");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Add text_alignment column for presentation-style sections (center, left, right)
    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN text_alignment VARCHAR(10) DEFAULT 'center'");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Section services table (reusable service cards for any section)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            icon_code VARCHAR(50) NOT NULL,
            label VARCHAR(100) NOT NULL,
            description TEXT,
            position INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_section (section_code),
            INDEX idx_position (position),
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Section service translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_service_translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            label VARCHAR(100) NOT NULL,
            description TEXT,
            UNIQUE KEY unique_service_lang (service_id, language_code),
            FOREIGN KEY (service_id) REFERENCES section_services(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Section gallery items table (for image gallery sections)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_gallery_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            image_filename VARCHAR(255) NOT NULL,
            image_alt VARCHAR(255),
            title VARCHAR(100) NOT NULL,
            description TEXT,
            position INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_section (section_code),
            INDEX idx_position (position),
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Section gallery item translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_gallery_item_translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            UNIQUE KEY unique_item_lang (item_id, language_code),
            FOREIGN KEY (item_id) REFERENCES section_gallery_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Section templates table - defines reusable section templates
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            image_mode ENUM('required', 'optional', 'forbidden') DEFAULT 'optional',
            max_blocks INT DEFAULT 1,
            has_title TINYINT(1) DEFAULT 0,
            has_description TINYINT(1) DEFAULT 0,
            has_link TINYINT(1) DEFAULT 0,
            has_overlay TINYINT(1) DEFAULT 1,
            has_features TINYINT(1) DEFAULT 1,
            has_services TINYINT(1) DEFAULT 0,
            css_class VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Add has_services column to section_templates if not exists
    try {
        $pdo->exec("ALTER TABLE section_templates ADD COLUMN has_services TINYINT(1) DEFAULT 0");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Add has_gallery column to section_templates if not exists
    try {
        $pdo->exec("ALTER TABLE section_templates ADD COLUMN has_gallery TINYINT(1) DEFAULT 0");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Seed default templates
    seedSectionTemplates();

    // Migrate any existing data from old template codes to new ones
    migrateSectionTemplates();
}

/**
 * Seed default content sections
 */
function seedContentSections(): void {
    $pdo = getDatabase();

    // Only structural header sections are seeded as fixed sections
    // All content sections are now managed through the dynamic sections system
    $sections = [
        // Home page - only hero carousel
        ['home_hero', 'Carrousel d\'accueil', 'Images du diaporama principal (3 images recommandées)', 'home', IMAGE_REQUIRED, null, 0, 0, 0, 1],

        // Services page - only header image
        ['services_hero', 'Image d\'en-tête Services', 'Image de fond de la bannière Services', 'services', IMAGE_REQUIRED, 1, 0, 0, 0, 1],

        // Activities page - only header image
        ['activities_hero', 'Image d\'en-tête Activités', 'Image de fond de la bannière À découvrir', 'activities', IMAGE_REQUIRED, 1, 0, 0, 0, 1],

        // Contact page sections (unchanged for now)
        ['contact_hero', 'Image d\'en-tête Contact', 'Image de fond de la bannière Contact', 'contact', IMAGE_REQUIRED, 1, 0, 0, 0, 1],
        ['contact_info', 'Informations de contact', 'Coordonnées et horaires (texte uniquement)', 'contact', IMAGE_FORBIDDEN, 1, 1, 1, 0, 2],
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO content_sections
        (code, name, description, page, image_mode, max_blocks, has_title, has_description, has_link, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($sections as $section) {
        $stmt->execute($section);
    }

    // Seed default overlay texts for home_hero
    seedDefaultOverlayTexts();
}

/**
 * Seed default overlay texts for sections that need them
 * Note: Fields are left empty - admin uses placeholders for guidance
 */
function seedDefaultOverlayTexts(): void {
    $pdo = getDatabase();

    // Enable overlay for home_hero and ensure it's configured as image-only (no title/description per image)
    $stmt = $pdo->prepare('
        UPDATE content_sections
        SET has_overlay = 1,
            has_title = 0,
            has_description = 0,
            has_link = 0
        WHERE code = ?
    ');
    $stmt->execute(['home_hero']);
}

/**
 * Clean up legacy static sections that are no longer used
 * Only hero/header sections should remain as static sections
 * All other content is managed through the dynamic sections system
 */
function cleanupLegacyStaticSections(): void {
    $pdo = getDatabase();

    // Only these core sections should remain (hero/header sections)
    $keepSections = [
        'home_hero',
        'services_hero',
        'activities_hero',
        'contact_hero',
        'contact_info',
    ];

    // Helper to safely execute a delete (ignores missing tables)
    $safeDelete = function($sql, $params) use ($pdo) {
        try {
            $pdo->prepare($sql)->execute($params);
        } catch (PDOException $e) {
            // Ignore errors (table might not exist)
        }
    };

    try {
        // Get ALL sections that are:
        // 1. NOT in the keep list AND
        // 2. NOT dynamically created from templates (template_type is NULL or empty)
        $placeholders = implode(',', array_fill(0, count($keepSections), '?'));
        $stmt = $pdo->prepare("
            SELECT code FROM content_sections
            WHERE code NOT IN ($placeholders)
            AND (template_type IS NULL OR template_type = '')
        ");
        $stmt->execute($keepSections);
        $legacySections = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Delete each legacy section and its related data
        foreach ($legacySections as $sectionCode) {
            // Delete related data first (ignore if tables don't exist)
            $safeDelete('DELETE FROM section_feature_translations WHERE feature_id IN (SELECT id FROM section_features WHERE section_code = ?)', [$sectionCode]);
            $safeDelete('DELETE FROM section_features WHERE section_code = ?', [$sectionCode]);
            $safeDelete('DELETE FROM section_overlay_translations WHERE overlay_id IN (SELECT id FROM section_overlay_texts WHERE section_code = ?)', [$sectionCode]);
            $safeDelete('DELETE FROM section_overlay_texts WHERE section_code = ?', [$sectionCode]);
            $safeDelete('DELETE FROM section_gallery_translations WHERE item_id IN (SELECT id FROM section_gallery_items WHERE section_code = ?)', [$sectionCode]);
            $safeDelete('DELETE FROM section_gallery_items WHERE section_code = ?', [$sectionCode]);
            $safeDelete('DELETE FROM section_service_translations WHERE service_id IN (SELECT id FROM section_services WHERE section_code = ?)', [$sectionCode]);
            $safeDelete('DELETE FROM section_services WHERE section_code = ?', [$sectionCode]);
            $safeDelete('DELETE FROM content_blocks WHERE section_code = ?', [$sectionCode]);

            // Delete the section itself
            $safeDelete('DELETE FROM content_sections WHERE code = ?', [$sectionCode]);
        }
    } catch (PDOException $e) {
        // Silently fail - tables might not exist yet
    }
}

// =====================================================
// SECTION TEMPLATES (Reusable Section Definitions)
// =====================================================

/**
 * Seed default section templates
 */
function seedSectionTemplates(): void {
    $pdo = getDatabase();

    $templates = [
        // Services section with indicators (icons + labels)
        [
            'code' => 'services_indicators',
            'name' => 'Section Services (indicateurs)',
            'description' => 'Section avec textes, image optionnelle et indicateurs avec icônes',
            'image_mode' => IMAGE_OPTIONAL,
            'max_blocks' => 1,
            'has_title' => 0,
            'has_description' => 0,
            'has_link' => 0,
            'has_overlay' => 1,
            'has_features' => 1,
            'has_services' => 0,
            'has_gallery' => 0,
            'css_class' => 'section-services-indicators'
        ],
        // Text-only template: overlay texts, no images
        [
            'code' => 'text_style',
            'name' => 'Section Texte',
            'description' => 'Section avec textes uniquement',
            'image_mode' => IMAGE_FORBIDDEN,
            'max_blocks' => 0,
            'has_title' => 0,
            'has_description' => 0,
            'has_link' => 0,
            'has_overlay' => 1,
            'has_features' => 1,
            'has_services' => 0,
            'has_gallery' => 0,
            'css_class' => 'section-text-style'
        ],
        // Services-style template: overlay texts + service cards grid
        [
            'code' => 'services_style',
            'name' => 'Section Services (grille)',
            'description' => 'Section avec textes et grille de services (icône + texte)',
            'image_mode' => IMAGE_FORBIDDEN,
            'max_blocks' => 0,
            'has_title' => 0,
            'has_description' => 0,
            'has_link' => 0,
            'has_overlay' => 1,
            'has_features' => 0,
            'has_services' => 1,
            'has_gallery' => 0,
            'css_class' => 'section-services-style'
        ],
        // Services section with checklist (checkmarks + labels)
        [
            'code' => 'services_checklist',
            'name' => 'Section Services (liste à puces)',
            'description' => 'Section avec textes, image optionnelle et liste à puces avec coches',
            'image_mode' => IMAGE_OPTIONAL,
            'max_blocks' => 1,
            'has_title' => 0,
            'has_description' => 0,
            'has_link' => 0,
            'has_overlay' => 1,
            'has_features' => 1,
            'has_services' => 0,
            'has_gallery' => 0,
            'css_class' => 'section-services-checklist'
        ],
        // Image gallery section (grid of image cards with title + description)
        [
            'code' => 'gallery_style',
            'name' => 'Galerie d\'images',
            'description' => 'Section avec textes et grille d\'images (image + titre + description)',
            'image_mode' => IMAGE_FORBIDDEN,
            'max_blocks' => 0,
            'has_title' => 0,
            'has_description' => 0,
            'has_link' => 0,
            'has_overlay' => 1,
            'has_features' => 0,
            'has_services' => 0,
            'has_gallery' => 1,
            'css_class' => 'section-gallery-style'
        ],
        // Image gallery type 2 (room-card style with overlay)
        [
            'code' => 'gallery_cards',
            'name' => 'Galerie d\'images (type 2)',
            'description' => 'Grille d\'images avec titre et description en superposition',
            'image_mode' => IMAGE_FORBIDDEN,
            'max_blocks' => 0,
            'has_title' => 0,
            'has_description' => 0,
            'has_link' => 0,
            'has_overlay' => 1,
            'has_features' => 0,
            'has_services' => 0,
            'has_gallery' => 1,
            'css_class' => 'section-gallery-cards'
        ],
        // Presentation hero: full-width image with text overlay (like page hero)
        [
            'code' => 'presentation_hero',
            'name' => 'Image avec texte (type présentation)',
            'description' => 'Image pleine largeur avec titre et description en superposition (style hero)',
            'image_mode' => IMAGE_REQUIRED,
            'max_blocks' => 1,
            'has_title' => 0,
            'has_description' => 0,
            'has_link' => 0,
            'has_overlay' => 1,
            'has_features' => 0,
            'has_services' => 0,
            'has_gallery' => 0,
            'css_class' => 'section-presentation-hero'
        ]
    ];

    foreach ($templates as $template) {
        $stmt = $pdo->prepare('SELECT id FROM section_templates WHERE code = ?');
        $stmt->execute([$template['code']]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare('
                INSERT INTO section_templates (code, name, description, image_mode, max_blocks, has_title, has_description, has_link, has_overlay, has_features, has_services, has_gallery, css_class)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $template['code'],
                $template['name'],
                $template['description'],
                $template['image_mode'],
                $template['max_blocks'],
                $template['has_title'],
                $template['has_description'],
                $template['has_link'],
                $template['has_overlay'],
                $template['has_features'],
                $template['has_services'],
                $template['has_gallery'] ?? 0,
                $template['css_class']
            ]);
        }
    }
}

/**
 * Migrate existing data from old template codes to new ones
 * This ensures backward compatibility with any existing sections
 */
function migrateSectionTemplates(): void {
    $pdo = getDatabase();

    // Mapping of old codes to new codes
    $migrations = [
        'intro_style' => 'services_indicators',
        'detail_style' => 'services_checklist',
    ];

    // Update content_sections.template_type from old to new values
    foreach ($migrations as $oldCode => $newCode) {
        $stmt = $pdo->prepare('UPDATE content_sections SET template_type = ? WHERE template_type = ?');
        $stmt->execute([$newCode, $oldCode]);
    }

    // Delete old templates from section_templates table
    $oldCodes = array_keys($migrations);

    foreach ($oldCodes as $oldCode) {
        $stmt = $pdo->prepare('DELETE FROM section_templates WHERE code = ?');
        $stmt->execute([$oldCode]);
    }
}

/**
 * Get all section templates
 */
function getSectionTemplates(): array {
    $pdo = getDatabase();
    $stmt = $pdo->query('SELECT * FROM section_templates ORDER BY name');
    return $stmt->fetchAll();
}

/**
 * Get a section template by code
 */
function getSectionTemplate(string $code): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM section_templates WHERE code = ?');
    $stmt->execute([$code]);
    return $stmt->fetch() ?: null;
}

// =====================================================
// DYNAMIC SECTIONS (Admin-created sections)
// =====================================================

/**
 * Get all dynamic sections for a page
 */
function getDynamicSections(string $page): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        SELECT cs.*, st.css_class as template_css_class, st.has_services as template_has_services, st.has_gallery as template_has_gallery
        FROM content_sections cs
        LEFT JOIN section_templates st ON cs.template_type = st.code
        WHERE cs.page = ? AND cs.is_dynamic = 1
        ORDER BY cs.sort_order
    ');
    $stmt->execute([$page]);
    $sections = $stmt->fetchAll();

    // Merge template flags into section data
    foreach ($sections as &$section) {
        // Use template has_services if section doesn't have it set
        if (!isset($section['has_services']) && isset($section['template_has_services'])) {
            $section['has_services'] = $section['template_has_services'];
        }
        // Use template has_gallery if section doesn't have it set
        if (!isset($section['has_gallery']) && isset($section['template_has_gallery'])) {
            $section['has_gallery'] = $section['template_has_gallery'];
        }
    }

    return $sections;
}

/**
 * Get a dynamic section by code with full data
 */
function getDynamicSectionWithData(string $sectionCode): ?array {
    $section = getContentSection($sectionCode);

    // Check if section exists and is a dynamic section
    if (!$section || !isset($section['is_dynamic']) || (int)$section['is_dynamic'] !== 1) {
        return null;
    }

    // Get overlay texts with translations
    $section['overlay'] = getSectionOverlayWithTranslations($sectionCode);

    // Get features with translations if section supports them
    if (!empty($section['has_features'])) {
        $section['features'] = getSectionFeaturesWithTranslations($sectionCode);
    } else {
        $section['features'] = [];
    }

    // Get images/content blocks
    $section['blocks'] = getContentBlocks($sectionCode);

    // Add template CSS class if not present
    if (empty($section['template_css_class']) && !empty($section['template_type'])) {
        $template = getSectionTemplate($section['template_type']);
        if ($template) {
            $section['template_css_class'] = $template['css_class'];
        }
    }

    return $section;
}

/**
 * Get all dynamic sections for a page with full data
 */
function getDynamicSectionsWithData(string $page): array {
    $sections = getDynamicSections($page);
    $result = [];

    foreach ($sections as $section) {
        // getDynamicSections already filters by is_dynamic=1, so we can trust these are dynamic
        // Just augment with overlay, features, services, and blocks data

        // Get overlay texts with translations
        $section['overlay'] = getSectionOverlayWithTranslations($section['code']);

        // Get features with translations if section supports them
        if (!empty($section['has_features'])) {
            $section['features'] = getSectionFeaturesWithTranslations($section['code']);
        } else {
            $section['features'] = [];
        }

        // Get services with translations if section supports them
        if (!empty($section['has_services'])) {
            $section['services'] = getSectionServicesWithTranslations($section['code']);
        } else {
            $section['services'] = [];
        }

        // Get gallery items with translations if section supports them
        if (!empty($section['has_gallery'])) {
            $section['gallery_items'] = getSectionGalleryItemsWithTranslations($section['code']);
        } else {
            $section['gallery_items'] = [];
        }

        // Get images/content blocks
        $section['blocks'] = getContentBlocks($section['code']);

        $result[] = $section;
    }

    return $result;
}

/**
 * Create a dynamic section from a template
 */
function createDynamicSection(string $page, string $templateCode, string $customName): ?string {
    $pdo = getDatabase();

    $template = getSectionTemplate($templateCode);
    if (!$template) {
        return null;
    }

    // Generate unique section code
    $baseCode = 'dynamic_' . $page . '_' . preg_replace('/[^a-z0-9]/', '', strtolower($customName));
    $code = $baseCode;
    $counter = 1;

    // Ensure unique code
    while (getContentSection($code)) {
        $code = $baseCode . '_' . $counter;
        $counter++;
    }

    // Get next sort order for the page
    $stmt = $pdo->prepare('SELECT MAX(sort_order) FROM content_sections WHERE page = ?');
    $stmt->execute([$page]);
    $maxOrder = $stmt->fetchColumn() ?: 0;

    // Create the section
    $stmt = $pdo->prepare('
        INSERT INTO content_sections (
            code, name, description, page, image_mode, max_blocks,
            has_title, has_description, has_link, has_overlay, has_features, has_services, has_gallery,
            sort_order, is_dynamic, template_type, custom_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
    ');

    $success = $stmt->execute([
        $code,
        $customName,
        $template['description'],
        $page,
        $template['image_mode'],
        $template['max_blocks'],
        $template['has_title'],
        $template['has_description'],
        $template['has_link'],
        $template['has_overlay'],
        $template['has_features'],
        $template['has_services'] ?? 0,
        $template['has_gallery'] ?? 0,
        $maxOrder + 1,
        $templateCode,
        $customName
    ]);

    return $success ? $code : null;
}

/**
 * Update a dynamic section's name
 */
function updateDynamicSectionName(string $sectionCode, string $newName): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE content_sections
        SET name = ?, custom_name = ?
        WHERE code = ? AND is_dynamic = 1
    ');
    return $stmt->execute([$newName, $newName, $sectionCode]);
}

/**
 * Delete a dynamic section and all its data
 * Cascading delete handles: content_blocks, section_features, section_overlay_translations
 */
function deleteDynamicSection(string $sectionCode): bool {
    $pdo = getDatabase();

    // Verify it's a dynamic section
    $section = getContentSection($sectionCode);
    if (!$section || !$section['is_dynamic']) {
        return false;
    }

    // Delete associated image files first
    $blocks = getContentBlocks($sectionCode);
    foreach ($blocks as $block) {
        if (!empty($block['image_filename']) && strpos($block['image_filename'], 'uploads/') === 0) {
            $path = __DIR__ . '/../' . $block['image_filename'];
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    // Delete the section (foreign key cascades handle related tables)
    $stmt = $pdo->prepare('DELETE FROM content_sections WHERE code = ? AND is_dynamic = 1');
    return $stmt->execute([$sectionCode]);
}

/**
 * Reorder dynamic sections within a page
 */
function reorderDynamicSections(string $page, array $sectionCodes): bool {
    $pdo = getDatabase();

    $position = 100; // Start after static sections
    foreach ($sectionCodes as $code) {
        $stmt = $pdo->prepare('
            UPDATE content_sections
            SET sort_order = ?
            WHERE code = ? AND page = ? AND is_dynamic = 1
        ');
        $stmt->execute([$position, $code, $page]);
        $position++;
    }

    return true;
}

/**
 * Check if a page has any dynamic sections
 */
function pageHasDynamicSections(string $page): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM content_sections WHERE page = ? AND is_dynamic = 1');
    $stmt->execute([$page]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get count of dynamic sections for a page
 */
function countDynamicSections(string $page): int {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM content_sections WHERE page = ? AND is_dynamic = 1');
    $stmt->execute([$page]);
    return (int) $stmt->fetchColumn();
}

// =====================================================
// DYNAMIC SECTION RENDERING
// =====================================================

/**
 * Render a dynamic section based on its template type
 * Returns HTML string, or empty string if section has no renderable content
 *
 * @param array $section Section data from getDynamicSectionWithData()
 * @param string $currentLang Current language code (default: 'fr')
 * @return string HTML output
 */
function renderDynamicSection(array $section, string $currentLang = 'fr'): string {
    $templateType = $section['template_type'] ?? 'services_indicators';

    // Get localized overlay texts
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';

    // Check if section has any content to display
    $hasOverlayContent = !empty($subtitle) || !empty($title) || !empty($description);
    $hasFeatures = !empty($section['features']);
    $hasServices = !empty($section['services']);
    $hasImages = !empty($section['blocks']);

    // If section has no content, use the section's custom name as a fallback title
    // This ensures newly created sections are visible until content is added
    if (!$hasOverlayContent && !$hasFeatures && !$hasServices && !$hasImages) {
        // Use custom_name or name as fallback title
        $fallbackTitle = $section['custom_name'] ?? $section['name'] ?? '';
        if (!empty($fallbackTitle)) {
            // Inject fallback title into overlay so render functions can use it
            if (!isset($section['overlay'])) {
                $section['overlay'] = [];
            }
            $section['overlay']['title'] = $fallbackTitle;
            // Also set it for the current language
            if (!isset($section['overlay']['translations'])) {
                $section['overlay']['translations'] = [];
            }
            if (!isset($section['overlay']['translations'][$currentLang])) {
                $section['overlay']['translations'][$currentLang] = [];
            }
            $section['overlay']['translations'][$currentLang]['title'] = $fallbackTitle;
        } else {
            // No content and no name - truly empty section
            return '';
        }
    }

    // Get CSS class from template
    $cssClass = $section['template_css_class'] ?? 'section-services-indicators';
    $sectionCode = $section['code'];

    // Start output buffering
    ob_start();

    switch ($templateType) {
        case 'services_indicators':
        case 'intro_style': // Legacy support
            renderServicesIndicatorsSection($section, $currentLang, $cssClass);
            break;

        case 'text_style':
            renderTextStyleSection($section, $currentLang, $cssClass);
            break;

        case 'services_style':
            renderServicesStyleSection($section, $currentLang, $cssClass);
            break;

        case 'services_checklist':
        case 'detail_style': // Legacy support
            renderServicesChecklistSection($section, $currentLang, $cssClass);
            break;

        case 'gallery_style':
            renderGalleryStyleSection($section, $currentLang, $cssClass);
            break;

        case 'gallery_cards':
            renderGalleryCardsSection($section, $currentLang, $cssClass);
            break;

        case 'presentation_hero':
            renderPresentationHeroSection($section, $currentLang, $cssClass);
            break;

        default:
            // Fallback to services indicators
            renderServicesIndicatorsSection($section, $currentLang, $cssClass);
    }

    return ob_get_clean();
}

/**
 * Render services section with indicators (icons + labels)
 */
function renderServicesIndicatorsSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $features = $section['features'] ?? [];
    $blocks = $section['blocks'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');
    $imagePos = getImagePositionClass($section['image_position'] ?? 'left');

    // Get first image if available
    $image = !empty($blocks) ? $blocks[0] : null;
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <div class="intro-grid <?= h($imagePos) ?>">
                <?php if ($image && !empty($image['image_filename'])): ?>
                <div class="intro-image">
                    <img src="<?= h($image['image_filename']) ?>" alt="<?= h($image['image_alt'] ?: $title) ?>">
                </div>
                <?php endif; ?>
                <div class="intro-content" <?= (!$image || empty($image['image_filename'])) ? 'style="grid-column: 1 / -1;"' : '' ?>>
                    <?php if ($subtitle): ?>
                    <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                    <?php endif; ?>
                    <?php if ($title): ?>
                    <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                    <?php endif; ?>
                    <?php if ($description): ?>
                    <div class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description">
                        <?php
                        $paragraphs = preg_split('/\n\s*\n/', $description);
                        foreach ($paragraphs as $p):
                            $p = trim($p);
                            if ($p):
                        ?>
                        <p><?= nl2br(h($p)) ?></p>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($features)): ?>
                    <div class="intro-features">
                        <?php foreach ($features as $feature):
                            $featureLabel = $feature['translations'][$currentLang] ?? $feature['label'];
                        ?>
                        <div class="intro-feature">
                            <?= getIconSvg($feature['icon_code']) ?>
                            <span data-dynamic-feature="<?= $feature['id'] ?>"><?= h($featureLabel) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Render text-style section (text only + optional features)
 */
function renderTextStyleSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $features = $section['features'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');

    if (empty($subtitle) && empty($title) && empty($description) && empty($features)) {
        return;
    }
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <div class="text-content" style="max-width: 800px; margin: 0 auto; text-align: center;">
                <?php if ($subtitle): ?>
                <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                <?php endif; ?>
                <?php if ($title): ?>
                <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                <?php endif; ?>
                <?php if ($description): ?>
                <div class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description">
                    <?php
                    $paragraphs = preg_split('/\n\s*\n/', $description);
                    foreach ($paragraphs as $p):
                        $p = trim($p);
                        if ($p):
                    ?>
                    <p><?= nl2br(h($p)) ?></p>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($features)): ?>
                <div class="intro-features" style="justify-content: center; margin-top: 2rem;">
                    <?php foreach ($features as $feature):
                        $featureLabel = $feature['translations'][$currentLang] ?? $feature['label'];
                    ?>
                    <div class="intro-feature">
                        <?= getIconSvg($feature['icon_code']) ?>
                        <span data-dynamic-feature="<?= $feature['id'] ?>"><?= h($featureLabel) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Render services-style section (title + services grid)
 */
function renderServicesStyleSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $services = $section['services'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');

    // If no overlay content and no services, don't render
    if (empty($subtitle) && empty($title) && empty($description) && empty($services)) {
        return;
    }
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <?php if ($subtitle || $title || $description): ?>
            <div class="section-header">
                <?php if ($subtitle): ?>
                <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                <?php endif; ?>
                <?php if ($title): ?>
                <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                <?php endif; ?>
                <?php if ($description): ?>
                <p class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description"><?= h($description) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($services)): ?>
            <div class="services-grid">
                <?php foreach ($services as $service):
                    $serviceLabel = getServiceLabelForLanguage($service, $currentLang);
                    $serviceDescription = getServiceDescriptionForLanguage($service, $currentLang);
                ?>
                <div class="service-card">
                    <div class="service-icon">
                        <?= getIconSvg($service['icon_code']) ?>
                    </div>
                    <h3 data-dynamic-service="<?= $service['id'] ?>:label"><?= h($serviceLabel) ?></h3>
                    <?php if (!empty($serviceDescription)): ?>
                    <p data-dynamic-service="<?= $service['id'] ?>:description"><?= h($serviceDescription) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/**
 * Render gallery-style section (image cards grid like Wine Tourism)
 */
function renderGalleryStyleSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $galleryItems = $section['gallery_items'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');

    // If no overlay content and no gallery items, don't render
    if (empty($subtitle) && empty($title) && empty($description) && empty($galleryItems)) {
        return;
    }
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <?php if ($subtitle || $title || $description): ?>
            <div class="section-header">
                <?php if ($subtitle): ?>
                <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                <?php endif; ?>
                <?php if ($title): ?>
                <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                <?php endif; ?>
                <?php if ($description): ?>
                <p class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description"><?= h($description) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($galleryItems)): ?>
            <div class="activities-grid">
                <?php foreach ($galleryItems as $item):
                    $itemTitle = getGalleryItemTitleForLanguage($item, $currentLang);
                    $itemDescription = getGalleryItemDescriptionForLanguage($item, $currentLang);
                ?>
                <div class="activity-card">
                    <img src="<?= h($item['image_filename']) ?>" alt="<?= h($item['image_alt'] ?: $itemTitle) ?>">
                    <div class="activity-card-content">
                        <h3 data-dynamic-gallery="<?= $item['id'] ?>:title"><?= h($itemTitle) ?></h3>
                        <?php if (!empty($itemDescription)): ?>
                        <p data-dynamic-gallery="<?= $item['id'] ?>:description"><?= h($itemDescription) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/**
 * Render gallery cards section (room-card style with overlay)
 * Uses the room-card layout with image and overlay title/description
 */
function renderGalleryCardsSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $galleryItems = $section['gallery_items'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');

    // If no gallery items, don't render the section
    if (empty($galleryItems)) {
        return;
    }
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <?php if ($subtitle || $title || $description): ?>
            <div class="section-header">
                <?php if ($subtitle): ?>
                <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                <?php endif; ?>
                <?php if ($title): ?>
                <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                <?php endif; ?>
                <?php if ($description): ?>
                <p class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description"><?= h($description) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="rooms-gallery">
                <?php foreach ($galleryItems as $item):
                    $itemTitle = getGalleryItemTitleForLanguage($item, $currentLang);
                    $itemDescription = getGalleryItemDescriptionForLanguage($item, $currentLang);
                ?>
                <div class="room-card">
                    <img src="<?= h($item['image_filename']) ?>" alt="<?= h($item['image_alt'] ?: $itemTitle) ?>">
                    <div class="room-card-overlay">
                        <h4 data-dynamic-gallery="<?= $item['id'] ?>:title"><?= h($itemTitle) ?></h4>
                        <?php if (!empty($itemDescription)): ?>
                        <p data-dynamic-gallery="<?= $item['id'] ?>:description"><?= h($itemDescription) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Render services section with checklist (checkmarks + labels)
 * Uses service-detail layout with checkmark feature tags
 */
function renderServicesChecklistSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $features = $section['features'] ?? [];
    $blocks = $section['blocks'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');
    $imagePos = getImagePositionClass($section['image_position'] ?? 'left');

    // Get first image if available
    $image = !empty($blocks) ? $blocks[0] : null;
    $hasImage = $image && !empty($image['image_filename']);
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <div class="service-detail <?= h($imagePos) ?>">
                <?php if ($hasImage): ?>
                <div class="service-detail-image">
                    <img src="<?= h($image['image_filename']) ?>" alt="<?= h($image['image_alt'] ?: $title) ?>">
                </div>
                <?php endif; ?>
                <div class="service-detail-content" <?= !$hasImage ? 'style="grid-column: 1 / -1;"' : '' ?>>
                    <?php if ($subtitle): ?>
                    <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                    <?php endif; ?>
                    <?php if ($title): ?>
                    <h3 data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h3>
                    <?php endif; ?>
                    <?php if ($description): ?>
                    <div data-dynamic-text="<?= h($sectionCode) ?>:description">
                        <?php
                        $paragraphs = preg_split('/\n\s*\n/', $description);
                        foreach ($paragraphs as $p):
                            $p = trim($p);
                            if ($p):
                        ?>
                        <p><?= nl2br(h($p)) ?></p>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($features)): ?>
                    <div class="service-features">
                        <?php foreach ($features as $feature):
                            $featureLabel = $feature['translations'][$currentLang] ?? $feature['label'];
                        ?>
                        <span class="service-feature-tag" data-dynamic-feature="<?= $feature['id'] ?>">
                            <?= getIconSvg('check') ?>
                            <?= h($featureLabel) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Render presentation hero section (full-width image with text overlay)
 * Similar to page heroes but as a reusable dynamic section
 */
function renderPresentationHeroSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $blocks = $section['blocks'] ?? [];
    $sectionCode = $section['code'];

    // Get first image - required for this section type
    $image = !empty($blocks) ? $blocks[0] : null;

    // If no image, don't render the section
    if (!$image || empty($image['image_filename'])) {
        return;
    }
    ?>
    <section class="presentation-hero" data-section="<?= h($sectionCode) ?>" style="background-image: url('<?= h($image['image_filename']) ?>');">
        <div class="presentation-hero-overlay">
            <?php if ($subtitle): ?>
            <p class="presentation-hero-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
            <?php endif; ?>
            <?php if ($title): ?>
            <h2 class="presentation-hero-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
            <?php endif; ?>
            <?php if ($description): ?>
            <p class="presentation-hero-description" data-dynamic-text="<?= h($sectionCode) ?>:description"><?= h($description) ?></p>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/**
 * Render all dynamic sections for a page
 * Returns HTML string, or empty string if no sections
 *
 * @param string $page Page code (e.g., 'home')
 * @param string $currentLang Current language code
 * @return string HTML output
 */
function renderDynamicSectionsForPage(string $page, string $currentLang = 'fr'): string {
    $sections = getDynamicSectionsWithData($page);

    if (empty($sections)) {
        return '';
    }

    $output = '';
    foreach ($sections as $section) {
        $output .= renderDynamicSection($section, $currentLang);
    }

    return $output;
}

/**
 * Get dynamic sections translations for JavaScript
 * Used for client-side language switching
 *
 * @param string $page Page code
 * @return array Translations keyed by section code
 */
function getDynamicSectionsTranslations(string $page): array {
    $sections = getDynamicSectionsWithData($page);
    $translations = [];

    foreach ($sections as $section) {
        $sectionCode = $section['code'];
        $overlay = $section['overlay'] ?? [];

        // Build translations for overlay texts
        $translations[$sectionCode] = [
            'fr' => [
                'subtitle' => $overlay['subtitle'] ?? '',
                'title' => $overlay['title'] ?? '',
                'description' => $overlay['description'] ?? ''
            ]
        ];

        foreach (['en', 'es', 'it'] as $lang) {
            $translations[$sectionCode][$lang] = [
                'subtitle' => $overlay['translations'][$lang]['subtitle'] ?? '',
                'title' => $overlay['translations'][$lang]['title'] ?? '',
                'description' => $overlay['translations'][$lang]['description'] ?? ''
            ];
        }

        // Build translations for features
        if (!empty($section['features'])) {
            $translations[$sectionCode]['features'] = [];
            foreach ($section['features'] as $feature) {
                $translations[$sectionCode]['features'][$feature['id']] = [
                    'fr' => $feature['label'],
                    'en' => $feature['translations']['en'] ?? $feature['label'],
                    'es' => $feature['translations']['es'] ?? $feature['label'],
                    'it' => $feature['translations']['it'] ?? $feature['label']
                ];
            }
        }

        // Build translations for services
        if (!empty($section['services'])) {
            $translations[$sectionCode]['services'] = [];
            foreach ($section['services'] as $service) {
                $translations[$sectionCode]['services'][$service['id']] = [
                    'fr' => [
                        'label' => $service['label'],
                        'description' => $service['description'] ?? ''
                    ],
                    'en' => [
                        'label' => $service['translations']['en']['label'] ?? $service['label'],
                        'description' => $service['translations']['en']['description'] ?? ($service['description'] ?? '')
                    ],
                    'es' => [
                        'label' => $service['translations']['es']['label'] ?? $service['label'],
                        'description' => $service['translations']['es']['description'] ?? ($service['description'] ?? '')
                    ],
                    'it' => [
                        'label' => $service['translations']['it']['label'] ?? $service['label'],
                        'description' => $service['translations']['it']['description'] ?? ($service['description'] ?? '')
                    ]
                ];
            }
        }

        // Build translations for gallery items
        if (!empty($section['gallery_items'])) {
            $translations[$sectionCode]['gallery'] = [];
            foreach ($section['gallery_items'] as $item) {
                $translations[$sectionCode]['gallery'][$item['id']] = [
                    'fr' => [
                        'title' => $item['title'],
                        'description' => $item['description'] ?? ''
                    ],
                    'en' => [
                        'title' => $item['translations']['en']['title'] ?? $item['title'],
                        'description' => $item['translations']['en']['description'] ?? ($item['description'] ?? '')
                    ],
                    'es' => [
                        'title' => $item['translations']['es']['title'] ?? $item['title'],
                        'description' => $item['translations']['es']['description'] ?? ($item['description'] ?? '')
                    ],
                    'it' => [
                        'title' => $item['translations']['it']['title'] ?? $item['title'],
                        'description' => $item['translations']['it']['description'] ?? ($item['description'] ?? '')
                    ]
                ];
            }
        }
    }

    return $translations;
}

/**
 * Get all content sections grouped by page
 */
function getContentSectionsByPage(): array {
    $pdo = getDatabase();
    $stmt = $pdo->query('SELECT * FROM content_sections ORDER BY page, sort_order');
    $sections = $stmt->fetchAll();

    $grouped = [];
    foreach ($sections as $section) {
        $grouped[$section['page']][] = $section;
    }

    return $grouped;
}

/**
 * Get all content sections
 */
function getContentSections(): array {
    $pdo = getDatabase();
    $stmt = $pdo->query('SELECT * FROM content_sections ORDER BY page, sort_order');
    return $stmt->fetchAll();
}

/**
 * Get a content section by code
 */
function getContentSection(string $code): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM content_sections WHERE code = ?');
    $stmt->execute([$code]);
    return $stmt->fetch() ?: null;
}

/**
 * Get content blocks for a section
 */
function getContentBlocks(string $sectionCode, bool $activeOnly = false): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM content_blocks WHERE section_code = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sectionCode]);
    return $stmt->fetchAll();
}

/**
 * Get a content block by ID
 */
function getContentBlock(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM content_blocks WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Create a content block
 */
function createContentBlock(string $sectionCode, array $data): ?int {
    $section = getContentSection($sectionCode);
    if (!$section) {
        return null;
    }

    // Validate image requirement
    if ($section['image_mode'] === IMAGE_REQUIRED && empty($data['image_filename'])) {
        return null;
    }

    // Check max blocks limit
    if ($section['max_blocks']) {
        $currentCount = count(getContentBlocks($sectionCode));
        if ($currentCount >= $section['max_blocks']) {
            return null;
        }
    }

    $pdo = getDatabase();

    // Get next position
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM content_blocks WHERE section_code = ?');
    $stmt->execute([$sectionCode]);
    $nextPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        INSERT INTO content_blocks (section_code, title, description, image_filename, image_alt, link_url, link_text, position, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $success = $stmt->execute([
        $sectionCode,
        $data['title'] ?? null,
        $data['description'] ?? null,
        $data['image_filename'] ?? null,
        $data['image_alt'] ?? null,
        $data['link_url'] ?? null,
        $data['link_text'] ?? null,
        $data['position'] ?? $nextPosition,
        $data['is_active'] ?? 1
    ]);

    return $success ? (int)$pdo->lastInsertId() : null;
}

/**
 * Update a content block
 */
function updateContentBlock(int $id, array $data): bool {
    $block = getContentBlock($id);
    if (!$block) {
        return false;
    }

    $section = getContentSection($block['section_code']);
    if (!$section) {
        return false;
    }

    // Validate image requirement
    $imageFilename = $data['image_filename'] ?? $block['image_filename'];
    if ($section['image_mode'] === IMAGE_REQUIRED && empty($imageFilename)) {
        return false;
    }

    // Clear image if forbidden
    if ($section['image_mode'] === IMAGE_FORBIDDEN) {
        $imageFilename = null;
    }

    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE content_blocks SET
            title = ?,
            description = ?,
            image_filename = ?,
            image_alt = ?,
            link_url = ?,
            link_text = ?,
            position = ?,
            is_active = ?
        WHERE id = ?
    ');

    return $stmt->execute([
        $data['title'] ?? $block['title'],
        $data['description'] ?? $block['description'],
        $imageFilename,
        $data['image_alt'] ?? $block['image_alt'],
        $data['link_url'] ?? $block['link_url'],
        $data['link_text'] ?? $block['link_text'],
        $data['position'] ?? $block['position'],
        $data['is_active'] ?? $block['is_active'],
        $id
    ]);
}

/**
 * Delete a content block
 */
function deleteContentBlock(int $id): bool {
    $block = getContentBlock($id);
    if (!$block) {
        return false;
    }

    // Delete associated image file if exists
    if (!empty($block['image_filename']) && strpos($block['image_filename'], 'uploads/') === 0) {
        $filePath = __DIR__ . '/../' . $block['image_filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $pdo = getDatabase();
    $stmt = $pdo->prepare('DELETE FROM content_blocks WHERE id = ?');
    return $stmt->execute([$id]);
}

/**
 * Reorder content blocks
 */
function reorderContentBlocks(string $sectionCode, array $blockIds): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_blocks SET position = ? WHERE id = ? AND section_code = ?');

    $position = 1;
    foreach ($blockIds as $id) {
        $stmt->execute([$position, $id, $sectionCode]);
        $position++;
    }

    return true;
}

/**
 * Handle content block image upload
 */
function handleContentBlockImageUpload(array $file, int $blockId): array {
    $block = getContentBlock($blockId);
    if (!$block) {
        return ['valid' => false, 'message' => 'Bloc de contenu introuvable.'];
    }

    $section = getContentSection($block['section_code']);
    if (!$section || $section['image_mode'] === IMAGE_FORBIDDEN) {
        return ['valid' => false, 'message' => 'Les images ne sont pas autorisées pour cette section.'];
    }

    // Validate file
    $validation = validateUploadedFile($file);
    if (!$validation['valid']) {
        return $validation;
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFilename = 'content_' . $blockId . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $newFilename;

    // Delete old image if exists
    if (!empty($block['image_filename']) && strpos($block['image_filename'], 'uploads/') === 0) {
        $oldPath = __DIR__ . '/../' . $block['image_filename'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['valid' => false, 'message' => 'Erreur lors du téléchargement du fichier.'];
    }

    // Update block with new image
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_blocks SET image_filename = ? WHERE id = ?');
    $stmt->execute(['uploads/' . $newFilename, $blockId]);

    return ['valid' => true, 'message' => 'Image téléchargée avec succès.', 'filename' => 'uploads/' . $newFilename];
}

/**
 * Handle new content block image upload (before block is created)
 */
function handleNewContentImageUpload(array $file, string $sectionCode): array {
    $section = getContentSection($sectionCode);
    if (!$section || $section['image_mode'] === IMAGE_FORBIDDEN) {
        return ['valid' => false, 'message' => 'Les images ne sont pas autorisées pour cette section.'];
    }

    // Validate file
    $validation = validateUploadedFile($file);
    if (!$validation['valid']) {
        return $validation;
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFilename = 'content_new_' . time() . '_' . uniqid() . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $newFilename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['valid' => false, 'message' => 'Erreur lors du téléchargement du fichier.'];
    }

    return ['valid' => true, 'message' => 'Image téléchargée avec succès.', 'filename' => 'uploads/' . $newFilename];
}

/**
 * Get page names for display
 */
function getContentPageNames(): array {
    return [
        'home' => 'Accueil',
        'services' => 'Services',
        'activities' => 'À découvrir',
        'contact' => 'Contact'
    ];
}

/**
 * Get image mode label
 */
function getImageModeLabel(string $mode): string {
    $labels = [
        IMAGE_REQUIRED => 'Image obligatoire',
        IMAGE_OPTIONAL => 'Image optionnelle',
        IMAGE_FORBIDDEN => 'Texte uniquement'
    ];
    return $labels[$mode] ?? $mode;
}

/**
 * Get image mode badge class
 */
function getImageModeBadgeClass(string $mode): string {
    $classes = [
        IMAGE_REQUIRED => 'badge-required',
        IMAGE_OPTIONAL => 'badge-optional',
        IMAGE_FORBIDDEN => 'badge-text-only'
    ];
    return $classes[$mode] ?? '';
}

/**
 * Migrate existing images to content blocks
 */
function migrateImagesToContentBlocks(): array {
    $pdo = getDatabase();
    $migrated = 0;
    $errors = [];

    // Map old sections to new section codes (only hero sections remain fixed)
    $sectionMap = [
        'home' => [
            1 => ['section' => 'home_hero', 'position' => 1],
            2 => ['section' => 'home_hero', 'position' => 2],
            3 => ['section' => 'home_hero', 'position' => 3],
        ],
        'services' => [
            1 => ['section' => 'services_hero', 'position' => 1],
        ],
        'activities' => [
            1 => ['section' => 'activities_hero', 'position' => 1],
        ],
        'contact' => [
            1 => ['section' => 'contact_hero', 'position' => 1],
        ],
    ];

    try {
        // Check if old images table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'images'");
        if (!$stmt->fetch()) {
            return ['migrated' => 0, 'errors' => ['Table images non trouvée.']];
        }

        // Get existing images
        $images = $pdo->query('SELECT * FROM images ORDER BY section, position')->fetchAll();

        foreach ($images as $image) {
            $section = $image['section'];
            $position = $image['position'];

            if (isset($sectionMap[$section][$position])) {
                $mapping = $sectionMap[$section][$position];

                // Check if block already exists
                $existingBlocks = getContentBlocks($mapping['section']);
                $exists = false;
                foreach ($existingBlocks as $block) {
                    if ($block['image_filename'] === $image['filename']) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $blockId = createContentBlock($mapping['section'], [
                        'title' => $image['title'] ?? '',
                        'description' => '',
                        'image_filename' => $image['filename'],
                        'image_alt' => $image['alt_text'] ?? '',
                        'position' => $mapping['position']
                    ]);

                    if ($blockId) {
                        $migrated++;
                    } else {
                        $errors[] = "Impossible de migrer l'image {$image['id']}";
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $errors[] = 'Erreur de base de données: ' . $e->getMessage();
    }

    return ['migrated' => $migrated, 'errors' => $errors];
}

// =====================================================
// SECTION OVERLAY TEXT FUNCTIONS
// =====================================================

/**
 * Check if section has overlay text capability
 */
function sectionHasOverlay(string $sectionCode): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT has_overlay FROM content_sections WHERE code = ?');
    $stmt->execute([$sectionCode]);
    $result = $stmt->fetchColumn();
    return $result == 1;
}

/**
 * Enable overlay for a section
 */
function enableSectionOverlay(string $sectionCode): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET has_overlay = 1 WHERE code = ?');
    return $stmt->execute([$sectionCode]);
}

/**
 * Get section overlay texts (default language - French)
 */
function getSectionOverlay(string $sectionCode): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT overlay_subtitle, overlay_title, overlay_description FROM content_sections WHERE code = ?');
    $stmt->execute([$sectionCode]);
    $result = $stmt->fetch();

    return [
        'subtitle' => $result['overlay_subtitle'] ?? '',
        'title' => $result['overlay_title'] ?? '',
        'description' => $result['overlay_description'] ?? ''
    ];
}

/**
 * Get section overlay texts with all translations
 */
function getSectionOverlayWithTranslations(string $sectionCode): array {
    $overlay = getSectionOverlay($sectionCode);

    // Get translations
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT language_code, overlay_subtitle, overlay_title, overlay_description FROM section_overlay_translations WHERE section_code = ?');
    $stmt->execute([$sectionCode]);
    $translations = $stmt->fetchAll();

    $overlay['translations'] = [];
    foreach ($translations as $trans) {
        $overlay['translations'][$trans['language_code']] = [
            'subtitle' => $trans['overlay_subtitle'] ?? '',
            'title' => $trans['overlay_title'] ?? '',
            'description' => $trans['overlay_description'] ?? ''
        ];
    }

    return $overlay;
}

/**
 * Save section overlay texts (French - default)
 */
function saveSectionOverlay(string $sectionCode, string $subtitle, string $title, string $description): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE content_sections
        SET overlay_subtitle = ?, overlay_title = ?, overlay_description = ?, has_overlay = 1
        WHERE code = ?
    ');
    return $stmt->execute([trim($subtitle), trim($title), trim($description), $sectionCode]);
}

/**
 * Save section overlay translations
 */
function saveSectionOverlayTranslations(string $sectionCode, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $texts) {
        if (!in_array($langCode, getSupportedLanguages()) || $langCode === 'fr') {
            continue;
        }

        $subtitle = trim($texts['subtitle'] ?? '');
        $title = trim($texts['title'] ?? '');
        $description = trim($texts['description'] ?? '');

        // Skip if all empty
        if (empty($subtitle) && empty($title) && empty($description)) {
            // Delete existing translation if all fields are empty
            $stmt = $pdo->prepare('DELETE FROM section_overlay_translations WHERE section_code = ? AND language_code = ?');
            $stmt->execute([$sectionCode, $langCode]);
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO section_overlay_translations (section_code, language_code, overlay_subtitle, overlay_title, overlay_description)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    overlay_subtitle = VALUES(overlay_subtitle),
                    overlay_title = VALUES(overlay_title),
                    overlay_description = VALUES(overlay_description)
            ');
            $success = $stmt->execute([$sectionCode, $langCode, $subtitle, $title, $description]) && $success;
        } catch (PDOException $e) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Get section overlay for a specific language (with French fallback)
 */
function getSectionOverlayForLanguage(string $sectionCode, string $langCode = 'fr'): array {
    // Always get French as base/fallback
    $base = getSectionOverlay($sectionCode);

    if ($langCode === 'fr') {
        return $base;
    }

    // Try to get translation
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT overlay_subtitle, overlay_title, overlay_description FROM section_overlay_translations WHERE section_code = ? AND language_code = ?');
    $stmt->execute([$sectionCode, $langCode]);
    $trans = $stmt->fetch();

    if ($trans) {
        return [
            'subtitle' => !empty($trans['overlay_subtitle']) ? $trans['overlay_subtitle'] : $base['subtitle'],
            'title' => !empty($trans['overlay_title']) ? $trans['overlay_title'] : $base['title'],
            'description' => !empty($trans['overlay_description']) ? $trans['overlay_description'] : $base['description']
        ];
    }

    return $base;
}

// =====================================================
// ICON LIBRARY (Centralized, Reusable)
// =====================================================

/**
 * Get all available icons for feature indicators
 * Centralized icon library - reusable across all sections
 */
function getAvailableIcons(): array {
    return [
        // Nature & Outdoor
        'garden' => [
            'name' => 'Jardin',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>'
        ],
        'tree' => [
            'name' => 'Arbre',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22v-7"/><path d="M9 22h6"/><path d="M12 15L8 9h8l-4 6z"/><path d="M12 9L8 3h8l-4 6z"/></svg>'
        ],
        'sun' => [
            'name' => 'Soleil',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>'
        ],
        'flower' => [
            'name' => 'Fleur',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/></svg>'
        ],
        'mountain' => [
            'name' => 'Montagne',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3l4 8 5-5 5 15H2L8 3z"/></svg>'
        ],
        'countryside' => [
            'name' => 'Campagne',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>'
        ],

        // Amenities
        'terrace' => [
            'name' => 'Terrasse',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 11h1a3 3 0 0 1 0 6h-1"/><path d="M2 11h14v7a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-7z"/><path d="M6 7v4"/><path d="M10 7v4"/><path d="M14 7v4"/></svg>'
        ],
        'lounge' => [
            'name' => 'Salon',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>'
        ],
        'parking' => [
            'name' => 'Parking',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2" ry="2"/><path d="M16 8h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1"/></svg>'
        ],
        'wifi' => [
            'name' => 'WiFi',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1"/></svg>'
        ],
        'pool' => [
            'name' => 'Piscine',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20"/><path d="M2 16c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/><path d="M2 20c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/></svg>'
        ],
        'spa' => [
            'name' => 'Spa',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2c-4 4-6 8-6 10a6 6 0 1 0 12 0c0-2-2-6-6-10z"/></svg>'
        ],
        'gym' => [
            'name' => 'Fitness',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6.5 6.5a2 2 0 0 1 3 0L12 9l2.5-2.5a2 2 0 0 1 3 0l2 2a2 2 0 0 1 0 3L17 14l2.5 2.5a2 2 0 0 1 0 3l-2 2a2 2 0 0 1-3 0L12 19l-2.5 2.5a2 2 0 0 1-3 0l-2-2a2 2 0 0 1 0-3L7 14l-2.5-2.5a2 2 0 0 1 0-3l2-2z"/></svg>'
        ],
        'aircon' => [
            'name' => 'Climatisation',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="8" rx="2"/><path d="M6 12v4"/><path d="M10 12v6"/><path d="M14 12v4"/><path d="M18 12v6"/></svg>'
        ],

        // Dining
        'restaurant' => [
            'name' => 'Restaurant',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>'
        ],
        'bar' => [
            'name' => 'Bar',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 22h8"/><path d="M12 11v11"/><path d="M5 3l7 8 7-8"/></svg>'
        ],
        'coffee' => [
            'name' => 'Café',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>'
        ],
        'wine' => [
            'name' => 'Vin',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 22h8"/><path d="M12 15v7"/><path d="M5 3h14l-1.5 9a5.5 5.5 0 0 1-11 0L5 3z"/></svg>'
        ],
        'room-service' => [
            'name' => 'Room Service',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15h16a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1z"/><path d="M12 4a6 6 0 0 1 6 6v5H6v-5a6 6 0 0 1 6-6z"/></svg>'
        ],

        // Comfort
        'bed' => [
            'name' => 'Lit',
            'category' => 'comfort',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4v16"/><path d="M22 4v16"/><path d="M2 12h20"/><path d="M2 20h20"/><rect x="6" y="8" width="12" height="4" rx="1"/></svg>'
        ],
        'fireplace' => [
            'name' => 'Cheminée',
            'category' => 'comfort',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 17c-2 0-3-2-3-4 0-3 3-5 3-7 0 2 3 4 3 7 0 2-1 4-3 4z"/></svg>'
        ],

        // Location
        'map-pin' => [
            'name' => 'Localisation',
            'category' => 'location',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'
        ],
        'vineyard' => [
            'name' => 'Vignoble',
            'category' => 'location',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="6" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="12" r="3"/><path d="M12 9v13"/><path d="M9 22h6"/></svg>'
        ],

        // Activities
        'bike' => [
            'name' => 'Vélo',
            'category' => 'activities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/></svg>'
        ],
        'hiking' => [
            'name' => 'Randonnée',
            'category' => 'activities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13" cy="4" r="2"/><path d="M4 17l3-3 3 3"/><path d="M15 21l-3-9 4-3"/><path d="M8 14l3-3"/></svg>'
        ],
        'golf' => [
            'name' => 'Golf',
            'category' => 'activities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 18V4l6 4-6 4"/><circle cx="12" cy="20" r="2"/></svg>'
        ],
        'tennis' => [
            'name' => 'Tennis',
            'category' => 'activities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20"/><path d="M12 2a14.5 14.5 0 0 1 0 20"/></svg>'
        ],

        // Family & Accessibility
        'family' => [
            'name' => 'Famille',
            'category' => 'family',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="2"/><circle cx="15" cy="7" r="2"/><path d="M9 11v10"/><path d="M15 11v10"/><path d="M5 21h4"/><path d="M15 21h4"/></svg>'
        ],
        'pets' => [
            'name' => 'Animaux acceptés',
            'category' => 'family',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="4" r="2"/><circle cx="18" cy="8" r="2"/><circle cx="4" cy="8" r="2"/><path d="M9 10c0 1 1 2 2 2s2-1 2-2"/><ellipse cx="11" cy="17" rx="5" ry="6"/></svg>'
        ],
        'accessible' => [
            'name' => 'Accessible',
            'category' => 'family',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="4" r="2"/><path d="M16 22l-2-5H7"/><path d="M12 12v-2l4 1"/><circle cx="9" cy="19" r="3"/></svg>'
        ]
    ];
}

/**
 * Get icon categories
 */
function getIconCategories(): array {
    return [
        'nature' => 'Nature & Extérieur',
        'amenities' => 'Équipements',
        'dining' => 'Restauration',
        'comfort' => 'Confort',
        'location' => 'Localisation',
        'activities' => 'Activités',
        'family' => 'Famille & Accessibilité'
    ];
}

/**
 * Get icon by code
 */
function getIcon(string $code): ?array {
    $icons = getAvailableIcons();
    return $icons[$code] ?? null;
}

/**
 * Get icon SVG by code
 */
function getIconSvg(string $code): string {
    $icon = getIcon($code);
    return $icon['svg'] ?? '';
}

// =====================================================
// SECTION FEATURES (Reusable CRUD Functions)
// =====================================================

/**
 * Check if section supports features
 */
function sectionHasFeatures(string $sectionCode): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT has_features FROM content_sections WHERE code = ?');
    $stmt->execute([$sectionCode]);
    $result = $stmt->fetchColumn();
    return $result == 1;
}

/**
 * Check if a section supports services
 */
function sectionHasServices(string $sectionCode): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT has_services FROM content_sections WHERE code = ?');
    $stmt->execute([$sectionCode]);
    $result = $stmt->fetchColumn();
    return $result == 1;
}

/**
 * Enable features for a section
 */
function enableSectionFeatures(string $sectionCode): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET has_features = 1 WHERE code = ?');
    return $stmt->execute([$sectionCode]);
}

/**
 * Get available background color options for sections
 * Returns an array of color options with their CSS variable and display info
 * Preview colors are dynamically fetched from the current theme settings
 *
 * Groups:
 * - 'theme': Colors linked to the site theme (dynamic)
 * - 'neutral': Hardcoded neutral colors (static)
 */
function getSectionBackgroundOptions(): array {
    // Get current theme colors (dynamically from database)
    $theme = getThemeSettings();

    return [
        // Theme-driven colors (dynamic)
        'cream' => [
            'label' => 'Fond crème',
            'css_var' => '--color-cream',
            'css_class' => 'section-cream',
            'text_light' => false,
            'theme_key' => 'color_cream',
            'preview' => $theme['color_cream'],
            'group' => 'theme'
        ],
        'white' => [
            'label' => 'Fond blanc',
            'css_var' => '--color-white',
            'css_class' => 'section-white',
            'text_light' => false,
            'theme_key' => null,
            'preview' => '#FFFFFF',
            'group' => 'theme'
        ],
        'beige' => [
            'label' => 'Fond beige',
            'css_var' => '--color-beige',
            'css_class' => 'section-beige',
            'text_light' => false,
            'theme_key' => 'color_beige',
            'preview' => $theme['color_beige'],
            'group' => 'theme'
        ],
        'primary' => [
            'label' => 'Couleur primaire',
            'css_var' => '--color-primary',
            'css_class' => 'section-primary',
            'text_light' => true,
            'theme_key' => 'color_primary',
            'preview' => $theme['color_primary'],
            'group' => 'theme'
        ],
        'primary-dark' => [
            'label' => 'Primaire foncé',
            'css_var' => '--color-primary-dark',
            'css_class' => 'section-primary-dark',
            'text_light' => true,
            'theme_key' => 'color_primary_dark',
            'preview' => $theme['color_primary_dark'],
            'group' => 'theme'
        ],
        'accent' => [
            'label' => 'Couleur accent',
            'css_var' => '--color-accent',
            'css_class' => 'section-accent',
            'text_light' => true,
            'theme_key' => 'color_accent',
            'preview' => $theme['color_accent'],
            'group' => 'theme'
        ],

        // Neutral hardcoded colors (static)
        'neutral-gray' => [
            'label' => 'Gris clair',
            'css_var' => null,
            'css_class' => 'section-neutral-gray',
            'text_light' => false,
            'theme_key' => null,
            'preview' => '#F5F5F5',
            'group' => 'neutral'
        ],
        'neutral-blue' => [
            'label' => 'Bleu gris',
            'css_var' => null,
            'css_class' => 'section-neutral-blue',
            'text_light' => false,
            'theme_key' => null,
            'preview' => '#F0F4F8',
            'group' => 'neutral'
        ],
        'neutral-sand' => [
            'label' => 'Sable clair',
            'css_var' => null,
            'css_class' => 'section-neutral-sand',
            'text_light' => false,
            'theme_key' => null,
            'preview' => '#F8F6F1',
            'group' => 'neutral'
        ],
    ];
}

/**
 * Get the CSS class for a section background color
 */
function getSectionBackgroundClass(string $colorKey): string {
    $options = getSectionBackgroundOptions();
    return $options[$colorKey]['css_class'] ?? 'section-cream';
}

/**
 * Check if a background color requires light text
 */
function sectionBackgroundNeedsLightText(string $colorKey): bool {
    $options = getSectionBackgroundOptions();
    return $options[$colorKey]['text_light'] ?? false;
}

/**
 * Get the background color for a section
 */
function getSectionBackgroundColor(string $sectionCode): string {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT background_color FROM content_sections WHERE code = ?');
    $stmt->execute([$sectionCode]);
    return $stmt->fetchColumn() ?: 'cream';
}

/**
 * Set the background color for a section
 */
function setSectionBackgroundColor(string $sectionCode, string $colorKey): bool {
    $options = getSectionBackgroundOptions();
    if (!isset($options[$colorKey])) {
        $colorKey = 'cream'; // Fallback to default
    }

    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET background_color = ? WHERE code = ?');
    return $stmt->execute([$colorKey, $sectionCode]);
}

/**
 * Get available image position options for sections
 * Used by section types that have image + text layouts
 */
function getImagePositionOptions(): array {
    return [
        'left' => [
            'label' => 'Image à gauche',
            'css_class' => 'image-left',
            'icon' => 'layout-left'
        ],
        'right' => [
            'label' => 'Image à droite',
            'css_class' => 'image-right',
            'icon' => 'layout-right'
        ]
    ];
}

/**
 * Get section types that support image position setting
 */
function getSectionTypesWithImagePosition(): array {
    return ['services_indicators', 'services_checklist'];
}

/**
 * Check if a section type supports image position setting
 */
function sectionSupportsImagePosition(string $templateType): bool {
    return in_array($templateType, getSectionTypesWithImagePosition());
}

/**
 * Get the image position for a section
 */
function getSectionImagePosition(string $sectionCode): string {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT image_position FROM content_sections WHERE code = ?');
    $stmt->execute([$sectionCode]);
    return $stmt->fetchColumn() ?: 'left';
}

/**
 * Set the image position for a section
 */
function setSectionImagePosition(string $sectionCode, string $position): bool {
    $options = getImagePositionOptions();
    if (!isset($options[$position])) {
        $position = 'left'; // Fallback to default
    }

    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET image_position = ? WHERE code = ?');
    return $stmt->execute([$position, $sectionCode]);
}

/**
 * Get the CSS class for image position
 */
function getImagePositionClass(string $position): string {
    $options = getImagePositionOptions();
    return $options[$position]['css_class'] ?? 'image-left';
}

/**
 * Get available text alignment options
 */
function getTextAlignmentOptions(): array {
    return [
        'center' => [
            'label' => 'Centré',
            'css_class' => 'text-center',
            'icon' => 'align-center'
        ],
        'left' => [
            'label' => 'Gauche',
            'css_class' => 'text-left',
            'icon' => 'align-left'
        ],
        'right' => [
            'label' => 'Droite',
            'css_class' => 'text-right',
            'icon' => 'align-right'
        ]
    ];
}

/**
 * Get section types that support text alignment setting
 */
function getSectionTypesWithTextAlignment(): array {
    return ['presentation_hero'];
}

/**
 * Check if a section type supports text alignment setting
 */
function sectionSupportsTextAlignment(string $templateType): bool {
    return in_array($templateType, getSectionTypesWithTextAlignment());
}

/**
 * Get the text alignment for a section
 */
function getSectionTextAlignment(string $sectionCode): string {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT text_alignment FROM content_sections WHERE code = ?');
    $stmt->execute([$sectionCode]);
    return $stmt->fetchColumn() ?: 'center';
}

/**
 * Set the text alignment for a section
 */
function setSectionTextAlignment(string $sectionCode, string $alignment): bool {
    $options = getTextAlignmentOptions();
    if (!isset($options[$alignment])) {
        $alignment = 'center'; // Fallback to default
    }

    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET text_alignment = ? WHERE code = ?');
    return $stmt->execute([$alignment, $sectionCode]);
}

/**
 * Get all features for a section
 */
function getSectionFeatures(string $sectionCode, bool $activeOnly = true): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM section_features WHERE section_code = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sectionCode]);
    return $stmt->fetchAll();
}

/**
 * Get features with all translations
 */
function getSectionFeaturesWithTranslations(string $sectionCode, bool $activeOnly = true): array {
    $features = getSectionFeatures($sectionCode, $activeOnly);
    $pdo = getDatabase();

    foreach ($features as &$feature) {
        $stmt = $pdo->prepare('SELECT language_code, label FROM section_feature_translations WHERE feature_id = ?');
        $stmt->execute([$feature['id']]);
        $translations = $stmt->fetchAll();

        $feature['translations'] = [];
        foreach ($translations as $trans) {
            $feature['translations'][$trans['language_code']] = $trans['label'];
        }

        // Add icon data
        $icon = getIcon($feature['icon_code']);
        $feature['icon_svg'] = $icon['svg'] ?? '';
        $feature['icon_name'] = $icon['name'] ?? $feature['icon_code'];
    }

    return $features;
}

/**
 * Get a single feature by ID
 */
function getSectionFeature(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM section_features WHERE id = ?');
    $stmt->execute([$id]);
    $feature = $stmt->fetch();

    if ($feature) {
        // Get translations
        $stmt = $pdo->prepare('SELECT language_code, label FROM section_feature_translations WHERE feature_id = ?');
        $stmt->execute([$id]);
        $translations = $stmt->fetchAll();

        $feature['translations'] = [];
        foreach ($translations as $trans) {
            $feature['translations'][$trans['language_code']] = $trans['label'];
        }
    }

    return $feature ?: null;
}

/**
 * Create a new feature
 */
function createSectionFeature(string $sectionCode, string $iconCode, string $label): ?int {
    $pdo = getDatabase();

    // Get next position
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM section_features WHERE section_code = ?');
    $stmt->execute([$sectionCode]);
    $nextPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        INSERT INTO section_features (section_code, icon_code, label, position)
        VALUES (?, ?, ?, ?)
    ');

    $success = $stmt->execute([$sectionCode, $iconCode, trim($label), $nextPosition]);
    return $success ? (int)$pdo->lastInsertId() : null;
}

/**
 * Update a feature
 */
function updateSectionFeature(int $id, string $iconCode, string $label, bool $isActive = true): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE section_features
        SET icon_code = ?, label = ?, is_active = ?
        WHERE id = ?
    ');
    return $stmt->execute([$iconCode, trim($label), $isActive ? 1 : 0, $id]);
}

/**
 * Delete a feature
 */
function deleteSectionFeature(int $id): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('DELETE FROM section_features WHERE id = ?');
    return $stmt->execute([$id]);
}

/**
 * Reorder features
 */
function reorderSectionFeatures(string $sectionCode, array $featureIds): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE section_features SET position = ? WHERE id = ? AND section_code = ?');

    $position = 1;
    foreach ($featureIds as $id) {
        $stmt->execute([$position, $id, $sectionCode]);
        $position++;
    }

    return true;
}

/**
 * Save feature translations
 */
function saveSectionFeatureTranslations(int $featureId, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $label) {
        if (!in_array($langCode, getSupportedLanguages()) || $langCode === 'fr') {
            continue;
        }

        $label = trim($label);
        if (empty($label)) {
            // Delete translation if empty
            $stmt = $pdo->prepare('DELETE FROM section_feature_translations WHERE feature_id = ? AND language_code = ?');
            $stmt->execute([$featureId, $langCode]);
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO section_feature_translations (feature_id, language_code, label)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE label = VALUES(label)
            ');
            $success = $stmt->execute([$featureId, $langCode, $label]) && $success;
        } catch (PDOException $e) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Get feature label for a specific language
 */
function getFeatureLabelForLanguage(array $feature, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $feature['label'];
    }

    return $feature['translations'][$langCode] ?? $feature['label'];
}

/**
 * Seed default features for a section
 */
function seedSectionFeatures(string $sectionCode, array $defaultFeatures): void {
    $pdo = getDatabase();

    // Check if features already exist
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM section_features WHERE section_code = ?');
    $stmt->execute([$sectionCode]);
    if ($stmt->fetchColumn() > 0) {
        return; // Already seeded
    }

    foreach ($defaultFeatures as $feature) {
        $featureId = createSectionFeature($sectionCode, $feature['icon'], $feature['label']);
        if ($featureId && isset($feature['translations'])) {
            saveSectionFeatureTranslations($featureId, $feature['translations']);
        }
    }
}

// =====================================================
// SECTION SERVICES FUNCTIONS
// Reusable service cards with icon + label + description
// =====================================================

/**
 * Get all services for a section
 */
function getSectionServices(string $sectionCode, bool $activeOnly = true): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM section_services WHERE section_code = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sectionCode]);
    return $stmt->fetchAll();
}

/**
 * Get services with all translations
 */
function getSectionServicesWithTranslations(string $sectionCode, bool $activeOnly = true): array {
    $services = getSectionServices($sectionCode, $activeOnly);
    $pdo = getDatabase();

    foreach ($services as &$service) {
        $stmt = $pdo->prepare('SELECT language_code, label, description FROM section_service_translations WHERE service_id = ?');
        $stmt->execute([$service['id']]);
        $translations = $stmt->fetchAll();

        $service['translations'] = [];
        foreach ($translations as $trans) {
            $service['translations'][$trans['language_code']] = [
                'label' => $trans['label'],
                'description' => $trans['description']
            ];
        }

        // Add icon data
        $icon = getIcon($service['icon_code']);
        $service['icon_svg'] = $icon['svg'] ?? '';
        $service['icon_name'] = $icon['name'] ?? $service['icon_code'];
    }

    return $services;
}

/**
 * Get a single service by ID
 */
function getSectionService(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM section_services WHERE id = ?');
    $stmt->execute([$id]);
    $service = $stmt->fetch();

    if ($service) {
        // Get translations
        $stmt = $pdo->prepare('SELECT language_code, label, description FROM section_service_translations WHERE service_id = ?');
        $stmt->execute([$id]);
        $translations = $stmt->fetchAll();

        $service['translations'] = [];
        foreach ($translations as $trans) {
            $service['translations'][$trans['language_code']] = [
                'label' => $trans['label'],
                'description' => $trans['description']
            ];
        }
    }

    return $service ?: null;
}

/**
 * Create a new service
 */
function createSectionService(string $sectionCode, string $iconCode, string $label, string $description = ''): ?int {
    $pdo = getDatabase();

    // Get next position
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM section_services WHERE section_code = ?');
    $stmt->execute([$sectionCode]);
    $nextPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        INSERT INTO section_services (section_code, icon_code, label, description, position)
        VALUES (?, ?, ?, ?, ?)
    ');

    $success = $stmt->execute([$sectionCode, $iconCode, trim($label), trim($description), $nextPosition]);
    return $success ? (int)$pdo->lastInsertId() : null;
}

/**
 * Update a service
 */
function updateSectionService(int $id, string $iconCode, string $label, string $description = '', bool $isActive = true): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE section_services
        SET icon_code = ?, label = ?, description = ?, is_active = ?
        WHERE id = ?
    ');
    return $stmt->execute([$iconCode, trim($label), trim($description), $isActive ? 1 : 0, $id]);
}

/**
 * Delete a service
 */
function deleteSectionService(int $id): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('DELETE FROM section_services WHERE id = ?');
    return $stmt->execute([$id]);
}

/**
 * Reorder services
 */
function reorderSectionServices(string $sectionCode, array $serviceIds): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE section_services SET position = ? WHERE id = ? AND section_code = ?');

    $position = 1;
    foreach ($serviceIds as $id) {
        $stmt->execute([$position, $id, $sectionCode]);
        $position++;
    }

    return true;
}

/**
 * Save service translations
 */
function saveSectionServiceTranslations(int $serviceId, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $data) {
        if (!in_array($langCode, getSupportedLanguages()) || $langCode === 'fr') {
            continue;
        }

        $label = is_array($data) ? trim($data['label'] ?? '') : trim($data);
        $description = is_array($data) ? trim($data['description'] ?? '') : '';

        if (empty($label)) {
            // Delete translation if empty
            $stmt = $pdo->prepare('DELETE FROM section_service_translations WHERE service_id = ? AND language_code = ?');
            $stmt->execute([$serviceId, $langCode]);
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO section_service_translations (service_id, language_code, label, description)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description)
            ');
            $success = $stmt->execute([$serviceId, $langCode, $label, $description]) && $success;
        } catch (PDOException $e) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Get service label for a specific language
 */
function getServiceLabelForLanguage(array $service, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $service['label'];
    }

    return $service['translations'][$langCode]['label'] ?? $service['label'];
}

/**
 * Get service description for a specific language
 */
function getServiceDescriptionForLanguage(array $service, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $service['description'] ?? '';
    }

    return $service['translations'][$langCode]['description'] ?? ($service['description'] ?? '');
}

// =====================================================
// SECTION GALLERY ITEMS (for image gallery sections)
// =====================================================

/**
 * Get gallery items for a section
 */
function getSectionGalleryItems(string $sectionCode, bool $activeOnly = true): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM section_gallery_items WHERE section_code = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sectionCode]);
    return $stmt->fetchAll();
}

/**
 * Get gallery items with all translations
 */
function getSectionGalleryItemsWithTranslations(string $sectionCode, bool $activeOnly = true): array {
    $items = getSectionGalleryItems($sectionCode, $activeOnly);
    $pdo = getDatabase();

    foreach ($items as &$item) {
        $stmt = $pdo->prepare('SELECT language_code, title, description FROM section_gallery_item_translations WHERE item_id = ?');
        $stmt->execute([$item['id']]);
        $translations = $stmt->fetchAll();

        $item['translations'] = [];
        foreach ($translations as $trans) {
            $item['translations'][$trans['language_code']] = [
                'title' => $trans['title'],
                'description' => $trans['description']
            ];
        }
    }

    return $items;
}

/**
 * Get a single gallery item
 */
function getSectionGalleryItem(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM section_gallery_items WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item) {
        // Get translations
        $stmt = $pdo->prepare('SELECT language_code, title, description FROM section_gallery_item_translations WHERE item_id = ?');
        $stmt->execute([$id]);
        $translations = $stmt->fetchAll();

        $item['translations'] = [];
        foreach ($translations as $trans) {
            $item['translations'][$trans['language_code']] = [
                'title' => $trans['title'],
                'description' => $trans['description']
            ];
        }
    }

    return $item ?: null;
}

/**
 * Create a gallery item
 */
function createSectionGalleryItem(string $sectionCode, string $imageFilename, string $title, string $description = '', string $imageAlt = ''): ?int {
    $pdo = getDatabase();

    // Get next position
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM section_gallery_items WHERE section_code = ?');
    $stmt->execute([$sectionCode]);
    $nextPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        INSERT INTO section_gallery_items (section_code, image_filename, image_alt, title, description, position)
        VALUES (?, ?, ?, ?, ?, ?)
    ');

    $success = $stmt->execute([$sectionCode, $imageFilename, $imageAlt, trim($title), trim($description), $nextPosition]);
    return $success ? (int)$pdo->lastInsertId() : null;
}

/**
 * Update a gallery item
 */
function updateSectionGalleryItem(int $id, string $title, string $description = '', string $imageAlt = '', bool $isActive = true, ?string $imageFilename = null): bool {
    $pdo = getDatabase();

    if ($imageFilename !== null) {
        $stmt = $pdo->prepare('
            UPDATE section_gallery_items
            SET title = ?, description = ?, image_alt = ?, is_active = ?, image_filename = ?
            WHERE id = ?
        ');
        return $stmt->execute([trim($title), trim($description), trim($imageAlt), $isActive ? 1 : 0, $imageFilename, $id]);
    } else {
        $stmt = $pdo->prepare('
            UPDATE section_gallery_items
            SET title = ?, description = ?, image_alt = ?, is_active = ?
            WHERE id = ?
        ');
        return $stmt->execute([trim($title), trim($description), trim($imageAlt), $isActive ? 1 : 0, $id]);
    }
}

/**
 * Delete a gallery item
 */
function deleteSectionGalleryItem(int $id): bool {
    $pdo = getDatabase();

    // Get item to delete image file
    $stmt = $pdo->prepare('SELECT image_filename FROM section_gallery_items WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item && !empty($item['image_filename']) && file_exists($item['image_filename'])) {
        @unlink($item['image_filename']);
    }

    $stmt = $pdo->prepare('DELETE FROM section_gallery_items WHERE id = ?');
    return $stmt->execute([$id]);
}

/**
 * Reorder gallery items
 */
function reorderSectionGalleryItems(string $sectionCode, array $itemIds): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE section_gallery_items SET position = ? WHERE id = ? AND section_code = ?');

    $position = 1;
    foreach ($itemIds as $id) {
        $stmt->execute([$position, $id, $sectionCode]);
        $position++;
    }

    return true;
}

/**
 * Save gallery item translations
 */
function saveSectionGalleryItemTranslations(int $itemId, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $data) {
        if (!in_array($langCode, getSupportedLanguages()) || $langCode === 'fr') {
            continue;
        }

        $title = is_array($data) ? trim($data['title'] ?? '') : trim($data);
        $description = is_array($data) ? trim($data['description'] ?? '') : '';

        if (empty($title)) {
            // Delete translation if empty
            $stmt = $pdo->prepare('DELETE FROM section_gallery_item_translations WHERE item_id = ? AND language_code = ?');
            $stmt->execute([$itemId, $langCode]);
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO section_gallery_item_translations (item_id, language_code, title, description)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)
            ');
            $success = $stmt->execute([$itemId, $langCode, $title, $description]) && $success;
        } catch (PDOException $e) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Get gallery item title for a specific language
 */
function getGalleryItemTitleForLanguage(array $item, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $item['title'];
    }

    return $item['translations'][$langCode]['title'] ?? $item['title'];
}

/**
 * Get gallery item description for a specific language
 */
function getGalleryItemDescriptionForLanguage(array $item, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $item['description'] ?? '';
    }

    return $item['translations'][$langCode]['description'] ?? ($item['description'] ?? '');
}

// =====================================================
// VAT / TAX CALCULATION SYSTEM
// =====================================================

/**
 * Default VAT rate in France for restaurant/hotel food service
 * Standard rate: 20%, Reduced (food service): 10%, Super-reduced: 5.5%
 * Hotel room service food/beverages consumed on-premises: 10%
 */
define('DEFAULT_VAT_RATE', 10.0);

/**
 * Get the default VAT rate (stored in settings or fallback to constant)
 * @return float VAT rate as percentage (e.g., 10.0 for 10%)
 */
function getDefaultVatRate(): float {
    return (float) getSetting('default_vat_rate', DEFAULT_VAT_RATE);
}

/**
 * Set the default VAT rate
 * @param float $rate VAT rate as percentage
 * @return bool Success status
 */
function setDefaultVatRate(float $rate): bool {
    if ($rate < 0 || $rate > 100) {
        return false;
    }
    return setSetting('default_vat_rate', (string) $rate);
}

/**
 * Get VAT rate for a specific category
 * Categories can have their own VAT rates (e.g., alcohol at 20%, food at 10%)
 * @param string $categoryCode Category code
 * @return float VAT rate as percentage
 */
function getCategoryVatRate(string $categoryCode): float {
    $categoryRate = getSetting('vat_rate_' . $categoryCode, null);
    if ($categoryRate !== null && $categoryRate !== '') {
        return (float) $categoryRate;
    }
    return getDefaultVatRate();
}

/**
 * Set VAT rate for a specific category
 * @param string $categoryCode Category code
 * @param float|null $rate VAT rate (null to use default)
 * @return bool Success status
 */
function setCategoryVatRate(string $categoryCode, ?float $rate): bool {
    if ($rate === null) {
        // Remove custom rate, will use default
        try {
            $pdo = getDatabase();
            $stmt = $pdo->prepare('DELETE FROM settings WHERE setting_key = ?');
            return $stmt->execute(['vat_rate_' . $categoryCode]);
        } catch (PDOException $e) {
            return false;
        }
    }
    if ($rate < 0 || $rate > 100) {
        return false;
    }
    return setSetting('vat_rate_' . $categoryCode, (string) $rate);
}

/**
 * Get all VAT rates (default + per category)
 * @return array Array with 'default' and category codes as keys
 */
function getAllVatRates(): array {
    $rates = ['default' => getDefaultVatRate()];
    $categories = getRoomServiceCategories();

    foreach ($categories as $code => $name) {
        $customRate = getSetting('vat_rate_' . $code, null);
        $rates[$code] = $customRate !== null && $customRate !== ''
            ? (float) $customRate
            : null; // null means "use default"
    }

    return $rates;
}

/**
 * Calculate price excluding tax (HT) from price including tax (TTC)
 * Uses French accounting standards for rounding
 *
 * Formula: HT = TTC / (1 + VAT/100)
 *
 * @param float $priceTTC Price including tax
 * @param float $vatRate VAT rate as percentage (default: use default rate)
 * @return float Price excluding tax, rounded to 2 decimal places
 */
function calculatePriceHT(float $priceTTC, ?float $vatRate = null): float {
    $vatRate = $vatRate ?? getDefaultVatRate();
    if ($vatRate <= 0) {
        return round($priceTTC, 2);
    }
    $priceHT = $priceTTC / (1 + ($vatRate / 100));
    return round($priceHT, 2);
}

/**
 * Calculate price including tax (TTC) from price excluding tax (HT)
 * Uses French accounting standards for rounding
 *
 * Formula: TTC = HT * (1 + VAT/100)
 *
 * @param float $priceHT Price excluding tax
 * @param float $vatRate VAT rate as percentage (default: use default rate)
 * @return float Price including tax, rounded to 2 decimal places
 */
function calculatePriceTTC(float $priceHT, ?float $vatRate = null): float {
    $vatRate = $vatRate ?? getDefaultVatRate();
    $priceTTC = $priceHT * (1 + ($vatRate / 100));
    return round($priceTTC, 2);
}

/**
 * Calculate VAT amount from price including tax (TTC)
 *
 * Formula: VAT = TTC - HT = TTC - (TTC / (1 + VAT/100))
 *
 * @param float $priceTTC Price including tax
 * @param float $vatRate VAT rate as percentage (default: use default rate)
 * @return float VAT amount, rounded to 2 decimal places
 */
function calculateVATAmount(float $priceTTC, ?float $vatRate = null): float {
    $vatRate = $vatRate ?? getDefaultVatRate();
    if ($vatRate <= 0) {
        return 0.00;
    }
    $priceHT = calculatePriceHT($priceTTC, $vatRate);
    return round($priceTTC - $priceHT, 2);
}

/**
 * Get price breakdown (TTC, HT, VAT) for a given TTC price
 * @param float $priceTTC Price including tax
 * @param float|null $vatRate VAT rate as percentage (default: use default rate)
 * @return array ['ttc' => float, 'ht' => float, 'vat' => float, 'vat_rate' => float]
 */
function getPriceBreakdown(float $priceTTC, ?float $vatRate = null): array {
    $vatRate = $vatRate ?? getDefaultVatRate();
    $priceHT = calculatePriceHT($priceTTC, $vatRate);
    $vatAmount = round($priceTTC - $priceHT, 2);

    return [
        'ttc' => round($priceTTC, 2),
        'ht' => $priceHT,
        'vat' => $vatAmount,
        'vat_rate' => $vatRate
    ];
}

/**
 * Format price for display (French format)
 * @param float $price Price value
 * @param bool $includeSymbol Include € symbol
 * @return string Formatted price
 */
function formatPrice(float $price, bool $includeSymbol = true): string {
    $formatted = number_format($price, 2, ',', ' ');
    return $includeSymbol ? $formatted . ' €' : $formatted;
}

/**
 * Get VAT statistics for a date range
 * Calculates total revenue TTC, HT, and VAT collected
 *
 * @param string $startDate Start date (Y-m-d)
 * @param string $endDate End date (Y-m-d)
 * @return array Financial statistics with TTC, HT, VAT breakdown
 */
function getVATStatistics(string $startDate, string $endDate): array {
    $pdo = getDatabase();

    // Get all completed orders with their items
    $stmt = $pdo->prepare("
        SELECT
            o.id as order_id,
            o.total_amount,
            o.created_at,
            oi.item_id,
            oi.item_price,
            oi.quantity,
            oi.subtotal,
            COALESCE(i.category, 'general') as category
        FROM room_service_orders o
        JOIN room_service_order_items oi ON o.id = oi.order_id
        LEFT JOIN room_service_items i ON oi.item_id = i.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
            AND o.status NOT IN ('cancelled')
        ORDER BY o.id
    ");
    $stmt->execute([$startDate, $endDate]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalTTC = 0;
    $totalHT = 0;
    $totalVAT = 0;
    $vatByCategory = [];
    $vatByRate = [];

    foreach ($items as $item) {
        $category = $item['category'];
        $vatRate = getCategoryVatRate($category);
        $itemTTC = (float) $item['subtotal'];
        $itemHT = calculatePriceHT($itemTTC, $vatRate);
        $itemVAT = round($itemTTC - $itemHT, 2);

        $totalTTC += $itemTTC;
        $totalHT += $itemHT;
        $totalVAT += $itemVAT;

        // By category
        if (!isset($vatByCategory[$category])) {
            $vatByCategory[$category] = [
                'category' => $category,
                'vat_rate' => $vatRate,
                'total_ttc' => 0,
                'total_ht' => 0,
                'total_vat' => 0
            ];
        }
        $vatByCategory[$category]['total_ttc'] += $itemTTC;
        $vatByCategory[$category]['total_ht'] += $itemHT;
        $vatByCategory[$category]['total_vat'] += $itemVAT;

        // By VAT rate
        $rateKey = (string) $vatRate;
        if (!isset($vatByRate[$rateKey])) {
            $vatByRate[$rateKey] = [
                'vat_rate' => $vatRate,
                'total_ttc' => 0,
                'total_ht' => 0,
                'total_vat' => 0
            ];
        }
        $vatByRate[$rateKey]['total_ttc'] += $itemTTC;
        $vatByRate[$rateKey]['total_ht'] += $itemHT;
        $vatByRate[$rateKey]['total_vat'] += $itemVAT;
    }

    // Round final totals
    $totalTTC = round($totalTTC, 2);
    $totalHT = round($totalHT, 2);
    $totalVAT = round($totalVAT, 2);

    // Round category totals
    foreach ($vatByCategory as &$cat) {
        $cat['total_ttc'] = round($cat['total_ttc'], 2);
        $cat['total_ht'] = round($cat['total_ht'], 2);
        $cat['total_vat'] = round($cat['total_vat'], 2);
    }

    // Round rate totals
    foreach ($vatByRate as &$rate) {
        $rate['total_ttc'] = round($rate['total_ttc'], 2);
        $rate['total_ht'] = round($rate['total_ht'], 2);
        $rate['total_vat'] = round($rate['total_vat'], 2);
    }

    return [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'totals' => [
            'ttc' => $totalTTC,
            'ht' => $totalHT,
            'vat' => $totalVAT
        ],
        'by_category' => array_values($vatByCategory),
        'by_rate' => array_values($vatByRate)
    ];
}

/**
 * Get room service financial statistics for a period
 * Extended version of getRoomServicePeriodStats with VAT breakdown
 *
 * @param string $period Period type: 'day', 'week', 'month', 'year'
 * @param string|null $date Reference date (defaults to today)
 * @return array Extended statistics with VAT information
 */
function getRoomServiceFinancialStats(string $period = 'day', ?string $date = null): array {
    $refDate = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');

    // Calculate date ranges
    switch ($period) {
        case 'week':
            $startDate = date('Y-m-d', strtotime('monday this week', strtotime($refDate)));
            $endDate = date('Y-m-d', strtotime('sunday this week', strtotime($refDate)));
            $prevStart = date('Y-m-d', strtotime('-1 week', strtotime($startDate)));
            $prevEnd = date('Y-m-d', strtotime('-1 week', strtotime($endDate)));
            break;
        case 'month':
            $startDate = date('Y-m-01', strtotime($refDate));
            $endDate = date('Y-m-t', strtotime($refDate));
            $prevStart = date('Y-m-01', strtotime('-1 month', strtotime($refDate)));
            $prevEnd = date('Y-m-t', strtotime('-1 month', strtotime($refDate)));
            break;
        case 'year':
            $startDate = date('Y-01-01', strtotime($refDate));
            $endDate = date('Y-12-31', strtotime($refDate));
            $prevStart = date('Y-01-01', strtotime('-1 year', strtotime($refDate)));
            $prevEnd = date('Y-12-31', strtotime('-1 year', strtotime($refDate)));
            break;
        default: // day
            $startDate = $refDate;
            $endDate = $refDate;
            $prevStart = date('Y-m-d', strtotime('-1 day', strtotime($refDate)));
            $prevEnd = $prevStart;
    }

    // Get VAT statistics for current and previous period
    $current = getVATStatistics($startDate, $endDate);
    $previous = getVATStatistics($prevStart, $prevEnd);

    // Get order counts
    $pdo = getDatabase();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as order_count
        FROM room_service_orders
        WHERE DATE(created_at) BETWEEN ? AND ?
            AND status NOT IN ('cancelled')
    ");
    $stmt->execute([$startDate, $endDate]);
    $currentOrders = (int) $stmt->fetchColumn();

    $stmt->execute([$prevStart, $prevEnd]);
    $previousOrders = (int) $stmt->fetchColumn();

    // Calculate changes
    $revenueTTCChange = $previous['totals']['ttc'] > 0
        ? (($current['totals']['ttc'] - $previous['totals']['ttc']) / $previous['totals']['ttc']) * 100
        : ($current['totals']['ttc'] > 0 ? 100 : 0);

    $ordersChange = $previousOrders > 0
        ? (($currentOrders - $previousOrders) / $previousOrders) * 100
        : ($currentOrders > 0 ? 100 : 0);

    return [
        'period' => $period,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'current' => [
            'order_count' => $currentOrders,
            'revenue_ttc' => $current['totals']['ttc'],
            'revenue_ht' => $current['totals']['ht'],
            'vat_collected' => $current['totals']['vat'],
            'avg_order_ttc' => $currentOrders > 0 ? round($current['totals']['ttc'] / $currentOrders, 2) : 0,
            'by_category' => $current['by_category'],
            'by_rate' => $current['by_rate']
        ],
        'previous' => [
            'start_date' => $prevStart,
            'end_date' => $prevEnd,
            'order_count' => $previousOrders,
            'revenue_ttc' => $previous['totals']['ttc'],
            'revenue_ht' => $previous['totals']['ht'],
            'vat_collected' => $previous['totals']['vat']
        ],
        'changes' => [
            'revenue_ttc_percent' => round($revenueTTCChange, 1),
            'orders_percent' => round($ordersChange, 1)
        ]
    ];
}
