-- =====================================================
-- HOTEL ROOMS MANAGEMENT SYSTEM
-- Migration 005: Create rooms table
-- Hotel Corintel
-- =====================================================

-- Rooms table - Core table for hotel room management
-- Designed for scalability: housekeeping, reservations, room planning
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Room identification
    room_number VARCHAR(20) NOT NULL UNIQUE COMMENT 'Unique room identifier (e.g., 101, 201A, Suite-1)',
    floor TINYINT UNSIGNED DEFAULT NULL COMMENT 'Floor number (NULL for ground floor or unspecified)',

    -- Room characteristics
    room_type ENUM('single', 'double', 'twin', 'suite', 'family', 'accessible') NOT NULL DEFAULT 'double' COMMENT 'Type of room',
    capacity TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT 'Maximum number of guests',
    bed_count TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Number of beds',
    surface_area DECIMAL(5,2) DEFAULT NULL COMMENT 'Room surface in square meters',

    -- Operational status
    status ENUM('available', 'occupied', 'maintenance', 'out_of_order') NOT NULL DEFAULT 'available' COMMENT 'Current room availability status',
    housekeeping_status ENUM('pending', 'in_progress', 'cleaned', 'inspected') NOT NULL DEFAULT 'cleaned' COMMENT 'Housekeeping state',

    -- Tracking
    last_cleaned_at DATETIME DEFAULT NULL COMMENT 'Last time room was cleaned',
    last_inspection_at DATETIME DEFAULT NULL COMMENT 'Last housekeeping inspection',
    last_checkout_at DATETIME DEFAULT NULL COMMENT 'Last guest checkout (for housekeeping prioritization)',

    -- Features (JSON for flexibility)
    amenities JSON DEFAULT NULL COMMENT 'Room amenities: {"wifi": true, "minibar": true, "balcony": false, ...}',

    -- Notes and metadata
    notes TEXT DEFAULT NULL COMMENT 'Internal notes about the room',
    is_active BOOLEAN NOT NULL DEFAULT 1 COMMENT 'Soft delete / deactivation flag',

    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes for common queries
    INDEX idx_room_number (room_number),
    INDEX idx_floor (floor),
    INDEX idx_status (status),
    INDEX idx_housekeeping (housekeeping_status),
    INDEX idx_room_type (room_type),
    INDEX idx_active (is_active),
    INDEX idx_floor_status (floor, status),
    INDEX idx_housekeeping_priority (housekeeping_status, last_checkout_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ROOM TYPES REFERENCE TABLE (for future extensibility)
-- Allows dynamic room type management
-- =====================================================
CREATE TABLE IF NOT EXISTS room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE COMMENT 'Type code used in rooms table',
    name VARCHAR(100) NOT NULL COMMENT 'Display name',
    description TEXT DEFAULT NULL,
    base_capacity TINYINT UNSIGNED NOT NULL DEFAULT 2,
    base_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Base price per night (for future pricing)',
    position INT NOT NULL DEFAULT 0 COMMENT 'Display order',
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default room types
INSERT INTO room_types (code, name, description, base_capacity, position) VALUES
('single', 'Chambre Simple', 'Chambre pour une personne avec lit simple', 1, 1),
('double', 'Chambre Double', 'Chambre avec lit double pour deux personnes', 2, 2),
('twin', 'Chambre Twin', 'Chambre avec deux lits simples', 2, 3),
('suite', 'Suite', 'Suite spacieuse avec salon séparé', 2, 4),
('family', 'Chambre Familiale', 'Grande chambre pour famille', 4, 5),
('accessible', 'Chambre Accessible', 'Chambre adaptée aux personnes à mobilité réduite', 2, 6)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- =====================================================
-- HOUSEKEEPING LOGS TABLE (prepared for future use)
-- Tracks all housekeeping activities
-- =====================================================
CREATE TABLE IF NOT EXISTS housekeeping_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    action ENUM('cleaning_started', 'cleaning_completed', 'inspection_passed', 'inspection_failed', 'maintenance_requested', 'maintenance_completed', 'status_changed') NOT NULL,
    previous_status VARCHAR(30) DEFAULT NULL,
    new_status VARCHAR(30) DEFAULT NULL,
    performed_by VARCHAR(100) DEFAULT NULL COMMENT 'Staff member name or ID',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_room (room_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_room_created (room_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample data for testing (optional - comment out for production)
-- =====================================================
-- INSERT INTO rooms (room_number, floor, room_type, capacity, bed_count, status, housekeeping_status) VALUES
-- ('101', 1, 'double', 2, 1, 'available', 'cleaned'),
-- ('102', 1, 'double', 2, 1, 'available', 'cleaned'),
-- ('103', 1, 'twin', 2, 2, 'available', 'cleaned'),
-- ('104', 1, 'accessible', 2, 1, 'available', 'cleaned'),
-- ('201', 2, 'double', 2, 1, 'available', 'cleaned'),
-- ('202', 2, 'double', 2, 1, 'available', 'cleaned'),
-- ('203', 2, 'suite', 2, 1, 'available', 'cleaned'),
-- ('301', 3, 'family', 4, 2, 'available', 'cleaned'),
-- ('302', 3, 'suite', 2, 1, 'available', 'cleaned')
-- ON DUPLICATE KEY UPDATE room_number = VALUES(room_number);
