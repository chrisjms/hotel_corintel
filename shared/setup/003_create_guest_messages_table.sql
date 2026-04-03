-- Guest Messages Table
-- Migration for guest messaging feature (PostgreSQL)

CREATE TABLE IF NOT EXISTS guest_messages (
    id SERIAL PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL,
    guest_name VARCHAR(100) DEFAULT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    subject VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'read', 'in_progress', 'resolved')),
    admin_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_guest_messages_room_number ON guest_messages (room_number);
CREATE INDEX IF NOT EXISTS idx_guest_messages_status ON guest_messages (status);
CREATE INDEX IF NOT EXISTS idx_guest_messages_created_at ON guest_messages (created_at);

CREATE TRIGGER update_guest_messages_updated_at BEFORE UPDATE ON guest_messages
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
