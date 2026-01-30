<?php
/**
 * Room Service Categories Management
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$csrfToken = generateCsrfToken();

// Handle POST requests
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'update_time':
                $code = $_POST['code'] ?? '';
                $timeStart = !empty($_POST['time_start']) ? $_POST['time_start'] : null;
                $timeEnd = !empty($_POST['time_end']) ? $_POST['time_end'] : null;

                // Validate: both must be set or both must be empty
                if (($timeStart && !$timeEnd) || (!$timeStart && $timeEnd)) {
                    $message = 'Veuillez définir à la fois l\'heure de début et de fin, ou laissez les deux vides.';
                    $messageType = 'error';
                } else {
                    if (updateCategoryTimeWindow($code, $timeStart, $timeEnd)) {
                        $message = 'Horaires mis à jour avec succès.';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de la mise à jour.';
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

// Get all categories
$categories = getRoomServiceCategoriesAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Room Service - Catégories | Admin Hôtel Corintel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .categories-table {
            width: 100%;
            border-collapse: collapse;
        }
        .categories-table th,
        .categories-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--admin-border);
        }
        .categories-table th {
            font-weight: 600;
            color: var(--admin-text-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .categories-table tr:hover {
            background: var(--admin-bg);
        }
        .time-inputs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .time-inputs input[type="time"] {
            padding: 0.5rem;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .time-separator {
            color: var(--admin-text-light);
        }
        .availability-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .availability-badge.available {
            background: rgba(72, 187, 120, 0.1);
            color: #276749;
        }
        .availability-badge.unavailable {
            background: rgba(245, 101, 101, 0.1);
            color: #C53030;
        }
        .availability-badge.always {
            background: rgba(66, 153, 225, 0.1);
            color: #2B6CB0;
        }
        .btn-save {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        .info-box {
            background: rgba(66, 153, 225, 0.1);
            border-left: 3px solid #4299E1;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0 4px 4px 0;
        }
        .info-box p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--admin-text);
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
                <a href="room-service-categories.php" class="nav-item active">
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
                </a>
                <a href="room-service-messages.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages Clients
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
                <h1>Room Service - Horaires des catégories</h1>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= h($message) ?>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <p>
                        <strong>Définissez les plages horaires de disponibilité pour chaque catégorie.</strong><br>
                        Les articles d'une catégorie ne seront disponibles que pendant les horaires définis.
                        Laissez les champs vides pour une disponibilité 24h/24.
                    </p>
                </div>

                <!-- Categories Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>Plages horaires par catégorie</h2>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($categories)): ?>
                            <p class="empty-state">Aucune catégorie trouvée. Veuillez exécuter le script d'installation de la base de données.</p>
                        <?php else: ?>
                            <table class="categories-table">
                                <thead>
                                    <tr>
                                        <th>Catégorie</th>
                                        <th>Plage horaire</th>
                                        <th>Statut actuel</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <?php
                                        $availability = getCategoryAvailabilityInfo($category['code']);
                                        $timeStart = $category['time_start'] ? substr($category['time_start'], 0, 5) : '';
                                        $timeEnd = $category['time_end'] ? substr($category['time_end'], 0, 5) : '';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= h($category['name']) ?></strong>
                                                <br><small style="color: var(--admin-text-light);"><?= h($category['code']) ?></small>
                                            </td>
                                            <td>
                                                <form method="POST" class="time-form" id="form-<?= h($category['code']) ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="update_time">
                                                    <input type="hidden" name="code" value="<?= h($category['code']) ?>">
                                                    <div class="time-inputs">
                                                        <input type="time" name="time_start" value="<?= h($timeStart) ?>" placeholder="Début">
                                                        <span class="time-separator">à</span>
                                                        <input type="time" name="time_end" value="<?= h($timeEnd) ?>" placeholder="Fin">
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <?php if (!$timeStart && !$timeEnd): ?>
                                                    <span class="availability-badge always">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <polyline points="12 6 12 12 16 14"/>
                                                        </svg>
                                                        24h/24
                                                    </span>
                                                <?php elseif ($availability['available']): ?>
                                                    <span class="availability-badge available">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                                            <polyline points="20 6 9 17 4 12"/>
                                                        </svg>
                                                        Disponible
                                                    </span>
                                                <?php else: ?>
                                                    <span class="availability-badge unavailable">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <line x1="15" y1="9" x2="9" y2="15"/>
                                                            <line x1="9" y1="9" x2="15" y2="15"/>
                                                        </svg>
                                                        Indisponible
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="submit" form="form-<?= h($category['code']) ?>" class="btn btn-primary btn-save">
                                                    Enregistrer
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="card">
                    <div class="card-header">
                        <h2>Aide</h2>
                    </div>
                    <div class="card-body">
                        <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                            <li><strong>Plage horaire vide</strong> : La catégorie est disponible 24h/24</li>
                            <li><strong>Plage horaire définie</strong> : Les articles ne sont commandables que pendant cette période</li>
                            <li><strong>Validation</strong> : Le système vérifie automatiquement si les articles sont disponibles à l'heure de livraison demandée</li>
                            <li><strong>Exemple</strong> : Petit-déjeuner de 07:00 à 11:00 signifie que les commandes pour cette catégorie ne seront acceptées que si l'heure de livraison est entre 07:00 et 11:00</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
