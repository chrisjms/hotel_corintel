<?php
/**
 * Super Admin - Server Monitoring Dashboard
 * Real DB metrics + simulated system metrics
 */

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/includes/super-auth.php';
require_once __DIR__ . '/includes/super-functions.php';

superRequireAuth();

$currentPage = 'monitoring.php';
$metrics = getMonitoringMetrics();
$cpuColor = $metrics['cpu_percent'] < 60 ? 'green' : ($metrics['cpu_percent'] < 85 ? 'yellow' : 'red');
$ramColor = $metrics['ram_percent'] < 60 ? 'green' : ($metrics['ram_percent'] < 85 ? 'yellow' : 'red');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Monitoring | Super Admin</title>
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
                <h1>Monitoring</h1>
                <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--sa-text-light); font-size: 0.8rem;">
                    <span class="pulse-dot"></span>
                    <span>Système en ligne</span>
                    <span style="margin-left: 0.5rem;" id="lastUpdate">MAJ: maintenant</span>
                </div>
            </header>

            <div class="sa-content">
                <!-- Top Stats — server only -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(66, 153, 225, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-primary);">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-value" id="uptime"><?= $metrics['uptime_days'] ?> jours <span class="badge badge-simulated">simulé</span></div>
                            <div class="stat-label">Uptime</div>
                        </div>
                    </div>
                </div>

                <!-- CPU, RAM & Requests Gauges -->
                <div class="metric-grid">
                    <div class="metric-card">
                        <div class="metric-card-header">
                            <h3>CPU <span class="badge badge-simulated">simulé</span></h3>
                            <span style="font-size: 0.8rem; color: var(--sa-text-light);" id="cpuLabel"><?= $metrics['cpu_percent'] ?>%</span>
                        </div>
                        <div class="metric-value-large" id="cpuValue"><?= $metrics['cpu_percent'] ?>%</div>
                        <div class="gauge-bar">
                            <div class="gauge-fill <?= $cpuColor ?>" id="cpuFill" style="width: <?= $metrics['cpu_percent'] ?>%;"></div>
                        </div>
                        <div class="gauge-label">
                            <span>0%</span>
                            <span>100%</span>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-card-header">
                            <h3>RAM <span class="badge badge-simulated">simulé</span></h3>
                            <span style="font-size: 0.8rem; color: var(--sa-text-light);" id="ramLabel"><?= $metrics['ram_percent'] ?>%</span>
                        </div>
                        <div class="metric-value-large" id="ramValue"><?= $metrics['ram_percent'] ?>%</div>
                        <div class="gauge-bar">
                            <div class="gauge-fill <?= $ramColor ?>" id="ramFill" style="width: <?= $metrics['ram_percent'] ?>%;"></div>
                        </div>
                        <div class="gauge-label">
                            <span>0%</span>
                            <span>100%</span>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-card-header">
                            <h3>Requêtes / min <span class="badge badge-simulated">simulé</span></h3>
                        </div>
                        <div class="metric-value-large" id="requestsMin"><?= $metrics['requests_min'] ?></div>
                        <div style="font-size: 0.8rem; color: var(--sa-text-light);">Trafic estimé</div>
                    </div>
                </div>

                <!-- Glossary -->
                <div class="metrics-glossary">
                    <div class="metrics-glossary-title">Glossaire des métriques</div>
                    <div class="metrics-glossary-grid">
                        <div class="glossary-item is-simulated">
                            <div>
                                <div class="glossary-item-label">⏱️ Uptime <span class="badge badge-simulated">simulé</span></div>
                                <div class="glossary-item-desc">Durée depuis laquelle le serveur tourne sans interruption. Valeur simulée pour l'instant — sera réelle une fois migré sur le VPS OVH via <code>/proc/uptime</code>.</div>
                            </div>
                        </div>
                        <div class="glossary-item is-simulated">
                            <div>
                                <div class="glossary-item-label">🖥️ CPU <span class="badge badge-simulated">simulé</span></div>
                                <div class="glossary-item-desc">Pourcentage d'utilisation du processeur du serveur. En dessous de 60% = normal. 60–85% = charge élevée. Au-dessus de 85% = risque de ralentissements. Valeur simulée — Render.com ne fournit pas cette métrique via API.</div>
                            </div>
                        </div>
                        <div class="glossary-item is-simulated">
                            <div>
                                <div class="glossary-item-label">💾 RAM <span class="badge badge-simulated">simulé</span></div>
                                <div class="glossary-item-desc">Pourcentage de mémoire vive utilisée. Une RAM trop pleine (>90%) force le serveur à utiliser le disque dur à la place, ce qui est beaucoup plus lent. Valeur simulée — sera réelle sur VPS OVH.</div>
                            </div>
                        </div>
                        <div class="glossary-item is-simulated">
                            <div>
                                <div class="glossary-item-label">📊 Requêtes / min <span class="badge badge-simulated">simulé</span></div>
                                <div class="glossary-item-desc">Nombre de requêtes HTTP reçues par le serveur chaque minute. Permet de mesurer le trafic et d'anticiper les besoins en ressources. Valeur simulée — nécessiterait un outil comme Prometheus ou l'API Render pour être réelle.</div>
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
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('sidebarClose');
    if (menuBtn) menuBtn.addEventListener('click', () => { sidebar.classList.add('open'); sidebarOverlay.classList.add('open'); });
    function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('open'); }
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);

    // Auto-refresh
    let pollTimer;

    function gaugeColor(pct) {
        return pct < 60 ? 'green' : (pct < 85 ? 'yellow' : 'red');
    }

    function updateMonitoring() {
        fetch('api/monitoring-data.php')
        .then(r => r.json())
        .then(result => {
            if (!result.success) return;
            const d = result.data;

            document.getElementById('dbLatency').textContent = d.db_latency_ms + ' ms';
            document.getElementById('dbSize').textContent = d.db_size_with_quota;
            document.getElementById('connections').textContent = d.connections.active + ' / ' + d.connections.total;
            document.getElementById('uptime').textContent = d.uptime_days + ' jours';
            document.getElementById('requestsMin').textContent = d.requests_min;

            // CPU
            document.getElementById('cpuValue').textContent = d.cpu_percent + '%';
            document.getElementById('cpuLabel').textContent = d.cpu_percent + '%';
            const cpuFill = document.getElementById('cpuFill');
            cpuFill.style.width = d.cpu_percent + '%';
            cpuFill.className = 'gauge-fill ' + gaugeColor(d.cpu_percent);

            // RAM
            document.getElementById('ramValue').textContent = d.ram_percent + '%';
            document.getElementById('ramLabel').textContent = d.ram_percent + '%';
            const ramFill = document.getElementById('ramFill');
            ramFill.style.width = d.ram_percent + '%';
            ramFill.className = 'gauge-fill ' + gaugeColor(d.ram_percent);

            // Schemas table
            const tbody = document.getElementById('schemasTable');
            if (d.schemas && d.schemas.length > 0) {
                tbody.innerHTML = d.schemas.map(s =>
                    '<tr><td>' + s.name + '</td><td style="text-align:right;font-family:monospace;">' + Number(s.total_rows).toLocaleString() + '</td></tr>'
                ).join('');
            }

            document.getElementById('lastUpdate').textContent = 'MAJ: ' + new Date().toLocaleTimeString('fr-FR');
        })
        .catch(() => {});
    }

    pollTimer = setInterval(updateMonitoring, 30000);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(pollTimer);
        } else {
            updateMonitoring();
            pollTimer = setInterval(updateMonitoring, 30000);
        }
    });
    </script>
</body>
</html>
