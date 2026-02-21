<?php
/**
 * Room Service Statistics
 * Hotel Corintel - Admin Panel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$hotelName = getHotelName();

// Get selected period from query params
$period = $_GET['period'] ?? 'day';
$validPeriods = ['day', 'week', 'month', 'year'];
if (!in_array($period, $validPeriods)) {
    $period = 'day';
}

// Fetch all statistics
try {
    $periodStats = getRoomServicePeriodStats($period);
    $financialStats = getRoomServiceFinancialStats($period);
    $dailyRevenue = getRoomServiceDailyRevenue(30);
    $weeklyRevenue = getRoomServiceWeeklyRevenue(12);
    $monthlyRevenue = getRoomServiceMonthlyRevenue(12);
    $yearlyRevenue = getRoomServiceYearlyRevenue(); // Current year monthly breakdown
    $peakHours = getRoomServicePeakHours(30);
    $peakDays = getRoomServicePeakDays(8);
    $topItems = getRoomServiceTopItems(10, 30);
    $categoryRevenue = getRoomServiceRevenueByCategory(30);
    $paymentBreakdown = getRoomServicePaymentBreakdown(30);
    $statusBreakdown = getRoomServiceStatusBreakdown(30);
    $topRooms = getRoomServiceTopRooms(5, 30);
    $bestDay = getRoomServiceBestPeriod('day', 30);
    $bestWeek = getRoomServiceBestPeriod('week', 12);
    $bestMonth = getRoomServiceBestPeriod('month', 12);
    $defaultVatRate = getDefaultVatRate();
    $statsEnabled = true;
} catch (PDOException $e) {
    $statsEnabled = false;
}

// Guest Messages Statistics
$msgByRoom = [];
$msgByCategory = [];
$msgTotalCount = 0;
$msgThisMonth = 0;
$msgLastMonth = 0;
$msgStatsEnabled = false;

try {
    $pdo = getDatabase();

    // Messages by room (top 5, last 30 days)
    $stmtByRoom = $pdo->prepare("
        SELECT room_number, COUNT(*) as msg_count
        FROM guest_messages
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY room_number
        ORDER BY msg_count DESC
        LIMIT 5
    ");
    $stmtByRoom->execute();
    $msgByRoom = $stmtByRoom->fetchAll(PDO::FETCH_ASSOC);

    // Messages by category (last 30 days)
    $stmtByCategory = $pdo->prepare("
        SELECT category, COUNT(*) as msg_count
        FROM guest_messages
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY category
        ORDER BY msg_count DESC
    ");
    $stmtByCategory->execute();
    $msgByCategory = $stmtByCategory->fetchAll(PDO::FETCH_ASSOC);

    // Total messages (last 30 days)
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM guest_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmtTotal->execute();
    $msgTotalCount = (int)$stmtTotal->fetchColumn();

    // This month vs last month comparison
    $stmtThisMonth = $pdo->prepare("SELECT COUNT(*) FROM guest_messages WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stmtThisMonth->execute();
    $msgThisMonth = (int)$stmtThisMonth->fetchColumn();

    $stmtLastMonth = $pdo->prepare("SELECT COUNT(*) FROM guest_messages WHERE MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))");
    $stmtLastMonth->execute();
    $msgLastMonth = (int)$stmtLastMonth->fetchColumn();

    $msgStatsEnabled = true;
} catch (PDOException $e) {
    $msgStatsEnabled = false;
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

$periodLabels = [
    'day' => "Aujourd'hui",
    'week' => 'Cette semaine',
    'month' => 'Ce mois',
    'year' => 'Cette année'
];

$comparisonLabels = [
    'day' => 'vs hier',
    'week' => 'vs semaine dernière',
    'month' => 'vs mois dernier',
    'year' => 'vs année dernière'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Statistiques Room Service | Admin <?= h($hotelName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .period-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .period-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--admin-border);
            background: var(--admin-card);
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
            text-decoration: none;
            color: var(--admin-text);
        }
        .period-btn:hover {
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }
        .period-btn.active {
            background: var(--admin-primary);
            border-color: var(--admin-primary);
            color: white;
        }
        .stats-grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card-enhanced {
            background: var(--admin-card);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--admin-shadow);
            border: 1px solid var(--admin-border);
        }
        .stat-card-enhanced .stat-label {
            font-size: 0.8rem;
            color: var(--admin-text-light);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card-enhanced .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--admin-text);
            margin-bottom: 0.25rem;
        }
        .stat-card-enhanced .stat-change {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .stat-change.positive {
            color: var(--admin-success);
        }
        .stat-change.negative {
            color: var(--admin-error);
        }
        .stat-change.neutral {
            color: var(--admin-text-light);
        }
        .stat-change svg {
            width: 14px;
            height: 14px;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        .chart-card {
            background: var(--admin-card);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--admin-shadow);
            border: 1px solid var(--admin-border);
        }
        .chart-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--admin-text);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .chart-container-small {
            position: relative;
            height: 200px;
        }
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .table-card {
            background: var(--admin-card);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--admin-shadow);
            border: 1px solid var(--admin-border);
        }
        .table-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--admin-text);
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-table th,
        .stats-table td {
            padding: 0.75rem 0.5rem;
            text-align: left;
            border-bottom: 1px solid var(--admin-border);
            font-size: 0.875rem;
            color: var(--admin-text);
        }
        .stats-table th {
            font-weight: 600;
            color: var(--admin-text-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stats-table tr:last-child td {
            border-bottom: none;
        }
        .stats-table .text-right {
            text-align: right;
        }
        .stats-table .rank {
            color: var(--admin-text-light);
            font-weight: 500;
        }
        .insight-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .insight-card {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            border-radius: 12px;
            padding: 1.25rem;
            color: white;
        }
        .insight-card .insight-label {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        .insight-card .insight-value {
            font-size: 1.25rem;
            font-weight: 700;
        }
        .insight-card .insight-detail {
            font-size: 0.85rem;
            opacity: 0.85;
            margin-top: 0.25rem;
        }
        .chart-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .chart-tab {
            padding: 0.375rem 0.75rem;
            border: none;
            background: var(--admin-bg);
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            color: var(--admin-text);
            transition: all 0.2s;
        }
        .chart-tab:hover {
            background: var(--admin-border);
        }
        .chart-tab.active {
            background: var(--admin-primary);
            color: white;
        }
        .no-data {
            text-align: center;
            padding: 3rem;
            color: var(--admin-text-light);
        }
        .no-data svg {
            width: 48px;
            height: 48px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .progress-bar-container {
            background: var(--admin-bg);
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: var(--admin-primary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .section-divider {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 2.5rem 0 1.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--admin-border);
        }
        .section-title-divider {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--admin-text);
            margin: 0;
        }
        .stats-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        /* Financial Stats Styles */
        .financial-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
        }
        .financial-card h3 {
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 0.75rem;
        }
        .financial-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .financial-item {
            text-align: center;
        }
        .financial-item .label {
            font-size: 0.75rem;
            opacity: 0.85;
            margin-bottom: 0.25rem;
        }
        .financial-item .value {
            font-size: 1.25rem;
            font-weight: 700;
        }
        .financial-item .value.small {
            font-size: 1rem;
        }
        .vat-breakdown-card {
            background: white;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            padding: 1.25rem;
        }
        .vat-breakdown-card h4 {
            font-size: 0.875rem;
            color: var(--admin-text);
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .vat-breakdown-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--admin-bg);
        }
        .vat-breakdown-row:last-child {
            border-bottom: none;
        }
        .vat-breakdown-row .category {
            font-size: 0.875rem;
            color: var(--admin-text);
        }
        .vat-breakdown-row .rate {
            font-size: 0.75rem;
            color: var(--admin-text-light);
            padding: 2px 6px;
            background: var(--admin-bg);
            border-radius: 3px;
        }
        .vat-breakdown-row .amounts {
            text-align: right;
            font-size: 0.875rem;
        }
        .vat-breakdown-row .ttc {
            font-weight: 600;
            color: var(--admin-primary);
        }
        .vat-breakdown-row .ht-vat {
            font-size: 0.75rem;
            color: var(--admin-text-light);
        }
        @media (max-width: 768px) {
            .stats-grid-3 {
                grid-template-columns: 1fr;
            }
            .financial-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .section-divider {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
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

                <div class="nav-separator">Service</div>
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
                <a href="room-service-categories.php" class="nav-item">
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
                <a href="room-service-stats.php" class="nav-item active">
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
                <h1>Statistiques Room Service</h1>
                <a href="room-service-orders.php" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    Voir les commandes
                </a>
            </header>

            <div class="admin-content">
                <?php if (!$statsEnabled): ?>
                <div class="no-data">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <h3>Statistiques indisponibles</h3>
                    <p>Impossible de charger les statistiques. Vérifiez la connexion à la base de données.</p>
                </div>
                <?php else: ?>

                <!-- Period Selector -->
                <div class="period-selector">
                    <a href="?period=day" class="period-btn <?= $period === 'day' ? 'active' : '' ?>">Aujourd'hui</a>
                    <a href="?period=week" class="period-btn <?= $period === 'week' ? 'active' : '' ?>">Cette semaine</a>
                    <a href="?period=month" class="period-btn <?= $period === 'month' ? 'active' : '' ?>">Ce mois</a>
                    <a href="?period=year" class="period-btn <?= $period === 'year' ? 'active' : '' ?>">Cette année</a>
                </div>

                <!-- Main KPIs -->
                <div class="stats-grid-4">
                    <div class="stat-card-enhanced">
                        <div class="stat-label">Chiffre d'affaires</div>
                        <div class="stat-value"><?= number_format($periodStats['current']['revenue'], 2, ',', ' ') ?> €</div>
                        <div class="stat-change <?= $periodStats['changes']['revenue_percent'] >= 0 ? 'positive' : 'negative' ?>">
                            <?php if ($periodStats['changes']['revenue_percent'] >= 0): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
                            <?php endif; ?>
                            <?= ($periodStats['changes']['revenue_percent'] >= 0 ? '+' : '') . $periodStats['changes']['revenue_percent'] ?>% <?= $comparisonLabels[$period] ?>
                        </div>
                    </div>

                    <div class="stat-card-enhanced">
                        <div class="stat-label">Commandes</div>
                        <div class="stat-value"><?= $periodStats['current']['total_orders'] ?></div>
                        <div class="stat-change <?= $periodStats['changes']['orders_percent'] >= 0 ? 'positive' : 'negative' ?>">
                            <?php if ($periodStats['changes']['orders_percent'] >= 0): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
                            <?php endif; ?>
                            <?= ($periodStats['changes']['orders_percent'] >= 0 ? '+' : '') . $periodStats['changes']['orders_percent'] ?>% <?= $comparisonLabels[$period] ?>
                        </div>
                    </div>

                    <div class="stat-card-enhanced">
                        <div class="stat-label">Panier moyen</div>
                        <div class="stat-value"><?= number_format($periodStats['current']['avg_order_value'], 2, ',', ' ') ?> €</div>
                        <div class="stat-change neutral">
                            Sur <?= $periodStats['current']['total_orders'] ?> commande(s)
                        </div>
                    </div>

                    <div class="stat-card-enhanced">
                        <div class="stat-label">Taux de livraison</div>
                        <div class="stat-value"><?= $periodStats['current']['delivery_rate'] ?>%</div>
                        <div class="stat-change neutral">
                            <?= $periodStats['current']['delivered_orders'] ?> livrée(s), <?= $periodStats['current']['cancelled_orders'] ?> annulée(s)
                        </div>
                    </div>
                </div>

                <!-- Financial Breakdown -->
                <div class="charts-grid" style="margin-bottom: 1.5rem;">
                    <div class="financial-card">
                        <h3>Détail financier (<?= $periodLabels[$period] ?? $period ?>)</h3>
                        <div class="financial-grid">
                            <div class="financial-item">
                                <div class="label">CA TTC</div>
                                <div class="value"><?= number_format($financialStats['current']['revenue_ttc'], 2, ',', ' ') ?> €</div>
                            </div>
                            <div class="financial-item">
                                <div class="label">CA HT</div>
                                <div class="value"><?= number_format($financialStats['current']['revenue_ht'], 2, ',', ' ') ?> €</div>
                            </div>
                            <div class="financial-item">
                                <div class="label">TVA collectée</div>
                                <div class="value"><?= number_format($financialStats['current']['vat_collected'], 2, ',', ' ') ?> €</div>
                            </div>
                        </div>
                    </div>

                    <div class="vat-breakdown-card">
                        <h4>TVA par catégorie</h4>
                        <?php if (empty($financialStats['current']['by_category'])): ?>
                            <p style="color: var(--admin-text-light); font-size: 0.875rem;">Aucune donnée pour cette période</p>
                        <?php else: ?>
                            <?php
                            $categories = getRoomServiceCategories();
                            foreach ($financialStats['current']['by_category'] as $catData):
                            ?>
                            <div class="vat-breakdown-row">
                                <div>
                                    <span class="category"><?= h($categories[$catData['category']] ?? ucfirst($catData['category'])) ?></span>
                                    <span class="rate"><?= number_format($catData['vat_rate'], 1) ?>%</span>
                                </div>
                                <div class="amounts">
                                    <div class="ttc"><?= number_format($catData['total_ttc'], 2, ',', ' ') ?> € TTC</div>
                                    <div class="ht-vat"><?= number_format($catData['total_ht'], 2, ',', ' ') ?> € HT / <?= number_format($catData['total_vat'], 2, ',', ' ') ?> € TVA</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Insights -->
                <div class="insight-cards">
                    <?php if ($bestDay): ?>
                    <div class="insight-card">
                        <div class="insight-label">Meilleur jour (30j)</div>
                        <div class="insight-value"><?= date('d/m/Y', strtotime($bestDay['period_start'])) ?></div>
                        <div class="insight-detail"><?= number_format($bestDay['revenue'], 2, ',', ' ') ?> € - <?= $bestDay['orders'] ?> commandes</div>
                    </div>
                    <?php endif; ?>

                    <div class="insight-card">
                        <div class="insight-label">Heure de pointe</div>
                        <div class="insight-value"><?= $peakHours['peak_hour_label'] ?></div>
                        <div class="insight-detail"><?= $peakHours['data'][$peakHours['peak_hour']]['orders'] ?> commandes sur 30j</div>
                    </div>

                    <div class="insight-card">
                        <div class="insight-label">Jour le plus actif</div>
                        <div class="insight-value"><?= $peakDays['peak_day_name'] ?></div>
                        <div class="insight-detail"><?= $peakDays['data'][$peakDays['peak_day'] - 1]['orders'] ?> commandes sur 8 semaines</div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h3>Évolution du chiffre d'affaires</h3>
                        <div class="chart-tabs">
                            <button class="chart-tab active" data-chart="daily">Quotidien</button>
                            <button class="chart-tab" data-chart="weekly">Hebdomadaire</button>
                            <button class="chart-tab" data-chart="monthly">Mensuel</button>
                            <button class="chart-tab" data-chart="yearly">Annuel</button>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Répartition par statut</h3>
                        <div class="chart-container-small">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Peak Analysis Charts -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h3>Commandes par heure de livraison</h3>
                        <div class="chart-container">
                            <canvas id="peakHoursChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Commandes par jour de la semaine</h3>
                        <div class="chart-container-small">
                            <canvas id="peakDaysChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tables -->
                <div class="tables-grid">
                    <!-- Top Items -->
                    <div class="table-card">
                        <h3>Articles les plus vendus (30j)</h3>
                        <?php if (empty($topItems)): ?>
                            <p style="color: var(--admin-text-light); text-align: center; padding: 2rem;">Aucune donnée disponible</p>
                        <?php else: ?>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Article</th>
                                    <th class="text-right">Qté</th>
                                    <th class="text-right">CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topItems as $i => $item): ?>
                                <tr>
                                    <td class="rank"><?= $i + 1 ?></td>
                                    <td><?= h($item['item_name']) ?></td>
                                    <td class="text-right"><?= $item['total_quantity'] ?></td>
                                    <td class="text-right"><?= number_format($item['total_revenue'], 2, ',', ' ') ?> €</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>

                    <!-- Revenue by Category -->
                    <div class="table-card">
                        <h3>CA par catégorie (30j)</h3>
                        <?php if (empty($categoryRevenue)): ?>
                            <p style="color: var(--admin-text-light); text-align: center; padding: 2rem;">Aucune donnée disponible</p>
                        <?php else: ?>
                        <?php
                            $totalCatRevenue = array_sum(array_column($categoryRevenue, 'total_revenue'));
                        ?>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th class="text-right">CA</th>
                                    <th style="width: 100px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryRevenue as $cat): ?>
                                <?php $percent = $totalCatRevenue > 0 ? ($cat['total_revenue'] / $totalCatRevenue) * 100 : 0; ?>
                                <tr>
                                    <td><?= h($cat['category_name']) ?></td>
                                    <td class="text-right"><?= number_format($cat['total_revenue'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>

                    <!-- Payment Methods -->
                    <div class="table-card">
                        <h3>Modes de paiement (30j)</h3>
                        <?php if (empty($paymentBreakdown)): ?>
                            <p style="color: var(--admin-text-light); text-align: center; padding: 2rem;">Aucune donnée disponible</p>
                        <?php else: ?>
                        <?php
                            $totalPayRevenue = array_sum(array_column($paymentBreakdown, 'total_revenue'));
                        ?>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Mode</th>
                                    <th class="text-right">Commandes</th>
                                    <th class="text-right">CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentBreakdown as $pay): ?>
                                <tr>
                                    <td><?= h($pay['method_name']) ?></td>
                                    <td class="text-right"><?= $pay['order_count'] ?></td>
                                    <td class="text-right"><?= number_format($pay['total_revenue'], 2, ',', ' ') ?> €</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>

                    <!-- Top Rooms -->
                    <div class="table-card">
                        <h3>Chambres les plus actives (30j)</h3>
                        <?php if (empty($topRooms)): ?>
                            <p style="color: var(--admin-text-light); text-align: center; padding: 2rem;">Aucune donnée disponible</p>
                        <?php else: ?>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Chambre</th>
                                    <th class="text-right">Commandes</th>
                                    <th class="text-right">CA total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topRooms as $room): ?>
                                <tr>
                                    <td><strong><?= h($room['room_number']) ?></strong></td>
                                    <td class="text-right"><?= $room['order_count'] ?></td>
                                    <td class="text-right"><?= number_format($room['total_revenue'], 2, ',', ' ') ?> €</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>

                <?php endif; ?>

                <!-- Messages Analytics Section -->
                <?php if ($msgStatsEnabled): ?>
                <div class="section-divider">
                    <h2 class="section-title-divider">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        Messages Clients
                    </h2>
                    <a href="room-service-messages.php" class="btn btn-sm btn-outline">Voir les messages</a>
                </div>

                <!-- Message KPIs -->
                <div class="stats-grid-3">
                    <div class="stat-card-enhanced">
                        <div class="stat-label">Messages (30j)</div>
                        <div class="stat-value"><?= $msgTotalCount ?></div>
                    </div>
                    <div class="stat-card-enhanced">
                        <div class="stat-label">Ce mois</div>
                        <div class="stat-value"><?= $msgThisMonth ?></div>
                        <?php
                            $msgChange = $msgLastMonth > 0 ? round((($msgThisMonth - $msgLastMonth) / $msgLastMonth) * 100) : ($msgThisMonth > 0 ? 100 : 0);
                        ?>
                        <div class="stat-change <?= $msgChange > 0 ? 'negative' : ($msgChange < 0 ? 'positive' : 'neutral') ?>">
                            <?php if ($msgChange > 0): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                            <?php elseif ($msgChange < 0): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
                            <?php endif; ?>
                            <?= ($msgChange >= 0 ? '+' : '') . $msgChange ?>% vs mois dernier
                        </div>
                    </div>
                    <div class="stat-card-enhanced">
                        <div class="stat-label">Non lus</div>
                        <div class="stat-value" style="<?= $unreadMessages > 0 ? 'color: #E53E3E;' : '' ?>"><?= $unreadMessages ?></div>
                    </div>
                </div>

                <!-- Message Tables -->
                <div class="tables-grid">
                    <!-- Messages by Room -->
                    <div class="table-card">
                        <h3>Messages par chambre (30j)</h3>
                        <?php if (empty($msgByRoom)): ?>
                            <p style="color: var(--admin-text-light); text-align: center; padding: 2rem;">Aucune donnée disponible</p>
                        <?php else: ?>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Chambre</th>
                                    <th class="text-right">Messages</th>
                                    <th style="width: 100px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($msgByRoom as $room): ?>
                                <?php $roomPercent = $msgTotalCount > 0 ? ($room['msg_count'] / $msgTotalCount) * 100 : 0; ?>
                                <tr>
                                    <td><strong><?= h($room['room_number']) ?></strong></td>
                                    <td class="text-right"><?= $room['msg_count'] ?></td>
                                    <td>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar" style="width: <?= $roomPercent ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>

                    <!-- Messages by Category -->
                    <div class="table-card">
                        <h3>Messages par catégorie (30j)</h3>
                        <?php if (empty($msgByCategory)): ?>
                            <p style="color: var(--admin-text-light); text-align: center; padding: 2rem;">Aucune donnée disponible</p>
                        <?php else: ?>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th class="text-right">Messages</th>
                                    <th style="width: 100px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($msgByCategory as $cat): ?>
                                <?php $catPercent = $msgTotalCount > 0 ? ($cat['msg_count'] / $msgTotalCount) * 100 : 0; ?>
                                <tr>
                                    <td><?= h($msgCategoryLabels[$cat['category']] ?? $cat['category']) ?></td>
                                    <td class="text-right"><?= $cat['msg_count'] ?></td>
                                    <td>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar" style="width: <?= $catPercent ?>%"></div>
                                        </div>
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

    <?php if ($statsEnabled): ?>
    <script>
        // Chart.js default configuration
        Chart.defaults.font.family = "'Lato', sans-serif";
        Chart.defaults.color = '#4A5568';

        const primaryColor = '#8B5A2B';
        const primaryColorLight = 'rgba(139, 90, 43, 0.1)';
        const successColor = '#48BB78';
        const errorColor = '#F56565';
        const warningColor = '#ED8936';
        const infoColor = '#4299E1';

        // Revenue data
        const dailyData = <?= json_encode($dailyRevenue) ?>;
        const weeklyData = <?= json_encode($weeklyRevenue) ?>;
        const monthlyData = <?= json_encode($monthlyRevenue) ?>;
        const yearlyData = <?= json_encode($yearlyRevenue) ?>;

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        let revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.label),
                datasets: [{
                    label: 'Chiffre d\'affaires (€)',
                    data: dailyData.map(d => d.revenue),
                    borderColor: primaryColor,
                    backgroundColor: primaryColorLight,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' €';
                            }
                        }
                    }
                }
            }
        });

        // Chart tabs functionality
        document.querySelectorAll('.chart-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const chartType = this.dataset.chart;
                let data, labels;

                if (chartType === 'daily') {
                    labels = dailyData.map(d => d.label);
                    data = dailyData.map(d => d.revenue);
                } else if (chartType === 'weekly') {
                    labels = weeklyData.map(d => d.label);
                    data = weeklyData.map(d => d.revenue);
                } else if (chartType === 'yearly') {
                    // Yearly view: 12 months (Jan-Dec) for current year
                    labels = yearlyData.map(d => d.label);
                    data = yearlyData.map(d => d.revenue);
                } else {
                    // Monthly (default)
                    labels = monthlyData.map(d => d.label);
                    data = monthlyData.map(d => d.revenue);
                }

                revenueChart.data.labels = labels;
                revenueChart.data.datasets[0].data = data;
                revenueChart.update();
            });
        });

        // Status Chart
        const statusData = <?= json_encode($statusBreakdown) ?>;
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(s => s.status_name),
                datasets: [{
                    data: statusData.map(s => s.order_count),
                    backgroundColor: [
                        warningColor,  // pending
                        infoColor,     // confirmed
                        primaryColor,  // preparing
                        successColor,  // delivered
                        errorColor     // cancelled
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true }
                    }
                }
            }
        });

        // Peak Hours Chart
        const peakHoursData = <?= json_encode($peakHours['data']) ?>;
        const peakHoursCtx = document.getElementById('peakHoursChart').getContext('2d');
        new Chart(peakHoursCtx, {
            type: 'bar',
            data: {
                labels: peakHoursData.map(h => h.label),
                datasets: [{
                    label: 'Commandes',
                    data: peakHoursData.map(h => h.orders),
                    backgroundColor: primaryColor,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Peak Days Chart
        const peakDaysData = <?= json_encode($peakDays['data']) ?>;
        const peakDaysCtx = document.getElementById('peakDaysChart').getContext('2d');
        new Chart(peakDaysCtx, {
            type: 'bar',
            data: {
                labels: peakDaysData.map(d => d.label),
                datasets: [{
                    label: 'Commandes',
                    data: peakDaysData.map(d => d.orders),
                    backgroundColor: primaryColor,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
    <?php endif; ?>

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
