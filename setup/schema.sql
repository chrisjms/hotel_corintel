-- Hotel Corintel - Database Schema
-- Run this SQL to set up the database

-- Create database (if needed)
-- CREATE DATABASE IF NOT EXISTS hotel_corintel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE hotel_corintel;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Images table
CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    section VARCHAR(50) NOT NULL,
    position INT NOT NULL DEFAULT 1,
    slot_name VARCHAR(100),
    title VARCHAR(255),
    alt_text VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_position (section, position),
    INDEX idx_section (section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts table (for security)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin (password: admin123 - CHANGE THIS IMMEDIATELY!)
-- Password hash for 'admin123'
INSERT INTO admins (username, password, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hotel-corintel.fr')
ON DUPLICATE KEY UPDATE username = username;

-- Insert default image slots for each section
-- Home page images
INSERT INTO images (filename, section, position, slot_name, title, alt_text) VALUES
('images/acceuil/plan_large.jpg', 'home', 1, 'hero_slide_1', 'Hero Slide 1', 'Hôtel Corintel - Vue principale'),
('images/acceuil/plan_large-2.png', 'home', 2, 'hero_slide_2', 'Hero Slide 2', 'Hôtel Corintel - Vue extérieure'),
('images/acceuil/dehors_nuit.jpg', 'home', 3, 'hero_slide_3', 'Hero Slide 3', 'Hôtel Corintel - Vue de nuit'),
('images/acceuil/bar.jpg', 'home', 4, 'intro_image', 'Image Introduction', 'Bar de l\'hôtel'),
('images/resto/restaurant-hotel-tresses-3.jpg', 'home', 5, 'service_1', 'Service Restaurant', 'Restaurant'),
('images/resto/barlounge.jpg', 'home', 6, 'service_2', 'Service Bar', 'Bar Lounge'),
('images/acceuil/boulodrome.jpg', 'home', 7, 'service_3', 'Service Boulodrome', 'Boulodrome')
ON DUPLICATE KEY UPDATE filename = VALUES(filename);

-- Services page images
INSERT INTO images (filename, section, position, slot_name, title, alt_text) VALUES
('images/resto/restaurant-hotel-tresses-3.jpg', 'services', 1, 'hero', 'Services Hero', 'Restaurant de l\'hôtel'),
('images/resto/21968112.jpg', 'services', 2, 'restaurant_1', 'Restaurant Image 1', 'Salle du restaurant'),
('images/resto/property-amenity-2.jpg', 'services', 3, 'restaurant_2', 'Restaurant Image 2', 'Terrasse du restaurant'),
('images/resto/barlounge.jpg', 'services', 4, 'bar_1', 'Bar Image', 'Bar Lounge'),
('images/acceuil/boulodrome.jpg', 'services', 5, 'boulodrome_1', 'Boulodrome Image', 'Terrain de pétanque'),
('images/acceuil/bar.jpg', 'services', 6, 'parking_1', 'Parking Image', 'Parking de l\'hôtel')
ON DUPLICATE KEY UPDATE filename = VALUES(filename);

-- Activities page images
INSERT INTO images (filename, section, position, slot_name, title, alt_text) VALUES
('images/acceuil/plan_large.jpg', 'activities', 1, 'hero', 'Activities Hero', 'Vue de la région'),
('images/acceuil/plan_large-2.png', 'activities', 2, 'bordeaux', 'Bordeaux', 'Vue de Bordeaux'),
('images/resto/21968112.jpg', 'activities', 3, 'saint_emilion', 'Saint-Émilion', 'Vignobles de Saint-Émilion'),
('images/resto/property-amenity-2.jpg', 'activities', 4, 'wine_tasting', 'Dégustation', 'Dégustation de vins'),
('images/resto/restaurant-hotel-tresses-3.jpg', 'activities', 5, 'wine_cellars', 'Caves', 'Visite de caves'),
('images/resto/barlounge.jpg', 'activities', 6, 'wine_walks', 'Balades', 'Balades dans les vignes'),
('images/acceuil/1759071986_IMG_2108.jpeg', 'activities', 7, 'gastronomy', 'Gastronomie', 'Gastronomie locale'),
('images/acceuil/bar.jpg', 'activities', 8, 'countryside', 'Campagne', 'Campagne bordelaise')
ON DUPLICATE KEY UPDATE filename = VALUES(filename);

-- Contact page images
INSERT INTO images (filename, section, position, slot_name, title, alt_text) VALUES
('images/acceuil/dehors_nuit.jpg', 'contact', 1, 'hero', 'Contact Hero', 'Hôtel de nuit')
ON DUPLICATE KEY UPDATE filename = VALUES(filename);
