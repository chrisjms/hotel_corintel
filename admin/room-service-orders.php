<?php
/**
 * Room Service Orders Management
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
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
        .orders-table tr:hover {
            background: var(--admin-bg);
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
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
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
                <a href="images.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    Gestion des images
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
                <a href="room-service-orders.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    Room Service - Commandes
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
                            <span class="badge"><?= count($orders) ?> commandes</span>
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
                                            <tr<?= $isUrgent ? ' style="background: rgba(237, 137, 54, 0.05);"' : ($isPast ? ' style="background: rgba(245, 101, 101, 0.05);"' : '') ?>>
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
</body>
</html>
