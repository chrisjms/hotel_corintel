-- Guest Messages Table
-- Migration for guest messaging feature
-- Run this SQL in your MySQL database

CREATE TABLE IF NOT EXISTS guest_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL,
    guest_name VARCHAR(100) DEFAULT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    subject VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'in_progress', 'resolved') NOT NULL DEFAULT 'new',
    admin_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_number (room_number),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
