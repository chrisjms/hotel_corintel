-- Migration: Create QR scans tracking table (PostgreSQL)
-- Track room service QR code usage for analytics

CREATE TABLE IF NOT EXISTS qr_scans (
    id SERIAL PRIMARY KEY,
    room_id INT NOT NULL,
    scanned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,

    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_qr_scans_room_id ON qr_scans (room_id);
CREATE INDEX IF NOT EXISTS idx_qr_scans_scanned_at ON qr_scans (scanned_at);

-- Add last_scan_at and total_scans columns to rooms table
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS last_scan_at TIMESTAMP DEFAULT NULL;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS total_scans INT DEFAULT 0;
