-- =====================================================
-- DYNAMIC PAGES SYSTEM
-- Migration 006: Create pages table and update sections (PostgreSQL)
-- Hotel Corintel
-- =====================================================

-- Pages table - Core table for dynamic page management
CREATE TABLE IF NOT EXISTS pages (
    id SERIAL PRIMARY KEY,

    -- Page identification
    slug VARCHAR(50) NOT NULL UNIQUE,
    code VARCHAR(50) NOT NULL UNIQUE,

    -- Display information
    title VARCHAR(100) NOT NULL,
    nav_title VARCHAR(50) DEFAULT NULL,

    -- SEO
    meta_title VARCHAR(150) DEFAULT NULL,
    meta_description VARCHAR(300) DEFAULT NULL,
    meta_keywords VARCHAR(255) DEFAULT NULL,

    -- Hero section
    hero_section_code VARCHAR(50) DEFAULT NULL,

    -- Page type and behavior
    page_type VARCHAR(20) NOT NULL DEFAULT 'standard' CHECK (page_type IN ('standard', 'home', 'contact', 'special')),
    template VARCHAR(50) DEFAULT 'default',

    -- Ordering and visibility
    display_order INT NOT NULL DEFAULT 0,
    show_in_nav BOOLEAN NOT NULL DEFAULT TRUE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    -- i18n keys for translations
    i18n_nav_key VARCHAR(100) DEFAULT NULL,
    i18n_hero_title_key VARCHAR(100) DEFAULT NULL,
    i18n_hero_subtitle_key VARCHAR(100) DEFAULT NULL,

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_pages_slug ON pages (slug);
CREATE INDEX IF NOT EXISTS idx_pages_code ON pages (code);
CREATE INDEX IF NOT EXISTS idx_pages_display_order ON pages (display_order);
CREATE INDEX IF NOT EXISTS idx_pages_active ON pages (is_active);
CREATE INDEX IF NOT EXISTS idx_pages_nav ON pages (show_in_nav, is_active, display_order);

CREATE TRIGGER update_pages_updated_at BEFORE UPDATE ON pages
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- Seed existing pages for backward compatibility
-- =====================================================
INSERT INTO pages (slug, code, title, nav_title, page_type, display_order, i18n_nav_key, hero_section_code, i18n_hero_title_key, i18n_hero_subtitle_key) VALUES
('', 'home', 'Accueil', 'Accueil', 'home', 1, 'nav.home', 'home_hero', 'home.heroTitle', 'home.heroSubtitle'),
('services', 'services', 'Services', 'Services', 'standard', 2, 'nav.services', 'services_hero', 'services.heroTitle', 'services.heroDescription'),
('activites', 'activities', 'À découvrir', 'À découvrir', 'standard', 3, 'nav.discover', 'activities_hero', 'activities.heroTitle', 'activities.heroDescription'),
('contact', 'contact', 'Contact', 'Contact', 'contact', 4, 'nav.contact', 'contact_hero', 'contact.heroTitle', 'contact.heroDescription')
ON CONFLICT (slug) DO UPDATE SET title = EXCLUDED.title;

-- =====================================================
-- Add page_id to content_sections for future use
-- (only if table exists — it is created dynamically by the PHP application)
-- =====================================================
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'content_sections'
    ) THEN
        ALTER TABLE content_sections ADD COLUMN IF NOT EXISTS page_id INT DEFAULT NULL;
        CREATE INDEX IF NOT EXISTS idx_content_sections_page_id ON content_sections (page_id);

        UPDATE content_sections
        SET page_id = p.id
        FROM pages p
        WHERE content_sections.page = p.code
        AND content_sections.page_id IS NULL;
    END IF;
END $$;

-- =====================================================
-- Page translations table
-- =====================================================
CREATE TABLE IF NOT EXISTS page_translations (
    id SERIAL PRIMARY KEY,
    page_id INT NOT NULL,
    lang_code VARCHAR(5) NOT NULL,

    -- Translated content
    title VARCHAR(100) DEFAULT NULL,
    nav_title VARCHAR(50) DEFAULT NULL,
    meta_title VARCHAR(150) DEFAULT NULL,
    meta_description VARCHAR(300) DEFAULT NULL,
    hero_title VARCHAR(150) DEFAULT NULL,
    hero_subtitle VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (page_id, lang_code),
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_page_translations_page_id ON page_translations (page_id);
CREATE INDEX IF NOT EXISTS idx_page_translations_lang ON page_translations (lang_code);

CREATE TRIGGER update_page_translations_updated_at BEFORE UPDATE ON page_translations
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
