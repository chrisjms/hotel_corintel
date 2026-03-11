<?php
/**
 * Super Admin Helper Functions
 * Hotel CRUD, audit logging, cross-login token generation
 */

// getSuperDatabase() is defined in super-auth.php (always loaded before this file)

/**
 * Ensure hotels table exists
 */
function ensureHotelsTable(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $pdo = getSuperDatabase();

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS hotels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            site_url VARCHAR(500),
            admin_url VARCHAR(500),
            db_host VARCHAR(200) NULL,
            db_name VARCHAR(200) NULL,
            db_user VARCHAR(200) NULL,
            db_pass VARCHAR(500) NULL,
            cross_login_secret VARCHAR(128) NULL,
            notes TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS super_admin_login_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token_nonce VARCHAR(64) NOT NULL UNIQUE,
            used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nonce (token_nonce),
            INDEX idx_used_at (used_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS super_admin_audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            super_admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            hotel_id INT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_super_admin (super_admin_id),
            INDEX idx_action (action),
            INDEX idx_hotel (hotel_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
}

// --- Hotel CRUD ---

function getAllHotels(): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    $stmt = $pdo->query('SELECT * FROM hotels ORDER BY name ASC');
    return $stmt->fetchAll();
}

function getHotelById(int $id): ?array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    $stmt = $pdo->prepare('SELECT * FROM hotels WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function createHotel(array $data): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    $name = trim($data['name'] ?? '');
    $slug = trim($data['slug'] ?? '');
    $siteUrl = trim($data['site_url'] ?? '');
    $adminUrl = trim($data['admin_url'] ?? '');
    $notes = trim($data['notes'] ?? '');

    if (empty($name)) {
        return ['success' => false, 'message' => 'Le nom est obligatoire.'];
    }

    if (empty($slug)) {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $name)));
    }

    // Check uniqueness
    $stmt = $pdo->prepare('SELECT id FROM hotels WHERE slug = ?');
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Ce slug est déjà utilisé.'];
    }

    $stmt = $pdo->prepare('
        INSERT INTO hotels (name, slug, site_url, admin_url, notes)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$name, $slug, $siteUrl, $adminUrl, $notes]);

    return ['success' => true, 'message' => 'Hôtel ajouté avec succès.', 'id' => $pdo->lastInsertId()];
}

function updateHotel(int $id, array $data): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    $name = trim($data['name'] ?? '');
    $slug = trim($data['slug'] ?? '');
    $siteUrl = trim($data['site_url'] ?? '');
    $adminUrl = trim($data['admin_url'] ?? '');
    $notes = trim($data['notes'] ?? '');
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

    if (empty($name)) {
        return ['success' => false, 'message' => 'Le nom est obligatoire.'];
    }

    // Check slug uniqueness (exclude current hotel)
    $stmt = $pdo->prepare('SELECT id FROM hotels WHERE slug = ? AND id != ?');
    $stmt->execute([$slug, $id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Ce slug est déjà utilisé.'];
    }

    $stmt = $pdo->prepare('
        UPDATE hotels SET name = ?, slug = ?, site_url = ?, admin_url = ?, notes = ?, is_active = ?
        WHERE id = ?
    ');
    $stmt->execute([$name, $slug, $siteUrl, $adminUrl, $notes, $isActive, $id]);

    return ['success' => true, 'message' => 'Hôtel mis à jour avec succès.'];
}

function deleteHotel(int $id): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    $stmt = $pdo->prepare('DELETE FROM hotels WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'message' => 'Hôtel introuvable.'];
    }

    return ['success' => true, 'message' => 'Hôtel supprimé.'];
}

function countHotels(): int {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    return (int)$pdo->query('SELECT COUNT(*) FROM hotels')->fetchColumn();
}

function countActiveHotels(): int {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    return (int)$pdo->query('SELECT COUNT(*) FROM hotels WHERE is_active = 1')->fetchColumn();
}

// --- Audit Logging ---

function logAudit(int $superAdminId, string $action, ?int $hotelId = null, ?string $details = null): void {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $stmt = $pdo->prepare('
        INSERT INTO super_admin_audit_log (super_admin_id, action, hotel_id, details, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$superAdminId, $action, $hotelId, $details, $ip]);
}

function getAuditLog(int $limit = 50, int $offset = 0): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    $stmt = $pdo->prepare('
        SELECT al.*, sa.username AS admin_username, h.name AS hotel_name
        FROM super_admin_audit_log al
        LEFT JOIN super_admins sa ON sa.id = al.super_admin_id
        LEFT JOIN hotels h ON h.id = al.hotel_id
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function countAuditLogs(): int {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    return (int)$pdo->query('SELECT COUNT(*) FROM super_admin_audit_log')->fetchColumn();
}

// --- Cross-Login Token Generation ---

/**
 * Get the HMAC secret for a hotel (per-hotel or global fallback)
 */
function getHotelCrossLoginSecret(array $hotel): string {
    return !empty($hotel['cross_login_secret'])
        ? $hotel['cross_login_secret']
        : SUPER_ADMIN_CROSS_LOGIN_SECRET;
}

function generateCrossLoginToken(array $hotel, int $superAdminId): string {
    $nonce = bin2hex(random_bytes(16));
    $timestamp = time();
    $payload = $hotel['id'] . '|' . $superAdminId . '|' . $timestamp . '|' . $nonce;
    $secret = getHotelCrossLoginSecret($hotel);
    $signature = hash_hmac('sha256', $payload, $secret);
    return base64_encode($payload . '.' . $signature);
}

function getCrossLoginUrl(array $hotel, int $superAdminId): ?string {
    $adminUrl = rtrim($hotel['admin_url'] ?? '', '/');
    if (empty($adminUrl)) {
        return null;
    }

    $token = generateCrossLoginToken($hotel, $superAdminId);
    return $adminUrl . '/super-login.php?token=' . urlencode($token);
}
