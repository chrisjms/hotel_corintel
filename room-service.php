<?php
/**
 * Room Service Page - Hôtel Corintel
 * QR-code protected access for hotel guests
 *
 * Access: /room-service.php?room=X&token=TOKEN
 * Each room has a unique QR code that links to this page
 */

require_once __DIR__ . '/includes/functions.php';

// =====================================================
// ACCESS CONTROL - QR CODE VALIDATION
// =====================================================

// Start session for room service access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// MULTI-LANGUAGE DETECTION (on first QR scan)
// =====================================================
if (isset($_GET['room']) && isset($_GET['token']) && !isset($_COOKIE['hotel_corintel_lang'])) {
    $detectedLang = detectBrowserLanguage();
    if ($detectedLang) {
        setcookie('hotel_corintel_lang', $detectedLang, time() + (365 * 24 * 60 * 60), '/');
        $_COOKIE['hotel_corintel_lang'] = $detectedLang;
    }
}

// Check access (via URL params or session)
$accessCheck = checkRoomServiceAccess();

// Get hotel info for error pages
$hotelName = getHotelName();
$logoText = getLogoText();

// If no valid access, show error page
if (!$accessCheck['valid']) {
    $errorType = $accessCheck['error'];
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Accès Refusé | <?= h($hotelName) ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
        <?= getThemeCSS() ?>
        <style>
            .access-denied {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                background: linear-gradient(135deg, var(--color-cream) 0%, #fff 100%);
            }
            .access-denied-card {
                max-width: 420px;
                background: #fff;
                border-radius: 20px;
                padding: 3rem 2rem;
                text-align: center;
                box-shadow: 0 20px 60px rgba(139, 111, 71, 0.15);
            }
            .access-denied-icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 1.5rem;
                background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .access-denied-icon svg {
                width: 40px;
                height: 40px;
                color: #fff;
            }
            .access-denied h1 {
                font-family: var(--font-heading);
                font-size: 1.75rem;
                color: var(--color-primary);
                margin-bottom: 1rem;
            }
            .access-denied p {
                color: var(--color-text-light);
                line-height: 1.6;
                margin-bottom: 2rem;
            }
            .access-denied .hotel-logo {
                margin-top: 2rem;
                padding-top: 2rem;
                border-top: 1px solid var(--color-beige);
            }
            .access-denied .hotel-logo span {
                font-family: var(--font-heading);
                font-size: 1.25rem;
                color: var(--color-primary);
            }
            .access-denied .hotel-logo small {
                display: block;
                color: var(--color-text-light);
                font-size: 0.85rem;
                margin-top: 0.25rem;
            }
        </style>
    </head>
    <body>
        <div class="access-denied">
            <div class="access-denied-card">
                <div class="access-denied-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <h1>Accès Réservé aux Clients</h1>
                <p>
                    <?php if ($errorType === 'invalid_token'): ?>
                        Le lien que vous avez utilisé n'est pas valide. Veuillez scanner le QR code présent dans votre chambre pour accéder au Room Service.
                    <?php elseif ($errorType === 'room_not_found' || $errorType === 'room_inactive'): ?>
                        Cette chambre n'est pas disponible pour le Room Service. Veuillez contacter la réception.
                    <?php else: ?>
                        Pour commander le Room Service, veuillez scanner le QR code présent dans votre chambre d'hôtel.
                    <?php endif; ?>
                </p>
                <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                <div class="hotel-logo">
                    <span><?= h($hotelName) ?></span>
                    <small><?= h($logoText) ?></small>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// =====================================================
// VALID ACCESS - LOAD ROOM SERVICE
// =====================================================

$currentRoom = $accessCheck['room'];
$roomNumber = $currentRoom['room_number'];
$roomId = $currentRoom['id'];

// Log QR scan only on initial access (not on form submissions or refreshes)
// We check if this is a fresh QR scan by looking at a session flag
if (isset($_GET['room']) && isset($_GET['token']) && empty($_SESSION['qr_scan_logged_' . $roomId])) {
    logQrScan($roomId);
    $_SESSION['qr_scan_logged_' . $roomId] = time();
}

// Initialize pages table for navigation
initPagesTable();
$navPages = getNavigationPages();

// Get message categories for contact modal
$messageCategories = getGuestMessageCategories();

// Get current language
$currentLang = getCurrentLanguage();

// Get active items with translations
$items = getRoomServiceItemsTranslated(true, $currentLang);
$categories = getRoomServiceCategoriesTranslated($currentLang);
$paymentMethods = getRoomServicePaymentMethods();

// Store all translations for JS
$allItemTranslations = [];
foreach ($items as $item) {
    $allItemTranslations[$item['id']] = getItemTranslations($item['id']);
}

$allCategoryTranslations = [];
foreach (array_keys($categories) as $catCode) {
    $allCategoryTranslations[$catCode] = getCategoryTranslations($catCode);
}

// Get order history for the room
$orderHistory = getRoomOrderHistory($roomId, 5);
$frequentItems = getRoomFrequentItems($roomId, 4);
$estimatedDelivery = getEstimatedDeliveryTime();

// Group items by category
$itemsByCategory = [];
foreach ($items as $item) {
    $cat = $item['category'] ?? 'general';
    if (!isset($itemsByCategory[$cat])) {
        $itemsByCategory[$cat] = [];
    }
    $itemsByCategory[$cat][] = $item;
}

// Handle order submission
$orderSuccess = false;
$orderId = null;
$orderError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    // Room number comes from QR code session, not user input
    $guestName = trim($_POST['guest_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'room_charge';
    $deliveryDatetime = trim($_POST['delivery_datetime'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $orderItems = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

    if (empty($orderItems) || !is_array($orderItems)) {
        $orderError = 'Veuillez sélectionner au moins un article.';
    } elseif (!array_key_exists($paymentMethod, $paymentMethods)) {
        $orderError = 'Mode de paiement invalide.';
    } else {
        $deliveryValidation = validateDeliveryDatetime($deliveryDatetime);
        if (!$deliveryValidation['valid']) {
            $orderError = $deliveryValidation['message'];
        } else {
            $validItems = [];
            foreach ($orderItems as $orderItem) {
                if (!isset($orderItem['id']) || !isset($orderItem['quantity'])) continue;
                $itemId = intval($orderItem['id']);
                $quantity = intval($orderItem['quantity']);
                if ($quantity < 1) continue;
                $dbItem = getRoomServiceItemById($itemId);
                if ($dbItem && $dbItem['is_active']) {
                    $validItems[] = [
                        'id' => $dbItem['id'],
                        'name' => $dbItem['name'],
                        'price' => $dbItem['price'],
                        'quantity' => $quantity
                    ];
                }
            }

            if (empty($validItems)) {
                $orderError = 'Aucun article valide sélectionné.';
            } else {
                $availabilityCheck = validateOrderItemsAvailability(
                    array_map(fn($i) => ['item_id' => $i['id']], $validItems),
                    $deliveryValidation['datetime']
                );
                if (!$availabilityCheck['valid']) {
                    $orderError = implode(' ', $availabilityCheck['errors']);
                } else {
                    $orderId = createRoomServiceOrder([
                        'room_number' => $roomNumber, // Auto-filled from QR
                        'guest_name' => $guestName,
                        'phone' => $phone,
                        'payment_method' => $paymentMethod,
                        'delivery_datetime' => $deliveryValidation['datetime'],
                        'notes' => $notes
                    ], $validItems);

                    if ($orderId) {
                        $orderSuccess = true;
                    } else {
                        $orderError = 'Une erreur est survenue. Veuillez réessayer.';
                    }
                }
            }
        }
    }
}

$contactInfo = getContactInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Room Service | Chambre <?= h($roomNumber) ?> | <?= h($hotelName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?= getThemeCSS() ?>
    <?= getHotelNameJS() ?>
    <style>
        /* =====================================================
           PREMIUM MOBILE-FIRST ROOM SERVICE STYLES
           ===================================================== */

        :root {
            --rs-radius: 16px;
            --rs-shadow: 0 4px 20px rgba(0,0,0,0.08);
            --rs-shadow-hover: 0 8px 30px rgba(0,0,0,0.12);
            --rs-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --rs-header-height: 70px;
            --rs-cart-height: 72px;
        }

        /* Room indicator banner */
        .room-banner {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: #fff;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .room-banner svg {
            width: 18px;
            height: 18px;
            opacity: 0.9;
        }
        .room-banner strong {
            font-weight: 600;
        }

        /* Compact header for mobile */
        .rs-header {
            background: #fff;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--color-beige);
            position: sticky;
            top: 45px;
            z-index: 99;
        }
        .rs-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }
        .rs-logo {
            font-family: var(--font-heading);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-primary);
            text-decoration: none;
        }
        .rs-logo span {
            display: block;
            font-size: 0.75rem;
            font-weight: 400;
            color: var(--color-text-light);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .btn-contact-small {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.5rem 0.875rem;
            background: var(--color-cream);
            border: 1px solid var(--color-beige);
            border-radius: 8px;
            font-size: 0.8rem;
            color: var(--color-text);
            cursor: pointer;
            transition: var(--rs-transition);
        }
        .btn-contact-small:hover {
            background: var(--color-beige);
        }
        .btn-contact-small svg {
            width: 16px;
            height: 16px;
            color: var(--color-primary);
        }

        /* Main content area */
        .rs-main {
            background: var(--color-cream);
            min-height: calc(100vh - var(--rs-header-height) - var(--rs-cart-height));
            padding-bottom: calc(var(--rs-cart-height) + 2rem);
        }

        /* Hero section - simplified for mobile */
        .rs-hero {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: #fff;
            padding: 2.5rem 1.25rem;
            text-align: center;
        }
        .rs-hero h1 {
            font-family: var(--font-heading);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .rs-hero p {
            opacity: 0.9;
            font-size: 1rem;
        }

        /* Order History Section */
        .order-history-section {
            background: white;
            border-bottom: 1px solid var(--color-beige);
            padding: 1rem 1.25rem;
        }
        .order-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .order-history-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--color-primary);
            margin: 0;
        }
        .order-history-header h3 svg {
            width: 18px;
            height: 18px;
        }
        .order-history-toggle {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: none;
            border: none;
            color: var(--color-primary);
            font-size: 0.8rem;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
        }
        .order-history-toggle svg {
            width: 16px;
            height: 16px;
            transition: transform 0.2s;
        }
        .order-history-toggle.expanded svg {
            transform: rotate(180deg);
        }
        .order-history-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            max-height: 140px;
            overflow: hidden;
            transition: max-height 0.3s;
        }
        .order-history-list.expanded {
            max-height: 500px;
        }
        .order-history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--color-cream);
            border-radius: 8px;
            cursor: pointer;
        }
        .order-history-item:hover {
            background: var(--color-beige);
        }
        .order-history-main {
            flex: 1;
            min-width: 0;
        }
        .order-date {
            font-size: 0.7rem;
            color: var(--color-text-light);
            margin-bottom: 0.25rem;
        }
        .order-items {
            font-size: 0.85rem;
            color: var(--color-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .order-history-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
            margin-left: 1rem;
        }
        .order-total {
            font-weight: 600;
            color: var(--color-primary);
            font-size: 0.9rem;
        }
        .order-status {
            font-size: 0.65rem;
            padding: 0.125rem 0.5rem;
            border-radius: 10px;
            text-transform: uppercase;
            font-weight: 500;
        }
        .order-status-delivered {
            background: #C8E6C9;
            color: #2E7D32;
        }
        .order-status-pending, .order-status-confirmed, .order-status-preparing {
            background: #FFF3E0;
            color: #E65100;
        }
        .order-status-cancelled {
            background: #FFCDD2;
            color: #C62828;
        }
        /* Quick Reorder */
        .quick-reorder {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px dashed var(--color-beige);
        }
        .quick-reorder-label {
            font-size: 0.75rem;
            color: var(--color-text-light);
            margin-bottom: 0.5rem;
            display: block;
        }
        .quick-reorder-items {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .quick-reorder-btn {
            padding: 0.375rem 0.75rem;
            background: white;
            border: 1px solid var(--color-primary);
            border-radius: 20px;
            color: var(--color-primary);
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quick-reorder-btn:hover {
            background: var(--color-primary);
            color: white;
        }

        /* Category tabs - horizontal scroll */
        .category-tabs {
            display: flex;
            gap: 0.5rem;
            padding: 1rem 1.25rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            background: #fff;
            border-bottom: 1px solid var(--color-beige);
            position: sticky;
            top: 115px;
            z-index: 98;
        }
        .category-tabs::-webkit-scrollbar {
            display: none;
        }
        .category-tab {
            flex-shrink: 0;
            padding: 0.625rem 1rem;
            background: var(--color-cream);
            border: 1px solid var(--color-beige);
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--color-text);
            cursor: pointer;
            transition: var(--rs-transition);
            white-space: nowrap;
        }
        .category-tab.active,
        .category-tab:hover {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: #fff;
        }
        .category-tab .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            margin-left: 0.35rem;
            padding: 0 0.35rem;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            font-size: 0.75rem;
        }
        .category-tab.active .tab-count {
            background: rgba(255,255,255,0.3);
        }

        /* Menu sections */
        .menu-section {
            padding: 1.25rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--color-beige);
        }
        .section-title {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-primary);
        }
        .section-availability {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .section-availability.available {
            background: rgba(72, 187, 120, 0.15);
            color: #276749;
        }
        .section-availability.unavailable {
            background: rgba(245, 101, 101, 0.15);
            color: #C53030;
        }
        .section-availability.always {
            background: rgba(66, 153, 225, 0.15);
            color: #2B6CB0;
        }
        .section-availability svg {
            width: 14px;
            height: 14px;
        }

        /* Item cards - Mobile optimized */
        .items-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .item-card {
            display: flex;
            background: #fff;
            border-radius: var(--rs-radius);
            box-shadow: var(--rs-shadow);
            overflow: hidden;
            transition: var(--rs-transition);
        }
        .item-card:active {
            transform: scale(0.98);
        }
        .item-image {
            width: 110px;
            min-width: 110px;
            height: 110px;
            background: var(--color-beige);
            flex-shrink: 0;
        }
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .item-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-beige) 0%, var(--color-cream) 100%);
        }
        .item-image-placeholder svg {
            width: 32px;
            height: 32px;
            color: var(--color-primary);
            opacity: 0.4;
        }
        .item-content {
            flex: 1;
            padding: 0.875rem 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .item-name {
            font-family: var(--font-heading);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }
        .item-description {
            font-size: 0.8rem;
            color: var(--color-text-light);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .item-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 0.5rem;
        }
        .item-price {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--color-primary);
        }

        /* Quantity controls - larger touch targets */
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: var(--color-cream);
            border-radius: 8px;
            padding: 0.25rem;
        }
        .quantity-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: #fff;
            border-radius: 6px;
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--color-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--rs-transition);
            -webkit-tap-highlight-color: transparent;
        }
        .quantity-btn:active {
            transform: scale(0.9);
            background: var(--color-primary);
            color: #fff;
        }
        .quantity-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        .quantity-btn:disabled:active {
            transform: none;
            background: #fff;
            color: var(--color-primary);
        }
        .quantity-value {
            width: 32px;
            text-align: center;
            font-weight: 700;
            font-size: 1rem;
            color: var(--color-text);
        }
        .quantity-value.has-items {
            color: var(--color-primary);
        }

        /* Floating cart bar - bottom sheet style */
        .cart-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid var(--color-beige);
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
            z-index: 1000;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .cart-bar.visible {
            transform: translateY(0);
        }
        .cart-bar-content {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.25rem;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .cart-summary {
            flex: 1;
        }
        .cart-items-count {
            font-size: 0.85rem;
            color: var(--color-text-light);
        }
        .cart-items-count strong {
            color: var(--color-primary);
        }
        .cart-total {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        .btn-view-cart {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--rs-transition);
        }
        .btn-view-cart:active {
            transform: scale(0.96);
        }
        .btn-view-cart svg {
            width: 20px;
            height: 20px;
        }

        /* Cart modal - full screen on mobile */
        .cart-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .cart-modal.active {
            opacity: 1;
            visibility: visible;
        }
        .cart-modal-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            max-height: 90vh;
            background: #fff;
            border-radius: 24px 24px 0 0;
            transform: translateY(100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }
        .cart-modal.active .cart-modal-content {
            transform: translateY(0);
        }
        .cart-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--color-beige);
        }
        .cart-modal-header h2 {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            color: var(--color-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .cart-modal-header h2 svg {
            width: 24px;
            height: 24px;
        }
        .btn-close-cart {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-cream);
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }
        .btn-close-cart svg {
            width: 20px;
            height: 20px;
            color: var(--color-text);
        }
        .cart-modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 1.5rem;
        }
        .cart-empty-modal {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--color-text-light);
        }
        .cart-empty-modal svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--color-beige);
            gap: 1rem;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        .cart-item-qty {
            font-size: 0.85rem;
            color: var(--color-text-light);
        }
        .cart-item-price {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--color-primary);
        }
        .btn-remove-item {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(220, 53, 69, 0.1);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--rs-transition);
        }
        .btn-remove-item svg {
            width: 18px;
            height: 18px;
            color: #dc3545;
        }
        .btn-remove-item:active {
            background: #dc3545;
        }
        .btn-remove-item:active svg {
            color: #fff;
        }
        .cart-modal-footer {
            padding: 1rem 1.5rem 1.5rem;
            border-top: 1px solid var(--color-beige);
            background: var(--color-cream);
        }
        /* Delivery Estimate */
        .delivery-estimate {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #2E7D32;
        }
        .delivery-estimate svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        .delivery-estimate.busy {
            background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
            color: #E65100;
        }
        /* Notification Toggle */
        .notification-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem;
            border: 1px dashed var(--color-primary);
            background: transparent;
            border-radius: 8px;
            font-size: 0.875rem;
            color: var(--color-primary);
            cursor: pointer;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        .notification-toggle:hover {
            background: rgba(139, 111, 71, 0.05);
        }
        .notification-toggle.active {
            background: var(--color-primary);
            color: white;
            border-style: solid;
        }
        .notification-toggle svg {
            width: 18px;
            height: 18px;
        }
        .cart-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .cart-total-label {
            font-size: 1rem;
            color: var(--color-text);
        }
        .cart-total-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        .btn-checkout {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: var(--rs-transition);
        }
        .btn-checkout:active {
            transform: scale(0.98);
        }
        .btn-checkout svg {
            width: 22px;
            height: 22px;
        }

        /* Checkout form modal */
        .checkout-form {
            padding-top: 1rem;
        }
        .form-section {
            margin-bottom: 1.5rem;
        }
        .form-section-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color-text-light);
            margin-bottom: 0.75rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--color-beige);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: var(--rs-transition);
            background: #fff;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.15);
        }
        .form-group small {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.8rem;
            color: var(--color-text-light);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .room-display {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            border-radius: 10px;
            color: #fff;
        }
        .room-display svg {
            width: 24px;
            height: 24px;
            opacity: 0.9;
        }
        .room-display-text {
            flex: 1;
        }
        .room-display-text small {
            display: block;
            font-size: 0.75rem;
            opacity: 0.8;
        }
        .room-display-text strong {
            font-size: 1.25rem;
            font-weight: 700;
        }

        /* Order success */
        .order-success {
            text-align: center;
            padding: 4rem 2rem;
            max-width: 500px;
            margin: 0 auto;
        }
        .order-success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: successPulse 0.6s ease-out;
        }
        @keyframes successPulse {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        .order-success-icon svg {
            width: 50px;
            height: 50px;
            color: #fff;
        }
        .order-success h2 {
            font-family: var(--font-heading);
            font-size: 2rem;
            color: var(--color-primary);
            margin-bottom: 1rem;
        }
        .order-success p {
            color: var(--color-text-light);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .order-id {
            display: inline-block;
            background: var(--color-cream);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 2rem;
        }
        .btn-new-order {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        /* Alert styles */
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-error svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* Service unavailable */
        .service-unavailable {
            text-align: center;
            padding: 4rem 2rem;
        }
        .service-unavailable svg {
            width: 80px;
            height: 80px;
            color: var(--color-primary);
            opacity: 0.5;
            margin-bottom: 1.5rem;
        }
        .service-unavailable h2 {
            font-family: var(--font-heading);
            font-size: 1.75rem;
            color: var(--color-primary);
            margin-bottom: 1rem;
        }
        .service-unavailable p {
            color: var(--color-text-light);
            max-width: 400px;
            margin: 0 auto;
        }

        /* Desktop optimizations */
        @media (min-width: 768px) {
            .room-banner {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
            }
            .rs-header {
                position: fixed;
                top: 45px;
                left: 0;
                right: 0;
            }
            .category-tabs {
                position: fixed;
                top: 115px;
                left: 0;
                right: 0;
                justify-content: center;
            }
            .rs-main {
                padding-top: 120px;
            }
            .rs-hero {
                padding: 3rem 2rem;
            }
            .rs-hero h1 {
                font-size: 2.5rem;
            }
            .menu-section {
                padding: 2rem;
            }
            .items-list {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
                gap: 1.25rem;
            }
            .item-card {
                flex-direction: column;
            }
            .item-image {
                width: 100%;
                height: 180px;
            }
            .item-content {
                padding: 1.25rem;
            }
            .cart-modal-content {
                max-width: 500px;
                left: 50%;
                transform: translateX(-50%) translateY(100%);
                border-radius: 24px;
                bottom: 20px;
                max-height: calc(90vh - 40px);
            }
            .cart-modal.active .cart-modal-content {
                transform: translateX(-50%) translateY(0);
            }
            .cart-bar-content {
                justify-content: center;
                gap: 2rem;
            }
        }

        /* Hide footer on room service for cleaner mobile experience */
        .footer {
            display: none;
        }

        /* Quick reorder highlight animation */
        @keyframes highlightItem {
            0% { box-shadow: 0 0 0 0 var(--color-primary); }
            50% { box-shadow: 0 0 0 4px rgba(139, 111, 71, 0.3); }
            100% { box-shadow: var(--rs-shadow); }
        }
    </style>
</head>
<body>
    <!-- Room Banner -->
    <div class="room-banner">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <span>Chambre <strong><?= h($roomNumber) ?></strong></span>
    </div>

    <!-- Compact Header -->
    <header class="rs-header">
        <div class="rs-header-content">
            <a href="index.php" class="rs-logo">
                <?= h($hotelName) ?>
                <span>Room Service</span>
            </a>
            <button type="button" class="btn-contact-small" id="btnContactReception">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81"/>
                </svg>
                Réception
            </button>
        </div>
    </header>

    <main class="rs-main">
        <?php if ($orderSuccess): ?>
        <!-- Order Success -->
        <div class="order-success">
            <div class="order-success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <h2 data-i18n="roomService.orderConfirmed">Commande confirmée !</h2>
            <p data-i18n="roomService.orderSuccessMessage">Votre commande a été enregistrée. Notre équipe va la préparer et vous la livrer dans les meilleurs délais.</p>
            <div class="order-id">Commande #<?= $orderId ?></div>
            <a href="room-service.php?room=<?= $roomId ?>&token=<?= h(generateRoomServiceToken($roomId, $roomNumber)) ?>" class="btn-new-order">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Nouvelle commande
            </a>
        </div>

        <?php elseif (empty($items)): ?>
        <!-- Service Unavailable -->
        <div class="service-unavailable">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
            </svg>
            <h2 data-i18n="roomService.serviceUnavailable">Service momentanément indisponible</h2>
            <p data-i18n="roomService.serviceUnavailableMessage">Le room service n'est pas disponible actuellement. Veuillez contacter la réception.</p>
        </div>

        <?php else: ?>
        <!-- Hero -->
        <section class="rs-hero">
            <h1 data-i18n="roomService.heroTitle">Room Service</h1>
            <p data-i18n="roomService.heroDescription">Commandez et faites-vous livrer en chambre</p>
        </section>

        <?php if (!empty($orderHistory)): ?>
        <!-- Order History Section -->
        <section class="order-history-section">
            <div class="order-history-header">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Vos commandes récentes
                </h3>
                <button type="button" class="order-history-toggle" id="orderHistoryToggle">
                    <span>Voir tout</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>
            </div>
            <div class="order-history-list" id="orderHistoryList">
                <?php foreach ($orderHistory as $order): ?>
                <div class="order-history-item" data-order-id="<?= $order['id'] ?>">
                    <div class="order-history-main">
                        <div class="order-date"><?= $order['created_at_formatted'] ?></div>
                        <div class="order-items">
                            <?php
                            $itemNames = array_map(fn($i) => $i['name'], array_slice($order['items'], 0, 2));
                            echo h(implode(', ', $itemNames));
                            if (count($order['items']) > 2) echo ' +' . (count($order['items']) - 2);
                            ?>
                        </div>
                    </div>
                    <div class="order-history-right">
                        <span class="order-total"><?= number_format($order['total_amount'], 2, ',', ' ') ?> €</span>
                        <span class="order-status order-status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($frequentItems)): ?>
            <div class="quick-reorder">
                <span class="quick-reorder-label">Commander à nouveau :</span>
                <div class="quick-reorder-items">
                    <?php foreach ($frequentItems as $fItem): ?>
                    <button type="button" class="quick-reorder-btn" data-item-id="<?= $fItem['item_id'] ?>">
                        <?= h($fItem['item_name']) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Category Tabs -->
        <div class="category-tabs" id="categoryTabs">
            <?php $firstCategory = true; ?>
            <?php foreach ($itemsByCategory as $categoryKey => $categoryItems): ?>
            <button type="button" class="category-tab<?= $firstCategory ? ' active' : '' ?>" data-category="<?= h($categoryKey) ?>">
                <?= h($categories[$categoryKey] ?? ucfirst($categoryKey)) ?>
                <span class="tab-count"><?= count($categoryItems) ?></span>
            </button>
            <?php $firstCategory = false; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($orderError): ?>
        <div style="padding: 1rem 1.25rem;">
            <div class="alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= h($orderError) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Menu Sections -->
        <?php $firstSection = true; ?>
        <?php foreach ($itemsByCategory as $categoryKey => $categoryItems): ?>
        <?php $catAvailability = getCategoryAvailabilityInfo($categoryKey); ?>
        <section class="menu-section" id="section-<?= h($categoryKey) ?>" style="<?= $firstSection ? '' : 'display:none;' ?>">
            <div class="section-header">
                <h2 class="section-title"><?= h($categories[$categoryKey] ?? ucfirst($categoryKey)) ?></h2>
                <?php if ($catAvailability['time_start'] && $catAvailability['time_end']): ?>
                <span class="section-availability <?= $catAvailability['available'] ? 'available' : 'unavailable' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <?= h($catAvailability['time_start']) ?> - <?= h($catAvailability['time_end']) ?>
                </span>
                <?php else: ?>
                <span class="section-availability always">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    24h/24
                </span>
                <?php endif; ?>
            </div>

            <div class="items-list">
                <?php foreach ($categoryItems as $item): ?>
                <div class="item-card" data-item-id="<?= $item['id'] ?>" data-item-name="<?= h($item['name']) ?>" data-item-price="<?= $item['price'] ?>">
                    <div class="item-image">
                        <?php if ($item['image']): ?>
                        <img src="<?= h($item['image']) ?>" alt="<?= h($item['name']) ?>" loading="lazy">
                        <?php else: ?>
                        <div class="item-image-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                            </svg>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-content">
                        <div>
                            <h3 class="item-name"><?= h($item['name']) ?></h3>
                            <?php if (!empty($item['description'])): ?>
                            <p class="item-description"><?= h($item['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="item-footer">
                            <span class="item-price"><?= number_format($item['price'], 2, ',', ' ') ?> €</span>
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn btn-minus" disabled>−</button>
                                <span class="quantity-value">0</span>
                                <button type="button" class="quantity-btn btn-plus">+</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php $firstSection = false; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Floating Cart Bar -->
    <div class="cart-bar" id="cartBar">
        <div class="cart-bar-content">
            <div class="cart-summary">
                <div class="cart-items-count"><strong id="cartCount">0</strong> article(s)</div>
                <div class="cart-total" id="cartTotalBar">0,00 €</div>
            </div>
            <button type="button" class="btn-view-cart" id="btnViewCart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                Commander
            </button>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-modal-content">
            <div class="cart-modal-header">
                <h2>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    Votre commande
                </h2>
                <button type="button" class="btn-close-cart" id="btnCloseCart">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="cart-modal-body" id="cartModalBody">
                <div class="cart-empty-modal" id="cartEmpty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <p>Votre panier est vide</p>
                </div>
                <div id="cartItems"></div>

                <!-- Checkout Form -->
                <form method="POST" id="orderForm" class="checkout-form" style="display:none;">
                    <input type="hidden" name="action" value="place_order">
                    <input type="hidden" name="items" id="orderItemsInput">

                    <div class="form-section">
                        <div class="form-section-title">Livraison en chambre</div>
                        <div class="room-display">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                            <div class="room-display-text">
                                <small>Chambre</small>
                                <strong><?= h($roomNumber) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Vos informations (optionnel)</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="guest_name">Votre nom</label>
                                <input type="text" id="guest_name" name="guest_name" placeholder="Pour la livraison">
                            </div>
                            <div class="form-group">
                                <label for="phone">Téléphone</label>
                                <input type="tel" id="phone" name="phone" placeholder="En cas de besoin">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Livraison</div>
                        <div class="form-group">
                            <label for="delivery_datetime">Date et heure souhaitées *</label>
                            <input type="datetime-local" id="delivery_datetime" name="delivery_datetime" required>
                            <small>Minimum 30 minutes à l'avance</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Paiement</div>
                        <div class="form-group">
                            <label for="payment_method">Mode de paiement</label>
                            <select id="payment_method" name="payment_method">
                                <?php foreach ($paymentMethods as $key => $label): ?>
                                <option value="<?= h($key) ?>"><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-group">
                            <label for="notes">Remarques</label>
                            <textarea id="notes" name="notes" rows="2" placeholder="Allergies, préférences..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="cart-modal-footer">
                <!-- Estimated Delivery Time -->
                <div class="delivery-estimate" id="deliveryEstimate">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span><?= h($estimatedDelivery['message']) ?></span>
                </div>

                <!-- Push Notification Toggle -->
                <button type="button" class="notification-toggle" id="notificationToggle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    Activer les notifications
                </button>

                <div class="cart-total-row">
                    <span class="cart-total-label">Total</span>
                    <span class="cart-total-value" id="cartTotalModal">0,00 €</span>
                </div>
                <button type="button" class="btn-checkout" id="btnCheckout" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span id="checkoutBtnText">Valider la commande</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Contact Reception Modal (simplified) -->
    <div class="modal-overlay" id="contactReceptionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Contacter la réception</h3>
                <button type="button" class="modal-close" id="modalClose">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-success" id="modalSuccess" style="display: none;">
                    <div class="modal-success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <h3>Message envoyé</h3>
                    <p>Votre message a bien été transmis à la réception.</p>
                    <button type="button" class="btn-new-message" id="btnNewMessage">Envoyer un autre message</button>
                </div>
                <div id="modalFormContainer">
                    <div class="modal-alert-error" id="modalError" style="display: none;"></div>
                    <form method="POST" class="modal-form" id="modalMessageForm" action="contact.php">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="redirect_back" value="1">
                        <input type="hidden" name="msg_room_number" value="<?= h($roomNumber) ?>">
                        <div class="form-group">
                            <label for="modal_guest_name">Votre nom</label>
                            <input type="text" id="modal_guest_name" name="msg_guest_name" placeholder="Optionnel">
                        </div>
                        <div class="form-group">
                            <label for="modal_category">Catégorie</label>
                            <select id="modal_category" name="msg_category">
                                <?php foreach ($messageCategories as $key => $label): ?>
                                <option value="<?= h($key) ?>"><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="modal_message">Votre message *</label>
                            <textarea id="modal_message" name="msg_message" required placeholder="Comment pouvons-nous vous aider ?"></textarea>
                        </div>
                        <button type="submit" class="btn-submit-modal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                            Envoyer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.itemTranslations = <?= json_encode($allItemTranslations) ?>;
        window.categoryTranslations = <?= json_encode($allCategoryTranslations) ?>;
        window.defaultLang = '<?= getDefaultLanguage() ?>';
    </script>
    <script src="js/translations.js"></script>
    <script src="js/i18n.js"></script>
    <script>
        // =====================================================
        // ROOM SERVICE APP
        // =====================================================

        const cart = {};
        let checkoutMode = false;

        // DOM Elements
        const cartBar = document.getElementById('cartBar');
        const cartModal = document.getElementById('cartModal');
        const cartItems = document.getElementById('cartItems');
        const cartEmpty = document.getElementById('cartEmpty');
        const cartCount = document.getElementById('cartCount');
        const cartTotalBar = document.getElementById('cartTotalBar');
        const cartTotalModal = document.getElementById('cartTotalModal');
        const btnViewCart = document.getElementById('btnViewCart');
        const btnCloseCart = document.getElementById('btnCloseCart');
        const btnCheckout = document.getElementById('btnCheckout');
        const checkoutBtnText = document.getElementById('checkoutBtnText');
        const orderForm = document.getElementById('orderForm');
        const orderItemsInput = document.getElementById('orderItemsInput');

        // Format price
        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        }

        // Update cart display
        function updateCart() {
            let total = 0;
            let count = 0;
            let html = '';
            const itemsArray = [];

            for (const [id, item] of Object.entries(cart)) {
                if (item.quantity > 0) {
                    const subtotal = item.price * item.quantity;
                    total += subtotal;
                    count += item.quantity;
                    html += `
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <div class="cart-item-name">${item.name}</div>
                                <div class="cart-item-qty">${item.quantity} × ${formatPrice(item.price)}</div>
                            </div>
                            <span class="cart-item-price">${formatPrice(subtotal)}</span>
                            <button type="button" class="btn-remove-item" onclick="removeFromCart(${id})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </div>
                    `;
                    itemsArray.push({ id: parseInt(id), quantity: item.quantity });
                }
            }

            cartItems.innerHTML = html;
            cartEmpty.style.display = count > 0 ? 'none' : 'block';
            orderForm.style.display = count > 0 ? 'block' : 'none';
            cartCount.textContent = count;
            cartTotalBar.textContent = formatPrice(total);
            cartTotalModal.textContent = formatPrice(total);
            orderItemsInput.value = JSON.stringify(itemsArray);
            btnCheckout.disabled = count === 0;

            // Show/hide cart bar
            if (count > 0) {
                cartBar.classList.add('visible');
            } else {
                cartBar.classList.remove('visible');
            }
        }

        // Remove item from cart
        function removeFromCart(id) {
            if (cart[id]) {
                cart[id].quantity = 0;
                const card = document.querySelector(`[data-item-id="${id}"]`);
                if (card) {
                    card.querySelector('.quantity-value').textContent = '0';
                    card.querySelector('.quantity-value').classList.remove('has-items');
                    card.querySelector('.btn-minus').disabled = true;
                }
                updateCart();
            }
        }

        // Initialize item cards
        document.querySelectorAll('.item-card').forEach(card => {
            const id = card.dataset.itemId;
            const name = card.dataset.itemName;
            const price = parseFloat(card.dataset.itemPrice);
            const quantityEl = card.querySelector('.quantity-value');
            const minusBtn = card.querySelector('.btn-minus');
            const plusBtn = card.querySelector('.btn-plus');

            cart[id] = { name, price, quantity: 0 };

            plusBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                cart[id].quantity++;
                quantityEl.textContent = cart[id].quantity;
                quantityEl.classList.add('has-items');
                minusBtn.disabled = false;
                updateCart();
            });

            minusBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (cart[id].quantity > 0) {
                    cart[id].quantity--;
                    quantityEl.textContent = cart[id].quantity;
                    if (cart[id].quantity === 0) {
                        quantityEl.classList.remove('has-items');
                        minusBtn.disabled = true;
                    }
                    updateCart();
                }
            });
        });

        // Category tabs
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Update active tab
                document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // Show corresponding section
                const category = tab.dataset.category;
                document.querySelectorAll('.menu-section').forEach(section => {
                    section.style.display = section.id === `section-${category}` ? 'block' : 'none';
                });
            });
        });

        // Cart modal
        btnViewCart?.addEventListener('click', () => {
            cartModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        btnCloseCart?.addEventListener('click', () => {
            cartModal.classList.remove('active');
            document.body.style.overflow = '';
        });

        cartModal?.addEventListener('click', (e) => {
            if (e.target === cartModal) {
                cartModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Checkout button
        btnCheckout?.addEventListener('click', () => {
            if (btnCheckout.disabled) return;

            const items = JSON.parse(orderItemsInput.value);
            if (items.length === 0) {
                alert('Veuillez sélectionner au moins un article.');
                return;
            }

            const deliveryDatetime = document.getElementById('delivery_datetime').value;
            if (!deliveryDatetime) {
                alert('Veuillez indiquer la date et heure de livraison.');
                document.getElementById('delivery_datetime').focus();
                return;
            }

            // Check if delivery time is in the future
            const deliveryTime = new Date(deliveryDatetime);
            const minTime = new Date();
            minTime.setMinutes(minTime.getMinutes() + 25);
            if (deliveryTime < minTime) {
                alert('La livraison doit être prévue au moins 30 minutes à l\'avance.');
                return;
            }

            // Submit form
            orderForm.submit();
        });

        // Set minimum delivery datetime
        function updateMinDeliveryTime() {
            const deliveryInput = document.getElementById('delivery_datetime');
            if (!deliveryInput) return;

            const now = new Date();
            now.setMinutes(now.getMinutes() + 30);
            const minutes = now.getMinutes();
            const roundedMinutes = Math.ceil(minutes / 15) * 15;
            now.setMinutes(roundedMinutes);
            now.setSeconds(0);

            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const mins = String(now.getMinutes()).padStart(2, '0');

            const minDatetime = `${year}-${month}-${day}T${hours}:${mins}`;
            deliveryInput.min = minDatetime;

            const maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + 7);
            const maxYear = maxDate.getFullYear();
            const maxMonth = String(maxDate.getMonth() + 1).padStart(2, '0');
            const maxDay = String(maxDate.getDate()).padStart(2, '0');
            deliveryInput.max = `${maxYear}-${maxMonth}-${maxDay}T23:59`;

            if (!deliveryInput.value) {
                deliveryInput.value = minDatetime;
            }
        }
        updateMinDeliveryTime();
        setInterval(updateMinDeliveryTime, 60000);

        // Contact modal
        const contactModal = document.getElementById('contactReceptionModal');
        const btnContact = document.getElementById('btnContactReception');
        const btnModalClose = document.getElementById('modalClose');

        btnContact?.addEventListener('click', () => {
            contactModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        btnModalClose?.addEventListener('click', () => {
            contactModal.classList.remove('active');
            document.body.style.overflow = '';
        });

        contactModal?.addEventListener('click', (e) => {
            if (e.target === contactModal) {
                contactModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Handle contact form submission via AJAX
        document.getElementById('modalMessageForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const modalError = document.getElementById('modalError');
            const modalSuccess = document.getElementById('modalSuccess');
            const modalFormContainer = document.getElementById('modalFormContainer');

            try {
                const response = await fetch('contact.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await response.text();

                if (text.includes('message_sent=1') || text.includes('Message envoyé') || response.ok) {
                    modalFormContainer.style.display = 'none';
                    modalSuccess.style.display = 'block';
                } else {
                    modalError.textContent = 'Une erreur est survenue. Veuillez réessayer.';
                    modalError.style.display = 'block';
                }
            } catch (error) {
                modalError.textContent = 'Une erreur est survenue. Veuillez réessayer.';
                modalError.style.display = 'block';
            }
        });

        document.getElementById('btnNewMessage')?.addEventListener('click', () => {
            document.getElementById('modalMessageForm').reset();
            document.getElementById('modalError').style.display = 'none';
            document.getElementById('modalSuccess').style.display = 'none';
            document.getElementById('modalFormContainer').style.display = 'block';
        });

        // Escape key closes modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cartModal?.classList.remove('active');
                contactModal?.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // =====================================================
        // SERVICE WORKER & PUSH NOTIFICATIONS
        // =====================================================

        // Register service worker for offline support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('/sw.js');
                    console.log('Service Worker registered:', registration.scope);

                    // Cache menu data for offline use
                    if (registration.active) {
                        const menuData = await fetch('/api/room-service-menu.php').then(r => r.json());
                        registration.active.postMessage({ type: 'CACHE_MENU', data: menuData });
                    }
                } catch (error) {
                    console.log('Service Worker registration failed:', error);
                }
            });
        }

        // Push notification functions
        const pushNotifications = {
            async init() {
                if (!('PushManager' in window)) {
                    console.log('Push notifications not supported');
                    return;
                }

                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    console.log('Push notification permission denied');
                    return;
                }

                await this.subscribe();
            },

            async subscribe() {
                try {
                    const registration = await navigator.serviceWorker.ready;

                    // Get VAPID public key
                    const response = await fetch('/api/push-subscribe.php');
                    const { publicKey } = await response.json();

                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: this.urlBase64ToUint8Array(publicKey)
                    });

                    // Send subscription to server
                    await fetch('/api/push-subscribe.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(subscription.toJSON())
                    });

                    console.log('Push subscription successful');
                    this.updateUI(true);
                } catch (error) {
                    console.error('Push subscription failed:', error);
                }
            },

            urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);
                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            },

            updateUI(subscribed) {
                const btn = document.getElementById('notificationToggle');
                if (btn) {
                    btn.textContent = subscribed ? 'Notifications activées' : 'Activer les notifications';
                    btn.classList.toggle('active', subscribed);
                }
            }
        };

        // Initialize push notifications when user interacts (to avoid permission prompt on load)
        document.getElementById('notificationToggle')?.addEventListener('click', () => {
            pushNotifications.init();
        });

        // =====================================================
        // ORDER HISTORY & QUICK REORDER
        // =====================================================

        // Order history toggle (expand/collapse)
        const orderHistoryToggle = document.getElementById('orderHistoryToggle');
        const orderHistoryList = document.getElementById('orderHistoryList');

        orderHistoryToggle?.addEventListener('click', () => {
            const isExpanded = orderHistoryList.classList.toggle('expanded');
            orderHistoryToggle.classList.toggle('expanded', isExpanded);
            orderHistoryToggle.querySelector('span').textContent = isExpanded ? 'Réduire' : 'Voir tout';
        });

        // Quick reorder buttons - add items to cart
        document.querySelectorAll('.quick-reorder-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const itemId = btn.dataset.itemId;
                const itemCard = document.querySelector(`[data-item-id="${itemId}"]`);

                if (itemCard) {
                    // Scroll to item category first
                    const section = itemCard.closest('.menu-section');
                    if (section) {
                        const categoryKey = section.id.replace('section-', '');
                        const categoryTab = document.querySelector(`.category-tab[data-category="${categoryKey}"]`);
                        if (categoryTab) {
                            categoryTab.click();
                        }
                    }

                    // Add item to cart
                    if (cart[itemId]) {
                        cart[itemId].quantity++;
                        const quantityEl = itemCard.querySelector('.quantity-value');
                        const minusBtn = itemCard.querySelector('.btn-minus');
                        quantityEl.textContent = cart[itemId].quantity;
                        quantityEl.classList.add('has-items');
                        minusBtn.disabled = false;
                        updateCart();
                    }

                    // Visual feedback on button
                    btn.textContent = '✓ Ajouté';
                    btn.style.background = 'var(--color-primary)';
                    btn.style.color = 'white';
                    setTimeout(() => {
                        btn.textContent = btn.dataset.originalText || btn.textContent;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 1500);

                    // Scroll to the item with smooth animation
                    setTimeout(() => {
                        itemCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        itemCard.style.animation = 'highlightItem 1s ease';
                        setTimeout(() => itemCard.style.animation = '', 1000);
                    }, 100);
                }
            });

            // Store original text for restoration
            btn.dataset.originalText = btn.textContent.trim();
        });

        // Click on order history item to reorder all items from that order
        document.querySelectorAll('.order-history-item').forEach(item => {
            item.addEventListener('click', () => {
                // Show a tooltip or feedback that this is a view-only for now
                const orderId = item.dataset.orderId;
                const orderItems = item.querySelector('.order-items').textContent;

                // Simple feedback for now - could be extended to full reorder
                const original = item.style.background;
                item.style.background = 'var(--color-beige)';
                setTimeout(() => {
                    item.style.background = original;
                }, 300);
            });
        });
    </script>
</body>
</html>
