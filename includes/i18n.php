<?php
/**
 * Internationalization (i18n) Helper
 * Loads PHP language files, provides t() function, builds nested structures for JS API.
 */

require_once __DIR__ . '/functions.php';

/**
 * Load translations for a given language code.
 * Returns flat associative array. Cached per request via static variable.
 */
function loadTranslations(string $lang): array {
    static $cache = [];

    if (isset($cache[$lang])) {
        return $cache[$lang];
    }

    $file = __DIR__ . '/../lang/' . $lang . '.php';
    if (!file_exists($file)) {
        $file = __DIR__ . '/../lang/fr.php';
        if (!file_exists($file)) {
            return [];
        }
    }

    $translations = require $file;
    $cache[$lang] = is_array($translations) ? $translations : [];
    return $cache[$lang];
}

/**
 * Translate a key for the current language with French fallback.
 *
 * @param string $key Dot-notation key (e.g., 'nav.home')
 * @param string|null $lang Override language (defaults to getCurrentLanguage())
 * @return string Translated string, or the key itself if not found
 */
function t(string $key, ?string $lang = null): string {
    $lang = $lang ?? getCurrentLanguage();
    $translations = loadTranslations($lang);

    if (isset($translations[$key])) {
        return $translations[$key];
    }

    // Fallback to French
    if ($lang !== 'fr') {
        $frTranslations = loadTranslations('fr');
        if (isset($frTranslations[$key])) {
            return $frTranslations[$key];
        }
    }

    return $key;
}

/**
 * Build nested associative array from flat dot-notation keys.
 * Used by the API endpoint to reconstruct the structure i18n.js expects.
 */
function buildNestedTranslations(array $flat): array {
    $nested = [];
    foreach ($flat as $key => $value) {
        $keys = explode('.', $key);
        $current = &$nested;
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        $current = $value;
        unset($current);
    }
    return $nested;
}

/**
 * Get all translations for all languages as nested structure.
 * Returns the exact shape that window.translations had in translations.js:
 * { languages: {...}, fr: {...}, en: {...}, es: {...}, it: {...} }
 */
function getAllTranslationsNested(): array {
    $supportedLanguages = getSupportedLanguages();
    $result = [];

    foreach ($supportedLanguages as $lang) {
        $flat = loadTranslations($lang);

        // Separate "languages.*" metadata from content keys
        $langKeys = [];
        $contentKeys = [];
        foreach ($flat as $key => $value) {
            if (strpos($key, 'languages.') === 0) {
                $langKeys[$key] = $value;
            } else {
                $contentKeys[$key] = $value;
            }
        }

        $result[$lang] = buildNestedTranslations($contentKeys);

        // Build "languages" block once (from first language file)
        if (!isset($result['languages'])) {
            $built = buildNestedTranslations($langKeys);
            $result['languages'] = $built['languages'] ?? [];
        }
    }

    return $result;
}
