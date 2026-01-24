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

/**
 * Get image stats
 */
function getImageStats(): array {
    $pdo = getDatabase();

    $stats = [];

    // Total images
    $stmt = $pdo->query('SELECT COUNT(*) FROM images');
    $stats['total'] = $stmt->fetchColumn();

    // Images per section
    $stmt = $pdo->query('SELECT section, COUNT(*) as count FROM images GROUP BY section');
    $stats['by_section'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Recently updated
    $stmt = $pdo->query('SELECT COUNT(*) FROM images WHERE updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)');
    $stats['recent_updates'] = $stmt->fetchColumn();

    return $stats;
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
 * Position is auto-calculated (MAX+1) and cannot be set from input
 */
function createRoomServiceItem(array $data): int|false {
    $pdo = getDatabase();

    // Auto-calculate position (next available)
    $stmtPos = $pdo->query('SELECT COALESCE(MAX(position), 0) + 1 FROM room_service_items');
    $nextPosition = (int)$stmtPos->fetchColumn();

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
        $nextPosition
    ]);
    return $success ? (int)$pdo->lastInsertId() : false;
}

/**
 * Update a room service item
 * Position is NOT updated - it is managed internally only
 */
function updateRoomServiceItem(int $id, array $data): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE room_service_items
        SET name = ?, description = ?, price = ?, image = ?, category = ?, is_active = ?
        WHERE id = ?
    ');
    return $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['price'],
        $data['image'] ?? null,
        $data['category'] ?? 'general',
        $data['is_active'] ?? 1,
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
 * Get room service item categories
 */
function getRoomServiceCategories(): array {
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
