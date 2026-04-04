<?php
/**
 * i18n Database Functions
 * Languages, item translations, category translations
 */

function getSupportedLanguages(): array {
    return ['fr', 'en', 'es', 'it'];
}

/**
 * Get default language code
 */
function getDefaultLanguage(): string {
    return 'fr';
}

/**
 * Get current language from cookie/session or default
 */
function getCurrentLanguage(): string {
    // Check cookie first (set by JS i18n system)
    if (isset($_COOKIE['hotel_corintel_lang'])) {
        $lang = $_COOKIE['hotel_corintel_lang'];
        if (in_array($lang, getSupportedLanguages())) {
            return $lang;
        }
    }
    // Check query parameter
    if (isset($_GET['lang'])) {
        $lang = $_GET['lang'];
        if (in_array($lang, getSupportedLanguages())) {
            return $lang;
        }
    }
    return getDefaultLanguage();
}

/**
 * Detect browser language from Accept-Language header
 * Returns the best matching supported language or null
 */
function detectBrowserLanguage(): ?string {
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return null;
    }

    $supportedLanguages = getSupportedLanguages();
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

    // Parse Accept-Language header (e.g., "en-US,en;q=0.9,fr;q=0.8")
    $languages = [];
    foreach (explode(',', $acceptLanguage) as $part) {
        $part = trim($part);
        $qValue = 1.0;

        if (strpos($part, ';q=') !== false) {
            list($lang, $q) = explode(';q=', $part);
            $qValue = (float) $q;
        } else {
            $lang = $part;
        }

        // Extract primary language code (e.g., "en" from "en-US")
        $primaryLang = strtolower(substr($lang, 0, 2));
        $languages[$primaryLang] = max($languages[$primaryLang] ?? 0, $qValue);
    }

    // Sort by q-value descending
    arsort($languages);

    // Find first supported language
    foreach ($languages as $lang => $q) {
        if (in_array($lang, $supportedLanguages)) {
            return $lang;
        }
    }

    return null;
}

/**
 * Save item translations
 * @param int $itemId Item ID
 * @param array $translations Array of ['language_code' => ['name' => '', 'description' => '']]
 */
function saveItemTranslations(int $itemId, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $data) {
        if (!in_array($langCode, getSupportedLanguages())) {
            continue;
        }

        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');

        // Skip if name is empty
        if (empty($name)) {
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO room_service_item_translations (item_id, language_code, name, description, hotel_id)
                VALUES (?, ?, ?, ?, ?)
                ON CONFLICT (item_id, language_code) DO UPDATE SET name = EXCLUDED.name, description = EXCLUDED.description
            ');
            $success = $stmt->execute([$itemId, $langCode, $name, $description, getHotelId()]) && $success;
        } catch (PDOException $e) {
            error_log('Error saving item translation: ' . $e->getMessage());
            $success = false;
        }
    }

    return $success;
}

/**
 * Get item translations for all languages
 * @param int $itemId Item ID
 * @return array Translations keyed by language code
 */
function getItemTranslations(int $itemId): array {
    $pdo = getDatabase();
    $translations = [];

    try {
        $stmt = $pdo->prepare('
            SELECT language_code, name, description
            FROM room_service_item_translations
            WHERE item_id = ? AND hotel_id = ?
        ');
        $stmt->execute([$itemId, getHotelId()]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $translations[$row['language_code']] = [
                'name' => $row['name'],
                'description' => $row['description']
            ];
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    return $translations;
}

/**
 * Get translated item name with fallback
 * @param int $itemId Item ID
 * @param string|null $langCode Language code (defaults to current language)
 * @return string|null Translated name or null if not found
 */
function getItemTranslatedName(int $itemId, ?string $langCode = null): ?string {
    $langCode = $langCode ?? getCurrentLanguage();
    $defaultLang = getDefaultLanguage();
    $pdo = getDatabase();

    try {
        // Try requested language first
        $stmt = $pdo->prepare('
            SELECT name FROM room_service_item_translations
            WHERE item_id = ? AND language_code = ? AND hotel_id = ?
        ');
        $stmt->execute([$itemId, $langCode, getHotelId()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['name'])) {
            return $result['name'];
        }

        // Fallback to default language
        if ($langCode !== $defaultLang) {
            $stmt->execute([$itemId, $defaultLang, getHotelId()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['name'])) {
                return $result['name'];
            }
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    return null;
}

/**
 * Get translated item with fallback to base item
 * @param array $item Base item array
 * @param string|null $langCode Language code
 * @return array Item with translated name and description
 */
function getItemWithTranslation(array $item, ?string $langCode = null): array {
    $langCode = $langCode ?? getCurrentLanguage();
    $translations = getItemTranslations($item['id']);

    // Try current language
    if (isset($translations[$langCode]) && !empty($translations[$langCode]['name'])) {
        $item['name'] = $translations[$langCode]['name'];
        $item['description'] = $translations[$langCode]['description'] ?? $item['description'];
    }
    // Fallback to default language
    elseif ($langCode !== getDefaultLanguage() && isset($translations[getDefaultLanguage()])) {
        $item['name'] = $translations[getDefaultLanguage()]['name'] ?? $item['name'];
        $item['description'] = $translations[getDefaultLanguage()]['description'] ?? $item['description'];
    }

    return $item;
}

/**
 * Get room service items with translations
 * @param bool $activeOnly Only return active items
 * @param string|null $langCode Language code
 * @return array Items with translations applied
 */
function getRoomServiceItemsTranslated(bool $activeOnly = false, ?string $langCode = null): array {
    $items = getRoomServiceItems($activeOnly);
    $langCode = $langCode ?? getCurrentLanguage();

    return array_map(function($item) use ($langCode) {
        return getItemWithTranslation($item, $langCode);
    }, $items);
}

/**
 * Save category translations
 * @param string $categoryCode Category code
 * @param array $translations Array of ['language_code' => 'name']
 */
function saveCategoryTranslations(string $categoryCode, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $name) {
        if (!in_array($langCode, getSupportedLanguages())) {
            continue;
        }

        $name = trim($name);
        if (empty($name)) {
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO room_service_category_translations (category_code, language_code, name, hotel_id)
                VALUES (?, ?, ?, ?)
                ON CONFLICT (category_code, language_code) DO UPDATE SET name = EXCLUDED.name
            ');
            $success = $stmt->execute([$categoryCode, $langCode, $name, getHotelId()]) && $success;
        } catch (PDOException $e) {
            error_log('Error saving category translation: ' . $e->getMessage());
            $success = false;
        }
    }

    return $success;
}

/**
 * Get category translations for all languages
 * @param string $categoryCode Category code
 * @return array Translations keyed by language code
 */
function getCategoryTranslations(string $categoryCode): array {
    $pdo = getDatabase();
    $translations = [];

    try {
        $stmt = $pdo->prepare('
            SELECT language_code, name
            FROM room_service_category_translations
            WHERE category_code = ? AND hotel_id = ?
        ');
        $stmt->execute([$categoryCode, getHotelId()]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $translations[$row['language_code']] = $row['name'];
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    return $translations;
}

/**
 * Get translated category name with fallback
 * @param string $categoryCode Category code
 * @param string|null $langCode Language code
 * @return string|null Translated name or null
 */
function getCategoryTranslatedName(string $categoryCode, ?string $langCode = null): ?string {
    $langCode = $langCode ?? getCurrentLanguage();
    $defaultLang = getDefaultLanguage();
    $pdo = getDatabase();

    try {
        // Try requested language first
        $stmt = $pdo->prepare('
            SELECT name FROM room_service_category_translations
            WHERE category_code = ? AND language_code = ? AND hotel_id = ?
        ');
        $stmt->execute([$categoryCode, $langCode, getHotelId()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['name'])) {
            return $result['name'];
        }

        // Fallback to default language
        if ($langCode !== $defaultLang) {
            $stmt->execute([$categoryCode, $defaultLang, getHotelId()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['name'])) {
                return $result['name'];
            }
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    return null;
}

/**
 * Get room service categories with translations
 * @param string|null $langCode Language code
 * @return array Categories with translated names
 */
function getRoomServiceCategoriesTranslated(?string $langCode = null): array {
    $langCode = $langCode ?? getCurrentLanguage();
    $defaultLang = getDefaultLanguage();

    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT code, name FROM room_service_categories WHERE is_active = TRUE AND hotel_id = ? ORDER BY position ASC');
        $stmt->execute([getHotelId()]);
        $categories = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $translatedName = getCategoryTranslatedName($row['code'], $langCode);
            $categories[$row['code']] = $translatedName ?? $row['name'];
        }

        if (!empty($categories)) {
            return $categories;
        }
    } catch (PDOException $e) {
        // Table doesn't exist, use fallback
    }

    // Fallback to hardcoded categories
    return getRoomServiceCategories();
}

/**
 * Get all categories with full details including translations (for admin)
 * @return array Categories with translations for each language
 */
function getRoomServiceCategoriesAllWithTranslations(): array {
    $categories = getRoomServiceCategoriesAll();

    foreach ($categories as &$category) {
        $category['translations'] = getCategoryTranslations($category['code']);
    }

    return $categories;
}
