<?php
/**
 * Room Service Page - Hôtel Corintel
 * Public page for guests to order room service
 */

require_once __DIR__ . '/includes/functions.php';

// Get active items
$items = getRoomServiceItems(true);
$categories = getRoomServiceCategories();
$paymentMethods = getRoomServicePaymentMethods();

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

// Handle message submission
$messageSuccess = false;
$messageError = '';
$messageCategories = getGuestMessageCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $msgRoomNumber = trim($_POST['msg_room_number'] ?? '');
    $msgGuestName = trim($_POST['msg_guest_name'] ?? '');
    $msgCategory = $_POST['msg_category'] ?? 'general';
    $msgSubject = trim($_POST['msg_subject'] ?? '');
    $msgMessage = trim($_POST['msg_message'] ?? '');

    // Validation
    if (empty($msgRoomNumber)) {
        $messageError = 'Veuillez indiquer votre numéro de chambre.';
    } elseif (empty($msgMessage)) {
        $messageError = 'Veuillez écrire votre message.';
    } elseif (strlen($msgMessage) > 2000) {
        $messageError = 'Le message est trop long (max. 2000 caractères).';
    } elseif (!array_key_exists($msgCategory, $messageCategories)) {
        $messageError = 'Catégorie invalide.';
    } else {
        $msgId = createGuestMessage([
            'room_number' => $msgRoomNumber,
            'guest_name' => $msgGuestName,
            'category' => $msgCategory,
            'subject' => $msgSubject,
            'message' => $msgMessage
        ]);

        if ($msgId) {
            $messageSuccess = true;
        } else {
            $messageError = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Commandez votre room service directement depuis votre chambre. Hôtel Corintel, Bordeaux Est.">
  <title>Room Service | Hôtel Corintel - Bordeaux Est</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
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
    /* Guest Message Section */
    .guest-message-section {
      padding: 4rem 0;
      background: var(--color-white);
    }
    .guest-message-section h2 {
      text-align: center;
      margin-bottom: 0.5rem;
    }
    .guest-message-section .section-subtitle {
      text-align: center;
      color: var(--color-text-light);
      max-width: 600px;
      margin: 0 auto 2rem;
    }
    .message-form-container {
      max-width: 600px;
      margin: 0 auto;
      background: var(--color-cream);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: var(--shadow-soft);
    }
    .message-form .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    @media (max-width: 600px) {
      .message-form .form-row {
        grid-template-columns: 1fr;
      }
    }
    .message-form .form-group {
      margin-bottom: 1rem;
    }
    .message-form label {
      display: block;
      font-weight: 500;
      margin-bottom: 0.375rem;
      font-size: 0.9rem;
    }
    .message-form input,
    .message-form select,
    .message-form textarea {
      width: 100%;
      padding: 0.625rem 0.875rem;
      border: 1px solid var(--color-beige);
      border-radius: 6px;
      font-size: 0.9rem;
      font-family: var(--font-body);
      transition: var(--transition);
      background: var(--color-white);
    }
    .message-form input:focus,
    .message-form select:focus,
    .message-form textarea:focus {
      outline: none;
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.1);
    }
    .message-form textarea {
      min-height: 120px;
      resize: vertical;
    }
    .btn-message {
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
    .btn-message:hover {
      background: var(--color-primary-dark);
    }
    .btn-message svg {
      width: 20px;
      height: 20px;
    }
    .message-success {
      text-align: center;
      padding: 2rem;
    }
    .message-success-icon {
      width: 60px;
      height: 60px;
      background: rgba(72, 187, 120, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
    }
    .message-success-icon svg {
      width: 30px;
      height: 30px;
      color: #48BB78;
    }
    .message-success h3 {
      margin-bottom: 0.5rem;
    }
    .message-success p {
      color: var(--color-text-light);
      margin-bottom: 1rem;
    }
    .alert-message-error {
      background: rgba(245, 101, 101, 0.1);
      color: #C53030;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      border: 1px solid rgba(245, 101, 101, 0.3);
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
          Hôtel Corintel
          <span>Bordeaux Est</span>
        </div>
      </a>
      <nav class="nav-menu" id="navMenu">
        <a href="index.php" class="nav-link" data-i18n="nav.home">Accueil</a>
        <a href="services.php" class="nav-link" data-i18n="nav.services">Services</a>
        <a href="room-service.php" class="nav-link active" data-i18n="nav.roomService">Room Service</a>
        <a href="activites.php" class="nav-link" data-i18n="nav.activities">À découvrir</a>
        <a href="contact.php" class="nav-link" data-i18n="nav.contact">Contact</a>
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
      <p class="hero-subtitle">Hôtel Corintel</p>
      <h1 class="page-hero-title">Room Service</h1>
      <p class="page-hero-subtitle">Commandez depuis votre chambre</p>
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
          <h2>Commande confirmée</h2>
          <p>Votre commande a été enregistrée avec succès. Notre équipe va la préparer et vous la livrer dans les meilleurs délais.</p>
          <div class="order-id">Commande #<?= $orderId ?></div>
          <a href="room-service.php" class="btn btn-primary">Passer une nouvelle commande</a>
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
          <h2>Service actuellement indisponible</h2>
          <p>Le room service n'est pas disponible pour le moment. Veuillez réessayer ultérieurement ou appeler la réception au +33 5 57 34 13 95.</p>
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
                    <h2 class="category-title"><?= htmlspecialchars($categories[$categoryKey] ?? ucfirst($categoryKey)) ?></h2>
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
                      24h/24
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
                Votre commande
              </h3>
            </div>
            <div class="cart-body" id="cartBody">
              <div class="cart-empty" id="cartEmpty">
                Sélectionnez des articles pour commencer
              </div>
              <div id="cartItems"></div>
            </div>
            <div class="cart-footer">
              <div class="cart-total">
                <span class="cart-total-label">Total</span>
                <span class="cart-total-value" id="cartTotal">0,00 €</span>
              </div>

              <form method="POST" id="orderForm">
                <input type="hidden" name="action" value="place_order">
                <input type="hidden" name="items" id="orderItems">

                <div class="form-group">
                  <label for="room_number">Numéro de chambre *</label>
                  <input type="text" id="room_number" name="room_number" required placeholder="Ex: 101">
                </div>

                <div class="form-group">
                  <label for="guest_name">Votre nom</label>
                  <input type="text" id="guest_name" name="guest_name" placeholder="Optionnel">
                </div>

                <div class="form-group">
                  <label for="phone">Téléphone</label>
                  <input type="tel" id="phone" name="phone" placeholder="Pour vous joindre si nécessaire">
                </div>

                <div class="form-group">
                  <label for="delivery_datetime">Date et heure de livraison *</label>
                  <input type="datetime-local" id="delivery_datetime" name="delivery_datetime" required>
                  <small>Minimum 30 minutes à l'avance</small>
                </div>

                <div class="form-group">
                  <label for="payment_method">Mode de paiement</label>
                  <select id="payment_method" name="payment_method">
                    <?php foreach ($paymentMethods as $key => $label): ?>
                      <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label for="notes">Remarques</label>
                  <textarea id="notes" name="notes" rows="2" placeholder="Allergies, préférences..."></textarea>
                </div>

                <button type="submit" class="btn-order" id="submitBtn" disabled>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  Commander
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- Guest Message Section -->
  <section class="guest-message-section" id="contact-reception">
    <div class="container">
      <h2>Contacter la réception</h2>
      <p class="section-subtitle">Un problème dans votre chambre ? Une question ? Envoyez-nous un message et nous vous répondrons dans les plus brefs délais.</p>

      <div class="message-form-container">
        <div class="message-success" id="messageSuccess" style="<?= $messageSuccess ? '' : 'display: none;' ?>">
          <div class="message-success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <h3>Message envoyé</h3>
          <p>Votre message a bien été transmis à la réception. Nous vous répondrons dans les meilleurs délais.</p>
          <button type="button" class="btn btn-primary" id="newMessageBtn">Envoyer un autre message</button>
        </div>

        <div id="messageFormContainer" style="<?= $messageSuccess ? 'display: none;' : '' ?>">
          <?php if ($messageError): ?>
            <div class="alert-message-error"><?= htmlspecialchars($messageError) ?></div>
          <?php endif; ?>

          <form method="POST" class="message-form" id="messageForm">
            <input type="hidden" name="action" value="send_message">

            <div class="form-row">
              <div class="form-group">
                <label for="msg_room_number">Numéro de chambre *</label>
                <input type="text" id="msg_room_number" name="msg_room_number" required placeholder="Ex: 101" value="<?= htmlspecialchars($_POST['msg_room_number'] ?? '') ?>">
              </div>

              <div class="form-group">
                <label for="msg_guest_name">Votre nom</label>
                <input type="text" id="msg_guest_name" name="msg_guest_name" placeholder="Optionnel" value="<?= htmlspecialchars($_POST['msg_guest_name'] ?? '') ?>">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="msg_category">Catégorie</label>
                <select id="msg_category" name="msg_category">
                  <?php foreach ($messageCategories as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= (($_POST['msg_category'] ?? '') === $key) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($label) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="msg_subject">Objet</label>
                <input type="text" id="msg_subject" name="msg_subject" placeholder="Résumé du problème" value="<?= htmlspecialchars($_POST['msg_subject'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group">
              <label for="msg_message">Votre message *</label>
              <textarea id="msg_message" name="msg_message" required placeholder="Décrivez votre demande ou problème..."><?= htmlspecialchars($_POST['msg_message'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-message">
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
  </section>

  <script>
  document.getElementById('newMessageBtn')?.addEventListener('click', function() {
    document.getElementById('messageSuccess').style.display = 'none';
    document.getElementById('messageFormContainer').style.display = 'block';
    document.getElementById('messageForm').reset();
    document.getElementById('msg_room_number').focus();
  });
  </script>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo-text">
            Hôtel Corintel
            <span>Bordeaux Est</span>
          </div>
          <p>Un havre de paix aux portes de Bordeaux, où charme et authenticité vous attendent pour un séjour inoubliable.</p>
        </div>
        <div class="footer-nav">
          <h4 class="footer-title">Navigation</h4>
          <ul class="footer-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="activites.php">À découvrir</a></li>
          </ul>
        </div>
        <div class="footer-nav">
          <h4 class="footer-title">Services</h4>
          <ul class="footer-links">
            <li><a href="services.php#restaurant">Restaurant</a></li>
            <li><a href="services.php#bar">Bar</a></li>
            <li><a href="room-service.php">Room Service</a></li>
            <li><a href="services.php#parking">Parking</a></li>
          </ul>
        </div>
        <div class="footer-contact">
          <h4 class="footer-title">Contact</h4>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <span>14 Avenue du Périgord. 33370 TRESSES<br>Gironde, France</span>
          </div>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            <span>+33 5 57 34 13 95</span>
          </div>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span>hotel.bordeaux.tresses@gmail.com</span>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2024 Hôtel Corintel. Tous droits réservés.</p>
      </div>
    </div>
  </footer>

  <!-- Scroll to Top Button -->
  <button class="scroll-top" id="scrollTop" aria-label="Retour en haut">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="18 15 12 9 6 15"/>
    </svg>
  </button>

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
        alert('Veuillez sélectionner au moins un article.');
        return false;
      }
      const roomNumber = document.getElementById('room_number').value.trim();
      if (!roomNumber) {
        e.preventDefault();
        alert('Veuillez indiquer votre numéro de chambre.');
        return false;
      }
      const deliveryDatetime = document.getElementById('delivery_datetime').value;
      if (!deliveryDatetime) {
        e.preventDefault();
        alert('Veuillez indiquer la date et heure de livraison.');
        return false;
      }
      // Check if delivery time is in the future
      const deliveryTime = new Date(deliveryDatetime);
      const minTime = new Date();
      minTime.setMinutes(minTime.getMinutes() + 25); // 25 min buffer for form submission
      if (deliveryTime < minTime) {
        e.preventDefault();
        alert('La livraison doit être prévue au moins 30 minutes à l\'avance.');
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
  </script>
</body>
</html>
