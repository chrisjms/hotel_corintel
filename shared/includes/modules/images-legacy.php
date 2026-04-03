<?php
/**
 * Legacy Image Management
 * Per-section, per-position image storage system
 */

/**
 * Get all images for a section
 */
function getImagesBySection(string $section): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM images WHERE section = ? AND hotel_id = ? ORDER BY position ASC');
    $stmt->execute([$section, getHotelId()]);
    return $stmt->fetchAll();
}

/**
 * Get a single image by section and position
 */
function getImage(string $section, int $position): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM images WHERE section = ? AND position = ? AND hotel_id = ?');
    $stmt->execute([$section, $position, getHotelId()]);
    return $stmt->fetch() ?: null;
}

/**
 * Get a single image by ID
 */
function getImageById(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM images WHERE id = ? AND hotel_id = ?');
    $stmt->execute([$id, getHotelId()]);
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
    $stmt = $pdo->prepare('UPDATE images SET filename = ?, title = ?, alt_text = ?, updated_at = NOW() WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$filename, $title, $altText, $id, getHotelId()]);
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
