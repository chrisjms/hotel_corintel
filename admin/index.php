<?php
/**
 * Admin Dashboard
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$hotelName = getHotelName();

// Room Service Statistics
$rsTodayTotal = 0;
$rsStatusCounts = ['pending' => 0, 'confirmed' => 0, 'preparing' => 0, 'delivered' => 0, 'cancelled' => 0];
$rsUpcomingCount = 0;
$rsUrgentOrders = [];
$rsEnabled = false;

try {
    $pdo = getDatabase();
    $today = date('Y-m-d');

    // Total orders today
    $stmtTodayTotal = $pdo->prepare("SELECT COUNT(*) FROM room_service_orders WHERE DATE(created_at) = ?");
    $stmtTodayTotal->execute([$today]);
    $rsTodayTotal = $stmtTodayTotal->fetchColumn();

    // Orders by status
    $stmtByStatus = $pdo->query("SELECT status, COUNT(*) as count FROM room_service_orders WHERE DATE(created_at) = CURDATE() GROUP BY status");
    while ($row = $stmtByStatus->fetch(PDO::FETCH_ASSOC)) {
        $rsStatusCounts[$row['status']] = (int)$row['count'];
    }

    // Upcoming deliveries (not delivered/cancelled, delivery in the future)
    $stmtUpcoming = $pdo->prepare("SELECT COUNT(*) FROM room_service_orders WHERE delivery_datetime >= NOW() AND status NOT IN ('delivered', 'cancelled')");
    $stmtUpcoming->execute();
    $rsUpcomingCount = $stmtUpcoming->fetchColumn();

    // 3 most urgent orders (nearest delivery, not delivered/cancelled)
    $stmtUrgent = $pdo->prepare("SELECT id, room_number, delivery_datetime, status FROM room_service_orders WHERE delivery_datetime >= NOW() AND status NOT IN ('delivered', 'cancelled') ORDER BY delivery_datetime ASC LIMIT 3");
    $stmtUrgent->execute();
    $rsUrgentOrders = $stmtUrgent->fetchAll(PDO::FETCH_ASSOC);

    $rsEnabled = true;
} catch (PDOException $e) {
    // Table doesn't exist yet or other DB error
    $rsEnabled = false;
}

$statusLabels = [
    'pending' => 'En attente',
    'confirmed' => 'Confirmée',
    'preparing' => 'En préparation',
    'delivered' => 'Livrée',
    'cancelled' => 'Annulée'
];

// Guest Messages Statistics
$msgTodayTotal = 0;
$msgTodayNew = 0;
$msgRecentMessages = [];
$msgEnabled = false;

try {
    $pdo = getDatabase();
    $today = date('Y-m-d');

    // Total messages today
    $stmtMsgTotal = $pdo->prepare("SELECT COUNT(*) FROM guest_messages WHERE DATE(created_at) = ?");
    $stmtMsgTotal->execute([$today]);
    $msgTodayTotal = (int)$stmtMsgTotal->fetchColumn();

    // Unread messages today
    $stmtMsgNew = $pdo->prepare("SELECT COUNT(*) FROM guest_messages WHERE DATE(created_at) = ? AND status = 'new'");
    $stmtMsgNew->execute([$today]);
    $msgTodayNew = (int)$stmtMsgNew->fetchColumn();

    // 3 most recent messages
    $stmtRecent = $pdo->prepare("SELECT id, room_number, guest_name, category, subject, message, status, created_at FROM guest_messages ORDER BY created_at DESC LIMIT 3");
    $stmtRecent->execute();
    $msgRecentMessages = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    $msgEnabled = true;
} catch (PDOException $e) {
    // Table doesn't exist yet or other DB error
    $msgEnabled = false;
}

$msgCategoryLabels = [
    'general' => 'Général',
    'room_issue' => 'Problème chambre',
    'housekeeping' => 'Ménage',
    'maintenance' => 'Maintenance',
    'room_service' => 'Room Service',
    'complaint' => 'Réclamation',
    'other' => 'Autre'
];

$msgStatusLabels = [
    'new' => 'Nouveau',
    'read' => 'Lu',
    'in_progress' => 'En cours',
    'resolved' => 'Résolu'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Tableau de bord | Admin <?= h($hotelName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        /* Real-time update styles */
        .value-updated {
            animation: highlight 0.5s ease;
        }
        @keyframes highlight {
            0% { background: rgba(72, 187, 120, 0.3); }
            100% { background: transparent; }
        }
        .row-new {
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Toast notification */
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
            border: 1px solid var(--admin-border);
            border-left: 4px solid #4299E1;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: toastIn 0.3s ease;
            max-width: 350px;
        }
        .toast.toast-order {
            border-left-color: #ED8936;
        }
        .toast.toast-message {
            border-left-color: #4299E1;
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        .toast-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }
        .toast-icon.order { color: #ED8936; }
        .toast-icon.message { color: #4299E1; }
        .toast-content {
            flex: 1;
        }
        .toast-title {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.125rem;
        }
        .toast-text {
            font-size: 0.8rem;
            color: var(--admin-text-light);
        }
        .toast-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--admin-text-light);
            padding: 0.25rem;
        }
        .toast-close:hover {
            color: var(--admin-text);
        }
        /* Clickable table rows */
        .data-table tbody tr[data-href] {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .data-table tbody tr[data-href]:hover {
            background: var(--admin-bg);
        }
        .data-table tbody tr[data-href]:active {
            background: rgba(139, 90, 43, 0.08);
        }
        /* Relative time display */
        .time-relative {
            display: block;
            font-weight: 500;
            color: var(--admin-text);
        }
        .time-exact {
            display: block;
            font-size: 0.75rem;
            color: var(--admin-text-light);
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
                <a href="index.php" class="nav-item active">
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
                    <span class="badge" id="badgePendingOrders" style="background: #E53E3E; color: white; margin-left: auto; <?= $pendingOrders > 0 ? '' : 'display: none;' ?>"><?= $pendingOrders ?></span>
                </a>
                <a href="room-service-messages.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages
                    <span class="badge" id="badgeUnreadMessages" style="background: #E53E3E; color: white; margin-left: auto; <?= $unreadMessages > 0 ? '' : 'display: none;' ?>"><?= $unreadMessages ?></span>
                </a>

                <div class="nav-separator">Room Service</div>
                <a href="room-service-categories.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
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
                <a href="rooms.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <rect x="9" y="13" width="6" height="9"/>
                    </svg>
                    Chambres
                </a>
                <a href="settings.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
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
                <h1>Tableau de bord</h1>
                <a href="../index.html" target="_blank" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    Voir le site
                </a>
            </header>

            <div class="admin-content">
                <!-- Room Service Activity -->
                <?php if ($rsEnabled): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Room Service - Activité du jour</h2>
                        <a href="room-service-orders.php" class="btn btn-sm">Voir les commandes</a>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-content">
                                    <span class="stat-value" id="statOrdersToday"><?= $rsTodayTotal ?></span>
                                    <span class="stat-label">Commandes aujourd'hui</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-content">
                                    <span class="stat-value" id="statUpcomingDeliveries"><?= $rsUpcomingCount ?></span>
                                    <span class="stat-label">Livraisons à venir</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-content">
                                    <span class="stat-value" id="statPendingOrders"><?= $rsStatusCounts['pending'] ?></span>
                                    <span class="stat-label">En attente</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-content">
                                    <span class="stat-value" id="statPreparingOrders"><?= $rsStatusCounts['preparing'] ?></span>
                                    <span class="stat-label">En préparation</span>
                                </div>
                            </div>
                        </div>

                        <h3 style="margin: 1.5rem 0 1rem; font-size: 1rem;">Commandes urgentes</h3>
                        <div id="urgentOrdersContainer">
                        <?php if (!empty($rsUrgentOrders)): ?>
                        <table class="data-table" id="urgentOrdersTable">
                            <thead>
                                <tr>
                                    <th>Chambre</th>
                                    <th>Livraison prévue</th>
                                    <th>Statut</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="urgentOrdersBody">
                                <?php foreach ($rsUrgentOrders as $order): ?>
                                <tr data-order-id="<?= $order['id'] ?>" data-href="room-service-orders.php?view=<?= $order['id'] ?>">
                                    <td><strong><?= h($order['room_number']) ?></strong></td>
                                    <td title="<?= date('d/m/Y H:i', strtotime($order['delivery_datetime'])) ?>">
                                        <span class="time-relative"><?= timeAgo($order['delivery_datetime']) ?></span>
                                        <span class="time-exact"><?= date('H:i', strtotime($order['delivery_datetime'])) ?></span>
                                    </td>
                                    <td><span class="status-badge status-<?= $order['status'] ?>"><?= $statusLabels[$order['status']] ?></span></td>
                                    <td><a href="room-service-orders.php?view=<?= $order['id'] ?>" class="btn btn-sm">Voir</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p id="noUrgentOrders" style="color: #666;">Aucune commande urgente.</p>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages Activity -->
                <?php if ($msgEnabled): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Messages – Activité du jour</h2>
                        <a href="room-service-messages.php" class="btn btn-sm">Voir tous les messages</a>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid stats-grid-2">
                            <div class="stat-card">
                                <div class="stat-content">
                                    <span class="stat-value" id="statMessagesToday"><?= $msgTodayTotal ?></span>
                                    <span class="stat-label">Messages aujourd'hui</span>
                                </div>
                            </div>
                            <div class="stat-card" id="statUnreadCard">
                                <div class="stat-content">
                                    <span class="stat-value" id="statMessagesTodayNew"><?= $msgTodayNew ?></span>
                                    <span class="stat-label">Non lus</span>
                                </div>
                            </div>
                        </div>

                        <h3 style="margin: 1.5rem 0 1rem; font-size: 1rem;">Messages récents</h3>
                        <div id="recentMessagesContainer">
                        <?php if (!empty($msgRecentMessages)): ?>
                        <table class="data-table" id="recentMessagesTable">
                            <thead>
                                <tr>
                                    <th>Chambre</th>
                                    <th>Sujet</th>
                                    <th>Reçu</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="recentMessagesBody">
                                <?php foreach ($msgRecentMessages as $msg): ?>
                                <tr class="<?= $msg['status'] === 'new' ? 'row-unread' : '' ?>" data-msg-id="<?= $msg['id'] ?>" data-href="room-service-messages.php?view=<?= $msg['id'] ?>">
                                    <td><strong><?= h($msg['room_number']) ?></strong></td>
                                    <td>
                                        <?php if ($msg['subject']): ?>
                                            <?= h($msg['subject']) ?>
                                        <?php else: ?>
                                            <span style="color: var(--admin-text-light); font-style: italic;"><?= $msgCategoryLabels[$msg['category']] ?? $msg['category'] ?></span>
                                        <?php endif; ?>
                                        <?php if ($msg['status'] === 'new'): ?>
                                            <span class="badge-new">Nouveau</span>
                                        <?php endif; ?>
                                    </td>
                                    <td title="<?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>">
                                        <span class="time-relative"><?= timeAgo($msg['created_at']) ?></span>
                                    </td>
                                    <td><a href="room-service-messages.php?view=<?= $msg['id'] ?>" class="btn btn-sm">Voir</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p id="noRecentMessages" style="color: #666;">Aucun message récent.</p>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script>
    // Clickable table rows
    document.addEventListener('click', function(e) {
        const row = e.target.closest('.data-table tbody tr[data-href]');
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

    // Real-time updates
    const POLL_INTERVAL = 15000; // 15 seconds
    let lastData = null;
    let pollTimer = null;

    // Toast notification system
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    toastContainer.id = 'toastContainer';
    document.body.appendChild(toastContainer);

    function showToast(type, title, text) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <svg class="toast-icon ${type}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${type === 'order'
                    ? '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>'
                    : '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'}
            </svg>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-text">${text}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;
        toastContainer.appendChild(toast);

        // Auto-remove after 8 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'toastIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }
        }, 8000);
    }

    function updateValue(elementId, newValue) {
        const el = document.getElementById(elementId);
        if (el && el.textContent !== String(newValue)) {
            el.textContent = newValue;
            el.classList.add('value-updated');
            setTimeout(() => el.classList.remove('value-updated'), 500);
        }
    }

    function updateBadge(elementId, count) {
        const el = document.getElementById(elementId);
        if (el) {
            if (count > 0) {
                el.textContent = count;
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        }
    }

    function updateStatCardAlert(elementId, hasAlert) {
        const el = document.getElementById(elementId);
        if (el) {
            if (hasAlert) {
                el.classList.add('stat-card-alert');
            } else {
                el.classList.remove('stat-card-alert');
            }
        }
    }

    function updateUrgentOrders(orders) {
        const container = document.getElementById('urgentOrdersContainer');
        if (!container) return;

        if (orders.length === 0) {
            container.innerHTML = '<p id="noUrgentOrders" style="color: #666;">Aucune commande urgente.</p>';
            return;
        }

        let html = `
            <table class="data-table" id="urgentOrdersTable">
                <thead>
                    <tr>
                        <th>Chambre</th>
                        <th>Livraison prévue</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="urgentOrdersBody">
        `;

        orders.forEach(order => {
            html += `
                <tr data-order-id="${order.id}" data-href="room-service-orders.php?view=${order.id}">
                    <td><strong>${escapeHtml(order.room_number)}</strong></td>
                    <td title="${order.delivery_datetime}">
                        <span class="time-relative">${escapeHtml(order.delivery_relative)}</span>
                        <span class="time-exact">${order.delivery_time}</span>
                    </td>
                    <td><span class="status-badge status-${order.status}">${escapeHtml(order.status_label)}</span></td>
                    <td><a href="room-service-orders.php?view=${order.id}" class="btn btn-sm">Voir</a></td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function updateRecentMessages(messages) {
        const container = document.getElementById('recentMessagesContainer');
        if (!container) return;

        if (messages.length === 0) {
            container.innerHTML = '<p id="noRecentMessages" style="color: #666;">Aucun message récent.</p>';
            return;
        }

        let html = `
            <table class="data-table" id="recentMessagesTable">
                <thead>
                    <tr>
                        <th>Chambre</th>
                        <th>Sujet</th>
                        <th>Reçu</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="recentMessagesBody">
        `;

        messages.forEach(msg => {
            const rowClass = msg.is_new ? 'row-unread' : '';
            const badge = msg.is_new ? '<span class="badge-new">Nouveau</span>' : '';
            html += `
                <tr class="${rowClass}" data-msg-id="${msg.id}" data-href="room-service-messages.php?view=${msg.id}">
                    <td><strong>${escapeHtml(msg.room_number)}</strong></td>
                    <td>${escapeHtml(msg.subject)} ${badge}</td>
                    <td title="${msg.created_at}">
                        <span class="time-relative">${escapeHtml(msg.created_relative)}</span>
                    </td>
                    <td><a href="room-service-messages.php?view=${msg.id}" class="btn btn-sm">Voir</a></td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function checkForNewItems(newData) {
        if (!lastData) return;

        // Check for new orders
        if (newData.ordersToday > lastData.ordersToday) {
            const diff = newData.ordersToday - lastData.ordersToday;
            showToast('order', 'Nouvelle commande', `${diff} nouvelle(s) commande(s) reçue(s)`);
            // Play notification sound if available
            playNotificationSound();
        }

        // Check for new messages
        if (newData.messagesToday > lastData.messagesToday) {
            const diff = newData.messagesToday - lastData.messagesToday;
            showToast('message', 'Nouveau message', `${diff} nouveau(x) message(s) reçu(s)`);
            playNotificationSound();
        }
    }

    function playNotificationSound() {
        // Create a simple beep sound using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            gainNode.gain.value = 0.1;

            oscillator.start();
            setTimeout(() => {
                oscillator.stop();
                audioContext.close();
            }, 150);
        } catch (e) {
            // Audio not supported or blocked
        }
    }

    async function fetchDashboardUpdates() {
        try {
            const response = await fetch('api/dashboard-updates.php');
            if (!response.ok) throw new Error('Network error');

            const result = await response.json();
            if (!result.success) throw new Error(result.error || 'Unknown error');

            const data = result.data;

            // Check for new items before updating
            checkForNewItems(data);

            // Update badge counts
            updateBadge('badgePendingOrders', data.pendingOrders);
            updateBadge('badgeUnreadMessages', data.unreadMessages);

            // Update stats
            updateValue('statOrdersToday', data.ordersToday);
            updateValue('statUpcomingDeliveries', data.upcomingDeliveries);
            updateValue('statPendingOrders', data.orderStatusCounts.pending);
            updateValue('statPreparingOrders', data.orderStatusCounts.preparing);
            updateValue('statMessagesToday', data.messagesToday);
            updateValue('statMessagesTodayNew', data.messagesTodayNew);

            // Update alert styling for unread messages
            updateStatCardAlert('statUnreadCard', data.messagesTodayNew > 0);

            // Update tables
            updateUrgentOrders(data.urgentOrders);
            updateRecentMessages(data.recentMessages);

            // Store for comparison
            lastData = data;

        } catch (error) {
            console.warn('Dashboard update failed:', error.message);
            // Don't show error to user, just continue polling
        }
    }

    // Start polling
    function startPolling() {
        // Initial fetch
        fetchDashboardUpdates();

        // Set up interval
        pollTimer = setInterval(fetchDashboardUpdates, POLL_INTERVAL);
    }

    // Stop polling when page is hidden (save resources)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        } else {
            // Resume polling and fetch immediately
            if (!pollTimer) {
                fetchDashboardUpdates();
                pollTimer = setInterval(fetchDashboardUpdates, POLL_INTERVAL);
            }
        }
    });

    // Initialize
    startPolling();
    </script>
</body>
</html>
