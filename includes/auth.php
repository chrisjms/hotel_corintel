<?php
/**
 * Authentication Functions
 * Hotel Corintel - Admin System
 *
 * Persistence Strategy:
 * - Uses a database-stored persistent token ("remember me" token)
 * - Token is stored in a long-lived cookie (10 years)
 * - If PHP session is lost (server GC), the token auto-restores the session
 * - Only manual logout destroys the token
 * - No timeouts, no automatic logout, no session regeneration
 */

require_once __DIR__ . '/../config/database.php';

// Token configuration constants
// 10 years in seconds - effectively indefinite
define('AUTH_TOKEN_LIFETIME', 315360000);
define('AUTH_TOKEN_NAME', 'hotel_admin_auth');
define('SESSION_NAME', 'hotel_admin_session');

// Configure session before starting
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);

    // Try to set long session lifetime (may be ignored by shared hosting)
    @ini_set('session.gc_maxlifetime', AUTH_TOKEN_LIFETIME);
    @ini_set('session.gc_probability', 0); // Disable GC entirely if possible

    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

    session_set_cookie_params([
        'lifetime' => AUTH_TOKEN_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();

    // If session exists, refresh the cookie
    if (isset($_SESSION['admin_id'])) {
        setcookie(
            session_name(),
            session_id(),
            [
                'expires' => time() + AUTH_TOKEN_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    // If session is lost but persistent token exists, restore the session
    elseif (isset($_COOKIE[AUTH_TOKEN_NAME])) {
        restoreSessionFromToken($_COOKIE[AUTH_TOKEN_NAME]);
    }
}

/**
 * Ensure persistent_tokens table exists
 */
function ensurePersistentTokensTable(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $pdo = getDatabase();
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS persistent_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin (admin_id),
            INDEX idx_token (token_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
}

/**
 * Restore session from persistent token (auto-login)
 */
function restoreSessionFromToken(string $token): bool {
    if (empty($token)) {
        return false;
    }

    ensurePersistentTokensTable();
    $pdo = getDatabase();

    // Find valid token
    $stmt = $pdo->prepare('
        SELECT pt.admin_id, a.username
        FROM persistent_tokens pt
        JOIN admins a ON a.id = pt.admin_id
        WHERE pt.token_hash = ?
    ');
    $stmt->execute([hash('sha256', $token)]);
    $result = $stmt->fetch();

    if ($result) {
        // Restore session
        $_SESSION['admin_id'] = $result['admin_id'];
        $_SESSION['admin_username'] = $result['username'];
        $_SESSION['restored_from_token'] = true;

        // Update token last used time
        $stmt = $pdo->prepare('UPDATE persistent_tokens SET last_used_at = NOW() WHERE token_hash = ?');
        $stmt->execute([hash('sha256', $token)]);

        return true;
    }

    // Invalid token - clear the cookie
    clearPersistentTokenCookie();
    return false;
}

/**
 * Create a persistent token for the admin
 */
function createPersistentToken(int $adminId): string {
    ensurePersistentTokensTable();
    $pdo = getDatabase();

    // Generate secure random token
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    // Remove any existing tokens for this admin (one token per admin)
    $stmt = $pdo->prepare('DELETE FROM persistent_tokens WHERE admin_id = ?');
    $stmt->execute([$adminId]);

    // Store new token
    $stmt = $pdo->prepare('
        INSERT INTO persistent_tokens (admin_id, token_hash, created_at, last_used_at)
        VALUES (?, ?, NOW(), NOW())
    ');
    $stmt->execute([$adminId, $tokenHash]);

    // Set cookie
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    setcookie(
        AUTH_TOKEN_NAME,
        $token,
        [
            'expires' => time() + AUTH_TOKEN_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );

    return $token;
}

/**
 * Delete persistent token (on logout)
 */
function deletePersistentToken(int $adminId): void {
    ensurePersistentTokensTable();
    $pdo = getDatabase();
    $stmt = $pdo->prepare('DELETE FROM persistent_tokens WHERE admin_id = ?');
    $stmt->execute([$adminId]);
    clearPersistentTokenCookie();
}

/**
 * Clear the persistent token cookie
 */
function clearPersistentTokenCookie(): void {
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    setcookie(
        AUTH_TOKEN_NAME,
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Check login attempts to prevent brute force
 */
function checkLoginAttempts(string $ip): bool {
    $pdo = getDatabase();

    // Clean old attempts (older than 15 minutes)
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $stmt->execute();

    // Count recent attempts
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $stmt->execute([$ip]);
    $attempts = $stmt->fetchColumn();

    // Allow max 5 attempts per 15 minutes
    return $attempts < 5;
}

/**
 * Record a failed login attempt
 */
function recordLoginAttempt(string $ip): void {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address) VALUES (?)');
    $stmt->execute([$ip]);
}

/**
 * Clear login attempts for an IP
 */
function clearLoginAttempts(string $ip): void {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
    $stmt->execute([$ip]);
}

/**
 * Attempt to log in a user
 */
function attemptLogin(string $username, string $password): array {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Check for too many attempts
    if (!checkLoginAttempts($ip)) {
        return [
            'success' => false,
            'message' => 'Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.'
        ];
    }

    // Validate input
    $username = trim($username);
    if (empty($username) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Veuillez remplir tous les champs.'
        ];
    }

    $pdo = getDatabase();

    // Find user
    $stmt = $pdo->prepare('SELECT id, username, password FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        recordLoginAttempt($ip);
        return [
            'success' => false,
            'message' => 'Identifiants incorrects.'
        ];
    }

    // Clear login attempts on successful login
    clearLoginAttempts($ip);

    // Update last login
    $stmt = $pdo->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?');
    $stmt->execute([$admin['id']]);

    // Set session
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['login_time'] = time();

    // Regenerate session ID for security (only on login, not periodically)
    session_regenerate_id(true);

    // Create persistent token for automatic re-authentication
    // This ensures the admin stays logged in even if PHP session is garbage collected
    createPersistentToken($admin['id']);

    return [
        'success' => true,
        'message' => 'Connexion réussie.'
    ];
}

/**
 * Log out the current user
 */
function logout(): void {
    // Delete persistent token from database and cookie
    if (isset($_SESSION['admin_id'])) {
        deletePersistentToken($_SESSION['admin_id']);
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Get current admin info
 */
function getCurrentAdmin(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT id, username, email, last_login FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Change admin password
 */
function changePassword(int $adminId, string $currentPassword, string $newPassword): array {
    $pdo = getDatabase();

    // Get current hash
    $stmt = $pdo->prepare('SELECT password FROM admins WHERE id = ?');
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($currentPassword, $admin['password'])) {
        return [
            'success' => false,
            'message' => 'Mot de passe actuel incorrect.'
        ];
    }

    // Validate new password
    if (strlen($newPassword) < 8) {
        return [
            'success' => false,
            'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'
        ];
    }

    // Update password
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?');
    $stmt->execute([$hash, $adminId]);

    return [
        'success' => true,
        'message' => 'Mot de passe modifié avec succès.'
    ];
}

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
