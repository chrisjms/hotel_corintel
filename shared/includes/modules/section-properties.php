<?php
/**
 * Section Properties Functions
 * Icons, features, services, gallery items, backgrounds, position, alignment, links
 */

function getAvailableIcons(): array {
    return [
        // Nature & Outdoor
        'garden' => [
            'name' => 'Jardin',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>'
        ],
        'tree' => [
            'name' => 'Arbre',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22v-7"/><path d="M9 22h6"/><path d="M12 15L8 9h8l-4 6z"/><path d="M12 9L8 3h8l-4 6z"/></svg>'
        ],
        'sun' => [
            'name' => 'Soleil',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>'
        ],
        'flower' => [
            'name' => 'Fleur',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/></svg>'
        ],
        'mountain' => [
            'name' => 'Montagne',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3l4 8 5-5 5 15H2L8 3z"/></svg>'
        ],
        'countryside' => [
            'name' => 'Campagne',
            'category' => 'nature',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>'
        ],

        // Amenities
        'terrace' => [
            'name' => 'Terrasse',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 11h1a3 3 0 0 1 0 6h-1"/><path d="M2 11h14v7a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-7z"/><path d="M6 7v4"/><path d="M10 7v4"/><path d="M14 7v4"/></svg>'
        ],
        'lounge' => [
            'name' => 'Salon',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>'
        ],
        'parking' => [
            'name' => 'Parking',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2" ry="2"/><path d="M16 8h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1"/></svg>'
        ],
        'wifi' => [
            'name' => 'WiFi',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1"/></svg>'
        ],
        'pool' => [
            'name' => 'Piscine',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20"/><path d="M2 16c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/><path d="M2 20c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/></svg>'
        ],
        'spa' => [
            'name' => 'Spa',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2c-4 4-6 8-6 10a6 6 0 1 0 12 0c0-2-2-6-6-10z"/></svg>'
        ],
        'gym' => [
            'name' => 'Fitness',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6.5 6.5a2 2 0 0 1 3 0L12 9l2.5-2.5a2 2 0 0 1 3 0l2 2a2 2 0 0 1 0 3L17 14l2.5 2.5a2 2 0 0 1 0 3l-2 2a2 2 0 0 1-3 0L12 19l-2.5 2.5a2 2 0 0 1-3 0l-2-2a2 2 0 0 1 0-3L7 14l-2.5-2.5a2 2 0 0 1 0-3l2-2z"/></svg>'
        ],
        'aircon' => [
            'name' => 'Climatisation',
            'category' => 'amenities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="8" rx="2"/><path d="M6 12v4"/><path d="M10 12v6"/><path d="M14 12v4"/><path d="M18 12v6"/></svg>'
        ],

        // Dining
        'restaurant' => [
            'name' => 'Restaurant',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>'
        ],
        'bar' => [
            'name' => 'Bar',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 22h8"/><path d="M12 11v11"/><path d="M5 3l7 8 7-8"/></svg>'
        ],
        'coffee' => [
            'name' => 'Café',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>'
        ],
        'wine' => [
            'name' => 'Vin',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 22h8"/><path d="M12 15v7"/><path d="M5 3h14l-1.5 9a5.5 5.5 0 0 1-11 0L5 3z"/></svg>'
        ],
        'room-service' => [
            'name' => 'Room Service',
            'category' => 'dining',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15h16a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1z"/><path d="M12 4a6 6 0 0 1 6 6v5H6v-5a6 6 0 0 1 6-6z"/></svg>'
        ],

        // Comfort
        'bed' => [
            'name' => 'Lit',
            'category' => 'comfort',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4v16"/><path d="M22 4v16"/><path d="M2 12h20"/><path d="M2 20h20"/><rect x="6" y="8" width="12" height="4" rx="1"/></svg>'
        ],
        'fireplace' => [
            'name' => 'Cheminée',
            'category' => 'comfort',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 17c-2 0-3-2-3-4 0-3 3-5 3-7 0 2 3 4 3 7 0 2-1 4-3 4z"/></svg>'
        ],

        // Location
        'map-pin' => [
            'name' => 'Localisation',
            'category' => 'location',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'
        ],
        'vineyard' => [
            'name' => 'Vignoble',
            'category' => 'location',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="6" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="12" r="3"/><path d="M12 9v13"/><path d="M9 22h6"/></svg>'
        ],

        // Activities
        'bike' => [
            'name' => 'Vélo',
            'category' => 'activities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/></svg>'
        ],
        'hiking' => [
            'name' => 'Randonnée',
            'category' => 'activities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13" cy="4" r="2"/><path d="M4 17l3-3 3 3"/><path d="M15 21l-3-9 4-3"/><path d="M8 14l3-3"/></svg>'
        ],
        'golf' => [
            'name' => 'Golf',
            'category' => 'activities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 18V4l6 4-6 4"/><circle cx="12" cy="20" r="2"/></svg>'
        ],
        'tennis' => [
            'name' => 'Tennis',
            'category' => 'activities',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20"/><path d="M12 2a14.5 14.5 0 0 1 0 20"/></svg>'
        ],

        // Family & Accessibility
        'family' => [
            'name' => 'Famille',
            'category' => 'family',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="2"/><circle cx="15" cy="7" r="2"/><path d="M9 11v10"/><path d="M15 11v10"/><path d="M5 21h4"/><path d="M15 21h4"/></svg>'
        ],
        'pets' => [
            'name' => 'Animaux acceptés',
            'category' => 'family',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="4" r="2"/><circle cx="18" cy="8" r="2"/><circle cx="4" cy="8" r="2"/><path d="M9 10c0 1 1 2 2 2s2-1 2-2"/><ellipse cx="11" cy="17" rx="5" ry="6"/></svg>'
        ],
        'accessible' => [
            'name' => 'Accessible',
            'category' => 'family',
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="4" r="2"/><path d="M16 22l-2-5H7"/><path d="M12 12v-2l4 1"/><circle cx="9" cy="19" r="3"/></svg>'
        ]
    ];
}

/**
 * Get icon categories
 */
function getIconCategories(): array {
    return [
        'nature' => 'Nature & Extérieur',
        'amenities' => 'Équipements',
        'dining' => 'Restauration',
        'comfort' => 'Confort',
        'location' => 'Localisation',
        'activities' => 'Activités',
        'family' => 'Famille & Accessibilité'
    ];
}

/**
 * Get icon by code
 */
function getIcon(string $code): ?array {
    $icons = getAvailableIcons();
    return $icons[$code] ?? null;
}

/**
 * Get icon SVG by code
 */
function getIconSvg(string $code): string {
    $icon = getIcon($code);
    return $icon['svg'] ?? '';
}

/**
 * Check if section supports features
 */
function sectionHasFeatures(string $sectionCode): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT has_features FROM content_sections WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    $result = $stmt->fetchColumn();
    return $result == 1;
}

/**
 * Check if a section supports services
 */
function sectionHasServices(string $sectionCode): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT has_services FROM content_sections WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    $result = $stmt->fetchColumn();
    return $result == 1;
}

/**
 * Enable features for a section
 */
function enableSectionFeatures(string $sectionCode): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET has_features = TRUE WHERE code = ? AND hotel_id = ?');
    return $stmt->execute([$sectionCode, getHotelId()]);
}

/**
 * Get available background color options for sections
 * Returns an array of color options with their CSS variable and display info
 * Preview colors are dynamically fetched from the current theme settings
 *
 * Groups:
 * - 'theme': Colors linked to the site theme (dynamic)
 * - 'neutral': Hardcoded neutral colors (static)
 */
function getSectionBackgroundOptions(): array {
    // Get current theme colors (dynamically from database)
    $theme = getThemeSettings();

    return [
        // Theme-driven colors (dynamic)
        'cream' => [
            'label' => 'Fond crème',
            'css_var' => '--color-cream',
            'css_class' => 'section-cream',
            'text_light' => false,
            'theme_key' => 'color_cream',
            'preview' => $theme['color_cream'],
            'group' => 'theme'
        ],
        'white' => [
            'label' => 'Fond blanc',
            'css_var' => '--color-white',
            'css_class' => 'section-white',
            'text_light' => false,
            'theme_key' => null,
            'preview' => '#FFFFFF',
            'group' => 'theme'
        ],
        'beige' => [
            'label' => 'Fond beige',
            'css_var' => '--color-beige',
            'css_class' => 'section-beige',
            'text_light' => false,
            'theme_key' => 'color_beige',
            'preview' => $theme['color_beige'],
            'group' => 'theme'
        ],
        'primary' => [
            'label' => 'Couleur primaire',
            'css_var' => '--color-primary',
            'css_class' => 'section-primary',
            'text_light' => true,
            'theme_key' => 'color_primary',
            'preview' => $theme['color_primary'],
            'group' => 'theme'
        ],
        'primary-dark' => [
            'label' => 'Primaire foncé',
            'css_var' => '--color-primary-dark',
            'css_class' => 'section-primary-dark',
            'text_light' => true,
            'theme_key' => 'color_primary_dark',
            'preview' => $theme['color_primary_dark'],
            'group' => 'theme'
        ],
        'accent' => [
            'label' => 'Couleur accent',
            'css_var' => '--color-accent',
            'css_class' => 'section-accent',
            'text_light' => true,
            'theme_key' => 'color_accent',
            'preview' => $theme['color_accent'],
            'group' => 'theme'
        ],

        // Neutral hardcoded colors (static)
        'neutral-gray' => [
            'label' => 'Gris clair',
            'css_var' => null,
            'css_class' => 'section-neutral-gray',
            'text_light' => false,
            'theme_key' => null,
            'preview' => '#F5F5F5',
            'group' => 'neutral'
        ],
        'neutral-blue' => [
            'label' => 'Bleu gris',
            'css_var' => null,
            'css_class' => 'section-neutral-blue',
            'text_light' => false,
            'theme_key' => null,
            'preview' => '#F0F4F8',
            'group' => 'neutral'
        ],
        'neutral-sand' => [
            'label' => 'Sable clair',
            'css_var' => null,
            'css_class' => 'section-neutral-sand',
            'text_light' => false,
            'theme_key' => null,
            'preview' => '#F8F6F1',
            'group' => 'neutral'
        ],
    ];
}

/**
 * Get the CSS class for a section background color
 */
function getSectionBackgroundClass(string $colorKey): string {
    $options = getSectionBackgroundOptions();
    return $options[$colorKey]['css_class'] ?? 'section-cream';
}

/**
 * Check if a background color requires light text
 */
function sectionBackgroundNeedsLightText(string $colorKey): bool {
    $options = getSectionBackgroundOptions();
    return $options[$colorKey]['text_light'] ?? false;
}

/**
 * Get the background color for a section
 */
function getSectionBackgroundColor(string $sectionCode): string {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT background_color FROM content_sections WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    return $stmt->fetchColumn() ?: 'cream';
}

/**
 * Set the background color for a section
 */
function setSectionBackgroundColor(string $sectionCode, string $colorKey): bool {
    $options = getSectionBackgroundOptions();
    if (!isset($options[$colorKey])) {
        $colorKey = 'cream'; // Fallback to default
    }

    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET background_color = ? WHERE code = ? AND hotel_id = ?');
    return $stmt->execute([$colorKey, $sectionCode, getHotelId()]);
}

/**
 * Get available image position options for sections
 * Used by section types that have image + text layouts
 */
function getImagePositionOptions(): array {
    return [
        'left' => [
            'label' => 'Image à gauche',
            'css_class' => 'image-left',
            'icon' => 'layout-left'
        ],
        'right' => [
            'label' => 'Image à droite',
            'css_class' => 'image-right',
            'icon' => 'layout-right'
        ]
    ];
}

/**
 * Get section types that support image position setting
 */
function getSectionTypesWithImagePosition(): array {
    return ['services_indicators', 'services_checklist'];
}

/**
 * Check if a section type supports image position setting
 */
function sectionSupportsImagePosition(string $templateType): bool {
    return in_array($templateType, getSectionTypesWithImagePosition());
}

/**
 * Get the image position for a section
 */
function getSectionImagePosition(string $sectionCode): string {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT image_position FROM content_sections WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    return $stmt->fetchColumn() ?: 'left';
}

/**
 * Set the image position for a section
 */
function setSectionImagePosition(string $sectionCode, string $position): bool {
    $options = getImagePositionOptions();
    if (!isset($options[$position])) {
        $position = 'left'; // Fallback to default
    }

    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET image_position = ? WHERE code = ? AND hotel_id = ?');
    return $stmt->execute([$position, $sectionCode, getHotelId()]);
}

/**
 * Get the CSS class for image position
 */
function getImagePositionClass(string $position): string {
    $options = getImagePositionOptions();
    return $options[$position]['css_class'] ?? 'image-left';
}

/**
 * Get available text alignment options
 */
function getTextAlignmentOptions(): array {
    return [
        'center' => [
            'label' => 'Centré',
            'css_class' => 'text-center',
            'icon' => 'align-center'
        ],
        'left' => [
            'label' => 'Gauche',
            'css_class' => 'text-left',
            'icon' => 'align-left'
        ],
        'right' => [
            'label' => 'Droite',
            'css_class' => 'text-right',
            'icon' => 'align-right'
        ]
    ];
}

/**
 * Get section types that support text alignment setting
 */
function getSectionTypesWithTextAlignment(): array {
    return ['presentation_hero'];
}

/**
 * Check if a section type supports text alignment setting
 */
function sectionSupportsTextAlignment(string $templateType): bool {
    return in_array($templateType, getSectionTypesWithTextAlignment());
}

/**
 * Get the text alignment for a section
 */
function getSectionTextAlignment(string $sectionCode): string {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT text_alignment FROM content_sections WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    return $stmt->fetchColumn() ?: 'center';
}

/**
 * Set the text alignment for a section
 */
function setSectionTextAlignment(string $sectionCode, string $alignment): bool {
    $options = getTextAlignmentOptions();
    if (!isset($options[$alignment])) {
        $alignment = 'center'; // Fallback to default
    }

    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE content_sections SET text_alignment = ? WHERE code = ? AND hotel_id = ?');
    return $stmt->execute([$alignment, $sectionCode, getHotelId()]);
}

/**
 * Get section types that support optional links
 */
function getSectionTypesWithLinks(): array {
    return ['services_indicators', 'services_checklist'];
}

/**
 * Check if a section type supports optional links
 */
function sectionSupportsLinks(string $templateType): bool {
    return in_array($templateType, getSectionTypesWithLinks());
}

/**
 * Get the section link data
 */
function getSectionLink(string $sectionCode): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT section_link_url, section_link_text, section_link_new_tab FROM content_sections WHERE code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    $result = $stmt->fetch();

    return [
        'url' => $result['section_link_url'] ?? '',
        'text' => $result['section_link_text'] ?? '',
        'new_tab' => (bool)($result['section_link_new_tab'] ?? true)
    ];
}

/**
 * Get section link with translations
 */
function getSectionLinkWithTranslations(string $sectionCode, string $lang = 'fr'): array {
    $link = getSectionLink($sectionCode);

    if (empty($link['url'])) {
        return $link;
    }

    // Get translation if not French
    if ($lang !== 'fr') {
        $pdo = getDatabase();
        $stmt = $pdo->prepare('SELECT link_text FROM section_link_translations WHERE section_code = ? AND language_code = ? AND hotel_id = ?');
        $stmt->execute([$sectionCode, $lang, getHotelId()]);
        $translation = $stmt->fetchColumn();

        if ($translation) {
            $link['text'] = $translation;
        }
    }

    return $link;
}

/**
 * Get all section link translations
 */
function getSectionLinkTranslations(string $sectionCode): array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT language_code, link_text FROM section_link_translations WHERE section_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    $rows = $stmt->fetchAll();

    $translations = [];
    foreach ($rows as $row) {
        $translations[$row['language_code']] = $row['link_text'];
    }

    return $translations;
}

/**
 * Validate a URL for section links
 * Returns sanitized URL or empty string if invalid
 */
function validateSectionLinkUrl(string $url): string {
    $url = trim($url);

    if (empty($url)) {
        return '';
    }

    // Add https:// if no protocol specified
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    // Validate URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }

    // Only allow http and https protocols
    $parsed = parse_url($url);
    if (!in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'])) {
        return '';
    }

    return $url;
}

/**
 * Save section link data
 */
function saveSectionLink(string $sectionCode, string $url, string $text, bool $newTab = true): bool {
    $pdo = getDatabase();

    // Validate and sanitize URL
    $url = validateSectionLinkUrl($url);

    // Sanitize text
    $text = trim($text);
    if (strlen($text) > 100) {
        $text = substr($text, 0, 100);
    }

    $stmt = $pdo->prepare('
        UPDATE content_sections
        SET section_link_url = ?, section_link_text = ?, section_link_new_tab = ?
        WHERE code = ? AND hotel_id = ?
    ');

    return $stmt->execute([
        $url ?: null,
        $text ?: null,
        $newTab ? 1 : 0,
        $sectionCode,
        getHotelId()
    ]);
}

/**
 * Save section link translations
 */
function saveSectionLinkTranslations(string $sectionCode, array $translations): bool {
    $pdo = getDatabase();

    // Delete existing translations
    $stmt = $pdo->prepare('DELETE FROM section_link_translations WHERE section_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);

    // Insert new translations
    $stmt = $pdo->prepare('
        INSERT INTO section_link_translations (section_code, language_code, link_text, hotel_id)
        VALUES (?, ?, ?, ?)
    ');

    foreach ($translations as $lang => $text) {
        $text = trim($text);
        if (!empty($text)) {
            if (strlen($text) > 100) {
                $text = substr($text, 0, 100);
            }
            $stmt->execute([$sectionCode, $lang, $text, getHotelId()]);
        }
    }

    return true;
}

/**
 * Clear section link
 */
function clearSectionLink(string $sectionCode): bool {
    $pdo = getDatabase();

    // Clear link data
    $stmt = $pdo->prepare('
        UPDATE content_sections
        SET section_link_url = NULL, section_link_text = NULL, section_link_new_tab = TRUE
        WHERE code = ? AND hotel_id = ?
    ');
    $stmt->execute([$sectionCode, getHotelId()]);

    // Clear translations
    $stmt = $pdo->prepare('DELETE FROM section_link_translations WHERE section_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);

    return true;
}

/**
 * Check if a section has a link configured
 */
function sectionHasLink(string $sectionCode): bool {
    $link = getSectionLink($sectionCode);
    return !empty($link['url']);
}

/**
 * Get all features for a section
 */
function getSectionFeatures(string $sectionCode, bool $activeOnly = true): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM section_features WHERE section_code = ? AND hotel_id = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = TRUE';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sectionCode, getHotelId()]);
    return $stmt->fetchAll();
}

/**
 * Get features with all translations
 */
function getSectionFeaturesWithTranslations(string $sectionCode, bool $activeOnly = true): array {
    $features = getSectionFeatures($sectionCode, $activeOnly);
    $pdo = getDatabase();

    foreach ($features as &$feature) {
        $stmt = $pdo->prepare('SELECT language_code, label FROM section_feature_translations WHERE feature_id = ?');
        $stmt->execute([$feature['id']]);
        $translations = $stmt->fetchAll();

        $feature['translations'] = [];
        foreach ($translations as $trans) {
            $feature['translations'][$trans['language_code']] = $trans['label'];
        }

        // Add icon data
        $icon = getIcon($feature['icon_code']);
        $feature['icon_svg'] = $icon['svg'] ?? '';
        $feature['icon_name'] = $icon['name'] ?? $feature['icon_code'];
    }

    return $features;
}

/**
 * Get a single feature by ID
 */
function getSectionFeature(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM section_features WHERE id = ? AND hotel_id = ?');
    $stmt->execute([$id, getHotelId()]);
    $feature = $stmt->fetch();

    if ($feature) {
        // Get translations
        $stmt = $pdo->prepare('SELECT language_code, label FROM section_feature_translations WHERE feature_id = ?');
        $stmt->execute([$id]);
        $translations = $stmt->fetchAll();

        $feature['translations'] = [];
        foreach ($translations as $trans) {
            $feature['translations'][$trans['language_code']] = $trans['label'];
        }
    }

    return $feature ?: null;
}

/**
 * Create a new feature
 */
function createSectionFeature(string $sectionCode, string $iconCode, string $label): ?int {
    $pdo = getDatabase();

    // Get next position
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM section_features WHERE section_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    $nextPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        INSERT INTO section_features (section_code, icon_code, label, position, hotel_id)
        VALUES (?, ?, ?, ?, ?)
    ');

    $success = $stmt->execute([$sectionCode, $iconCode, trim($label), $nextPosition, getHotelId()]);
    return $success ? (int)$pdo->lastInsertId() : null;
}

/**
 * Update a feature
 */
function updateSectionFeature(int $id, string $iconCode, string $label, bool $isActive = true): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE section_features
        SET icon_code = ?, label = ?, is_active = ?
        WHERE id = ? AND hotel_id = ?
    ');
    return $stmt->execute([$iconCode, trim($label), $isActive ? 1 : 0, $id, getHotelId()]);
}

/**
 * Delete a feature
 */
function deleteSectionFeature(int $id): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('DELETE FROM section_features WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$id, getHotelId()]);
}

/**
 * Reorder features
 */
function reorderSectionFeatures(string $sectionCode, array $featureIds): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE section_features SET position = ? WHERE id = ? AND section_code = ? AND hotel_id = ?');

    $position = 1;
    foreach ($featureIds as $id) {
        $stmt->execute([$position, $id, $sectionCode, getHotelId()]);
        $position++;
    }

    return true;
}

/**
 * Save feature translations
 */
function saveSectionFeatureTranslations(int $featureId, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $label) {
        if (!in_array($langCode, getSupportedLanguages()) || $langCode === 'fr') {
            continue;
        }

        $label = trim($label);
        if (empty($label)) {
            // Delete translation if empty
            $stmt = $pdo->prepare('DELETE FROM section_feature_translations WHERE feature_id = ? AND language_code = ? AND hotel_id = ?');
            $stmt->execute([$featureId, $langCode, getHotelId()]);
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO section_feature_translations (feature_id, language_code, label, hotel_id)
                VALUES (?, ?, ?, ?)
                ON CONFLICT (feature_id, language_code, hotel_id) DO UPDATE SET label = EXCLUDED.label
            ');
            $success = $stmt->execute([$featureId, $langCode, $label, getHotelId()]) && $success;
        } catch (PDOException $e) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Get feature label for a specific language
 */
function getFeatureLabelForLanguage(array $feature, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $feature['label'];
    }

    return $feature['translations'][$langCode] ?? $feature['label'];
}

/**
 * Seed default features for a section
 */
function seedSectionFeatures(string $sectionCode, array $defaultFeatures): void {
    $pdo = getDatabase();

    // Check if features already exist
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM section_features WHERE section_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    if ($stmt->fetchColumn() > 0) {
        return; // Already seeded
    }

    foreach ($defaultFeatures as $feature) {
        $featureId = createSectionFeature($sectionCode, $feature['icon'], $feature['label']);
        if ($featureId && isset($feature['translations'])) {
            saveSectionFeatureTranslations($featureId, $feature['translations']);
        }
    }
}

/**
 * Get all services for a section
 */
function getSectionServices(string $sectionCode, bool $activeOnly = true): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM section_services WHERE section_code = ? AND hotel_id = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = TRUE';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sectionCode, getHotelId()]);
    return $stmt->fetchAll();
}

/**
 * Get services with all translations
 */
function getSectionServicesWithTranslations(string $sectionCode, bool $activeOnly = true): array {
    $services = getSectionServices($sectionCode, $activeOnly);
    $pdo = getDatabase();

    foreach ($services as &$service) {
        $stmt = $pdo->prepare('SELECT language_code, label, description FROM section_service_translations WHERE service_id = ?');
        $stmt->execute([$service['id']]);
        $translations = $stmt->fetchAll();

        $service['translations'] = [];
        foreach ($translations as $trans) {
            $service['translations'][$trans['language_code']] = [
                'label' => $trans['label'],
                'description' => $trans['description']
            ];
        }

        // Add icon data
        $icon = getIcon($service['icon_code']);
        $service['icon_svg'] = $icon['svg'] ?? '';
        $service['icon_name'] = $icon['name'] ?? $service['icon_code'];
    }

    return $services;
}

/**
 * Get a single service by ID
 */
function getSectionService(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM section_services WHERE id = ? AND hotel_id = ?');
    $stmt->execute([$id, getHotelId()]);
    $service = $stmt->fetch();

    if ($service) {
        // Get translations
        $stmt = $pdo->prepare('SELECT language_code, label, description FROM section_service_translations WHERE service_id = ?');
        $stmt->execute([$id]);
        $translations = $stmt->fetchAll();

        $service['translations'] = [];
        foreach ($translations as $trans) {
            $service['translations'][$trans['language_code']] = [
                'label' => $trans['label'],
                'description' => $trans['description']
            ];
        }
    }

    return $service ?: null;
}

/**
 * Create a new service
 */
function createSectionService(string $sectionCode, string $iconCode, string $label, string $description = ''): ?int {
    $pdo = getDatabase();

    // Get next position
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM section_services WHERE section_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    $nextPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        INSERT INTO section_services (section_code, icon_code, label, description, position, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ');

    $success = $stmt->execute([$sectionCode, $iconCode, trim($label), trim($description), $nextPosition, getHotelId()]);
    return $success ? (int)$pdo->lastInsertId() : null;
}

/**
 * Update a service
 */
function updateSectionService(int $id, string $iconCode, string $label, string $description = '', bool $isActive = true): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('
        UPDATE section_services
        SET icon_code = ?, label = ?, description = ?, is_active = ?
        WHERE id = ? AND hotel_id = ?
    ');
    return $stmt->execute([$iconCode, trim($label), trim($description), $isActive ? 1 : 0, $id, getHotelId()]);
}

/**
 * Delete a service
 */
function deleteSectionService(int $id): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('DELETE FROM section_services WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$id, getHotelId()]);
}

/**
 * Reorder services
 */
function reorderSectionServices(string $sectionCode, array $serviceIds): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE section_services SET position = ? WHERE id = ? AND section_code = ? AND hotel_id = ?');

    $position = 1;
    foreach ($serviceIds as $id) {
        $stmt->execute([$position, $id, $sectionCode, getHotelId()]);
        $position++;
    }

    return true;
}

/**
 * Save service translations
 */
function saveSectionServiceTranslations(int $serviceId, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $data) {
        if (!in_array($langCode, getSupportedLanguages()) || $langCode === 'fr') {
            continue;
        }

        $label = is_array($data) ? trim($data['label'] ?? '') : trim($data);
        $description = is_array($data) ? trim($data['description'] ?? '') : '';

        if (empty($label)) {
            // Delete translation if empty
            $stmt = $pdo->prepare('DELETE FROM section_service_translations WHERE service_id = ? AND language_code = ? AND hotel_id = ?');
            $stmt->execute([$serviceId, $langCode, getHotelId()]);
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO section_service_translations (service_id, language_code, label, description, hotel_id)
                VALUES (?, ?, ?, ?, ?)
                ON CONFLICT (service_id, language_code, hotel_id) DO UPDATE SET label = EXCLUDED.label, description = EXCLUDED.description
            ');
            $success = $stmt->execute([$serviceId, $langCode, $label, $description, getHotelId()]) && $success;
        } catch (PDOException $e) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Get service label for a specific language
 */
function getServiceLabelForLanguage(array $service, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $service['label'];
    }

    return $service['translations'][$langCode]['label'] ?? $service['label'];
}

/**
 * Get service description for a specific language
 */
function getServiceDescriptionForLanguage(array $service, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $service['description'] ?? '';
    }

    return $service['translations'][$langCode]['description'] ?? ($service['description'] ?? '');
}

/**
 * Get gallery items for a section
 */
function getSectionGalleryItems(string $sectionCode, bool $activeOnly = true): array {
    $pdo = getDatabase();
    $sql = 'SELECT * FROM section_gallery_items WHERE section_code = ? AND hotel_id = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = TRUE';
    }
    $sql .= ' ORDER BY position ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sectionCode, getHotelId()]);
    return $stmt->fetchAll();
}

/**
 * Get gallery items with all translations
 */
function getSectionGalleryItemsWithTranslations(string $sectionCode, bool $activeOnly = true): array {
    $items = getSectionGalleryItems($sectionCode, $activeOnly);
    $pdo = getDatabase();

    foreach ($items as &$item) {
        $stmt = $pdo->prepare('SELECT language_code, title, description FROM section_gallery_item_translations WHERE item_id = ?');
        $stmt->execute([$item['id']]);
        $translations = $stmt->fetchAll();

        $item['translations'] = [];
        foreach ($translations as $trans) {
            $item['translations'][$trans['language_code']] = [
                'title' => $trans['title'],
                'description' => $trans['description']
            ];
        }
    }

    return $items;
}

/**
 * Get a single gallery item
 */
function getSectionGalleryItem(int $id): ?array {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('SELECT * FROM section_gallery_items WHERE id = ? AND hotel_id = ?');
    $stmt->execute([$id, getHotelId()]);
    $item = $stmt->fetch();

    if ($item) {
        // Get translations
        $stmt = $pdo->prepare('SELECT language_code, title, description FROM section_gallery_item_translations WHERE item_id = ?');
        $stmt->execute([$id]);
        $translations = $stmt->fetchAll();

        $item['translations'] = [];
        foreach ($translations as $trans) {
            $item['translations'][$trans['language_code']] = [
                'title' => $trans['title'],
                'description' => $trans['description']
            ];
        }
    }

    return $item ?: null;
}

/**
 * Create a gallery item
 */
function createSectionGalleryItem(string $sectionCode, string $imageFilename, string $title, string $description = '', string $imageAlt = ''): ?int {
    $pdo = getDatabase();

    // Get next position
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM section_gallery_items WHERE section_code = ? AND hotel_id = ?');
    $stmt->execute([$sectionCode, getHotelId()]);
    $nextPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        INSERT INTO section_gallery_items (section_code, image_filename, image_alt, title, description, position, hotel_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    $success = $stmt->execute([$sectionCode, $imageFilename, $imageAlt, trim($title), trim($description), $nextPosition, getHotelId()]);
    return $success ? (int)$pdo->lastInsertId() : null;
}

/**
 * Update a gallery item
 */
function updateSectionGalleryItem(int $id, string $title, string $description = '', string $imageAlt = '', bool $isActive = true, ?string $imageFilename = null): bool {
    $pdo = getDatabase();

    if ($imageFilename !== null) {
        $stmt = $pdo->prepare('
            UPDATE section_gallery_items
            SET title = ?, description = ?, image_alt = ?, is_active = ?, image_filename = ?
            WHERE id = ? AND hotel_id = ?
        ');
        return $stmt->execute([trim($title), trim($description), trim($imageAlt), $isActive ? 1 : 0, $imageFilename, $id, getHotelId()]);
    } else {
        $stmt = $pdo->prepare('
            UPDATE section_gallery_items
            SET title = ?, description = ?, image_alt = ?, is_active = ?
            WHERE id = ? AND hotel_id = ?
        ');
        return $stmt->execute([trim($title), trim($description), trim($imageAlt), $isActive ? 1 : 0, $id, getHotelId()]);
    }
}

/**
 * Delete a gallery item
 */
function deleteSectionGalleryItem(int $id): bool {
    $pdo = getDatabase();

    // Get item to delete image file
    $stmt = $pdo->prepare('SELECT image_filename FROM section_gallery_items WHERE id = ? AND hotel_id = ?');
    $stmt->execute([$id, getHotelId()]);
    $item = $stmt->fetch();

    if ($item && !empty($item['image_filename']) && file_exists($item['image_filename'])) {
        @unlink($item['image_filename']);
    }

    $stmt = $pdo->prepare('DELETE FROM section_gallery_items WHERE id = ? AND hotel_id = ?');
    return $stmt->execute([$id, getHotelId()]);
}

/**
 * Reorder gallery items
 */
function reorderSectionGalleryItems(string $sectionCode, array $itemIds): bool {
    $pdo = getDatabase();
    $stmt = $pdo->prepare('UPDATE section_gallery_items SET position = ? WHERE id = ? AND section_code = ? AND hotel_id = ?');

    $position = 1;
    foreach ($itemIds as $id) {
        $stmt->execute([$position, $id, $sectionCode, getHotelId()]);
        $position++;
    }

    return true;
}

/**
 * Save gallery item translations
 */
function saveSectionGalleryItemTranslations(int $itemId, array $translations): bool {
    $pdo = getDatabase();
    $success = true;

    foreach ($translations as $langCode => $data) {
        if (!in_array($langCode, getSupportedLanguages()) || $langCode === 'fr') {
            continue;
        }

        $title = is_array($data) ? trim($data['title'] ?? '') : trim($data);
        $description = is_array($data) ? trim($data['description'] ?? '') : '';

        if (empty($title)) {
            // Delete translation if empty
            $stmt = $pdo->prepare('DELETE FROM section_gallery_item_translations WHERE item_id = ? AND language_code = ? AND hotel_id = ?');
            $stmt->execute([$itemId, $langCode, getHotelId()]);
            continue;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO section_gallery_item_translations (item_id, language_code, title, description, hotel_id)
                VALUES (?, ?, ?, ?, ?)
                ON CONFLICT (item_id, language_code, hotel_id) DO UPDATE SET title = EXCLUDED.title, description = EXCLUDED.description
            ');
            $success = $stmt->execute([$itemId, $langCode, $title, $description, getHotelId()]) && $success;
        } catch (PDOException $e) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Get gallery item title for a specific language
 */
function getGalleryItemTitleForLanguage(array $item, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $item['title'];
    }

    return $item['translations'][$langCode]['title'] ?? $item['title'];
}

/**
 * Get gallery item description for a specific language
 */
function getGalleryItemDescriptionForLanguage(array $item, string $langCode = 'fr'): string {
    if ($langCode === 'fr') {
        return $item['description'] ?? '';
    }

    return $item['translations'][$langCode]['description'] ?? ($item['description'] ?? '');
}
