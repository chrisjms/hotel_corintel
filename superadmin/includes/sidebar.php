<?php
/**
 * Super Admin Sidebar
 *
 * Expected variables:
 *   $currentPage - filename of the current page
 */
?>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <aside class="sa-sidebar" id="saSidebar">
            <button class="sidebar-close" id="sidebarClose" aria-label="Fermer le menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <div class="sidebar-header">
                <h2>Platform Admin</h2>
                <span>Super Administration</span>
            </div>

            <nav class="sidebar-nav">
                <?php $sidebarType = $_GET['type'] ?? 'hotel'; ?>
                <a href="index.php?type=hotel" class="nav-item<?= $currentPage === 'index.php' && $sidebarType === 'hotel' ? ' active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Hôtels
                </a>
                <a href="index.php?type=pizzeria" class="nav-item<?= $currentPage === 'index.php' && $sidebarType === 'pizzeria' ? ' active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/>
                    </svg>
                    Pizzerias
                </a>
                <div class="sa-nav-separator">Outils</div>
                <a href="analytics.php" class="nav-item<?= $currentPage === 'analytics.php' ? ' active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Analytiques
                </a>
                <a href="monitoring.php" class="nav-item<?= $currentPage === 'monitoring.php' ? ' active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    Monitoring
                </a>
                <a href="db-health.php" class="nav-item<?= $currentPage === 'db-health.php' ? ' active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                    </svg>
                    Santé DB
                </a>
                <a href="feature-toggles.php" class="nav-item<?= $currentPage === 'feature-toggles.php' ? ' active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="5" width="22" height="14" rx="7" ry="7"/><circle cx="16" cy="12" r="3"/>
                    </svg>
                    Features
                </a>
                <a href="audit-log.php" class="nav-item<?= $currentPage === 'audit-log.php' ? ' active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Journal d'audit
                </a>
                <a href="settings.php" class="nav-item<?= $currentPage === 'settings.php' ? ' active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.32 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Paramètres
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['super_admin_username'] ?? '') ?></span>
                    <span style="font-size: 0.7rem; opacity: 0.5; display: block;">Super Admin</span>
                </div>
                <button class="theme-toggle" onclick="toggleTheme()" title="Changer le thème">
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
