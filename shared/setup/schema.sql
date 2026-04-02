-- Hotel Platform - Database Schema (PostgreSQL)
-- Multi-tenant: all per-hotel tables include hotel_id column
-- Run this SQL to set up the database

-- Trigger function for auto-updating updated_at columns
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- SUPER ADMIN SYSTEM (global tables - no hotel_id)
-- =====================================================

-- Super admin users (completely separate from hotel admins)
CREATE TABLE IF NOT EXISTS super_admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_super_admins_username ON super_admins (username);

-- Persistent auth tokens for super admin sessions
CREATE TABLE IF NOT EXISTS super_persistent_tokens (
    id SERIAL PRIMARY KEY,
    super_admin_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (super_admin_id) REFERENCES super_admins(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_super_persistent_tokens_admin ON super_persistent_tokens (super_admin_id);
CREATE INDEX IF NOT EXISTS idx_super_persistent_tokens_token ON super_persistent_tokens (token_hash);

-- Login attempts for super admin (stricter rate limiting)
CREATE TABLE IF NOT EXISTS super_login_attempts (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_super_login_attempts_ip_time ON super_login_attempts (ip_address, attempted_at);

-- Hotels registry
CREATE TABLE IF NOT EXISTS hotels (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    site_url VARCHAR(500),
    admin_url VARCHAR(500),
    db_host VARCHAR(200) NULL,
    db_name VARCHAR(200) NULL,
    db_user VARCHAR(200) NULL,
    db_pass VARCHAR(500) NULL,
    cross_login_secret VARCHAR(128) NULL,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_hotels_slug ON hotels (slug);
CREATE INDEX IF NOT EXISTS idx_hotels_active ON hotels (is_active);

CREATE TRIGGER update_hotels_updated_at BEFORE UPDATE ON hotels
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Cross-login token nonces (replay prevention)
CREATE TABLE IF NOT EXISTS super_admin_login_tokens (
    id SERIAL PRIMARY KEY,
    token_nonce VARCHAR(64) NOT NULL UNIQUE,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_super_admin_login_tokens_nonce ON super_admin_login_tokens (token_nonce);
CREATE INDEX IF NOT EXISTS idx_super_admin_login_tokens_used_at ON super_admin_login_tokens (used_at);

-- Audit log for super admin actions
CREATE TABLE IF NOT EXISTS super_admin_audit_log (
    id SERIAL PRIMARY KEY,
    super_admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    hotel_id INT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_super_admin_audit_log_admin ON super_admin_audit_log (super_admin_id);
CREATE INDEX IF NOT EXISTS idx_super_admin_audit_log_action ON super_admin_audit_log (action);
CREATE INDEX IF NOT EXISTS idx_super_admin_audit_log_hotel ON super_admin_audit_log (hotel_id);
CREATE INDEX IF NOT EXISTS idx_super_admin_audit_log_created ON super_admin_audit_log (created_at);

-- Default super admin (password: superadmin123 - CHANGE THIS IMMEDIATELY!)
INSERT INTO super_admins (username, password, email) VALUES
('superadmin', '$2y$12$3/0cErCh4bDllLP3R6uaZu0m7rktACgRUD9tygpJC0gW88q7h9TDK', 'super@platform.com')
ON CONFLICT (username) DO NOTHING;

-- Seed first hotel
INSERT INTO hotels (id, name, slug, site_url, admin_url, is_active)
VALUES (1, 'Hôtel Corintel', 'corintel', 'https://corintel.hothello.ovh', 'https://admin-corintel.hothello.ovh', TRUE)
ON CONFLICT (slug) DO UPDATE SET site_url = EXCLUDED.site_url, admin_url = EXCLUDED.admin_url;

-- =====================================================
-- PER-HOTEL TABLES (all include hotel_id)
-- =====================================================

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    hotel_id INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    UNIQUE (username, hotel_id)
);
CREATE INDEX IF NOT EXISTS idx_admins_username ON admins (username);
CREATE INDEX IF NOT EXISTS idx_admins_hotel_id ON admins (hotel_id);

-- Images table
CREATE TABLE IF NOT EXISTS images (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    section VARCHAR(50) NOT NULL,
    position INT NOT NULL DEFAULT 1,
    slot_name VARCHAR(100),
    title VARCHAR(255),
    alt_text VARCHAR(255),
    hotel_id INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (section, position, hotel_id)
);
CREATE INDEX IF NOT EXISTS idx_images_section ON images (section);
CREATE INDEX IF NOT EXISTS idx_images_hotel_id ON images (hotel_id);

CREATE TRIGGER update_images_updated_at BEFORE UPDATE ON images
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Login attempts table (for security)
CREATE TABLE IF NOT EXISTS login_attempts (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    hotel_id INT NOT NULL DEFAULT 1,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts (ip_address, attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_hotel_id ON login_attempts (hotel_id);

-- Persistent authentication tokens
CREATE TABLE IF NOT EXISTS persistent_tokens (
    id SERIAL PRIMARY KEY,
    admin_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    hotel_id INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_persistent_tokens_admin ON persistent_tokens (admin_id);
CREATE INDEX IF NOT EXISTS idx_persistent_tokens_token ON persistent_tokens (token_hash);
CREATE INDEX IF NOT EXISTS idx_persistent_tokens_hotel_id ON persistent_tokens (hotel_id);

-- Insert default admin for hotel 1 (password: admin123 - CHANGE THIS IMMEDIATELY!)
INSERT INTO admins (username, password, email, role, hotel_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hotel-corintel.fr', 'admin', 1)
ON CONFLICT (username, hotel_id) DO NOTHING;

-- Insert default image slots for each section (hotel_id = 1)
INSERT INTO images (filename, section, position, slot_name, title, alt_text, hotel_id) VALUES
('images/acceuil/plan_large.jpg', 'home', 1, 'hero_slide_1', 'Hero Slide 1', 'Hôtel Corintel - Vue principale', 1),
('images/acceuil/plan_large-2.png', 'home', 2, 'hero_slide_2', 'Hero Slide 2', 'Hôtel Corintel - Vue extérieure', 1),
('images/acceuil/dehors_nuit.jpg', 'home', 3, 'hero_slide_3', 'Hero Slide 3', 'Hôtel Corintel - Vue de nuit', 1),
('images/acceuil/bar.jpg', 'home', 4, 'intro_image', 'Image Introduction', 'Bar de l''hôtel', 1),
('images/resto/restaurant-hotel-tresses-3.jpg', 'home', 5, 'service_1', 'Service Restaurant', 'Restaurant', 1),
('images/resto/barlounge.jpg', 'home', 6, 'service_2', 'Service Bar', 'Bar Lounge', 1),
('images/acceuil/boulodrome.jpg', 'home', 7, 'service_3', 'Service Boulodrome', 'Boulodrome', 1)
ON CONFLICT (section, position, hotel_id) DO UPDATE SET filename = EXCLUDED.filename;

INSERT INTO images (filename, section, position, slot_name, title, alt_text, hotel_id) VALUES
('images/resto/restaurant-hotel-tresses-3.jpg', 'services', 1, 'hero', 'Services Hero', 'Restaurant de l''hôtel', 1),
('images/resto/21968112.jpg', 'services', 2, 'restaurant_1', 'Restaurant Image 1', 'Salle du restaurant', 1),
('images/resto/property-amenity-2.jpg', 'services', 3, 'restaurant_2', 'Restaurant Image 2', 'Terrasse du restaurant', 1),
('images/resto/barlounge.jpg', 'services', 4, 'bar_1', 'Bar Image', 'Bar Lounge', 1),
('images/acceuil/boulodrome.jpg', 'services', 5, 'boulodrome_1', 'Boulodrome Image', 'Terrain de pétanque', 1),
('images/acceuil/bar.jpg', 'services', 6, 'parking_1', 'Parking Image', 'Parking de l''hôtel', 1)
ON CONFLICT (section, position, hotel_id) DO UPDATE SET filename = EXCLUDED.filename;

INSERT INTO images (filename, section, position, slot_name, title, alt_text, hotel_id) VALUES
('images/acceuil/plan_large.jpg', 'activities', 1, 'hero', 'Activities Hero', 'Vue de la région', 1),
('images/acceuil/plan_large-2.png', 'activities', 2, 'bordeaux', 'Bordeaux', 'Vue de Bordeaux', 1),
('images/resto/21968112.jpg', 'activities', 3, 'saint_emilion', 'Saint-Émilion', 'Vignobles de Saint-Émilion', 1),
('images/resto/property-amenity-2.jpg', 'activities', 4, 'wine_tasting', 'Dégustation', 'Dégustation de vins', 1),
('images/resto/restaurant-hotel-tresses-3.jpg', 'activities', 5, 'wine_cellars', 'Caves', 'Visite de caves', 1),
('images/resto/barlounge.jpg', 'activities', 6, 'wine_walks', 'Balades', 'Balades dans les vignes', 1),
('images/acceuil/1759071986_IMG_2108.jpeg', 'activities', 7, 'gastronomy', 'Gastronomie', 'Gastronomie locale', 1),
('images/acceuil/bar.jpg', 'activities', 8, 'countryside', 'Campagne', 'Campagne bordelaise', 1)
ON CONFLICT (section, position, hotel_id) DO UPDATE SET filename = EXCLUDED.filename;

INSERT INTO images (filename, section, position, slot_name, title, alt_text, hotel_id) VALUES
('images/acceuil/dehors_nuit.jpg', 'contact', 1, 'hero', 'Contact Hero', 'Hôtel de nuit', 1)
ON CONFLICT (section, position, hotel_id) DO UPDATE SET filename = EXCLUDED.filename;

-- =====================================================
-- ROOM SERVICE SYSTEM
-- =====================================================

-- Room service items (menu items)
CREATE TABLE IF NOT EXISTS room_service_items (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    category VARCHAR(50) DEFAULT 'general',
    is_active BOOLEAN DEFAULT TRUE,
    position INT DEFAULT 0,
    hotel_id INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_room_service_items_active ON room_service_items (is_active);
CREATE INDEX IF NOT EXISTS idx_room_service_items_category ON room_service_items (category);
CREATE INDEX IF NOT EXISTS idx_room_service_items_position ON room_service_items (position);
CREATE INDEX IF NOT EXISTS idx_room_service_items_hotel_id ON room_service_items (hotel_id);

CREATE TRIGGER update_room_service_items_updated_at BEFORE UPDATE ON room_service_items
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Room service orders
CREATE TABLE IF NOT EXISTS room_service_orders (
    id SERIAL PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL,
    guest_name VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL DEFAULT 'room_charge' CHECK (payment_method IN ('cash', 'card', 'room_charge')),
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'preparing', 'delivered', 'cancelled')),
    delivery_datetime TIMESTAMP NOT NULL,
    notes TEXT,
    hotel_id INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_room_service_orders_room ON room_service_orders (room_number);
CREATE INDEX IF NOT EXISTS idx_room_service_orders_status ON room_service_orders (status);
CREATE INDEX IF NOT EXISTS idx_room_service_orders_created ON room_service_orders (created_at);
CREATE INDEX IF NOT EXISTS idx_room_service_orders_delivery ON room_service_orders (delivery_datetime);
CREATE INDEX IF NOT EXISTS idx_room_service_orders_hotel_id ON room_service_orders (hotel_id);

CREATE TRIGGER update_room_service_orders_updated_at BEFORE UPDATE ON room_service_orders
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Room service categories with time availability
CREATE TABLE IF NOT EXISTS room_service_categories (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    time_start TIME DEFAULT NULL,
    time_end TIME DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    position INT DEFAULT 0,
    hotel_id INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (code, hotel_id)
);
CREATE INDEX IF NOT EXISTS idx_room_service_categories_code ON room_service_categories (code);
CREATE INDEX IF NOT EXISTS idx_room_service_categories_active ON room_service_categories (is_active);
CREATE INDEX IF NOT EXISTS idx_room_service_categories_position ON room_service_categories (position);
CREATE INDEX IF NOT EXISTS idx_room_service_categories_hotel_id ON room_service_categories (hotel_id);

CREATE TRIGGER update_room_service_categories_updated_at BEFORE UPDATE ON room_service_categories
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert default categories (hotel_id = 1)
INSERT INTO room_service_categories (code, name, time_start, time_end, position, hotel_id) VALUES
('breakfast', 'Petit-déjeuner', '07:00', '11:00', 1, 1),
('lunch', 'Déjeuner', '12:00', '14:30', 2, 1),
('dinner', 'Dîner', '19:00', '22:00', 3, 1),
('snacks', 'Snacks', NULL, NULL, 4, 1),
('drinks', 'Boissons', NULL, NULL, 5, 1),
('desserts', 'Desserts', '12:00', '22:00', 6, 1),
('general', 'Général', NULL, NULL, 7, 1)
ON CONFLICT (code, hotel_id) DO UPDATE SET name = EXCLUDED.name;

-- Room service order items (line items)
CREATE TABLE IF NOT EXISTS room_service_order_items (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    item_price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(10, 2) NOT NULL,
    hotel_id INT NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES room_service_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES room_service_items(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_room_service_order_items_order ON room_service_order_items (order_id);
CREATE INDEX IF NOT EXISTS idx_room_service_order_items_hotel_id ON room_service_order_items (hotel_id);

-- Guest messages
CREATE TABLE IF NOT EXISTS guest_messages (
    id SERIAL PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL,
    guest_name VARCHAR(100) DEFAULT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    subject VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'read', 'in_progress', 'resolved')),
    admin_notes TEXT DEFAULT NULL,
    hotel_id INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_guest_messages_room_number ON guest_messages (room_number);
CREATE INDEX IF NOT EXISTS idx_guest_messages_status ON guest_messages (status);
CREATE INDEX IF NOT EXISTS idx_guest_messages_created_at ON guest_messages (created_at);
CREATE INDEX IF NOT EXISTS idx_guest_messages_hotel_id ON guest_messages (hotel_id);

CREATE TRIGGER update_guest_messages_updated_at BEFORE UPDATE ON guest_messages
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
