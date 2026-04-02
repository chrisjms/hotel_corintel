<?php
require_once __DIR__ . '/../bootstrap.php';
/**
 * Site Theme Customization
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('content');

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
    <script>(function(){if(localStorage.getItem('admin_theme')==='dark')document.documentElement.setAttribute('data-theme','dark')})();function toggleAdminTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';h.setAttribute('data-theme',d?'light':'dark');localStorage.setItem('admin_theme',d?'light':'dark')}</script>
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
        <?php $currentPage = 'theme.php'; include __DIR__ . '/includes/sidebar.php'; ?>
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
