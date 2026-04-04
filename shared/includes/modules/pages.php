<?php
/**
 * Pages Functions
 * Pages CRUD, navigation, slugs, hero section creation
 */

function initPagesTable(): void {
    $pdo = getDatabase();

    // Check if pages table exists
    $stmt = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'pages'");
    if ($stmt->rowCount() === 0) {
        // Create pages table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pages (
                id SERIAL PRIMARY KEY,
                slug VARCHAR(50) NOT NULL,
                code VARCHAR(50) NOT NULL,
                title VARCHAR(100) NOT NULL,
                nav_title VARCHAR(50) DEFAULT NULL,
                meta_title VARCHAR(150) DEFAULT NULL,
                meta_description VARCHAR(300) DEFAULT NULL,
                meta_keywords VARCHAR(255) DEFAULT NULL,
                hero_section_code VARCHAR(50) DEFAULT NULL,
                page_type VARCHAR(20) NOT NULL DEFAULT 'standard' CHECK (page_type IN ('standard', 'home', 'contact', 'special')),
                template VARCHAR(50) DEFAULT 'default',
                display_order INT NOT NULL DEFAULT 0,
                show_in_nav BOOLEAN NOT NULL DEFAULT TRUE,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                i18n_nav_key VARCHAR(100) DEFAULT NULL,
                i18n_hero_title_key VARCHAR(100) DEFAULT NULL,
                i18n_hero_subtitle_key VARCHAR(100) DEFAULT NULL,
                hotel_id INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(slug, hotel_id),
                UNIQUE(code, hotel_id)
            )
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_pages_slug ON pages(slug)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_pages_code ON pages(code)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_pages_display_order ON pages(display_order)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_pages_active ON pages(is_active)");

        // Seed default pages
        seedDefaultPages();
    }
}

/**
 * Seed default pages (for migration)
 */
function seedDefaultPages(): void {
    $pdo = getDatabase();

    $defaultPages = [
        ['', 'home', 'Accueil', 'Accueil', 'home', 1, 'nav.home', 'home_hero', 'home.heroTitle', 'home.heroSubtitle'],
        ['services', 'services', 'Services', 'Services', 'standard', 2, 'nav.services', 'services_hero', 'services.heroTitle', 'services.heroDescription'],
        ['activites', 'activities', 'À découvrir', 'À découvrir', 'standard', 3, 'nav.discover', 'activities_hero', 'activities.heroTitle', 'activities.heroDescription'],
        ['contact', 'contact', 'Contact', 'Contact', 'contact', 4, 'nav.contact', 'contact_hero', 'contact.heroTitle', 'contact.heroDescription']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO pages (slug, code, title, nav_title, page_type, display_order, i18n_nav_key, hero_section_code, i18n_hero_title_key, i18n_hero_subtitle_key, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (slug, hotel_id) DO NOTHING
    ");

    foreach ($defaultPages as $page) {
        $page[] = getHotelId();
        $stmt->execute($page);
    }
}

/**
 * Get all pages
 * @param bool $activeOnly Only return active pages
 * @param bool $navOnly Only return pages shown in navigation
 * @return array List of pages
 */
function getPages(bool $activeOnly = false, bool $navOnly = false): array {
    $pdo = getDatabase();

    try {
        initPagesTable();

        $sql = "SELECT * FROM pages WHERE hotel_id = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = TRUE";
        }
        if ($navOnly) {
            $sql .= " AND show_in_nav = TRUE";
        }
        $sql .= " ORDER BY display_order ASC, id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([getHotelId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting pages: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get navigation pages (active and shown in nav)
 * @return array List of pages for navigation
 */
function getNavigationPages(): array {
    return getPages(true, true);
}

/**
 * Get page by ID
 * @param int $id Page ID
 * @return array|null Page data or null if not found
 */
function getPageById(int $id): ?array {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ? AND hotel_id = ?");
        $stmt->execute([$id, getHotelId()]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        return $page ?: null;
    } catch (PDOException $e) {
        error_log('Error getting page by ID: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get page by slug
 * @param string $slug Page slug
 * @return array|null Page data or null if not found
 */
function getPageBySlug(string $slug): ?array {
    $pdo = getDatabase();

    try {
        initPagesTable();

        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND is_active = TRUE AND hotel_id = ?");
        $stmt->execute([$slug, getHotelId()]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        return $page ?: null;
    } catch (PDOException $e) {
        error_log('Error getting page by slug: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get page by code (for backward compatibility)
 * @param string $code Page code
 * @return array|null Page data or null if not found
 */
function getPageByCode(string $code): ?array {
    $pdo = getDatabase();

    try {
        initPagesTable();

        $stmt = $pdo->prepare("SELECT * FROM pages WHERE code = ? AND hotel_id = ?");
        $stmt->execute([$code, getHotelId()]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        return $page ?: null;
    } catch (PDOException $e) {
        error_log('Error getting page by code: ' . $e->getMessage());
        return null;
    }
}

/**
 * Create a new page
 * @param array $data Page data
 * @param string|null $error Error message reference
 * @return int|false Page ID on success, false on failure
 */
function createPage(array $data, ?string &$error = null): int|false {
    $pdo = getDatabase();

    // Generate slug if not provided
    $slug = $data['slug'] ?? generateSlug($data['title']);
    $code = $data['code'] ?? $slug;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO pages (slug, code, title, nav_title, meta_title, meta_description, meta_keywords,
                hero_section_code, page_type, template, display_order, show_in_nav, is_active,
                i18n_nav_key, i18n_hero_title_key, i18n_hero_subtitle_key, hotel_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $slug,
            $code,
            $data['title'],
            $data['nav_title'] ?? $data['title'],
            $data['meta_title'] ?? null,
            $data['meta_description'] ?? null,
            $data['meta_keywords'] ?? null,
            $data['hero_section_code'] ?? null,
            $data['page_type'] ?? PAGE_TYPE_STANDARD,
            $data['template'] ?? 'default',
            $data['display_order'] ?? getNextPageDisplayOrder(),
            $data['show_in_nav'] ?? 1,
            $data['is_active'] ?? 1,
            $data['i18n_nav_key'] ?? null,
            $data['i18n_hero_title_key'] ?? null,
            $data['i18n_hero_subtitle_key'] ?? null,
            getHotelId()
        ]);

        $pageId = (int) $pdo->lastInsertId();

        // Create hero section for the page
        if (!empty($data['create_hero_section']) && $pageId) {
            createPageHeroSection($pageId, $slug);
        }

        return $pageId;
    } catch (PDOException $e) {
        error_log('Error creating page: ' . $e->getMessage());
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            if (strpos($e->getMessage(), 'slug') !== false) {
                $error = "Ce slug existe déjà.";
            } elseif (strpos($e->getMessage(), 'code') !== false) {
                $error = "Ce code existe déjà.";
            } else {
                $error = "Une page avec ces informations existe déjà.";
            }
        } else {
            $error = "Erreur lors de la création de la page.";
        }
        return false;
    }
}

/**
 * Update a page
 * @param int $id Page ID
 * @param array $data Page data to update
 * @param string|null $error Error message reference
 * @return bool Success status
 */
function updatePage(int $id, array $data, ?string &$error = null): bool {
    $pdo = getDatabase();

    $fields = [];
    $params = [];

    $allowedFields = [
        'slug', 'code', 'title', 'nav_title', 'meta_title', 'meta_description', 'meta_keywords',
        'hero_section_code', 'page_type', 'template', 'display_order', 'show_in_nav', 'is_active',
        'i18n_nav_key', 'i18n_hero_title_key', 'i18n_hero_subtitle_key'
    ];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($fields)) {
        return true; // Nothing to update
    }

    $params[] = $id;

    try {
        $params[] = getHotelId();
        $sql = "UPDATE pages SET " . implode(', ', $fields) . " WHERE id = ? AND hotel_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log('Error updating page: ' . $e->getMessage());
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error = "Ce slug ou code existe déjà.";
        } else {
            $error = "Erreur lors de la mise à jour de la page.";
        }
        return false;
    }
}

/**
 * Delete a page
 * @param int $id Page ID
 * @param string|null $error Error message reference
 * @return bool Success status
 */
function deletePage(int $id, ?string &$error = null): bool {
    $pdo = getDatabase();

    // Check if this is a protected page (home, contact)
    $page = getPageById($id);
    if ($page && in_array($page['page_type'], [PAGE_TYPE_HOME, PAGE_TYPE_CONTACT])) {
        $error = "Cette page système ne peut pas être supprimée.";
        return false;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ? AND hotel_id = ?");
        return $stmt->execute([$id, getHotelId()]);
    } catch (PDOException $e) {
        error_log('Error deleting page: ' . $e->getMessage());
        $error = "Erreur lors de la suppression de la page.";
        return false;
    }
}

/**
 * Reorder pages
 * @param array $pageIds Array of page IDs in new order
 * @return bool Success status
 */
function reorderPages(array $pageIds): bool {
    $pdo = getDatabase();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE pages SET display_order = ? WHERE id = ? AND hotel_id = ?");

        foreach ($pageIds as $order => $pageId) {
            $stmt->execute([$order + 1, (int) $pageId, getHotelId()]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Error reordering pages: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get next display order for new page
 * @return int Next display order
 */
function getNextPageDisplayOrder(): int {
    $pdo = getDatabase();

    try {
        $stmt = $pdo->prepare("SELECT MAX(display_order) FROM pages WHERE hotel_id = ?");
        $stmt->execute([getHotelId()]);
        $max = $stmt->fetchColumn();
        return ($max ?? 0) + 1;
    } catch (PDOException $e) {
        return 1;
    }
}

/**
 * Check if slug exists (for validation)
 * @param string $slug Slug to check
 * @param int|null $excludeId Exclude this page ID from check
 * @return bool True if slug exists
 */
function pageSlugExists(string $slug, ?int $excludeId = null): bool {
    $pdo = getDatabase();

    try {
        $sql = "SELECT COUNT(*) FROM pages WHERE slug = ? AND hotel_id = ?";
        $params = [$slug, getHotelId()];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Generate a URL-friendly slug from text
 * @param string $text Text to convert
 * @return string Slug
 */
function generateSlug(string $text): string {
    // Convert to lowercase
    $slug = mb_strtolower($text, 'UTF-8');

    // Replace accented characters
    $accents = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
        'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'é' => 'e',
        'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
        'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ÿ' => 'y', 'ý' => 'y',
        'ñ' => 'n', 'ç' => 'c',
        'œ' => 'oe', 'æ' => 'ae'
    ];
    $slug = strtr($slug, $accents);

    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');

    return $slug;
}

/**
 * Create hero section for a new page
 * @param int $pageId Page ID
 * @param string $slug Page slug
 * @return bool Success
 */
function createPageHeroSection(int $pageId, string $slug): bool {
    $pdo = getDatabase();

    $heroCode = $slug . '_hero';

    try {
        // Create the hero section in content_sections
        $stmt = $pdo->prepare("
            INSERT INTO content_sections (code, name, description, page, page_id, image_mode, max_blocks, has_title, has_description, has_link, sort_order, template_type, is_hero, hotel_id)
            VALUES (?, ?, ?, ?, ?, 'required', 1, 0, 0, 0, 0, 'hero', 1, ?)
        ");
        $stmt->execute([
            $heroCode,
            'Image Hero - ' . ucfirst($slug),
            'Image de bannière principale',
            $slug,
            $pageId,
            getHotelId()
        ]);

        // Update page with hero section code
        $stmt = $pdo->prepare("UPDATE pages SET hero_section_code = ? WHERE id = ? AND hotel_id = ?");
        $stmt->execute([$heroCode, $pageId, getHotelId()]);

        return true;
    } catch (PDOException $e) {
        error_log('Error creating hero section: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get page types with labels
 * @return array Page types
 */
function getPageTypes(): array {
    return [
        PAGE_TYPE_STANDARD => 'Standard',
        PAGE_TYPE_HOME => 'Accueil',
        PAGE_TYPE_CONTACT => 'Contact',
        PAGE_TYPE_SPECIAL => 'Spécial'
    ];
}

/**
 * Get dynamic content page names (replaces hardcoded getContentPageNames)
 * @return array Associative array of code => title
 */
function getDynamicPageNames(): array {
    $pages = getPages(true);
    $names = [];
    foreach ($pages as $page) {
        $names[$page['code']] = $page['title'];
    }
    return $names;
}

/**
 * Get page count
 * @param bool $activeOnly Only count active pages
 * @return int Page count
 */
function getPageCount(bool $activeOnly = false): int {
    $pdo = getDatabase();

    try {
        initPagesTable();

        $sql = "SELECT COUNT(*) FROM pages WHERE hotel_id = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = TRUE";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([getHotelId()]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
