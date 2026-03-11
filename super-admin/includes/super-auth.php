<?php
/**
 * Super Admin Authentication
 * Completely separate from hotel admin auth (includes/auth.php)
 */

require_once __DIR__ . '/../../config/database.php';

/**
 * Get the platform (Super Admin) database connection.
 * Today: same DB as hotel. Later: point to a dedicated platform DB.
 */
function getSuperDatabase(): PDO {
    return getDatabase();
}

define('SUPER_AUTH_TOKEN_LIFETIME', 315360000); // 10 years
define('SUPER_AUTH_TOKEN_NAME', 'super_admin_auth');
define('SUPER_SESSION_NAME', 'super_admin_session');

// Configure session before starting
if (session_status() === PHP_SESSION_NONE) {
    session_name(SUPER_SESSION_NAME);

    @ini_set('session.gc_maxlifetime', SUPER_AUTH_TOKEN_LIFETIME);
    @ini_set('session.gc_probability', 0);

    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

    session_set_cookie_params([
        'lifetime' => SUPER_AUTH_TOKEN_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();

    if (isset($_SESSION['super_admin_id'])) {
        setcookie(
            session_name(),
            session_id(),
            [
                'expires' => time() + SUPER_AUTH_TOKEN_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    } elseif (isset($_COOKIE[SUPER_AUTH_TOKEN_NAME])) {
        superRestoreSessionFromToken($_COOKIE[SUPER_AUTH_TOKEN_NAME]);
    }
}

/**
 * Ensure super admin tables exist
 */
function ensureSuperAdminTables(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $pdo = getSuperDatabase();

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS super_admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME NULL,
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS super_persistent_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            super_admin_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_super_admin (super_admin_id),
            INDEX idx_token (token_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS super_login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_time (ip_address, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
}

/**
 * Restore session from persistent token
 */
function superRestoreSessionFromToken(string $token): bool {
    if (empty($token)) return false;

    ensureSuperAdminTables();
    $pdo = getSuperDatabase();

    $stmt = $pdo->prepare('
        SELECT pt.super_admin_id, a.username
        FROM super_persistent_tokens pt
        JOIN super_admins a ON a.id = pt.super_admin_id
        WHERE pt.token_hash = ? AND a.is_active = 1
    ');
    $stmt->execute([hash('sha256', $token)]);
    $result = $stmt->fetch();

    if ($result) {
        $_SESSION['super_admin_id'] = $result['super_admin_id'];
        $_SESSION['super_admin_username'] = $result['username'];

        $stmt = $pdo->prepare('UPDATE super_persistent_tokens SET last_used_at = NOW() WHERE token_hash = ?');
        $stmt->execute([hash('sha256', $token)]);

        return true;
    }

    superClearPersistentTokenCookie();
    return false;
}

/**
 * Create persistent token
 */
function superCreatePersistentToken(int $adminId): string {
    ensureSuperAdminTables();
    $pdo = getSuperDatabase();

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare('DELETE FROM super_persistent_tokens WHERE super_admin_id = ?');
    $stmt->execute([$adminId]);

    $stmt = $pdo->prepare('
        INSERT INTO super_persistent_tokens (super_admin_id, token_hash, created_at, last_used_at)
        VALUES (?, ?, NOW(), NOW())
    ');
    $stmt->execute([$adminId, $tokenHash]);

    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    setcookie(
        SUPER_AUTH_TOKEN_NAME,
        $token,
        [
            'expires' => time() + SUPER_AUTH_TOKEN_LIFETIME,
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
 * Delete persistent token
 */
function superDeletePersistentToken(int $adminId): void {
    ensureSuperAdminTables();
    $pdo = getSuperDatabase();
    $stmt = $pdo->prepare('DELETE FROM super_persistent_tokens WHERE super_admin_id = ?');
    $stmt->execute([$adminId]);
    superClearPersistentTokenCookie();
}

function superClearPersistentTokenCookie(): void {
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    setcookie(
        SUPER_AUTH_TOKEN_NAME,
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

function superIsLoggedIn(): bool {
    return isset($_SESSION['super_admin_id']) && isset($_SESSION['super_admin_username']);
}

function superRequireAuth(): void {
    if (!superIsLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Rate limiting: 3 attempts per 30 minutes (stricter than hotel admin)
 */
function superCheckLoginAttempts(string $ip): bool {
    ensureSuperAdminTables();
    $pdo = getSuperDatabase();

    $stmt = $pdo->prepare('DELETE FROM super_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)');
    $stmt->execute();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM super_login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)');
    $stmt->execute([$ip]);

    return $stmt->fetchColumn() < 3;
}

function superRecordLoginAttempt(string $ip): void {
    $pdo = getSuperDatabase();
    $stmt = $pdo->prepare('INSERT INTO super_login_attempts (ip_address) VALUES (?)');
    $stmt->execute([$ip]);
}

function superClearLoginAttempts(string $ip): void {
    $pdo = getSuperDatabase();
    $stmt = $pdo->prepare('DELETE FROM super_login_attempts WHERE ip_address = ?');
    $stmt->execute([$ip]);
}

/**
 * Attempt super admin login
 */
function superAttemptLogin(string $username, string $password): array {
    ensureSuperAdminTables();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!superCheckLoginAttempts($ip)) {
        return [
            'success' => false,
            'message' => 'Trop de tentatives. Réessayez dans 30 minutes.'
        ];
    }

    $username = trim($username);
    if (empty($username) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Veuillez remplir tous les champs.'
        ];
    }

    $pdo = getSuperDatabase();
    $stmt = $pdo->prepare('SELECT id, username, password FROM super_admins WHERE username = ? AND is_active = 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        superRecordLoginAttempt($ip);
        return [
            'success' => false,
            'message' => 'Identifiants incorrects.'
        ];
    }

    superClearLoginAttempts($ip);

    $stmt = $pdo->prepare('UPDATE super_admins SET last_login = NOW() WHERE id = ?');
    $stmt->execute([$admin['id']]);

    $_SESSION['super_admin_id'] = $admin['id'];
    $_SESSION['super_admin_username'] = $admin['username'];
    $_SESSION['login_time'] = time();

    session_regenerate_id(true);
    superCreatePersistentToken($admin['id']);

    return [
        'success' => true,
        'message' => 'Connexion réussie.'
    ];
}

/**
 * Logout
 */
function superLogout(): void {
    if (isset($_SESSION['super_admin_id'])) {
        superDeletePersistentToken($_SESSION['super_admin_id']);
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
 * CSRF token
 */
function superGenerateCsrfToken(): string {
    if (empty($_SESSION['super_csrf_token'])) {
        $_SESSION['super_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['super_csrf_token'];
}

function superVerifyCsrfToken(string $token): bool {
    return isset($_SESSION['super_csrf_token']) && hash_equals($_SESSION['super_csrf_token'], $token);
}

/**
 * Change super admin password
 */
function superChangePassword(int $adminId, string $currentPassword, string $newPassword): array {
    $pdo = getSuperDatabase();

    $stmt = $pdo->prepare('SELECT password FROM super_admins WHERE id = ?');
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($currentPassword, $admin['password'])) {
        return ['success' => false, 'message' => 'Mot de passe actuel incorrect.'];
    }

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE super_admins SET password = ? WHERE id = ?');
    $stmt->execute([$hash, $adminId]);

    return ['success' => true, 'message' => 'Mot de passe modifié avec succès.'];
}
