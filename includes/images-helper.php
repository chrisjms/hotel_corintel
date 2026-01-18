<?php
/**
 * Image Helper for Public Pages
 * Hotel Corintel
 *
 * Include this file at the top of your PHP pages to enable dynamic image loading
 */

// Include database functions
require_once __DIR__ . '/functions.php';

/**
 * Get image URL for a specific slot
 * Falls back to default path if database is unavailable
 *
 * @param string $section Section name (home, services, rooms, activities, contact)
 * @param int $position Position number
 * @param string $fallback Fallback image path
 * @return string Image URL
 */
function img(string $section, int $position, string $fallback = ''): string {
    static $cache = [];

    $key = $section . '_' . $position;

    if (!isset($cache[$key])) {
        try {
            $image = getImage($section, $position);
            $cache[$key] = $image ? $image['filename'] : $fallback;
        } catch (Exception $e) {
            $cache[$key] = $fallback;
        }
    }

    return $cache[$key];
}

/**
 * Get all images for a section
 *
 * @param string $section Section name
 * @return array Array of images
 */
function sectionImages(string $section): array {
    try {
        return getImagesBySection($section);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Output image tag with dynamic source
 *
 * @param string $section Section name
 * @param int $position Position number
 * @param string $fallback Fallback image path
 * @param string $alt Alt text
 * @param string $class CSS class
 */
function imgTag(string $section, int $position, string $fallback, string $alt = '', string $class = ''): void {
    $src = img($section, $position, $fallback);
    $classAttr = $class ? ' class="' . htmlspecialchars($class) . '"' : '';
    echo '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '"' . $classAttr . '>';
}

/**
 * Get background image style
 *
 * @param string $section Section name
 * @param int $position Position number
 * @param string $fallback Fallback image path
 * @return string CSS background-image value
 */
function bgImg(string $section, int $position, string $fallback): string {
    $src = img($section, $position, $fallback);
    return "background-image: url('" . htmlspecialchars($src) . "');";
}
