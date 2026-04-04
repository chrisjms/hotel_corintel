<?php
/**
 * Dynamic Sections Functions
 * Section templates, dynamic sections CRUD, reorder
 */

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
        $stmt = $pdo->prepare('UPDATE content_sections SET template_type = ? WHERE template_type = ? AND hotel_id = ?');
        $stmt->execute([$newCode, $oldCode, getHotelId()]);
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

/**
 * Get all dynamic sections for a page
 */
function getDynamicSections(string $page): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        SELECT cs.*, st.css_class as template_css_class, st.has_services as template_has_services, st.has_gallery as template_has_gallery
        FROM content_sections cs
        LEFT JOIN section_templates st ON cs.template_type = st.code
        WHERE cs.page = ? AND cs.is_dynamic = TRUE AND cs.hotel_id = ?
        ORDER BY cs.sort_order
    ');
    $stmt->execute([$page, getHotelId()]);
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
    if (!$section || !isset($section['is_dynamic']) || !filter_var($section['is_dynamic'], FILTER_VALIDATE_BOOLEAN)) {
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
    $stmt = $pdo->prepare('SELECT MAX(sort_order) FROM content_sections WHERE page = ? AND hotel_id = ?');
    $stmt->execute([$page, getHotelId()]);
    $maxOrder = $stmt->fetchColumn() ?: 0;

    // Create the section
    $stmt = $pdo->prepare('
        INSERT INTO content_sections (
            code, name, description, page, image_mode, max_blocks,
            has_title, has_description, has_link, has_overlay, has_features, has_services, has_gallery,
            sort_order, is_dynamic, template_type, custom_name, hotel_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
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
        $customName,
        getHotelId()
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
        WHERE code = ? AND is_dynamic = TRUE AND hotel_id = ?
    ');
    return $stmt->execute([$newName, $newName, $sectionCode, getHotelId()]);
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
    $stmt = $pdo->prepare('DELETE FROM content_sections WHERE code = ? AND is_dynamic = TRUE AND hotel_id = ?');
    return $stmt->execute([$sectionCode, getHotelId()]);
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
            WHERE code = ? AND page = ? AND is_dynamic = TRUE AND hotel_id = ?
        ');
        $stmt->execute([$position, $code, $page, getHotelId()]);
        $position++;
    }

    return true;
}

/**
 * Check if a page has any dynamic sections
 */
function pageHasDynamicSections(string $page): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM content_sections WHERE page = ? AND is_dynamic = TRUE AND hotel_id = ?');
    $stmt->execute([$page, getHotelId()]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get count of dynamic sections for a page
 */
function countDynamicSections(string $page): int {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM content_sections WHERE page = ? AND is_dynamic = TRUE AND hotel_id = ?');
    $stmt->execute([$page, getHotelId()]);
    return (int) $stmt->fetchColumn();
}
