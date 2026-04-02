<?php
/**
 * Bootstrap - Multi-tenant hotel platform
 *
 * Include this at the top of every entry point (client pages, admin pages, APIs).
 * Resolves the hotel context from the subdomain and sets dynamic constants.
 */

require_once __DIR__ . '/config/hotel-context.php';

$ctx = HotelContext::getInstance();
$ctx->resolve();

/**
 * Get current hotel ID (shorthand)
 */
function getHotelId(): int {
    return HotelContext::getInstance()->getHotelId() ?? 0;
}

// Dynamic constants based on hotel context
if (!defined('SITE_URL') && $ctx->getSiteUrl()) {
    define('SITE_URL', $ctx->getSiteUrl());
}

$hotelId = $ctx->getHotelId();
if ($hotelId && !defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/uploads/hotel_' . $hotelId . '/');
    define('UPLOAD_URL', SITE_URL . '/uploads/hotel_' . $hotelId . '/');
}
