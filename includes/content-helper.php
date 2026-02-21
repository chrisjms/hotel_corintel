<?php
/**
 * Content Helper for Public Pages
 * Hotel Corintel
 *
 * Include this file at the top of your PHP pages to enable dynamic content loading
 * Maintains backward compatibility with the old image system
 */

require_once __DIR__ . '/functions.php';

// Initialize content tables on first load
try {
    initContentTables();
    cleanupLegacyStaticSections(); // Remove old non-hero static sections
    seedContentSections(); // Only seeds hero sections now
} catch (Exception $e) {
    // Tables may already exist, continue silently
}

/**
 * Get content blocks for a section
 *
 * @param string $sectionCode Section code (e.g., 'home_hero', 'home_services')
 * @return array Array of content blocks
 */
function content(string $sectionCode): array {
    static $cache = [];

    if (!isset($cache[$sectionCode])) {
        try {
            $cache[$sectionCode] = getContentBlocks($sectionCode, true);
        } catch (Exception $e) {
            $cache[$sectionCode] = [];
        }
    }

    return $cache[$sectionCode];
}

/**
 * Get a single content block by position
 *
 * @param string $sectionCode Section code
 * @param int $position Position (1-based)
 * @return array|null Content block or null
 */
function contentAt(string $sectionCode, int $position): ?array {
    $blocks = content($sectionCode);

    foreach ($blocks as $block) {
        if ($block['position'] == $position) {
            return $block;
        }
    }

    // Fallback: try to get by array index
    if (isset($blocks[$position - 1])) {
        return $blocks[$position - 1];
    }

    return null;
}

/**
 * Get first content block of a section
 *
 * @param string $sectionCode Section code
 * @return array|null Content block or null
 */
function contentFirst(string $sectionCode): ?array {
    $blocks = content($sectionCode);
    return !empty($blocks) ? $blocks[0] : null;
}

/**
 * Get content image URL with fallback
 *
 * @param string $sectionCode Section code
 * @param int $position Position (1-based)
 * @param string $fallback Fallback image path
 * @return string Image URL
 */
function contentImage(string $sectionCode, int $position, string $fallback = ''): string {
    $block = contentAt($sectionCode, $position);

    if ($block && !empty($block['image_filename'])) {
        return $block['image_filename'];
    }

    return $fallback;
}

/**
 * Get content title
 *
 * @param string $sectionCode Section code
 * @param int $position Position (1-based)
 * @param string $fallback Fallback text
 * @return string Title
 */
function contentTitle(string $sectionCode, int $position, string $fallback = ''): string {
    $block = contentAt($sectionCode, $position);

    if ($block && !empty($block['title'])) {
        return $block['title'];
    }

    return $fallback;
}

/**
 * Get content description
 *
 * @param string $sectionCode Section code
 * @param int $position Position (1-based)
 * @param string $fallback Fallback text
 * @return string Description
 */
function contentDescription(string $sectionCode, int $position, string $fallback = ''): string {
    $block = contentAt($sectionCode, $position);

    if ($block && !empty($block['description'])) {
        return $block['description'];
    }

    return $fallback;
}

/**
 * Check if section has any content
 *
 * @param string $sectionCode Section code
 * @return bool True if section has content
 */
function hasContent(string $sectionCode): bool {
    return !empty(content($sectionCode));
}

/**
 * Count content blocks in a section
 *
 * @param string $sectionCode Section code
 * @return int Number of blocks
 */
function contentCount(string $sectionCode): int {
    return count(content($sectionCode));
}

/**
 * Output content image tag
 *
 * @param string $sectionCode Section code
 * @param int $position Position (1-based)
 * @param string $fallback Fallback image path
 * @param string $class CSS class
 */
function contentImageTag(string $sectionCode, int $position, string $fallback, string $class = ''): void {
    $block = contentAt($sectionCode, $position);
    $src = ($block && !empty($block['image_filename'])) ? $block['image_filename'] : $fallback;
    $alt = ($block && !empty($block['image_alt'])) ? $block['image_alt'] : '';
    $classAttr = $class ? ' class="' . htmlspecialchars($class) . '"' : '';
    echo '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '"' . $classAttr . '>';
}

/**
 * Get background image style from content
 *
 * @param string $sectionCode Section code
 * @param int $position Position (1-based)
 * @param string $fallback Fallback image path
 * @return string CSS background-image value
 */
function contentBgImage(string $sectionCode, int $position, string $fallback): string {
    $src = contentImage($sectionCode, $position, $fallback);
    return "background-image: url('" . htmlspecialchars($src) . "');";
}

// =====================================================
// BACKWARD COMPATIBILITY WITH OLD IMAGE SYSTEM
// =====================================================

/**
 * Map old section/position to new content section
 * Note: Only hero/header sections remain as fixed sections
 * All other content sections are managed through the dynamic sections system
 */
function mapOldToNewSection(string $section, int $position): array {
    $mapping = [
        'home' => [
            1 => ['section' => 'home_hero', 'position' => 1],
            2 => ['section' => 'home_hero', 'position' => 2],
            3 => ['section' => 'home_hero', 'position' => 3],
        ],
        'services' => [
            1 => ['section' => 'services_hero', 'position' => 1],
        ],
        'activities' => [
            1 => ['section' => 'activities_hero', 'position' => 1],
        ],
        'contact' => [
            1 => ['section' => 'contact_hero', 'position' => 1],
        ],
    ];

    if (isset($mapping[$section][$position])) {
        return $mapping[$section][$position];
    }

    return ['section' => $section, 'position' => $position];
}

/**
 * Backward-compatible image function
 * First tries new content system, then falls back to old images table
 *
 * @param string $section Old section name
 * @param int $position Position number
 * @param string $fallback Fallback image path
 * @return string Image URL
 */
function img(string $section, int $position, string $fallback = ''): string {
    // Try new content system first
    $mapped = mapOldToNewSection($section, $position);
    $image = contentImage($mapped['section'], $mapped['position'], '');

    if (!empty($image)) {
        return $image;
    }

    // Fall back to old images table
    static $cache = [];
    $key = $section . '_' . $position;

    if (!isset($cache[$key])) {
        try {
            $oldImage = getImage($section, $position);
            $cache[$key] = $oldImage ? $oldImage['filename'] : $fallback;
        } catch (Exception $e) {
            $cache[$key] = $fallback;
        }
    }

    return $cache[$key];
}

/**
 * Backward-compatible section images function
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
 * Output image tag with dynamic source (backward compatible)
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
 * Get background image style (backward compatible)
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
