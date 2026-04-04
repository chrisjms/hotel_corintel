<?php
/**
 * Hotel Identity Functions
 * Hotel name, logo, description, theme colors, contact information
 */

// Default hotel identity constants
if (!defined('DEFAULT_HOTEL_NAME')) {
    define('DEFAULT_HOTEL_NAME', 'Hôtel Corintel');
}
if (!defined('DEFAULT_LOGO_TEXT')) {
    define('DEFAULT_LOGO_TEXT', 'Bordeaux Est');
}

// Default contact information
if (!defined('DEFAULT_CONTACT_INFO')) {
    define('DEFAULT_CONTACT_INFO', [
        'phone' => '',
        'email' => '',
        'address' => '',
        'postal_code' => '',
        'city' => '',
        'country' => 'France',
        'maps_url' => '',
    ]);
}

// =====================================================
// HOTEL DESCRIPTION (with translation support)
// =====================================================

/**
 * Get the hotel description for footer (with translation support)
 * @param string|null $langCode Language code (defaults to current language)
 * @return string Description in requested language or fallback to default
 */
function getHotelDescription(?string $langCode = null): string {
    $langCode = $langCode ?? getCurrentLanguage();
    $defaultLang = getDefaultLanguage();

    // Try requested language first (non-default languages stored with suffix)
    if ($langCode !== $defaultLang) {
        $translated = getSetting('hotel_description_' . $langCode, '');
        if (!empty($translated)) {
            return $translated;
        }
    }

    // Fallback to default language (French)
    return (string) getSetting('hotel_description', '');
}

/**
 * Set the hotel description for footer (default language)
 */
function setHotelDescription(string $description): bool {
    return setSetting('hotel_description', $description);
}

/**
 * Get all hotel description translations
 * @return array Translations keyed by language code
 */
function getHotelDescriptionTranslations(): array {
    $translations = [];
    $defaultLang = getDefaultLanguage();

    // Default language (French)
    $translations[$defaultLang] = getSetting('hotel_description', '');

    // Other languages
    foreach (getSupportedLanguages() as $langCode) {
        if ($langCode !== $defaultLang) {
            $translations[$langCode] = getSetting('hotel_description_' . $langCode, '');
        }
    }

    return $translations;
}

/**
 * Save hotel description translations
 * @param array $translations Array of ['language_code' => 'description']
 * @return bool Success status
 */
function setHotelDescriptionTranslations(array $translations): bool {
    $success = true;
    $defaultLang = getDefaultLanguage();

    foreach ($translations as $langCode => $description) {
        if (!in_array($langCode, getSupportedLanguages())) {
            continue;
        }

        $description = trim($description);

        if ($langCode === $defaultLang) {
            // Default language uses base key
            $success = setSetting('hotel_description', $description) && $success;
        } else {
            // Other languages use suffixed keys
            $success = setSetting('hotel_description_' . $langCode, $description) && $success;
        }
    }

    return $success;
}

// =====================================================
// THEME COLORS
// =====================================================

/**
 * Default theme colors (matching style.css)
 */
function getDefaultThemeColors(): array {
    return [
        'color_primary' => '#8B6F47',
        'color_primary_dark' => '#6B5635',
        'color_secondary' => '#D4A574',
        'color_accent' => '#5C7C5E',
        'color_accent_light' => '#7A9B7C',
        'color_cream' => '#FAF6F0',
        'color_beige' => '#F0E6D8',
        'color_text' => '#3D3D3D',
        'color_text_light' => '#6B6B6B',
        'color_gold' => '#C9A962',
    ];
}

/**
 * Get all theme settings
 */
function getThemeSettings(): array {
    $defaults = getDefaultThemeColors();
    $settings = [];

    foreach ($defaults as $key => $default) {
        $settings[$key] = getSetting('theme_' . $key, $default);
    }

    return $settings;
}

/**
 * Save theme settings
 */
function saveThemeSettings(array $colors): bool {
    $defaults = getDefaultThemeColors();
    $success = true;

    foreach ($defaults as $key => $default) {
        if (isset($colors[$key])) {
            // Validate hex color format
            $color = $colors[$key];
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                if (!setSetting('theme_' . $key, $color)) {
                    $success = false;
                }
            }
        }
    }

    return $success;
}

/**
 * Reset theme to default colors
 */
function resetThemeSettings(): bool {
    $defaults = getDefaultThemeColors();
    $success = true;

    foreach ($defaults as $key => $default) {
        if (!setSetting('theme_' . $key, $default)) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Generate CSS variables from theme settings
 * Returns inline CSS to override :root variables
 */
function getThemeCSS(): string {
    $settings = getThemeSettings();
    $defaults = getDefaultThemeColors();

    $css = [];
    $cssVarMap = [
        'color_primary' => '--color-primary',
        'color_primary_dark' => '--color-primary-dark',
        'color_secondary' => '--color-secondary',
        'color_accent' => '--color-accent',
        'color_accent_light' => '--color-accent-light',
        'color_cream' => '--color-cream',
        'color_beige' => '--color-beige',
        'color_text' => '--color-text',
        'color_text_light' => '--color-text-light',
        'color_gold' => '--color-gold',
    ];

    foreach ($cssVarMap as $key => $cssVar) {
        $value = $settings[$key] ?? $defaults[$key];
        $css[] = "{$cssVar}: {$value};";
    }

    if (empty($css)) {
        return '';
    }

    return '<style id="theme-override">:root { ' . implode(' ', $css) . ' }</style>';
}

// =====================================================
// HOTEL NAME & LOGO
// =====================================================

/**
 * Get the configured hotel name
 * @param bool $withPrefix If true, returns "Hôtel Name", if false returns just "Name"
 * @return string The hotel name
 */
function getHotelName(bool $withPrefix = true): string {
    $name = getSetting('hotel_name', DEFAULT_HOTEL_NAME);

    if (!$withPrefix) {
        // Remove "Hôtel " or "Hotel " prefix if present
        $name = preg_replace('/^(Hôtel|Hotel)\s+/i', '', $name);
    }

    return $name;
}

/**
 * Save the hotel name setting
 * @param string $name The hotel name to save
 * @return bool True on success
 */
function setHotelName(string $name): bool {
    $name = trim($name);
    if (empty($name)) {
        return false;
    }
    return setSetting('hotel_name', $name);
}

/**
 * Get the configured logo text (subtitle under hotel name)
 * @return string The logo text
 */
function getLogoText(): string {
    return getSetting('logo_text', DEFAULT_LOGO_TEXT);
}

/**
 * Save the logo text setting
 * @param string $text The logo text to save
 * @return bool True on success
 */
function setLogoText(string $text): bool {
    $text = trim($text);
    if (empty($text)) {
        return false;
    }
    return setSetting('logo_text', $text);
}

/**
 * Get hotel identity settings for use in templates
 * Returns an array with various forms of the hotel name and branding
 * @return array
 */
function getHotelIdentity(): array {
    $fullName = getHotelName(true);
    $shortName = getHotelName(false);
    $logoText = getLogoText();

    return [
        'full_name' => $fullName,                  // "Hôtel Corintel"
        'short_name' => $shortName,                // "Corintel"
        'logo_text' => $logoText,                  // "Bordeaux Est"
        'name_possessive_fr' => "l'" . $fullName,  // "l'Hôtel Corintel"
        'name_at_fr' => "à l'" . $fullName,        // "à l'Hôtel Corintel"
    ];
}

/**
 * Output hotel name JavaScript variable for client-side use
 * Call this in the <head> section before other scripts
 * @return string
 */
function getHotelNameJS(): string {
    $identity = getHotelIdentity();
    return '<script>window.hotelIdentity = ' . json_encode($identity, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ';</script>';
}

// =====================================================
// CONTACT INFORMATION
// =====================================================

/**
 * Get a single contact setting
 * @param string $key Contact field key
 * @param string $default Default value if not set
 * @return string
 */
function getContactSetting(string $key, string $default = ''): string {
    $value = getSetting('contact_' . $key, null);
    if ($value === null) {
        return DEFAULT_CONTACT_INFO[$key] ?? $default;
    }
    return $value;
}

/**
 * Get all contact information
 * @return array
 */
function getContactInfo(): array {
    return [
        'phone' => getContactSetting('phone'),
        'email' => getContactSetting('email'),
        'address' => getContactSetting('address'),
        'postal_code' => getContactSetting('postal_code'),
        'city' => getContactSetting('city'),
        'country' => getContactSetting('country', 'France'),
        'maps_url' => getContactSetting('maps_url'),
    ];
}

/**
 * Save all contact information
 * @param array $data Contact data array
 * @return bool True if all settings saved successfully
 */
function saveContactInfo(array $data): bool {
    $fields = ['phone', 'email', 'address', 'postal_code', 'city', 'country', 'maps_url'];
    $success = true;

    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $value = trim($data[$field]);
            if (!setSetting('contact_' . $field, $value)) {
                $success = false;
            }
        }
    }

    return $success;
}

/**
 * Get formatted full address
 * @param bool $html Whether to format with HTML line breaks
 * @return string
 */
function getFormattedAddress(bool $html = true): string {
    $contact = getContactInfo();
    $parts = [];

    if (!empty($contact['address'])) {
        $parts[] = $contact['address'];
    }

    $cityLine = '';
    if (!empty($contact['postal_code'])) {
        $cityLine .= $contact['postal_code'];
    }
    if (!empty($contact['city'])) {
        $cityLine .= (!empty($cityLine) ? ' ' : '') . strtoupper($contact['city']);
    }
    if (!empty($cityLine)) {
        $parts[] = $cityLine;
    }

    if (!empty($contact['country'])) {
        $parts[] = $contact['country'];
    }

    $separator = $html ? '<br>' : ', ';
    return implode($separator, $parts);
}

/**
 * Get contact phone number, optionally formatted for tel: link
 * @param bool $forLink If true, returns number suitable for tel: link
 * @return string
 */
function getContactPhone(bool $forLink = false): string {
    $phone = getContactSetting('phone');
    if ($forLink && !empty($phone)) {
        // Remove spaces, dashes, dots for tel: link
        return preg_replace('/[\s\-\.]/', '', $phone);
    }
    return $phone;
}

/**
 * Get contact email
 * @return string
 */
function getContactEmail(): string {
    return getContactSetting('email');
}

/**
 * Get Google Maps URL
 * @return string
 */
function getContactMapsUrl(): string {
    return getContactSetting('maps_url');
}

/**
 * Check if contact info has required fields filled
 * @return bool
 */
function hasContactInfo(): bool {
    $contact = getContactInfo();
    return !empty($contact['phone']) || !empty($contact['email']) || !empty($contact['address']);
}
