<?php
/**
 * Utility Functions
 * Core helpers: HTML sanitization, date formatting
 */

/**
 * Sanitize output for HTML
 */
function h(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 */
function formatDate(?string $date): string {
    if (empty($date)) {
        return '-';
    }
    return date('d/m/Y H:i', strtotime($date));
}

/**
 * Format date/time as relative time (e.g., "5 minutes ago", "in 2 hours")
 * Supports both past and future dates
 */
function timeAgo(?string $datetime): string {
    if (empty($datetime)) {
        return '-';
    }

    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    $isFuture = $diff < 0;
    $diff = abs($diff);

    // Define time intervals in seconds
    $intervals = [
        ['value' => 31536000, 'singular' => 'an', 'plural' => 'ans'],
        ['value' => 2592000, 'singular' => 'mois', 'plural' => 'mois'],
        ['value' => 604800, 'singular' => 'semaine', 'plural' => 'semaines'],
        ['value' => 86400, 'singular' => 'jour', 'plural' => 'jours'],
        ['value' => 3600, 'singular' => 'heure', 'plural' => 'heures'],
        ['value' => 60, 'singular' => 'minute', 'plural' => 'minutes'],
    ];

    // Less than a minute
    if ($diff < 60) {
        return $isFuture ? "dans moins d'une minute" : "à l'instant";
    }

    foreach ($intervals as $interval) {
        $count = floor($diff / $interval['value']);
        if ($count >= 1) {
            $unit = $count === 1 ? $interval['singular'] : $interval['plural'];
            if ($isFuture) {
                return "dans $count $unit";
            } else {
                return "il y a $count $unit";
            }
        }
    }

    return formatDate($datetime);
}
