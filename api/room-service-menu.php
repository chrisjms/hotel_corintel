<?php
/**
 * Room Service Menu API
 * Returns menu data for offline caching
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // 5 minutes cache

// Get room access check (optional - for personalization)
$roomId = null;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$session = getRoomServiceSession();
if ($session) {
    $roomId = $session['room_id'];
}

// Get menu items by category
$categories = getRoomServiceCategoriesWithItems();
$currentLang = getCurrentLanguage();

// Format response
$response = [
    'success' => true,
    'timestamp' => date('c'),
    'language' => $currentLang,
    'room_id' => $roomId,
    'categories' => [],
    'estimated_delivery' => getEstimatedDeliveryTime()
];

foreach ($categories as $category) {
    $catData = [
        'id' => $category['id'],
        'name' => $category['name'],
        'description' => $category['description'] ?? '',
        'icon' => $category['icon'] ?? null,
        'items' => []
    ];

    foreach ($category['items'] as $item) {
        $catData['items'][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'description' => $item['description'] ?? '',
            'price' => (float) $item['price'],
            'image' => $item['image'] ?? null,
            'is_available' => (bool) ($item['is_available'] ?? true),
            'allergens' => $item['allergens'] ?? null
        ];
    }

    $response['categories'][] = $catData;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
