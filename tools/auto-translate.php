<?php
/**
 * Auto-Translate Tool (CLI only)
 * Uses DeepL API to generate translations from French source.
 *
 * Usage:
 *   DEEPL_API_KEY=xxx php tools/auto-translate.php              # missing keys only
 *   DEEPL_API_KEY=xxx php tools/auto-translate.php --force       # re-translate all
 *   DEEPL_API_KEY=xxx php tools/auto-translate.php --lang=en     # one language only
 *   DEEPL_API_KEY=xxx php tools/auto-translate.php --dry-run     # preview changes
 *   DEEPL_API_KEY=xxx php tools/auto-translate.php --key=nav.home # one key only
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/../includes/functions.php';

// --- Configuration ---

$deeplApiKey = getenv('DEEPL_API_KEY') ?: getSetting('deepl_api_key', '');
if (empty($deeplApiKey)) {
    echo "ERROR: No DeepL API key found.\n";
    echo "Set DEEPL_API_KEY environment variable or store in DB settings table.\n";
    exit(1);
}

// DeepL free tier endpoint (change to api.deepl.com for paid tier)
$deeplEndpoint = 'https://api-free.deepl.com/v2/translate';

// Map our language codes to DeepL target language codes
$deeplLangMap = [
    'en' => 'EN-GB',
    'es' => 'ES',
    'it' => 'IT',
];

// --- Parse CLI arguments ---

$args = parseCliArgs($argv);
$force = isset($args['force']);
$dryRun = isset($args['dry-run']);
$targetLang = $args['lang'] ?? null;
$specificKey = $args['key'] ?? null;

// --- Load French source (source of truth) ---

$frFile = __DIR__ . '/../lang/fr.php';
if (!file_exists($frFile)) {
    echo "ERROR: French source file not found: $frFile\n";
    exit(1);
}
$frTranslations = require $frFile;
echo "French source: " . count($frTranslations) . " keys\n";

// --- Determine target languages ---

$targetLangs = $targetLang ? [$targetLang] : array_keys($deeplLangMap);

foreach ($targetLangs as $lang) {
    if (!isset($deeplLangMap[$lang])) {
        echo "WARNING: No DeepL mapping for '$lang', skipping.\n";
        continue;
    }

    echo "\n=== Translating to $lang (" . $deeplLangMap[$lang] . ") ===\n";

    $langFile = __DIR__ . '/../lang/' . $lang . '.php';
    $existing = file_exists($langFile) ? require $langFile : [];

    // Determine which keys need translation
    $toTranslate = [];
    foreach ($frTranslations as $key => $value) {
        // Skip languages metadata (identical across all files)
        if (strpos($key, 'languages.') === 0) continue;
        if ($specificKey !== null && $key !== $specificKey) continue;

        if ($force || !isset($existing[$key]) || trim($existing[$key]) === '') {
            $toTranslate[$key] = $value;
        }
    }

    if (empty($toTranslate)) {
        echo "No keys to translate.\n";
        continue;
    }

    echo count($toTranslate) . " keys to translate.\n";

    if ($dryRun) {
        foreach ($toTranslate as $key => $value) {
            $preview = mb_strlen($value) > 60 ? mb_substr($value, 0, 60) . '...' : $value;
            echo "  WOULD TRANSLATE: $key => $preview\n";
        }
        continue;
    }

    // Batch translate (DeepL supports up to 50 texts per request)
    $batches = array_chunk($toTranslate, 50, true);
    $translated = [];
    $batchNum = 0;

    foreach ($batches as $batch) {
        $batchNum++;
        $texts = array_values($batch);
        $keys = array_keys($batch);

        // Protect placeholders from translation using XML tags
        $protectedTexts = array_map(function ($text) {
            return str_replace(
                ['{hotelName}', '{hotelShortName}'],
                ['<x translate="no">{hotelName}</x>', '<x translate="no">{hotelShortName}</x>'],
                $text
            );
        }, $texts);

        echo "  Batch $batchNum/" . count($batches) . " (" . count($texts) . " texts)...";

        $result = callDeepL($deeplEndpoint, $deeplApiKey, $protectedTexts, $deeplLangMap[$lang]);

        if ($result === false) {
            echo " FAILED\n";
            echo "ERROR: DeepL API call failed. Stopping.\n";
            exit(1);
        }

        // Restore placeholders by stripping XML protection tags
        foreach ($result as $i => $translatedText) {
            $clean = preg_replace('/<x[^>]*>\s*/', '', $translatedText);
            $clean = str_replace('</x>', '', $clean);
            $translated[$keys[$i]] = trim($clean);
        }

        echo " OK\n";

        // Rate limit between batches
        if (count($batches) > 1 && $batchNum < count($batches)) {
            usleep(500000);
        }
    }

    // Merge: existing + newly translated
    $merged = array_merge($existing, $translated);

    // Ensure languages metadata is present (copy from French)
    foreach ($frTranslations as $key => $value) {
        if (strpos($key, 'languages.') === 0) {
            $merged[$key] = $value;
        }
    }

    // Reorder to match French file key order
    $ordered = [];
    foreach ($frTranslations as $key => $value) {
        if (isset($merged[$key])) {
            $ordered[$key] = $merged[$key];
        }
    }

    // Write the updated language file
    writeLangFile($langFile, $lang, $ordered);
    echo "Wrote " . count($ordered) . " translations to lang/$lang.php\n";
}

// Invalidate translation cache
$cacheFiles = glob(__DIR__ . '/../cache/translations_*.json');
if ($cacheFiles) {
    foreach ($cacheFiles as $cf) {
        @unlink($cf);
    }
}
$allCache = __DIR__ . '/../cache/translations_all.json';
if (file_exists($allCache)) {
    @unlink($allCache);
}
echo "\nCache invalidated.\nDone.\n";


// === Helper Functions ===

function callDeepL(string $endpoint, string $apiKey, array $texts, string $targetLang): array|false {
    $postFields = ['target_lang' => $targetLang, 'source_lang' => 'FR', 'tag_handling' => 'html'];
    foreach ($texts as $text) {
        $postFields['text'][] = $text;
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_HTTPHEADER => [
            'Authorization: DeepL-Auth-Key ' . $apiKey,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "\ncURL error: $error\n";
        return false;
    }

    if ($httpCode !== 200) {
        echo "\nDeepL API error (HTTP $httpCode): $response\n";
        return false;
    }

    $data = json_decode($response, true);
    if (!isset($data['translations'])) {
        echo "\nUnexpected DeepL response: $response\n";
        return false;
    }

    return array_map(fn($t) => $t['text'], $data['translations']);
}

function writeLangFile(string $path, string $langCode, array $translations): void {
    $langNames = ['fr' => 'French', 'en' => 'English', 'es' => 'Spanish', 'it' => 'Italian'];
    $langName = $langNames[$langCode] ?? $langCode;

    $output = "<?php\n";
    $output .= "/**\n * $langName translations\n * Auto-generated by tools/auto-translate.php\n * Last updated: " . date('Y-m-d H:i:s') . "\n */\n";
    $output .= "return [\n";

    $lastGroup = '';
    foreach ($translations as $key => $value) {
        $group = explode('.', $key)[0];
        if ($group !== $lastGroup && $lastGroup !== '') {
            $output .= "\n";
        }
        $lastGroup = $group;
        $escaped = str_replace("'", "\\'", $value);
        $output .= "    '$key' => '$escaped',\n";
    }

    $output .= "];\n";
    file_put_contents($path, $output);
}

function parseCliArgs(array $argv): array {
    $args = [];
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $arg = substr($arg, 2);
            if (strpos($arg, '=') !== false) {
                [$key, $value] = explode('=', $arg, 2);
                $args[$key] = $value;
            } else {
                $args[$arg] = true;
            }
        }
    }
    return $args;
}
