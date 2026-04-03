<?php
/**
 * Content Management Functions
 * Content tables init/seed, sections, blocks, overlays
 */

if (!defined('IMAGE_REQUIRED')) {
    define('IMAGE_REQUIRED', 'required');
}
if (!defined('IMAGE_OPTIONAL')) {
    define('IMAGE_OPTIONAL', 'optional');
}
if (!defined('IMAGE_FORBIDDEN')) {
    define('IMAGE_FORBIDDEN', 'forbidden');
}

/**
 * Initialize content management tables
 */
function initContentTables(): void {
    $pdo = getDatabase();

    // Content sections configuration table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_sections (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            page VARCHAR(50) NOT NULL,
            image_mode VARCHAR(20) DEFAULT 'optional' CHECK (image_mode IN ('required', 'optional', 'forbidden')),
            max_blocks INT DEFAULT NULL,
            has_title BOOLEAN DEFAULT TRUE,
            has_description BOOLEAN DEFAULT TRUE,
            has_link BOOLEAN DEFAULT FALSE,
            sort_order INT DEFAULT 0,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(code, hotel_id)
        )
    ");

    // Content blocks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_blocks (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            title VARCHAR(255),
            description TEXT,
            image_filename VARCHAR(255),
            image_alt VARCHAR(255),
            link_url VARCHAR(500),
            link_text VARCHAR(100),
            position INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_content_blocks_section ON content_blocks(section_code)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_content_blocks_position ON content_blocks(position)");

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
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN has_overlay BOOLEAN DEFAULT FALSE");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Section overlay translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_overlay_translations (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            overlay_subtitle VARCHAR(255),
            overlay_title VARCHAR(255),
            overlay_description TEXT,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (section_code, language_code),
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        )
    ");

    // Add has_features flag to content_sections if not exist
    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN has_features BOOLEAN DEFAULT FALSE");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Section features table (reusable for any section)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_features (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            icon_code VARCHAR(50) NOT NULL,
            label VARCHAR(100) NOT NULL,
            position INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_section_features_section ON section_features(section_code)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_section_features_position ON section_features(position)");

    // Section feature translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_feature_translations (
            id SERIAL PRIMARY KEY,
            feature_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            label VARCHAR(100) NOT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (feature_id, language_code),
            FOREIGN KEY (feature_id) REFERENCES section_features(id) ON DELETE CASCADE
        )
    ");

    // Add columns for dynamic sections support
    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN is_dynamic BOOLEAN DEFAULT FALSE");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN template_type VARCHAR(50) DEFAULT NULL");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN custom_name VARCHAR(100) DEFAULT NULL");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN has_services BOOLEAN DEFAULT FALSE");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN has_gallery BOOLEAN DEFAULT FALSE");
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

    // Add section link columns for external CTA links
    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN section_link_url VARCHAR(500) DEFAULT NULL");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN section_link_text VARCHAR(100) DEFAULT NULL");
    } catch (PDOException $e) { /* Column may already exist */ }

    try {
        $pdo->exec("ALTER TABLE content_sections ADD COLUMN section_link_new_tab BOOLEAN DEFAULT TRUE");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Section link text translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_link_translations (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            link_text VARCHAR(100) NOT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (section_code, language_code),
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        )
    ");

    // Section services table (reusable service cards for any section)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_services (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            icon_code VARCHAR(50) NOT NULL,
            label VARCHAR(100) NOT NULL,
            description TEXT,
            position INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_section_services_section ON section_services(section_code)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_section_services_position ON section_services(position)");

    // Section service translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_service_translations (
            id SERIAL PRIMARY KEY,
            service_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            label VARCHAR(100) NOT NULL,
            description TEXT,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (service_id, language_code),
            FOREIGN KEY (service_id) REFERENCES section_services(id) ON DELETE CASCADE
        )
    ");

    // Section gallery items table (for image gallery sections)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_gallery_items (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            image_filename VARCHAR(255) NOT NULL,
            image_alt VARCHAR(255),
            title VARCHAR(100) NOT NULL,
            description TEXT,
            position INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (section_code) REFERENCES content_sections(code) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_section_gallery_items_section ON section_gallery_items(section_code)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_section_gallery_items_position ON section_gallery_items(position)");

    // Section gallery item translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_gallery_item_translations (
            id SERIAL PRIMARY KEY,
            item_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (item_id, language_code),
            FOREIGN KEY (item_id) REFERENCES section_gallery_items(id) ON DELETE CASCADE
        )
    ");

    // Section templates table - defines reusable section templates
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_templates (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            image_mode VARCHAR(20) DEFAULT 'optional' CHECK (image_mode IN ('required', 'optional', 'forbidden')),
            max_blocks INT DEFAULT 1,
            has_title BOOLEAN DEFAULT FALSE,
            has_description BOOLEAN DEFAULT FALSE,
            has_link BOOLEAN DEFAULT FALSE,
            has_overlay BOOLEAN DEFAULT TRUE,
            has_features BOOLEAN DEFAULT TRUE,
            has_services BOOLEAN DEFAULT FALSE,
            css_class VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Add has_services column to section_templates if not exists
    try {
        $pdo->exec("ALTER TABLE section_templates ADD COLUMN has_services BOOLEAN DEFAULT FALSE");
    } catch (PDOException $e) { /* Column may already exist */ }

    // Add has_gallery column to section_templates if not exists
    try {
        $pdo->exec("ALTER TABLE section_templates ADD COLUMN has_gallery BOOLEAN DEFAULT FALSE");
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
        INSERT INTO content_sections
        (code, name, description, page, image_mode, max_blocks, has_title, has_description, has_link, sort_order, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (code, hotel_id) DO NOTHING
    ");

    foreach ($sections as $section) {
        $section[] = getHotelId();
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
        SET has_overlay = TRUE,
            has_title = FALSE,
            has_description = FALSE,
            has_link = FALSE
        WHERE code = ? AND hotel_id = ?
    ');
    $stmt->execute(['home_hero', getHotelId()]);
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
            AND hotel_id = ?
        ");
        $stmt->execute(array_merge($keepSections, [getHotelId()]));
        $legacySections = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Delete each legacy section and its related data
        foreach ($legacySections as $sectionCode) {
            // Delete related data first (ignore if tables don't exist)
            $safeDelete('DELETE FROM section_feature_translations WHERE feature_id IN (SELECT id FROM section_features WHERE section_code = ? AND hotel_id = ?)', [$sectionCode, getHotelId()]);
            $safeDelete('DELETE FROM section_features WHERE section_code = ? AND hotel_id = ?', [$sectionCode, getHotelId()]);
            $safeDelete('DELETE FROM section_overlay_translations WHERE overlay_id IN (SELECT id FROM section_overlay_texts WHERE section_code = ? AND hotel_id = ?)', [$sectionCode, getHotelId()]);
            $safeDelete('DELETE FROM section_overlay_texts WHERE section_code = ? AND hotel_id = ?', [$sectionCode, getHotelId()]);
            $safeDelete('DELETE FROM section_gallery_translations WHERE item_id IN (SELECT id FROM section_gallery_items WHERE section_code = ? AND hotel_id = ?)', [$sectionCode, getHotelId()]);
            $safeDelete('DELETE FROM section_gallery_items WHERE section_code = ? AND hotel_id = ?', [$sectionCode, getHotelId()]);
            $safeDelete('DELETE FROM section_service_translations WHERE service_id IN (SELECT id FROM section_services WHERE section_code = ? AND hotel_id = ?)', [$sectionCode, getHotelId()]);
            $safeDelete('DELETE FROM section_services WHERE section_code = ? AND hotel_id = ?', [$sectionCode, getHotelId()]);
            $safeDelete('DELETE FROM content_blocks WHERE section_code = ? AND hotel_id = ?', [$sectionCode, getHotelId()]);

            // Delete the section itself
            $safeDelete('DELETE FROM content_sections WHERE code = ? AND hotel_id = ?', [$sectionCode, getHotelId()]);
        }
    } catch (PDOException $e) {
        // Silently fail - tables might not exist yet
    }
}

// =====================================================
// CONTENT SECTIONS & BLOCKS FUNCTIONS
// =====================================================

function getContentSectionsByPage(): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM content_sections WHERE hotel_id = ? ORDER BY page, sort_order');
    $stmt->execute([getHotelId()]);
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
    $stmt = $pdo->prepare('SELECT * FROM content_sections WHERE hotel_id = ? ORDER BY page, sort_order');
    $stmt->execute([getHotelId()]);
    return $stmt->fetchAll();
}

/**
 * Get a content section by code
 */
function getContentSection(string $code): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM content_sections WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$code, getHotelId()]);
    return $stmt->fetch() ?: null;
}

/**
 * Get content blocks for a section
 */
function getContentBlocks(string $sectionCode, bool $activeOnly = false): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM content_blocks WHERE section_code = ? AND hotel_id = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = TRUE';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sectionCode, getHotelId()]);
    return $stmt->fetchAll();
}

/**
 * Get a content block by ID
 */
function getContentBlock(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM content_blocks WHERE id = ? AND hotel_id = ?');
    $stmt->execute([$id, getHotelId()]);
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
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM content_blocks WHERE section_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    $nextPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        INSERT INTO content_blocks (section_code, title, description, image_filename, image_alt, link_url, link_text, position, is_active, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $data['is_active'] ?? 1,
        getHotelId()
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
        WHERE id = ? AND hotel_id = ?
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
        $id,
        getHotelId()
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
    $stmt = $pdo->prepare('DELETE FROM content_blocks WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$id, getHotelId()]);
}

/**
 * Reorder content blocks
 */
function reorderContentBlocks(string $sectionCode, array $blockIds): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_blocks SET position = ? WHERE id = ? AND section_code = ? AND hotel_id = ?');

    $position = 1;
    foreach ($blockIds as $id) {
        $stmt->execute([$position, $id, $sectionCode, getHotelId()]);
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
    $stmt = $pdo->prepare('UPDATE content_blocks SET image_filename = ? WHERE id = ? AND hotel_id = ?');
    $stmt->execute(['uploads/' . $newFilename, $blockId, getHotelId()]);

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
 * Get page names for display (now uses dynamic pages system)
 * @return array Associative array of code => title
 */
function getContentPageNames(): array {
    try {
        // Try to use dynamic pages system
        $pages = getPages(true);
        if (!empty($pages)) {
            $names = [];
            foreach ($pages as $page) {
                $names[$page['code']] = $page['title'];
            }
            return $names;
        }
    } catch (Exception $e) {
        // Fall back to hardcoded if pages table doesn't exist
    }

    // Fallback for backward compatibility
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
        $stmt = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'images'");
        if (!$stmt->fetch()) {
            return ['migrated' => 0, 'errors' => ['Table images non trouvée.']];
        }

        // Get existing images
        $stmt = $pdo->prepare('SELECT * FROM images WHERE hotel_id = ? ORDER BY section, position');
        $stmt->execute([getHotelId()]);
        $images = $stmt->fetchAll();

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
    $stmt = $pdo->prepare('SELECT has_overlay FROM content_sections WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    $result = $stmt->fetchColumn();
    return $result == 1;
}

/**
 * Enable overlay for a section
 */
function enableSectionOverlay(string $sectionCode): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET has_overlay = TRUE WHERE code = ? AND hotel_id = ?');
    return $stmt->execute([$sectionCode, getHotelId()]);
}

/**
 * Get section overlay texts (default language - French)
 */
function getSectionOverlay(string $sectionCode): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT overlay_subtitle, overlay_title, overlay_description FROM content_sections WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
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
    $stmt = $pdo->prepare('SELECT language_code, overlay_subtitle, overlay_title, overlay_description FROM section_overlay_translations WHERE section_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
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
        SET overlay_subtitle = ?, overlay_title = ?, overlay_description = ?, has_overlay = TRUE
        WHERE code = ? AND hotel_id = ?
    ');
    return $stmt->execute([trim($subtitle), trim($title), trim($description), $sectionCode, getHotelId()]);
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
            $stmt = $pdo->prepare('DELETE FROM section_overlay_translations WHERE section_code = ? AND language_code = ? AND hotel_id = ?');
            $stmt->execute([$sectionCode, $langCode, getHotelId()]);
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO section_overlay_translations (section_code, language_code, overlay_subtitle, overlay_title, overlay_description, hotel_id)
                VALUES (?, ?, ?, ?, ?, ?)
                ON CONFLICT (section_code, language_code, hotel_id) DO UPDATE SET
                    overlay_subtitle = EXCLUDED.overlay_subtitle,
                    overlay_title = EXCLUDED.overlay_title,
                    overlay_description = EXCLUDED.overlay_description
            ');
            $success = $stmt->execute([$sectionCode, $langCode, $subtitle, $title, $description, getHotelId()]) && $success;
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
    $stmt = $pdo->prepare('SELECT overlay_subtitle, overlay_title, overlay_description FROM section_overlay_translations WHERE section_code = ? AND language_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, $langCode, getHotelId()]);
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
