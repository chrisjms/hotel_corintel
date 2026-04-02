<?php
/**
 * Development Router for PHP built-in server
 *
 * Usage: php -S localhost:8000 -t client/ client/router.php
 *
 * Maps shared asset URLs to the shared/ directory:
 *   /js/*       -> shared/js/*
 *   /uploads/*  -> shared/uploads/*
 *   /images/*   -> shared/images/*
 *
 * This replicates the Apache Alias directives used in production.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Map shared asset prefixes to the shared/ directory
$sharedPrefixes = ['/js/', '/uploads/', '/images/'];

foreach ($sharedPrefixes as $prefix) {
    if (str_starts_with($uri, $prefix)) {
        $file = dirname(__DIR__) . '/shared' . $uri;
        if (is_file($file)) {
            // Let PHP serve the file with correct MIME type
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $mimeTypes = [
                'js' => 'application/javascript',
                'css' => 'text/css',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'json' => 'application/json',
            ];
            if (isset($mimeTypes[$ext])) {
                header('Content-Type: ' . $mimeTypes[$ext]);
            }
            readfile($file);
            return true;
        }
        // File not found in shared, continue to default handling
    }
}

// Default: let PHP built-in server handle the request normally
return false;
