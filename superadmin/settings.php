<?php
/**
 * Super Admin - Settings (password change)
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';

superRequireAuth();

$currentPage = 'settings.php';
$csrfToken = superGenerateCsrfToken();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $message = 'Les mots de passe ne correspondent pas.';
            $messageType = 'error';
        } else {
            $result = superChangePassword($_SESSION['super_admin_id'], $current, $new);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Paramètres | Super Admin</title>
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
                <h1>Paramètres</h1>
                <div></div>
            </header>

            <div class="sa-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2>Changer le mot de passe</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" style="max-width: 400px;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                            <div class="form-group">
                                <label for="current_password">Mot de passe actuel</label>
                                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                            </div>

                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe</label>
                                <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                                <small>Minimum 8 caractères</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Modifier le mot de passe
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Informations du compte</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Utilisateur :</strong> <?= htmlspecialchars($_SESSION['super_admin_username'] ?? '') ?></p>
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
