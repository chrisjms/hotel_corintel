<?php
/**
 * Database Configuration
 * Hotel Corintel - Admin System
 *
 * Update these values with your PostgreSQL credentials
 */

define('DB_HOST', 'aws-0-eu-west-1.pooler.supabase.com');
define('DB_PORT', '6543'); // 6543 = Supabase pgBouncer pooler, 5432 = direct (often blocked)
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres.afiixohjgnguomglpulr');
define('DB_PASS', 'Toutesdesputes33');

// Site configuration
define('SITE_URL', 'https://hothello.ovh/corintel');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Super Admin cross-login secret (CHANGE THIS to a unique random string)
define('SUPER_ADMIN_CROSS_LOGIN_SECRET', 'CHANGE_ME_TO_A_RANDOM_SECRET_STRING_64_CHARS_MINIMUM');

/**
 * Get PDO database connection
 */
function getDatabase(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
                DB_HOST,
                DB_PORT,
                DB_NAME
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true, // required for pgBouncer transaction mode (port 6543)
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET client_encoding = 'UTF8'");
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection error. Please check your configuration.');
        }
    }

    return $pdo;
}
