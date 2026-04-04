<?php
/**
 * Feature Toggles — Shared Module
 *
 * Reads establishment_features from the public schema.
 * Used by admin and client panels to enforce feature access.
 * Toggles are managed via the Super Admin panel.
 *
 * All features default to enabled if no row exists in the DB.
 */

const AVAILABLE_FEATURES = [
    'room_service'   => ['label' => 'Room Service',        'description' => 'Commandes en chambre via QR code'],
    'messaging'      => ['label' => 'Messagerie',          'description' => 'Messages invités → réception'],
    'qr_codes'       => ['label' => 'QR Codes',            'description' => 'Scan QR pour accès chambre'],
    'multilingual'   => ['label' => 'Multilingue',         'description' => 'Support FR / EN / ES / IT'],
    'dynamic_pages'  => ['label' => 'Pages dynamiques',    'description' => 'Sections et contenu personnalisable'],
    'housekeeping'   => ['label' => 'Housekeeping',        'description' => 'Gestion ménage et inspections'],
];

/**
 * Check if a feature is enabled for a given hotel.
 * Returns true by default (if no row exists).
 * Results are cached per request to avoid repeated DB queries.
 */
function isFeatureEnabled(int $hotelId, string $featureKey): bool {
    static $cache = [];

    if ($hotelId <= 0) return true;

    $cacheKey = $hotelId . ':' . $featureKey;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT is_enabled FROM public.establishment_features WHERE hotel_id = ? AND feature_key = ?');
        $stmt->execute([$hotelId, $featureKey]);
        $row = $stmt->fetch();
        $result = $row ? filter_var($row['is_enabled'], FILTER_VALIDATE_BOOLEAN) : true;
    } catch (PDOException $e) {
        $result = true; // fail open — don't break the site
    }

    $cache[$cacheKey] = $result;
    return $result;
}

/**
 * Load all feature toggles for a hotel in one query.
 * Populates the internal cache for subsequent isFeatureEnabled() calls.
 */
function loadFeatureToggles(int $hotelId): array {
    static $loaded = [];

    if ($hotelId <= 0) return [];
    if (isset($loaded[$hotelId])) return $loaded[$hotelId];

    $toggles = [];
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT feature_key, is_enabled FROM public.establishment_features WHERE hotel_id = ?');
        $stmt->execute([$hotelId]);
        foreach ($stmt->fetchAll() as $row) {
            $toggles[$row['feature_key']] = filter_var($row['is_enabled'], FILTER_VALIDATE_BOOLEAN);
        }
    } catch (PDOException $e) {}

    $loaded[$hotelId] = $toggles;
    return $toggles;
}

/**
 * Shorthand: check feature for the current hotel context.
 * Uses getHotelId() from bootstrap.php.
 */
function featureEnabled(string $featureKey): bool {
    $hotelId = function_exists('getHotelId') ? getHotelId() : 0;
    return isFeatureEnabled($hotelId, $featureKey);
}

/**
 * Guard: redirect away if a feature is disabled.
 * Use at the top of admin/client pages to block access entirely.
 *
 * @param string $featureKey  Feature key to check
 * @param string $redirect    URL to redirect to (default: index.php)
 */
function requireFeature(string $featureKey, string $redirect = 'index.php'): void {
    if (!featureEnabled($featureKey)) {
        header('Location: ' . $redirect);
        exit;
    }
}
