<?php
/**
 * Admin Sidebar (shared across all admin pages)
 *
 * Expected variables from including page:
 *   $currentPage   - filename of the current page (e.g. 'index.php')
 *   $admin         - current admin array (from getCurrentAdmin())
 *   $pendingOrders - int
 *   $unreadMessages - int
 *   $hotelName     - string
 */

$sidebarRole = getCurrentAdminRole();
$isAdmin = ($sidebarRole === 'admin');

// Navigation structure: each group has a label and items
// Items with 'permission' are filtered by role
$navGroups = [
    [
        'separator' => null,
        'items' => [
            ['href' => 'index.php', 'label' => 'Tableau de bord', 'permission' => 'dashboard',
             'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
        ]
    ],
    [
        'separator' => 'Activité',
        'items' => [
            ['href' => 'room-service-orders.php', 'label' => 'Commandes', 'permission' => 'orders',
             'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
             'badge' => $pendingOrders, 'badge_id' => 'badgePendingOrders'],
            ['href' => 'room-service-messages.php', 'label' => 'Messages', 'permission' => 'messages',
             'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
             'badge' => $unreadMessages, 'badge_id' => 'badgeUnreadMessages'],
        ]
    ],
    [
        'separator' => 'Room Service',
        'items' => [
            ['href' => 'room-service-categories.php', 'label' => 'Catégories', 'permission' => 'content',
             'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
            ['href' => 'room-service-items.php', 'label' => 'Articles', 'permission' => 'content',
             'icon' => '<path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>'],
            ['href' => 'room-service-stats.php', 'label' => 'Statistiques', 'permission' => 'stats',
             'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
        ]
    ],
    [
        'separator' => 'Contenu',
        'items' => [
            ['href' => 'content.php?tab=general', 'label' => 'Général', 'permission' => 'content', 'match' => 'content-general',
             'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
            ['href' => 'content.php', 'label' => 'Sections', 'permission' => 'content', 'match' => 'content-sections',
             'icon' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>'],
            ['href' => 'theme.php', 'label' => 'Thème', 'permission' => 'content',
             'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 0 0 20"/><path d="M12 2c-2.5 2.5-4 6-4 10s1.5 7.5 4 10"/>'],
        ]
    ],
    [
        'separator' => 'Administration',
        'items' => [
            ['href' => 'rooms.php', 'label' => 'Chambres', 'permission' => 'rooms',
             'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><rect x="9" y="13" width="6" height="9"/>'],
            ['href' => 'users.php', 'label' => 'Utilisateurs', 'permission' => 'settings',
             'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ['href' => 'settings.php', 'label' => 'Paramètres', 'permission' => 'settings',
             'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
        ]
    ],
];

// Determine active state for each item
function isNavItemActive(array $item, string $currentPage): bool {
    // Special handling for content.php which has two entries (general vs sections)
    if (isset($item['match'])) {
        $sidebarTab = $GLOBALS['sidebarTab'] ?? null;
        if ($item['match'] === 'content-general') {
            return $currentPage === 'content.php' && $sidebarTab === 'general';
        }
        if ($item['match'] === 'content-sections') {
            return $currentPage === 'content.php' && $sidebarTab !== 'general';
        }
    }

    // Extract filename from href (strip query string)
    $hrefFile = strtok($item['href'], '?');
    return $currentPage === $hrefFile;
}
?>
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
<?php foreach ($navGroups as $group):
    // Filter items by permission
    $visibleItems = array_filter($group['items'], function($item) {
        return hasPermission($item['permission']);
    });
    if (empty($visibleItems)) continue;
?>
<?php if ($group['separator']): ?>
                <div class="nav-separator"><?= $group['separator'] ?></div>
<?php endif; ?>
<?php foreach ($visibleItems as $item):
    $active = isNavItemActive($item, $currentPage) ? ' active' : '';
    $badgeHtml = '';
    if (isset($item['badge'])) {
        $count = (int)$item['badge'];
        $id = $item['badge_id'] ?? '';
        $idAttr = $id ? ' id="' . $id . '"' : '';
        $style = $count > 0 ? '' : ' display: none;';
        $badgeHtml = '<span class="badge"' . $idAttr . ' style="background: #E53E3E; color: white; margin-left: auto;' . $style . '">' . $count . '</span>';
    }
?>
                <a href="<?= $item['href'] ?>" class="nav-item<?= $active ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $item['icon'] ?></svg>
                    <?= $item['label'] ?>
                    <?= $badgeHtml ?>
                </a>
<?php endforeach; ?>
<?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-name"><?= h($admin['username']) ?></span>
                    <span style="font-size: 0.7rem; opacity: 0.5; display: block;"><?= ROLE_LABELS[$sidebarRole] ?? $sidebarRole ?></span>
                </div>
                <button class="theme-toggle" onclick="toggleAdminTheme()" title="Changer le thème">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path class="icon-moon" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        <g class="icon-sun"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></g>
                    </svg>
                    <span class="theme-label-light">Mode sombre</span>
                    <span class="theme-label-dark">Mode clair</span>
                </button>
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
