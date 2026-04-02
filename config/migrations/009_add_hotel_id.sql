-- Migration 009: Add hotel_id to all per-hotel tables
-- Multi-tenant support: single database, hotel_id column for isolation
-- All existing data gets hotel_id = 1 (Hôtel Corintel)

-- =====================================================
-- 1. ADD hotel_id COLUMN TO ALL PER-HOTEL TABLES
-- =====================================================

-- Tables from schema.sql (always exist)
ALTER TABLE admins ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE persistent_tokens ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE login_attempts ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE images ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE room_service_items ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE room_service_orders ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE room_service_categories ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE room_service_order_items ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;

-- Tables from migrations (should exist)
ALTER TABLE guest_messages ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE housekeeping_logs ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE page_translations ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE qr_scans ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE push_subscriptions ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE room_service_item_translations ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;
ALTER TABLE room_service_category_translations ADD COLUMN IF NOT EXISTS hotel_id INT NOT NULL DEFAULT 1;

-- Tables created dynamically by PHP (may or may not exist)
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'settings') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'settings' AND column_name = 'hotel_id') THEN
            ALTER TABLE settings ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'content_sections') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'content_sections' AND column_name = 'hotel_id') THEN
            ALTER TABLE content_sections ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'content_blocks') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'content_blocks' AND column_name = 'hotel_id') THEN
            ALTER TABLE content_blocks ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_features') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'section_features' AND column_name = 'hotel_id') THEN
            ALTER TABLE section_features ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_feature_translations') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'section_feature_translations' AND column_name = 'hotel_id') THEN
            ALTER TABLE section_feature_translations ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_services') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'section_services' AND column_name = 'hotel_id') THEN
            ALTER TABLE section_services ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_service_translations') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'section_service_translations' AND column_name = 'hotel_id') THEN
            ALTER TABLE section_service_translations ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_gallery_items') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'section_gallery_items' AND column_name = 'hotel_id') THEN
            ALTER TABLE section_gallery_items ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_gallery_item_translations') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'section_gallery_item_translations' AND column_name = 'hotel_id') THEN
            ALTER TABLE section_gallery_item_translations ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_overlay_translations') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'section_overlay_translations' AND column_name = 'hotel_id') THEN
            ALTER TABLE section_overlay_translations ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_link_translations') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'section_link_translations' AND column_name = 'hotel_id') THEN
            ALTER TABLE section_link_translations ADD COLUMN hotel_id INT NOT NULL DEFAULT 1;
        END IF;
    END IF;
END $$;

-- =====================================================
-- 2. DROP OLD UNIQUE CONSTRAINTS & CREATE NEW ONES
-- =====================================================

-- admins: username -> (username, hotel_id)
ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_username_key;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'admins_username_hotel_unique') THEN
        ALTER TABLE admins ADD CONSTRAINT admins_username_hotel_unique UNIQUE (username, hotel_id);
    END IF;
END $$;

-- images: (section, position) -> (section, position, hotel_id)
ALTER TABLE images DROP CONSTRAINT IF EXISTS images_section_position_key;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'images_section_position_hotel_unique') THEN
        ALTER TABLE images ADD CONSTRAINT images_section_position_hotel_unique UNIQUE (section, position, hotel_id);
    END IF;
END $$;

-- room_service_categories: code -> (code, hotel_id)
ALTER TABLE room_service_categories DROP CONSTRAINT IF EXISTS room_service_categories_code_key;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'room_service_categories_code_hotel_unique') THEN
        ALTER TABLE room_service_categories ADD CONSTRAINT room_service_categories_code_hotel_unique UNIQUE (code, hotel_id);
    END IF;
END $$;

-- rooms: room_number -> (room_number, hotel_id)
ALTER TABLE rooms DROP CONSTRAINT IF EXISTS rooms_room_number_key;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'rooms_room_number_hotel_unique') THEN
        ALTER TABLE rooms ADD CONSTRAINT rooms_room_number_hotel_unique UNIQUE (room_number, hotel_id);
    END IF;
END $$;

-- pages: slug -> (slug, hotel_id), code -> (code, hotel_id)
ALTER TABLE pages DROP CONSTRAINT IF EXISTS pages_slug_key;
ALTER TABLE pages DROP CONSTRAINT IF EXISTS pages_code_key;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'pages_slug_hotel_unique') THEN
        ALTER TABLE pages ADD CONSTRAINT pages_slug_hotel_unique UNIQUE (slug, hotel_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'pages_code_hotel_unique') THEN
        ALTER TABLE pages ADD CONSTRAINT pages_code_hotel_unique UNIQUE (code, hotel_id);
    END IF;
END $$;

-- settings: setting_key (PK) -> id PK + (setting_key, hotel_id) unique
-- Special handling: settings uses setting_key as primary key
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'settings') THEN
        -- Drop the old PK on setting_key
        IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'settings_pkey' AND conrelid = 'settings'::regclass) THEN
            ALTER TABLE settings DROP CONSTRAINT settings_pkey;
        END IF;
        -- Add id column if not exists
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'settings' AND column_name = 'id') THEN
            ALTER TABLE settings ADD COLUMN id SERIAL PRIMARY KEY;
        END IF;
        -- Add new unique constraint
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'settings_key_hotel_unique') THEN
            ALTER TABLE settings ADD CONSTRAINT settings_key_hotel_unique UNIQUE (setting_key, hotel_id);
        END IF;
    END IF;
END $$;

-- content_sections: code -> (code, hotel_id)
-- Must use CASCADE because content_blocks, section_features, etc. have FKs referencing this unique index.
-- The FKs are dropped and NOT re-added: data integrity is now enforced via hotel_id filtering in queries.
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'content_sections') THEN
        IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'content_sections_code_key') THEN
            ALTER TABLE content_sections DROP CONSTRAINT content_sections_code_key CASCADE;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'content_sections_code_hotel_unique') THEN
            ALTER TABLE content_sections ADD CONSTRAINT content_sections_code_hotel_unique UNIQUE (code, hotel_id);
        END IF;
    END IF;
END $$;

-- room_service_category_translations: (category_code, language_code) -> (category_code, language_code, hotel_id)
ALTER TABLE room_service_category_translations DROP CONSTRAINT IF EXISTS room_service_category_translations_category_code_language__key;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'rs_cat_trans_code_lang_hotel_unique') THEN
        ALTER TABLE room_service_category_translations ADD CONSTRAINT rs_cat_trans_code_lang_hotel_unique UNIQUE (category_code, language_code, hotel_id);
    END IF;
END $$;

-- section_overlay_translations: (section_code, language_code) -> (section_code, language_code, hotel_id)
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_overlay_translations') THEN
        IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'section_overlay_translations_section_code_language_code_key') THEN
            ALTER TABLE section_overlay_translations DROP CONSTRAINT section_overlay_translations_section_code_language_code_key;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'section_overlay_trans_code_lang_hotel_unique') THEN
            ALTER TABLE section_overlay_translations ADD CONSTRAINT section_overlay_trans_code_lang_hotel_unique UNIQUE (section_code, language_code, hotel_id);
        END IF;
    END IF;
END $$;

-- section_link_translations: (section_code, language_code) -> (section_code, language_code, hotel_id)
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_link_translations') THEN
        IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'section_link_translations_section_code_language_code_key') THEN
            ALTER TABLE section_link_translations DROP CONSTRAINT section_link_translations_section_code_language_code_key;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'section_link_trans_code_lang_hotel_unique') THEN
            ALTER TABLE section_link_translations ADD CONSTRAINT section_link_trans_code_lang_hotel_unique UNIQUE (section_code, language_code, hotel_id);
        END IF;
    END IF;
END $$;

-- =====================================================
-- 3. ADD INDEXES ON hotel_id
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_admins_hotel_id ON admins (hotel_id);
CREATE INDEX IF NOT EXISTS idx_persistent_tokens_hotel_id ON persistent_tokens (hotel_id);
CREATE INDEX IF NOT EXISTS idx_login_attempts_hotel_id ON login_attempts (hotel_id);
CREATE INDEX IF NOT EXISTS idx_images_hotel_id ON images (hotel_id);
CREATE INDEX IF NOT EXISTS idx_room_service_items_hotel_id ON room_service_items (hotel_id);
CREATE INDEX IF NOT EXISTS idx_room_service_orders_hotel_id ON room_service_orders (hotel_id);
CREATE INDEX IF NOT EXISTS idx_room_service_categories_hotel_id ON room_service_categories (hotel_id);
CREATE INDEX IF NOT EXISTS idx_room_service_order_items_hotel_id ON room_service_order_items (hotel_id);
CREATE INDEX IF NOT EXISTS idx_guest_messages_hotel_id ON guest_messages (hotel_id);
CREATE INDEX IF NOT EXISTS idx_rooms_hotel_id ON rooms (hotel_id);
CREATE INDEX IF NOT EXISTS idx_housekeeping_logs_hotel_id ON housekeeping_logs (hotel_id);
CREATE INDEX IF NOT EXISTS idx_pages_hotel_id ON pages (hotel_id);
CREATE INDEX IF NOT EXISTS idx_page_translations_hotel_id ON page_translations (hotel_id);
CREATE INDEX IF NOT EXISTS idx_qr_scans_hotel_id ON qr_scans (hotel_id);
CREATE INDEX IF NOT EXISTS idx_push_subscriptions_hotel_id ON push_subscriptions (hotel_id);
CREATE INDEX IF NOT EXISTS idx_room_service_item_translations_hotel_id ON room_service_item_translations (hotel_id);
CREATE INDEX IF NOT EXISTS idx_room_service_category_translations_hotel_id ON room_service_category_translations (hotel_id);

-- Dynamic tables indexes (conditional)
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'settings') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_settings_hotel_id ON settings (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'content_sections') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_content_sections_hotel_id ON content_sections (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'content_blocks') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_content_blocks_hotel_id ON content_blocks (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_features') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_section_features_hotel_id ON section_features (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_feature_translations') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_section_feature_translations_hotel_id ON section_feature_translations (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_services') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_section_services_hotel_id ON section_services (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_service_translations') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_section_service_translations_hotel_id ON section_service_translations (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_gallery_items') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_section_gallery_items_hotel_id ON section_gallery_items (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_gallery_item_translations') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_section_gallery_item_translations_hotel_id ON section_gallery_item_translations (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_overlay_translations') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_section_overlay_translations_hotel_id ON section_overlay_translations (hotel_id)';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'section_link_translations') THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_section_link_translations_hotel_id ON section_link_translations (hotel_id)';
    END IF;
END $$;

-- =====================================================
-- 4. SEED FIRST HOTEL
-- =====================================================

INSERT INTO hotels (id, name, slug, site_url, admin_url, is_active)
VALUES (1, 'Hôtel Corintel', 'corintel', 'https://corintel.hothello.ovh', 'https://admin-corintel.hothello.ovh', TRUE)
ON CONFLICT (slug) DO UPDATE SET site_url = EXCLUDED.site_url, admin_url = EXCLUDED.admin_url;
