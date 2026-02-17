<?php
/**
 * Room Service Page - Hôtel Corintel
 * Public page for guests to order room service
 */

require_once __DIR__ . '/includes/functions.php';

// Get message categories for the contact reception modal
$messageCategories = getGuestMessageCategories();

// Get current language for translations
$currentLang = getCurrentLanguage();

// Get active items with translations
$items = getRoomServiceItemsTranslated(true, $currentLang);
$categories = getRoomServiceCategoriesTranslated($currentLang);
$paymentMethods = getRoomServicePaymentMethods();

// Store all translations for JavaScript (for dynamic language switching)
$allItemTranslations = [];
foreach ($items as $item) {
    $allItemTranslations[$item['id']] = getItemTranslations($item['id']);
}

// Store all category translations for JavaScript
$allCategoryTranslations = [];
foreach (array_keys($categories) as $catCode) {
    $allCategoryTranslations[$catCode] = getCategoryTranslations($catCode);
}

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
    $roomNumber = trim($_POST['room_number'] ?? '');
    $guestName = trim($_POST['guest_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'room_charge';
    $deliveryDatetime = trim($_POST['delivery_datetime'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $orderItems = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

    // Validation
    if (empty($roomNumber)) {
        $orderError = 'Veuillez indiquer votre numéro de chambre.';
    } elseif (empty($orderItems) || !is_array($orderItems)) {
        $orderError = 'Veuillez sélectionner au moins un article.';
    } elseif (!array_key_exists($paymentMethod, $paymentMethods)) {
        $orderError = 'Mode de paiement invalide.';
    } else {
        // Validate delivery datetime
        $deliveryValidation = validateDeliveryDatetime($deliveryDatetime);
        if (!$deliveryValidation['valid']) {
            $orderError = $deliveryValidation['message'];
        } else {
            // Validate items and build order items array
            $validItems = [];
            foreach ($orderItems as $orderItem) {
                if (!isset($orderItem['id']) || !isset($orderItem['quantity'])) {
                    continue;
                }
                $itemId = intval($orderItem['id']);
                $quantity = intval($orderItem['quantity']);
                if ($quantity < 1) {
                    continue;
                }
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
                // Validate item availability at delivery time
                $availabilityCheck = validateOrderItemsAvailability(
                    array_map(fn($i) => ['item_id' => $i['id']], $validItems),
                    $deliveryValidation['datetime']
                );
                if (!$availabilityCheck['valid']) {
                    $orderError = implode(' ', $availabilityCheck['errors']);
                } else {
                    $orderId = createRoomServiceOrder([
                        'room_number' => $roomNumber,
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

$hotelName = getHotelName();
$logoText = getLogoText();
$contactInfo = getContactInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Commandez votre room service directement depuis votre chambre. <?= h($hotelName) ?>, <?= h($logoText) ?>.">
  <title>Room Service | <?= h($hotelName) ?> - <?= h($logoText) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <?= getThemeCSS() ?>
  <?= getHotelNameJS() ?>
  <style>
    .room-service-section {
      padding: 6rem 0 4rem;
      background: var(--color-cream);
    }
    .room-service-header {
      text-align: center;
      margin-bottom: 3rem;
    }
    .room-service-header h1 {
      margin-bottom: 1rem;
    }
    .room-service-header p {
      color: var(--color-text-light);
      max-width: 600px;
      margin: 0 auto;
    }
    .room-service-grid {
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: 2rem;
      align-items: start;
    }
    @media (max-width: 1024px) {
      .room-service-grid {
        grid-template-columns: 1fr;
      }
    }
    .category-section {
      margin-bottom: 1.5rem;
    }
    .category-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.5rem;
      padding: 1rem 0;
      border-bottom: 2px solid var(--color-beige);
      cursor: pointer;
      user-select: none;
      transition: var(--transition);
    }
    .category-header:hover {
      background: rgba(139, 111, 71, 0.03);
    }
    .category-header-left {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .category-toggle {
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.3s ease;
    }
    .category-toggle svg {
      width: 20px;
      height: 20px;
      color: var(--color-primary);
    }
    .category-section.expanded .category-toggle {
      transform: rotate(180deg);
    }
    .items-grid-wrapper {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.4s ease-out;
    }
    .category-section.expanded .items-grid-wrapper {
      max-height: 2000px;
      transition: max-height 0.5s ease-in;
    }
    .category-section.expanded .category-header {
      border-bottom-color: var(--color-primary);
    }
    .category-title {
      font-family: var(--font-heading);
      font-size: 1.5rem;
      color: var(--color-primary);
      margin: 0;
    }
    .category-availability {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    .category-availability.available {
      background: rgba(72, 187, 120, 0.15);
      color: #276749;
    }
    .category-availability.unavailable {
      background: rgba(245, 101, 101, 0.15);
      color: #C53030;
    }
    .category-availability.always-available {
      background: rgba(66, 153, 225, 0.15);
      color: #2B6CB0;
    }
    .items-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.5rem;
      padding-top: 1.5rem;
      padding-bottom: 0.5rem;
    }
    .item-card {
      background: var(--color-white);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow-soft);
      transition: var(--transition);
    }
    .item-card:hover {
      box-shadow: var(--shadow-hover);
      transform: translateY(-4px);
    }
    .item-image {
      height: 160px;
      background: var(--color-beige);
      overflow: hidden;
    }
    .item-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .item-content {
      padding: 1.25rem;
    }
    .item-name {
      font-family: var(--font-heading);
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    .item-description {
      color: var(--color-text-light);
      font-size: 0.9rem;
      margin-bottom: 1rem;
      min-height: 2.5rem;
    }
    .item-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .item-price {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--color-primary);
    }
    .quantity-control {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .quantity-btn {
      width: 32px;
      height: 32px;
      border: 1px solid var(--color-beige);
      background: var(--color-white);
      border-radius: 6px;
      cursor: pointer;
      font-size: 1.25rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition);
    }
    .quantity-btn:hover {
      background: var(--color-primary);
      color: var(--color-white);
      border-color: var(--color-primary);
    }
    .quantity-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    .quantity-value {
      width: 40px;
      text-align: center;
      font-weight: 600;
      font-size: 1rem;
    }
    .cart-sidebar {
      background: var(--color-white);
      border-radius: 12px;
      box-shadow: var(--shadow-soft);
      position: sticky;
      top: calc(var(--header-height) + 1rem);
    }
    .cart-header {
      padding: 1.25rem;
      border-bottom: 1px solid var(--color-beige);
    }
    .cart-header h3 {
      font-family: var(--font-heading);
      font-size: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .cart-header h3 svg {
      width: 24px;
      height: 24px;
      color: var(--color-primary);
    }
    .cart-body {
      padding: 1.25rem;
      max-height: 400px;
      overflow-y: auto;
    }
    .cart-empty {
      text-align: center;
      color: var(--color-text-light);
      padding: 2rem 0;
    }
    .cart-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid var(--color-beige);
    }
    .cart-item:last-child {
      border-bottom: none;
    }
    .cart-item-info {
      flex: 1;
    }
    .cart-item-name {
      font-weight: 600;
      font-size: 0.9rem;
    }
    .cart-item-qty {
      color: var(--color-text-light);
      font-size: 0.8rem;
    }
    .cart-item-subtotal {
      font-weight: 600;
      color: var(--color-primary);
    }
    .cart-item-remove {
      background: none;
      border: none;
      color: var(--color-text-light);
      cursor: pointer;
      padding: 0.25rem;
      margin-left: 0.5rem;
    }
    .cart-item-remove:hover {
      color: #e53e3e;
    }
    .cart-footer {
      padding: 1.25rem;
      border-top: 1px solid var(--color-beige);
      background: var(--color-cream);
      border-radius: 0 0 12px 12px;
    }
    .cart-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }
    .cart-total-label {
      font-size: 1rem;
      font-weight: 600;
    }
    .cart-total-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--color-primary);
    }
    .form-group {
      margin-bottom: 1rem;
    }
    .form-group label {
      display: block;
      font-weight: 500;
      margin-bottom: 0.375rem;
      font-size: 0.9rem;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.625rem 0.875rem;
      border: 1px solid var(--color-beige);
      border-radius: 6px;
      font-size: 0.9rem;
      font-family: var(--font-body);
      transition: var(--transition);
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.1);
    }
    .form-group small {
      display: block;
      margin-top: 0.25rem;
      color: var(--color-text-light);
      font-size: 0.8rem;
    }
    .btn-order {
      width: 100%;
      padding: 1rem;
      background: var(--color-primary);
      color: var(--color-white);
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .btn-order:hover {
      background: var(--color-primary-dark);
    }
    .btn-order:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    .btn-order svg {
      width: 20px;
      height: 20px;
    }
    .order-success {
      text-align: center;
      padding: 4rem 2rem;
    }
    .order-success-icon {
      width: 80px;
      height: 80px;
      background: rgba(72, 187, 120, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
    }
    .order-success-icon svg {
      width: 40px;
      height: 40px;
      color: #48BB78;
    }
    .order-success h2 {
      margin-bottom: 1rem;
    }
    .order-success p {
      color: var(--color-text-light);
      max-width: 500px;
      margin: 0 auto 2rem;
    }
    .order-id {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--color-primary);
      margin-bottom: 2rem;
    }
    .alert-error {
      background: rgba(245, 101, 101, 0.1);
      color: #C53030;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      border: 1px solid rgba(245, 101, 101, 0.3);
    }
    .no-items {
      text-align: center;
      padding: 4rem 2rem;
      color: var(--color-text-light);
    }
    .no-items svg {
      width: 64px;
      height: 64px;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header" id="header">
    <div class="container">
      <a href="index.php" class="logo">
        <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/>
        </svg>
        <div class="logo-text">
          <?= h($hotelName) ?>
          <span><?= h($logoText) ?></span>
        </div>
      </a>
      <nav class="nav-menu" id="navMenu">
        <a href="index.php" class="nav-link" data-i18n="nav.home">Accueil</a>
        <a href="services.php" class="nav-link" data-i18n="nav.services">Services</a>
        <a href="room-service.php" class="nav-link active" data-i18n="nav.roomService">Room Service</a>
        <a href="activites.php" class="nav-link" data-i18n="nav.activities">À découvrir</a>
        <a href="contact.php" class="nav-link" data-i18n="nav.contact">Contact</a>
        <button type="button" class="btn-contact-reception" id="btnContactReception" data-i18n="header.contactReception">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          Contacter la réception
        </button>
      </nav>
      <div class="menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </header>

  <!-- Page Hero -->
  <section class="page-hero" style="background-image: url('images/resto/restaurant-hotel-tresses-3.jpg');">
    <div class="page-hero-content">
      <p class="hero-subtitle" data-i18n="roomService.heroSubtitle"><?= h($hotelName) ?></p>
      <h1 class="page-hero-title" data-i18n="roomService.heroTitle">Room Service</h1>
      <p class="page-hero-subtitle" data-i18n="roomService.heroDescription">Commandez depuis votre chambre</p>
    </div>
  </section>

  <?php if ($orderSuccess): ?>
    <!-- Order Success -->
    <section class="room-service-section">
      <div class="container">
        <div class="order-success">
          <div class="order-success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <h2 data-i18n="roomService.orderConfirmed">Commande confirmée</h2>
          <p data-i18n="roomService.orderSuccessMessage">Votre commande a été enregistrée avec succès. Notre équipe va la préparer et vous la livrer dans les meilleurs délais.</p>
          <div class="order-id"><span data-i18n="roomService.orderNumber">Commande #</span><?= $orderId ?></div>
          <a href="room-service.php" class="btn btn-primary" data-i18n="roomService.newOrder">Passer une nouvelle commande</a>
        </div>
      </div>
    </section>

  <?php elseif (empty($items)): ?>
    <!-- No Items Available -->
    <section class="room-service-section">
      <div class="container">
        <div class="no-items">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
          </svg>
          <h2 data-i18n="roomService.serviceUnavailable">Service actuellement indisponible</h2>
          <p data-i18n="roomService.serviceUnavailableMessage">Le room service n'est pas disponible pour le moment. Veuillez réessayer ultérieurement ou appeler la réception au +33 5 57 34 13 95.</p>
        </div>
      </div>
    </section>

  <?php else: ?>
    <!-- Room Service Order Form -->
    <section class="room-service-section">
      <div class="container">
        <?php if ($orderError): ?>
          <div class="alert-error"><?= htmlspecialchars($orderError) ?></div>
        <?php endif; ?>

        <div class="room-service-grid">
          <!-- Menu Items -->
          <div class="menu-items">
            <?php foreach ($itemsByCategory as $categoryKey => $categoryItems): ?>
              <?php $catAvailability = getCategoryAvailabilityInfo($categoryKey); ?>
              <div class="category-section" data-category="<?= htmlspecialchars($categoryKey) ?>">
                <div class="category-header">
                  <div class="category-header-left">
                    <span class="category-toggle">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                      </svg>
                    </span>
                    <h2 class="category-title" data-i18n="roomService.categories.<?= htmlspecialchars($categoryKey) ?>"><?= htmlspecialchars($categories[$categoryKey] ?? ucfirst($categoryKey)) ?></h2>
                  </div>
                  <?php if ($catAvailability['time_start'] && $catAvailability['time_end']): ?>
                    <span class="category-availability <?= $catAvailability['available'] ? 'available' : 'unavailable' ?>">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                      </svg>
                      <?= htmlspecialchars($catAvailability['time_start']) ?> - <?= htmlspecialchars($catAvailability['time_end']) ?>
                    </span>
                  <?php else: ?>
                    <span class="category-availability always-available">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                        <polyline points="20 6 9 17 4 12"/>
                      </svg>
                      <span data-i18n="roomService.available24h">24h/24</span>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="items-grid-wrapper">
                  <div class="items-grid">
                    <?php foreach ($categoryItems as $item): ?>
                      <div class="item-card" data-item-id="<?= $item['id'] ?>" data-item-name="<?= htmlspecialchars($item['name']) ?>" data-item-price="<?= $item['price'] ?>" data-item-category="<?= htmlspecialchars($item['category'] ?? 'general') ?>">
                        <div class="item-image">
                          <?php if ($item['image']): ?>
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                          <?php else: ?>
                            <div style="height: 100%; display: flex; align-items: center; justify-content: center;">
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 48px; height: 48px; opacity: 0.3;">
                                <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                              </svg>
                            </div>
                          <?php endif; ?>
                        </div>
                        <div class="item-content">
                          <h3 class="item-name"><?= htmlspecialchars($item['name']) ?></h3>
                          <p class="item-description"><?= htmlspecialchars($item['description'] ?? '') ?></p>
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
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Cart Sidebar -->
          <div class="cart-sidebar">
            <div class="cart-header">
              <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="9" cy="21" r="1"/>
                  <circle cx="20" cy="21" r="1"/>
                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <span data-i18n="roomService.yourOrder">Votre commande</span>
              </h3>
            </div>
            <div class="cart-body" id="cartBody">
              <div class="cart-empty" id="cartEmpty" data-i18n="roomService.cartEmpty">
                Sélectionnez des articles pour commencer
              </div>
              <div id="cartItems"></div>
            </div>
            <div class="cart-footer">
              <div class="cart-total">
                <span class="cart-total-label" data-i18n="roomService.total">Total</span>
                <span class="cart-total-value" id="cartTotal">0,00 €</span>
              </div>

              <form method="POST" id="orderForm">
                <input type="hidden" name="action" value="place_order">
                <input type="hidden" name="items" id="orderItems">

                <div class="form-group">
                  <label for="room_number" data-i18n="roomService.roomNumber">Numéro de chambre *</label>
                  <input type="text" id="room_number" name="room_number" required placeholder="Ex: 101" data-i18n-placeholder="roomService.roomNumberPlaceholder">
                </div>

                <div class="form-group">
                  <label for="guest_name" data-i18n="roomService.yourName">Votre nom</label>
                  <input type="text" id="guest_name" name="guest_name" placeholder="Optionnel" data-i18n-placeholder="roomService.optionalPlaceholder">
                </div>

                <div class="form-group">
                  <label for="phone" data-i18n="roomService.phone">Téléphone</label>
                  <input type="tel" id="phone" name="phone" placeholder="Pour vous joindre si nécessaire" data-i18n-placeholder="roomService.phonePlaceholder">
                </div>

                <div class="form-group">
                  <label for="delivery_datetime" data-i18n="roomService.deliveryDateTime">Date et heure de livraison *</label>
                  <input type="datetime-local" id="delivery_datetime" name="delivery_datetime" required>
                  <small data-i18n="roomService.deliveryMinTime">Minimum 30 minutes à l'avance</small>
                </div>

                <div class="form-group">
                  <label for="payment_method" data-i18n="roomService.paymentMethod">Mode de paiement</label>
                  <select id="payment_method" name="payment_method">
                    <?php foreach ($paymentMethods as $key => $label): ?>
                      <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label for="notes" data-i18n="roomService.notes">Remarques</label>
                  <textarea id="notes" name="notes" rows="2" placeholder="Allergies, préférences..." data-i18n-placeholder="roomService.notesPlaceholder"></textarea>
                </div>

                <button type="submit" class="btn-order" id="submitBtn" disabled>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  <span data-i18n="roomService.orderButton">Commander</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo-text">
            <?= h($hotelName) ?>
            <span><?= h($logoText) ?></span>
          </div>
<?php $footerDescription = getHotelDescription(); if ($footerDescription): ?>
          <p><?= h($footerDescription) ?></p>
          <?php endif; ?>
        </div>
        <div class="footer-nav">
          <h4 class="footer-title" data-i18n="footer.navigation">Navigation</h4>
          <ul class="footer-links">
            <li><a href="index.php" data-i18n="footer.home">Accueil</a></li>
            <li><a href="services.php" data-i18n="footer.services">Services</a></li>
            <li><a href="activites.php" data-i18n="footer.discover">À découvrir</a></li>
          </ul>
        </div>
        <div class="footer-nav">
          <h4 class="footer-title" data-i18n="footer.services">Services</h4>
          <ul class="footer-links">
            <li><a href="services.php" data-i18n="footer.restaurant">Restaurant</a></li>
            <li><a href="services.php" data-i18n="footer.bar">Bar</a></li>
            <li><a href="room-service.php" data-i18n="footer.roomService">Room Service</a></li>
            <li><a href="services.php" data-i18n="footer.parking">Parking</a></li>
          </ul>
        </div>
        <div class="footer-contact">
          <h4 class="footer-title" data-i18n="footer.contactTitle">Contact</h4>
          <?php if (hasContactInfo()): ?>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <span><?= getFormattedAddress() ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($contactInfo['phone'])): ?>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            <span><?= h($contactInfo['phone']) ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($contactInfo['email'])): ?>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span><?= h($contactInfo['email']) ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="footer-bottom">
        <p data-i18n="footer.copyright">&copy; <?= date('Y') ?> <?= h($hotelName) ?>. Tous droits réservés.</p>
      </div>
    </div>
  </footer>

  <!-- Scroll to Top Button -->
  <button class="scroll-top" id="scrollTop" aria-label="Retour en haut" data-i18n-aria="common.backToTop">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="18 15 12 9 6 15"/>
    </svg>
  </button>

  <!-- Contact Reception Modal -->
  <div class="modal-overlay" id="contactReceptionModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 data-i18n="modal.contactReceptionTitle">Contacter la réception</h3>
        <button type="button" class="modal-close" id="modalClose" aria-label="Fermer">
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
          <h3 data-i18n="modal.successTitle">Message envoyé</h3>
          <p data-i18n="modal.successMessage">Votre message a bien été transmis à la réception. Nous vous répondrons dans les meilleurs délais.</p>
          <button type="button" class="btn-new-message" id="btnNewMessage" data-i18n="modal.newMessage">Envoyer un autre message</button>
        </div>
        <div id="modalFormContainer">
          <div class="modal-alert-error" id="modalError" style="display: none;"></div>
          <form method="POST" class="modal-form" id="modalMessageForm" action="contact.php">
            <input type="hidden" name="action" value="send_message">
            <input type="hidden" name="redirect_back" value="1">
            <div class="form-row">
              <div class="form-group">
                <label for="modal_room_number" data-i18n="modal.roomNumber">Numéro de chambre *</label>
                <input type="text" id="modal_room_number" name="msg_room_number" required placeholder="Ex: 101" data-i18n-placeholder="modal.roomNumberPlaceholder">
              </div>
              <div class="form-group">
                <label for="modal_guest_name" data-i18n="modal.guestName">Votre nom</label>
                <input type="text" id="modal_guest_name" name="msg_guest_name" placeholder="Optionnel" data-i18n-placeholder="modal.guestNamePlaceholder">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="modal_category" data-i18n="modal.category">Catégorie</label>
                <select id="modal_category" name="msg_category">
                  <?php foreach ($messageCategories as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="modal_subject" data-i18n="modal.subject">Objet</label>
                <input type="text" id="modal_subject" name="msg_subject" placeholder="Résumé du problème" data-i18n-placeholder="modal.subjectPlaceholder">
              </div>
            </div>
            <div class="form-group">
              <label for="modal_message" data-i18n="modal.message">Votre message *</label>
              <textarea id="modal_message" name="msg_message" required placeholder="Décrivez votre demande ou problème..." data-i18n-placeholder="modal.messagePlaceholder"></textarea>
            </div>
            <button type="submit" class="btn-submit-modal" data-i18n="modal.sendMessage">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
              </svg>
              Envoyer le message
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Dynamic content translations (for room service items and categories) -->
  <script>
    // Item translations from database
    window.itemTranslations = <?= json_encode($allItemTranslations) ?>;
    // Category translations from database
    window.categoryTranslations = <?= json_encode($allCategoryTranslations) ?>;
    // Default language
    window.defaultLang = '<?= getDefaultLanguage() ?>';
  </script>

  <script src="js/translations.js"></script>
  <script src="js/i18n.js"></script>
  <script src="js/animations.js"></script>
  <script>
    // Cart management
    const cart = {};

    function formatPrice(price) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(price);
    }

    function updateCart() {
      const cartItems = document.getElementById('cartItems');
      const cartEmpty = document.getElementById('cartEmpty');
      const cartTotal = document.getElementById('cartTotal');
      const orderItems = document.getElementById('orderItems');
      const submitBtn = document.getElementById('submitBtn');

      let total = 0;
      let html = '';
      const itemsArray = [];

      for (const [id, item] of Object.entries(cart)) {
        if (item.quantity > 0) {
          const subtotal = item.price * item.quantity;
          total += subtotal;
          html += `
            <div class="cart-item">
              <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-qty">${item.quantity} x ${formatPrice(item.price)}</div>
              </div>
              <span class="cart-item-subtotal">${formatPrice(subtotal)}</span>
              <button type="button" class="cart-item-remove" onclick="removeFromCart(${id})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          `;
          itemsArray.push({
            id: parseInt(id),
            quantity: item.quantity
          });
        }
      }

      cartItems.innerHTML = html;
      cartEmpty.style.display = html ? 'none' : 'block';
      cartTotal.textContent = formatPrice(total);
      orderItems.value = JSON.stringify(itemsArray);
      submitBtn.disabled = itemsArray.length === 0;
    }

    function removeFromCart(id) {
      if (cart[id]) {
        cart[id].quantity = 0;
        const card = document.querySelector(`[data-item-id="${id}"]`);
        if (card) {
          card.querySelector('.quantity-value').textContent = '0';
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

      plusBtn.addEventListener('click', () => {
        cart[id].quantity++;
        quantityEl.textContent = cart[id].quantity;
        minusBtn.disabled = false;
        updateCart();
      });

      minusBtn.addEventListener('click', () => {
        if (cart[id].quantity > 0) {
          cart[id].quantity--;
          quantityEl.textContent = cart[id].quantity;
          minusBtn.disabled = cart[id].quantity === 0;
          updateCart();
        }
      });
    });

    // Set minimum delivery datetime (30 minutes from now)
    function updateMinDeliveryTime() {
      const deliveryInput = document.getElementById('delivery_datetime');
      if (deliveryInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() + 30);
        // Round up to next 15 minutes
        const minutes = now.getMinutes();
        const roundedMinutes = Math.ceil(minutes / 15) * 15;
        now.setMinutes(roundedMinutes);
        now.setSeconds(0);

        // Format for datetime-local input (YYYY-MM-DDTHH:MM)
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');

        const minDatetime = `${year}-${month}-${day}T${hours}:${mins}`;
        deliveryInput.min = minDatetime;

        // Set max to 7 days from now
        const maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 7);
        const maxYear = maxDate.getFullYear();
        const maxMonth = String(maxDate.getMonth() + 1).padStart(2, '0');
        const maxDay = String(maxDate.getDate()).padStart(2, '0');
        deliveryInput.max = `${maxYear}-${maxMonth}-${maxDay}T23:59`;

        // Set default value to minimum time if empty
        if (!deliveryInput.value) {
          deliveryInput.value = minDatetime;
        }
      }
    }
    updateMinDeliveryTime();
    // Update every minute
    setInterval(updateMinDeliveryTime, 60000);

    // Form validation
    document.getElementById('orderForm')?.addEventListener('submit', (e) => {
      const items = JSON.parse(document.getElementById('orderItems').value);
      if (items.length === 0) {
        e.preventDefault();
        alert(window.I18n ? window.I18n.t('roomService.errorSelectItem') : 'Veuillez sélectionner au moins un article.');
        return false;
      }
      const roomNumber = document.getElementById('room_number').value.trim();
      if (!roomNumber) {
        e.preventDefault();
        alert(window.I18n ? window.I18n.t('roomService.errorRoomNumber') : 'Veuillez indiquer votre numéro de chambre.');
        return false;
      }
      const deliveryDatetime = document.getElementById('delivery_datetime').value;
      if (!deliveryDatetime) {
        e.preventDefault();
        alert(window.I18n ? window.I18n.t('roomService.errorDeliveryTime') : 'Veuillez indiquer la date et heure de livraison.');
        return false;
      }
      // Check if delivery time is in the future
      const deliveryTime = new Date(deliveryDatetime);
      const minTime = new Date();
      minTime.setMinutes(minTime.getMinutes() + 25); // 25 min buffer for form submission
      if (deliveryTime < minTime) {
        e.preventDefault();
        alert(window.I18n ? window.I18n.t('roomService.errorMinDeliveryTime') : 'La livraison doit être prévue au moins 30 minutes à l\'avance.');
        return false;
      }
    });

    // Category accordion toggle
    document.querySelectorAll('.category-header').forEach(header => {
      header.addEventListener('click', () => {
        const section = header.closest('.category-section');
        section.classList.toggle('expanded');
      });
    });

    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');

    menuToggle?.addEventListener('click', () => {
      menuToggle.classList.toggle('active');
      navMenu.classList.toggle('active');
    });

    // Header scroll effect
    const header = document.getElementById('header');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 100) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });

    // Scroll to top button
    const scrollTop = document.getElementById('scrollTop');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 500) {
        scrollTop.classList.add('visible');
      } else {
        scrollTop.classList.remove('visible');
      }
    });

    scrollTop?.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Contact Reception Modal
    const modal = document.getElementById('contactReceptionModal');
    const btnOpenModal = document.getElementById('btnContactReception');
    const btnCloseModal = document.getElementById('modalClose');
    const modalForm = document.getElementById('modalMessageForm');
    const modalSuccess = document.getElementById('modalSuccess');
    const modalFormContainer = document.getElementById('modalFormContainer');
    const modalError = document.getElementById('modalError');
    const btnNewMessage = document.getElementById('btnNewMessage');

    function openModal() {
      modal.classList.add('active');
      document.body.classList.add('modal-open');
      menuToggle?.classList.remove('active');
      navMenu?.classList.remove('active');
    }

    function closeModal() {
      modal.classList.remove('active');
      document.body.classList.remove('modal-open');
    }

    function resetModalForm() {
      modalForm.reset();
      modalError.style.display = 'none';
      modalSuccess.style.display = 'none';
      modalFormContainer.style.display = 'block';
    }

    btnOpenModal?.addEventListener('click', openModal);
    btnCloseModal?.addEventListener('click', closeModal);
    btnNewMessage?.addEventListener('click', resetModalForm);

    modal?.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal?.classList.contains('active')) closeModal();
    });

    modalForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(modalForm);
      modalError.style.display = 'none';

      try {
        const response = await fetch('contact.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
          modalFormContainer.style.display = 'none';
          modalSuccess.style.display = 'block';
        } else {
          modalError.textContent = data.error || (window.I18n ? window.I18n.t('modal.errorGeneric') : 'Une erreur est survenue. Veuillez réessayer.');
          modalError.style.display = 'block';
        }
      } catch (error) {
        modalError.textContent = window.I18n ? window.I18n.t('modal.errorGeneric') : 'Une erreur est survenue. Veuillez réessayer.';
        modalError.style.display = 'block';
      }
    });

    // ===========================================
    // Dynamic Content Translation (Items & Categories)
    // ===========================================

    /**
     * Get translation for an item based on current language
     */
    function getItemTranslation(itemId, field, fallback) {
      const lang = window.I18n ? window.I18n.currentLang : window.defaultLang;
      const translations = window.itemTranslations[itemId];

      if (translations) {
        // Try current language
        if (translations[lang] && translations[lang][field]) {
          return translations[lang][field];
        }
        // Fallback to default language
        if (translations[window.defaultLang] && translations[window.defaultLang][field]) {
          return translations[window.defaultLang][field];
        }
      }

      return fallback;
    }

    /**
     * Get translation for a category based on current language
     */
    function getCategoryTranslation(categoryCode, fallback) {
      const lang = window.I18n ? window.I18n.currentLang : window.defaultLang;
      const translations = window.categoryTranslations[categoryCode];

      if (translations) {
        // Try current language
        if (translations[lang]) {
          return translations[lang];
        }
        // Fallback to default language
        if (translations[window.defaultLang]) {
          return translations[window.defaultLang];
        }
      }

      return fallback;
    }

    /**
     * Update all dynamic content (items & categories) for current language
     */
    function updateDynamicTranslations() {
      // Update item names
      document.querySelectorAll('.item-card').forEach(card => {
        const itemId = card.dataset.itemId;
        const nameEl = card.querySelector('.item-name');
        const descEl = card.querySelector('.item-description');

        if (nameEl) {
          const originalName = nameEl.dataset.originalName || nameEl.textContent;
          nameEl.dataset.originalName = originalName;
          nameEl.textContent = getItemTranslation(itemId, 'name', originalName);
        }

        if (descEl) {
          const originalDesc = descEl.dataset.originalDesc || descEl.textContent;
          descEl.dataset.originalDesc = originalDesc;
          const translated = getItemTranslation(itemId, 'description', originalDesc);
          descEl.textContent = translated || '';
        }
      });

      // Update category titles (those not handled by data-i18n)
      document.querySelectorAll('.category-title').forEach(title => {
        const section = title.closest('.category-section');
        if (section) {
          const categoryCode = section.dataset.category;
          if (categoryCode && window.categoryTranslations[categoryCode]) {
            const originalName = title.dataset.originalName || title.textContent;
            title.dataset.originalName = originalName;
            title.textContent = getCategoryTranslation(categoryCode, originalName);
          }
        }
      });

      // Update cart items if any
      updateCart();
    }

    // Override the original updateCart to use translated names
    const originalUpdateCart = updateCart;
    updateCart = function() {
      const cartItems = document.getElementById('cartItems');
      const cartEmpty = document.getElementById('cartEmpty');
      const cartTotal = document.getElementById('cartTotal');
      const orderItems = document.getElementById('orderItems');
      const submitBtn = document.getElementById('submitBtn');

      let total = 0;
      let html = '';
      const itemsArray = [];

      for (const [id, item] of Object.entries(cart)) {
        if (item.quantity > 0) {
          const subtotal = item.price * item.quantity;
          total += subtotal;
          // Use translated name
          const translatedName = getItemTranslation(id, 'name', item.name);
          html += `
            <div class="cart-item">
              <div class="cart-item-info">
                <div class="cart-item-name">${translatedName}</div>
                <div class="cart-item-qty">${item.quantity} x ${formatPrice(item.price)}</div>
              </div>
              <span class="cart-item-subtotal">${formatPrice(subtotal)}</span>
              <button type="button" class="cart-item-remove" onclick="removeFromCart(${id})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          `;
          itemsArray.push({
            id: parseInt(id),
            quantity: item.quantity
          });
        }
      }

      cartItems.innerHTML = html;
      cartEmpty.style.display = html ? 'none' : 'block';
      cartTotal.textContent = formatPrice(total);
      orderItems.value = JSON.stringify(itemsArray);
      submitBtn.disabled = itemsArray.length === 0;
    };

    // Listen for language changes and update dynamic content
    // The I18n system stores original switchLanguage, we need to extend it
    if (window.I18n) {
      const originalSwitchLanguage = window.I18n.switchLanguage.bind(window.I18n);
      window.I18n.switchLanguage = function(lang) {
        originalSwitchLanguage(lang);
        // Update dynamic content after I18n has updated
        setTimeout(updateDynamicTranslations, 50);
      };
    }

    // Initial update of dynamic translations on page load
    setTimeout(updateDynamicTranslations, 100);
  </script>
</body>
</html>
