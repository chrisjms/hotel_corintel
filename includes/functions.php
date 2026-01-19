<?php
/**
 * Helper Functions
 * Hotel Corintel - Admin System
 */

require_once __DIR__ . '/../config/database.php';

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
 * Update image record (basic - for file uploads)
 */
function updateImage(int $id, string $filename, ?string $title = null, ?string $altText = null): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE images SET filename = ?, title = ?, alt_text = ?, updated_at = NOW() WHERE id = ?');
    return $stmt->execute([$filename, $title, $altText, $id]);
}

/**
 * Update content block (image info + text content)
 */
function updateContentBlock(int $id, array $data): bool {
    $pdo = getDatabase();

    // Build dynamic update query based on provided data
    $fields = [];
    $values = [];

    $allowedFields = ['title', 'alt_text', 'heading', 'content'];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }

    if (empty($fields)) {
        return false;
    }

    $fields[] = 'updated_at = NOW()';
    $values[] = $id;

    $sql = 'UPDATE images SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($values);
}

/**
 * Get content block by section and position
 * Returns image data with all associated text content
 */
function getContentBlock(string $section, int $position): ?array {
    $image = getImage($section, $position);

    if (!$image) {
        return null;
    }

    return [
        'id' => $image['id'],
        'image' => $image['filename'],
        'title' => $image['title'] ?? '',
        'alt' => $image['alt_text'] ?? '',
        'heading' => $image['heading'] ?? '',
        'content' => $image['content'] ?? '',
        'slot' => $image['slot_name'] ?? '',
        'updated' => $image['updated_at']
    ];
}

/**
 * Get all content blocks for a section
 */
function getContentBlocks(string $section): array {
    $images = getImagesBySection($section);
    $blocks = [];

    foreach ($images as $image) {
        $blocks[$image['position']] = [
            'id' => $image['id'],
            'image' => $image['filename'],
            'title' => $image['title'] ?? '',
            'alt' => $image['alt_text'] ?? '',
            'heading' => $image['heading'] ?? '',
            'content' => $image['content'] ?? '',
            'slot' => $image['slot_name'] ?? '',
            'position' => $image['position'],
            'updated' => $image['updated_at']
        ];
    }

    return $blocks;
}

/**
 * Render a content block's text with fallback
 */
function blockText(array $block, string $field, string $fallback = ''): string {
    return !empty($block[$field]) ? $block[$field] : $fallback;
}

/**
 * Render a content block's image URL with fallback
 */
function blockImage(array $block, string $fallback = ''): string {
    return !empty($block['image']) ? $block['image'] : $fallback;
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
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'valid' => false,
            'message' => 'Erreur lors de l\'enregistrement du fichier.'
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
