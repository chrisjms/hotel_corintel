<?php
/**
 * Room Service Categories Management
 * Hotel Corintel - Full CRUD for categories
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$csrfToken = generateCsrfToken();
$hotelName = getHotelName();

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
                $code = trim($_POST['code'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $timeStart = !empty($_POST['time_start']) ? $_POST['time_start'] : null;
                $timeEnd = !empty($_POST['time_end']) ? $_POST['time_end'] : null;
                $position = intval($_POST['position'] ?? getNextCategoryPosition());

                // Validate code format (alphanumeric and underscores only)
                if (!preg_match('/^[a-z0-9_]+$/', $code)) {
                    $message = 'Le code ne doit contenir que des lettres minuscules, chiffres et underscores.';
                    $messageType = 'error';
                } elseif (empty($name)) {
                    $message = 'Le nom est obligatoire.';
                    $messageType = 'error';
                } elseif (($timeStart && !$timeEnd) || (!$timeStart && $timeEnd)) {
                    $message = 'Veuillez définir les deux horaires ou laisser les deux vides.';
                    $messageType = 'error';
                } else {
                    $categoryId = createRoomServiceCategory([
                        'code' => $code,
                        'name' => $name,
                        'time_start' => $timeStart,
                        'time_end' => $timeEnd,
                        'position' => $position,
                        'is_active' => 1
                    ]);
                    if ($categoryId) {
                        // Save translations (French as base)
                        $translations = ['fr' => $name];
                        foreach (['en', 'es', 'it'] as $lang) {
                            $transName = trim($_POST["name_$lang"] ?? '');
                            if (!empty($transName)) {
                                $translations[$lang] = $transName;
                            }
                        }
                        saveCategoryTranslations($code, $translations);

                        // Save custom VAT rate if specified
                        $vatRate = isset($_POST['vat_rate']) && $_POST['vat_rate'] !== '' ? floatval($_POST['vat_rate']) : null;
                        if ($vatRate !== null) {
                            setCategoryVatRate($code, $vatRate);
                        }

                        $message = 'Catégorie créée avec succès.';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur: ce code existe déjà ou erreur de création.';
                        $messageType = 'error';
                    }
                }
                break;

            case 'update':
                $code = $_POST['code'] ?? '';
                $name = trim($_POST['name'] ?? '');
                $timeStart = !empty($_POST['time_start']) ? $_POST['time_start'] : null;
                $timeEnd = !empty($_POST['time_end']) ? $_POST['time_end'] : null;
                $position = intval($_POST['position'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if (empty($name)) {
                    $message = 'Le nom est obligatoire.';
                    $messageType = 'error';
                } elseif (($timeStart && !$timeEnd) || (!$timeStart && $timeEnd)) {
                    $message = 'Veuillez définir les deux horaires ou laisser les deux vides.';
                    $messageType = 'error';
                } else {
                    $success = updateRoomServiceCategory($code, [
                        'name' => $name,
                        'time_start' => $timeStart,
                        'time_end' => $timeEnd,
                        'position' => $position,
                        'is_active' => $isActive
                    ]);
                    if ($success) {
                        // Save translations
                        $translations = ['fr' => $name];
                        foreach (['en', 'es', 'it'] as $lang) {
                            $transName = trim($_POST["name_$lang"] ?? '');
                            if (!empty($transName)) {
                                $translations[$lang] = $transName;
                            }
                        }
                        saveCategoryTranslations($code, $translations);

                        // Save custom VAT rate (empty = use default)
                        $vatRate = isset($_POST['vat_rate']) && $_POST['vat_rate'] !== '' ? floatval($_POST['vat_rate']) : null;
                        setCategoryVatRate($code, $vatRate);

                        $message = 'Catégorie mise à jour.';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de la mise à jour.';
                        $messageType = 'error';
                    }
                }
                break;

            case 'toggle':
                $code = $_POST['code'] ?? '';
                if (toggleRoomServiceCategoryStatus($code)) {
                    $message = 'Statut mis à jour.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $code = $_POST['code'] ?? '';
                $reassignTo = !empty($_POST['reassign_to']) ? $_POST['reassign_to'] : 'general';

                $result = deleteRoomServiceCategory($code, $reassignTo);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Get all categories with translations, item counts, and VAT rates
$categories = getRoomServiceCategoriesAllWithTranslations();
$defaultVatRate = getDefaultVatRate();
foreach ($categories as &$cat) {
    $cat['items_count'] = getCategoryItemsCount($cat['code']);
    $customVatRate = getSetting('vat_rate_' . $cat['code'], null);
    $cat['vat_rate'] = $customVatRate !== null && $customVatRate !== '' ? (float)$customVatRate : null;
    $cat['effective_vat_rate'] = $cat['vat_rate'] ?? $defaultVatRate;
}
unset($cat);

$nextPosition = getNextCategoryPosition();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Room Service - Catégories | Admin <?= h($hotelName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .categories-table {
            width: 100%;
            border-collapse: collapse;
        }
        .categories-table th,
        .categories-table td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--admin-border);
        }
        .categories-table th {
            font-weight: 600;
            color: var(--admin-text-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .categories-table tr:hover {
            background: var(--admin-bg);
        }
        .badge-active {
            background: rgba(72, 187, 120, 0.1);
            color: #276749;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-inactive {
            background: rgba(245, 101, 101, 0.1);
            color: #C53030;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .time-badge {
            background: rgba(66, 153, 225, 0.1);
            color: #2B6CB0;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .vat-rate {
            font-weight: 600;
            color: var(--admin-primary);
        }
        .vat-rate.vat-default {
            color: var(--admin-text-light);
            font-weight: 400;
        }
        .vat-rate small {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        .vat-field-note {
            font-size: 0.8rem;
            color: var(--admin-text-light);
            margin-top: 0.25rem;
        }
        .actions-cell {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-icon svg {
            width: 16px;
            height: 16px;
        }
        .btn-edit {
            background: #4299E1;
            color: white;
        }
        .btn-edit:hover {
            background: #3182CE;
        }
        .btn-success {
            background: var(--admin-success);
            color: #fff;
        }
        .btn-success:hover {
            background: #38a169;
        }
        .btn-danger {
            background: var(--admin-error);
            color: #fff;
        }
        .btn-danger:hover {
            background: #e53e3e;
        }
        .btn-outline {
            background: white;
            border: 1px solid var(--admin-border);
            color: var(--admin-text);
        }
        .category-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .category-info strong {
            font-size: 0.9375rem;
        }
        .category-info small {
            color: var(--admin-text-light);
            font-size: 0.75rem;
        }
        .items-count {
            color: var(--admin-text-light);
            font-size: 0.8125rem;
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.125rem;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--admin-text-light);
        }
        .modal-body {
            padding: 1.25rem;
        }
        .modal-footer {
            padding: 1.25rem;
            border-top: 1px solid var(--admin-border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-hint {
            font-size: 0.75rem;
            color: var(--admin-text-light);
            margin-top: 0.25rem;
        }
        .translation-section {
            background: var(--admin-bg);
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .translation-section h4 {
            margin: 0 0 1rem 0;
            font-size: 0.875rem;
            color: var(--admin-text-light);
        }
        .translation-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .translation-row:last-child {
            margin-bottom: 0;
        }
        .translation-row .flag {
            width: 24px;
            text-align: center;
        }
        .translation-row input {
            flex: 1;
        }
        .delete-warning {
            background: rgba(245, 101, 101, 0.1);
            border-left: 3px solid #E53E3E;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 4px 4px 0;
        }
        .delete-warning p {
            margin: 0;
            color: #C53030;
            font-size: 0.875rem;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
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

                <div class="nav-separator">Activité</div>
                <a href="room-service-orders.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    Commandes
                    <?php if ($pendingOrders > 0): ?>
                        <span class="badge" style="background: #E53E3E; color: white; margin-left: auto;"><?= $pendingOrders ?></span>
                    <?php endif; ?>
                </a>
                <a href="room-service-messages.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge" style="background: #E53E3E; color: white; margin-left: auto;"><?= $unreadMessages ?></span>
                    <?php endif; ?>
                </a>

                <div class="nav-separator">Room Service</div>
                <a href="room-service-categories.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/>
                        <line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                    Catégories
                </a>
                <a href="room-service-items.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                        <line x1="6" y1="1" x2="6" y2="4"/>
                        <line x1="10" y1="1" x2="10" y2="4"/>
                        <line x1="14" y1="1" x2="14" y2="4"/>
                    </svg>
                    Articles
                </a>
                <a href="room-service-stats.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Statistiques
                </a>

                <div class="nav-separator">Contenu</div>
                <a href="content.php?tab=general" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Général
                </a>
                <a href="content.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="9" y1="21" x2="9" y2="9"/>
                    </svg>
                    Sections
                </a>
                <a href="theme.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a10 10 0 0 0 0 20"/>
                        <path d="M12 2c-2.5 2.5-4 6-4 10s1.5 7.5 4 10"/>
                    </svg>
                    Thème
                </a>

                <div class="nav-separator">Administration</div>
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
                <h1>Room Service - Catégories</h1>
                <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Ajouter une catégorie
                </button>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= h($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Categories Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>Liste des catégories</h2>
                        <span class="badge"><?= count($categories) ?> catégories</span>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($categories)): ?>
                            <p class="empty-state">Aucune catégorie. Cliquez sur "Ajouter une catégorie" pour commencer.</p>
                        <?php else: ?>
                            <table class="categories-table">
                                <thead>
                                    <tr>
                                        <th>Position</th>
                                        <th>Catégorie</th>
                                        <th>TVA</th>
                                        <th>Horaires</th>
                                        <th>Articles</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <?php
                                        $timeStart = $category['time_start'] ? substr($category['time_start'], 0, 5) : '';
                                        $timeEnd = $category['time_end'] ? substr($category['time_end'], 0, 5) : '';
                                        $isGeneral = $category['code'] === 'general';
                                        ?>
                                        <tr>
                                            <td><?= $category['position'] ?></td>
                                            <td>
                                                <div class="category-info">
                                                    <strong><?= h($category['name']) ?></strong>
                                                    <small><?= h($category['code']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($category['vat_rate'] !== null): ?>
                                                    <span class="vat-rate"><?= number_format($category['vat_rate'], 1) ?>%</span>
                                                <?php else: ?>
                                                    <span class="vat-rate vat-default"><?= number_format($defaultVatRate, 1) ?>% <small>(défaut)</small></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($timeStart && $timeEnd): ?>
                                                    <span class="time-badge">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <polyline points="12 6 12 12 16 14"/>
                                                        </svg>
                                                        <?= h($timeStart) ?> - <?= h($timeEnd) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="time-badge">24h/24</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="items-count"><?= $category['items_count'] ?> article(s)</span>
                                            </td>
                                            <td>
                                                <span class="<?= $category['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                                    <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions-cell">
                                                    <button type="button" class="btn-icon btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($category)) ?>)" title="Modifier">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                        </svg>
                                                    </button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="toggle">
                                                        <input type="hidden" name="code" value="<?= h($category['code']) ?>">
                                                        <button type="submit" class="btn-icon <?= $category['is_active'] ? 'btn-outline' : 'btn-success' ?>" title="<?= $category['is_active'] ? 'Désactiver' : 'Activer' ?>">
                                                            <?php if ($category['is_active']): ?>
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
                                                    <?php if (!$isGeneral): ?>
                                                        <button type="button" class="btn-icon btn-danger" onclick="openDeleteModal('<?= h($category['code']) ?>', '<?= h($category['name']) ?>', <?= $category['items_count'] ?>)" title="Supprimer">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <polyline points="3 6 5 6 21 6"/>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                            </svg>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="card">
                    <div class="card-header">
                        <h2>Aide</h2>
                    </div>
                    <div class="card-body">
                        <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                            <li><strong>Code</strong> : Identifiant unique (minuscules, chiffres, underscores)</li>
                            <li><strong>Position</strong> : Ordre d'affichage sur le site (1 = premier)</li>
                            <li><strong>Horaires</strong> : Si définis, les articles ne sont commandables que pendant cette période</li>
                            <li><strong>Catégorie "Général"</strong> : Ne peut pas être supprimée (catégorie par défaut)</li>
                            <li><strong>Suppression</strong> : Les articles seront réassignés à une autre catégorie</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nouvelle catégorie</h3>
                <button type="button" class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="createCode">Code *</label>
                            <input type="text" id="createCode" name="code" required maxlength="50" pattern="[a-z0-9_]+" placeholder="ex: special_menu">
                            <p class="form-hint">Minuscules, chiffres, underscores uniquement</p>
                        </div>
                        <div class="form-group">
                            <label for="createPosition">Position</label>
                            <input type="number" id="createPosition" name="position" value="<?= $nextPosition ?>" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="createName">Nom (Français) *</label>
                        <input type="text" id="createName" name="name" required maxlength="100" placeholder="ex: Menu Spécial">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="createTimeStart">Heure début</label>
                            <input type="time" id="createTimeStart" name="time_start">
                        </div>
                        <div class="form-group">
                            <label for="createTimeEnd">Heure fin</label>
                            <input type="time" id="createTimeEnd" name="time_end">
                        </div>
                    </div>
                    <p class="form-hint">Laissez vide pour une disponibilité 24h/24</p>

                    <div class="form-group">
                        <label for="createVatRate">Taux de TVA (%)</label>
                        <input type="number" id="createVatRate" name="vat_rate" min="0" max="100" step="0.1" placeholder="<?= $defaultVatRate ?>">
                        <p class="vat-field-note">Laissez vide pour utiliser le taux par défaut (<?= $defaultVatRate ?>%)</p>
                    </div>

                    <div class="translation-section">
                        <h4>Traductions (optionnel)</h4>
                        <div class="translation-row">
                            <span class="flag">🇬🇧</span>
                            <input type="text" name="name_en" placeholder="English name" maxlength="100">
                        </div>
                        <div class="translation-row">
                            <span class="flag">🇪🇸</span>
                            <input type="text" name="name_es" placeholder="Nombre en español" maxlength="100">
                        </div>
                        <div class="translation-row">
                            <span class="flag">🇮🇹</span>
                            <input type="text" name="name_it" placeholder="Nome in italiano" maxlength="100">
                        </div>
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
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier la catégorie</h3>
                <button type="button" class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="code" id="editCode">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Code</label>
                            <input type="text" id="editCodeDisplay" disabled>
                            <p class="form-hint">Le code ne peut pas être modifié</p>
                        </div>
                        <div class="form-group">
                            <label for="editPosition">Position</label>
                            <input type="number" id="editPosition" name="position" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editName">Nom (Français) *</label>
                        <input type="text" id="editName" name="name" required maxlength="100">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editTimeStart">Heure début</label>
                            <input type="time" id="editTimeStart" name="time_start">
                        </div>
                        <div class="form-group">
                            <label for="editTimeEnd">Heure fin</label>
                            <input type="time" id="editTimeEnd" name="time_end">
                        </div>
                    </div>
                    <p class="form-hint">Laissez vide pour une disponibilité 24h/24</p>

                    <div class="form-group">
                        <label for="editVatRate">Taux de TVA (%)</label>
                        <input type="number" id="editVatRate" name="vat_rate" min="0" max="100" step="0.1" placeholder="<?= $defaultVatRate ?>">
                        <p class="vat-field-note">Laissez vide pour utiliser le taux par défaut (<?= $defaultVatRate ?>%)</p>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" id="editActive" name="is_active" value="1">
                            Catégorie active
                        </label>
                    </div>

                    <div class="translation-section">
                        <h4>Traductions (optionnel)</h4>
                        <div class="translation-row">
                            <span class="flag">🇬🇧</span>
                            <input type="text" name="name_en" id="editName_en" placeholder="English name" maxlength="100">
                        </div>
                        <div class="translation-row">
                            <span class="flag">🇪🇸</span>
                            <input type="text" name="name_es" id="editName_es" placeholder="Nombre en español" maxlength="100">
                        </div>
                        <div class="translation-row">
                            <span class="flag">🇮🇹</span>
                            <input type="text" name="name_it" id="editName_it" placeholder="Nome in italiano" maxlength="100">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Supprimer la catégorie</h3>
                <button type="button" class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="code" id="deleteCode">
                <div class="modal-body">
                    <div class="delete-warning">
                        <p>Êtes-vous sûr de vouloir supprimer la catégorie "<strong id="deleteName"></strong>" ?</p>
                    </div>
                    <div id="deleteItemsWarning" style="display: none;">
                        <p style="margin-bottom: 1rem;">Cette catégorie contient <strong id="deleteItemsCount"></strong> article(s). Ils seront réassignés à :</p>
                        <div class="form-group">
                            <select name="reassign_to" id="reassignTo">
                                <?php foreach ($categories as $cat): ?>
                                    <?php if ($cat['code'] !== 'general'): ?>
                                        <option value="<?= h($cat['code']) ?>"><?= h($cat['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <option value="general" selected>Général (par défaut)</option>
                            </select>
                        </div>
                    </div>
                    <p style="color: var(--admin-text-light); font-size: 0.875rem;">Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openCreateModal() {
        document.getElementById('createModal').classList.add('active');
    }

    function openEditModal(category) {
        document.getElementById('editCode').value = category.code;
        document.getElementById('editCodeDisplay').value = category.code;
        document.getElementById('editName').value = category.name;
        document.getElementById('editPosition').value = category.position || 0;
        document.getElementById('editTimeStart').value = category.time_start ? category.time_start.substring(0, 5) : '';
        document.getElementById('editTimeEnd').value = category.time_end ? category.time_end.substring(0, 5) : '';
        document.getElementById('editActive').checked = category.is_active == 1;

        // Set VAT rate (empty if null/default)
        document.getElementById('editVatRate').value = category.vat_rate !== null ? category.vat_rate : '';

        // Set translations
        const translations = category.translations || {};
        document.getElementById('editName_en').value = translations.en || '';
        document.getElementById('editName_es').value = translations.es || '';
        document.getElementById('editName_it').value = translations.it || '';

        document.getElementById('editModal').classList.add('active');
    }

    function openDeleteModal(code, name, itemsCount) {
        document.getElementById('deleteCode').value = code;
        document.getElementById('deleteName').textContent = name;

        const itemsWarning = document.getElementById('deleteItemsWarning');
        const itemsCountEl = document.getElementById('deleteItemsCount');
        const reassignSelect = document.getElementById('reassignTo');

        if (itemsCount > 0) {
            itemsCountEl.textContent = itemsCount;
            itemsWarning.style.display = 'block';
            // Remove current category from reassign options
            Array.from(reassignSelect.options).forEach(opt => {
                opt.disabled = opt.value === code;
            });
        } else {
            itemsWarning.style.display = 'none';
        }

        document.getElementById('deleteModal').classList.add('active');
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
