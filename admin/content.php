<?php
/**
 * Content Management
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$csrfToken = generateCsrfToken();

// Initialize content tables and seed sections
initContentTables();
seedContentSections();

$message = '';
$messageType = '';

// Get current section from URL
$currentSection = $_GET['section'] ?? null;
$editBlockId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$viewMode = $_GET['view'] ?? 'list'; // list, edit, add

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create':
                $sectionCode = $_POST['section_code'] ?? '';
                $section = getContentSection($sectionCode);

                if (!$section) {
                    $message = 'Section invalide.';
                    $messageType = 'error';
                    break;
                }

                // Handle image upload if provided
                $imageFilename = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleNewContentImageUpload($_FILES['image'], $sectionCode);
                    if ($uploadResult['valid']) {
                        $imageFilename = $uploadResult['filename'];
                    } else {
                        $message = $uploadResult['message'];
                        $messageType = 'error';
                        break;
                    }
                }

                // Check if image is required but not provided
                if ($section['image_mode'] === IMAGE_REQUIRED && empty($imageFilename)) {
                    $message = 'Une image est obligatoire pour cette section.';
                    $messageType = 'error';
                    break;
                }

                $blockId = createContentBlock($sectionCode, [
                    'title' => $_POST['title'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'image_filename' => $imageFilename,
                    'image_alt' => $_POST['image_alt'] ?? '',
                    'link_url' => $_POST['link_url'] ?? '',
                    'link_text' => $_POST['link_text'] ?? '',
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ]);

                if ($blockId) {
                    $message = 'Contenu créé avec succès.';
                    $messageType = 'success';
                    $currentSection = $sectionCode;
                } else {
                    $message = 'Erreur lors de la création. Vérifiez les limites de la section.';
                    $messageType = 'error';
                }
                break;

            case 'update':
                $blockId = (int)($_POST['block_id'] ?? 0);
                $block = getContentBlock($blockId);

                if (!$block) {
                    $message = 'Bloc introuvable.';
                    $messageType = 'error';
                    break;
                }

                $section = getContentSection($block['section_code']);
                $imageFilename = $block['image_filename'];

                // Handle image upload if provided
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleContentBlockImageUpload($_FILES['image'], $blockId);
                    if ($uploadResult['valid']) {
                        $imageFilename = $uploadResult['filename'];
                    } else {
                        $message = $uploadResult['message'];
                        $messageType = 'error';
                        break;
                    }
                }

                // Handle image removal
                if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
                    if ($section['image_mode'] === IMAGE_REQUIRED) {
                        $message = 'Impossible de supprimer l\'image : elle est obligatoire pour cette section.';
                        $messageType = 'error';
                        break;
                    }
                    // Delete file
                    if (!empty($block['image_filename']) && strpos($block['image_filename'], 'uploads/') === 0) {
                        $oldPath = __DIR__ . '/../' . $block['image_filename'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $imageFilename = null;
                }

                $success = updateContentBlock($blockId, [
                    'title' => $_POST['title'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'image_filename' => $imageFilename,
                    'image_alt' => $_POST['image_alt'] ?? '',
                    'link_url' => $_POST['link_url'] ?? '',
                    'link_text' => $_POST['link_text'] ?? '',
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ]);

                if ($success) {
                    $message = 'Contenu mis à jour avec succès.';
                    $messageType = 'success';
                    $currentSection = $block['section_code'];
                    $editBlockId = null;
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $blockId = (int)($_POST['block_id'] ?? 0);
                $block = getContentBlock($blockId);

                if ($block && deleteContentBlock($blockId)) {
                    $message = 'Contenu supprimé.';
                    $messageType = 'success';
                    $currentSection = $block['section_code'];
                } else {
                    $message = 'Erreur lors de la suppression.';
                    $messageType = 'error';
                }
                break;

            case 'reorder':
                $sectionCode = $_POST['section_code'] ?? '';
                $blockIds = json_decode($_POST['block_ids'] ?? '[]', true);

                if ($sectionCode && is_array($blockIds)) {
                    reorderContentBlocks($sectionCode, $blockIds);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }
                break;

            case 'migrate':
                $result = migrateImagesToContentBlocks();
                if ($result['migrated'] > 0) {
                    $message = "{$result['migrated']} image(s) migrée(s) avec succès.";
                    $messageType = 'success';
                } elseif (empty($result['errors'])) {
                    $message = 'Aucune image à migrer.';
                    $messageType = 'info';
                } else {
                    $message = 'Erreurs lors de la migration : ' . implode(', ', $result['errors']);
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get sections grouped by page
$sectionsByPage = getContentSectionsByPage();
$pageNames = getContentPageNames();

// Get blocks for current section if selected
$blocks = [];
$currentSectionData = null;
if ($currentSection) {
    $currentSectionData = getContentSection($currentSection);
    if ($currentSectionData) {
        $blocks = getContentBlocks($currentSection);
    }
}

// Get block for editing
$editBlock = null;
if ($editBlockId) {
    $editBlock = getContentBlock($editBlockId);
    if ($editBlock) {
        $currentSection = $editBlock['section_code'];
        $currentSectionData = getContentSection($currentSection);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestion du contenu | Admin Hôtel Corintel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        /* Section navigation */
        .sections-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .section-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--admin-border);
            background: var(--admin-card);
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            text-decoration: none;
            color: var(--admin-text);
            transition: all 0.2s;
        }
        .section-btn:hover {
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }
        .section-btn.active {
            background: var(--admin-primary);
            border-color: var(--admin-primary);
            color: white;
        }
        .page-group {
            margin-bottom: 1rem;
        }
        .page-group-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--admin-text-light);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        /* Image mode badges */
        .image-mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-required {
            background: rgba(237, 137, 54, 0.1);
            color: #C05621;
        }
        .badge-optional {
            background: rgba(72, 187, 120, 0.1);
            color: #276749;
        }
        .badge-text-only {
            background: rgba(66, 153, 225, 0.1);
            color: #2B6CB0;
        }

        /* Content blocks list */
        .content-blocks {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .content-block-item {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 1rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            transition: box-shadow 0.2s;
        }
        .content-block-item:hover {
            box-shadow: var(--admin-shadow);
        }
        .content-block-item.inactive {
            opacity: 0.6;
        }
        .block-drag-handle {
            cursor: grab;
            color: var(--admin-text-light);
            padding: 0.5rem;
            display: flex;
            align-items: center;
        }
        .block-drag-handle:active {
            cursor: grabbing;
        }
        .block-image {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            background: var(--admin-bg);
            flex-shrink: 0;
        }
        .block-image-placeholder {
            width: 100px;
            height: 70px;
            border-radius: 6px;
            background: var(--admin-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--admin-text-light);
            flex-shrink: 0;
        }
        .block-content {
            flex: 1;
            min-width: 0;
        }
        .block-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--admin-text);
        }
        .block-description {
            font-size: 0.875rem;
            color: var(--admin-text-light);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .block-meta {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--admin-text-light);
        }
        .block-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        /* Form styles */
        .content-form {
            max-width: 700px;
        }
        .content-form .form-group {
            margin-bottom: 1.25rem;
        }
        .content-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--admin-text);
        }
        .content-form input[type="text"],
        .content-form input[type="url"],
        .content-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .content-form input:focus,
        .content-form textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .content-form textarea {
            min-height: 120px;
            resize: vertical;
        }
        .content-form small {
            display: block;
            margin-top: 0.25rem;
            color: var(--admin-text-light);
            font-size: 0.8rem;
        }

        /* Image upload area */
        .image-upload-area {
            border: 2px dashed var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 2rem;
            text-align: center;
            background: var(--admin-bg);
            transition: all 0.2s;
            cursor: pointer;
        }
        .image-upload-area:hover {
            border-color: var(--admin-primary);
            background: rgba(139, 90, 43, 0.05);
        }
        .image-upload-area.dragover {
            border-color: var(--admin-primary);
            background: rgba(139, 90, 43, 0.1);
        }
        .image-upload-area input[type="file"] {
            display: none;
        }
        .image-upload-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 1rem;
            color: var(--admin-text-light);
        }
        .image-upload-text {
            color: var(--admin-text);
            margin-bottom: 0.25rem;
        }
        .image-upload-hint {
            font-size: 0.8rem;
            color: var(--admin-text-light);
        }

        /* Current image preview */
        .current-image-preview {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--admin-bg);
            border-radius: var(--admin-radius);
        }
        .current-image-preview img {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
        }
        .current-image-info {
            flex: 1;
        }
        .current-image-info p {
            margin: 0 0 0.5rem;
            font-size: 0.875rem;
            color: var(--admin-text-light);
        }

        /* Section info box */
        .section-info {
            background: var(--admin-bg);
            border-radius: var(--admin-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .section-info-icon {
            width: 40px;
            height: 40px;
            color: var(--admin-primary);
        }
        .section-info-content h3 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
        }
        .section-info-content p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--admin-text-light);
        }

        /* Checkbox styling */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--admin-text-light);
        }
        .empty-state-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            color: var(--admin-border);
        }
        .empty-state h3 {
            color: var(--admin-text);
            margin-bottom: 0.5rem;
        }

        /* Action bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Migration notice */
        .migration-notice {
            background: rgba(66, 153, 225, 0.1);
            border: 1px solid rgba(66, 153, 225, 0.3);
            border-radius: var(--admin-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .migration-notice svg {
            width: 24px;
            height: 24px;
            color: #2B6CB0;
            flex-shrink: 0;
        }
        .migration-notice p {
            margin: 0;
            flex: 1;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .content-block-item {
                flex-direction: column;
            }
            .block-image, .block-image-placeholder {
                width: 100%;
                height: 120px;
            }
            .block-actions {
                width: 100%;
                justify-content: flex-end;
            }
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
                <a href="content.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="9" y1="21" x2="9" y2="9"/>
                    </svg>
                    Gestion du contenu
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
                    Room Service - Catégories
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
                <a href="room-service-messages.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages Clients
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge" style="background: #E53E3E; color: white; margin-left: auto;"><?= $unreadMessages ?></span>
                    <?php endif; ?>
                </a>
                <a href="theme.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a10 10 0 0 0 0 20"/>
                        <path d="M12 2c-2.5 2.5-4 6-4 10s1.5 7.5 4 10"/>
                    </svg>
                    Thème du site
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
                <h1>Gestion du contenu</h1>
                <a href="../index.php" target="_blank" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    Voir le site
                </a>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= h($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($editBlock): ?>
                    <!-- Edit Block Form -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Modifier le contenu</h2>
                            <a href="?section=<?= h($currentSection) ?>" class="btn btn-outline">Retour</a>
                        </div>
                        <div class="card-body">
                            <?php if ($currentSectionData): ?>
                            <div class="section-info">
                                <svg class="section-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <div class="section-info-content">
                                    <h3><?= h($currentSectionData['name']) ?></h3>
                                    <p><?= h($currentSectionData['description']) ?></p>
                                </div>
                                <span class="image-mode-badge <?= getImageModeBadgeClass($currentSectionData['image_mode']) ?>">
                                    <?= getImageModeLabel($currentSectionData['image_mode']) ?>
                                </span>
                            </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" class="content-form">
                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="block_id" value="<?= $editBlock['id'] ?>">

                                <?php if ($currentSectionData['has_title']): ?>
                                <div class="form-group">
                                    <label for="title">Titre</label>
                                    <input type="text" id="title" name="title" value="<?= h($editBlock['title']) ?>">
                                </div>
                                <?php endif; ?>

                                <?php if ($currentSectionData['has_description']): ?>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description"><?= h($editBlock['description']) ?></textarea>
                                </div>
                                <?php endif; ?>

                                <?php if ($currentSectionData['image_mode'] !== IMAGE_FORBIDDEN): ?>
                                <div class="form-group">
                                    <label>Image <?= $currentSectionData['image_mode'] === IMAGE_REQUIRED ? '(obligatoire)' : '(optionnelle)' ?></label>

                                    <?php if (!empty($editBlock['image_filename'])): ?>
                                    <div class="current-image-preview">
                                        <img src="../<?= h($editBlock['image_filename']) ?>" alt="<?= h($editBlock['image_alt']) ?>">
                                        <div class="current-image-info">
                                            <p>Image actuelle</p>
                                            <?php if ($currentSectionData['image_mode'] !== IMAGE_REQUIRED): ?>
                                            <label class="checkbox-group">
                                                <input type="checkbox" name="remove_image" value="1">
                                                <span>Supprimer cette image</span>
                                            </label>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="image-upload-area" id="uploadArea">
                                        <input type="file" id="imageInput" name="image" accept="image/jpeg,image/png,image/webp">
                                        <svg class="image-upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21 15 16 10 5 21"/>
                                        </svg>
                                        <div class="image-upload-text"><?= empty($editBlock['image_filename']) ? 'Cliquez ou déposez une image' : 'Remplacer l\'image' ?></div>
                                        <div class="image-upload-hint">JPG, PNG ou WebP - Max 5 Mo</div>
                                    </div>

                                    <div class="form-group" style="margin-top: 1rem;">
                                        <label for="image_alt">Texte alternatif (accessibilité)</label>
                                        <input type="text" id="image_alt" name="image_alt" value="<?= h($editBlock['image_alt']) ?>">
                                        <small>Décrit l'image pour les lecteurs d'écran</small>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($currentSectionData['has_link']): ?>
                                <div class="form-group">
                                    <label for="link_url">URL du lien</label>
                                    <input type="url" id="link_url" name="link_url" value="<?= h($editBlock['link_url']) ?>" placeholder="https://...">
                                </div>
                                <div class="form-group">
                                    <label for="link_text">Texte du lien</label>
                                    <input type="text" id="link_text" name="link_text" value="<?= h($editBlock['link_text']) ?>" placeholder="En savoir plus">
                                </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label class="checkbox-group">
                                        <input type="checkbox" name="is_active" value="1" <?= $editBlock['is_active'] ? 'checked' : '' ?>>
                                        <span>Actif (visible sur le site)</span>
                                    </label>
                                </div>

                                <div class="form-actions" style="display: flex; gap: 1rem;">
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    <a href="?section=<?= h($currentSection) ?>" class="btn btn-outline">Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php elseif ($viewMode === 'add' && $currentSection && $currentSectionData): ?>
                    <!-- Add Block Form -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Ajouter du contenu</h2>
                            <a href="?section=<?= h($currentSection) ?>" class="btn btn-outline">Retour</a>
                        </div>
                        <div class="card-body">
                            <div class="section-info">
                                <svg class="section-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <div class="section-info-content">
                                    <h3><?= h($currentSectionData['name']) ?></h3>
                                    <p><?= h($currentSectionData['description']) ?></p>
                                </div>
                                <span class="image-mode-badge <?= getImageModeBadgeClass($currentSectionData['image_mode']) ?>">
                                    <?= getImageModeLabel($currentSectionData['image_mode']) ?>
                                </span>
                            </div>

                            <form method="POST" enctype="multipart/form-data" class="content-form">
                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                <input type="hidden" name="action" value="create">
                                <input type="hidden" name="section_code" value="<?= h($currentSection) ?>">

                                <?php if ($currentSectionData['has_title']): ?>
                                <div class="form-group">
                                    <label for="title">Titre</label>
                                    <input type="text" id="title" name="title" required>
                                </div>
                                <?php endif; ?>

                                <?php if ($currentSectionData['has_description']): ?>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description"></textarea>
                                </div>
                                <?php endif; ?>

                                <?php if ($currentSectionData['image_mode'] !== IMAGE_FORBIDDEN): ?>
                                <div class="form-group">
                                    <label>Image <?= $currentSectionData['image_mode'] === IMAGE_REQUIRED ? '(obligatoire)' : '(optionnelle)' ?></label>

                                    <div class="image-upload-area" id="uploadArea">
                                        <input type="file" id="imageInput" name="image" accept="image/jpeg,image/png,image/webp" <?= $currentSectionData['image_mode'] === IMAGE_REQUIRED ? 'required' : '' ?>>
                                        <svg class="image-upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21 15 16 10 5 21"/>
                                        </svg>
                                        <div class="image-upload-text">Cliquez ou déposez une image</div>
                                        <div class="image-upload-hint">JPG, PNG ou WebP - Max 5 Mo</div>
                                    </div>

                                    <div class="form-group" style="margin-top: 1rem;">
                                        <label for="image_alt">Texte alternatif (accessibilité)</label>
                                        <input type="text" id="image_alt" name="image_alt">
                                        <small>Décrit l'image pour les lecteurs d'écran</small>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($currentSectionData['has_link']): ?>
                                <div class="form-group">
                                    <label for="link_url">URL du lien</label>
                                    <input type="url" id="link_url" name="link_url" placeholder="https://...">
                                </div>
                                <div class="form-group">
                                    <label for="link_text">Texte du lien</label>
                                    <input type="text" id="link_text" name="link_text" placeholder="En savoir plus">
                                </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label class="checkbox-group">
                                        <input type="checkbox" name="is_active" value="1" checked>
                                        <span>Actif (visible sur le site)</span>
                                    </label>
                                </div>

                                <div class="form-actions" style="display: flex; gap: 1rem;">
                                    <button type="submit" class="btn btn-primary">Créer</button>
                                    <a href="?section=<?= h($currentSection) ?>" class="btn btn-outline">Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Section Selection and Content List -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Sections du site</h2>
                        </div>
                        <div class="card-body">
                            <?php foreach ($sectionsByPage as $page => $sections): ?>
                            <div class="page-group">
                                <div class="page-group-title"><?= h($pageNames[$page] ?? $page) ?></div>
                                <div class="sections-nav">
                                    <?php foreach ($sections as $section): ?>
                                    <a href="?section=<?= h($section['code']) ?>"
                                       class="section-btn <?= $currentSection === $section['code'] ? 'active' : '' ?>">
                                        <?= h($section['name']) ?>
                                        <span class="image-mode-badge <?= getImageModeBadgeClass($section['image_mode']) ?>" style="margin-left: 0.5rem;">
                                            <?php if ($section['image_mode'] === IMAGE_REQUIRED): ?>
                                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <?php elseif ($section['image_mode'] === IMAGE_FORBIDDEN): ?>
                                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/></svg>
                                            <?php else: ?>
                                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                            <?php endif; ?>
                                        </span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($currentSection && $currentSectionData): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2><?= h($currentSectionData['name']) ?></h2>
                            <?php
                            $canAdd = true;
                            if ($currentSectionData['max_blocks']) {
                                $canAdd = count($blocks) < $currentSectionData['max_blocks'];
                            }
                            ?>
                            <?php if ($canAdd): ?>
                            <a href="?section=<?= h($currentSection) ?>&view=add" class="btn btn-primary">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Ajouter
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="section-info">
                                <svg class="section-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <div class="section-info-content">
                                    <p><?= h($currentSectionData['description']) ?></p>
                                    <?php if ($currentSectionData['max_blocks']): ?>
                                    <p><strong>Limite:</strong> <?= count($blocks) ?>/<?= $currentSectionData['max_blocks'] ?> bloc(s)</p>
                                    <?php endif; ?>
                                </div>
                                <span class="image-mode-badge <?= getImageModeBadgeClass($currentSectionData['image_mode']) ?>">
                                    <?= getImageModeLabel($currentSectionData['image_mode']) ?>
                                </span>
                            </div>

                            <?php if (empty($blocks)): ?>
                            <div class="empty-state">
                                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="3" y1="9" x2="21" y2="9"/>
                                    <line x1="9" y1="21" x2="9" y2="9"/>
                                </svg>
                                <h3>Aucun contenu</h3>
                                <p>Cette section ne contient pas encore de contenu.</p>
                                <?php if ($canAdd): ?>
                                <a href="?section=<?= h($currentSection) ?>&view=add" class="btn btn-primary" style="margin-top: 1rem;">
                                    Ajouter du contenu
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="content-blocks" id="contentBlocks" data-section="<?= h($currentSection) ?>">
                                <?php foreach ($blocks as $block): ?>
                                <div class="content-block-item <?= !$block['is_active'] ? 'inactive' : '' ?>" data-block-id="<?= $block['id'] ?>">
                                    <div class="block-drag-handle" title="Glisser pour réordonner">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="8" y1="6" x2="16" y2="6"/>
                                            <line x1="8" y1="12" x2="16" y2="12"/>
                                            <line x1="8" y1="18" x2="16" y2="18"/>
                                        </svg>
                                    </div>

                                    <?php if ($currentSectionData['image_mode'] !== IMAGE_FORBIDDEN): ?>
                                        <?php if (!empty($block['image_filename'])): ?>
                                        <img src="../<?= h($block['image_filename']) ?>" alt="<?= h($block['image_alt']) ?>" class="block-image">
                                        <?php else: ?>
                                        <div class="block-image-placeholder">
                                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                <polyline points="21 15 16 10 5 21"/>
                                            </svg>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <div class="block-content">
                                        <div class="block-title"><?= h($block['title']) ?: '<em>Sans titre</em>' ?></div>
                                        <?php if ($block['description']): ?>
                                        <div class="block-description"><?= h($block['description']) ?></div>
                                        <?php endif; ?>
                                        <div class="block-meta">
                                            <span>Position: <?= $block['position'] ?></span>
                                            <?php if (!$block['is_active']): ?>
                                            <span style="color: #C53030;">Inactif</span>
                                            <?php endif; ?>
                                            <?php if ($block['link_url']): ?>
                                            <span>Lien: <?= h($block['link_text'] ?: 'Oui') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="block-actions">
                                        <a href="?edit=<?= $block['id'] ?>" class="btn btn-sm">Modifier</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce contenu ?');">
                                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="block_id" value="<?= $block['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif (!$currentSection): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                                </svg>
                                <h3>Sélectionnez une section</h3>
                                <p>Choisissez une section ci-dessus pour gérer son contenu.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

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

    // Image upload area interaction
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('imageInput');

    if (uploadArea && imageInput) {
        uploadArea.addEventListener('click', () => imageInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                imageInput.files = e.dataTransfer.files;
                updateUploadAreaText(e.dataTransfer.files[0].name);
            }
        });

        imageInput.addEventListener('change', () => {
            if (imageInput.files.length) {
                updateUploadAreaText(imageInput.files[0].name);
            }
        });

        function updateUploadAreaText(filename) {
            const textEl = uploadArea.querySelector('.image-upload-text');
            if (textEl) {
                textEl.textContent = filename;
            }
        }
    }

    // Drag and drop reordering
    const contentBlocks = document.getElementById('contentBlocks');
    if (contentBlocks) {
        let draggedItem = null;

        contentBlocks.querySelectorAll('.content-block-item').forEach(item => {
            const handle = item.querySelector('.block-drag-handle');

            handle.addEventListener('mousedown', () => {
                item.draggable = true;
            });

            item.addEventListener('dragstart', (e) => {
                draggedItem = item;
                item.style.opacity = '0.5';
            });

            item.addEventListener('dragend', () => {
                item.draggable = false;
                item.style.opacity = '';
                draggedItem = null;
                saveOrder();
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (draggedItem && draggedItem !== item) {
                    const rect = item.getBoundingClientRect();
                    const midpoint = rect.top + rect.height / 2;
                    if (e.clientY < midpoint) {
                        contentBlocks.insertBefore(draggedItem, item);
                    } else {
                        contentBlocks.insertBefore(draggedItem, item.nextSibling);
                    }
                }
            });
        });

        function saveOrder() {
            const blockIds = Array.from(contentBlocks.querySelectorAll('.content-block-item'))
                .map(item => item.dataset.blockId);

            const sectionCode = contentBlocks.dataset.section;

            fetch('content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reorder&csrf_token=<?= h($csrfToken) ?>&section_code=${encodeURIComponent(sectionCode)}&block_ids=${encodeURIComponent(JSON.stringify(blockIds))}`
            });
        }
    }
    </script>
</body>
</html>
