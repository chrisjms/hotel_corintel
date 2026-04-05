<?php
/**
 * Header Dispatcher
 * Reads the header_style setting and includes the correct header template.
 *
 * Expects: $currentPageSlug (string) to be set before inclusion.
 * Sets: $headerStyle (available for body data-attribute in the calling page)
 */

$_headerHotelName = $hotelName ?? getHotelName();
$_headerLogoText = $logoText ?? getLogoText();
$_headerRoomSession = $roomSession ?? getRoomServiceSession();

// Ensure nav pages are available
if (!isset($navPages)) {
    initPagesTable();
    $navPages = getNavigationPages();
}
$_headerNavPages = $navPages;
$_headerCurrentSlug = $currentPageSlug ?? '';

if (!isset($headerStyle)) {
    $headerStyle = getSetting('header_style', 'classic');
}

switch ($headerStyle) {
    case 'centered':
        include __DIR__ . '/header-centered.php';
        break;
    case 'minimal':
        include __DIR__ . '/header-minimal.php';
        break;
    case 'classic':
    default:
        $headerStyle = 'classic';
        include __DIR__ . '/header-classic.php';
        break;
}
