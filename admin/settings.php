<?php
require_once __DIR__ . '/../shared/bootstrap.php';
/**
 * Admin Settings
 * Hotel Corintel
 */

require_once HOTEL_ROOT . '/shared/includes/auth.php';
require_once HOTEL_ROOT . '/shared/includes/functions.php';

requireRole('settings');

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$csrfToken = generateCsrfToken();

$message = '';
$messageType = '';

// Get current hotel name for display
$hotelName = getHotelName();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'change_password':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if ($newPassword !== $confirmPassword) {
                    $message = 'Les nouveaux mots de passe ne correspondent pas.';
                    $messageType = 'error';
                } else {
                    $result = changePassword($admin['id'], $currentPassword, $newPassword);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;

            case 'update_vat_settings':
                $defaultRate = floatval($_POST['default_vat_rate'] ?? 10);
                if ($defaultRate < 0 || $defaultRate > 100) {
                    $message = 'Le taux de TVA doit être entre 0 et 100%.';
                    $messageType = 'error';
                } else {
                    $success = setDefaultVatRate($defaultRate);
                    if ($success) {
                        $message = 'Taux de TVA par défaut mis à jour.';
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

// Get VAT settings
$defaultVatRate = getDefaultVatRate();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Paramètres | Admin <?= h($hotelName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script>(function(){if(localStorage.getItem('admin_theme')==='dark')document.documentElement.setAttribute('data-theme','dark')})();function toggleAdminTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';h.setAttribute('data-theme',d?'light':'dark');localStorage.setItem('admin_theme',d?'light':'dark')}</script>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'settings.php'; include __DIR__ . '/includes/sidebar.php'; ?>
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
                <h1>Paramètres</h1>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= h($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Account Info -->
                <div class="card">
                    <div class="card-header">
                        <h2>Informations du compte</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Identifiant</span>
                                <span class="info-value"><?= h($admin['username']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?= h($admin['email'] ?? '-') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Dernière connexion</span>
                                <span class="info-value"><?= formatDate($admin['last_login']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h2>Changer le mot de passe</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="password-form">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <input type="hidden" name="action" value="change_password">

                            <div class="form-group">
                                <label for="current_password">Mot de passe actuel</label>
                                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                            </div>

                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe</label>
                                <input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="8">
                                <small>Minimum 8 caractères</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" minlength="8">
                            </div>

                            <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                        </form>
                    </div>
                </div>

                <!-- VAT Settings -->
                <div class="card">
                    <div class="card-header">
                        <h2>Paramètres TVA</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <input type="hidden" name="action" value="update_vat_settings">

                            <div class="form-group">
                                <label for="default_vat_rate">Taux de TVA par défaut (%)</label>
                                <input type="number" id="default_vat_rate" name="default_vat_rate"
                                       value="<?= h($defaultVatRate) ?>"
                                       min="0" max="100" step="0.1" required>
                                <small>Ce taux est utilisé pour calculer les prix HT/TTC dans le room service. En France, le taux réduit pour la restauration est de 10%.</small>
                            </div>

                            <div class="form-group" style="margin-top: 1rem;">
                                <p style="font-size: 0.875rem; color: var(--admin-text-light);">
                                    <strong>Taux de TVA par catégorie :</strong> Vous pouvez définir un taux spécifique pour chaque catégorie dans
                                    <a href="room-service-categories.php" style="color: var(--admin-primary);">Room Service → Catégories</a>.
                                </p>
                            </div>

                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </form>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="card">
                    <div class="card-header">
                        <h2>Sécurité</h2>
                    </div>
                    <div class="card-body">
                        <div class="security-notice">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            <div>
                                <h4>Conseils de sécurité</h4>
                                <ul>
                                    <li>Utilisez un mot de passe fort et unique</li>
                                    <li>Ne partagez jamais vos identifiants</li>
                                    <li>Déconnectez-vous après chaque session sur un ordinateur partagé</li>
                                    <li>Changez votre mot de passe régulièrement</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
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
