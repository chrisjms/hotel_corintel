<?php
/**
 * Super Admin - Global Analytics Dashboard
 * Cross-schema aggregation of orders, revenue, messages, scans
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';
require_once __DIR__ . '/includes/super-functions.php';

superRequireAuth();

$currentPage = 'analytics.php';
$days = (int)($_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90])) $days = 30;

$global = getGlobalAnalytics($days);
$perHotel = getPerHotelAnalytics($days);
$trend = getAnalyticsTrend($days);
$maxOrders = max(1, max(array_column($trend, 'orders')));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Analytiques | Super Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>(function(){if(localStorage.getItem('sa_theme')==='dark')document.documentElement.setAttribute('data-theme','dark')})();</script>
    <link rel="stylesheet" href="super-admin-style.css">
</head>
<body>
    <div class="sa-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="sa-main">
            <header class="sa-header">
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <h1>Analytiques globales</h1>
                <div class="period-selector">
                    <a href="?days=7" class="btn btn-outline btn-sm <?= $days === 7 ? 'active' : '' ?>">7j</a>
                    <a href="?days=30" class="btn btn-outline btn-sm <?= $days === 30 ? 'active' : '' ?>">30j</a>
                    <a href="?days=90" class="btn btn-outline btn-sm <?= $days === 90 ? 'active' : '' ?>">90j</a>
                </div>
            </header>

            <div class="sa-content">
                <!-- Global Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(72, 187, 120, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-success);">
                                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="totalRevenue"><?= number_format($global['total_revenue'], 2, ',', ' ') ?> &euro;</div>
                            <div class="stat-label">Chiffre d'affaires</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(66, 153, 225, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-primary);">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="totalOrders"><?= $global['total_orders'] ?></div>
                            <div class="stat-label">Commandes</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(237, 137, 54, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-warning);">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="totalMessages"><?= $global['total_messages'] ?></div>
                            <div class="stat-label">Messages</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(66, 153, 225, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-primary);">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="totalScans"><?= $global['total_scans'] ?></div>
                            <div class="stat-label">Scans QR</div>
                        </div>
                    </div>
                </div>

                <!-- Orders Trend Chart -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2>Commandes par jour</h2>
                    </div>
                    <div class="card-body">
                        <div class="bar-chart" id="trendChart">
                            <?php foreach ($trend as $t):
                                $pct = $maxOrders > 0 ? round(($t['orders'] / $maxOrders) * 100) : 0;
                                $minHeight = $t['orders'] > 0 ? max($pct, 3) : 0;
                            ?>
                                <div class="bar-chart-bar"
                                     style="height: <?= $minHeight ?>%;"
                                     data-tooltip="<?= htmlspecialchars($t['date']) ?>: <?= $t['orders'] ?> cmd, <?= number_format($t['revenue'], 2, ',', ' ') ?>&euro;"
                                     title="<?= htmlspecialchars($t['date']) ?>: <?= $t['orders'] ?> commandes"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Per-Hotel Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <h2>Détail par établissement</h2>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Établissement</th>
                                    <th style="text-align: right;">Commandes</th>
                                    <th style="text-align: right;">CA</th>
                                    <th style="text-align: right;">Messages</th>
                                    <th style="text-align: right;">Scans QR</th>
                                </tr>
                            </thead>
                            <tbody id="perHotelBody">
                                <?php foreach ($perHotel as $i => $h):
                                    $rank = $i + 1;
                                    $rankStyle = '';
                                    if ($rank === 1) $rankStyle = 'background: rgba(237,196,58,0.15); color: #D4A017;';
                                    elseif ($rank === 2) $rankStyle = 'background: rgba(169,169,169,0.15); color: #808080;';
                                    elseif ($rank === 3) $rankStyle = 'background: rgba(205,127,50,0.15); color: #CD7F32;';
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge" style="<?= $rankStyle ?> font-weight: 700;">
                                                <?= $rank ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($h['hotel_name']) ?></strong>
                                            <?php if (!$h['is_active']): ?>
                                                <span class="badge" style="background: rgba(245,101,101,0.15); color: var(--sa-error); margin-left: 0.5rem;">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; font-family: monospace;"><?= $h['orders'] ?></td>
                                        <td style="text-align: right; font-family: monospace;"><?= number_format($h['revenue'], 2, ',', ' ') ?> &euro;</td>
                                        <td style="text-align: right; font-family: monospace;"><?= $h['messages'] ?></td>
                                        <td style="text-align: right; font-family: monospace;"><?= $h['scans'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($perHotel)): ?>
                                    <tr><td colspan="6" style="text-align: center; color: var(--sa-text-light); padding: 2rem;">Aucune donnée sur cette période</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? '' : 'dark';
        if (next) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('sa_theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.removeItem('sa_theme');
        }
    }

    const menuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('saSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('sidebarClose');
    if (menuBtn) menuBtn.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('open'); });
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
    if (overlay) overlay.addEventListener('click', closeSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    </script>
</body>
</html>
