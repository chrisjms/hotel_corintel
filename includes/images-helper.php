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

/**
 * Get a content block (image + associated text)
 *
 * @param string $section Section name
 * @param int $position Position number
 * @return array|null Content block data or null
 */
function block(string $section, int $position): ?array {
    static $cache = [];
    $key = $section . '_' . $position;

    if (!isset($cache[$key])) {
        try {
            $cache[$key] = getContentBlock($section, $position);
        } catch (Exception $e) {
            $cache[$key] = null;
        }
    }

    return $cache[$key];
}

/**
 * Get all content blocks for a section
 *
 * @param string $section Section name
 * @return array Array of content blocks indexed by position
 */
function blocks(string $section): array {
    try {
        return getContentBlocks($section);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get text from a content block with fallback
 * Useful for displaying database content or falling back to translation/default
 *
 * @param string $section Section name
 * @param int $position Position number
 * @param string $field Field name (heading, content, alt)
 * @param string $fallback Fallback text (can be a translation key value)
 * @return string The text content
 */
function blockField(string $section, int $position, string $field, string $fallback = ''): string {
    $contentBlock = block($section, $position);
    if ($contentBlock && !empty($contentBlock[$field])) {
        return $contentBlock[$field];
    }
    return $fallback;
}

/**
 * Output escaped text from a content block
 *
 * @param string $section Section name
 * @param int $position Position number
 * @param string $field Field name
 * @param string $fallback Fallback text
 */
function echoBlock(string $section, int $position, string $field, string $fallback = ''): void {
    echo htmlspecialchars(blockField($section, $position, $field, $fallback));
}

/**
 * Check if a content block has text content
 *
 * @param string $section Section name
 * @param int $position Position number
 * @return bool True if block has heading or content
 */
function hasBlockContent(string $section, int $position): bool {
    $contentBlock = block($section, $position);
    return $contentBlock && (!empty($contentBlock['heading']) || !empty($contentBlock['content']));
}
