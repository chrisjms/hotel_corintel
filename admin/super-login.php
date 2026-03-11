<?php
/**
 * Super Admin Cross-Login Entry Point
 *
 * Validates HMAC-signed tokens from the Super Admin platform
 * and creates a hotel admin session with full access.
 *
 * Token format: base64(payload.signature)
 * Payload: hotel_id|super_admin_id|timestamp|nonce
 * Signature: HMAC-SHA256(payload, shared_secret)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    die('Token manquant.');
}

// Decode token
$decoded = base64_decode($token, true);
if ($decoded === false || strpos($decoded, '.') === false) {
    http_response_code(400);
    die('Token invalide.');
}

[$payload, $signature] = explode('.', $decoded, 2);

// Verify HMAC signature
$expectedSignature = hash_hmac('sha256', $payload, SUPER_ADMIN_CROSS_LOGIN_SECRET);
if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(403);
    die('Signature invalide.');
}

// Parse payload
$parts = explode('|', $payload);
if (count($parts) !== 4) {
    http_response_code(400);
    die('Token malformé.');
}

[$hotelId, $superAdminId, $timestamp, $nonce] = $parts;
$timestamp = (int)$timestamp;

// Check expiry (60 seconds)
if (abs(time() - $timestamp) > 60) {
    http_response_code(403);
    die('Token expiré.');
}

// Check nonce (replay prevention)
$pdo = getDatabase();

// Ensure nonce table exists
$pdo->exec('
    CREATE TABLE IF NOT EXISTS super_admin_login_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token_nonce VARCHAR(64) NOT NULL UNIQUE,
        used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_nonce (token_nonce),
        INDEX idx_used_at (used_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
');

// Clean old nonces (older than 5 minutes)
$pdo->exec('DELETE FROM super_admin_login_tokens WHERE used_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)');

// Try to insert nonce (will fail if already used)
try {
    $stmt = $pdo->prepare('INSERT INTO super_admin_login_tokens (token_nonce) VALUES (?)');
    $stmt->execute([$nonce]);
} catch (PDOException $e) {
    // Duplicate nonce = replay attack
    http_response_code(403);
    die('Token déjà utilisé.');
}

// Create hotel admin session with full access
ensureRoleColumn();

$_SESSION['admin_id'] = 0; // Super admin, not a real hotel admin
$_SESSION['admin_username'] = 'Super Admin';
$_SESSION['admin_role'] = 'admin';
$_SESSION['is_super_admin'] = true;
$_SESSION['super_admin_source_id'] = (int)$superAdminId;
$_SESSION['login_time'] = time();

// Redirect to hotel admin dashboard
header('Location: index.php');
exit;
