<?php
/**
 * Room Service Items Management
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$csrfToken = generateCsrfToken();
$hotelName = getHotelName();
$categories = getRoomServiceCategories();

// Handle POST requests
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $category = $_POST['category'] ?? 'general';
                $position = intval($_POST['position'] ?? 0);

                if (empty($name)) {
                    $message = 'Le nom est obligatoire.';
                    $messageType = 'error';
                } elseif ($price <= 0) {
                    $message = 'Le prix doit être supérieur à 0.';
                    $messageType = 'error';
                } else {
                    $itemId = createRoomServiceItem([
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'category' => $category,
                        'position' => $position,
                        'is_active' => 1
                    ]);
                    if ($itemId) {
                        // Save translations
                        $translations = [];
                        foreach (getSupportedLanguages() as $lang) {
                            $transName = trim($_POST["name_$lang"] ?? '');
                            $transDesc = trim($_POST["description_$lang"] ?? '');
                            if (!empty($transName)) {
                                $translations[$lang] = [
                                    'name' => $transName,
                                    'description' => $transDesc
                                ];
                            }
                        }
                        // Use main fields as French translation if not provided
                        if (empty($translations['fr']['name'])) {
                            $translations['fr'] = [
                                'name' => $name,
                                'description' => $description
                            ];
                        }
                        saveItemTranslations($itemId, $translations);

                        // Handle image upload if provided
                        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                            handleRoomServiceItemImageUpload($_FILES['image'], $itemId);
                        }
                        $message = 'Article créé avec succès.';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de la création.';
                        $messageType = 'error';
                    }
                }
                break;

            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $category = $_POST['category'] ?? 'general';
                $position = intval($_POST['position'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                $item = getRoomServiceItemById($id);
                if (!$item) {
                    $message = 'Article non trouvé.';
                    $messageType = 'error';
                } elseif (empty($name)) {
                    $message = 'Le nom est obligatoire.';
                    $messageType = 'error';
                } elseif ($price <= 0) {
                    $message = 'Le prix doit être supérieur à 0.';
                    $messageType = 'error';
                } else {
                    $success = updateRoomServiceItem($id, [
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'image' => $item['image'],
                        'category' => $category,
                        'position' => $position,
                        'is_active' => $isActive
                    ]);
                    if ($success) {
                        // Save translations
                        $translations = [];
                        foreach (getSupportedLanguages() as $lang) {
                            $transName = trim($_POST["name_$lang"] ?? '');
                            $transDesc = trim($_POST["description_$lang"] ?? '');
                            if (!empty($transName)) {
                                $translations[$lang] = [
                                    'name' => $transName,
                                    'description' => $transDesc
                                ];
                            }
                        }
                        // Use main fields as French translation if not provided
                        if (empty($translations['fr']['name'])) {
                            $translations['fr'] = [
                                'name' => $name,
                                'description' => $description
                            ];
                        }
                        saveItemTranslations($id, $translations);

                        $message = 'Article mis à jour.';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de la mise à jour.';
                        $messageType = 'error';
                    }
                }
                break;

            case 'upload_image':
                $id = intval($_POST['id'] ?? 0);
                if (!isset($_FILES['image'])) {
                    $message = 'Aucun fichier reçu.';
                    $messageType = 'error';
                } else {
                    $result = handleRoomServiceItemImageUpload($_FILES['image'], $id);
                    $message = $result['message'];
                    $messageType = $result['valid'] ? 'success' : 'error';
                }
                break;

            case 'toggle':
                $id = intval($_POST['id'] ?? 0);
                if (toggleRoomServiceItemStatus($id)) {
                    $message = 'Statut mis à jour.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if (deleteRoomServiceItem($id)) {
                    $message = 'Article supprimé.';
                    $messageType = 'success';
                } else {
                    $message = 'Impossible de supprimer cet article (utilisé dans des commandes).';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get all items with their translations
$items = getRoomServiceItems();
$stats = getRoomServiceStats();

// Prepare items with translations for JavaScript
$itemsWithTranslations = [];
foreach ($items as $item) {
    $item['translations'] = getItemTranslations($item['id']);
    $itemsWithTranslations[$item['id']] = $item;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Room Service - Articles | Admin <?= h($hotelName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th,
        .items-table td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--admin-border);
        }
        .items-table th {
            font-weight: 600;
            color: var(--admin-text-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table tr:hover {
            background: var(--admin-bg);
        }
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--admin-bg);
        }
        .item-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .item-details h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .item-details p {
            font-size: 0.8125rem;
            color: var(--admin-text-light);
            margin: 0;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .badge-active {
            background: rgba(72, 187, 120, 0.1);
            color: #276749;
        }
        .badge-inactive {
            background: rgba(245, 101, 101, 0.1);
            color: #C53030;
        }
        .price {
            font-weight: 600;
            color: var(--admin-primary);
        }
        .price-ttc {
            font-weight: 600;
            color: var(--admin-primary);
        }
        .price-ht {
            font-size: 0.8rem;
            color: var(--admin-text-light);
            margin-top: 2px;
        }
        .price-cell {
            white-space: nowrap;
        }
        .price-input-group {
            position: relative;
        }
        .price-ht-display {
            font-size: 0.85rem;
            color: var(--admin-text-light);
            margin-top: 0.25rem;
            padding: 0.5rem 0.75rem;
            background: var(--admin-bg);
            border-radius: 4px;
        }
        .vat-badge {
            display: inline-block;
            font-size: 0.7rem;
            padding: 2px 6px;
            background: rgba(66, 153, 225, 0.1);
            color: #2B6CB0;
            border-radius: 3px;
            margin-left: 0.5rem;
        }
        .actions-cell {
            display: flex;
            gap: 0.5rem;
        }
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-icon svg {
            width: 16px;
            height: 16px;
        }
        .btn-success {
            background: var(--admin-success);
            color: #fff;
        }
        .btn-success:hover {
            background: #38a169;
            color: #fff;
        }
        .btn-danger {
            background: var(--admin-error);
            color: #fff;
        }
        .btn-danger:hover {
            background: #e53e3e;
            color: #fff;
        }
        .modal-lg {
            max-width: 700px;
        }
        /* Language Tabs */
        .lang-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--admin-border);
            margin-bottom: 1.5rem;
        }
        .lang-tab {
            padding: 0.75rem 1.25rem;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--admin-text-light);
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s ease;
        }
        .lang-tab:hover {
            color: var(--admin-primary);
        }
        .lang-tab.active {
            color: var(--admin-primary);
            border-bottom-color: var(--admin-primary);
        }
        .lang-tab .flag {
            margin-right: 0.5rem;
        }
        .lang-content {
            display: none;
        }
        .lang-content.active {
            display: block;
        }
        .translation-note {
            background: rgba(66, 153, 225, 0.1);
            border-left: 3px solid #4299E1;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 0 4px 4px 0;
            font-size: 0.8125rem;
            color: var(--admin-text);
        }
        .translation-note strong {
            color: #2B6CB0;
        }
        /* Collapsible Categories */
        .category-group {
            margin-bottom: 0.5rem;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            overflow: hidden;
        }
        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            background: var(--admin-bg);
            cursor: pointer;
            user-select: none;
            transition: background 0.2s ease;
        }
        .category-header:hover {
            background: #e8e4de;
        }
        .category-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .category-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--admin-text);
        }
        .category-header .badge {
            font-size: 0.75rem;
        }
        .category-toggle {
            width: 24px;
            height: 24px;
            color: var(--admin-text-light);
            transition: transform 0.3s ease;
        }
        .category-header.expanded .category-toggle {
            transform: rotate(180deg);
        }
        .category-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .category-content.expanded {
            max-height: 2000px;
            transition: max-height 0.5s ease-in;
        }
        .category-content .items-table {
            border-top: 1px solid var(--admin-border);
        }
        .category-content .items-table tr:last-child td {
            border-bottom: none;
        }
        .empty-category {
            padding: 1.5rem;
            text-align: center;
            color: var(--admin-text-light);
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <button class="sidebar-close" id="sidebarClose" aria-label="Fermer le menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <div class="sidebar-header">
                <h2><?= h($hotelName) ?></h2>
                <span>Administration</span>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Tableau de bord
                </a>
                <a href="content.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="9" y1="21" x2="9" y2="9"/>
                    </svg>
                    Gestion du contenu
                </a>
                <a href="room-service-stats.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Statistiques
                </a>
                <a href="room-service-items.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                        <line x1="6" y1="1" x2="6" y2="4"/>
                        <line x1="10" y1="1" x2="10" y2="4"/>
                        <line x1="14" y1="1" x2="14" y2="4"/>
                    </svg>
                    Room Service - Articles
                </a>
                <a href="room-service-categories.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Room Service - Catégories
                </a>
                <a href="room-service-orders.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    Room Service - Commandes
                    <?php if ($pendingOrders > 0): ?>
                        <span class="badge" style="background: #E53E3E; color: white; margin-left: auto;"><?= $pendingOrders ?></span>
                    <?php endif; ?>
                </a>
                <a href="room-service-messages.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages Clients
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge" style="background: #E53E3E; color: white; margin-left: auto;"><?= $unreadMessages ?></span>
                    <?php endif; ?>
                </a>
                <a href="theme.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a10 10 0 0 0 0 20"/>
                        <path d="M12 2c-2.5 2.5-4 6-4 10s1.5 7.5 4 10"/>
                    </svg>
                    Thème du site
                </a>
                <a href="settings.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Paramètres
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-name"><?= h($admin['username']) ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Déconnexion
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Ouvrir le menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <h1>Room Service - Articles</h1>
                <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Ajouter un article
                </button>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= h($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['total_items'] ?></span>
                            <span class="stat-label">Articles total</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['active_items'] ?></span>
                            <span class="stat-label">Articles actifs</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['today_orders'] ?></span>
                            <span class="stat-label">Commandes aujourd'hui</span>
                        </div>
                    </div>
                </div>

                <!-- Items by Category -->
                <div class="card">
                    <div class="card-header">
                        <h2>Liste des articles</h2>
                        <span class="badge"><?= count($items) ?> articles</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($items)): ?>
                            <p class="empty-state">Aucun article. Cliquez sur "Ajouter un article" pour commencer.</p>
                        <?php else: ?>
                            <?php
                            // Group items by category
                            $itemsByCategory = [];
                            foreach ($items as $item) {
                                $cat = $item['category'] ?? 'general';
                                if (!isset($itemsByCategory[$cat])) {
                                    $itemsByCategory[$cat] = [];
                                }
                                $itemsByCategory[$cat][] = $item;
                            }
                            ?>
                            <?php foreach ($categories as $catKey => $catLabel): ?>
                                <?php $categoryItems = $itemsByCategory[$catKey] ?? []; ?>
                                <div class="category-group">
                                    <div class="category-header" onclick="toggleCategory(this)">
                                        <div class="category-header-left">
                                            <h3><?= h($catLabel) ?></h3>
                                            <span class="badge"><?= count($categoryItems) ?> article<?= count($categoryItems) > 1 ? 's' : '' ?></span>
                                        </div>
                                        <svg class="category-toggle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 12 15 18 9"/>
                                        </svg>
                                    </div>
                                    <div class="category-content">
                                        <?php if (empty($categoryItems)): ?>
                                            <p class="empty-category">Aucun article dans cette catégorie</p>
                                        <?php else: ?>
                                            <table class="items-table">
                                                <thead>
                                                    <tr>
                                                        <th>Article</th>
                                                        <th>Prix TTC / HT</th>
                                                        <th>Position</th>
                                                        <th>Statut</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($categoryItems as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="item-info">
                                                                    <?php if ($item['image']): ?>
                                                                        <img src="../<?= h($item['image']) ?>" alt="<?= h($item['name']) ?>" class="item-image">
                                                                    <?php else: ?>
                                                                        <div class="item-image" style="display: flex; align-items: center; justify-content: center;">
                                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px; color: var(--admin-text-light);">
                                                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                                                <polyline points="21 15 16 10 5 21"/>
                                                                            </svg>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="item-details">
                                                                        <h4><?= h($item['name']) ?></h4>
                                                                        <p><?= h($item['description'] ?? '') ?></p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="price-cell">
                                                                <?php
                                                                $vatRate = getCategoryVatRate($catKey);
                                                                $priceHT = calculatePriceHT($item['price'], $vatRate);
                                                                ?>
                                                                <div class="price-ttc"><?= number_format($item['price'], 2, ',', ' ') ?> € TTC</div>
                                                                <div class="price-ht"><?= number_format($priceHT, 2, ',', ' ') ?> € HT</div>
                                                            </td>
                                                            <td><?= $item['position'] ?></td>
                                                            <td>
                                                                <span class="badge <?= $item['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                                                    <?= $item['is_active'] ? 'Actif' : 'Inactif' ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="actions-cell">
                                                                    <button type="button" class="btn btn-sm btn-outline btn-icon" onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)" title="Modifier">
                                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                                        </svg>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline btn-icon" onclick="openImageModal(<?= $item['id'] ?>)" title="Changer l'image">
                                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                                            <polyline points="21 15 16 10 5 21"/>
                                                                        </svg>
                                                                    </button>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                                        <input type="hidden" name="action" value="toggle">
                                                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                                        <button type="submit" class="btn btn-sm <?= $item['is_active'] ? 'btn-outline' : 'btn-success' ?> btn-icon" title="<?= $item['is_active'] ? 'Désactiver' : 'Activer' ?>">
                                                                            <?php if ($item['is_active']): ?>
                                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                                    <path d="M18.36 6.64a9 9 0 1 1-12.73 0"/>
                                                                                    <line x1="12" y1="2" x2="12" y2="12"/>
                                                                                </svg>
                                                                            <?php else: ?>
                                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                                    <polyline points="20 6 9 17 4 12"/>
                                                                                </svg>
                                                                            <?php endif; ?>
                                                                        </button>
                                                                    </form>
                                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?');">
                                                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                                        <input type="hidden" name="action" value="delete">
                                                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                                        <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Supprimer">
                                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                                <polyline points="3 6 5 6 21 6"/>
                                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                                            </svg>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Ajouter un article</h3>
                <button type="button" class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <div class="lang-tabs" id="createLangTabs">
                        <button type="button" class="lang-tab active" data-lang="fr" onclick="switchCreateTab('fr')">
                            <span class="flag">🇫🇷</span> Français
                        </button>
                        <button type="button" class="lang-tab" data-lang="en" onclick="switchCreateTab('en')">
                            <span class="flag">🇬🇧</span> English
                        </button>
                        <button type="button" class="lang-tab" data-lang="es" onclick="switchCreateTab('es')">
                            <span class="flag">🇪🇸</span> Español
                        </button>
                        <button type="button" class="lang-tab" data-lang="it" onclick="switchCreateTab('it')">
                            <span class="flag">🇮🇹</span> Italiano
                        </button>
                    </div>

                    <!-- French (default) -->
                    <div class="lang-content active" id="createLang-fr">
                        <div class="form-group">
                            <label for="createName">Nom * (Français)</label>
                            <input type="text" id="createName" name="name" required maxlength="100">
                            <input type="hidden" name="name_fr" id="createName_fr">
                        </div>
                        <div class="form-group">
                            <label for="createDescription">Description (Français)</label>
                            <textarea id="createDescription" name="description" rows="3" maxlength="500"></textarea>
                            <input type="hidden" name="description_fr" id="createDescription_fr">
                        </div>
                    </div>

                    <!-- English -->
                    <div class="lang-content" id="createLang-en">
                        <div class="translation-note">
                            <strong>Traduction anglaise</strong> - Si vide, la version française sera utilisée.
                        </div>
                        <div class="form-group">
                            <label for="createName_en">Name (English)</label>
                            <input type="text" id="createName_en" name="name_en" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="createDescription_en">Description (English)</label>
                            <textarea id="createDescription_en" name="description_en" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>

                    <!-- Spanish -->
                    <div class="lang-content" id="createLang-es">
                        <div class="translation-note">
                            <strong>Traduction espagnole</strong> - Si vide, la version française sera utilisée.
                        </div>
                        <div class="form-group">
                            <label for="createName_es">Nombre (Español)</label>
                            <input type="text" id="createName_es" name="name_es" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="createDescription_es">Descripción (Español)</label>
                            <textarea id="createDescription_es" name="description_es" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>

                    <!-- Italian -->
                    <div class="lang-content" id="createLang-it">
                        <div class="translation-note">
                            <strong>Traduction italienne</strong> - Si vide, la version française sera utilisée.
                        </div>
                        <div class="form-group">
                            <label for="createName_it">Nome (Italiano)</label>
                            <input type="text" id="createName_it" name="name_it" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="createDescription_it">Descrizione (Italiano)</label>
                            <textarea id="createDescription_it" name="description_it" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>

                    <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--admin-border);">

                    <!-- Common fields (not translatable) -->
                    <div class="form-group">
                        <label for="createCategory">Catégorie</label>
                        <select id="createCategory" name="category" onchange="updateCreatePriceHT()">
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= h($key) ?>" data-vat="<?= getCategoryVatRate($key) ?>"><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="createPrice">Prix TTC (€) * <span class="vat-badge" id="createVatBadge">TVA <?= getDefaultVatRate() ?>%</span></label>
                        <div class="price-input-group">
                            <input type="number" id="createPrice" name="price" required min="0.01" step="0.01" oninput="updateCreatePriceHT()">
                            <div class="price-ht-display" id="createPriceHT">Prix HT : - €</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="createPosition">Position (ordre d'affichage)</label>
                        <input type="number" id="createPosition" name="position" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label for="createImage">Image</label>
                        <input type="file" id="createImage" name="image" accept="image/jpeg,image/png,image/webp">
                        <small>JPG, PNG, WEBP - Max 5 Mo</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('createModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Modifier l'article</h3>
                <button type="button" class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <div class="lang-tabs" id="editLangTabs">
                        <button type="button" class="lang-tab active" data-lang="fr" onclick="switchEditTab('fr')">
                            <span class="flag">🇫🇷</span> Français
                        </button>
                        <button type="button" class="lang-tab" data-lang="en" onclick="switchEditTab('en')">
                            <span class="flag">🇬🇧</span> English
                        </button>
                        <button type="button" class="lang-tab" data-lang="es" onclick="switchEditTab('es')">
                            <span class="flag">🇪🇸</span> Español
                        </button>
                        <button type="button" class="lang-tab" data-lang="it" onclick="switchEditTab('it')">
                            <span class="flag">🇮🇹</span> Italiano
                        </button>
                    </div>

                    <!-- French (default) -->
                    <div class="lang-content active" id="editLang-fr">
                        <div class="form-group">
                            <label for="editName">Nom * (Français)</label>
                            <input type="text" id="editName" name="name" required maxlength="100">
                            <input type="hidden" name="name_fr" id="editName_fr">
                        </div>
                        <div class="form-group">
                            <label for="editDescription">Description (Français)</label>
                            <textarea id="editDescription" name="description" rows="3" maxlength="500"></textarea>
                            <input type="hidden" name="description_fr" id="editDescription_fr">
                        </div>
                    </div>

                    <!-- English -->
                    <div class="lang-content" id="editLang-en">
                        <div class="translation-note">
                            <strong>Traduction anglaise</strong> - Si vide, la version française sera utilisée.
                        </div>
                        <div class="form-group">
                            <label for="editName_en">Name (English)</label>
                            <input type="text" id="editName_en" name="name_en" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="editDescription_en">Description (English)</label>
                            <textarea id="editDescription_en" name="description_en" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>

                    <!-- Spanish -->
                    <div class="lang-content" id="editLang-es">
                        <div class="translation-note">
                            <strong>Traduction espagnole</strong> - Si vide, la version française sera utilisée.
                        </div>
                        <div class="form-group">
                            <label for="editName_es">Nombre (Español)</label>
                            <input type="text" id="editName_es" name="name_es" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="editDescription_es">Descripción (Español)</label>
                            <textarea id="editDescription_es" name="description_es" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>

                    <!-- Italian -->
                    <div class="lang-content" id="editLang-it">
                        <div class="translation-note">
                            <strong>Traduction italienne</strong> - Si vide, la version française sera utilisée.
                        </div>
                        <div class="form-group">
                            <label for="editName_it">Nome (Italiano)</label>
                            <input type="text" id="editName_it" name="name_it" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="editDescription_it">Descrizione (Italiano)</label>
                            <textarea id="editDescription_it" name="description_it" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>

                    <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--admin-border);">

                    <!-- Common fields (not translatable) -->
                    <div class="form-group">
                        <label for="editCategory">Catégorie</label>
                        <select id="editCategory" name="category" onchange="updateEditPriceHT()">
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= h($key) ?>" data-vat="<?= getCategoryVatRate($key) ?>"><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editPrice">Prix TTC (€) * <span class="vat-badge" id="editVatBadge">TVA <?= getDefaultVatRate() ?>%</span></label>
                        <div class="price-input-group">
                            <input type="number" id="editPrice" name="price" required min="0.01" step="0.01" oninput="updateEditPriceHT()">
                            <div class="price-ht-display" id="editPriceHT">Prix HT : - €</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editPosition">Position (ordre d'affichage)</label>
                        <input type="number" id="editPosition" name="position" min="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="editActive" name="is_active" value="1">
                            Article actif
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Upload Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Changer l'image</h3>
                <button type="button" class="modal-close" onclick="closeModal('imageModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="imageForm">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="upload_image">
                <input type="hidden" name="id" id="imageItemId">
                <div class="modal-body">
                    <div class="upload-area" id="uploadArea">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <p>Glissez une image ici ou cliquez pour sélectionner</p>
                        <span class="upload-info">JPG, PNG, WEBP - Max 5 Mo</span>
                        <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <div id="previewContainer" class="preview-container" style="display: none;">
                        <img id="imagePreview" src="" alt="Aperçu">
                        <button type="button" class="btn btn-sm btn-outline" onclick="clearPreview()">Changer</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('imageModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Items translations data for JavaScript -->
    <script>
        const itemsTranslations = <?= json_encode($itemsWithTranslations) ?>;
    </script>

    <script>
        // Category toggle functionality
        function toggleCategory(header) {
            header.classList.toggle('expanded');
            const content = header.nextElementSibling;
            content.classList.toggle('expanded');
        }

        // VAT rate data for categories
        const categoryVatRates = <?= json_encode(getAllVatRates()) ?>;
        const defaultVatRate = <?= getDefaultVatRate() ?>;

        // Calculate HT price from TTC
        function calculateHT(priceTTC, vatRate) {
            if (vatRate <= 0) return priceTTC;
            return priceTTC / (1 + (vatRate / 100));
        }

        // Format price for display (French format)
        function formatPriceDisplay(price) {
            return price.toFixed(2).replace('.', ',') + ' €';
        }

        // Get VAT rate for a category
        function getVatRateForCategory(categoryCode) {
            if (categoryVatRates[categoryCode] !== null && categoryVatRates[categoryCode] !== undefined) {
                return categoryVatRates[categoryCode];
            }
            return defaultVatRate;
        }

        // Update Create modal HT price display
        function updateCreatePriceHT() {
            const priceInput = document.getElementById('createPrice');
            const categorySelect = document.getElementById('createCategory');
            const htDisplay = document.getElementById('createPriceHT');
            const vatBadge = document.getElementById('createVatBadge');

            const priceTTC = parseFloat(priceInput.value) || 0;
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const vatRate = parseFloat(selectedOption.dataset.vat) || defaultVatRate;

            vatBadge.textContent = `TVA ${vatRate}%`;

            if (priceTTC > 0) {
                const priceHT = calculateHT(priceTTC, vatRate);
                htDisplay.textContent = `Prix HT : ${formatPriceDisplay(priceHT)}`;
            } else {
                htDisplay.textContent = 'Prix HT : - €';
            }
        }

        // Update Edit modal HT price display
        function updateEditPriceHT() {
            const priceInput = document.getElementById('editPrice');
            const categorySelect = document.getElementById('editCategory');
            const htDisplay = document.getElementById('editPriceHT');
            const vatBadge = document.getElementById('editVatBadge');

            const priceTTC = parseFloat(priceInput.value) || 0;
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const vatRate = parseFloat(selectedOption.dataset.vat) || defaultVatRate;

            vatBadge.textContent = `TVA ${vatRate}%`;

            if (priceTTC > 0) {
                const priceHT = calculateHT(priceTTC, vatRate);
                htDisplay.textContent = `Prix HT : ${formatPriceDisplay(priceHT)}`;
            } else {
                htDisplay.textContent = 'Prix HT : - €';
            }
        }

        // Language tab switching for Create modal
        function switchCreateTab(lang) {
            document.querySelectorAll('#createLangTabs .lang-tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.lang === lang);
            });
            document.querySelectorAll('#createModal .lang-content').forEach(content => {
                content.classList.toggle('active', content.id === `createLang-${lang}`);
            });
        }

        // Language tab switching for Edit modal
        function switchEditTab(lang) {
            document.querySelectorAll('#editLangTabs .lang-tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.lang === lang);
            });
            document.querySelectorAll('#editModal .lang-content').forEach(content => {
                content.classList.toggle('active', content.id === `editLang-${lang}`);
            });
        }

        function openCreateModal() {
            // Reset form
            document.querySelector('#createModal form').reset();
            // Reset tabs to French
            switchCreateTab('fr');
            // Reset HT display
            updateCreatePriceHT();
            document.getElementById('createModal').classList.add('active');
        }

        function openEditModal(item) {
            const itemData = itemsTranslations[item.id] || item;
            const translations = itemData.translations || {};

            document.getElementById('editId').value = item.id;

            // Set French values (from main fields or translations)
            const frTrans = translations.fr || {};
            document.getElementById('editName').value = frTrans.name || item.name || '';
            document.getElementById('editDescription').value = frTrans.description || item.description || '';

            // Set English translations
            const enTrans = translations.en || {};
            document.getElementById('editName_en').value = enTrans.name || '';
            document.getElementById('editDescription_en').value = enTrans.description || '';

            // Set Spanish translations
            const esTrans = translations.es || {};
            document.getElementById('editName_es').value = esTrans.name || '';
            document.getElementById('editDescription_es').value = esTrans.description || '';

            // Set Italian translations
            const itTrans = translations.it || {};
            document.getElementById('editName_it').value = itTrans.name || '';
            document.getElementById('editDescription_it').value = itTrans.description || '';

            // Set common fields
            document.getElementById('editCategory').value = item.category || 'general';
            document.getElementById('editPrice').value = item.price;
            document.getElementById('editPosition').value = item.position || 0;
            document.getElementById('editActive').checked = item.is_active == 1;

            // Update HT price display
            updateEditPriceHT();

            // Reset tabs to French
            switchEditTab('fr');

            document.getElementById('editModal').classList.add('active');
        }

        function openImageModal(itemId) {
            document.getElementById('imageItemId').value = itemId;
            clearPreview();
            document.getElementById('imageModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });

        // File upload preview
        const imageInput = document.getElementById('imageInput');
        const uploadArea = document.getElementById('uploadArea');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    uploadArea.style.display = 'none';
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        function clearPreview() {
            imageInput.value = '';
            uploadArea.style.display = 'flex';
            previewContainer.style.display = 'none';
            imagePreview.src = '';
        }

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageInput.files = files;
                imageInput.dispatchEvent(new Event('change'));
            }
        });

        uploadArea.addEventListener('click', () => {
            imageInput.click();
        });

        // Sync French translation fields before form submission
        document.querySelector('#createModal form').addEventListener('submit', function() {
            document.getElementById('createName_fr').value = document.getElementById('createName').value;
            document.getElementById('createDescription_fr').value = document.getElementById('createDescription').value;
        });

        document.querySelector('#editModal form').addEventListener('submit', function() {
            document.getElementById('editName_fr').value = document.getElementById('editName').value;
            document.getElementById('editDescription_fr').value = document.getElementById('editDescription').value;
        });

        // Mobile menu toggle
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('mobileMenuToggle');
        const sidebarClose = document.getElementById('sidebarClose');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        menuToggle?.addEventListener('click', openSidebar);
        sidebarClose?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);
    </script>
</body>
</html>
