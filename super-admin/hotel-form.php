<?php
/**
 * Super Admin - Add / Edit Hotel
 */

require_once __DIR__ . '/includes/super-auth.php';
require_once __DIR__ . '/includes/super-functions.php';

superRequireAuth();

$currentPage = 'index.php';
$csrfToken = superGenerateCsrfToken();
$message = '';
$messageType = '';

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hotel = $editId ? getHotelById($editId) : null;
$isEdit = $hotel !== null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        $data = [
            'name'      => $_POST['name'] ?? '',
            'slug'      => $_POST['slug'] ?? '',
            'site_url'  => $_POST['site_url'] ?? '',
            'admin_url' => $_POST['admin_url'] ?? '',
            'notes'     => $_POST['notes'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($isEdit) {
            $result = updateHotel($editId, $data);
            if ($result['success']) {
                logAudit($_SESSION['super_admin_id'], 'hotel_updated', $editId, json_encode(['name' => $data['name']]));
            }
        } else {
            $result = createHotel($data);
            if ($result['success']) {
                logAudit($_SESSION['super_admin_id'], 'hotel_created', $result['id'] ?? null, json_encode(['name' => $data['name']]));
                header('Location: index.php');
                exit;
            }
        }

        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';

        // Refresh hotel data after successful edit
        if ($isEdit && $result['success']) {
            $hotel = getHotelById($editId);
        }
    }
}

// Use POST data for form values on error, else hotel data, else empty
$formData = [
    'name'      => $_POST['name']      ?? ($hotel['name'] ?? ''),
    'slug'      => $_POST['slug']      ?? ($hotel['slug'] ?? ''),
    'site_url'  => $_POST['site_url']  ?? ($hotel['site_url'] ?? ''),
    'admin_url' => $_POST['admin_url'] ?? ($hotel['admin_url'] ?? ''),
    'notes'     => $_POST['notes']     ?? ($hotel['notes'] ?? ''),
    'is_active' => isset($_POST['is_active']) ? 1 : ($hotel['is_active'] ?? 1),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $isEdit ? 'Modifier' : 'Ajouter' ?> un hôtel | Super Admin</title>
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
                <h1><?= $isEdit ? 'Modifier l\'hôtel' : 'Ajouter un hôtel' ?></h1>
                <a href="index.php" class="btn btn-outline">Retour</a>
            </header>

            <div class="sa-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" style="max-width: 600px;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                            <div class="form-group">
                                <label for="name">Nom de l'hôtel *</label>
                                <input type="text" id="name" name="name" required
                                       value="<?= htmlspecialchars($formData['name']) ?>"
                                       placeholder="Hôtel Bordeaux">
                            </div>

                            <div class="form-group">
                                <label for="slug">Slug (identifiant URL)</label>
                                <input type="text" id="slug" name="slug"
                                       value="<?= htmlspecialchars($formData['slug']) ?>"
                                       placeholder="bordeaux"
                                       pattern="[a-z0-9-]+"
                                       title="Lettres minuscules, chiffres et tirets uniquement">
                                <small>Généré automatiquement si vide</small>
                            </div>

                            <div class="form-group">
                                <label for="site_url">URL du site client</label>
                                <input type="url" id="site_url" name="site_url"
                                       value="<?= htmlspecialchars($formData['site_url']) ?>"
                                       placeholder="https://exemple.com/hotel">
                            </div>

                            <div class="form-group">
                                <label for="admin_url">URL du panneau admin</label>
                                <input type="url" id="admin_url" name="admin_url"
                                       value="<?= htmlspecialchars($formData['admin_url']) ?>"
                                       placeholder="https://exemple.com/hotel/admin">
                                <small>Utilisé pour la connexion croisée (cross-login)</small>
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes internes</label>
                                <textarea id="notes" name="notes" rows="3"
                                          placeholder="Notes internes..."><?= htmlspecialchars($formData['notes']) ?></textarea>
                            </div>

                            <?php if ($isEdit): ?>
                                <div class="form-group">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                        <input type="checkbox" name="is_active" value="1"
                                               <?= $formData['is_active'] ? 'checked' : '' ?>
                                               style="width: auto;">
                                        Hôtel actif
                                    </label>
                                </div>
                            <?php endif; ?>

                            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-primary">
                                    <?= $isEdit ? 'Enregistrer' : 'Ajouter l\'hôtel' ?>
                                </button>
                                <a href="index.php" class="btn btn-outline">Annuler</a>
                            </div>
                        </form>
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

    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.manual) {
                slugInput.value = this.value.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        });
        slugInput.addEventListener('input', function() {
            this.dataset.manual = '1';
        });
    }
    </script>
</body>
</html>
