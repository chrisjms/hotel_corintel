<?php
/**
 * Database Configuration
 * Hotel Corintel - Admin System
 *
 * Update these values with your OVH MySQL credentials
 */

define('DB_HOST', 'hothelvhothello.mysql.db');
define('DB_NAME', 'hothelvhothello');
define('DB_USER', 'hothelvhothello');
define('DB_PASS', 'Toutesdesputes33');
define('DB_CHARSET', 'utf8mb4');

// Site configuration
define('SITE_URL', 'https://hothello.ovh/corintel');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

/**
 * Get PDO database connection
 */
function getDatabase(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection error. Please check your configuration.');
        }
    }

    return $pdo;
}
