<?php
/**
 * Super Admin - Database Health Panel
 * Real PostgreSQL metrics from system catalogs
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';
require_once __DIR__ . '/includes/super-functions.php';

superRequireAuth();

$currentPage = 'db-health.php';
$health = getDatabaseHealth();
$maxSize = max(1, max(array_column($health['schemas'], 'size')));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Santé DB | Super Admin</title>
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
                <h1>Santé de la base de données</h1>
                <span style="color: var(--sa-text-light); font-size: 0.8rem;" id="lastUpdate">MAJ: maintenant</span>
            </header>

            <div class="sa-content">
                <!-- Summary Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(66, 153, 225, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-primary);">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="dbLatency"><?= $health['db_latency_ms'] ?> ms</div>
                            <div class="stat-label">Latence DB</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(72, 187, 120, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-success);">
                                <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="totalSize"><?= htmlspecialchars($health['total_size_with_quota']) ?></div>
                            <div class="stat-label">Taille totale DB <small style="opacity:0.6;">(quota Supabase)</small></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(72, 187, 120, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-success);">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="schemaCount"><?= $health['schema_count'] ?></div>
                            <div class="stat-label">Schémas hôtel</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(237, 137, 54, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-warning);">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="activeConn"><?= $health['connections']['active'] ?> / <?= $health['connections']['total'] ?></div>
                            <div class="stat-label">Connexions (actives/total)</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(245, 101, 101, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-error);">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="deadTuples"><?= number_format($health['total_dead']) ?></div>
                            <div class="stat-label">Dead tuples</div>
                        </div>
                    </div>
                </div>

                <!-- Connection Pool Visualization -->
                <div class="metric-card" style="margin-bottom: 1.5rem;">
                    <div class="metric-card-header">
                        <h3>Pool de connexions</h3>
                        <span style="font-size: 0.8rem; color: var(--sa-text-light);">
                            <?= $health['connections']['active'] ?> actives, <?= $health['connections']['idle'] ?> idle
                        </span>
                    </div>
                    <?php
                    $connTotal = max(1, $health['connections']['total']);
                    $activePct = round(($health['connections']['active'] / $connTotal) * 100);
                    $idlePct = 100 - $activePct;
                    ?>
                    <div class="connection-pool">
                        <div class="pool-active" style="width: <?= $activePct ?>%;" title="Actives: <?= $health['connections']['active'] ?>"></div>
                        <div class="pool-idle" style="width: <?= $idlePct ?>%;" title="Idle: <?= $health['connections']['idle'] ?>"></div>
                    </div>
                    <div class="gauge-label" style="margin-top: 0.25rem;">
                        <span style="color: var(--sa-success);">Actives (<?= $activePct ?>%)</span>
                        <span>Idle (<?= $idlePct ?>%)</span>
                    </div>
                </div>

                <!-- Per-Schema Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>Détail par schéma</h2>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Schéma</th>
                                    <th>Taille</th>
                                    <th style="min-width: 120px;"></th>
                                    <th style="text-align: right;">Lignes</th>
                                    <th style="text-align: right;">Dead tuples</th>
                                    <th>Dernier vacuum</th>
                                </tr>
                            </thead>
                            <tbody id="schemasBody">
                                <?php foreach ($health['schemas'] as $s):
                                    $sizePct = $maxSize > 0 ? round((($s['size'] ?? 0) / $maxSize) * 100) : 0;
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                        <td style="white-space: nowrap;"><?= htmlspecialchars($s['size_human'] ?? '0 B') ?></td>
                                        <td>
                                            <div class="size-bar">
                                                <div class="size-bar-fill" style="width: <?= $sizePct ?>%;"></div>
                                            </div>
                                        </td>
                                        <td style="text-align: right; font-family: monospace;"><?= number_format($s['live_rows']) ?></td>
                                        <td style="text-align: right; font-family: monospace; <?= $s['dead_rows'] > 1000 ? 'color: var(--sa-error);' : '' ?>"><?= number_format($s['dead_rows']) ?></td>
                                        <td style="font-size: 0.8rem; color: var(--sa-text-light);">
                                            <?= $s['last_vacuum'] ? htmlspecialchars(substr($s['last_vacuum'], 0, 16)) : '<em>jamais</em>' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Lignes par schéma -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2>Lignes par schéma</h2>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Schéma</th>
                                    <th style="text-align: right;">Lignes totales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $hasRows = false;
                                foreach ($health['schemas'] as $s):
                                    if ($s['live_rows'] > 0) $hasRows = true;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['name']) ?></td>
                                        <td style="text-align: right; font-family: monospace;"><?= number_format($s['live_rows']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($health['schemas'])): ?>
                                    <tr><td colspan="2" style="text-align: center; color: var(--sa-text-light);">Aucun schéma détecté</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Glossary -->
                <div class="metrics-glossary">
                    <div class="metrics-glossary-title">Glossaire des métriques</div>
                    <div class="metrics-glossary-grid">
                        <div class="glossary-item is-real">
                            <div>
                                <div class="glossary-item-label">⚡ Latence DB <span class="badge badge-hotel" style="font-size:0.6rem;">réel</span></div>
                                <div class="glossary-item-desc">Temps que met la base de données pour répondre à une requête simple. En dessous de 50 ms c'est excellent, au-dessus de 200 ms il peut y avoir un problème de réseau ou de charge.</div>
                            </div>
                        </div>
                        <div class="glossary-item is-real">
                            <div>
                                <div class="glossary-item-label">🗄️ Taille totale DB <span class="badge badge-hotel" style="font-size:0.6rem;">réel</span></div>
                                <div class="glossary-item-desc">Espace disque total occupé par l'ensemble de la base de données, incluant tous les hôtels et données système. Affiché sur le quota de votre offre Supabase. Si vous approchez 80–90% de la limite, envisagez de passer sur une offre supérieure.</div>
                            </div>
                        </div>
                        <div class="glossary-item is-real">
                            <div>
                                <div class="glossary-item-label">🏗️ Schémas hôtel <span class="badge badge-hotel" style="font-size:0.6rem;">réel</span></div>
                                <div class="glossary-item-desc">Chaque hôtel ou pizzeria a son propre espace isolé dans la base de données (appelé "schéma"). C'est comme un dossier séparé qui contient toutes les tables de cet établissement : commandes, messages, chambres, etc.</div>
                            </div>
                        </div>
                        <div class="glossary-item is-real">
                            <div>
                                <div class="glossary-item-label">🔗 Pool de connexions <span class="badge badge-hotel" style="font-size:0.6rem;">réel</span></div>
                                <div class="glossary-item-desc">Un "pool" est un groupe de connexions partagées entre toutes les requêtes de votre application. Plutôt que d'ouvrir/fermer une connexion à chaque requête (lent), on réutilise des connexions déjà ouvertes. <strong>Actives</strong> = requête en cours. <strong>Idle</strong> = connexion ouverte, en attente.</div>
                            </div>
                        </div>
                        <div class="glossary-item is-real">
                            <div>
                                <div class="glossary-item-label">🧹 Dead tuples <span class="badge badge-hotel" style="font-size:0.6rem;">réel</span></div>
                                <div class="glossary-item-desc">Lignes "fantômes" laissées après des suppressions ou modifications. PostgreSQL ne supprime pas immédiatement les anciennes données — il les marque comme mortes et les nettoie plus tard via le "vacuum". Un nombre élevé de dead tuples ralentit les recherches.</div>
                            </div>
                        </div>
                        <div class="glossary-item is-real">
                            <div>
                                <div class="glossary-item-label">🔄 Dernier vacuum <span class="badge badge-hotel" style="font-size:0.6rem;">réel</span></div>
                                <div class="glossary-item-desc">Date du dernier nettoyage automatique de la table par PostgreSQL. Le vacuum récupère l'espace occupé par les dead tuples. Si cette date est très ancienne (ou "jamais") sur une table active, c'est un signal que l'autovacuum devrait être vérifié.</div>
                            </div>
                        </div>
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

    // Auto-refresh every 60s
    setInterval(() => {
        fetch('api/db-health-data.php')
        .then(r => r.json())
        .then(result => {
            if (!result.success) return;
            const d = result.data;
            document.getElementById('totalSize').textContent = d.total_size_with_quota;
            document.getElementById('schemaCount').textContent = d.schema_count;
            document.getElementById('activeConn').textContent = d.connections.active + ' / ' + d.connections.total;
            document.getElementById('deadTuples').textContent = Number(d.total_dead).toLocaleString();
            document.getElementById('lastUpdate').textContent = 'MAJ: ' + new Date().toLocaleTimeString('fr-FR');
        })
        .catch(() => {});
    }, 60000);
    </script>
</body>
</html>
