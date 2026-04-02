-- Migration: Add multi-language support for room service items and categories
-- Run this SQL to create the translation tables (PostgreSQL)

-- Supported languages table (for reference)
CREATE TABLE IF NOT EXISTS supported_languages (
    code VARCHAR(5) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    position INT DEFAULT 0
);

-- Insert supported languages
INSERT INTO supported_languages (code, name, is_default, is_active, position) VALUES
('fr', 'Français', TRUE, TRUE, 1),
('en', 'English', FALSE, TRUE, 2),
('es', 'Español', FALSE, TRUE, 3),
('it', 'Italiano', FALSE, TRUE, 4)
ON CONFLICT (code) DO NOTHING;

-- Room service item translations
CREATE TABLE IF NOT EXISTS room_service_item_translations (
    id SERIAL PRIMARY KEY,
    item_id INT NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (item_id, language_code),
    FOREIGN KEY (item_id) REFERENCES room_service_items(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_item_translations_item_lang ON room_service_item_translations (item_id, language_code);

CREATE TRIGGER update_room_service_item_translations_updated_at BEFORE UPDATE ON room_service_item_translations
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Room service category translations
CREATE TABLE IF NOT EXISTS room_service_category_translations (
    id SERIAL PRIMARY KEY,
    category_code VARCHAR(50) NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (category_code, language_code)
);
CREATE INDEX IF NOT EXISTS idx_category_translations_cat_lang ON room_service_category_translations (category_code, language_code);

CREATE TRIGGER update_room_service_category_translations_updated_at BEFORE UPDATE ON room_service_category_translations
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Migrate existing item data to French translations
INSERT INTO room_service_item_translations (item_id, language_code, name, description)
SELECT id, 'fr', name, description
FROM room_service_items
ON CONFLICT (item_id, language_code) DO UPDATE SET name = EXCLUDED.name, description = EXCLUDED.description;

-- Migrate existing category data to French translations
INSERT INTO room_service_category_translations (category_code, language_code, name)
SELECT code, 'fr', name
FROM room_service_categories
ON CONFLICT (category_code, language_code) DO UPDATE SET name = EXCLUDED.name;
