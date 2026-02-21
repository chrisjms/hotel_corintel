<?php
/**
 * Site Theme Customization
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$csrfToken = generateCsrfToken();
$hotelName = getHotelName();

$message = '';
$messageType = '';

// Initialize settings table
initSettingsTable();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_theme':
                $colors = [
                    'color_primary' => $_POST['color_primary'] ?? '',
                    'color_primary_dark' => $_POST['color_primary_dark'] ?? '',
                    'color_secondary' => $_POST['color_secondary'] ?? '',
                    'color_accent' => $_POST['color_accent'] ?? '',
                    'color_accent_light' => $_POST['color_accent_light'] ?? '',
                    'color_cream' => $_POST['color_cream'] ?? '',
                    'color_beige' => $_POST['color_beige'] ?? '',
                    'color_text' => $_POST['color_text'] ?? '',
                    'color_text_light' => $_POST['color_text_light'] ?? '',
                    'color_gold' => $_POST['color_gold'] ?? '',
                ];
                if (saveThemeSettings($colors)) {
                    $message = 'Thème enregistré avec succès.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de l\'enregistrement du thème.';
                    $messageType = 'error';
                }
                break;

            case 'reset_theme':
                if (resetThemeSettings()) {
                    $message = 'Thème réinitialisé aux valeurs par défaut.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de la réinitialisation.';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get current theme settings
$themeSettings = getThemeSettings();
$defaultColors = getDefaultThemeColors();

// Color definitions with labels
$colorFields = [
    'color_primary' => ['label' => 'Couleur principale', 'desc' => 'Boutons, liens, accents principaux'],
    'color_primary_dark' => ['label' => 'Couleur principale foncée', 'desc' => 'États hover, bordures'],
    'color_secondary' => ['label' => 'Couleur secondaire', 'desc' => 'Accents secondaires, décorations'],
    'color_accent' => ['label' => 'Couleur d\'accent', 'desc' => 'Éléments de mise en valeur'],
    'color_accent_light' => ['label' => 'Couleur d\'accent claire', 'desc' => 'Variante légère de l\'accent'],
    'color_cream' => ['label' => 'Fond crème', 'desc' => 'Arrière-plan principal'],
    'color_beige' => ['label' => 'Fond beige', 'desc' => 'Sections alternées'],
    'color_text' => ['label' => 'Texte principal', 'desc' => 'Titres et texte important'],
    'color_text_light' => ['label' => 'Texte secondaire', 'desc' => 'Texte léger, descriptions'],
    'color_gold' => ['label' => 'Or / Accent doré', 'desc' => 'Touches dorées décoratives'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Thème du site | Admin <?= h($hotelName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .color-field {
            background: var(--admin-bg);
            border-radius: var(--admin-radius);
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .color-picker-wrapper {
            position: relative;
            width: 50px;
            height: 50px;
            flex-shrink: 0;
        }
        .color-picker-wrapper input[type="color"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 0;
            border: 2px solid var(--admin-border);
            border-radius: 8px;
            cursor: pointer;
            background: none;
        }
        .color-picker-wrapper input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }
        .color-picker-wrapper input[type="color"]::-webkit-color-swatch {
            border: none;
            border-radius: 6px;
        }
        .color-picker-wrapper input[type="color"]::-moz-color-swatch {
            border: none;
            border-radius: 6px;
        }
        .color-info {
            flex: 1;
        }
        .color-info label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--admin-text);
        }
        .color-info .color-desc {
            font-size: 0.8rem;
            color: var(--admin-text-light);
            margin-bottom: 0.5rem;
        }
        .color-info .color-value {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--admin-text-light);
            background: var(--admin-card);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            display: inline-block;
        }
        .preview-section {
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            padding: 2rem;
            margin-top: 1.5rem;
        }
        .preview-section h3 {
            margin-bottom: 1.5rem;
            font-family: 'Cormorant Garamond', serif;
        }
        .preview-frame {
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            overflow: hidden;
            background: #fff;
        }
        .preview-content {
            padding: 2rem;
            font-family: 'Lato', sans-serif;
        }
        .preview-header {
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
        }
        .preview-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .preview-nav {
            display: flex;
            gap: 1.5rem;
        }
        .preview-nav a {
            text-decoration: none;
            font-size: 0.9rem;
        }
        .preview-hero {
            padding: 3rem 2rem;
            text-align: center;
        }
        .preview-hero h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .preview-hero p {
            margin-bottom: 1.5rem;
        }
        .preview-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin: 0 0.5rem;
        }
        .preview-btn-primary {
            color: white;
        }
        .preview-btn-outline {
            border: 2px solid;
        }
        .preview-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            padding: 0 2rem 2rem;
        }
        .preview-card {
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        .preview-card h4 {
            font-family: 'Cormorant Garamond', serif;
            margin-bottom: 0.5rem;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .btn-reset {
            background: transparent;
            color: var(--admin-text-light);
            border: 1px solid var(--admin-border);
        }
        .btn-reset:hover {
            background: var(--admin-bg);
            color: var(--admin-text);
        }
        @media (max-width: 768px) {
            .preview-cards {
                grid-template-columns: 1fr;
            }
            .preview-nav {
                display: none;
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
                <a href="theme.php" class="nav-item active">
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
                <h1>Thème du site</h1>
                <a href="../index.php" target="_blank" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    Voir le site
                </a>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= h($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="themeForm">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_theme">

                    <div class="card">
                        <div class="card-header">
                            <h2>Personnalisation des couleurs</h2>
                        </div>
                        <div class="card-body">
                            <p style="margin-bottom: 1.5rem; color: var(--admin-text-light);">
                                Personnalisez les couleurs du site client. Les modifications sont appliquées immédiatement après enregistrement.
                            </p>

                            <div class="color-grid">
                                <?php foreach ($colorFields as $key => $field): ?>
                                <div class="color-field">
                                    <div class="color-picker-wrapper">
                                        <input type="color"
                                               id="<?= $key ?>"
                                               name="<?= $key ?>"
                                               value="<?= h($themeSettings[$key]) ?>"
                                               data-default="<?= h($defaultColors[$key]) ?>">
                                    </div>
                                    <div class="color-info">
                                        <label for="<?= $key ?>"><?= h($field['label']) ?></label>
                                        <div class="color-desc"><?= h($field['desc']) ?></div>
                                        <span class="color-value" id="<?= $key ?>_value"><?= h($themeSettings[$key]) ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                        <polyline points="17 21 17 13 7 13 7 21"/>
                                        <polyline points="7 3 7 8 15 8"/>
                                    </svg>
                                    Enregistrer le thème
                                </button>
                                <button type="button" class="btn btn-reset" id="resetBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <polyline points="1 4 1 10 7 10"/>
                                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                                    </svg>
                                    Réinitialiser par défaut
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="preview-section">
                        <h3>Aperçu en direct</h3>
                        <div class="preview-frame" id="previewFrame">
                            <div class="preview-header" id="previewHeader">
                                <div class="preview-logo" id="previewLogo"><?= h($hotelName) ?></div>
                                <nav class="preview-nav">
                                    <a href="#" id="previewNavLink1">Accueil</a>
                                    <a href="#" id="previewNavLink2">Services</a>
                                    <a href="#" id="previewNavLink3">Contact</a>
                                </nav>
                            </div>
                            <div class="preview-hero" id="previewHero">
                                <h1 id="previewTitle">Bienvenue à <?= h($hotelName) ?></h1>
                                <p id="previewSubtitle">Un havre de paix au coeur de Bordeaux</p>
                                <a href="#" class="preview-btn preview-btn-primary" id="previewBtnPrimary">Réserver</a>
                                <a href="#" class="preview-btn preview-btn-outline" id="previewBtnOutline">En savoir plus</a>
                            </div>
                            <div class="preview-cards">
                                <div class="preview-card" id="previewCard1">
                                    <h4>Chambres</h4>
                                    <p id="previewCardText1">Confort et élégance</p>
                                </div>
                                <div class="preview-card" id="previewCard2">
                                    <h4>Restaurant</h4>
                                    <p id="previewCardText2">Cuisine raffinée</p>
                                </div>
                                <div class="preview-card" id="previewCard3">
                                    <h4>Spa</h4>
                                    <p id="previewCardText3">Détente absolue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Reset Form (separate) -->
                <form method="POST" id="resetForm" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <input type="hidden" name="action" value="reset_theme">
                </form>
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

    // Color picker handling and live preview
    const colorInputs = document.querySelectorAll('input[type="color"]');

    function updatePreview() {
        const colors = {};
        colorInputs.forEach(input => {
            colors[input.id] = input.value;
            // Update value display
            const valueEl = document.getElementById(input.id + '_value');
            if (valueEl) {
                valueEl.textContent = input.value.toUpperCase();
            }
        });

        // Apply to preview
        const previewFrame = document.getElementById('previewFrame');
        const previewHero = document.getElementById('previewHero');
        const previewHeader = document.getElementById('previewHeader');

        // Background
        previewFrame.style.backgroundColor = colors.color_cream;
        previewHero.style.backgroundColor = colors.color_beige;

        // Logo
        document.getElementById('previewLogo').style.color = colors.color_primary;

        // Nav links
        document.getElementById('previewNavLink1').style.color = colors.color_text;
        document.getElementById('previewNavLink2').style.color = colors.color_text;
        document.getElementById('previewNavLink3').style.color = colors.color_text;

        // Title
        document.getElementById('previewTitle').style.color = colors.color_primary;
        document.getElementById('previewSubtitle').style.color = colors.color_text_light;

        // Buttons
        const btnPrimary = document.getElementById('previewBtnPrimary');
        btnPrimary.style.backgroundColor = colors.color_primary;
        btnPrimary.style.color = '#fff';

        const btnOutline = document.getElementById('previewBtnOutline');
        btnOutline.style.borderColor = colors.color_accent;
        btnOutline.style.color = colors.color_accent;
        btnOutline.style.backgroundColor = 'transparent';

        // Cards
        const card1 = document.getElementById('previewCard1');
        card1.style.backgroundColor = colors.color_cream;
        card1.querySelector('h4').style.color = colors.color_primary;
        document.getElementById('previewCardText1').style.color = colors.color_text_light;

        const card2 = document.getElementById('previewCard2');
        card2.style.backgroundColor = colors.color_secondary;
        card2.style.opacity = '0.3';
        card2.querySelector('h4').style.color = colors.color_text;
        document.getElementById('previewCardText2').style.color = colors.color_text;

        const card3 = document.getElementById('previewCard3');
        card3.style.backgroundColor = colors.color_accent_light;
        card3.style.opacity = '0.3';
        card3.querySelector('h4').style.color = colors.color_text;
        document.getElementById('previewCardText3').style.color = colors.color_text;
    }

    // Initialize preview
    updatePreview();

    // Update preview on color change
    colorInputs.forEach(input => {
        input.addEventListener('input', updatePreview);
    });

    // Reset button
    document.getElementById('resetBtn').addEventListener('click', function() {
        if (confirm('Voulez-vous vraiment réinitialiser toutes les couleurs aux valeurs par défaut ?')) {
            document.getElementById('resetForm').submit();
        }
    });
    </script>
</body>
</html>
