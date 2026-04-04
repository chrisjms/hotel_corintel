<?php
/**
 * Super Admin - Onboarding Wizard
 * Multi-step form to create a new establishment
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';
require_once __DIR__ . '/includes/super-functions.php';
require_once HOTEL_ROOT . '/shared/includes/modules/establishment-types.php';

superRequireAuth();

$currentPage = 'index.php'; // highlight the hotels/pizzerias nav item
$csrfToken = superGenerateCsrfToken();
$message = '';
$messageType = '';

$viewType = ($_GET['type'] ?? 'hotel') === 'pizzeria' ? 'pizzeria' : 'hotel';
$typeLabel = ESTABLISHMENT_TYPES[$viewType]['label'] ?? 'Hôtel';

// Handle form submission (final step)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        $data = [
            'name'     => $_POST['name'] ?? '',
            'slug'     => $_POST['slug'] ?? '',
            'site_url' => $_POST['site_url'] ?? '',
            'admin_url'=> $_POST['admin_url'] ?? '',
            'notes'    => $_POST['notes'] ?? '',
            'type'     => $viewType,
        ];

        $result = createHotel($data);

        if ($result['success']) {
            $hotelId = $result['id'];

            // Provision default data
            provisionHotelData($hotelId);

            // Apply feature toggles
            foreach (AVAILABLE_FEATURES as $key => $meta) {
                $enabled = isset($_POST['feature_' . $key]);
                setFeatureToggle($hotelId, $key, $enabled);
            }

            logAudit($_SESSION['super_admin_id'], 'hotel_created', $hotelId, json_encode([
                'name' => $data['name'],
                'type' => $viewType,
                'via'  => 'onboarding_wizard',
            ]));

            header('Location: index.php?type=' . urlencode($viewType) . '&created=1');
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'error';
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
    <title>Nouvel établissement | Super Admin</title>
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
                <h1>Nouveau <?= htmlspecialchars(strtolower($typeLabel)) ?></h1>
                <a href="index.php?type=<?= htmlspecialchars($viewType) ?>" class="btn btn-outline btn-sm">Annuler</a>
            </header>

            <div class="sa-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <!-- Step Indicator -->
                <div class="wizard-steps">
                    <div class="wizard-step-indicator">
                        <div class="wizard-step-item">
                            <div class="wizard-step-circle active" id="circle-1">1</div>
                            <span class="wizard-step-label">Informations</span>
                        </div>
                    </div>
                    <div class="wizard-step-line" id="line-1"></div>
                    <div class="wizard-step-indicator">
                        <div class="wizard-step-item">
                            <div class="wizard-step-circle" id="circle-2">2</div>
                            <span class="wizard-step-label">Configuration</span>
                        </div>
                    </div>
                    <div class="wizard-step-line" id="line-2"></div>
                    <div class="wizard-step-indicator">
                        <div class="wizard-step-item">
                            <div class="wizard-step-circle" id="circle-3">3</div>
                            <span class="wizard-step-label">Fonctionnalités</span>
                        </div>
                    </div>
                    <div class="wizard-step-line" id="line-3"></div>
                    <div class="wizard-step-indicator">
                        <div class="wizard-step-item">
                            <div class="wizard-step-circle" id="circle-4">4</div>
                            <span class="wizard-step-label">Résumé</span>
                        </div>
                    </div>
                </div>

                <form method="POST" id="wizardForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($viewType) ?>">

                    <div class="wizard-content">
                        <!-- Step 1: Basic Info -->
                        <div class="wizard-panel active" data-step="1">
                            <div class="card">
                                <div class="card-header"><h2>Informations de base</h2></div>
                                <div class="card-body" style="max-width: 600px;">
                                    <div class="form-group">
                                        <label for="name">Nom de l'établissement *</label>
                                        <input type="text" id="name" name="name" required placeholder="Ex: Hôtel du Parc">
                                    </div>
                                    <div class="form-group">
                                        <label for="slug">Slug (identifiant URL) *</label>
                                        <input type="text" id="slug" name="slug" required placeholder="hotel-du-parc" pattern="[a-z0-9-]+">
                                        <small>Minuscules, chiffres et tirets uniquement. Utilisé dans les URLs.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="notes">Notes (optionnel)</label>
                                        <textarea id="notes" name="notes" rows="3" placeholder="Informations internes..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Configuration -->
                        <div class="wizard-panel" data-step="2">
                            <div class="card">
                                <div class="card-header"><h2>Configuration</h2></div>
                                <div class="card-body" style="max-width: 600px;">
                                    <div class="form-group">
                                        <label for="site_url">URL du site client</label>
                                        <input type="url" id="site_url" name="site_url" placeholder="https://...">
                                        <small>Auto-rempli depuis le slug si vide</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="admin_url">URL du panel admin</label>
                                        <input type="url" id="admin_url" name="admin_url" placeholder="https://...">
                                        <small>Auto-rempli depuis le slug si vide</small>
                                    </div>
                                    <div class="alert alert-warning" style="margin-top: 1rem;">
                                        <strong>Compte admin par défaut :</strong> admin / admin123<br>
                                        <small>Changez le mot de passe dès la première connexion.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Features -->
                        <div class="wizard-panel" data-step="3">
                            <div class="card">
                                <div class="card-header"><h2>Fonctionnalités activées</h2></div>
                                <div class="card-body">
                                    <?php foreach (AVAILABLE_FEATURES as $key => $meta): ?>
                                        <div class="feature-row">
                                            <div class="feature-info">
                                                <span class="feature-label"><?= htmlspecialchars($meta['label']) ?></span>
                                                <span class="feature-desc"><?= htmlspecialchars($meta['description']) ?></span>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="feature_<?= htmlspecialchars($key) ?>" value="1" checked>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Summary -->
                        <div class="wizard-panel" data-step="4">
                            <div class="card">
                                <div class="card-header"><h2>Résumé</h2></div>
                                <div class="card-body wizard-summary">
                                    <dl>
                                        <dt>Type</dt>
                                        <dd><?= htmlspecialchars($typeLabel) ?></dd>
                                        <dt>Nom</dt>
                                        <dd id="summary-name">—</dd>
                                        <dt>Slug</dt>
                                        <dd id="summary-slug">—</dd>
                                        <dt>URL Client</dt>
                                        <dd id="summary-site-url">—</dd>
                                        <dt>URL Admin</dt>
                                        <dd id="summary-admin-url">—</dd>
                                        <dt>Notes</dt>
                                        <dd id="summary-notes">—</dd>
                                        <dt>Fonctionnalités</dt>
                                        <dd id="summary-features">—</dd>
                                        <dt>Admin par défaut</dt>
                                        <dd>admin / admin123</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="wizard-nav">
                        <button type="button" class="btn btn-outline" id="prevBtn" style="display: none;" onclick="wizardNav(-1)">Précédent</button>
                        <div></div>
                        <button type="button" class="btn btn-primary" id="nextBtn" onclick="wizardNav(1)">Suivant</button>
                        <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            Créer l'établissement
                        </button>
                    </div>
                </form>
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
    let slugManuallyEdited = false;

    slugInput.addEventListener('input', () => { slugManuallyEdited = true; });

    nameInput.addEventListener('input', () => {
        if (!slugManuallyEdited) {
            slugInput.value = nameInput.value
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            updateUrls();
        }
    });

    // Auto-fill URLs from slug
    function updateUrls() {
        const slug = slugInput.value;
        const siteUrl = document.getElementById('site_url');
        const adminUrl = document.getElementById('admin_url');
        if (!siteUrl.value || siteUrl.dataset.autoFilled === 'true') {
            siteUrl.value = slug ? 'https://hothello-client.onrender.com/?hotel=' + slug : '';
            siteUrl.dataset.autoFilled = 'true';
        }
        if (!adminUrl.value || adminUrl.dataset.autoFilled === 'true') {
            adminUrl.value = 'https://hothello-admin.onrender.com';
            adminUrl.dataset.autoFilled = 'true';
        }
    }

    slugInput.addEventListener('input', updateUrls);
    document.getElementById('site_url').addEventListener('input', function() { this.dataset.autoFilled = 'false'; });
    document.getElementById('admin_url').addEventListener('input', function() { this.dataset.autoFilled = 'false'; });

    // Wizard navigation
    let currentStep = 1;
    const totalSteps = 4;

    function wizardNav(direction) {
        // Validate current step
        if (direction > 0 && currentStep === 1) {
            const name = document.getElementById('name').value.trim();
            const slug = document.getElementById('slug').value.trim();
            if (!name || !slug) {
                alert('Le nom et le slug sont obligatoires.');
                return;
            }
            if (!/^[a-z0-9-]+$/.test(slug)) {
                alert('Le slug ne peut contenir que des minuscules, chiffres et tirets.');
                return;
            }
        }

        // Move step
        const newStep = currentStep + direction;
        if (newStep < 1 || newStep > totalSteps) return;

        // Update panels
        document.querySelector('.wizard-panel[data-step="' + currentStep + '"]').classList.remove('active');
        document.querySelector('.wizard-panel[data-step="' + newStep + '"]').classList.add('active');

        // Update circles
        for (let i = 1; i <= totalSteps; i++) {
            const circle = document.getElementById('circle-' + i);
            const line = document.getElementById('line-' + (i - 1));
            circle.classList.remove('active', 'completed');
            if (i < newStep) {
                circle.classList.add('completed');
                circle.innerHTML = '&#10003;';
            } else if (i === newStep) {
                circle.classList.add('active');
                circle.textContent = i;
            } else {
                circle.textContent = i;
            }
            if (line) {
                line.classList.toggle('completed', i <= newStep);
            }
        }

        currentStep = newStep;

        // Show/hide buttons
        document.getElementById('prevBtn').style.display = currentStep > 1 ? '' : 'none';
        document.getElementById('nextBtn').style.display = currentStep < totalSteps ? '' : 'none';
        document.getElementById('submitBtn').style.display = currentStep === totalSteps ? '' : 'none';

        // Update summary on last step
        if (currentStep === totalSteps) {
            updateSummary();
        }
    }

    function updateSummary() {
        document.getElementById('summary-name').textContent = document.getElementById('name').value || '—';
        document.getElementById('summary-slug').textContent = document.getElementById('slug').value || '—';
        document.getElementById('summary-site-url').textContent = document.getElementById('site_url').value || '(auto)';
        document.getElementById('summary-admin-url').textContent = document.getElementById('admin_url').value || '(auto)';
        document.getElementById('summary-notes').textContent = document.getElementById('notes').value || '—';

        // Features
        const features = [];
        document.querySelectorAll('.wizard-panel[data-step="3"] input[type="checkbox"]').forEach(cb => {
            if (cb.checked) {
                const label = cb.closest('.feature-row').querySelector('.feature-label').textContent;
                features.push(label);
            }
        });
        document.getElementById('summary-features').textContent = features.length > 0 ? features.join(', ') : 'Aucune';
    }
    </script>
</body>
</html>
