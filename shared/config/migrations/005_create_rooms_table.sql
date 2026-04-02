-- =====================================================
-- HOTEL ROOMS MANAGEMENT SYSTEM
-- Migration 005: Create rooms table (PostgreSQL)
-- Hotel Corintel
-- =====================================================

-- Rooms table - Core table for hotel room management
CREATE TABLE IF NOT EXISTS rooms (
    id SERIAL PRIMARY KEY,

    -- Room identification
    room_number VARCHAR(20) NOT NULL UNIQUE,
    floor SMALLINT DEFAULT NULL,

    -- Room characteristics
    room_type VARCHAR(20) NOT NULL DEFAULT 'double' CHECK (room_type IN ('single', 'double', 'twin', 'suite', 'family', 'accessible')),
    capacity SMALLINT NOT NULL DEFAULT 2,
    bed_count SMALLINT NOT NULL DEFAULT 1,
    surface_area DECIMAL(5,2) DEFAULT NULL,

    -- Operational status
    status VARCHAR(20) NOT NULL DEFAULT 'available' CHECK (status IN ('available', 'occupied', 'maintenance', 'out_of_order')),
    housekeeping_status VARCHAR(20) NOT NULL DEFAULT 'cleaned' CHECK (housekeeping_status IN ('pending', 'in_progress', 'cleaned', 'inspected')),

    -- Tracking
    last_cleaned_at TIMESTAMP DEFAULT NULL,
    last_inspection_at TIMESTAMP DEFAULT NULL,
    last_checkout_at TIMESTAMP DEFAULT NULL,

    -- Features (JSON for flexibility)
    amenities JSONB DEFAULT NULL,

    -- Notes and metadata
    notes TEXT DEFAULT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_rooms_room_number ON rooms (room_number);
CREATE INDEX IF NOT EXISTS idx_rooms_floor ON rooms (floor);
CREATE INDEX IF NOT EXISTS idx_rooms_status ON rooms (status);
CREATE INDEX IF NOT EXISTS idx_rooms_housekeeping ON rooms (housekeeping_status);
CREATE INDEX IF NOT EXISTS idx_rooms_room_type ON rooms (room_type);
CREATE INDEX IF NOT EXISTS idx_rooms_active ON rooms (is_active);
CREATE INDEX IF NOT EXISTS idx_rooms_floor_status ON rooms (floor, status);
CREATE INDEX IF NOT EXISTS idx_rooms_housekeeping_priority ON rooms (housekeeping_status, last_checkout_at);

CREATE TRIGGER update_rooms_updated_at BEFORE UPDATE ON rooms
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- ROOM TYPES REFERENCE TABLE (for future extensibility)
-- =====================================================
CREATE TABLE IF NOT EXISTS room_types (
    id SERIAL PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    base_capacity SMALLINT NOT NULL DEFAULT 2,
    base_price DECIMAL(10,2) DEFAULT NULL,
    position INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_room_types_code ON room_types (code);
CREATE INDEX IF NOT EXISTS idx_room_types_active ON room_types (is_active);
CREATE INDEX IF NOT EXISTS idx_room_types_position ON room_types (position);

CREATE TRIGGER update_room_types_updated_at BEFORE UPDATE ON room_types
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert default room types
INSERT INTO room_types (code, name, description, base_capacity, position) VALUES
('single', 'Chambre Simple', 'Chambre pour une personne avec lit simple', 1, 1),
('double', 'Chambre Double', 'Chambre avec lit double pour deux personnes', 2, 2),
('twin', 'Chambre Twin', 'Chambre avec deux lits simples', 2, 3),
('suite', 'Suite', 'Suite spacieuse avec salon séparé', 2, 4),
('family', 'Chambre Familiale', 'Grande chambre pour famille', 4, 5),
('accessible', 'Chambre Accessible', 'Chambre adaptée aux personnes à mobilité réduite', 2, 6)
ON CONFLICT (code) DO UPDATE SET name = EXCLUDED.name, description = EXCLUDED.description;

-- =====================================================
-- HOUSEKEEPING LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS housekeeping_logs (
    id SERIAL PRIMARY KEY,
    room_id INT NOT NULL,
    action VARCHAR(30) NOT NULL CHECK (action IN ('cleaning_started', 'cleaning_completed', 'inspection_passed', 'inspection_failed', 'maintenance_requested', 'maintenance_completed', 'status_changed')),
    previous_status VARCHAR(30) DEFAULT NULL,
    new_status VARCHAR(30) DEFAULT NULL,
    performed_by VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_housekeeping_logs_room ON housekeeping_logs (room_id);
CREATE INDEX IF NOT EXISTS idx_housekeeping_logs_action ON housekeeping_logs (action);
CREATE INDEX IF NOT EXISTS idx_housekeeping_logs_created ON housekeeping_logs (created_at);
CREATE INDEX IF NOT EXISTS idx_housekeeping_logs_room_created ON housekeeping_logs (room_id, created_at);
