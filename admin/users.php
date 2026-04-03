<?php
require_once __DIR__ . '/../shared/bootstrap.php';
/**
 * Admin Users Management
 * Hotel Corintel
 */

require_once HOTEL_ROOT . '/shared/includes/auth.php';
require_once HOTEL_ROOT . '/shared/includes/functions.php';

requireRole('settings');

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$csrfToken = generateCsrfToken();
$hotelName = getHotelName();

// Ensure role column exists
ensureRoleColumn();

$message = '';
$messageType = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $pdo = getDatabase();

        switch ($action) {
            case 'create_user':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'reception';

                // Validate
                if (empty($username) || empty($password)) {
                    $message = 'Le nom d\'utilisateur et le mot de passe sont obligatoires.';
                    $messageType = 'error';
                    break;
                }
                if (strlen($username) < 3 || strlen($username) > 50) {
                    $message = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères.';
                    $messageType = 'error';
                    break;
                }
                if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
                    $message = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, points, tirets et underscores.';
                    $messageType = 'error';
                    break;
                }
                if (strlen($password) < 8) {
                    $message = 'Le mot de passe doit contenir au moins 8 caractères.';
                    $messageType = 'error';
                    break;
                }
                if (!in_array($role, array_keys(ROLE_PERMISSIONS))) {
                    $message = 'Rôle invalide.';
                    $messageType = 'error';
                    break;
                }

                // Check uniqueness
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE username = ? AND hotel_id = ?');
                $stmt->execute([$username, getHotelId()]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'Ce nom d\'utilisateur existe déjà.';
                    $messageType = 'error';
                    break;
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO admins (username, password, email, role, hotel_id) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$username, $hash, $email ?: null, $role, getHotelId()]);

                $message = 'Utilisateur créé avec succès.';
                $messageType = 'success';
                break;

            case 'update_role':
                $targetId = (int)($_POST['user_id'] ?? 0);
                $newRole = $_POST['role'] ?? '';

                if (!in_array($newRole, array_keys(ROLE_PERMISSIONS))) {
                    $message = 'Rôle invalide.';
                    $messageType = 'error';
                    break;
                }

                // Cannot change own role
                if ($targetId === $admin['id']) {
                    $message = 'Vous ne pouvez pas modifier votre propre rôle.';
                    $messageType = 'error';
                    break;
                }

                $stmt = $pdo->prepare('UPDATE admins SET role = ? WHERE id = ? AND hotel_id = ?');
                $stmt->execute([$newRole, $targetId, getHotelId()]);

                $message = 'Rôle mis à jour avec succès.';
                $messageType = 'success';
                break;

            case 'reset_password':
                $targetId = (int)($_POST['user_id'] ?? 0);
                $newPassword = $_POST['new_password'] ?? '';

                if (strlen($newPassword) < 8) {
                    $message = 'Le mot de passe doit contenir au moins 8 caractères.';
                    $messageType = 'error';
                    break;
                }

                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ? AND hotel_id = ?');
                $stmt->execute([$hash, $targetId, getHotelId()]);

                // Invalidate persistent tokens for that user
                $stmt = $pdo->prepare('DELETE FROM persistent_tokens WHERE admin_id = ? AND hotel_id = ?');
                $stmt->execute([$targetId, getHotelId()]);

                $message = 'Mot de passe réinitialisé avec succès.';
                $messageType = 'success';
                break;

            case 'delete_user':
                $targetId = (int)($_POST['user_id'] ?? 0);

                // Cannot delete yourself
                if ($targetId === $admin['id']) {
                    $message = 'Vous ne pouvez pas supprimer votre propre compte.';
                    $messageType = 'error';
                    break;
                }

                // Cannot delete the last admin
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE role = 'admin' AND id != ? AND hotel_id = ?");
                $stmt->execute([$targetId, getHotelId()]);
                $targetRole = $pdo->prepare('SELECT role FROM admins WHERE id = ? AND hotel_id = ?');
                $targetRole->execute([$targetId, getHotelId()]);
                $deletingRole = $targetRole->fetchColumn();

                if ($deletingRole === 'admin' && $stmt->fetchColumn() === 0) {
                    $message = 'Impossible de supprimer le dernier administrateur.';
                    $messageType = 'error';
                    break;
                }

                // Delete persistent tokens first
                $stmt = $pdo->prepare('DELETE FROM persistent_tokens WHERE admin_id = ? AND hotel_id = ?');
                $stmt->execute([$targetId, getHotelId()]);

                // Delete user
                $stmt = $pdo->prepare('DELETE FROM admins WHERE id = ? AND hotel_id = ?');
                $stmt->execute([$targetId, getHotelId()]);

                $message = 'Utilisateur supprimé avec succès.';
                $messageType = 'success';
                break;
        }
    }
}

// Fetch all admin users
$pdo = getDatabase();
$stmt = $pdo->prepare('SELECT id, username, email, role, created_at, last_login FROM admins WHERE hotel_id = ? ORDER BY created_at ASC');
$stmt->execute([getHotelId()]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Utilisateurs | Admin <?= h($hotelName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script>(function(){if(localStorage.getItem('admin_theme')==='dark')document.documentElement.setAttribute('data-theme','dark')})();function toggleAdminTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';h.setAttribute('data-theme',d?'light':'dark');localStorage.setItem('admin_theme',d?'light':'dark')}</script>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 20px;
        }
        .role-admin {
            background: rgba(139, 90, 43, 0.1);
            color: var(--admin-primary);
        }
        .role-reception {
            background: rgba(66, 153, 225, 0.1);
            color: #2B6CB0;
        }
        [data-theme="dark"] .role-reception {
            background: rgba(66, 153, 225, 0.18);
            color: #63B3ED;
        }
        .user-current {
            font-size: 0.7rem;
            color: var(--admin-text-light);
            font-style: italic;
            margin-left: 0.5rem;
        }
        .actions-cell {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-danger {
            background: var(--admin-error);
            color: #fff;
        }
        .btn-danger:hover {
            opacity: 0.9;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $currentPage = 'users.php'; include __DIR__ . '/includes/sidebar.php'; ?>

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
                <h1>Utilisateurs</h1>
                <button class="btn btn-primary" onclick="document.getElementById('createModal').classList.add('active')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Nouvel utilisateur
                </button>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= h($message) ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2>Comptes administrateurs</h2>
                        <span class="badge"><?= count($users) ?> utilisateur<?= count($users) > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Dernière connexion</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?= h($user['username']) ?></strong>
                                        <?php if ($user['id'] === $admin['id']): ?>
                                            <span class="user-current">(vous)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($user['email'] ?? '—') ?></td>
                                    <td>
                                        <?php if ($user['id'] === $admin['id']): ?>
                                            <span class="role-badge role-<?= h($user['role']) ?>"><?= h(ROLE_LABELS[$user['role']] ?? $user['role']) ?></span>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="role" onchange="this.form.submit()" style="
                                                    padding: 0.25rem 0.5rem;
                                                    border: 1px solid var(--admin-border);
                                                    border-radius: 6px;
                                                    background: var(--admin-card);
                                                    color: var(--admin-text);
                                                    font-size: 0.8rem;
                                                    cursor: pointer;
                                                ">
                                                    <?php foreach (ROLE_LABELS as $roleKey => $roleLabel): ?>
                                                    <option value="<?= h($roleKey) ?>" <?= $user['role'] === $roleKey ? 'selected' : '' ?>><?= h($roleLabel) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <span style="color: var(--admin-text-light)">Jamais</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['id'] !== $admin['id']): ?>
                                        <div class="actions-cell">
                                            <button class="btn btn-sm btn-outline" onclick="openResetPassword(<?= $user['id'] ?>, '<?= h($user['username']) ?>')">
                                                Mot de passe
                                            </button>
                                            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer l\'utilisateur <?= h($user['username']) ?> ?')">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                            <span style="color: var(--admin-text-light); font-size: 0.8rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Rôles disponibles</h2>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                            <div>
                                <h3 style="font-size: 1rem; margin-bottom: 0.5rem;">
                                    <span class="role-badge role-admin">Administrateur</span>
                                </h3>
                                <p style="font-size: 0.85rem; color: var(--admin-text-light); margin: 0;">
                                    Accès complet à tout le panneau d'administration. Peut gérer les utilisateurs, le contenu, les chambres et tous les paramètres.
                                </p>
                            </div>
                            <div>
                                <h3 style="font-size: 1rem; margin-bottom: 0.5rem;">
                                    <span class="role-badge role-reception">Réception</span>
                                </h3>
                                <p style="font-size: 0.85rem; color: var(--admin-text-light); margin: 0;">
                                    Accès limité aux commandes, messages et statistiques. Ne peut pas modifier le contenu du site, les chambres ou les paramètres.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Create User Modal -->
    <div class="modal" id="createModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nouvel utilisateur</h3>
                <button class="modal-close" onclick="document.getElementById('createModal').classList.remove('active')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <input type="hidden" name="action" value="create_user">

                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_.\-]+" autocomplete="off">
                        <small>Lettres, chiffres, points, tirets, underscores uniquement.</small>
                    </div>

                    <div class="form-group">
                        <label for="email">Email (optionnel)</label>
                        <input type="email" id="email" name="email" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                        <small>Minimum 8 caractères.</small>
                    </div>

                    <div class="form-group">
                        <label for="role">Rôle</label>
                        <select id="role" name="role">
                            <?php foreach (ROLE_LABELS as $roleKey => $roleLabel): ?>
                            <option value="<?= h($roleKey) ?>"><?= h($roleLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('createModal').classList.remove('active')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal" id="resetPasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Réinitialiser le mot de passe</h3>
                <button class="modal-close" onclick="document.getElementById('resetPasswordModal').classList.remove('active')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="resetUserId" value="">

                    <p style="margin-bottom: 1rem; color: var(--admin-text-light);">
                        Nouveau mot de passe pour <strong id="resetUsername"></strong> :
                    </p>

                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                        <small>Minimum 8 caractères. L'utilisateur sera déconnecté de toutes ses sessions.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('resetPasswordModal').classList.remove('active')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Réinitialiser</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Mobile menu
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

    // Close modals on overlay click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });

    function openResetPassword(userId, username) {
        document.getElementById('resetUserId').value = userId;
        document.getElementById('resetUsername').textContent = username;
        document.getElementById('resetPasswordModal').classList.add('active');
    }
    </script>
</body>
</html>
