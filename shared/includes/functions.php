<?php
/**
 * Helper Functions — Module Aggregator
 *
 * This file includes all function modules for backward compatibility.
 * All callers continue to work unchanged with:
 *   require_once HOTEL_ROOT . '/shared/includes/functions.php';
 *
 * Modules are loaded in dependency order (layered).
 */

require_once __DIR__ . '/../config/database.php';

// Layer 0: No cross-module dependencies
require_once __DIR__ . '/modules/utils.php';
require_once __DIR__ . '/modules/establishment-types.php';
require_once __DIR__ . '/modules/feature-toggles.php';
require_once __DIR__ . '/modules/upload.php';
require_once __DIR__ . '/modules/images-legacy.php';

// Layer 1: Depends on Layer 0
require_once __DIR__ . '/modules/settings.php';
require_once __DIR__ . '/modules/i18n-db.php';
require_once __DIR__ . '/modules/messages.php';
require_once __DIR__ . '/modules/rooms.php';
require_once __DIR__ . '/modules/push-notifications.php';

// Layer 2: Depends on settings
require_once __DIR__ . '/modules/hotel-identity.php';
require_once __DIR__ . '/modules/vat.php';
require_once __DIR__ . '/modules/room-service.php';
require_once __DIR__ . '/modules/room-service-stats.php';

// Layer 3: Content system
require_once __DIR__ . '/modules/content-management.php';
require_once __DIR__ . '/modules/section-properties.php';
require_once __DIR__ . '/modules/dynamic-sections.php';
require_once __DIR__ . '/modules/section-rendering.php';

// Layer 4: Depends on content + rooms
require_once __DIR__ . '/modules/qr-access.php';
require_once __DIR__ . '/modules/pages.php';
