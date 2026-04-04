<?php
/**
 * Super Admin - Feature Toggles
 * Enable/disable features per establishment
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';
require_once __DIR__ . '/includes/super-functions.php';

superRequireAuth();

$currentPage = 'feature-toggles.php';
$csrfToken = superGenerateCsrfToken();
$hotels = getAllHotels();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Feature Toggles | Super Admin</title>
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
                <h1>Feature Toggles</h1>
                <span style="color: var(--sa-text-light); font-size: 0.875rem;"><?= count($hotels) ?> établissements</span>
            </header>

            <div class="sa-content">
                <?php if (empty($hotels)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="1" y="5" width="22" height="14" rx="7" ry="7"/><circle cx="16" cy="12" r="3"/>
                        </svg>
                        <p>Aucun établissement enregistré</p>
                    </div>
                <?php else: ?>
                    <div class="feature-grid">
                        <?php foreach ($hotels as $hotel):
                            $features = getEstablishmentFeatures($hotel['id']);
                        ?>
                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <?= htmlspecialchars($hotel['name']) ?>
                                    <span class="hotel-status <?= $hotel['is_active'] ? 'active' : 'inactive' ?>" style="float: right;">
                                        <span class="dot"></span>
                                        <?= $hotel['is_active'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </div>
                                <div class="feature-card-body">
                                    <?php foreach ($features as $key => $feature): ?>
                                        <div class="feature-row">
                                            <div class="feature-info">
                                                <span class="feature-label"><?= htmlspecialchars($feature['label']) ?></span>
                                                <span class="feature-desc"><?= htmlspecialchars($feature['description']) ?></span>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox"
                                                       data-hotel-id="<?= $hotel['id'] ?>"
                                                       data-feature="<?= htmlspecialchars($key) ?>"
                                                       <?= $feature['is_enabled'] ? 'checked' : '' ?>
                                                       onchange="toggleFeature(this)">
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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

    function toggleFeature(checkbox) {
        const hotelId = checkbox.dataset.hotelId;
        const feature = checkbox.dataset.feature;
        const enabled = checkbox.checked ? 1 : 0;

        fetch('api/toggle-feature.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'hotel_id=' + hotelId + '&feature_key=' + encodeURIComponent(feature) + '&enabled=' + enabled + '&csrf_token=' + encodeURIComponent('<?= htmlspecialchars($csrfToken) ?>')
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                checkbox.checked = !checkbox.checked;
                alert(data.message || 'Erreur');
            }
        })
        .catch(() => {
            checkbox.checked = !checkbox.checked;
            alert('Erreur de connexion');
        });
    }
    </script>
</body>
</html>
