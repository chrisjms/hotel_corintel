<?php
/**
 * Images API
 * Hotel Corintel
 *
 * Returns images for a section in JSON format
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=60');

require_once __DIR__ . '/../includes/functions.php';

// Get section parameter
$section = $_GET['section'] ?? '';

// Validate section
$validSections = ['home', 'services', 'rooms', 'activities', 'contact'];

if (empty($section)) {
    http_response_code(400);
    echo json_encode(['error' => 'Section parameter is required']);
    exit;
}

if (!in_array($section, $validSections)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid section']);
    exit;
}

try {
    $images = getImagesBySection($section);

    // Format response
    $response = [
        'section' => $section,
        'count' => count($images),
        'images' => array_map(function($img) {
            return [
                'id' => (int) $img['id'],
                'filename' => $img['filename'],
                'position' => (int) $img['position'],
                'slot' => $img['slot_name'],
                'title' => $img['title'],
                'alt' => $img['alt_text'],
                'updated' => $img['updated_at']
            ];
        }, $images)
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
