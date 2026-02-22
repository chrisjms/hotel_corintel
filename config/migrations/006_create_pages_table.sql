-- =====================================================
-- DYNAMIC PAGES SYSTEM
-- Migration 006: Create pages table and update sections
-- Hotel Corintel
-- =====================================================

-- Pages table - Core table for dynamic page management
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Page identification
    slug VARCHAR(50) NOT NULL UNIQUE COMMENT 'URL slug (e.g., services, contact, experiences)',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Internal code for backward compatibility',

    -- Display information
    title VARCHAR(100) NOT NULL COMMENT 'Page title for admin display',
    nav_title VARCHAR(50) DEFAULT NULL COMMENT 'Short title for navigation (uses title if NULL)',

    -- SEO
    meta_title VARCHAR(150) DEFAULT NULL COMMENT 'SEO title (uses title if NULL)',
    meta_description VARCHAR(300) DEFAULT NULL COMMENT 'SEO meta description',
    meta_keywords VARCHAR(255) DEFAULT NULL COMMENT 'SEO keywords',

    -- Hero section
    hero_section_code VARCHAR(50) DEFAULT NULL COMMENT 'Code of the hero section for this page',

    -- Page type and behavior
    page_type ENUM('standard', 'home', 'contact', 'special') NOT NULL DEFAULT 'standard' COMMENT 'Page type for special handling',
    template VARCHAR(50) DEFAULT 'default' COMMENT 'Template to use for rendering',

    -- Ordering and visibility
    display_order INT NOT NULL DEFAULT 0 COMMENT 'Order in navigation menu',
    show_in_nav TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Show in main navigation',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Page is published',

    -- i18n keys for translations
    i18n_nav_key VARCHAR(100) DEFAULT NULL COMMENT 'i18n key for navigation (e.g., nav.services)',
    i18n_hero_title_key VARCHAR(100) DEFAULT NULL COMMENT 'i18n key for hero title',
    i18n_hero_subtitle_key VARCHAR(100) DEFAULT NULL COMMENT 'i18n key for hero subtitle',

    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_slug (slug),
    INDEX idx_code (code),
    INDEX idx_display_order (display_order),
    INDEX idx_active (is_active),
    INDEX idx_nav (show_in_nav, is_active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed existing pages for backward compatibility
-- =====================================================
INSERT INTO pages (slug, code, title, nav_title, page_type, display_order, i18n_nav_key, hero_section_code, i18n_hero_title_key, i18n_hero_subtitle_key) VALUES
('', 'home', 'Accueil', 'Accueil', 'home', 1, 'nav.home', 'home_hero', 'home.heroTitle', 'home.heroSubtitle'),
('services', 'services', 'Services', 'Services', 'standard', 2, 'nav.services', 'services_hero', 'services.heroTitle', 'services.heroDescription'),
('activites', 'activities', 'À découvrir', 'À découvrir', 'standard', 3, 'nav.discover', 'activities_hero', 'activities.heroTitle', 'activities.heroDescription'),
('contact', 'contact', 'Contact', 'Contact', 'contact', 4, 'nav.contact', 'contact_hero', 'contact.heroTitle', 'contact.heroDescription')
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- =====================================================
-- Add page_id to content_sections for future use
-- Keep 'page' column for backward compatibility
-- =====================================================

-- Add page_id column if it doesn't exist (MySQL 5.7 compatible)
SET @dbname = DATABASE();
SET @tablename = 'content_sections';
SET @columnname = 'page_id';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT DEFAULT NULL AFTER page')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index if it doesn't exist
SET @indexname = 'idx_page_id';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD INDEX ', @indexname, ' (page_id)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update existing sections with page_id based on page code
UPDATE content_sections cs
JOIN pages p ON cs.page = p.code
SET cs.page_id = p.id
WHERE cs.page_id IS NULL;

-- =====================================================
-- Page translations table
-- =====================================================
CREATE TABLE IF NOT EXISTS page_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    lang_code VARCHAR(5) NOT NULL COMMENT 'Language code (en, es, it, etc.)',

    -- Translated content
    title VARCHAR(100) DEFAULT NULL,
    nav_title VARCHAR(50) DEFAULT NULL,
    meta_title VARCHAR(150) DEFAULT NULL,
    meta_description VARCHAR(300) DEFAULT NULL,
    hero_title VARCHAR(150) DEFAULT NULL,
    hero_subtitle VARCHAR(255) DEFAULT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_page_lang (page_id, lang_code),
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_page_id (page_id),
    INDEX idx_lang (lang_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
