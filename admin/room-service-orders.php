<?php
/**
 * Room Service Orders Management
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$csrfToken = generateCsrfToken();
$statuses = getRoomServiceOrderStatuses();
$paymentMethods = getRoomServicePaymentMethods();

// Handle POST requests
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'update_status':
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? '';
                if (updateRoomServiceOrderStatus($id, $status)) {
                    $message = 'Statut mis à jour.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Filter by status
$statusFilter = $_GET['status'] ?? 'all';

// Sort options
$sortBy = $_GET['sort'] ?? 'delivery_datetime';
$sortOrder = $_GET['order'] ?? 'ASC';

// Filter by delivery date
$deliveryDateFilter = $_GET['delivery_date'] ?? null;

// Get orders
$orders = getRoomServiceOrders($statusFilter, $sortBy, $sortOrder, $deliveryDateFilter);
$stats = getRoomServiceStats();

// Get order details if viewing specific order
$viewOrder = null;
$viewOrderItems = [];
if (isset($_GET['view'])) {
    $viewOrder = getRoomServiceOrderById(intval($_GET['view']));
    if ($viewOrder) {
        $viewOrderItems = getRoomServiceOrderItems($viewOrder['id']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Room Service - Commandes | Admin Hôtel Corintel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        .orders-table th,
        .orders-table td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--admin-border);
        }
        .orders-table th {
            font-weight: 600;
            color: var(--admin-text-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .orders-table tbody tr {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .orders-table tbody tr:hover {
            background: var(--admin-bg);
        }
        .orders-table tbody tr:active {
            background: rgba(139, 90, 43, 0.08);
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 20px;
        }
        .status-pending {
            background: rgba(237, 137, 54, 0.1);
            color: #C05621;
        }
        .status-confirmed {
            background: rgba(66, 153, 225, 0.1);
            color: #2B6CB0;
        }
        .status-preparing {
            background: rgba(159, 122, 234, 0.1);
            color: #6B46C1;
        }
        .status-delivered {
            background: rgba(72, 187, 120, 0.1);
            color: #276749;
        }
        .status-cancelled {
            background: rgba(245, 101, 101, 0.1);
            color: #C53030;
        }
        .price {
            font-weight: 600;
            color: var(--admin-primary);
        }
        .order-details {
            background: var(--admin-bg);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .order-details h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .order-meta-item {
            display: flex;
            flex-direction: column;
        }
        .order-meta-item label {
            font-size: 0.75rem;
            color: var(--admin-text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .order-meta-item span {
            font-weight: 500;
        }
        .order-items-table {
            width: 100%;
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            overflow: hidden;
        }
        .order-items-table th,
        .order-items-table td {
            padding: 0.75rem 1rem;
            text-align: left;
        }
        .order-items-table th {
            background: var(--admin-bg);
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--admin-text-light);
            text-transform: uppercase;
        }
        .order-items-table td {
            border-bottom: 1px solid var(--admin-border);
        }
        .order-items-table tr:last-child td {
            border-bottom: none;
        }
        .order-total {
            display: flex;
            justify-content: flex-end;
            padding: 1rem;
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            margin-top: 0.5rem;
        }
        .order-total span {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--admin-primary);
        }
        .status-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status-form select {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            background: var(--admin-card);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--admin-text-light);
        }
        .back-link:hover {
            color: var(--admin-primary);
        }
        .back-link svg {
            width: 16px;
            height: 16px;
        }
        /* Export styles */
        .export-dropdown {
            position: relative;
            display: inline-block;
        }
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-size: 0.875rem;
            color: var(--admin-text);
            cursor: pointer;
            transition: all 0.2s;
        }
        .export-btn:hover {
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }
        .export-btn svg {
            width: 16px;
            height: 16px;
        }
        .export-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 320px;
            z-index: 100;
            display: none;
        }
        .export-menu.active {
            display: block;
        }
        .export-menu-header {
            padding: 1rem;
            border-bottom: 1px solid var(--admin-border);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .export-menu-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--admin-text-light);
            padding: 0.25rem;
        }
        .export-menu-close:hover {
            color: var(--admin-text);
        }
        .export-menu-body {
            padding: 1rem;
        }
        .export-menu-body .form-group {
            margin-bottom: 1rem;
        }
        .export-menu-body .form-group:last-of-type {
            margin-bottom: 0;
        }
        .export-menu-body label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--admin-text-light);
            margin-bottom: 0.375rem;
        }
        .export-menu-body input,
        .export-menu-body select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-size: 0.875rem;
            background: var(--admin-bg);
        }
        .export-menu-body input:focus,
        .export-menu-body select:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .export-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .export-menu-footer {
            padding: 1rem;
            border-top: 1px solid var(--admin-border);
            display: flex;
            gap: 0.5rem;
        }
        .export-menu-footer .btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .export-menu-footer .btn svg {
            width: 16px;
            height: 16px;
        }
        .btn-csv {
            background: #276749;
            color: white;
            border: none;
        }
        .btn-csv:hover {
            background: #22543D;
        }
        .btn-pdf {
            background: #C53030;
            color: white;
            border: none;
        }
        .btn-pdf:hover {
            background: #9B2C2C;
        }
        .export-info {
            font-size: 0.75rem;
            color: var(--admin-text-light);
            margin-top: 0.5rem;
        }
        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .toast {
            background: var(--admin-card);
            border-radius: 8px;
            padding: 1rem 1.25rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: toastIn 0.3s ease-out;
            max-width: 320px;
            border-left: 4px solid #48BB78;
        }
        .toast.toast-order {
            border-left-color: #ED8936;
        }
        .toast.toast-message {
            border-left-color: #3182CE;
        }
        @keyframes toastIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }
        .toast.toast-order .toast-icon {
            color: #ED8936;
        }
        .toast.toast-message .toast-icon {
            color: #3182CE;
        }
        .toast-content {
            flex: 1;
        }
        .toast-title {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.125rem;
        }
        .toast-message {
            font-size: 0.8rem;
            color: var(--admin-text-light);
        }
        .toast-close {
            background: none;
            border: none;
            color: var(--admin-text-light);
            cursor: pointer;
            padding: 0.25rem;
        }
        .toast-close:hover {
            color: var(--admin-text);
        }
        /* Row highlight animation */
        @keyframes highlightRow {
            0% { background: rgba(72, 187, 120, 0.2); }
            100% { background: transparent; }
        }
        .row-highlight {
            animation: highlightRow 2s ease-out;
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
                <h2>Hôtel Corintel</h2>
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
                <a href="room-service-items.php" class="nav-item">
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
                    Room Service - Horaires
                </a>
                <a href="room-service-orders.php" class="nav-item active">
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
                <h1>Room Service - Commandes</h1>
                <a href="../room-service.php" target="_blank" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    Voir la page publique
                </a>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= h($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($viewOrder): ?>
                    <!-- Order Detail View -->
                    <a href="room-service-orders.php<?= $statusFilter !== 'all' ? '?status=' . h($statusFilter) : '' ?>" class="back-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                        Retour aux commandes
                    </a>

                    <div class="order-details">
                        <h3>
                            Commande #<?= $viewOrder['id'] ?>
                            <span class="status-badge status-<?= $viewOrder['status'] ?>">
                                <?= h($statuses[$viewOrder['status']] ?? $viewOrder['status']) ?>
                            </span>
                        </h3>

                        <div class="order-meta">
                            <div class="order-meta-item">
                                <label>Chambre</label>
                                <span><?= h($viewOrder['room_number']) ?></span>
                            </div>
                            <?php if ($viewOrder['guest_name']): ?>
                            <div class="order-meta-item">
                                <label>Nom du client</label>
                                <span><?= h($viewOrder['guest_name']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($viewOrder['phone']): ?>
                            <div class="order-meta-item">
                                <label>Téléphone</label>
                                <span><?= h($viewOrder['phone']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="order-meta-item">
                                <label>Mode de paiement</label>
                                <span><?= h($paymentMethods[$viewOrder['payment_method']] ?? $viewOrder['payment_method']) ?></span>
                            </div>
                            <div class="order-meta-item">
                                <label>Livraison souhaitée</label>
                                <span style="color: var(--admin-primary); font-weight: 600;"><?= formatDate($viewOrder['delivery_datetime'] ?? null) ?></span>
                            </div>
                            <div class="order-meta-item">
                                <label>Date de commande</label>
                                <span><?= formatDate($viewOrder['created_at']) ?></span>
                            </div>
                            <div class="order-meta-item">
                                <label>Modifier le statut</label>
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $viewOrder['id'] ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <?php foreach ($statuses as $key => $label): ?>
                                            <option value="<?= h($key) ?>" <?= $viewOrder['status'] === $key ? 'selected' : '' ?>>
                                                <?= h($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <?php if ($viewOrder['notes']): ?>
                        <div class="order-meta-item" style="margin-bottom: 1.5rem;">
                            <label>Notes</label>
                            <span><?= nl2br(h($viewOrder['notes'])) ?></span>
                        </div>
                        <?php endif; ?>

                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Prix unitaire</th>
                                    <th>Quantité</th>
                                    <th style="text-align: right;">Sous-total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($viewOrderItems as $item): ?>
                                    <tr>
                                        <td><?= h($item['item_name']) ?></td>
                                        <td><?= number_format($item['item_price'], 2, ',', ' ') ?> €</td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td style="text-align: right;"><?= number_format($item['subtotal'], 2, ',', ' ') ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="order-total">
                            <span>Total: <?= number_format($viewOrder['total_amount'], 2, ',', ' ') ?> €</span>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Orders List View -->

                    <!-- Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $stats['total_orders'] ?></span>
                                <span class="stat-label">Commandes totales</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(237, 137, 54, 0.1);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #ED8936;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $stats['pending_orders'] ?></span>
                                <span class="stat-label">En attente</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                    <line x1="1" y1="10" x2="23" y2="10"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $stats['today_orders'] ?></span>
                                <span class="stat-label">Commandes aujourd'hui</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(72, 187, 120, 0.1);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #48BB78;">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= number_format($stats['today_revenue'], 2, ',', ' ') ?> €</span>
                                <span class="stat-label">Revenus aujourd'hui</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter Tabs -->
                    <div class="section-tabs">
                        <a href="?status=all&sort=<?= h($sortBy) ?>&order=<?= h($sortOrder) ?><?= $deliveryDateFilter ? '&delivery_date=' . h($deliveryDateFilter) : '' ?>" class="section-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">
                            Toutes
                        </a>
                        <?php foreach ($statuses as $key => $label): ?>
                            <a href="?status=<?= h($key) ?>&sort=<?= h($sortBy) ?>&order=<?= h($sortOrder) ?><?= $deliveryDateFilter ? '&delivery_date=' . h($deliveryDateFilter) : '' ?>" class="section-tab <?= $statusFilter === $key ? 'active' : '' ?>">
                                <?= h($label) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Filter and Sort Controls -->
                    <div class="card" style="margin-bottom: 1rem;">
                        <div class="card-body" style="padding: 1rem;">
                            <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                                <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--admin-text-light);">Filtrer par date de livraison</label>
                                    <input type="date" name="delivery_date" value="<?= h($deliveryDateFilter ?? '') ?>" style="padding: 0.5rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--admin-text-light);">Trier par</label>
                                    <select name="sort" style="padding: 0.5rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                                        <option value="delivery_datetime" <?= $sortBy === 'delivery_datetime' ? 'selected' : '' ?>>Livraison</option>
                                        <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date commande</option>
                                        <option value="total_amount" <?= $sortBy === 'total_amount' ? 'selected' : '' ?>>Montant</option>
                                        <option value="room_number" <?= $sortBy === 'room_number' ? 'selected' : '' ?>>Chambre</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--admin-text-light);">Ordre</label>
                                    <select name="order" style="padding: 0.5rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                                        <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Croissant</option>
                                        <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
                                <?php if ($deliveryDateFilter): ?>
                                    <a href="?status=<?= h($statusFilter) ?>&sort=<?= h($sortBy) ?>&order=<?= h($sortOrder) ?>" class="btn btn-sm btn-outline">Effacer date</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Liste des commandes</h2>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span class="badge"><?= count($orders) ?> commandes</span>
                                <div class="export-dropdown">
                                    <button type="button" class="export-btn" id="exportBtn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="7 10 12 15 17 10"/>
                                            <line x1="12" y1="15" x2="12" y2="3"/>
                                        </svg>
                                        Exporter
                                    </button>
                                    <div class="export-menu" id="exportMenu">
                                        <div class="export-menu-header">
                                            <span>Exporter les commandes</span>
                                            <button type="button" class="export-menu-close" id="exportMenuClose">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="export-menu-body">
                                            <div class="form-group">
                                                <label>Statut</label>
                                                <select id="exportStatus">
                                                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                                                    <?php foreach ($statuses as $key => $label): ?>
                                                        <option value="<?= h($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="export-row">
                                                <div class="form-group">
                                                    <label>Date debut</label>
                                                    <input type="date" id="exportDateFrom">
                                                </div>
                                                <div class="form-group">
                                                    <label>Date fin</label>
                                                    <input type="date" id="exportDateTo" value="<?= date('Y-m-d') ?>">
                                                </div>
                                            </div>
                                            <p class="export-info">Laissez les dates vides pour exporter toutes les commandes.</p>
                                        </div>
                                        <div class="export-menu-footer">
                                            <button type="button" class="btn btn-csv" onclick="exportOrders('csv')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                                </svg>
                                                CSV (Excel)
                                            </button>
                                            <button type="button" class="btn btn-pdf" onclick="exportOrders('pdf')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                </svg>
                                                PDF
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($orders)): ?>
                                <p class="empty-state">Aucune commande<?= $statusFilter !== 'all' ? ' avec ce statut' : '' ?>.</p>
                            <?php else: ?>
                                <table class="orders-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Chambre</th>
                                            <th>Client</th>
                                            <th>Total</th>
                                            <th>Livraison</th>
                                            <th>Statut</th>
                                            <th>Commandé le</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <?php
                                            // Check if delivery is upcoming (within next 2 hours)
                                            $deliveryTime = strtotime($order['delivery_datetime'] ?? '');
                                            $isUrgent = $deliveryTime && $deliveryTime <= time() + (2 * 60 * 60) && $deliveryTime > time() && $order['status'] !== 'delivered' && $order['status'] !== 'cancelled';
                                            $isPast = $deliveryTime && $deliveryTime < time() && $order['status'] !== 'delivered' && $order['status'] !== 'cancelled';
                                            ?>
                                            <tr data-href="?view=<?= $order['id'] ?>"<?= $isUrgent ? ' style="background: rgba(237, 137, 54, 0.05);"' : ($isPast ? ' style="background: rgba(245, 101, 101, 0.05);"' : '') ?>>
                                                <td><?= $order['id'] ?></td>
                                                <td><strong><?= h($order['room_number']) ?></strong></td>
                                                <td><?= h($order['guest_name'] ?? '-') ?></td>
                                                <td><span class="price"><?= number_format($order['total_amount'], 2, ',', ' ') ?> €</span></td>
                                                <td>
                                                    <span style="<?= $isUrgent ? 'color: #C05621; font-weight: 600;' : ($isPast ? 'color: #C53030; font-weight: 600;' : '') ?>">
                                                        <?= formatDate($order['delivery_datetime'] ?? null) ?>
                                                    </span>
                                                    <?php if ($isUrgent): ?>
                                                        <span style="display: block; font-size: 0.7rem; color: #C05621;">Bientôt</span>
                                                    <?php elseif ($isPast): ?>
                                                        <span style="display: block; font-size: 0.7rem; color: #C53030;">En retard</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?= $order['status'] ?>">
                                                        <?= h($statuses[$order['status']] ?? $order['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= formatDate($order['created_at']) ?></td>
                                                <td>
                                                    <a href="?view=<?= $order['id'] ?>" class="btn btn-sm btn-outline">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                            <circle cx="12" cy="12" r="3"/>
                                                        </svg>
                                                        Détails
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
    // Clickable table rows
    document.addEventListener('click', function(e) {
        const row = e.target.closest('.orders-table tbody tr[data-href]');
        if (!row) return;

        // Don't navigate if clicking on interactive elements
        if (e.target.closest('a, button, select, input')) return;

        window.location.href = row.dataset.href;
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

    // Export functionality
    const exportBtn = document.getElementById('exportBtn');
    const exportMenu = document.getElementById('exportMenu');
    const exportMenuClose = document.getElementById('exportMenuClose');

    exportBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        exportMenu.classList.toggle('active');
    });

    exportMenuClose?.addEventListener('click', function() {
        exportMenu.classList.remove('active');
    });

    // Close export menu when clicking outside
    document.addEventListener('click', function(e) {
        if (exportMenu && !exportMenu.contains(e.target) && e.target !== exportBtn) {
            exportMenu.classList.remove('active');
        }
    });

    function exportOrders(format) {
        const status = document.getElementById('exportStatus').value;
        const dateFrom = document.getElementById('exportDateFrom').value;
        const dateTo = document.getElementById('exportDateTo').value;

        let url = 'export-orders.php?format=' + format;
        url += '&status=' + encodeURIComponent(status);

        if (dateFrom) {
            url += '&date_from=' + encodeURIComponent(dateFrom);
        }
        if (dateTo) {
            url += '&date_to=' + encodeURIComponent(dateTo);
        }

        // Open in new window/tab
        window.open(url, '_blank');

        // Close the export menu
        exportMenu.classList.remove('active');
    }

    // ============================================
    // Real-Time Updates
    // ============================================
    const POLL_INTERVAL = 15000; // 15 seconds
    let pollTimer = null;
    let lastOrderCount = <?= count($orders) ?>;
    let lastOrderIds = [<?= implode(',', array_column($orders, 'id')) ?>];
    let lastUnreadMessages = <?= $unreadMessages ?>;
    let isPageVisible = true;

    // Toast notification
    function showToast(title, message, type = 'order') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;

        const icon = type === 'message'
            ? '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'
            : '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>';

        toast.innerHTML = `
            <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${icon}
            </svg>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;
        container.appendChild(toast);

        // Play notification sound
        playNotificationSound();

        // Auto-remove after 8 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'toastIn 0.3s ease-out reverse';
                setTimeout(() => toast.remove(), 300);
            }
        }, 8000);
    }

    // Notification sound
    function playNotificationSound() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);

            oscillator.start(audioCtx.currentTime);
            oscillator.stop(audioCtx.currentTime + 0.3);
        } catch (e) {
            // Silent fail if audio not supported
        }
    }

    // Fetch updates
    async function fetchUpdates() {
        try {
            const params = new URLSearchParams({
                status: '<?= h($statusFilter) ?>',
                sort: '<?= h($sortBy) ?>',
                order: '<?= h($sortOrder) ?>'
            });
            <?php if ($deliveryDateFilter): ?>
            params.append('delivery_date', '<?= h($deliveryDateFilter) ?>');
            <?php endif; ?>

            console.log('[Live Update] Fetching orders...');
            const response = await fetch('api/orders-updates.php?' + params.toString());
            if (!response.ok) {
                console.error('[Live Update] Response not OK:', response.status);
                return;
            }

            const data = await response.json();
            console.log('[Live Update] Data received:', data);
            if (!data.success) {
                console.error('[Live Update] API error:', data.error);
                return;
            }

            // Update sidebar badges
            updateBadge('pendingOrders', data.data.pendingOrders);
            updateBadge('unreadMessages', data.data.unreadMessages);

            // Update stats
            if (data.data.stats) {
                updateStats(data.data.stats);
            }

            // Check for new orders
            const currentOrders = data.data.orders || [];
            const currentIds = currentOrders.map(o => o.id);
            const newOrderIds = currentIds.filter(id => !lastOrderIds.includes(id));

            console.log('[Live Update] Current IDs:', currentIds);
            console.log('[Live Update] Last IDs:', lastOrderIds);
            console.log('[Live Update] New IDs:', newOrderIds);

            if (newOrderIds.length > 0) {
                // Show toast for new orders
                newOrderIds.forEach(id => {
                    const order = currentOrders.find(o => o.id === id);
                    if (order) {
                        showToast('Nouvelle commande', `Chambre ${order.room_number} - ${order.total_amount} €`);
                    }
                });
                // Update table
                updateOrdersTable(currentOrders);
            }

            // Update count badge
            const countBadge = document.querySelector('.card-header .badge');
            if (countBadge) {
                countBadge.textContent = data.data.orderCount + ' commandes';
            }

            // Check for new messages (cross-notification)
            const currentUnreadMessages = data.data.unreadMessages || 0;
            if (currentUnreadMessages > lastUnreadMessages) {
                const newCount = currentUnreadMessages - lastUnreadMessages;
                showToast('Nouveau message', `${newCount} nouveau${newCount > 1 ? 'x' : ''} message${newCount > 1 ? 's' : ''} client`, 'message');
            }
            lastUnreadMessages = currentUnreadMessages;

            lastOrderCount = data.data.orderCount;
            lastOrderIds = currentIds;

        } catch (e) {
            console.error('Polling error:', e);
        }
    }

    // Update badge counts
    function updateBadge(type, count) {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            if (type === 'pendingOrders' && item.href.includes('room-service-orders')) {
                let badge = item.querySelector('.badge');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge';
                        badge.style.cssText = 'background: #E53E3E; color: white; margin-left: auto;';
                        item.appendChild(badge);
                    }
                    badge.textContent = count;
                } else if (badge) {
                    badge.remove();
                }
            }
            if (type === 'unreadMessages' && item.href.includes('room-service-messages')) {
                let badge = item.querySelector('.badge');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge';
                        badge.style.cssText = 'background: #E53E3E; color: white; margin-left: auto;';
                        item.appendChild(badge);
                    }
                    badge.textContent = count;
                } else if (badge) {
                    badge.remove();
                }
            }
        });
    }

    // Update stats cards
    function updateStats(stats) {
        const statValues = document.querySelectorAll('.stat-value');
        if (statValues.length >= 4) {
            statValues[0].textContent = stats.total_orders;
            statValues[1].textContent = stats.pending_orders;
            statValues[2].textContent = stats.today_orders;
            statValues[3].textContent = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(stats.today_revenue) + ' €';
        }
    }

    // Update orders table
    function updateOrdersTable(orders) {
        const tbody = document.querySelector('.orders-table tbody');
        if (!tbody) return;

        // Clear and rebuild table
        tbody.innerHTML = orders.map(order => {
            const isUrgent = order.is_urgent;
            const isPast = order.is_past;
            const isNew = !lastOrderIds.includes(order.id);
            const rowStyle = isUrgent ? 'background: rgba(237, 137, 54, 0.05);' : (isPast ? 'background: rgba(245, 101, 101, 0.05);' : '');

            return `
                <tr data-href="?view=${order.id}" style="${rowStyle}" class="${isNew ? 'row-highlight' : ''}">
                    <td>${order.id}</td>
                    <td><strong>${escapeHtml(order.room_number)}</strong></td>
                    <td>${escapeHtml(order.guest_name)}</td>
                    <td><span class="price">${order.total_amount} €</span></td>
                    <td>
                        <span style="${isUrgent ? 'color: #C05621; font-weight: 600;' : (isPast ? 'color: #C53030; font-weight: 600;' : '')}">
                            ${order.delivery_datetime}
                        </span>
                        ${isUrgent ? '<span style="display: block; font-size: 0.7rem; color: #C05621;">Bientôt</span>' : ''}
                        ${isPast ? '<span style="display: block; font-size: 0.7rem; color: #C53030;">En retard</span>' : ''}
                    </td>
                    <td>
                        <span class="status-badge status-${order.status}">
                            ${escapeHtml(order.status_label)}
                        </span>
                    </td>
                    <td>${order.created_at}</td>
                    <td>
                        <a href="?view=${order.id}" class="btn btn-sm btn-outline">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Détails
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // HTML escape utility
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Visibility API for pause/resume
    document.addEventListener('visibilitychange', function() {
        isPageVisible = !document.hidden;
        if (isPageVisible) {
            fetchUpdates(); // Immediate update when tab becomes visible
            startPolling();
        } else {
            stopPolling();
        }
    });

    function startPolling() {
        if (!pollTimer && isPageVisible) {
            pollTimer = setInterval(fetchUpdates, POLL_INTERVAL);
        }
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    // Start polling only on list view (not detail view)
    <?php if (!$viewOrder): ?>
    console.log('[Live Update] Starting polling with interval:', POLL_INTERVAL, 'ms');
    console.log('[Live Update] Initial order IDs:', lastOrderIds);
    startPolling();
    // Also fetch immediately on page load
    fetchUpdates();
    <?php endif; ?>
    </script>
</body>
</html>
