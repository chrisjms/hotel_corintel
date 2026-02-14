<?php
/**
 * Authentication Functions
 * Hotel Corintel - Admin System
 *
 * Session Configuration:
 * - Sessions are persistent indefinitely (no automatic logout)
 * - Logout only occurs via manual logout or session destruction
 * - Session cookies are refreshed on every request to prevent expiration
 */

require_once __DIR__ . '/../config/database.php';

// Session configuration constants
// 10 years in seconds - effectively indefinite (315360000 = 10 * 365 * 24 * 60 * 60)
define('SESSION_LIFETIME', 315360000);
define('SESSION_NAME', 'hotel_admin_session');

// Configure session before starting
if (session_status() === PHP_SESSION_NONE) {
    // Set session name
    session_name(SESSION_NAME);

    // Set session garbage collection lifetime (when session data expires on server)
    // This prevents PHP from cleaning up session files prematurely
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

    // Reduce garbage collection probability to minimize session cleanup
    // gc_probability/gc_divisor = 1/1000 = 0.1% chance per request
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 1000);

    // Determine if connection is secure
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

    // Calculate cookie expiration time (current time + lifetime)
    $cookieExpireTime = time() + SESSION_LIFETIME;

    // Set session cookie parameters with far-future expiration
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,  // Cookie expires in ~10 years
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,           // Only send over HTTPS if available
        'httponly' => true,              // Not accessible via JavaScript (XSS protection)
        'samesite' => 'Lax'              // CSRF protection
    ]);

    session_start();

    // Refresh the session cookie on every request to extend its expiration
    // This ensures the cookie never expires as long as the admin is active
    if (isset($_SESSION['admin_id'])) {
        setcookie(
            session_name(),
            session_id(),
            [
                'expires' => $cookieExpireTime,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
    }

    // Regenerate session ID periodically for security (every 30 minutes)
    // This protects against session fixation attacks while maintaining persistence
    if (isset($_SESSION['last_regeneration'])) {
        if (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    } else {
        $_SESSION['last_regeneration'] = time();
    }
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

    // Regenerate session ID for security
    session_regenerate_id(true);

    return [
        'success' => true,
        'message' => 'Connexion réussie.'
    ];
}

/**
 * Log out the current user
 */
function logout(): void {
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
