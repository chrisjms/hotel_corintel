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

            case 'save_overlay':
                $sectionCode = $_POST['section_code'] ?? '';
                $section = getContentSection($sectionCode);

                if (!$section) {
                    $message = 'Section invalide.';
                    $messageType = 'error';
                    break;
                }

                // Save French (default) overlay texts
                $subtitle = $_POST['overlay_subtitle'] ?? '';
                $title = $_POST['overlay_title'] ?? '';
                $description = $_POST['overlay_description'] ?? '';

                $success = saveSectionOverlay($sectionCode, $subtitle, $title, $description);

                // Save translations
                $translations = [];
                foreach (['en', 'es', 'it'] as $lang) {
                    $translations[$lang] = [
                        'subtitle' => $_POST["overlay_subtitle_$lang"] ?? '',
                        'title' => $_POST["overlay_title_$lang"] ?? '',
                        'description' => $_POST["overlay_description_$lang"] ?? ''
                    ];
                }
                $success = saveSectionOverlayTranslations($sectionCode, $translations) && $success;

                if ($success) {
                    $message = 'Textes de la section enregistrés avec succès.';
                    $messageType = 'success';
                    $currentSection = $sectionCode;
                } else {
                    $message = 'Erreur lors de l\'enregistrement.';
                    $messageType = 'error';
                }
                break;

            case 'create_feature':
                $sectionCode = $_POST['section_code'] ?? '';
                $iconCode = $_POST['icon_code'] ?? '';
                $label = trim($_POST['feature_label'] ?? '');

                if (empty($iconCode) || empty($label)) {
                    $message = 'Veuillez sélectionner une icône et entrer un libellé.';
                    $messageType = 'error';
                    break;
                }

                $featureId = createSectionFeature($sectionCode, $iconCode, $label);

                if ($featureId) {
                    // Save translations
                    $translations = [];
                    foreach (['en', 'es', 'it'] as $lang) {
                        $transLabel = trim($_POST["feature_label_$lang"] ?? '');
                        if (!empty($transLabel)) {
                            $translations[$lang] = $transLabel;
                        }
                    }
                    if (!empty($translations)) {
                        saveSectionFeatureTranslations($featureId, $translations);
                    }

                    $message = 'Indicateur ajouté avec succès.';
                    $messageType = 'success';
                    $currentSection = $sectionCode;
                } else {
                    $message = 'Erreur lors de la création.';
                    $messageType = 'error';
                }
                break;

            case 'update_feature':
                $featureId = (int)($_POST['feature_id'] ?? 0);
                $iconCode = $_POST['icon_code'] ?? '';
                $label = trim($_POST['feature_label'] ?? '');
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if (empty($iconCode) || empty($label)) {
                    $message = 'Veuillez sélectionner une icône et entrer un libellé.';
                    $messageType = 'error';
                    break;
                }

                $feature = getSectionFeature($featureId);
                if (!$feature) {
                    $message = 'Indicateur introuvable.';
                    $messageType = 'error';
                    break;
                }

                $success = updateSectionFeature($featureId, $iconCode, $label, $isActive);

                if ($success) {
                    // Save translations
                    $translations = [];
                    foreach (['en', 'es', 'it'] as $lang) {
                        $transLabel = trim($_POST["feature_label_$lang"] ?? '');
                        $translations[$lang] = $transLabel;
                    }
                    saveSectionFeatureTranslations($featureId, $translations);

                    $message = 'Indicateur mis à jour avec succès.';
                    $messageType = 'success';
                    $currentSection = $feature['section_code'];
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'delete_feature':
                $featureId = (int)($_POST['feature_id'] ?? 0);
                $feature = getSectionFeature($featureId);

                if ($feature && deleteSectionFeature($featureId)) {
                    $message = 'Indicateur supprimé.';
                    $messageType = 'success';
                    $currentSection = $feature['section_code'];
                } else {
                    $message = 'Erreur lors de la suppression.';
                    $messageType = 'error';
                }
                break;

            case 'reorder_features':
                $sectionCode = $_POST['section_code'] ?? '';
                $featureIds = json_decode($_POST['feature_ids'] ?? '[]', true);

                if ($sectionCode && is_array($featureIds)) {
                    reorderSectionFeatures($sectionCode, $featureIds);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
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

        /* Section overlay text panel */
        .overlay-panel {
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .overlay-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .overlay-panel-header h4 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .overlay-panel-header h4 svg {
            width: 18px;
            height: 18px;
            color: var(--admin-primary);
        }
        .overlay-form-row {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .overlay-form-row.full-width {
            grid-template-columns: 1fr;
        }
        .overlay-field-group label {
            display: block;
            margin-bottom: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--admin-text);
        }
        .overlay-field-group input,
        .overlay-field-group textarea {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .overlay-field-group input:focus,
        .overlay-field-group textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .overlay-field-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        .overlay-field-hint {
            font-size: 0.75rem;
            color: var(--admin-text-light);
            margin-top: 0.25rem;
        }
        .overlay-translations {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--admin-border);
        }
        .overlay-translations-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            user-select: none;
        }
        .overlay-translations-header h5 {
            margin: 0;
            font-size: 0.875rem;
            color: var(--admin-text-light);
        }
        .overlay-translations-header svg {
            width: 16px;
            height: 16px;
            transition: transform 0.2s;
        }
        .overlay-translations-header.collapsed svg {
            transform: rotate(-90deg);
        }
        .overlay-translations-content {
            display: block;
        }
        .overlay-translations-content.hidden {
            display: none;
        }
        .translation-lang-group {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .translation-lang-group:last-child {
            margin-bottom: 0;
        }
        .translation-lang-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            color: var(--admin-text);
        }
        .translation-lang-label .flag {
            font-size: 1rem;
        }
        .translation-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .translation-fields .full-width {
            grid-column: 1 / -1;
        }
        .translation-field input,
        .translation-field textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            font-size: 0.8125rem;
        }
        .translation-field label {
            display: block;
            font-size: 0.75rem;
            color: var(--admin-text-light);
            margin-bottom: 0.25rem;
        }
        .translation-field textarea {
            min-height: 60px;
            resize: vertical;
        }
        .overlay-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--admin-border);
        }

        /* Features panel */
        .features-panel {
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .features-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .features-panel-header h4 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .features-panel-header h4 svg {
            width: 18px;
            height: 18px;
            color: var(--admin-primary);
        }
        .features-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            transition: box-shadow 0.2s;
        }
        .feature-item:hover {
            box-shadow: var(--admin-shadow);
        }
        .feature-item.inactive {
            opacity: 0.5;
        }
        .feature-drag-handle {
            cursor: grab;
            color: var(--admin-text-light);
            padding: 0.25rem;
            display: flex;
            align-items: center;
        }
        .feature-drag-handle:active {
            cursor: grabbing;
        }
        .feature-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(139, 90, 43, 0.1);
            border-radius: 6px;
            color: var(--admin-primary);
            flex-shrink: 0;
        }
        .feature-icon svg {
            width: 18px;
            height: 18px;
        }
        .feature-label {
            flex: 1;
            font-weight: 500;
            color: var(--admin-text);
        }
        .feature-actions {
            display: flex;
            gap: 0.25rem;
        }
        .feature-actions button {
            padding: 0.375rem;
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--admin-text-light);
            border-radius: 4px;
            transition: all 0.2s;
        }
        .feature-actions button:hover {
            background: var(--admin-bg);
            color: var(--admin-primary);
        }
        .feature-actions button.delete-btn:hover {
            color: #C53030;
        }
        .feature-actions button svg {
            width: 16px;
            height: 16px;
        }
        .features-empty {
            text-align: center;
            padding: 1.5rem;
            color: var(--admin-text-light);
            font-size: 0.9rem;
        }
        .add-feature-btn {
            width: 100%;
            padding: 0.75rem;
            border: 2px dashed var(--admin-border);
            background: transparent;
            border-radius: 6px;
            color: var(--admin-text-light);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .add-feature-btn:hover {
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }
        .add-feature-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Feature modal */
        .feature-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
        }
        .feature-modal.active {
            display: flex;
        }
        .feature-modal-content {
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .feature-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .feature-modal-header h3 {
            margin: 0;
            font-size: 1.125rem;
        }
        .feature-modal-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            color: var(--admin-text-light);
            transition: color 0.2s;
        }
        .feature-modal-close:hover {
            color: var(--admin-text);
        }
        .feature-modal-close svg {
            width: 20px;
            height: 20px;
        }
        .feature-modal-body {
            padding: 1.5rem;
        }
        .feature-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--admin-border);
        }

        /* Icon selector */
        .icon-selector {
            margin-bottom: 1.5rem;
        }
        .icon-selector-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--admin-text);
        }
        .icon-category {
            margin-bottom: 1rem;
        }
        .icon-category-name {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--admin-text-light);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 0.5rem;
        }
        .icon-option {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border: 2px solid var(--admin-border);
            border-radius: 6px;
            background: var(--admin-bg);
            cursor: pointer;
            transition: all 0.2s;
        }
        .icon-option:hover {
            border-color: var(--admin-primary);
        }
        .icon-option.selected {
            border-color: var(--admin-primary);
            background: rgba(139, 90, 43, 0.1);
        }
        .icon-option svg {
            width: 24px;
            height: 24px;
            color: var(--admin-text);
        }
        .icon-option.selected svg {
            color: var(--admin-primary);
        }
        .icon-option-name {
            font-size: 0.625rem;
            color: var(--admin-text-light);
            margin-top: 0.25rem;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 100%;
        }

        /* Feature form fields */
        .feature-form-group {
            margin-bottom: 1rem;
        }
        .feature-form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.375rem;
            color: var(--admin-text);
            font-size: 0.9rem;
        }
        .feature-form-group input {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .feature-form-group input:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .feature-translations {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--admin-border);
        }
        .feature-translations-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            user-select: none;
        }
        .feature-translations-header h5 {
            margin: 0;
            font-size: 0.875rem;
            color: var(--admin-text-light);
        }
        .feature-translations-header svg {
            width: 14px;
            height: 14px;
            transition: transform 0.2s;
        }
        .feature-translations-header.collapsed svg {
            transform: rotate(-90deg);
        }
        .feature-trans-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }
        .feature-trans-field label {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8rem;
            color: var(--admin-text-light);
            margin-bottom: 0.25rem;
        }
        .feature-trans-field label .flag {
            font-size: 0.9rem;
        }
        .feature-trans-field input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .feature-trans-field input:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .feature-active-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .feature-active-toggle input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .feature-active-toggle span {
            font-size: 0.9rem;
            color: var(--admin-text);
        }

        @media (max-width: 600px) {
            .feature-trans-grid {
                grid-template-columns: 1fr;
            }
            .icon-grid {
                grid-template-columns: repeat(5, 1fr);
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
                    <?php $isImageOnlyEdit = $currentSectionData && !$currentSectionData['has_title'] && !$currentSectionData['has_description']; ?>
                    <div class="card">
                        <div class="card-header">
                            <h2><?= $isImageOnlyEdit ? 'Modifier l\'image' : 'Modifier le contenu' ?></h2>
                            <a href="?section=<?= h($currentSection) ?>" class="btn btn-outline">Retour</a>
                        </div>
                        <div class="card-body">
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
                    <?php $isImageOnlySection = !$currentSectionData['has_title'] && !$currentSectionData['has_description']; ?>
                    <div class="card">
                        <div class="card-header">
                            <h2><?= $isImageOnlySection ? 'Ajouter une image' : 'Ajouter du contenu' ?></h2>
                            <a href="?section=<?= h($currentSection) ?>" class="btn btn-outline">Retour</a>
                        </div>
                        <div class="card-body">
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
                            <?php foreach ($pageNames as $page => $pageName): ?>
                            <?php if (!isset($sectionsByPage[$page])) continue; ?>
                            <div class="page-group">
                                <div class="page-group-title"><?= h($pageName) ?></div>
                                <div class="sections-nav">
                                    <?php foreach ($sectionsByPage[$page] as $section): ?>
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
                                <?= (!$currentSectionData['has_title'] && !$currentSectionData['has_description']) ? 'Ajouter une image' : 'Ajouter' ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php
                            // Show overlay text panel for sections that support it
                            $sectionsWithOverlay = ['home_hero', 'home_intro'];
                            $showOverlayPanel = in_array($currentSection, $sectionsWithOverlay);
                            if ($showOverlayPanel):
                                $overlayData = getSectionOverlayWithTranslations($currentSection);

                                // Section-specific configuration for placeholders and hints
                                $overlayConfig = [
                                    'home_hero' => [
                                        'subtitle_placeholder' => 'Ex: Bienvenue à l\'Hôtel Corintel',
                                        'subtitle_hint' => 'Texte affiché au-dessus du titre principal',
                                        'title_placeholder' => 'Ex: Un havre de paix aux portes de Bordeaux',
                                        'title_hint' => 'Titre accrocheur en grand format',
                                        'description_placeholder' => 'Ex: Découvrez notre hôtel de charme 3 étoiles...',
                                        'description_hint' => 'Paragraphe descriptif sous le titre'
                                    ],
                                    'home_intro' => [
                                        'subtitle_placeholder' => 'Ex: Notre philosophie',
                                        'subtitle_hint' => 'Petit texte au-dessus du titre',
                                        'title_placeholder' => 'Ex: Une atmosphère chaleureuse et conviviale',
                                        'title_hint' => 'Titre de la section',
                                        'description_placeholder' => 'Ex: L\'Hôtel Corintel vous accueille dans un cadre paisible...',
                                        'description_hint' => 'Texte descriptif (utilisez deux lignes vides pour séparer les paragraphes)'
                                    ]
                                ];
                                $config = $overlayConfig[$currentSection] ?? $overlayConfig['home_hero'];
                            ?>
                            <div class="overlay-panel">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                    <input type="hidden" name="action" value="save_overlay">
                                    <input type="hidden" name="section_code" value="<?= h($currentSection) ?>">

                                    <div class="overlay-panel-header">
                                        <h4>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 20h9"/>
                                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                            </svg>
                                            Textes de la section
                                        </h4>
                                    </div>

                                    <div class="overlay-form-row">
                                        <div class="overlay-field-group">
                                            <label for="overlay_subtitle">Sous-titre</label>
                                            <input type="text" id="overlay_subtitle" name="overlay_subtitle" value="<?= h($overlayData['subtitle']) ?>" placeholder="<?= h($config['subtitle_placeholder']) ?>">
                                            <p class="overlay-field-hint"><?= h($config['subtitle_hint']) ?></p>
                                        </div>
                                        <div class="overlay-field-group">
                                            <label for="overlay_title">Titre principal</label>
                                            <input type="text" id="overlay_title" name="overlay_title" value="<?= h($overlayData['title']) ?>" placeholder="<?= h($config['title_placeholder']) ?>">
                                            <p class="overlay-field-hint"><?= h($config['title_hint']) ?></p>
                                        </div>
                                    </div>

                                    <div class="overlay-form-row full-width">
                                        <div class="overlay-field-group">
                                            <label for="overlay_description">Description</label>
                                            <textarea id="overlay_description" name="overlay_description" placeholder="<?= h($config['description_placeholder']) ?>"><?= h($overlayData['description']) ?></textarea>
                                            <p class="overlay-field-hint"><?= h($config['description_hint']) ?></p>
                                        </div>
                                    </div>

                                    <div class="overlay-translations">
                                        <div class="overlay-translations-header" id="translationsToggle">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="6 9 12 15 18 9"/>
                                            </svg>
                                            <h5>Traductions (optionnel)</h5>
                                        </div>

                                        <div class="overlay-translations-content" id="translationsContent">
                                            <?php
                                            $languages = [
                                                'en' => ['flag' => '🇬🇧', 'name' => 'English'],
                                                'es' => ['flag' => '🇪🇸', 'name' => 'Español'],
                                                'it' => ['flag' => '🇮🇹', 'name' => 'Italiano']
                                            ];
                                            foreach ($languages as $langCode => $langInfo):
                                                $trans = $overlayData['translations'][$langCode] ?? ['subtitle' => '', 'title' => '', 'description' => ''];
                                            ?>
                                            <div class="translation-lang-group">
                                                <div class="translation-lang-label">
                                                    <span class="flag"><?= $langInfo['flag'] ?></span>
                                                    <?= $langInfo['name'] ?>
                                                </div>
                                                <div class="translation-fields">
                                                    <div class="translation-field">
                                                        <label>Sous-titre</label>
                                                        <input type="text" name="overlay_subtitle_<?= $langCode ?>" value="<?= h($trans['subtitle']) ?>" placeholder="Subtitle">
                                                    </div>
                                                    <div class="translation-field">
                                                        <label>Titre</label>
                                                        <input type="text" name="overlay_title_<?= $langCode ?>" value="<?= h($trans['title']) ?>" placeholder="Title">
                                                    </div>
                                                    <div class="translation-field full-width">
                                                        <label>Description</label>
                                                        <textarea name="overlay_description_<?= $langCode ?>" placeholder="Description"><?= h($trans['description']) ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="overlay-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                                <polyline points="17 21 17 13 7 13 7 21"/>
                                                <polyline points="7 3 7 8 15 8"/>
                                            </svg>
                                            Enregistrer les textes
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>

                            <?php
                            // Show features panel for sections that support it
                            $showFeaturesPanel = $currentSectionData && sectionHasFeatures($currentSection);
                            if ($showFeaturesPanel):
                                $sectionFeatures = getSectionFeaturesWithTranslations($currentSection, false);
                                $availableIcons = getAvailableIcons();
                                $iconCategories = getIconCategories();
                            ?>
                            <div class="features-panel">
                                <div class="features-panel-header">
                                    <h4>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                        </svg>
                                        Indicateurs
                                    </h4>
                                </div>

                                <?php if (!empty($sectionFeatures)): ?>
                                <div class="features-list" id="featuresList" data-section="<?= h($currentSection) ?>">
                                    <?php foreach ($sectionFeatures as $feature): ?>
                                    <div class="feature-item <?= !$feature['is_active'] ? 'inactive' : '' ?>" data-feature-id="<?= $feature['id'] ?>">
                                        <div class="feature-drag-handle" title="Glisser pour réordonner">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="8" y1="6" x2="16" y2="6"/>
                                                <line x1="8" y1="12" x2="16" y2="12"/>
                                                <line x1="8" y1="18" x2="16" y2="18"/>
                                            </svg>
                                        </div>
                                        <div class="feature-icon">
                                            <?= getIconSvg($feature['icon_code']) ?>
                                        </div>
                                        <span class="feature-label"><?= h($feature['label']) ?></span>
                                        <div class="feature-actions">
                                            <button type="button" class="edit-feature-btn" data-feature='<?= h(json_encode([
                                                'id' => $feature['id'],
                                                'icon_code' => $feature['icon_code'],
                                                'label' => $feature['label'],
                                                'is_active' => $feature['is_active'],
                                                'translations' => $feature['translations'] ?? []
                                            ])) ?>' title="Modifier">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cet indicateur ?');">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_feature">
                                                <input type="hidden" name="feature_id" value="<?= $feature['id'] ?>">
                                                <button type="submit" class="delete-btn" title="Supprimer">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="3 6 5 6 21 6"/>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="features-empty">
                                    Aucun indicateur configuré
                                </div>
                                <?php endif; ?>

                                <button type="button" class="add-feature-btn" id="addFeatureBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    Ajouter un indicateur
                                </button>
                            </div>

                            <!-- Feature Modal (Add/Edit) -->
                            <div class="feature-modal" id="featureModal">
                                <div class="feature-modal-content">
                                    <div class="feature-modal-header">
                                        <h3 id="featureModalTitle">Ajouter un indicateur</h3>
                                        <button type="button" class="feature-modal-close" id="featureModalClose">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <form method="POST" id="featureForm">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                        <input type="hidden" name="action" id="featureAction" value="create_feature">
                                        <input type="hidden" name="section_code" value="<?= h($currentSection) ?>">
                                        <input type="hidden" name="feature_id" id="featureId" value="">
                                        <input type="hidden" name="icon_code" id="selectedIconCode" value="">

                                        <div class="feature-modal-body">
                                            <div class="icon-selector">
                                                <label class="icon-selector-label">Icône</label>
                                                <?php foreach ($iconCategories as $catCode => $catName): ?>
                                                <div class="icon-category">
                                                    <div class="icon-category-name"><?= h($catName) ?></div>
                                                    <div class="icon-grid">
                                                        <?php foreach ($availableIcons as $iconCode => $icon): ?>
                                                        <?php if ($icon['category'] === $catCode): ?>
                                                        <div class="icon-option" data-icon="<?= h($iconCode) ?>" title="<?= h($icon['name']) ?>">
                                                            <?= $icon['svg'] ?>
                                                            <span class="icon-option-name"><?= h($icon['name']) ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <div class="feature-form-group">
                                                <label for="featureLabel">Libellé (Français)</label>
                                                <input type="text" id="featureLabel" name="feature_label" required placeholder="Ex: Jardin paisible">
                                            </div>

                                            <div class="feature-translations">
                                                <div class="feature-translations-header" id="featureTransToggle">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="6 9 12 15 18 9"/>
                                                    </svg>
                                                    <h5>Traductions (optionnel)</h5>
                                                </div>
                                                <div class="feature-trans-grid" id="featureTransContent">
                                                    <div class="feature-trans-field">
                                                        <label><span class="flag">🇬🇧</span> English</label>
                                                        <input type="text" name="feature_label_en" id="featureLabelEn" placeholder="Peaceful garden">
                                                    </div>
                                                    <div class="feature-trans-field">
                                                        <label><span class="flag">🇪🇸</span> Español</label>
                                                        <input type="text" name="feature_label_es" id="featureLabelEs" placeholder="Jardín tranquilo">
                                                    </div>
                                                    <div class="feature-trans-field">
                                                        <label><span class="flag">🇮🇹</span> Italiano</label>
                                                        <input type="text" name="feature_label_it" id="featureLabelIt" placeholder="Giardino tranquillo">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="feature-active-toggle" id="featureActiveToggle" style="display: none;">
                                                <input type="checkbox" name="is_active" id="featureIsActive" value="1" checked>
                                                <span>Actif (visible sur le site)</span>
                                            </div>
                                        </div>

                                        <div class="feature-modal-footer">
                                            <button type="button" class="btn btn-outline" id="featureModalCancel">Annuler</button>
                                            <button type="submit" class="btn btn-primary" id="featureSubmitBtn">Ajouter</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (empty($blocks)): ?>
                            <div class="empty-state">
                                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="3" y1="9" x2="21" y2="9"/>
                                    <line x1="9" y1="21" x2="9" y2="9"/>
                                </svg>
                                <?php $isImageOnly = !$currentSectionData['has_title'] && !$currentSectionData['has_description']; ?>
                                <h3><?= $isImageOnly ? 'Aucune image' : 'Aucun contenu' ?></h3>
                                <p>Cette section ne contient pas encore <?= $isImageOnly ? 'd\'image' : 'de contenu' ?>.</p>
                                <?php if ($canAdd): ?>
                                <a href="?section=<?= h($currentSection) ?>&view=add" class="btn btn-primary" style="margin-top: 1rem;">
                                    <?= $isImageOnly ? 'Ajouter une image' : 'Ajouter du contenu' ?>
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
                                        <?php if ($currentSectionData['has_title']): ?>
                                        <div class="block-title"><?= h($block['title']) ?: '<em>Sans titre</em>' ?></div>
                                        <?php else: ?>
                                        <div class="block-title">Image <?= $block['position'] ?></div>
                                        <?php endif; ?>
                                        <?php if ($currentSectionData['has_description'] && $block['description']): ?>
                                        <div class="block-description"><?= h($block['description']) ?></div>
                                        <?php endif; ?>
                                        <div class="block-meta">
                                            <?php if ($currentSectionData['has_title']): ?>
                                            <span>Position: <?= $block['position'] ?></span>
                                            <?php endif; ?>
                                            <?php if (!$block['is_active']): ?>
                                            <span style="color: #C53030;">Inactif</span>
                                            <?php endif; ?>
                                            <?php if ($currentSectionData['has_link'] && $block['link_url']): ?>
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

    // Translations toggle
    const translationsToggle = document.getElementById('translationsToggle');
    const translationsContent = document.getElementById('translationsContent');

    if (translationsToggle && translationsContent) {
        translationsToggle.addEventListener('click', () => {
            translationsToggle.classList.toggle('collapsed');
            translationsContent.classList.toggle('hidden');
        });
    }

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

    // =====================================================
    // FEATURES PANEL FUNCTIONALITY
    // =====================================================

    const featureModal = document.getElementById('featureModal');
    const featureForm = document.getElementById('featureForm');
    const addFeatureBtn = document.getElementById('addFeatureBtn');
    const featureModalClose = document.getElementById('featureModalClose');
    const featureModalCancel = document.getElementById('featureModalCancel');
    const featureModalTitle = document.getElementById('featureModalTitle');
    const featureAction = document.getElementById('featureAction');
    const featureId = document.getElementById('featureId');
    const selectedIconCode = document.getElementById('selectedIconCode');
    const featureLabel = document.getElementById('featureLabel');
    const featureLabelEn = document.getElementById('featureLabelEn');
    const featureLabelEs = document.getElementById('featureLabelEs');
    const featureLabelIt = document.getElementById('featureLabelIt');
    const featureIsActive = document.getElementById('featureIsActive');
    const featureActiveToggle = document.getElementById('featureActiveToggle');
    const featureSubmitBtn = document.getElementById('featureSubmitBtn');
    const featureTransToggle = document.getElementById('featureTransToggle');
    const featureTransContent = document.getElementById('featureTransContent');

    if (featureModal) {
        // Open modal for adding
        addFeatureBtn?.addEventListener('click', () => {
            openFeatureModal('add');
        });

        // Close modal
        featureModalClose?.addEventListener('click', closeFeatureModal);
        featureModalCancel?.addEventListener('click', closeFeatureModal);
        featureModal.addEventListener('click', (e) => {
            if (e.target === featureModal) closeFeatureModal();
        });

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && featureModal.classList.contains('active')) {
                closeFeatureModal();
            }
        });

        // Icon selection
        document.querySelectorAll('.icon-option').forEach(option => {
            option.addEventListener('click', () => {
                document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
                option.classList.add('selected');
                selectedIconCode.value = option.dataset.icon;
            });
        });

        // Edit feature buttons
        document.querySelectorAll('.edit-feature-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const featureData = JSON.parse(btn.dataset.feature);
                openFeatureModal('edit', featureData);
            });
        });

        // Translations toggle
        featureTransToggle?.addEventListener('click', () => {
            featureTransToggle.classList.toggle('collapsed');
            featureTransContent.style.display = featureTransToggle.classList.contains('collapsed') ? 'none' : 'grid';
        });

        // Form validation
        featureForm?.addEventListener('submit', (e) => {
            if (!selectedIconCode.value) {
                e.preventDefault();
                alert('Veuillez sélectionner une icône.');
                return false;
            }
            if (!featureLabel.value.trim()) {
                e.preventDefault();
                alert('Veuillez entrer un libellé.');
                return false;
            }
        });

        function openFeatureModal(mode, data = null) {
            // Reset form
            featureForm.reset();
            document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
            selectedIconCode.value = '';

            if (mode === 'edit' && data) {
                featureModalTitle.textContent = 'Modifier l\'indicateur';
                featureAction.value = 'update_feature';
                featureId.value = data.id;
                featureSubmitBtn.textContent = 'Enregistrer';
                featureActiveToggle.style.display = 'flex';

                // Select icon
                const iconOption = document.querySelector(`.icon-option[data-icon="${data.icon_code}"]`);
                if (iconOption) {
                    iconOption.classList.add('selected');
                    selectedIconCode.value = data.icon_code;
                }

                // Fill label
                featureLabel.value = data.label;
                featureIsActive.checked = data.is_active == 1;

                // Fill translations
                if (data.translations) {
                    featureLabelEn.value = data.translations.en || '';
                    featureLabelEs.value = data.translations.es || '';
                    featureLabelIt.value = data.translations.it || '';
                }
            } else {
                featureModalTitle.textContent = 'Ajouter un indicateur';
                featureAction.value = 'create_feature';
                featureId.value = '';
                featureSubmitBtn.textContent = 'Ajouter';
                featureActiveToggle.style.display = 'none';
            }

            featureModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeFeatureModal() {
            featureModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Features drag and drop reordering
    const featuresList = document.getElementById('featuresList');
    if (featuresList) {
        let draggedFeature = null;

        featuresList.querySelectorAll('.feature-item').forEach(item => {
            const handle = item.querySelector('.feature-drag-handle');

            handle.addEventListener('mousedown', () => {
                item.draggable = true;
            });

            item.addEventListener('dragstart', () => {
                draggedFeature = item;
                item.style.opacity = '0.5';
            });

            item.addEventListener('dragend', () => {
                item.draggable = false;
                item.style.opacity = '';
                draggedFeature = null;
                saveFeaturesOrder();
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (draggedFeature && draggedFeature !== item) {
                    const rect = item.getBoundingClientRect();
                    const midpoint = rect.top + rect.height / 2;
                    if (e.clientY < midpoint) {
                        featuresList.insertBefore(draggedFeature, item);
                    } else {
                        featuresList.insertBefore(draggedFeature, item.nextSibling);
                    }
                }
            });
        });

        function saveFeaturesOrder() {
            const featureIds = Array.from(featuresList.querySelectorAll('.feature-item'))
                .map(item => item.dataset.featureId);

            const sectionCode = featuresList.dataset.section;

            fetch('content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reorder_features&csrf_token=<?= h($csrfToken) ?>&section_code=${encodeURIComponent(sectionCode)}&feature_ids=${encodeURIComponent(JSON.stringify(featureIds))}`
            });
        }
    }
    </script>
</body>
</html>
