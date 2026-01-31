<?php
/**
 * Guest Messages Management
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$csrfToken = generateCsrfToken();
$statuses = getGuestMessageStatuses();
$categories = getGuestMessageCategories();

// Handle POST requests
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'update_status':
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? '';
                if (updateGuestMessageStatus($id, $status)) {
                    $message = 'Statut mis à jour.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'update_notes':
                $id = intval($_POST['id'] ?? 0);
                $notes = trim($_POST['admin_notes'] ?? '');
                if (updateGuestMessageNotes($id, $notes)) {
                    $message = 'Notes mises à jour.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if (deleteGuestMessage($id)) {
                    $message = 'Message supprimé.';
                    $messageType = 'success';
                    // Redirect to list if we were viewing the deleted message
                    if (isset($_GET['view']) && intval($_GET['view']) === $id) {
                        header('Location: room-service-messages.php?deleted=1');
                        exit;
                    }
                } else {
                    $message = 'Erreur lors de la suppression.';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Check for redirect message
if (isset($_GET['deleted'])) {
    $message = 'Message supprimé.';
    $messageType = 'success';
}

// Filter by status
$statusFilter = $_GET['status'] ?? 'all';

// Sort options
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';

// Get messages
$messages = getGuestMessages($statusFilter, $sortBy, $sortOrder);
$stats = getGuestMessagesStats();
$unreadMessages = $stats['new'];
$pendingOrders = getPendingOrdersCount();

// Get message details if viewing specific message
$viewMessage = null;
if (isset($_GET['view'])) {
    $viewMessage = getGuestMessageById(intval($_GET['view']));
    // Mark as read if it's new
    if ($viewMessage && $viewMessage['status'] === 'new') {
        updateGuestMessageStatus($viewMessage['id'], 'read');
        $viewMessage['status'] = 'read';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Messages Clients | Admin Hôtel Corintel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .messages-table {
            width: 100%;
            border-collapse: collapse;
        }
        .messages-table th,
        .messages-table td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--admin-border);
        }
        .messages-table th {
            font-weight: 600;
            color: var(--admin-text-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .messages-table tr:hover {
            background: var(--admin-bg);
        }
        .messages-table tr.unread {
            background: rgba(66, 153, 225, 0.05);
            font-weight: 500;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 20px;
        }
        .status-new {
            background: rgba(66, 153, 225, 0.1);
            color: #2B6CB0;
        }
        .status-read {
            background: rgba(160, 174, 192, 0.2);
            color: #4A5568;
        }
        .status-in_progress {
            background: rgba(237, 137, 54, 0.1);
            color: #C05621;
        }
        .status-resolved {
            background: rgba(72, 187, 120, 0.1);
            color: #276749;
        }
        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            font-size: 0.7rem;
            font-weight: 500;
            border-radius: 4px;
            background: var(--admin-bg);
            color: var(--admin-text-light);
        }
        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--admin-text-light);
            font-size: 0.875rem;
        }
        .message-details {
            background: var(--admin-bg);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .message-details h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .message-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .message-meta-item {
            display: flex;
            flex-direction: column;
        }
        .message-meta-item label {
            font-size: 0.75rem;
            color: var(--admin-text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .message-meta-item span {
            font-weight: 500;
        }
        .message-content {
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        .admin-notes-section {
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
        }
        .admin-notes-section h4 {
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .admin-notes-section h4 svg {
            width: 16px;
            height: 16px;
            color: var(--admin-primary);
        }
        .admin-notes-section textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.75rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.9rem;
            resize: vertical;
            margin-bottom: 0.75rem;
        }
        .admin-notes-section textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .status-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status-form select {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            background: var(--admin-card);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--admin-text-light);
        }
        .back-link:hover {
            color: var(--admin-primary);
        }
        .back-link svg {
            width: 16px;
            height: 16px;
        }
        .actions-row {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--admin-border);
        }
        .btn-danger {
            background: #E53E3E;
            color: white;
        }
        .btn-danger:hover {
            background: #C53030;
        }
        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .toast {
            background: var(--admin-card);
            border-radius: 8px;
            padding: 1rem 1.25rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: toastIn 0.3s ease-out;
            max-width: 320px;
            border-left: 4px solid #3182CE;
        }
        @keyframes toastIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            color: #3182CE;
        }
        .toast-content {
            flex: 1;
        }
        .toast-title {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.125rem;
        }
        .toast-message {
            font-size: 0.8rem;
            color: var(--admin-text-light);
        }
        .toast-close {
            background: none;
            border: none;
            color: var(--admin-text-light);
            cursor: pointer;
            padding: 0.25rem;
        }
        .toast-close:hover {
            color: var(--admin-text);
        }
        /* Row highlight animation */
        @keyframes highlightRow {
            0% { background: rgba(66, 153, 225, 0.2); }
            100% { background: transparent; }
        }
        .row-highlight {
            animation: highlightRow 2s ease-out;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <button class="sidebar-close" id="sidebarClose" aria-label="Fermer le menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <div class="sidebar-header">
                <h2>Hôtel Corintel</h2>
                <span>Administration</span>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Tableau de bord
                </a>
                <a href="images.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    Gestion des images
                </a>
                <a href="room-service-stats.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Statistiques
                </a>
                <a href="room-service-items.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                        <line x1="6" y1="1" x2="6" y2="4"/>
                        <line x1="10" y1="1" x2="10" y2="4"/>
                        <line x1="14" y1="1" x2="14" y2="4"/>
                    </svg>
                    Room Service - Articles
                </a>
                <a href="room-service-categories.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Room Service - Horaires
                </a>
                <a href="room-service-orders.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    Room Service - Commandes
                    <?php if ($pendingOrders > 0): ?>
                        <span class="badge" style="background: #E53E3E; color: white; margin-left: auto;"><?= $pendingOrders ?></span>
                    <?php endif; ?>
                </a>
                <a href="room-service-messages.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages Clients
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge" style="background: #E53E3E; color: white; margin-left: auto;"><?= $unreadMessages ?></span>
                    <?php endif; ?>
                </a>
                <a href="settings.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Paramètres
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-name"><?= h($admin['username']) ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Déconnexion
                </a>
            </div>
        </aside>

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
                <h1>Messages Clients</h1>
                <a href="../contact.php" target="_blank" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    Voir le formulaire
                </a>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= h($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($viewMessage): ?>
                    <!-- Message Detail View -->
                    <a href="room-service-messages.php<?= $statusFilter !== 'all' ? '?status=' . h($statusFilter) : '' ?>" class="back-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                        Retour aux messages
                    </a>

                    <div class="message-details">
                        <h3>
                            Message #<?= $viewMessage['id'] ?>
                            <span class="status-badge status-<?= $viewMessage['status'] ?>">
                                <?= h($statuses[$viewMessage['status']] ?? $viewMessage['status']) ?>
                            </span>
                            <span class="category-badge">
                                <?= h($categories[$viewMessage['category']] ?? $viewMessage['category']) ?>
                            </span>
                        </h3>

                        <div class="message-meta">
                            <div class="message-meta-item">
                                <label>Chambre</label>
                                <span style="font-size: 1.1rem; color: var(--admin-primary);"><?= h($viewMessage['room_number']) ?></span>
                            </div>
                            <?php if ($viewMessage['guest_name']): ?>
                            <div class="message-meta-item">
                                <label>Nom du client</label>
                                <span><?= h($viewMessage['guest_name']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($viewMessage['subject']): ?>
                            <div class="message-meta-item">
                                <label>Objet</label>
                                <span><?= h($viewMessage['subject']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="message-meta-item">
                                <label>Reçu le</label>
                                <span><?= formatDate($viewMessage['created_at']) ?></span>
                            </div>
                            <div class="message-meta-item">
                                <label>Modifier le statut</label>
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $viewMessage['id'] ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <?php foreach ($statuses as $key => $label): ?>
                                            <option value="<?= h($key) ?>" <?= $viewMessage['status'] === $key ? 'selected' : '' ?>>
                                                <?= h($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <h4 style="margin-bottom: 0.5rem; color: var(--admin-text-light); font-size: 0.75rem; text-transform: uppercase;">Message</h4>
                        <div class="message-content"><?= nl2br(h($viewMessage['message'])) ?></div>

                        <div class="admin-notes-section">
                            <h4>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Notes internes (non visibles par le client)
                            </h4>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                <input type="hidden" name="action" value="update_notes">
                                <input type="hidden" name="id" value="<?= $viewMessage['id'] ?>">
                                <textarea name="admin_notes" placeholder="Ajoutez des notes sur ce message (actions entreprises, suivi, etc.)"><?= h($viewMessage['admin_notes'] ?? '') ?></textarea>
                                <button type="submit" class="btn btn-sm btn-primary">Enregistrer les notes</button>
                            </form>
                        </div>

                        <div class="actions-row">
                            <div></div>
                            <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $viewMessage['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Messages List View -->

                    <!-- Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $stats['total'] ?></span>
                                <span class="stat-label">Total messages</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(66, 153, 225, 0.1);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #3182CE;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $stats['new'] ?></span>
                                <span class="stat-label">Non lus</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(237, 137, 54, 0.1);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #ED8936;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $stats['in_progress'] ?></span>
                                <span class="stat-label">En cours</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?= $stats['today'] ?></span>
                                <span class="stat-label">Aujourd'hui</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter Tabs -->
                    <div class="section-tabs">
                        <a href="?status=all&sort=<?= h($sortBy) ?>&order=<?= h($sortOrder) ?>" class="section-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">
                            Tous
                        </a>
                        <?php foreach ($statuses as $key => $label): ?>
                            <a href="?status=<?= h($key) ?>&sort=<?= h($sortBy) ?>&order=<?= h($sortOrder) ?>" class="section-tab <?= $statusFilter === $key ? 'active' : '' ?>">
                                <?= h($label) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Messages Table -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Liste des messages</h2>
                            <span class="badge"><?= count($messages) ?> messages</span>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($messages)): ?>
                                <p class="empty-state">Aucun message<?= $statusFilter !== 'all' ? ' avec ce statut' : '' ?>.</p>
                            <?php else: ?>
                                <table class="messages-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Chambre</th>
                                            <th>Catégorie</th>
                                            <th>Message</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $msg): ?>
                                            <tr class="<?= $msg['status'] === 'new' ? 'unread' : '' ?>">
                                                <td><?= $msg['id'] ?></td>
                                                <td><strong><?= h($msg['room_number']) ?></strong></td>
                                                <td>
                                                    <span class="category-badge">
                                                        <?= h($categories[$msg['category']] ?? $msg['category']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="message-preview">
                                                        <?php if ($msg['subject']): ?>
                                                            <strong><?= h($msg['subject']) ?></strong> -
                                                        <?php endif; ?>
                                                        <?= h($msg['message']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?= $msg['status'] ?>">
                                                        <?= h($statuses[$msg['status']] ?? $msg['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= formatDate($msg['created_at']) ?></td>
                                                <td>
                                                    <a href="?view=<?= $msg['id'] ?>" class="btn btn-sm btn-outline">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                            <circle cx="12" cy="12" r="3"/>
                                                        </svg>
                                                        Voir
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
    // Mobile menu toggle
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

    // ============================================
    // Real-Time Updates
    // ============================================
    const POLL_INTERVAL = 15000; // 15 seconds
    let pollTimer = null;
    let lastMessageCount = <?= count($messages) ?>;
    let lastMessageIds = [<?= implode(',', array_column($messages, 'id')) ?>];
    let isPageVisible = true;

    // Toast notification
    function showToast(title, message) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;
        container.appendChild(toast);

        // Play notification sound
        playNotificationSound();

        // Auto-remove after 8 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'toastIn 0.3s ease-out reverse';
                setTimeout(() => toast.remove(), 300);
            }
        }, 8000);
    }

    // Notification sound
    function playNotificationSound() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.frequency.value = 600;
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);

            oscillator.start(audioCtx.currentTime);
            oscillator.stop(audioCtx.currentTime + 0.3);
        } catch (e) {
            // Silent fail if audio not supported
        }
    }

    // Fetch updates
    async function fetchUpdates() {
        try {
            const params = new URLSearchParams({
                status: '<?= h($statusFilter) ?>',
                sort: '<?= h($sortBy) ?>',
                order: '<?= h($sortOrder) ?>'
            });

            console.log('[Live Update] Fetching messages...');
            const response = await fetch('api/messages-updates.php?' + params.toString());
            if (!response.ok) {
                console.error('[Live Update] Response not OK:', response.status);
                return;
            }

            const data = await response.json();
            console.log('[Live Update] Data received:', data);
            if (!data.success) {
                console.error('[Live Update] API error:', data.error);
                return;
            }

            // Update sidebar badges
            updateBadge('pendingOrders', data.data.pendingOrders);
            updateBadge('unreadMessages', data.data.unreadMessages);

            // Update stats
            if (data.data.stats) {
                updateStats(data.data.stats);
            }

            // Check for new messages
            const currentMessages = data.data.messages || [];
            const currentIds = currentMessages.map(m => m.id);
            const newMessageIds = currentIds.filter(id => !lastMessageIds.includes(id));

            console.log('[Live Update] Current IDs:', currentIds);
            console.log('[Live Update] Last IDs:', lastMessageIds);
            console.log('[Live Update] New IDs:', newMessageIds);

            if (newMessageIds.length > 0) {
                // Show toast for new messages
                newMessageIds.forEach(id => {
                    const msg = currentMessages.find(m => m.id === id);
                    if (msg) {
                        showToast('Nouveau message', `Chambre ${msg.room_number} - ${msg.category_label}`);
                    }
                });
                // Update table
                updateMessagesTable(currentMessages);
            }

            // Update count badge
            const countBadge = document.querySelector('.card-header .badge');
            if (countBadge) {
                countBadge.textContent = data.data.messageCount + ' messages';
            }

            lastMessageCount = data.data.messageCount;
            lastMessageIds = currentIds;

        } catch (e) {
            console.error('Polling error:', e);
        }
    }

    // Update badge counts
    function updateBadge(type, count) {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            if (type === 'pendingOrders' && item.href.includes('room-service-orders')) {
                let badge = item.querySelector('.badge');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge';
                        badge.style.cssText = 'background: #E53E3E; color: white; margin-left: auto;';
                        item.appendChild(badge);
                    }
                    badge.textContent = count;
                } else if (badge) {
                    badge.remove();
                }
            }
            if (type === 'unreadMessages' && item.href.includes('room-service-messages')) {
                let badge = item.querySelector('.badge');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge';
                        badge.style.cssText = 'background: #E53E3E; color: white; margin-left: auto;';
                        item.appendChild(badge);
                    }
                    badge.textContent = count;
                } else if (badge) {
                    badge.remove();
                }
            }
        });
    }

    // Update stats cards
    function updateStats(stats) {
        const statValues = document.querySelectorAll('.stat-value');
        if (statValues.length >= 4) {
            statValues[0].textContent = stats.total;
            statValues[1].textContent = stats.new;
            statValues[2].textContent = stats.in_progress;
            statValues[3].textContent = stats.today;
        }
    }

    // Update messages table
    function updateMessagesTable(messages) {
        const tbody = document.querySelector('.messages-table tbody');
        if (!tbody) return;

        // Clear and rebuild table
        tbody.innerHTML = messages.map(msg => {
            const isNew = !lastMessageIds.includes(msg.id);
            const isUnread = msg.status === 'new';

            return `
                <tr class="${isUnread ? 'unread' : ''} ${isNew ? 'row-highlight' : ''}">
                    <td>${msg.id}</td>
                    <td><strong>${escapeHtml(msg.room_number)}</strong></td>
                    <td>
                        <span class="category-badge">
                            ${escapeHtml(msg.category_label)}
                        </span>
                    </td>
                    <td>
                        <div class="message-preview">
                            ${msg.subject ? '<strong>' + escapeHtml(msg.subject) + '</strong> - ' : ''}
                            ${escapeHtml(msg.message_preview)}
                        </div>
                    </td>
                    <td>
                        <span class="status-badge status-${msg.status}">
                            ${escapeHtml(msg.status_label)}
                        </span>
                    </td>
                    <td>${msg.created_at}</td>
                    <td>
                        <a href="?view=${msg.id}" class="btn btn-sm btn-outline">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Voir
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // HTML escape utility
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    // Visibility API for pause/resume
    document.addEventListener('visibilitychange', function() {
        isPageVisible = !document.hidden;
        if (isPageVisible) {
            fetchUpdates(); // Immediate update when tab becomes visible
            startPolling();
        } else {
            stopPolling();
        }
    });

    function startPolling() {
        if (!pollTimer && isPageVisible) {
            pollTimer = setInterval(fetchUpdates, POLL_INTERVAL);
        }
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    // Start polling only on list view (not detail view)
    <?php if (!$viewMessage): ?>
    console.log('[Live Update] Starting polling with interval:', POLL_INTERVAL, 'ms');
    console.log('[Live Update] Initial message IDs:', lastMessageIds);
    startPolling();
    // Also fetch immediately on page load
    fetchUpdates();
    <?php endif; ?>
    </script>
</body>
</html>
