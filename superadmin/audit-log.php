<?php
/**
 * Super Admin - Audit Log
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';
require_once __DIR__ . '/includes/super-functions.php';

superRequireAuth();

$currentPage = 'audit-log.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$totalLogs = countAuditLogs();
$totalPages = max(1, ceil($totalLogs / $perPage));
$logs = getAuditLog($perPage, $offset);

$actionLabels = [
    'cross_login'   => 'Connexion croisée',
    'hotel_created' => 'Hôtel créé',
    'hotel_updated' => 'Hôtel modifié',
    'hotel_deleted' => 'Hôtel supprimé',
    'login'         => 'Connexion',
    'logout'        => 'Déconnexion',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Journal d'audit | Super Admin</title>
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
                <h1>Journal d'audit</h1>
                <span style="color: var(--sa-text-light); font-size: 0.875rem;"><?= $totalLogs ?> entrées</span>
            </header>

            <div class="sa-content">
                <?php if (empty($logs)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <p>Aucune entrée dans le journal</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Utilisateur</th>
                                        <th>Action</th>
                                        <th>Hôtel</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td style="white-space: nowrap;"><?= htmlspecialchars($log['created_at']) ?></td>
                                            <td><?= htmlspecialchars($log['admin_username'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge" style="background: rgba(66,153,225,0.15); color: var(--sa-primary);">
                                                    <?= htmlspecialchars($actionLabels[$log['action']] ?? $log['action']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($log['hotel_name'] ?? '-') ?></td>
                                            <td style="font-family: monospace; font-size: 0.8rem;"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="btn btn-outline btn-sm">Précédent</a>
                            <?php endif; ?>
                            <span style="color: var(--sa-text-light); font-size: 0.875rem;">
                                Page <?= $page ?> / <?= $totalPages ?>
                            </span>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="btn btn-outline btn-sm">Suivant</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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
