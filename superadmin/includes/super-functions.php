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
            id SERIAL PRIMARY KEY,
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
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_hotels_slug ON hotels (slug)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_hotels_active ON hotels (is_active)');

    // Add schema_name column if missing (migration 010)
    $pdo->exec('ALTER TABLE hotels ADD COLUMN IF NOT EXISTS schema_name VARCHAR(100) NULL');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_hotels_schema_name ON hotels (schema_name)');

    // Add type column if missing (migration 011)
    $pdo->exec("ALTER TABLE hotels ADD COLUMN IF NOT EXISTS type VARCHAR(50) NOT NULL DEFAULT 'hotel'");

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS super_admin_login_tokens (
            id SERIAL PRIMARY KEY,
            token_nonce VARCHAR(64) NOT NULL UNIQUE,
            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_super_admin_login_tokens_nonce ON super_admin_login_tokens (token_nonce)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_super_admin_login_tokens_used_at ON super_admin_login_tokens (used_at)');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS super_admin_audit_log (
            id SERIAL PRIMARY KEY,
            super_admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            hotel_id INT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_super_admin_audit_log_admin ON super_admin_audit_log (super_admin_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_super_admin_audit_log_action ON super_admin_audit_log (action)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_super_admin_audit_log_hotel ON super_admin_audit_log (hotel_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_super_admin_audit_log_created ON super_admin_audit_log (created_at)');

    // Establishment features table (migration 012)
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS public.establishment_features (
            id SERIAL PRIMARY KEY,
            hotel_id INT NOT NULL,
            feature_key VARCHAR(50) NOT NULL,
            is_enabled BOOLEAN DEFAULT TRUE,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (hotel_id, feature_key),
            FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
        )
    ');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_establishment_features_hotel ON public.establishment_features (hotel_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_establishment_features_key ON public.establishment_features (feature_key)');
}

// --- Hotel CRUD ---

function getAllHotels(?string $type = null): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    if ($type) {
        $stmt = $pdo->prepare('SELECT * FROM public.hotels WHERE type = ? ORDER BY name ASC');
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query('SELECT * FROM public.hotels ORDER BY name ASC');
    }
    return $stmt->fetchAll();
}

function getHotelById(int $id): ?array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    $stmt = $pdo->prepare('SELECT * FROM public.hotels WHERE id = ?');
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
    $stmt = $pdo->prepare('SELECT id FROM public.hotels WHERE slug = ?');
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Ce slug est déjà utilisé.'];
    }

    // Generate schema name from slug (replace hyphens with underscores for valid SQL identifier)
    $schemaName = 'hotel_' . str_replace('-', '_', $slug);

    // Create the hotel schema and its tables
    try {
        createHotelSchema($pdo, $schemaName);
    } catch (PDOException $e) {
        error_log('Failed to create schema ' . $schemaName . ': ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de la création du schema: ' . $e->getMessage()];
    }

    $type = trim($data['type'] ?? 'hotel');

    $stmt = $pdo->prepare('
        INSERT INTO public.hotels (name, slug, site_url, admin_url, notes, schema_name, type)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$name, $slug, $siteUrl, $adminUrl, $notes, $schemaName, $type]);
    $hotelId = $pdo->lastInsertId();

    // Populate hotel name in the per-hotel settings table
    $pdo->exec('SET search_path TO ' . $schemaName . ', public');
    $stmtSetting = $pdo->prepare('
        INSERT INTO settings (setting_key, setting_value, hotel_id)
        VALUES (?, ?, ?)
        ON CONFLICT (setting_key, hotel_id) DO UPDATE SET setting_value = EXCLUDED.setting_value
    ');
    $stmtSetting->execute(['hotel_name', $name, $hotelId]);
    $pdo->exec('SET search_path TO public');

    return ['success' => true, 'message' => 'Hôtel ajouté avec succès.', 'id' => $hotelId];
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
    $stmt = $pdo->prepare('SELECT id FROM public.hotels WHERE slug = ? AND id != ?');
    $stmt->execute([$slug, $id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Ce slug est déjà utilisé.'];
    }

    $type = trim($data['type'] ?? 'hotel');

    $stmt = $pdo->prepare('
        UPDATE public.hotels SET name = ?, slug = ?, site_url = ?, admin_url = ?, notes = ?, is_active = ?, type = ?
        WHERE id = ?
    ');
    $stmt->execute([$name, $slug, $siteUrl, $adminUrl, $notes, $isActive, $type, $id]);

    return ['success' => true, 'message' => 'Hôtel mis à jour avec succès.'];
}

function deleteHotel(int $id): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    // Get hotel info before deletion (for schema cleanup)
    $stmt = $pdo->prepare('SELECT schema_name FROM public.hotels WHERE id = ?');
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();

    $stmt = $pdo->prepare('DELETE FROM public.hotels WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'message' => 'Hôtel introuvable.'];
    }

    // Drop the hotel schema if it exists
    if (!empty($hotel['schema_name'])) {
        $schemaName = preg_replace('/[^a-z0-9_]/', '', $hotel['schema_name']);
        try {
            $pdo->exec('DROP SCHEMA IF EXISTS ' . $schemaName . ' CASCADE');
        } catch (PDOException $e) {
            error_log('Failed to drop schema ' . $schemaName . ': ' . $e->getMessage());
        }
    }

    // Clear the hotel slug cookie so the next page load doesn't try to load the deleted hotel
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    setcookie('_hotel_slug', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    return ['success' => true, 'message' => 'Hôtel supprimé.'];
}

function countHotels(): int {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    return (int)$pdo->query('SELECT COUNT(*) FROM public.hotels')->fetchColumn();
}

function countActiveHotels(): int {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    return (int)$pdo->query('SELECT COUNT(*) FROM public.hotels WHERE is_active = TRUE')->fetchColumn();
}

// --- Audit Logging ---

function logAudit(int $superAdminId, string $action, ?int $hotelId = null, ?string $details = null): void {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $stmt = $pdo->prepare('
        INSERT INTO public.super_admin_audit_log (super_admin_id, action, hotel_id, details, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$superAdminId, $action, $hotelId, $details, $ip]);
}

function getAuditLog(int $limit = 50, int $offset = 0): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    $stmt = $pdo->prepare('
        SELECT al.*, sa.username AS admin_username, h.name AS hotel_name
        FROM public.super_admin_audit_log al
        LEFT JOIN public.super_admins sa ON sa.id = al.super_admin_id
        LEFT JOIN public.hotels h ON h.id = al.hotel_id
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function countAuditLogs(): int {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    return (int)$pdo->query('SELECT COUNT(*) FROM public.super_admin_audit_log')->fetchColumn();
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
    $slug = $hotel['slug'] ?? '';
    return $adminUrl . '/super-login.php?hotel=' . urlencode($slug) . '&token=' . urlencode($token);
}

// --- Hotel Schema Creation ---

/**
 * Create a PostgreSQL schema with all per-hotel tables for a new hotel.
 * Tables mirror the public schema structure (with hotel_id columns kept for backward compatibility).
 */
function createHotelSchema(PDO $pdo, string $schemaName): void {
    // Sanitize schema name (only lowercase alphanumeric and underscores)
    $schemaName = preg_replace('/[^a-z0-9_]/', '', $schemaName);

    // Ensure the trigger function exists in public (shared across schemas)
    $pdo->exec("
        CREATE OR REPLACE FUNCTION public.update_updated_at_column()
        RETURNS TRIGGER AS \$\$
        BEGIN
            NEW.updated_at = CURRENT_TIMESTAMP;
            RETURN NEW;
        END;
        \$\$ LANGUAGE plpgsql
    ");

    $pdo->exec('CREATE SCHEMA IF NOT EXISTS ' . $schemaName);

    // Set search_path to new schema so all CREATE TABLE go there
    $pdo->exec('SET search_path TO ' . $schemaName . ', public');

    // --- Per-hotel tables ---

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            role VARCHAR(20) NOT NULL DEFAULT 'admin',
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            UNIQUE (username, hotel_id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS images (
            id SERIAL PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255),
            section VARCHAR(50) NOT NULL,
            position INT NOT NULL DEFAULT 1,
            slot_name VARCHAR(100),
            title VARCHAR(255),
            alt_text VARCHAR(255),
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (section, position, hotel_id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id SERIAL PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS persistent_tokens (
            id SERIAL PRIMARY KEY,
            admin_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS room_service_categories (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            time_start TIME DEFAULT NULL,
            time_end TIME DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            position INT DEFAULT 0,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (code, hotel_id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS room_service_items (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            category VARCHAR(50) DEFAULT 'general',
            is_active BOOLEAN DEFAULT TRUE,
            position INT DEFAULT 0,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS room_service_orders (
            id SERIAL PRIMARY KEY,
            room_number VARCHAR(20) NOT NULL,
            guest_name VARCHAR(100) DEFAULT NULL,
            phone VARCHAR(30) DEFAULT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            payment_method VARCHAR(20) NOT NULL DEFAULT 'room_charge' CHECK (payment_method IN ('cash', 'card', 'room_charge')),
            status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'preparing', 'delivered', 'cancelled')),
            delivery_datetime TIMESTAMP NOT NULL,
            notes TEXT,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS room_service_order_items (
            id SERIAL PRIMARY KEY,
            order_id INT NOT NULL,
            item_id INT NOT NULL,
            item_name VARCHAR(100) NOT NULL,
            item_price DECIMAL(10, 2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            subtotal DECIMAL(10, 2) NOT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            FOREIGN KEY (order_id) REFERENCES room_service_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES room_service_items(id) ON DELETE RESTRICT
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS guest_messages (
            id SERIAL PRIMARY KEY,
            room_number VARCHAR(20) NOT NULL,
            guest_name VARCHAR(100) DEFAULT NULL,
            category VARCHAR(50) NOT NULL DEFAULT 'general',
            subject VARCHAR(255) DEFAULT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'read', 'in_progress', 'resolved')),
            admin_notes TEXT DEFAULT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rooms (
            id SERIAL PRIMARY KEY,
            room_number VARCHAR(20) NOT NULL UNIQUE,
            floor SMALLINT DEFAULT NULL,
            room_type VARCHAR(20) NOT NULL DEFAULT 'double' CHECK (room_type IN ('single', 'double', 'twin', 'suite', 'family', 'accessible')),
            capacity SMALLINT NOT NULL DEFAULT 2,
            bed_count SMALLINT NOT NULL DEFAULT 1,
            surface_area DECIMAL(5,2) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'available' CHECK (status IN ('available', 'occupied', 'maintenance', 'out_of_order')),
            housekeeping_status VARCHAR(20) NOT NULL DEFAULT 'cleaned' CHECK (housekeeping_status IN ('pending', 'in_progress', 'cleaned', 'inspected')),
            last_cleaned_at TIMESTAMP DEFAULT NULL,
            last_inspection_at TIMESTAMP DEFAULT NULL,
            last_checkout_at TIMESTAMP DEFAULT NULL,
            amenities JSONB DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS housekeeping_logs (
            id SERIAL PRIMARY KEY,
            room_id INT NOT NULL,
            action VARCHAR(30) NOT NULL CHECK (action IN ('cleaning_started', 'cleaning_completed', 'inspection_passed', 'inspection_failed', 'maintenance_requested', 'maintenance_completed', 'status_changed')),
            previous_status VARCHAR(30) DEFAULT NULL,
            new_status VARCHAR(30) DEFAULT NULL,
            performed_by VARCHAR(100) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pages (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(50) NOT NULL,
            code VARCHAR(50) NOT NULL,
            title VARCHAR(100) NOT NULL,
            nav_title VARCHAR(50) DEFAULT NULL,
            meta_title VARCHAR(150) DEFAULT NULL,
            meta_description VARCHAR(300) DEFAULT NULL,
            meta_keywords VARCHAR(255) DEFAULT NULL,
            hero_section_code VARCHAR(50) DEFAULT NULL,
            page_type VARCHAR(20) NOT NULL DEFAULT 'standard' CHECK (page_type IN ('standard', 'home', 'contact', 'special')),
            template VARCHAR(50) DEFAULT 'default',
            display_order INT NOT NULL DEFAULT 0,
            show_in_nav BOOLEAN NOT NULL DEFAULT TRUE,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            i18n_nav_key VARCHAR(100) DEFAULT NULL,
            i18n_hero_title_key VARCHAR(100) DEFAULT NULL,
            i18n_hero_subtitle_key VARCHAR(100) DEFAULT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (slug, hotel_id),
            UNIQUE (code, hotel_id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS page_translations (
            id SERIAL PRIMARY KEY,
            page_id INT NOT NULL,
            lang_code VARCHAR(5) NOT NULL,
            title VARCHAR(100) DEFAULT NULL,
            nav_title VARCHAR(50) DEFAULT NULL,
            meta_title VARCHAR(150) DEFAULT NULL,
            meta_description VARCHAR(300) DEFAULT NULL,
            hero_title VARCHAR(150) DEFAULT NULL,
            hero_subtitle VARCHAR(255) DEFAULT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (page_id, lang_code),
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS qr_scans (
            id SERIAL PRIMARY KEY,
            room_id INT NOT NULL,
            scanned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id SERIAL PRIMARY KEY,
            room_id INT NOT NULL,
            endpoint TEXT NOT NULL,
            p256dh_key VARCHAR(255) NOT NULL,
            auth_key VARCHAR(255) NOT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP DEFAULT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS room_service_item_translations (
            id SERIAL PRIMARY KEY,
            item_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (item_id, language_code),
            FOREIGN KEY (item_id) REFERENCES room_service_items(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS room_service_category_translations (
            id SERIAL PRIMARY KEY,
            category_code VARCHAR(50) NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            name VARCHAR(100) NOT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (category_code, language_code)
        )
    ");

    // --- Content & dynamic sections tables ---

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

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_sections (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            page VARCHAR(50) NOT NULL,
            image_mode VARCHAR(20) DEFAULT 'optional' CHECK (image_mode IN ('required', 'optional', 'forbidden')),
            max_blocks INT DEFAULT NULL,
            has_title BOOLEAN DEFAULT TRUE,
            has_description BOOLEAN DEFAULT TRUE,
            has_link BOOLEAN DEFAULT FALSE,
            sort_order INT DEFAULT 0,
            has_overlay BOOLEAN DEFAULT FALSE,
            overlay_subtitle VARCHAR(255) DEFAULT NULL,
            overlay_title VARCHAR(255) DEFAULT NULL,
            overlay_description TEXT DEFAULT NULL,
            has_features BOOLEAN DEFAULT FALSE,
            is_dynamic BOOLEAN DEFAULT FALSE,
            template_type VARCHAR(50) DEFAULT NULL,
            custom_name VARCHAR(100) DEFAULT NULL,
            has_services BOOLEAN DEFAULT FALSE,
            has_gallery BOOLEAN DEFAULT FALSE,
            background_color VARCHAR(30) DEFAULT 'cream',
            image_position VARCHAR(10) DEFAULT 'left',
            text_alignment VARCHAR(10) DEFAULT 'center',
            section_link_url VARCHAR(500) DEFAULT NULL,
            section_link_text VARCHAR(100) DEFAULT NULL,
            section_link_new_tab BOOLEAN DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(code, hotel_id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_blocks (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            title VARCHAR(255),
            description TEXT,
            image_filename VARCHAR(255),
            image_alt VARCHAR(255),
            link_url VARCHAR(500),
            link_text VARCHAR(100),
            position INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_overlay_translations (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            overlay_subtitle VARCHAR(255),
            overlay_title VARCHAR(255),
            overlay_description TEXT,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (section_code, language_code)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_features (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            icon_code VARCHAR(50) NOT NULL,
            label VARCHAR(100) NOT NULL,
            position INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_feature_translations (
            id SERIAL PRIMARY KEY,
            feature_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            label VARCHAR(100) NOT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (feature_id, language_code),
            FOREIGN KEY (feature_id) REFERENCES section_features(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_link_translations (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            link_text VARCHAR(100) NOT NULL,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (section_code, language_code)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_services (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            icon_code VARCHAR(50) NOT NULL,
            label VARCHAR(100) NOT NULL,
            description TEXT,
            position INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_service_translations (
            id SERIAL PRIMARY KEY,
            service_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            label VARCHAR(100) NOT NULL,
            description TEXT,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (service_id, language_code),
            FOREIGN KEY (service_id) REFERENCES section_services(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_gallery_items (
            id SERIAL PRIMARY KEY,
            section_code VARCHAR(50) NOT NULL,
            image_filename VARCHAR(255) NOT NULL,
            image_alt VARCHAR(255),
            title VARCHAR(100) NOT NULL,
            description TEXT,
            position INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            hotel_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_gallery_item_translations (
            id SERIAL PRIMARY KEY,
            item_id INT NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            hotel_id INT NOT NULL DEFAULT 1,
            UNIQUE (item_id, language_code),
            FOREIGN KEY (item_id) REFERENCES section_gallery_items(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_templates (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            image_mode VARCHAR(20) DEFAULT 'optional' CHECK (image_mode IN ('required', 'optional', 'forbidden')),
            max_blocks INT DEFAULT 1,
            has_title BOOLEAN DEFAULT FALSE,
            has_description BOOLEAN DEFAULT FALSE,
            has_link BOOLEAN DEFAULT FALSE,
            has_overlay BOOLEAN DEFAULT TRUE,
            has_features BOOLEAN DEFAULT TRUE,
            has_services BOOLEAN DEFAULT FALSE,
            has_gallery BOOLEAN DEFAULT FALSE,
            css_class VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // --- Triggers (reference public function) ---
    // Note: split into separate exec() calls — pgBouncer doesn't support multi-statement queries
    $triggerTables = ['images', 'room_service_items', 'room_service_orders',
                      'room_service_categories', 'guest_messages', 'rooms', 'pages',
                      'content_blocks', 'settings'];
    foreach ($triggerTables as $table) {
        $pdo->exec("DROP TRIGGER IF EXISTS update_{$table}_updated_at ON {$table}");
        $pdo->exec("
            CREATE TRIGGER update_{$table}_updated_at BEFORE UPDATE ON {$table}
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");
    }

    // Reset search_path to public
    $pdo->exec('SET search_path TO public');
}

// --- Hotel Provisioning ---

/**
 * Provision default data for a newly created hotel.
 * Seeds admin user, room service categories, default pages.
 * Data is inserted into the hotel's own schema via search_path.
 */
function provisionHotelData(int $hotelId): void {
    $pdo = getSuperDatabase();

    // Get the hotel's schema name
    $stmt = $pdo->prepare('SELECT schema_name, slug FROM public.hotels WHERE id = ?');
    $stmt->execute([$hotelId]);
    $hotel = $stmt->fetch();
    $schemaName = $hotel['schema_name'] ?? null;

    // Set search_path to hotel schema if available
    if ($schemaName) {
        $schemaName = preg_replace('/[^a-z0-9_]/', '', $schemaName);
        $pdo->exec('SET search_path TO ' . $schemaName . ', public');
    }

    // 1. Default admin user (admin / admin123)
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('
        INSERT INTO admins (username, password, email, role, hotel_id)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT (username, hotel_id) DO NOTHING
    ');
    $stmt->execute(['admin', $adminHash, '', 'admin', $hotelId]);

    // 2. Default room service categories
    $categories = [
        ['breakfast', 'Petit-déjeuner', '07:00', '11:00', 1],
        ['lunch', 'Déjeuner', '12:00', '14:30', 2],
        ['dinner', 'Dîner', '19:00', '22:00', 3],
        ['snacks', 'Snacks', null, null, 4],
        ['drinks', 'Boissons', null, null, 5],
        ['desserts', 'Desserts', '12:00', '22:00', 6],
        ['general', 'Général', null, null, 7],
    ];
    $stmt = $pdo->prepare('
        INSERT INTO room_service_categories (code, name, time_start, time_end, position, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?)
        ON CONFLICT (code, hotel_id) DO NOTHING
    ');
    foreach ($categories as $cat) {
        $stmt->execute([$cat[0], $cat[1], $cat[2], $cat[3], $cat[4], $hotelId]);
    }

    // 3. Default pages
    $pages = [
        ['home', 'home', 'Accueil', 'Accueil', 1, true],
        ['services', 'services', 'Services', 'Services', 2, true],
        ['activities', 'activities', 'Activités', 'Activités', 3, true],
        ['contact', 'contact', 'Contact', 'Contact', 4, true],
        ['room-service', 'room-service', 'Room Service', 'Room Service', 5, false],
    ];
    $stmt = $pdo->prepare('
        INSERT INTO pages (slug, code, title, nav_title, display_order, show_in_nav, is_active, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?, TRUE, ?)
        ON CONFLICT (slug, hotel_id) DO NOTHING
    ');
    foreach ($pages as $p) {
        $stmt->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5] ? 'true' : 'false', $hotelId]);
    }

    // 4. Create uploads directory for this hotel
    $uploadsDir = HOTEL_ROOT . '/shared/uploads/hotel_' . $hotelId . '/';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }

    // Reset search_path
    $pdo->exec('SET search_path TO public');
}

/**
 * Generate default site_url and admin_url from hotel slug
 */
function getDefaultHotelUrls(string $slug): array {
    return [
        'site_url' => 'https://hothello-client.onrender.com/?hotel=' . $slug,
        'admin_url' => 'https://hothello-admin.onrender.com',
    ];
}

// ============================================================
// --- Feature Toggles ---
// ============================================================

const AVAILABLE_FEATURES = [
    'room_service'   => ['label' => 'Room Service',        'description' => 'Commandes en chambre via QR code'],
    'messaging'      => ['label' => 'Messagerie',          'description' => 'Messages invités → réception'],
    'qr_codes'       => ['label' => 'QR Codes',            'description' => 'Scan QR pour accès chambre'],
    'multilingual'   => ['label' => 'Multilingue',         'description' => 'Support FR / EN / ES / IT'],
    'dynamic_pages'  => ['label' => 'Pages dynamiques',    'description' => 'Sections et contenu personnalisable'],
    'housekeeping'   => ['label' => 'Housekeeping',        'description' => 'Gestion ménage et inspections'],
];

function getEstablishmentFeatures(int $hotelId): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    $stmt = $pdo->prepare('SELECT feature_key, is_enabled FROM public.establishment_features WHERE hotel_id = ?');
    $stmt->execute([$hotelId]);
    $rows = $stmt->fetchAll();

    $saved = [];
    foreach ($rows as $row) {
        $saved[$row['feature_key']] = filter_var($row['is_enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    $features = [];
    foreach (AVAILABLE_FEATURES as $key => $meta) {
        $features[$key] = [
            'feature_key' => $key,
            'label'       => $meta['label'],
            'description' => $meta['description'],
            'is_enabled'  => $saved[$key] ?? true, // enabled by default
        ];
    }
    return $features;
}

function setFeatureToggle(int $hotelId, string $featureKey, bool $enabled): bool {
    if (!isset(AVAILABLE_FEATURES[$featureKey])) {
        return false;
    }
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    $stmt = $pdo->prepare('
        INSERT INTO public.establishment_features (hotel_id, feature_key, is_enabled, updated_at)
        VALUES (?, ?, ?, NOW())
        ON CONFLICT (hotel_id, feature_key)
        DO UPDATE SET is_enabled = EXCLUDED.is_enabled, updated_at = NOW()
    ');
    $stmt->execute([$hotelId, $featureKey, $enabled ? 'true' : 'false']);
    return true;
}

function isFeatureEnabled(int $hotelId, string $featureKey): bool {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    $stmt = $pdo->prepare('SELECT is_enabled FROM public.establishment_features WHERE hotel_id = ? AND feature_key = ?');
    $stmt->execute([$hotelId, $featureKey]);
    $row = $stmt->fetch();
    if (!$row) return true; // enabled by default
    return filter_var($row['is_enabled'], FILTER_VALIDATE_BOOLEAN);
}

// ============================================================
// --- Monitoring ---
// ============================================================

function getMonitoringMetrics(): array {
    $pdo = getSuperDatabase();

    // Real: DB response time
    $t0 = microtime(true);
    $pdo->query('SELECT 1');
    $dbLatency = round((microtime(true) - $t0) * 1000, 2); // ms

    // Real: DB size
    $dbSize = 0;
    try {
        $row = $pdo->query('SELECT pg_database_size(current_database()) AS db_size')->fetch();
        $dbSize = (int)($row['db_size'] ?? 0);
    } catch (PDOException $e) {}

    // Real: connections
    $connections = ['total' => 0, 'active' => 0, 'idle' => 0];
    try {
        $row = $pdo->query("
            SELECT COUNT(*) AS total,
                   COUNT(*) FILTER (WHERE state = 'active') AS active,
                   COUNT(*) FILTER (WHERE state = 'idle') AS idle
            FROM pg_stat_activity
        ")->fetch();
        $connections = [
            'total'  => (int)$row['total'],
            'active' => (int)$row['active'],
            'idle'   => (int)$row['idle'],
        ];
    } catch (PDOException $e) {}

    // Real: per-schema row counts
    $schemas = [];
    try {
        $rows = $pdo->query("
            SELECT schemaname, SUM(n_live_tup) AS total_rows
            FROM pg_stat_user_tables
            WHERE schemaname LIKE 'hotel_%'
            GROUP BY schemaname
            ORDER BY total_rows DESC
        ")->fetchAll();
        foreach ($rows as $r) {
            $schemas[] = [
                'name'       => $r['schemaname'],
                'total_rows' => (int)$r['total_rows'],
            ];
        }
    } catch (PDOException $e) {}

    // Simulated metrics (consistent within a 30s window via session)
    $window = floor(time() / 30);
    $seed = crc32('monitoring_' . $window);
    mt_srand($seed);
    $cpuBase = 15 + (date('G') >= 9 && date('G') <= 18 ? 20 : 0); // higher during "business hours"
    $cpu = min(95, max(5, $cpuBase + mt_rand(-10, 25)));
    $ram = min(92, max(30, 45 + mt_rand(-10, 30)));
    $uptimeDays = 47 + floor((time() - strtotime('2026-01-01')) / 86400) % 90;
    $requestsMin = max(10, 120 + mt_rand(-60, 100));
    mt_srand(); // reset

    return [
        'db_latency_ms'  => $dbLatency,
        'db_size'        => $dbSize,
        'db_size_human'  => formatBytes($dbSize),
        'connections'    => $connections,
        'schemas'        => $schemas,
        'cpu_percent'    => $cpu,
        'ram_percent'    => $ram,
        'uptime_days'    => $uptimeDays,
        'requests_min'   => $requestsMin,
        'timestamp'      => time(),
    ];
}

// ============================================================
// --- Global Analytics ---
// ============================================================

function getGlobalAnalytics(int $days = 30): array {
    $pdo = getSuperDatabase();
    $hotels = getAllHotels();

    $totalOrders = 0;
    $totalRevenue = 0.0;
    $totalMessages = 0;
    $totalScans = 0;

    foreach ($hotels as $hotel) {
        if (empty($hotel['schema_name'])) continue;
        $schema = preg_replace('/[^a-z0-9_]/', '', $hotel['schema_name']);
        try {
            $pdo->exec('SET search_path TO ' . $schema . ', public');

            $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount), 0) AS rev FROM room_service_orders WHERE created_at >= NOW() - INTERVAL \'' . (int)$days . ' days\'');
            $stmt->execute();
            $r = $stmt->fetch();
            $totalOrders += (int)$r['cnt'];
            $totalRevenue += (float)$r['rev'];

            $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM guest_messages WHERE created_at >= NOW() - INTERVAL \'' . (int)$days . ' days\'');
            $stmt->execute();
            $totalMessages += (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM qr_scans WHERE scanned_at >= NOW() - INTERVAL \'' . (int)$days . ' days\'');
            $stmt->execute();
            $totalScans += (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            // Schema may not exist or table missing
        }
    }

    $pdo->exec('SET search_path TO public');

    return [
        'total_orders'   => $totalOrders,
        'total_revenue'  => round($totalRevenue, 2),
        'total_messages' => $totalMessages,
        'total_scans'    => $totalScans,
        'days'           => $days,
    ];
}

function getPerHotelAnalytics(int $days = 30): array {
    $pdo = getSuperDatabase();
    $hotels = getAllHotels();
    $results = [];

    foreach ($hotels as $hotel) {
        if (empty($hotel['schema_name'])) continue;
        $schema = preg_replace('/[^a-z0-9_]/', '', $hotel['schema_name']);
        $entry = [
            'hotel_id'   => $hotel['id'],
            'hotel_name' => $hotel['name'],
            'slug'       => $hotel['slug'],
            'type'       => $hotel['type'] ?? 'hotel',
            'is_active'  => filter_var($hotel['is_active'], FILTER_VALIDATE_BOOLEAN),
            'orders'     => 0,
            'revenue'    => 0.0,
            'messages'   => 0,
            'scans'      => 0,
        ];
        try {
            $pdo->exec('SET search_path TO ' . $schema . ', public');

            $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount), 0) AS rev FROM room_service_orders WHERE created_at >= NOW() - INTERVAL \'' . (int)$days . ' days\'');
            $stmt->execute();
            $r = $stmt->fetch();
            $entry['orders'] = (int)$r['cnt'];
            $entry['revenue'] = round((float)$r['rev'], 2);

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM guest_messages WHERE created_at >= NOW() - INTERVAL \'' . (int)$days . ' days\'');
            $stmt->execute();
            $entry['messages'] = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM qr_scans WHERE scanned_at >= NOW() - INTERVAL \'' . (int)$days . ' days\'');
            $stmt->execute();
            $entry['scans'] = (int)$stmt->fetchColumn();
        } catch (PDOException $e) {}

        $results[] = $entry;
    }

    $pdo->exec('SET search_path TO public');

    // Sort by revenue descending
    usort($results, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

    return $results;
}

function getAnalyticsTrend(int $days = 30): array {
    $pdo = getSuperDatabase();
    $hotels = getAllHotels();
    $daily = []; // date => ['orders' => X, 'revenue' => Y]

    foreach ($hotels as $hotel) {
        if (empty($hotel['schema_name'])) continue;
        $schema = preg_replace('/[^a-z0-9_]/', '', $hotel['schema_name']);
        try {
            $pdo->exec('SET search_path TO ' . $schema . ', public');
            $stmt = $pdo->prepare('
                SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS revenue
                FROM room_service_orders
                WHERE created_at >= NOW() - INTERVAL \'' . (int)$days . ' days\'
                GROUP BY DATE(created_at)
            ');
            $stmt->execute();
            foreach ($stmt->fetchAll() as $r) {
                $d = $r['day'];
                if (!isset($daily[$d])) $daily[$d] = ['orders' => 0, 'revenue' => 0];
                $daily[$d]['orders'] += (int)$r['orders'];
                $daily[$d]['revenue'] += (float)$r['revenue'];
            }
        } catch (PDOException $e) {}
    }

    $pdo->exec('SET search_path TO public');
    ksort($daily);

    // Fill gaps
    $result = [];
    $start = new DateTime("-{$days} days");
    $end = new DateTime('today');
    while ($start <= $end) {
        $d = $start->format('Y-m-d');
        $result[] = [
            'date'    => $d,
            'orders'  => $daily[$d]['orders'] ?? 0,
            'revenue' => round($daily[$d]['revenue'] ?? 0, 2),
        ];
        $start->modify('+1 day');
    }

    return $result;
}

// ============================================================
// --- Per-Hotel Quick Stats ---
// ============================================================

function getHotelQuickStats(int $hotelId): array {
    $pdo = getSuperDatabase();
    $hotel = getHotelById($hotelId);

    $stats = [
        'orders_week'   => 0,
        'unread_msgs'   => 0,
        'last_login'    => null,
        'scans_week'    => 0,
    ];

    if (!$hotel || empty($hotel['schema_name'])) return $stats;

    $schema = preg_replace('/[^a-z0-9_]/', '', $hotel['schema_name']);
    try {
        $pdo->exec('SET search_path TO ' . $schema . ', public');

        $stmt = $pdo->query("SELECT COUNT(*) FROM room_service_orders WHERE created_at >= NOW() - INTERVAL '7 days'");
        $stats['orders_week'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM guest_messages WHERE status IN ('new', 'read')");
        $stats['unread_msgs'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT MAX(last_login) FROM admins");
        $stats['last_login'] = $stmt->fetchColumn() ?: null;

        $stmt = $pdo->query("SELECT COUNT(*) FROM qr_scans WHERE scanned_at >= NOW() - INTERVAL '7 days'");
        $stats['scans_week'] = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {}

    $pdo->exec('SET search_path TO public');
    return $stats;
}

// ============================================================
// --- Database Health ---
// ============================================================

function formatBytes(int $bytes): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

function getDatabaseHealth(): array {
    $pdo = getSuperDatabase();

    // Total DB size
    $totalSize = 0;
    try {
        $row = $pdo->query('SELECT pg_database_size(current_database()) AS s')->fetch();
        $totalSize = (int)($row['s'] ?? 0);
    } catch (PDOException $e) {}

    // Connections
    $connections = ['total' => 0, 'active' => 0, 'idle' => 0];
    try {
        $row = $pdo->query("
            SELECT COUNT(*) AS total,
                   COUNT(*) FILTER (WHERE state = 'active') AS active,
                   COUNT(*) FILTER (WHERE state = 'idle') AS idle
            FROM pg_stat_activity
        ")->fetch();
        $connections = [
            'total'  => (int)$row['total'],
            'active' => (int)$row['active'],
            'idle'   => (int)$row['idle'],
        ];
    } catch (PDOException $e) {}

    // Per-schema stats
    $schemas = [];
    $totalDeadTuples = 0;
    try {
        $rows = $pdo->query("
            SELECT schemaname,
                   SUM(n_live_tup) AS live_rows,
                   SUM(n_dead_tup) AS dead_rows,
                   MAX(last_autovacuum) AS last_vacuum
            FROM pg_stat_user_tables
            WHERE schemaname LIKE 'hotel_%' OR schemaname = 'public'
            GROUP BY schemaname
            ORDER BY live_rows DESC
        ")->fetchAll();
        foreach ($rows as $r) {
            $schemas[] = [
                'name'        => $r['schemaname'],
                'live_rows'   => (int)$r['live_rows'],
                'dead_rows'   => (int)$r['dead_rows'],
                'last_vacuum' => $r['last_vacuum'],
            ];
            $totalDeadTuples += (int)$r['dead_rows'];
        }
    } catch (PDOException $e) {}

    // Per-schema sizes
    try {
        $rows = $pdo->query("
            SELECT n.nspname AS schema_name,
                   SUM(pg_total_relation_size(c.oid)) AS total_size
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE (n.nspname LIKE 'hotel_%' OR n.nspname = 'public')
              AND c.relkind IN ('r', 'i', 't')
            GROUP BY n.nspname
            ORDER BY total_size DESC
        ")->fetchAll();
        $sizeMap = [];
        foreach ($rows as $r) {
            $sizeMap[$r['schema_name']] = (int)$r['total_size'];
        }
        foreach ($schemas as &$s) {
            $s['size'] = $sizeMap[$s['name']] ?? 0;
            $s['size_human'] = formatBytes($s['size']);
        }
        unset($s);
    } catch (PDOException $e) {}

    return [
        'total_size'       => $totalSize,
        'total_size_human' => formatBytes($totalSize),
        'connections'      => $connections,
        'total_dead'       => $totalDeadTuples,
        'schema_count'     => count(array_filter($schemas, fn($s) => str_starts_with($s['name'], 'hotel_'))),
        'schemas'          => $schemas,
        'timestamp'        => time(),
    ];
}

// ============================================================
// --- Filtered Audit Log ---
// ============================================================

function getFilteredAuditLog(int $limit, int $offset, ?int $hotelId = null, ?string $action = null, ?string $dateFrom = null, ?string $dateTo = null, ?string $search = null): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    $where = '1=1';
    $params = [];

    if ($hotelId) {
        $where .= ' AND al.hotel_id = ?';
        $params[] = $hotelId;
    }
    if ($action) {
        $where .= ' AND al.action = ?';
        $params[] = $action;
    }
    if ($dateFrom) {
        $where .= ' AND al.created_at >= ?';
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $where .= " AND al.created_at < (?::date + INTERVAL '1 day')";
        $params[] = $dateTo;
    }
    if ($search) {
        $where .= ' AND (al.details ILIKE ? OR sa.username ILIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare("
        SELECT al.*, sa.username AS admin_username, h.name AS hotel_name
        FROM public.super_admin_audit_log al
        LEFT JOIN public.super_admins sa ON sa.id = al.super_admin_id
        LEFT JOIN public.hotels h ON h.id = al.hotel_id
        WHERE {$where}
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countFilteredAuditLogs(?int $hotelId = null, ?string $action = null, ?string $dateFrom = null, ?string $dateTo = null, ?string $search = null): int {
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    $where = '1=1';
    $params = [];

    if ($hotelId) {
        $where .= ' AND al.hotel_id = ?';
        $params[] = $hotelId;
    }
    if ($action) {
        $where .= ' AND al.action = ?';
        $params[] = $action;
    }
    if ($dateFrom) {
        $where .= ' AND al.created_at >= ?';
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $where .= " AND al.created_at < (?::date + INTERVAL '1 day')";
        $params[] = $dateTo;
    }
    if ($search) {
        $where .= ' AND (al.details ILIKE ? OR sa.username ILIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM public.super_admin_audit_log al
        LEFT JOIN public.super_admins sa ON sa.id = al.super_admin_id
        WHERE {$where}
    ");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function getDistinctAuditActions(): array {
    ensureHotelsTable();
    $pdo = getSuperDatabase();
    return $pdo->query('SELECT DISTINCT action FROM public.super_admin_audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
}

// ============================================================
// --- Bulk Actions ---
// ============================================================

function bulkUpdateHotelStatus(array $hotelIds, bool $isActive): int {
    if (empty($hotelIds)) return 0;
    ensureHotelsTable();
    $pdo = getSuperDatabase();

    // Safely cast all IDs to int
    $ids = array_map('intval', $hotelIds);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("UPDATE public.hotels SET is_active = ?, updated_at = NOW() WHERE id IN ({$placeholders})");
    $params = [$isActive ? 'true' : 'false'];
    $params = array_merge($params, $ids);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function exportHotelsCsv(array $hotels): void {
    // BOM for Excel UTF-8 compatibility
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Nom', 'Slug', 'Type', 'Site URL', 'Admin URL', 'Statut', 'Créé le', 'Notes'], ';');

    foreach ($hotels as $h) {
        fputcsv($out, [
            $h['name'],
            $h['slug'],
            $h['type'] ?? 'hotel',
            $h['site_url'] ?? '',
            $h['admin_url'] ?? '',
            filter_var($h['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'Actif' : 'Inactif',
            $h['created_at'] ?? '',
            $h['notes'] ?? '',
        ], ';');
    }

    fclose($out);
}
