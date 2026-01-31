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
    <title>Tableau de bord | Admin Hôtel Corintel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
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
                <a href="index.php" class="nav-item active">
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
                                    <span class="stat-value"><?= $rsTodayTotal ?></span>
                                    <span class="stat-label">Commandes aujourd'hui</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-content">
                                    <span class="stat-value"><?= $rsUpcomingCount ?></span>
                                    <span class="stat-label">Livraisons à venir</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-content">
                                    <span class="stat-value"><?= $rsStatusCounts['pending'] ?></span>
                                    <span class="stat-label">En attente</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-content">
                                    <span class="stat-value"><?= $rsStatusCounts['preparing'] ?></span>
                                    <span class="stat-label">En préparation</span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($rsUrgentOrders)): ?>
                        <h3 style="margin: 1.5rem 0 1rem; font-size: 1rem;">Commandes urgentes</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Chambre</th>
                                    <th>Livraison prévue</th>
                                    <th>Statut</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rsUrgentOrders as $order): ?>
                                <tr>
                                    <td><strong><?= h($order['room_number']) ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['delivery_datetime'])) ?></td>
                                    <td><span class="status-badge status-<?= $order['status'] ?>"><?= $statusLabels[$order['status']] ?></span></td>
                                    <td><a href="room-service-orders.php?id=<?= $order['id'] ?>" class="btn btn-sm">Voir</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p style="margin-top: 1rem; color: #666;">Aucune commande urgente.</p>
                        <?php endif; ?>
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
                                    <span class="stat-value"><?= $msgTodayTotal ?></span>
                                    <span class="stat-label">Messages aujourd'hui</span>
                                </div>
                            </div>
                            <div class="stat-card <?= $msgTodayNew > 0 ? 'stat-card-alert' : '' ?>">
                                <div class="stat-content">
                                    <span class="stat-value"><?= $msgTodayNew ?></span>
                                    <span class="stat-label">Non lus</span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($msgRecentMessages)): ?>
                        <h3 style="margin: 1.5rem 0 1rem; font-size: 1rem;">Messages récents</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Chambre</th>
                                    <th>Sujet</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($msgRecentMessages as $msg): ?>
                                <tr class="<?= $msg['status'] === 'new' ? 'row-unread' : '' ?>">
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
                                    <td><a href="room-service-messages.php?id=<?= $msg['id'] ?>" class="btn btn-sm">Voir</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p style="margin-top: 1rem; color: #666;">Aucun message récent.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script>
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
