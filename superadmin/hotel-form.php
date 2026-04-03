<?php
/**
 * Super Admin - Add / Edit Hotel
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';
require_once __DIR__ . '/includes/super-functions.php';
require_once HOTEL_ROOT . '/shared/includes/modules/establishment-types.php';

superRequireAuth();

$currentPage = 'index.php';
$csrfToken = superGenerateCsrfToken();
$message = '';
$messageType = '';

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hotel = $editId ? getHotelById($editId) : null;
$isEdit = $hotel !== null;

// Determine establishment type for labels
$formType = $hotel['type'] ?? ($_GET['type'] ?? 'hotel');
$formTypeLabel = $formType === 'pizzeria' ? 'pizzeria' : 'hôtel';
$formTypeLabelUn = $formType === 'pizzeria' ? 'une pizzeria' : 'un hôtel';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        $data = [
            'name'      => $_POST['name'] ?? '',
            'slug'      => $_POST['slug'] ?? '',
            'type'      => $_POST['type'] ?? 'hotel',
            'site_url'  => $_POST['site_url'] ?? '',
            'admin_url' => $_POST['admin_url'] ?? '',
            'notes'     => $_POST['notes'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // Auto-fill URLs from slug if empty
        $slug = trim($data['slug'] ?? '');
        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $data['name'])));
        }
        if (empty($data['site_url']) && !empty($slug)) {
            $urls = getDefaultHotelUrls($slug);
            $data['site_url'] = $urls['site_url'];
        }
        if (empty($data['admin_url']) && !empty($slug)) {
            $urls = $urls ?? getDefaultHotelUrls($slug);
            $data['admin_url'] = $urls['admin_url'];
        }

        if ($isEdit) {
            $result = updateHotel($editId, $data);
            if ($result['success']) {
                logAudit($_SESSION['super_admin_id'], 'hotel_updated', $editId, json_encode(['name' => $data['name']]));
            }
        } else {
            $result = createHotel($data);
            if ($result['success']) {
                // Provision default data for the new hotel
                $newHotelId = (int)($result['id'] ?? 0);
                if ($newHotelId > 0) {
                    provisionHotelData($newHotelId);
                }
                logAudit($_SESSION['super_admin_id'], 'hotel_created', $newHotelId, json_encode(['name' => $data['name']]));
                header('Location: index.php?type=' . urlencode($data['type'] ?? 'hotel'));
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
    'type'      => $_POST['type']      ?? ($hotel['type'] ?? ($_GET['type'] ?? 'hotel')),
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
    <title><?= $isEdit ? 'Modifier' : 'Ajouter' ?> <?= $formTypeLabelUn ?> | Super Admin</title>
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
                <h1><?= $isEdit ? 'Modifier' : 'Ajouter' ?> <?= $formTypeLabelUn ?></h1>
                <a href="index.php?type=<?= htmlspecialchars($formType) ?>" class="btn btn-outline">Retour</a>
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
                                <label for="name">Nom de l'établissement *</label>
                                <input type="text" id="name" name="name" required
                                       value="<?= htmlspecialchars($formData['name']) ?>"
                                       placeholder="<?= $formType === 'pizzeria' ? 'Pizzeria Roma' : 'Hôtel Bordeaux' ?>">
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
                                <label for="type">Type d'établissement</label>
                                <select id="type" name="type">
                                    <?php foreach (getEstablishmentTypeOptions() as $code => $label): ?>
                                        <option value="<?= htmlspecialchars($code) ?>" <?= $formData['type'] === $code ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                                        Établissement actif
                                    </label>
                                </div>
                            <?php endif; ?>

                            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-primary">
                                    <?= $isEdit ? 'Enregistrer' : 'Ajouter' ?>
                                </button>
                                <a href="index.php?type=<?= htmlspecialchars($formType) ?>" class="btn btn-outline">Annuler</a>
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

    // Auto-generate slug from name + auto-fill URLs
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    const siteUrlInput = document.getElementById('site_url');
    const adminUrlInput = document.getElementById('admin_url');

    function updateUrlsFromSlug(slug) {
        if (siteUrlInput && !siteUrlInput.dataset.manual && slug) {
            siteUrlInput.value = 'https://hothello-client.onrender.com/?hotel=' + slug;
        }
        if (adminUrlInput && !adminUrlInput.dataset.manual && slug) {
            adminUrlInput.value = 'https://hothello-admin.onrender.com';
        }
    }

    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.manual) {
                const slug = this.value.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-|-$/g, '');
                slugInput.value = slug;
                updateUrlsFromSlug(slug);
            }
        });
        slugInput.addEventListener('input', function() {
            this.dataset.manual = '1';
            updateUrlsFromSlug(this.value);
        });
    }
    if (siteUrlInput) siteUrlInput.addEventListener('input', function() { this.dataset.manual = '1'; });
    if (adminUrlInput) adminUrlInput.addEventListener('input', function() { this.dataset.manual = '1'; });
    </script>
</body>
</html>
