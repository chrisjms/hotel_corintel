<?php
/**
 * Establishment Types — Label configuration per type
 *
 * Central registry mapping establishment types to display labels.
 * Internal code (DB tables, PHP functions, file names, URLs) stays unchanged;
 * only user-visible labels adapt based on type.
 */

const ESTABLISHMENT_TYPES = [
    'hotel' => [
        'label' => 'Hôtel',
    ],
    'pizzeria' => [
        'label' => 'Pizzeria',
    ],
];

const ESTABLISHMENT_LABELS = [
    'hotel' => [
        'service_name'      => ['fr' => 'Room Service',    'en' => 'Room Service',    'es' => 'Room Service',    'it' => 'Room Service'],
        'service_separator' => ['fr' => 'Room Service',    'en' => 'Room Service',    'es' => 'Room Service',    'it' => 'Room Service'],
        'orders_title'      => ['fr' => 'Room Service — Commandes',   'en' => 'Room Service — Orders',   'es' => 'Room Service — Pedidos',   'it' => 'Room Service — Ordini'],
        'categories_title'  => ['fr' => 'Room Service — Catégories',  'en' => 'Room Service — Categories','es' => 'Room Service — Categorías','it' => 'Room Service — Categorie'],
        'items_title'       => ['fr' => 'Room Service — Articles',    'en' => 'Room Service — Items',    'es' => 'Room Service — Artículos', 'it' => 'Room Service — Articoli'],
        'stats_title'       => ['fr' => 'Room Service — Statistiques','en' => 'Room Service — Statistics','es' => 'Room Service — Estadísticas','it' => 'Room Service — Statistiche'],
        'nav_link'          => ['fr' => 'Room Service',    'en' => 'Room Service',    'es' => 'Room Service',    'it' => 'Room Service'],
        'room_unit'         => ['fr' => 'Chambre',         'en' => 'Room',            'es' => 'Habitación',      'it' => 'Camera'],
        'rooms_label'       => ['fr' => 'Chambres',        'en' => 'Rooms',           'es' => 'Habitaciones',    'it' => 'Camere'],
        'qr_scan_text'      => ['fr' => 'Scannez pour accéder au Room Service', 'en' => 'Scan to access Room Service', 'es' => 'Escanee para acceder al Room Service', 'it' => 'Scansiona per accedere al Room Service'],
        'dashboard_heading' => ['fr' => 'Room Service — Activité du jour', 'en' => 'Room Service — Today\'s Activity', 'es' => 'Room Service — Actividad del día', 'it' => 'Room Service — Attività del giorno'],
    ],
    'pizzeria' => [
        'service_name'      => ['fr' => 'Menu',            'en' => 'Menu',            'es' => 'Menú',            'it' => 'Menu'],
        'service_separator' => ['fr' => 'Menu',            'en' => 'Menu',            'es' => 'Menú',            'it' => 'Menu'],
        'orders_title'      => ['fr' => 'Menu — Commandes',          'en' => 'Menu — Orders',          'es' => 'Menú — Pedidos',          'it' => 'Menu — Ordini'],
        'categories_title'  => ['fr' => 'Menu — Catégories',         'en' => 'Menu — Categories',      'es' => 'Menú — Categorías',       'it' => 'Menu — Categorie'],
        'items_title'       => ['fr' => 'Menu — Articles',           'en' => 'Menu — Items',           'es' => 'Menú — Artículos',        'it' => 'Menu — Articoli'],
        'stats_title'       => ['fr' => 'Menu — Statistiques',       'en' => 'Menu — Statistics',      'es' => 'Menú — Estadísticas',     'it' => 'Menu — Statistiche'],
        'nav_link'          => ['fr' => 'Menu',            'en' => 'Menu',            'es' => 'Menú',            'it' => 'Menu'],
        'room_unit'         => ['fr' => 'Table',           'en' => 'Table',           'es' => 'Mesa',            'it' => 'Tavolo'],
        'rooms_label'       => ['fr' => 'Tables',          'en' => 'Tables',          'es' => 'Mesas',          'it' => 'Tavoli'],
        'qr_scan_text'      => ['fr' => 'Scannez pour accéder au Menu', 'en' => 'Scan to access Menu', 'es' => 'Escanee para acceder al Menú', 'it' => 'Scansiona per accedere al Menu'],
        'dashboard_heading' => ['fr' => 'Menu — Activité du jour',   'en' => 'Menu — Today\'s Activity','es' => 'Menú — Actividad del día','it' => 'Menu — Attività del giorno'],
    ],
];

/**
 * Get a display label for the current establishment type.
 *
 * @param string $key   Label key (e.g. 'service_name', 'orders_title')
 * @param string $lang  Language code, defaults to 'fr'
 * @param string|null $type  Override type, defaults to current establishment type
 * @return string The localized label
 */
function establishmentLabel(string $key, string $lang = 'fr', ?string $type = null): string {
    $type = $type ?? getEstablishmentType();
    return ESTABLISHMENT_LABELS[$type][$key][$lang]
        ?? ESTABLISHMENT_LABELS['hotel'][$key][$lang]
        ?? $key;
}

/**
 * Get all available establishment types for form selects.
 * @return array ['type_code' => 'Human label', ...]
 */
function getEstablishmentTypeOptions(): array {
    $options = [];
    foreach (ESTABLISHMENT_TYPES as $code => $meta) {
        $options[$code] = $meta['label'];
    }
    return $options;
}

/**
 * Output a <script> tag with establishment labels for JS-side override.
 * Call this in <head> of client pages.
 * @return string HTML script tag (empty string for hotel type, which uses default translations)
 */
function getEstablishmentLabelsJS(): string {
    $type = getEstablishmentType();
    $labels = [
        'type' => $type,
        'serviceName' => ESTABLISHMENT_LABELS[$type]['service_name'] ?? ESTABLISHMENT_LABELS['hotel']['service_name'],
        'navLink' => ESTABLISHMENT_LABELS[$type]['nav_link'] ?? ESTABLISHMENT_LABELS['hotel']['nav_link'],
    ];
    return '<script>window.establishmentConfig = ' . json_encode($labels, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) . ';</script>';
}
