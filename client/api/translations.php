<?php
require_once __DIR__ . '/../../shared/bootstrap.php';
/**
 * Translations API Endpoint
 * Serves static translations as JSON for the client-side i18n engine.
 * Replaces the direct loading of js/translations.js.
 *
 * Caching: Uses file-based JSON cache, invalidated when lang/*.php files change.
 */

require_once HOTEL_ROOT . '/shared/includes/i18n.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Try to serve from file cache first
$cacheDir = HOTEL_ROOT . '/shared/cache';
$cacheFile = $cacheDir . '/translations_all.json';

if (file_exists($cacheFile)) {
    $cacheTime = filemtime($cacheFile);
    $langDir = HOTEL_ROOT . '/shared/lang';
    $stale = false;

    foreach (getSupportedLanguages() as $lang) {
        $langFile = $langDir . '/' . $lang . '.php';
        if (file_exists($langFile) && filemtime($langFile) > $cacheTime) {
            $stale = true;
            break;
        }
    }

    if (!$stale) {
        readfile($cacheFile);
        exit;
    }
}

// Build fresh translations
$data = getAllTranslationsNested();

// Write cache
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@file_put_contents($cacheFile, $json);

echo $json;
