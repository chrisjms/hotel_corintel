<?php
/**
 * Super Admin - Settings (password change + account management)
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';

superRequireAuth();

$currentPage = 'settings.php';
$csrfToken = superGenerateCsrfToken();
$message = '';
$messageType = '';
$currentAdminId = $_SESSION['super_admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? 'change_password';

        if ($action === 'change_password') {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if ($new !== $confirm) {
                $message = 'Les mots de passe ne correspondent pas.';
                $messageType = 'error';
            } else {
                $result = superChangePassword($currentAdminId, $current, $new);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
        } elseif ($action === 'create_admin') {
            $username = $_POST['new_username'] ?? '';
            $password = $_POST['new_admin_password'] ?? '';
            $confirmPw = $_POST['new_admin_password_confirm'] ?? '';
            $email = $_POST['new_email'] ?? '';

            if ($password !== $confirmPw) {
                $message = 'Les mots de passe ne correspondent pas.';
                $messageType = 'error';
            } else {
                $result = createSuperAdmin($username, $password, $email);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
        } elseif ($action === 'toggle_admin') {
            $targetId = (int)($_POST['admin_id'] ?? 0);
            $result = toggleSuperAdminActive($targetId, $currentAdminId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        } elseif ($action === 'delete_admin') {
            $targetId = (int)($_POST['admin_id'] ?? 0);
            $result = deleteSuperAdmin($targetId, $currentAdminId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        }
    }
}

$superAdmins = getAllSuperAdmins();
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

                <!-- Comptes Super Admin -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>Comptes Super Admin</h2>
                        <button class="btn btn-primary btn-sm" onclick="document.getElementById('createAdminForm').style.display = document.getElementById('createAdminForm').style.display === 'none' ? 'block' : 'none'">
                            + Nouveau compte
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Formulaire de création -->
                        <div id="createAdminForm" style="display: none; margin-bottom: 1.5rem; padding: 1.25rem; background: var(--sa-bg); border-radius: var(--sa-radius); border: 1px solid var(--sa-border);">
                            <h3 style="margin-bottom: 1rem; font-size: 0.95rem; font-weight: 600;">Créer un compte</h3>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="create_admin">

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; max-width: 600px;">
                                    <div class="form-group">
                                        <label for="new_username">Nom d'utilisateur</label>
                                        <input type="text" id="new_username" name="new_username" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_.\-]+" autocomplete="off">
                                        <small>Lettres, chiffres, points, tirets, underscores</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="new_email">Email (optionnel)</label>
                                        <input type="email" id="new_email" name="new_email" autocomplete="off">
                                    </div>

                                    <div class="form-group">
                                        <label for="new_admin_password">Mot de passe</label>
                                        <input type="password" id="new_admin_password" name="new_admin_password" required minlength="8" autocomplete="new-password">
                                        <small>Minimum 8 caractères</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="new_admin_password_confirm">Confirmer</label>
                                        <input type="password" id="new_admin_password_confirm" name="new_admin_password_confirm" required minlength="8" autocomplete="new-password">
                                    </div>
                                </div>

                                <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
                                    <button type="submit" class="btn btn-primary btn-sm">Créer le compte</button>
                                    <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('createAdminForm').style.display='none'">Annuler</button>
                                </div>
                            </form>
                        </div>

                        <!-- Liste des comptes -->
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Utilisateur</th>
                                        <th>Email</th>
                                        <th>Statut</th>
                                        <th>Créé le</th>
                                        <th>Dernière connexion</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($superAdmins as $admin): ?>
                                        <?php
                                            $isActive = filter_var($admin['is_active'], FILTER_VALIDATE_BOOLEAN);
                                            $isSelf = (int)$admin['id'] === $currentAdminId;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($admin['username']) ?></strong>
                                                <?php if ($isSelf): ?>
                                                    <span class="badge" style="background: var(--sa-primary); color: #fff; margin-left: 0.25rem;">vous</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($admin['email'] ?? '—') ?></td>
                                            <td>
                                                <?php if ($isActive): ?>
                                                    <span class="badge" style="background: var(--sa-success); color: #fff;">Actif</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: var(--sa-error); color: #fff;">Inactif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($admin['created_at'])) ?></td>
                                            <td><?= $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : '—' ?></td>
                                            <td>
                                                <?php if (!$isSelf): ?>
                                                    <div style="display: flex; gap: 0.25rem;">
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                            <input type="hidden" name="action" value="toggle_admin">
                                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                            <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline' : 'btn-success' ?>" title="<?= $isActive ? 'Désactiver' : 'Activer' ?>">
                                                                <?= $isActive ? 'Désactiver' : 'Activer' ?>
                                                            </button>
                                                        </form>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce compte définitivement ?')">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                            <input type="hidden" name="action" value="delete_admin">
                                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--sa-text-light); font-size: 0.85rem;">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Changer le mot de passe -->
                <div class="card">
                    <div class="card-header">
                        <h2>Changer mon mot de passe</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" style="max-width: 400px;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="change_password">

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

                <!-- Informations du compte -->
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
