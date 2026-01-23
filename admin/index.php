<?php
/**
 * Admin Dashboard
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$stats = getImageStats();
$sections = getAllSections();
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
        <!-- Sidebar -->
        <aside class="admin-sidebar">
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
                <a href="room-service-orders.php" class="nav-item">
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
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['total'] ?></span>
                            <span class="stat-label">Images totales</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= count($sections) ?></span>
                            <span class="stat-label">Sections</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['recent_updates'] ?></span>
                            <span class="stat-label">Mises à jour (7j)</span>
                        </div>
                    </div>
                </div>

                <!-- Sections Overview -->
                <div class="card">
                    <div class="card-header">
                        <h2>Sections du site</h2>
                        <a href="images.php" class="btn btn-sm">Gérer les images</a>
                    </div>
                    <div class="card-body">
                        <div class="sections-grid">
                            <?php foreach ($sections as $key => $name): ?>
                                <a href="images.php?section=<?= h($key) ?>" class="section-card">
                                    <div class="section-icon">
                                        <?php if ($key === 'home'): ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                            </svg>
                                        <?php elseif ($key === 'services'): ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                                <path d="M2 17l10 5 10-5"/>
                                                <path d="M2 12l10 5 10-5"/>
                                            </svg>
                                        <?php elseif ($key === 'activities'): ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="10" r="3"/>
                                                <path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                                <polyline points="22,6 12,13 2,6"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="section-info">
                                        <span class="section-name"><?= h($name) ?></span>
                                        <span class="section-count">
                                            <?= $stats['by_section'][$key] ?? 0 ?> images
                                        </span>
                                    </div>
                                    <svg class="section-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"/>
                                    </svg>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h2>Actions rapides</h2>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="images.php?section=home" class="quick-action">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                                <span>Modifier le carrousel</span>
                            </a>
                            <a href="images.php?section=services" class="quick-action">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                    <path d="M2 17l10 5 10-5"/>
                                    <path d="M2 12l10 5 10-5"/>
                                </svg>
                                <span>Photos des services</span>
                            </a>
                            <a href="settings.php" class="quick-action">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                <span>Changer le mot de passe</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
