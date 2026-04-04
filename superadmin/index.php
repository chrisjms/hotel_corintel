<?php
/**
 * Super Admin Dashboard - Hotel List
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

// Determine which type we're viewing
$viewType = ($_GET['type'] ?? 'hotel') === 'pizzeria' ? 'pizzeria' : 'hotel';
$typeLabel = ESTABLISHMENT_TYPES[$viewType]['label'] ?? 'Hôtel';
$typeLabelPlural = $viewType === 'hotel' ? 'Hôtels' : 'Pizzerias';

// Handle hotel deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } elseif ($_POST['action'] === 'delete_hotel') {
        $hotelId = (int)($_POST['hotel_id'] ?? 0);
        $hotel = getHotelById($hotelId);
        if ($hotel) {
            $result = deleteHotel($hotelId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            if ($result['success']) {
                logAudit($_SESSION['super_admin_id'], 'hotel_deleted', $hotelId, json_encode(['name' => $hotel['name']]));
            }
        } else {
            $message = 'Hôtel introuvable.';
            $messageType = 'error';
        }
    }
}

$hotels = getAllHotels($viewType);
$totalHotels = count($hotels);
$activeHotels = count(array_filter($hotels, fn($h) => $h['is_active']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($typeLabelPlural) ?> | Super Admin</title>
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
                <h1><?= htmlspecialchars($typeLabelPlural) ?></h1>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <form method="POST" action="api/bulk-action.php" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="export_all">
                        <button type="submit" class="btn btn-outline btn-sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Exporter tout
                        </button>
                    </form>
                    <a href="onboarding.php?type=<?= htmlspecialchars($viewType) ?>" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Ajouter <?= $viewType === 'hotel' ? 'un hôtel' : 'une pizzeria' ?>
                    </a>
                </div>
            </header>

            <div class="sa-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totalHotels ?></div>
                            <div class="stat-label">Total <?= htmlspecialchars($typeLabelPlural) ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(72, 187, 120, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-success);">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value"><?= $activeHotels ?></div>
                            <div class="stat-label"><?= htmlspecialchars($typeLabelPlural) ?> actifs</div>
                        </div>
                    </div>
                </div>

                <!-- Hotel Cards -->
                <?php if (!empty($hotels)): ?>
                    <div class="select-all-bar">
                        <label class="bulk-checkbox">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                        </label>
                        <span>Tout sélectionner</span>
                        <span class="bulk-hint">Cochez pour activer, désactiver ou exporter en masse</span>
                    </div>
                <?php endif; ?>

                <?php if (empty($hotels)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        <p>Aucun<?= $viewType === 'hotel' ? ' hôtel enregistré' : 'e pizzeria enregistrée' ?></p>
                        <a href="hotel-form.php?type=<?= htmlspecialchars($viewType) ?>" class="btn btn-primary">Ajouter <?= $viewType === 'hotel' ? 'un hôtel' : 'une pizzeria' ?></a>
                    </div>
                <?php else: ?>
                    <div class="hotels-grid">
                        <?php foreach ($hotels as $hotel): ?>
                            <div class="hotel-card">
                                <div class="hotel-card-header">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <label class="bulk-checkbox" onclick="event.stopPropagation();">
                                            <input type="checkbox" class="hotel-select" value="<?= $hotel['id'] ?>" onchange="updateBulkBar()">
                                        </label>
                                        <h3><?= htmlspecialchars($hotel['name']) ?></h3>
                                    </div>
                                    <span class="hotel-status <?= $hotel['is_active'] ? 'active' : 'inactive' ?>">
                                        <span class="dot"></span>
                                        <?= $hotel['is_active'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </div>
                                <div class="hotel-card-body">
                                    <div class="hotel-info">
                                        <?php if ($hotel['site_url']): ?>
                                            <div>
                                                <strong>Site client :</strong>
                                                <a href="<?= htmlspecialchars($hotel['site_url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($hotel['site_url']) ?></a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($hotel['admin_url']): ?>
                                            <div>
                                                <strong>Admin :</strong>
                                                <span><?= htmlspecialchars($hotel['admin_url']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($hotel['notes']): ?>
                                            <div style="font-style: italic; opacity: 0.8;"><?= htmlspecialchars($hotel['notes']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="hotel-card-actions">
                                        <?php if ($hotel['site_url']): ?>
                                            <a href="<?= htmlspecialchars($hotel['site_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                                Visiter le site
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($hotel['admin_url']): ?>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="openAdmin(<?= $hotel['id'] ?>)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                                Ouvrir Admin
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="hotel-card-footer">
                                    <a href="hotel-form.php?id=<?= $hotel['id'] ?>" class="btn btn-outline btn-sm">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Modifier
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cet hôtel ? Cette action est irréversible.');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete_hotel">
                                        <input type="hidden" name="hotel_id" value="<?= $hotel['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Floating Action Bar for Bulk Actions -->
    <div class="floating-action-bar" id="floatingActionBar">
        <span class="selection-count" id="selectionCount">0 sélectionné(s)</span>
        <div class="floating-actions">
            <button class="btn btn-success btn-sm" onclick="bulkAction('activate')">Activer</button>
            <button class="btn btn-outline btn-sm" onclick="bulkAction('deactivate')">Désactiver</button>
            <button class="btn btn-primary btn-sm" onclick="bulkExport()">Exporter CSV</button>
        </div>
    </div>

    <!-- Hidden form for bulk export (triggers file download) -->
    <form id="bulkExportForm" method="POST" action="api/bulk-action.php" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" value="export">
        <div id="bulkExportIds"></div>
    </form>

    <script>
    // Theme toggle
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

    // Mobile menu
    const menuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('saSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('sidebarClose');

    if (menuBtn) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.add('open');
            overlay.classList.add('open');
        });
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    }

    if (overlay) overlay.addEventListener('click', closeSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);

    // Cross-login: Open Admin
    // window.open() must be called synchronously (before async fetch) to avoid Safari popup blocker
    function openAdmin(hotelId) {
        const popup = window.open('', '_blank');
        if (!popup) {
            alert('Veuillez autoriser les popups pour ce site.');
            return;
        }
        popup.document.write('<p style="font-family:system-ui;padding:2rem">Connexion en cours...</p>');

        fetch('api/generate-cross-login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'hotel_id=' + hotelId + '&csrf_token=' + encodeURIComponent('<?= htmlspecialchars($csrfToken) ?>')
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.url) {
                popup.location.href = data.url;
            } else {
                popup.close();
                alert(data.message || 'Erreur lors de la génération du lien.');
            }
        })
        .catch(() => {
            popup.close();
            alert('Erreur de connexion.');
        });
    }

    // Bulk actions
    function getSelectedIds() {
        const ids = [];
        document.querySelectorAll('.hotel-select:checked').forEach(cb => ids.push(cb.value));
        return ids;
    }

    function updateBulkBar() {
        const ids = getSelectedIds();
        const bar = document.getElementById('floatingActionBar');
        if (ids.length > 0) {
            bar.classList.add('visible');
            document.getElementById('selectionCount').textContent = ids.length + ' sélectionné(s)';
        } else {
            bar.classList.remove('visible');
        }
        // Update select-all checkbox state
        const allBoxes = document.querySelectorAll('.hotel-select');
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.checked = allBoxes.length > 0 && ids.length === allBoxes.length;
        }
    }

    function toggleSelectAll(cb) {
        document.querySelectorAll('.hotel-select').forEach(box => { box.checked = cb.checked; });
        updateBulkBar();
    }

    function bulkAction(action) {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        const label = action === 'activate' ? 'activer' : 'désactiver';
        if (!confirm(label.charAt(0).toUpperCase() + label.slice(1) + ' ' + ids.length + ' établissement(s) ?')) return;

        fetch('api/bulk-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=' + action + '&csrf_token=' + encodeURIComponent('<?= htmlspecialchars($csrfToken) ?>') + '&' + ids.map(id => 'hotel_ids[]=' + id).join('&')
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erreur');
            }
        })
        .catch(() => alert('Erreur de connexion'));
    }

    function bulkExport() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        const container = document.getElementById('bulkExportIds');
        container.innerHTML = ids.map(id => '<input type="hidden" name="hotel_ids[]" value="' + id + '">').join('');
        document.getElementById('bulkExportForm').submit();
    }
    </script>
</body>
</html>
