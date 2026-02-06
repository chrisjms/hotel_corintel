-- Migration: Add multi-language support for room service items and categories
-- Run this SQL to create the translation tables

-- Supported languages table (for reference)
CREATE TABLE IF NOT EXISTS supported_languages (
    code VARCHAR(5) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    position INT DEFAULT 0
);

-- Insert supported languages
INSERT IGNORE INTO supported_languages (code, name, is_default, is_active, position) VALUES
('fr', 'Français', 1, 1, 1),
('en', 'English', 0, 1, 2),
('es', 'Español', 0, 1, 3),
('it', 'Italiano', 0, 1, 4);

-- Room service item translations
CREATE TABLE IF NOT EXISTS room_service_item_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_item_lang (item_id, language_code),
    FOREIGN KEY (item_id) REFERENCES room_service_items(id) ON DELETE CASCADE,
    INDEX idx_item_lang (item_id, language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room service category translations
CREATE TABLE IF NOT EXISTS room_service_category_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(50) NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_category_lang (category_code, language_code),
    INDEX idx_category_lang (category_code, language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing item data to French translations
INSERT INTO room_service_item_translations (item_id, language_code, name, description)
SELECT id, 'fr', name, description
FROM room_service_items
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- Migrate existing category data to French translations
INSERT INTO room_service_category_translations (category_code, language_code, name)
SELECT code, 'fr', name
FROM room_service_categories
ON DUPLICATE KEY UPDATE name = VALUES(name);
