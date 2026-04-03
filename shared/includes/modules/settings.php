<?php
/**
 * Settings Functions
 * Key-value settings system for per-hotel configuration
 */

/**
 * Initialize settings table if it doesn't exist
 */
function initSettingsTable(): void {
    $pdo = getDatabase();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id SERIAL PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT,
            hotel_id INT NOT NULL DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(setting_key, hotel_id)
        )
    ");
}

/**
 * Get a setting value
 */
function getSetting(string $key, $default = null) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? AND hotel_id = ?');
        $stmt->execute([$key, getHotelId()]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Set a setting value
 */
function setSetting(string $key, string $value): bool {
    try {
        initSettingsTable();
        $pdo = getDatabase();
        $stmt = $pdo->prepare('
            INSERT INTO settings (setting_key, setting_value, hotel_id)
            VALUES (?, ?, ?)
            ON CONFLICT (setting_key, hotel_id) DO UPDATE SET setting_value = EXCLUDED.setting_value
        ');
        return $stmt->execute([$key, $value, getHotelId()]);
    } catch (PDOException $e) {
        return false;
    }
}
