<?php
/**
 * Bootstrap - Multi-tenant hotel platform
 *
 * Include this at the top of every entry point (client pages, admin pages, APIs).
 * Resolves the hotel context from the subdomain and sets dynamic constants.
 *
 * Usage from any surface folder (client/, admin/, superadmin/):
 *   require_once __DIR__ . '/../shared/bootstrap.php';
 */

// Absolute root path of the project (repo root)
if (!defined('HOTEL_ROOT')) {
    define('HOTEL_ROOT', dirname(__DIR__));
}

require_once __DIR__ . '/config/hotel-context.php';

$ctx = HotelContext::getInstance();
$ctx->resolve();

// For client and admin contexts, a valid hotel is mandatory
if ($ctx->isHotelClient() || $ctx->isHotelAdmin()) {
    $ctx->requireHotel();
}

/**
 * Get current hotel ID (shorthand)
 */
if (!function_exists('getHotelId')) {
    function getHotelId(): int {
        return HotelContext::getInstance()->getHotelId() ?? 0;
    }
}

/**
 * Get current establishment type (shorthand)
 */
if (!function_exists('getEstablishmentType')) {
    function getEstablishmentType(): string {
        return HotelContext::getInstance()->getType();
    }
}

// Dynamic constants based on hotel context
if (!defined('SITE_URL') && $ctx->getSiteUrl()) {
    define('SITE_URL', $ctx->getSiteUrl());
}

$hotelId = $ctx->getHotelId();
if ($hotelId && !defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', HOTEL_ROOT . '/shared/uploads/hotel_' . $hotelId . '/');
    define('UPLOAD_URL', SITE_URL . '/uploads/hotel_' . $hotelId . '/');
}
