<?php
/**
 * Content Management
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

// Ensure section templates are seeded
seedSectionTemplates();

// Fix existing gallery sections that are missing has_gallery flag
$pdo = getDatabase();
$pdo->exec("UPDATE content_sections SET has_gallery = 1 WHERE template_type = 'gallery_style' AND has_gallery = 0");

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$csrfToken = generateCsrfToken();
$hotelName = getHotelName();

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

            case 'update_section_settings':
                $sectionCode = $_POST['section_code'] ?? '';
                $section = getContentSection($sectionCode);

                if (!$section) {
                    $message = 'Section invalide.';
                    $messageType = 'error';
                    break;
                }

                $bgColor = $_POST['background_color'] ?? 'cream';
                $success = setSectionBackgroundColor($sectionCode, $bgColor);

                // Save image position if this section type supports it
                if (isset($_POST['image_position']) && sectionSupportsImagePosition($section['template_type'] ?? '')) {
                    $imagePosition = $_POST['image_position'];
                    $success = setSectionImagePosition($sectionCode, $imagePosition) && $success;
                }

                if ($success) {
                    $message = 'Apparence de la section mise à jour.';
                    $messageType = 'success';
                    $currentSection = $sectionCode;
                } else {
                    $message = 'Erreur lors de la mise à jour.';
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

            // Service CRUD operations
            case 'create_service':
                $sectionCode = $_POST['section_code'] ?? '';
                $iconCode = $_POST['icon_code'] ?? '';
                $label = trim($_POST['service_label'] ?? '');
                $description = trim($_POST['service_description'] ?? '');

                if (empty($iconCode) || empty($label)) {
                    $message = 'Veuillez sélectionner une icône et entrer un libellé.';
                    $messageType = 'error';
                    break;
                }

                $serviceId = createSectionService($sectionCode, $iconCode, $label, $description);

                if ($serviceId) {
                    // Save translations
                    $translations = [];
                    foreach (['en', 'es', 'it'] as $lang) {
                        $transLabel = trim($_POST["service_label_$lang"] ?? '');
                        $transDesc = trim($_POST["service_description_$lang"] ?? '');
                        if (!empty($transLabel)) {
                            $translations[$lang] = [
                                'label' => $transLabel,
                                'description' => $transDesc
                            ];
                        }
                    }
                    if (!empty($translations)) {
                        saveSectionServiceTranslations($serviceId, $translations);
                    }

                    $message = 'Service ajouté avec succès.';
                    $messageType = 'success';
                    $currentSection = $sectionCode;
                } else {
                    $message = 'Erreur lors de la création.';
                    $messageType = 'error';
                }
                break;

            case 'update_service':
                $serviceId = (int)($_POST['service_id'] ?? 0);
                $iconCode = $_POST['icon_code'] ?? '';
                $label = trim($_POST['service_label'] ?? '');
                $description = trim($_POST['service_description'] ?? '');
                $isActive = isset($_POST['service_active']);

                if (empty($iconCode) || empty($label)) {
                    $message = 'Veuillez sélectionner une icône et entrer un libellé.';
                    $messageType = 'error';
                    break;
                }

                $service = getSectionService($serviceId);
                if ($service && updateSectionService($serviceId, $iconCode, $label, $description, $isActive)) {
                    // Save translations
                    $translations = [];
                    foreach (['en', 'es', 'it'] as $lang) {
                        $transLabel = trim($_POST["service_label_$lang"] ?? '');
                        $transDesc = trim($_POST["service_description_$lang"] ?? '');
                        $translations[$lang] = [
                            'label' => $transLabel,
                            'description' => $transDesc
                        ];
                    }
                    saveSectionServiceTranslations($serviceId, $translations);

                    $message = 'Service mis à jour.';
                    $messageType = 'success';
                    $currentSection = $service['section_code'];
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'delete_service':
                $serviceId = (int)($_POST['service_id'] ?? 0);
                $service = getSectionService($serviceId);

                if ($service && deleteSectionService($serviceId)) {
                    $message = 'Service supprimé.';
                    $messageType = 'success';
                    $currentSection = $service['section_code'];
                } else {
                    $message = 'Erreur lors de la suppression.';
                    $messageType = 'error';
                }
                break;

            case 'reorder_services':
                $sectionCode = $_POST['section_code'] ?? '';
                $serviceIds = json_decode($_POST['service_ids'] ?? '[]', true);

                if ($sectionCode && is_array($serviceIds)) {
                    reorderSectionServices($sectionCode, $serviceIds);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }
                break;

            // Gallery CRUD operations
            case 'create_gallery_item':
                $sectionCode = $_POST['section_code'] ?? '';
                $title = trim($_POST['gallery_title'] ?? '');
                $description = trim($_POST['gallery_description'] ?? '');
                $imageAlt = trim($_POST['gallery_image_alt'] ?? '');

                if (empty($title)) {
                    $message = 'Veuillez entrer un titre.';
                    $messageType = 'error';
                    break;
                }

                // Handle image upload
                $imageFilename = '';
                if (!empty($_FILES['gallery_image']['tmp_name'])) {
                    $uploadDir = __DIR__ . '/../uploads/gallery/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $ext = strtolower(pathinfo($_FILES['gallery_image']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $message = 'Format d\'image non supporté. Utilisez JPG, PNG ou WebP.';
                        $messageType = 'error';
                        break;
                    }

                    $filename = 'gallery_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $uploadDir . $filename)) {
                        $imageFilename = 'uploads/gallery/' . $filename;
                    } else {
                        $message = 'Erreur lors de l\'upload de l\'image.';
                        $messageType = 'error';
                        break;
                    }
                } else {
                    $message = 'Veuillez sélectionner une image.';
                    $messageType = 'error';
                    break;
                }

                $itemId = createSectionGalleryItem($sectionCode, $imageFilename, $title, $description, $imageAlt);

                if ($itemId) {
                    // Save translations
                    $translations = [];
                    foreach (['en', 'es', 'it'] as $lang) {
                        $transTitle = trim($_POST["gallery_title_$lang"] ?? '');
                        $transDesc = trim($_POST["gallery_description_$lang"] ?? '');
                        if (!empty($transTitle)) {
                            $translations[$lang] = [
                                'title' => $transTitle,
                                'description' => $transDesc
                            ];
                        }
                    }
                    if (!empty($translations)) {
                        saveSectionGalleryItemTranslations($itemId, $translations);
                    }

                    $message = 'Élément ajouté avec succès.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de l\'ajout de l\'élément.';
                    $messageType = 'error';
                }
                break;

            case 'update_gallery_item':
                $itemId = (int)($_POST['gallery_item_id'] ?? 0);
                $title = trim($_POST['gallery_title'] ?? '');
                $description = trim($_POST['gallery_description'] ?? '');
                $imageAlt = trim($_POST['gallery_image_alt'] ?? '');
                $isActive = isset($_POST['gallery_active']) ? 1 : 0;

                if (empty($title)) {
                    $message = 'Veuillez entrer un titre.';
                    $messageType = 'error';
                    break;
                }

                // Handle image upload (optional for update)
                $newImageFilename = null;
                if (!empty($_FILES['gallery_image']['tmp_name'])) {
                    $uploadDir = __DIR__ . '/../uploads/gallery/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $ext = strtolower(pathinfo($_FILES['gallery_image']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $message = 'Format d\'image non supporté. Utilisez JPG, PNG ou WebP.';
                        $messageType = 'error';
                        break;
                    }

                    $filename = 'gallery_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $uploadDir . $filename)) {
                        $newImageFilename = 'uploads/gallery/' . $filename;

                        // Delete old image
                        $oldItem = getSectionGalleryItem($itemId);
                        if ($oldItem && !empty($oldItem['image_filename']) && file_exists(__DIR__ . '/../' . $oldItem['image_filename'])) {
                            @unlink(__DIR__ . '/../' . $oldItem['image_filename']);
                        }
                    }
                }

                if (updateSectionGalleryItem($itemId, $title, $description, $imageAlt, $isActive, $newImageFilename)) {
                    // Save translations
                    $translations = [];
                    foreach (['en', 'es', 'it'] as $lang) {
                        $transTitle = trim($_POST["gallery_title_$lang"] ?? '');
                        $transDesc = trim($_POST["gallery_description_$lang"] ?? '');
                        if (!empty($transTitle)) {
                            $translations[$lang] = [
                                'title' => $transTitle,
                                'description' => $transDesc
                            ];
                        }
                    }
                    saveSectionGalleryItemTranslations($itemId, $translations);

                    $message = 'Élément mis à jour.';
                    $messageType = 'success';

                    // Get item to redirect to its section
                    $item = getSectionGalleryItem($itemId);
                    if ($item) {
                        $currentSection = $item['section_code'];
                    }
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'delete_gallery_item':
                $itemId = (int)($_POST['gallery_item_id'] ?? 0);
                $item = getSectionGalleryItem($itemId);

                if ($item && deleteSectionGalleryItem($itemId)) {
                    $message = 'Élément supprimé.';
                    $messageType = 'success';
                    $currentSection = $item['section_code'];
                } else {
                    $message = 'Erreur lors de la suppression.';
                    $messageType = 'error';
                }
                break;

            case 'reorder_gallery_items':
                $sectionCode = $_POST['section_code'] ?? '';
                $itemIds = json_decode($_POST['gallery_item_ids'] ?? '[]', true);

                if ($sectionCode && is_array($itemIds)) {
                    reorderSectionGalleryItems($sectionCode, $itemIds);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }
                break;

            case 'create_dynamic_section':
                $page = $_POST['page'] ?? '';
                $templateCode = $_POST['template_code'] ?? '';
                $sectionName = trim($_POST['section_name'] ?? '');

                if (empty($page) || empty($templateCode) || empty($sectionName)) {
                    $message = 'Veuillez remplir tous les champs.';
                    $messageType = 'error';
                    break;
                }

                $newSectionCode = createDynamicSection($page, $templateCode, $sectionName);

                if ($newSectionCode) {
                    $message = 'Section créée avec succès.';
                    $messageType = 'success';
                    $currentSection = $newSectionCode;
                } else {
                    $message = 'Erreur lors de la création de la section.';
                    $messageType = 'error';
                }
                break;

            case 'update_dynamic_section':
                $sectionCode = $_POST['section_code'] ?? '';
                $sectionName = trim($_POST['section_name'] ?? '');

                if (empty($sectionCode) || empty($sectionName)) {
                    $message = 'Veuillez entrer un nom.';
                    $messageType = 'error';
                    break;
                }

                if (updateDynamicSectionName($sectionCode, $sectionName)) {
                    $message = 'Section mise à jour.';
                    $messageType = 'success';
                    $currentSection = $sectionCode;
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'delete_dynamic_section':
                $sectionCode = $_POST['section_code'] ?? '';

                if (deleteDynamicSection($sectionCode)) {
                    $message = 'Section supprimée.';
                    $messageType = 'success';
                    $currentSection = null;
                } else {
                    $message = 'Erreur lors de la suppression.';
                    $messageType = 'error';
                }
                break;

            case 'reorder_dynamic_sections':
                $page = $_POST['page'] ?? '';
                $sectionCodes = json_decode($_POST['section_codes'] ?? '[]', true);

                if ($page && is_array($sectionCodes)) {
                    reorderDynamicSections($page, $sectionCodes);
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
    <title>Gestion du contenu | Admin <?= h($hotelName) ?></title>
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
        .dynamic-sections-sortable {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .section-btn-wrapper {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .section-btn-wrapper.dragging {
            opacity: 0.5;
        }
        .section-drag-handle {
            cursor: grab;
            padding: 0.25rem;
            color: var(--admin-text-light);
            display: flex;
            align-items: center;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .section-drag-handle:hover {
            color: var(--admin-primary);
            background: rgba(139, 90, 43, 0.1);
        }
        .section-drag-handle:active {
            cursor: grabbing;
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

        /* Section settings panel */
        .section-settings-panel {
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .section-settings-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .section-settings-header h4 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-settings-header h4 svg {
            width: 18px;
            height: 18px;
            color: var(--admin-primary);
        }

        /* Background color selector */
        .bg-color-selector {
            margin-bottom: 1rem;
        }
        .bg-color-selector > label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        .bg-color-group {
            margin-bottom: 1rem;
        }
        .bg-color-group:last-child {
            margin-bottom: 0;
        }
        .bg-color-group-label {
            font-size: 0.8rem;
            color: var(--admin-text-light);
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .bg-color-theme-link {
            font-size: 0.75rem;
            color: var(--admin-primary);
            text-decoration: none;
        }
        .bg-color-theme-link:hover {
            text-decoration: underline;
        }
        .bg-color-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .bg-color-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border: 2px solid var(--admin-border);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
            background: var(--admin-bg);
        }
        .bg-color-option:hover {
            border-color: var(--admin-primary);
            background: #f8f8f8;
        }
        .bg-color-option.selected {
            border-color: var(--admin-primary);
            background: #f0f7f0;
        }
        .bg-color-option input[type="radio"] {
            display: none;
        }
        .bg-color-swatch {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }
        .bg-color-name {
            font-size: 0.85rem;
            color: var(--admin-text);
        }
        .bg-color-option.selected .bg-color-name {
            font-weight: 500;
        }

        /* Image position selector */
        .image-position-selector {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--admin-border);
        }
        .image-position-selector > label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        .image-position-options {
            display: flex;
            gap: 1rem;
        }
        .image-position-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 2px solid var(--admin-border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
            background: var(--admin-bg);
            flex: 1;
            max-width: 140px;
        }
        .image-position-option:hover {
            border-color: var(--admin-primary);
            background: #f8f8f8;
        }
        .image-position-option.selected {
            border-color: var(--admin-primary);
            background: #f0f7f0;
        }
        .image-position-option input[type="radio"] {
            display: none;
        }
        .image-position-preview {
            display: flex;
            gap: 4px;
            width: 60px;
            height: 36px;
            border-radius: 4px;
            overflow: hidden;
            background: #e9e9e9;
        }
        .image-position-preview .preview-image {
            width: 50%;
            height: 100%;
            background: var(--admin-primary);
            opacity: 0.7;
        }
        .image-position-preview .preview-text {
            width: 50%;
            height: 100%;
            background: repeating-linear-gradient(
                0deg,
                #ccc,
                #ccc 2px,
                transparent 2px,
                transparent 6px
            );
            background-size: 100% 8px;
            background-position: center;
        }
        .image-position-preview.right {
            flex-direction: row-reverse;
        }
        .image-position-name {
            font-size: 0.8rem;
            color: var(--admin-text);
            text-align: center;
        }
        .image-position-option.selected .image-position-name {
            font-weight: 500;
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

        /* Services panel (reuses feature panel styles with service- prefix) */
        .services-panel {
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .services-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .services-panel-header h4 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .services-panel-header h4 svg {
            width: 18px;
            height: 18px;
            color: var(--admin-primary);
        }
        .services-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .service-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            transition: box-shadow 0.2s;
        }
        .service-item:hover {
            box-shadow: var(--admin-shadow);
        }
        .service-item.inactive {
            opacity: 0.5;
        }
        .service-drag-handle {
            cursor: grab;
            color: var(--admin-text-light);
            padding: 0.25rem;
            display: flex;
            align-items: center;
        }
        .service-drag-handle:active {
            cursor: grabbing;
        }
        .service-icon {
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
        .service-icon svg {
            width: 18px;
            height: 18px;
        }
        .service-info {
            flex: 1;
            min-width: 0;
        }
        .service-label {
            font-weight: 500;
            color: var(--admin-text);
        }
        .service-description {
            font-size: 0.85rem;
            color: var(--admin-text-light);
            margin-top: 0.125rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .service-actions {
            display: flex;
            gap: 0.25rem;
        }
        .service-actions button {
            padding: 0.375rem;
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--admin-text-light);
            border-radius: 4px;
            transition: all 0.2s;
        }
        .service-actions button:hover {
            background: var(--admin-bg);
            color: var(--admin-primary);
        }
        .service-actions button.delete-btn:hover {
            color: #C53030;
        }
        .service-actions button svg {
            width: 16px;
            height: 16px;
        }
        .services-empty {
            text-align: center;
            padding: 1.5rem;
            color: var(--admin-text-light);
            font-size: 0.9rem;
        }
        .add-service-btn {
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
        .add-service-btn:hover {
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }
        .add-service-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Service modal (reuses feature modal structure) */
        .service-modal {
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
        .service-modal.active {
            display: flex;
        }
        .service-modal-content {
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            width: 100%;
            max-width: 500px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .service-form-group {
            margin-bottom: 1rem;
        }
        .service-form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.375rem;
            color: var(--admin-text);
            font-size: 0.9rem;
        }
        .service-form-group input,
        .service-form-group textarea {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .service-form-group input:focus,
        .service-form-group textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .service-form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .service-translations {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--admin-border);
        }
        .service-trans-grid {
            display: grid;
            gap: 0.75rem;
        }
        .service-trans-lang {
            background: var(--admin-bg);
            padding: 0.75rem;
            border-radius: 6px;
        }
        .service-trans-lang-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .service-trans-lang-header .flag {
            font-size: 1rem;
        }
        .service-trans-lang .form-row {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 0.5rem;
        }
        .service-trans-lang input,
        .service-trans-lang textarea {
            padding: 0.5rem;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .service-trans-lang textarea {
            resize: vertical;
            min-height: 40px;
        }

        /* Dynamic sections panel */
        .dynamic-sections-panel {
            background: linear-gradient(135deg, rgba(139, 90, 43, 0.05) 0%, rgba(92, 124, 94, 0.05) 100%);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .dynamic-sections-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .dynamic-sections-header h4 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dynamic-sections-header h4 svg {
            width: 18px;
            height: 18px;
            color: var(--admin-primary);
        }
        .dynamic-sections-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .dynamic-section-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            transition: all 0.2s;
        }
        .dynamic-section-item:hover {
            box-shadow: var(--admin-shadow);
            border-color: var(--admin-primary);
        }
        .dynamic-section-item.active {
            border-color: var(--admin-primary);
            background: rgba(139, 90, 43, 0.05);
        }
        .dynamic-section-drag {
            cursor: grab;
            color: var(--admin-text-light);
            padding: 0.25rem;
        }
        .dynamic-section-drag:active {
            cursor: grabbing;
        }
        .dynamic-section-info {
            flex: 1;
            min-width: 0;
        }
        .dynamic-section-name {
            font-weight: 500;
            color: var(--admin-text);
            margin-bottom: 0.125rem;
        }
        .dynamic-section-template {
            font-size: 0.75rem;
            color: var(--admin-text-light);
        }
        .dynamic-section-actions {
            display: flex;
            gap: 0.25rem;
        }
        .dynamic-section-actions a,
        .dynamic-section-actions button {
            padding: 0.375rem 0.625rem;
            font-size: 0.8rem;
        }
        .dynamic-sections-empty {
            text-align: center;
            padding: 1.5rem;
            color: var(--admin-text-light);
            font-size: 0.9rem;
        }
        .add-dynamic-section-btn {
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
        .add-dynamic-section-btn:hover {
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }
        .add-dynamic-section-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Dynamic section modal */
        .dynamic-section-modal {
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
        .dynamic-section-modal.active {
            display: flex;
        }
        .dynamic-section-modal-content {
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .dynamic-section-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .dynamic-section-modal-header h3 {
            margin: 0;
            font-size: 1.125rem;
        }
        .dynamic-section-modal-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            color: var(--admin-text-light);
        }
        .dynamic-section-modal-close:hover {
            color: var(--admin-text);
        }
        .dynamic-section-modal-close svg {
            width: 20px;
            height: 20px;
        }
        .dynamic-section-modal-body {
            padding: 1.5rem;
        }
        .dynamic-section-form-group {
            margin-bottom: 1.25rem;
        }
        .dynamic-section-form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--admin-text);
        }
        .dynamic-section-form-group input,
        .dynamic-section-form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .dynamic-section-form-group input:focus,
        .dynamic-section-form-group select:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .dynamic-section-form-group small {
            display: block;
            margin-top: 0.375rem;
            font-size: 0.8rem;
            color: var(--admin-text-light);
        }
        .template-option-desc {
            font-size: 0.8rem;
            color: var(--admin-text-light);
            margin-top: 0.25rem;
        }
        .dynamic-section-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--admin-border);
        }

        @media (max-width: 600px) {
            .feature-trans-grid {
                grid-template-columns: 1fr;
            }
            .icon-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        /* Images panel */
        .images-panel {
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .images-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .images-panel-header h4 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .images-panel-header h4 svg {
            width: 18px;
            height: 18px;
            color: var(--admin-primary);
        }
        .images-panel .empty-state {
            padding: 2rem 1rem;
        }
        .images-panel .empty-state-icon {
            width: 48px;
            height: 48px;
        }
        .images-panel .empty-state h3 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        .images-panel .empty-state p {
            font-size: 0.875rem;
        }

        /* Gallery panel */
        .gallery-panel {
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .gallery-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .gallery-panel-header h4 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .gallery-panel-header h4 svg {
            width: 18px;
            height: 18px;
            color: var(--admin-primary);
        }
        .gallery-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .gallery-item {
            position: relative;
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            overflow: hidden;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .gallery-item:hover {
            box-shadow: var(--admin-shadow);
            border-color: var(--admin-primary);
        }
        .gallery-item.inactive {
            opacity: 0.5;
        }
        .gallery-item-image {
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            background: var(--admin-bg);
        }
        .gallery-item-placeholder {
            width: 100%;
            aspect-ratio: 4/3;
            background: var(--admin-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--admin-text-light);
        }
        .gallery-item-placeholder svg {
            width: 48px;
            height: 48px;
            opacity: 0.3;
        }
        .gallery-item-info {
            padding: 0.75rem;
        }
        .gallery-item-title {
            font-weight: 500;
            color: var(--admin-text);
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .gallery-item-description {
            font-size: 0.8rem;
            color: var(--admin-text-light);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .gallery-item-actions {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.25rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .gallery-item:hover .gallery-item-actions {
            opacity: 1;
        }
        .gallery-item-actions button {
            padding: 0.375rem;
            background: var(--admin-card);
            border: none;
            cursor: pointer;
            color: var(--admin-text-light);
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: all 0.2s;
        }
        .gallery-item-actions button:hover {
            background: var(--admin-primary);
            color: white;
        }
        .gallery-item-actions button.delete-btn:hover {
            background: #C53030;
        }
        .gallery-item-actions button svg {
            width: 14px;
            height: 14px;
        }
        .gallery-item-drag {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            padding: 0.375rem;
            background: var(--admin-card);
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            cursor: grab;
            color: var(--admin-text-light);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .gallery-item:hover .gallery-item-drag {
            opacity: 1;
        }
        .gallery-item-drag:active {
            cursor: grabbing;
        }
        .gallery-item-drag svg {
            width: 14px;
            height: 14px;
        }
        .gallery-empty {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--admin-text-light);
            font-size: 0.9rem;
        }
        .add-gallery-btn {
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
        .add-gallery-btn:hover {
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }
        .add-gallery-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Gallery modal */
        .gallery-modal {
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
        .gallery-modal.active {
            display: flex;
        }
        .gallery-modal-content {
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            width: 100%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .gallery-form-group {
            margin-bottom: 1rem;
        }
        .gallery-form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.375rem;
            color: var(--admin-text);
            font-size: 0.9rem;
        }
        .gallery-form-group input,
        .gallery-form-group textarea {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .gallery-form-group input:focus,
        .gallery-form-group textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .gallery-form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .gallery-image-upload {
            margin-bottom: 1rem;
        }
        .gallery-image-preview {
            width: 100%;
            aspect-ratio: 16/9;
            background: var(--admin-bg);
            border: 2px dashed var(--admin-border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            overflow: hidden;
            position: relative;
        }
        .gallery-image-preview:hover {
            border-color: var(--admin-primary);
        }
        .gallery-image-preview.has-image {
            border-style: solid;
        }
        .gallery-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-image-preview-placeholder {
            text-align: center;
            color: var(--admin-text-light);
        }
        .gallery-image-preview-placeholder svg {
            width: 48px;
            height: 48px;
            margin-bottom: 0.5rem;
            opacity: 0.5;
        }
        .gallery-translations {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--admin-border);
        }
        .gallery-trans-grid {
            display: grid;
            gap: 0.75rem;
        }
        .gallery-trans-lang {
            background: var(--admin-bg);
            padding: 0.75rem;
            border-radius: 6px;
        }
        .gallery-trans-lang-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .gallery-trans-lang-header .flag {
            font-size: 1rem;
        }
        .gallery-trans-lang .form-row {
            display: grid;
            gap: 0.5rem;
        }
        .gallery-trans-lang input,
        .gallery-trans-lang textarea {
            padding: 0.5rem;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .gallery-trans-lang textarea {
            resize: vertical;
            min-height: 60px;
        }

        /* =====================================================
           SECTION TYPE PREVIEW SYSTEM
           Visual mockups for section templates
           ===================================================== */

        .section-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            overflow: hidden;
        }

        .section-preview-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--admin-text-light);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-preview-label svg {
            width: 14px;
            height: 14px;
        }

        .section-preview-mock {
            background: white;
            border-radius: 6px;
            padding: 1rem;
            font-size: 0.75rem;
            position: relative;
            min-height: 120px;
        }

        /* Mock elements - reusable building blocks */
        .mock-subtitle {
            height: 8px;
            width: 60px;
            background: linear-gradient(90deg, var(--admin-primary) 0%, transparent 100%);
            border-radius: 4px;
            margin-bottom: 0.5rem;
            opacity: 0.5;
        }

        .mock-title {
            height: 12px;
            width: 120px;
            background: var(--admin-text);
            border-radius: 4px;
            margin-bottom: 0.5rem;
            opacity: 0.3;
        }

        .mock-text {
            height: 6px;
            background: var(--admin-text-light);
            border-radius: 3px;
            margin-bottom: 0.35rem;
            opacity: 0.2;
        }

        .mock-text.short { width: 70%; }
        .mock-text.medium { width: 85%; }
        .mock-text.long { width: 100%; }

        .mock-image {
            background: linear-gradient(135deg, #e0e0e0 0%, #f5f5f5 100%);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #bbb;
        }

        .mock-image svg {
            width: 24px;
            height: 24px;
            opacity: 0.5;
        }

        .mock-icon {
            width: 24px;
            height: 24px;
            background: rgba(139, 90, 43, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .mock-icon svg {
            width: 12px;
            height: 12px;
            color: var(--admin-primary);
            opacity: 0.7;
        }

        .mock-check {
            width: 14px;
            height: 14px;
            background: rgba(92, 124, 94, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .mock-check svg {
            width: 8px;
            height: 8px;
            color: #5C7C5E;
        }

        /* Preview: Services Indicators */
        .preview-services-indicators {
            display: grid;
            grid-template-columns: 1fr 80px;
            gap: 1rem;
            align-items: start;
        }

        .preview-services-indicators .preview-content {
            display: flex;
            flex-direction: column;
        }

        .preview-services-indicators .preview-indicators {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .preview-services-indicators .indicator-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.5rem;
            background: var(--admin-bg);
            border-radius: 4px;
        }

        .preview-services-indicators .indicator-label {
            height: 6px;
            width: 35px;
            background: var(--admin-text-light);
            border-radius: 3px;
            opacity: 0.3;
        }

        .preview-services-indicators .preview-image {
            width: 80px;
            height: 60px;
        }

        /* Preview: Text Style */
        .preview-text-style {
            text-align: center;
            padding: 0.5rem;
        }

        .preview-text-style .mock-subtitle,
        .preview-text-style .mock-title {
            margin-left: auto;
            margin-right: auto;
        }

        .preview-text-style .text-block {
            max-width: 200px;
            margin: 0 auto;
        }

        .preview-text-style .preview-features {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .preview-text-style .feature-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .preview-text-style .feature-label {
            height: 5px;
            width: 30px;
            background: var(--admin-text-light);
            border-radius: 2px;
            opacity: 0.3;
        }

        /* Preview: Services Grid */
        .preview-services-grid .header-area {
            text-align: center;
            margin-bottom: 0.75rem;
        }

        .preview-services-grid .header-area .mock-subtitle,
        .preview-services-grid .header-area .mock-title {
            margin-left: auto;
            margin-right: auto;
        }

        .preview-services-grid .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .preview-services-grid .service-card {
            background: var(--admin-bg);
            border-radius: 4px;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
        }

        .preview-services-grid .card-title {
            height: 5px;
            width: 35px;
            background: var(--admin-text);
            border-radius: 2px;
            opacity: 0.3;
        }

        .preview-services-grid .card-text {
            height: 4px;
            width: 45px;
            background: var(--admin-text-light);
            border-radius: 2px;
            opacity: 0.2;
        }

        /* Preview: Services Checklist */
        .preview-services-checklist {
            display: grid;
            grid-template-columns: 80px 1fr;
            gap: 1rem;
            align-items: start;
        }

        .preview-services-checklist .preview-image {
            width: 80px;
            height: 60px;
        }

        .preview-services-checklist .preview-content {
            display: flex;
            flex-direction: column;
        }

        .preview-services-checklist .checklist {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-top: 0.5rem;
        }

        .preview-services-checklist .check-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .preview-services-checklist .check-label {
            height: 5px;
            width: 60px;
            background: var(--admin-text-light);
            border-radius: 2px;
            opacity: 0.3;
        }

        .preview-services-checklist .check-item:nth-child(2) .check-label { width: 75px; }
        .preview-services-checklist .check-item:nth-child(3) .check-label { width: 50px; }

        /* Preview: Gallery Style */
        .preview-gallery-style .header-area {
            text-align: center;
            margin-bottom: 0.75rem;
        }

        .preview-gallery-style .header-area .mock-subtitle,
        .preview-gallery-style .header-area .mock-title {
            margin-left: auto;
            margin-right: auto;
        }

        .preview-gallery-style .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
        }

        .preview-gallery-style .gallery-card {
            background: var(--admin-bg);
            border-radius: 4px;
            overflow: hidden;
        }

        .preview-gallery-style .card-image {
            height: 35px;
            background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-gallery-style .card-image svg {
            width: 14px;
            height: 14px;
            color: #ccc;
        }

        .preview-gallery-style .card-content {
            padding: 0.35rem;
        }

        .preview-gallery-style .card-title {
            height: 5px;
            width: 80%;
            background: var(--admin-text);
            border-radius: 2px;
            opacity: 0.3;
            margin-bottom: 0.25rem;
        }

        .preview-gallery-style .card-desc {
            height: 4px;
            width: 60%;
            background: var(--admin-text-light);
            border-radius: 2px;
            opacity: 0.2;
        }

        /* Preview: Gallery Cards (room-card style with overlay) */
        .preview-gallery-cards .header-area {
            text-align: center;
            margin-bottom: 0.75rem;
        }

        .preview-gallery-cards .header-area .mock-subtitle,
        .preview-gallery-cards .header-area .mock-title {
            margin-left: auto;
            margin-right: auto;
        }

        .preview-gallery-cards .gallery-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .preview-gallery-cards .gallery-card-overlay {
            position: relative;
            border-radius: 6px;
            overflow: hidden;
            height: 70px;
        }

        .preview-gallery-cards .card-image-bg {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #8B6F47 0%, #6B5635 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-gallery-cards .card-image-bg svg {
            width: 18px;
            height: 18px;
            color: rgba(255,255,255,0.3);
        }

        .preview-gallery-cards .card-overlay-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 0.4rem;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
        }

        .preview-gallery-cards .overlay-title {
            height: 5px;
            width: 70%;
            background: #fff;
            border-radius: 2px;
            opacity: 0.9;
            margin-bottom: 0.2rem;
        }

        .preview-gallery-cards .overlay-desc {
            height: 3px;
            width: 50%;
            background: #fff;
            border-radius: 2px;
            opacity: 0.6;
        }

        /* Animation for preview transitions */
        .section-preview-mock {
            transition: opacity 0.2s ease;
        }

        .section-preview-mock.switching {
            opacity: 0.5;
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
                <h2><?= h($hotelName) ?></h2>
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
                            <?php $pageDynamicSections = getDynamicSections($page); ?>
                            <div class="page-group">
                                <div class="page-group-title"><?= h($pageName) ?></div>
                                <div class="sections-nav">
                                    <?php
                                    // Show static sections first (non-dynamic)
                                    foreach ($sectionsByPage[$page] as $section):
                                        if (!empty($section['is_dynamic'])) continue;
                                    ?>
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

                                    <?php if (!empty($pageDynamicSections)): ?>
                                    <div class="dynamic-sections-sortable" data-page="<?= h($page) ?>">
                                    <?php
                                    // Show dynamic sections with drag handles
                                    foreach ($pageDynamicSections as $dynSection):
                                    ?>
                                    <div class="section-btn-wrapper" data-section-code="<?= h($dynSection['code']) ?>">
                                        <span class="section-drag-handle" title="Glisser pour réordonner">
                                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="8" y1="6" x2="16" y2="6"/>
                                                <line x1="8" y1="12" x2="16" y2="12"/>
                                                <line x1="8" y1="18" x2="16" y2="18"/>
                                            </svg>
                                        </span>
                                        <a href="?section=<?= h($dynSection['code']) ?>"
                                           class="section-btn <?= $currentSection === $dynSection['code'] ? 'active' : '' ?>"
                                           style="border-style: dashed; flex: 1;">
                                            <?= h($dynSection['custom_name'] ?: $dynSection['name']) ?>
                                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: 0.5rem; opacity: 0.5;">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <button type="button" class="section-btn add-section-btn" data-page="<?= h($page) ?>" style="border-style: dashed; color: var(--admin-text-light);">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                        Nouvelle section
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($currentSection && $currentSectionData): ?>
                    <?php
                    // Calculate if adding is allowed (used in images panel)
                    $canAdd = true;
                    if ($currentSectionData['max_blocks']) {
                        $canAdd = count($blocks) < $currentSectionData['max_blocks'];
                    }
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h2><?= h($currentSectionData['name']) ?></h2>
                            <?php if (!empty($currentSectionData['is_dynamic'])): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette section et tout son contenu ?');">
                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_dynamic_section">
                                <input type="hidden" name="section_code" value="<?= h($currentSection) ?>">
                                <button type="submit" class="btn btn-danger">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                    Supprimer la section
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php
                            // Show section settings panel for dynamic sections (Appearance settings first)
                            $showSettingsPanel = $currentSectionData && ($currentSectionData['is_dynamic'] ?? false);
                            if ($showSettingsPanel):
                                $bgOptions = getSectionBackgroundOptions();
                                $currentBgColor = $currentSectionData['background_color'] ?? 'cream';
                                $templateType = $currentSectionData['template_type'] ?? '';
                                $supportsImagePosition = sectionSupportsImagePosition($templateType);
                                $imagePositionOptions = getImagePositionOptions();
                                $currentImagePosition = $currentSectionData['image_position'] ?? 'left';
                            ?>
                            <div class="section-settings-panel">
                                <div class="section-settings-header">
                                    <h4>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                                        </svg>
                                        Apparence de la section
                                    </h4>
                                </div>

                                <form method="POST" id="sectionSettingsForm">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                    <input type="hidden" name="action" value="update_section_settings">
                                    <input type="hidden" name="section_code" value="<?= h($currentSection) ?>">

                                    <div class="bg-color-selector">
                                        <label>Couleur de fond</label>

                                        <div class="bg-color-group">
                                            <p class="bg-color-group-label">Couleurs du thème <a href="theme.php" class="bg-color-theme-link">Modifier</a></p>
                                            <div class="bg-color-options">
                                                <?php foreach ($bgOptions as $colorKey => $colorData):
                                                    if (($colorData['group'] ?? 'theme') !== 'theme') continue;
                                                    $tooltip = $colorData['css_var'] ? "Variable CSS: {$colorData['css_var']}" : '';
                                                ?>
                                                <label class="bg-color-option <?= $colorKey === $currentBgColor ? 'selected' : '' ?>" <?= $tooltip ? 'title="' . h($tooltip) . '"' : '' ?>>
                                                    <input type="radio" name="background_color" value="<?= h($colorKey) ?>" <?= $colorKey === $currentBgColor ? 'checked' : '' ?>>
                                                    <span class="bg-color-swatch" style="background-color: <?= h($colorData['preview']) ?>"></span>
                                                    <span class="bg-color-name"><?= h($colorData['label']) ?></span>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="bg-color-group">
                                            <p class="bg-color-group-label">Couleurs neutres</p>
                                            <div class="bg-color-options">
                                                <?php foreach ($bgOptions as $colorKey => $colorData):
                                                    if (($colorData['group'] ?? 'theme') !== 'neutral') continue;
                                                ?>
                                                <label class="bg-color-option <?= $colorKey === $currentBgColor ? 'selected' : '' ?>">
                                                    <input type="radio" name="background_color" value="<?= h($colorKey) ?>" <?= $colorKey === $currentBgColor ? 'checked' : '' ?>>
                                                    <span class="bg-color-swatch" style="background-color: <?= h($colorData['preview']) ?>"></span>
                                                    <span class="bg-color-name"><?= h($colorData['label']) ?></span>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($supportsImagePosition): ?>
                                    <div class="image-position-selector">
                                        <label>Position de l'image</label>
                                        <div class="image-position-options">
                                            <?php foreach ($imagePositionOptions as $posKey => $posData): ?>
                                            <label class="image-position-option <?= $posKey === $currentImagePosition ? 'selected' : '' ?>">
                                                <input type="radio" name="image_position" value="<?= h($posKey) ?>" <?= $posKey === $currentImagePosition ? 'checked' : '' ?>>
                                                <span class="image-position-preview <?= h($posKey) ?>">
                                                    <span class="preview-image"></span>
                                                    <span class="preview-text"></span>
                                                </span>
                                                <span class="image-position-name"><?= h($posData['label']) ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="overlay-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                                <polyline points="17 21 17 13 7 13 7 21"/>
                                                <polyline points="7 3 7 8 15 8"/>
                                            </svg>
                                            Enregistrer l'apparence
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>

                            <?php
                            // Show overlay text panel for sections that support it (static or dynamic with has_overlay)
                            $sectionsWithOverlay = ['home_hero'];
                            $showOverlayPanel = in_array($currentSection, $sectionsWithOverlay) || (!empty($currentSectionData['has_overlay']) && !empty($currentSectionData['is_dynamic']));
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
                                    '_default' => [
                                        'subtitle_placeholder' => 'Ex: Sous-titre de la section',
                                        'subtitle_hint' => 'Petit texte au-dessus du titre',
                                        'title_placeholder' => 'Ex: Titre de la section',
                                        'title_hint' => 'Titre principal de la section',
                                        'description_placeholder' => 'Ex: Description de la section...',
                                        'description_hint' => 'Texte descriptif (utilisez deux lignes vides pour séparer les paragraphes)'
                                    ]
                                ];
                                $config = $overlayConfig[$currentSection] ?? $overlayConfig['_default'];
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
                            // Check if this is a services_checklist section (simplified checklist - no icon selector)
                            $isChecklistMode = $currentSectionData && ($currentSectionData['template_type'] ?? '') === 'services_checklist';
                            if ($showFeaturesPanel):
                                $sectionFeatures = getSectionFeaturesWithTranslations($currentSection, false);
                                $availableIcons = getAvailableIcons();
                                $iconCategories = getIconCategories();
                            ?>
                            <div class="features-panel" data-checklist-mode="<?= $isChecklistMode ? '1' : '0' ?>">
                                <div class="features-panel-header">
                                    <h4>
                                        <?php if ($isChecklistMode): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        Liste à puces
                                        <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                        </svg>
                                        Indicateurs
                                        <?php endif; ?>
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
                                            <?php if ($isChecklistMode): ?>
                                            <?= getIconSvg('check') ?>
                                            <?php else: ?>
                                            <?= getIconSvg($feature['icon_code']) ?>
                                            <?php endif; ?>
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
                                    <?= $isChecklistMode ? 'Aucun élément configuré' : 'Aucun indicateur configuré' ?>
                                </div>
                                <?php endif; ?>

                                <button type="button" class="add-feature-btn" id="addFeatureBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    <?= $isChecklistMode ? 'Ajouter un élément' : 'Ajouter un indicateur' ?>
                                </button>
                            </div>

                            <!-- Feature Modal (Add/Edit) -->
                            <div class="feature-modal" id="featureModal" data-checklist-mode="<?= $isChecklistMode ? '1' : '0' ?>">
                                <div class="feature-modal-content">
                                    <div class="feature-modal-header">
                                        <h3 id="featureModalTitle"><?= $isChecklistMode ? 'Ajouter un élément' : 'Ajouter un indicateur' ?></h3>
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
                                        <input type="hidden" name="icon_code" id="selectedIconCode" value="<?= $isChecklistMode ? 'check' : '' ?>">

                                        <div class="feature-modal-body">
                                            <div class="icon-selector" <?= $isChecklistMode ? 'style="display: none;"' : '' ?>>
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

                            <?php
                            // Show services panel for sections that support services
                            $showServicesPanel = $currentSectionData && !empty($currentSectionData['has_services']);
                            if ($showServicesPanel):
                                $sectionServices = getSectionServicesWithTranslations($currentSection, false);
                                if (!isset($availableIcons)) $availableIcons = getAvailableIcons();
                                if (!isset($iconCategories)) $iconCategories = getIconCategories();
                            ?>
                            <div class="services-panel">
                                <div class="services-panel-header">
                                    <h4>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="7" height="7"/>
                                            <rect x="14" y="3" width="7" height="7"/>
                                            <rect x="14" y="14" width="7" height="7"/>
                                            <rect x="3" y="14" width="7" height="7"/>
                                        </svg>
                                        Services
                                    </h4>
                                </div>

                                <?php if (!empty($sectionServices)): ?>
                                <div class="services-list" id="servicesList" data-section="<?= h($currentSection) ?>">
                                    <?php foreach ($sectionServices as $service): ?>
                                    <div class="service-item <?= !$service['is_active'] ? 'inactive' : '' ?>" data-service-id="<?= $service['id'] ?>">
                                        <div class="service-drag-handle" title="Glisser pour réordonner">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="8" y1="6" x2="16" y2="6"/>
                                                <line x1="8" y1="12" x2="16" y2="12"/>
                                                <line x1="8" y1="18" x2="16" y2="18"/>
                                            </svg>
                                        </div>
                                        <div class="service-icon">
                                            <?= getIconSvg($service['icon_code']) ?>
                                        </div>
                                        <div class="service-info">
                                            <span class="service-label"><?= h($service['label']) ?></span>
                                            <?php if (!empty($service['description'])): ?>
                                            <span class="service-description"><?= h($service['description']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="service-actions">
                                            <button type="button" class="edit-service-btn" data-service='<?= h(json_encode([
                                                'id' => $service['id'],
                                                'icon_code' => $service['icon_code'],
                                                'label' => $service['label'],
                                                'description' => $service['description'] ?? '',
                                                'is_active' => $service['is_active'],
                                                'translations' => $service['translations'] ?? []
                                            ])) ?>' title="Modifier">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce service ?');">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_service">
                                                <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
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
                                <div class="services-empty">
                                    Aucun service configuré
                                </div>
                                <?php endif; ?>

                                <button type="button" class="add-service-btn" id="addServiceBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    Ajouter un service
                                </button>
                            </div>

                            <!-- Service Modal -->
                            <div class="service-modal" id="serviceModal">
                                <div class="service-modal-content">
                                    <div class="feature-modal-header">
                                        <h3 id="serviceModalTitle">Ajouter un service</h3>
                                        <button type="button" class="feature-modal-close" id="serviceModalClose">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <form method="POST" id="serviceForm">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                        <input type="hidden" name="action" id="serviceAction" value="create_service">
                                        <input type="hidden" name="section_code" value="<?= h($currentSection) ?>">
                                        <input type="hidden" name="service_id" id="serviceId" value="">
                                        <input type="hidden" name="icon_code" id="serviceSelectedIconCode" value="">

                                        <div class="feature-modal-body">
                                            <div class="icon-selector">
                                                <label class="icon-selector-label">Icône</label>
                                                <?php foreach ($iconCategories as $catCode => $catName): ?>
                                                <div class="icon-category">
                                                    <div class="icon-category-name"><?= h($catName) ?></div>
                                                    <div class="icon-grid" id="serviceIconGrid_<?= h($catCode) ?>">
                                                        <?php foreach ($availableIcons as $iconCode => $icon): ?>
                                                        <?php if ($icon['category'] === $catCode): ?>
                                                        <div class="icon-option service-icon-option" data-icon="<?= h($iconCode) ?>" title="<?= h($icon['name']) ?>">
                                                            <?= $icon['svg'] ?>
                                                            <span class="icon-option-name"><?= h($icon['name']) ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <div class="service-form-group">
                                                <label for="serviceLabel">Titre (Français)</label>
                                                <input type="text" id="serviceLabel" name="service_label" required placeholder="Ex: Table d'hôtes">
                                            </div>

                                            <div class="service-form-group">
                                                <label for="serviceDescription">Description (Français)</label>
                                                <textarea id="serviceDescription" name="service_description" placeholder="Description courte du service..."></textarea>
                                            </div>

                                            <div class="service-translations">
                                                <div class="feature-translations-header" id="serviceTransToggle">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="6 9 12 15 18 9"/>
                                                    </svg>
                                                    <h5>Traductions (optionnel)</h5>
                                                </div>
                                                <div class="service-trans-grid" id="serviceTransContent">
                                                    <div class="service-trans-lang">
                                                        <div class="service-trans-lang-header">
                                                            <span class="flag">🇬🇧</span> English
                                                        </div>
                                                        <div class="form-row">
                                                            <input type="text" name="service_label_en" id="serviceLabelEn" placeholder="Title">
                                                            <textarea name="service_description_en" id="serviceDescriptionEn" placeholder="Description"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="service-trans-lang">
                                                        <div class="service-trans-lang-header">
                                                            <span class="flag">🇪🇸</span> Español
                                                        </div>
                                                        <div class="form-row">
                                                            <input type="text" name="service_label_es" id="serviceLabelEs" placeholder="Título">
                                                            <textarea name="service_description_es" id="serviceDescriptionEs" placeholder="Descripción"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="service-trans-lang">
                                                        <div class="service-trans-lang-header">
                                                            <span class="flag">🇮🇹</span> Italiano
                                                        </div>
                                                        <div class="form-row">
                                                            <input type="text" name="service_label_it" id="serviceLabelIt" placeholder="Titolo">
                                                            <textarea name="service_description_it" id="serviceDescriptionIt" placeholder="Descrizione"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="feature-active-toggle" id="serviceActiveToggle" style="display: none;">
                                                <input type="checkbox" name="service_active" id="serviceIsActive" value="1" checked>
                                                <span>Actif (visible sur le site)</span>
                                            </div>
                                        </div>

                                        <div class="feature-modal-footer">
                                            <button type="button" class="btn btn-outline" id="serviceModalCancel">Annuler</button>
                                            <button type="submit" class="btn btn-primary" id="serviceSubmitBtn">Ajouter</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php
                            // Show gallery panel for sections that support gallery
                            $showGalleryPanel = $currentSectionData && !empty($currentSectionData['has_gallery']);
                            if ($showGalleryPanel):
                                $galleryItems = getSectionGalleryItemsWithTranslations($currentSection, false);
                            ?>
                            <div class="gallery-panel">
                                <div class="gallery-panel-header">
                                    <h4>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="7" height="7"/>
                                            <rect x="14" y="3" width="7" height="7"/>
                                            <rect x="14" y="14" width="7" height="7"/>
                                            <rect x="3" y="14" width="7" height="7"/>
                                        </svg>
                                        Galerie d'images
                                    </h4>
                                </div>

                                <?php if (!empty($galleryItems)): ?>
                                <div class="gallery-list" id="galleryList" data-section="<?= h($currentSection) ?>">
                                    <?php foreach ($galleryItems as $item): ?>
                                    <div class="gallery-item <?= !$item['is_active'] ? 'inactive' : '' ?>" data-gallery-id="<?= $item['id'] ?>">
                                        <div class="gallery-item-drag" title="Glisser pour réordonner">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="8" y1="6" x2="16" y2="6"/>
                                                <line x1="8" y1="12" x2="16" y2="12"/>
                                                <line x1="8" y1="18" x2="16" y2="18"/>
                                            </svg>
                                        </div>
                                        <?php if (!empty($item['image_filename'])): ?>
                                        <img src="../<?= h($item['image_filename']) ?>" alt="<?= h($item['title']) ?>" class="gallery-item-image">
                                        <?php else: ?>
                                        <div class="gallery-item-placeholder">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                <polyline points="21 15 16 10 5 21"/>
                                            </svg>
                                        </div>
                                        <?php endif; ?>
                                        <div class="gallery-item-info">
                                            <div class="gallery-item-title"><?= h($item['title']) ?></div>
                                            <?php if (!empty($item['description'])): ?>
                                            <div class="gallery-item-description"><?= h($item['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="gallery-item-actions">
                                            <button type="button" class="edit-gallery-btn" data-gallery='<?= h(json_encode([
                                                'id' => $item['id'],
                                                'title' => $item['title'],
                                                'description' => $item['description'] ?? '',
                                                'image_filename' => $item['image_filename'] ?? '',
                                                'is_active' => $item['is_active'],
                                                'translations' => $item['translations'] ?? []
                                            ])) ?>' title="Modifier">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cet élément ?');">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_gallery_item">
                                                <input type="hidden" name="gallery_item_id" value="<?= $item['id'] ?>">
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
                                <div class="gallery-empty">
                                    Aucun élément dans la galerie
                                </div>
                                <?php endif; ?>

                                <button type="button" class="add-gallery-btn" id="addGalleryBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    Ajouter un élément
                                </button>
                            </div>

                            <!-- Gallery Modal -->
                            <div class="gallery-modal" id="galleryModal">
                                <div class="gallery-modal-content">
                                    <div class="feature-modal-header">
                                        <h3 id="galleryModalTitle">Ajouter un élément</h3>
                                        <button type="button" class="feature-modal-close" id="galleryModalClose">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <form method="POST" id="galleryForm" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                        <input type="hidden" name="action" id="galleryAction" value="create_gallery_item">
                                        <input type="hidden" name="section_code" value="<?= h($currentSection) ?>">
                                        <input type="hidden" name="gallery_item_id" id="galleryItemId" value="">

                                        <div class="feature-modal-body">
                                            <div class="gallery-image-upload">
                                                <label class="gallery-form-group label">Image</label>
                                                <div class="gallery-image-preview" id="galleryImagePreview">
                                                    <div class="gallery-image-preview-placeholder" id="galleryImagePlaceholder">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <polyline points="21 15 16 10 5 21"/>
                                                        </svg>
                                                        <span>Cliquer pour ajouter une image</span>
                                                    </div>
                                                    <img id="galleryPreviewImg" src="" alt="Preview" style="display: none;">
                                                </div>
                                                <input type="file" id="galleryImageInput" name="gallery_image" accept="image/*" style="display: none;">
                                                <input type="hidden" name="existing_image" id="galleryExistingImage" value="">
                                            </div>

                                            <div class="gallery-form-group">
                                                <label for="galleryTitle">Titre (Français)</label>
                                                <input type="text" id="galleryTitle" name="gallery_title" required placeholder="Ex: Vignobles de Saint-Émilion">
                                            </div>

                                            <div class="gallery-form-group">
                                                <label for="galleryDescription">Description (Français)</label>
                                                <textarea id="galleryDescription" name="gallery_description" placeholder="Description de l'élément..."></textarea>
                                            </div>

                                            <div class="gallery-translations">
                                                <div class="feature-translations-header" id="galleryTransToggle">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="6 9 12 15 18 9"/>
                                                    </svg>
                                                    <h5>Traductions (optionnel)</h5>
                                                </div>
                                                <div class="gallery-trans-grid" id="galleryTransContent">
                                                    <div class="gallery-trans-lang">
                                                        <div class="gallery-trans-lang-header">
                                                            <span class="flag">🇬🇧</span> English
                                                        </div>
                                                        <div class="form-row">
                                                            <input type="text" name="gallery_title_en" id="galleryTitleEn" placeholder="Title">
                                                            <textarea name="gallery_description_en" id="galleryDescriptionEn" placeholder="Description"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="gallery-trans-lang">
                                                        <div class="gallery-trans-lang-header">
                                                            <span class="flag">🇪🇸</span> Español
                                                        </div>
                                                        <div class="form-row">
                                                            <input type="text" name="gallery_title_es" id="galleryTitleEs" placeholder="Título">
                                                            <textarea name="gallery_description_es" id="galleryDescriptionEs" placeholder="Descripción"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="gallery-trans-lang">
                                                        <div class="gallery-trans-lang-header">
                                                            <span class="flag">🇮🇹</span> Italiano
                                                        </div>
                                                        <div class="form-row">
                                                            <input type="text" name="gallery_title_it" id="galleryTitleIt" placeholder="Titolo">
                                                            <textarea name="gallery_description_it" id="galleryDescriptionIt" placeholder="Descrizione"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="feature-active-toggle" id="galleryActiveToggle" style="display: none;">
                                                <input type="checkbox" name="gallery_active" id="galleryIsActive" value="1" checked>
                                                <span>Actif (visible sur le site)</span>
                                            </div>
                                        </div>

                                        <div class="feature-modal-footer">
                                            <button type="button" class="btn btn-outline" id="galleryModalCancel">Annuler</button>
                                            <button type="submit" class="btn btn-primary" id="gallerySubmitBtn">Ajouter</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php
                            // Show images panel for sections that support images
                            $showImagesPanel = $currentSectionData && $currentSectionData['image_mode'] !== IMAGE_FORBIDDEN;
                            $isImageOnly = $currentSectionData && !$currentSectionData['has_title'] && !$currentSectionData['has_description'];
                            if ($showImagesPanel):
                            ?>
                            <div class="images-panel">
                                <div class="images-panel-header">
                                    <h4>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21 15 16 10 5 21"/>
                                        </svg>
                                        Images
                                    </h4>
                                    <?php if ($canAdd): ?>
                                    <a href="?section=<?= h($currentSection) ?>&view=add" class="btn btn-sm btn-primary">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                        <?= $isImageOnly ? 'Ajouter une image' : 'Ajouter' ?>
                                    </a>
                                    <?php endif; ?>
                                </div>

                                <?php if (empty($blocks)): ?>
                                <div class="empty-state">
                                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <polyline points="21 15 16 10 5 21"/>
                                    </svg>
                                    <h3><?= $isImageOnly ? 'Aucune image' : 'Aucun contenu' ?></h3>
                                    <p>Cette section ne contient pas encore <?= $isImageOnly ? 'd\'image' : 'de contenu' ?>.</p>
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

            <!-- Dynamic Section Modal -->
            <?php $templates = getSectionTemplates(); ?>
            <div class="dynamic-section-modal" id="dynamicSectionModal">
                <div class="dynamic-section-modal-content">
                    <div class="dynamic-section-modal-header">
                        <h3>Nouvelle section</h3>
                        <button type="button" class="dynamic-section-modal-close" id="dynamicSectionModalClose">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                    <form method="POST" id="dynamicSectionForm">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        <input type="hidden" name="action" value="create_dynamic_section">
                        <input type="hidden" name="page" id="dynamicSectionPage" value="">

                        <div class="dynamic-section-modal-body">
                            <div class="dynamic-section-form-group">
                                <label for="sectionName">Nom de la section</label>
                                <input type="text" id="sectionName" name="section_name" required placeholder="Ex: Nos engagements">
                                <small>Ce nom sera affiché dans l'admin et peut être utilisé comme titre par défaut</small>
                            </div>

                            <div class="dynamic-section-form-group">
                                <label for="templateCode">Type de section</label>
                                <select id="templateCode" name="template_code" required>
                                    <?php foreach ($templates as $template): ?>
                                    <option value="<?= h($template['code']) ?>"><?= h($template['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="template-option-desc" id="templateDescription">
                                    <?= h($templates[0]['description'] ?? '') ?>
                                </div>

                                <!-- Section Type Visual Preview -->
                                <div class="section-preview">
                                    <div class="section-preview-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="3" y1="9" x2="21" y2="9"/>
                                            <line x1="9" y1="21" x2="9" y2="9"/>
                                        </svg>
                                        Aperçu du rendu
                                    </div>
                                    <div class="section-preview-mock" id="sectionPreviewMock">
                                        <!-- Preview templates - one for each section type -->

                                        <!-- Services Indicators Preview -->
                                        <div class="preview-template preview-services-indicators" data-template="services_indicators">
                                            <div class="preview-content">
                                                <div class="mock-subtitle"></div>
                                                <div class="mock-title"></div>
                                                <div class="mock-text long"></div>
                                                <div class="mock-text medium"></div>
                                                <div class="preview-indicators">
                                                    <div class="indicator-item">
                                                        <div class="mock-icon">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <circle cx="12" cy="12" r="10"/>
                                                            </svg>
                                                        </div>
                                                        <div class="indicator-label"></div>
                                                    </div>
                                                    <div class="indicator-item">
                                                        <div class="mock-icon">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="indicator-label"></div>
                                                    </div>
                                                    <div class="indicator-item">
                                                        <div class="mock-icon">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="indicator-label"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mock-image preview-image">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                                    <polyline points="21 15 16 10 5 21"/>
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Text Style Preview -->
                                        <div class="preview-template preview-text-style" data-template="text_style" style="display: none;">
                                            <div class="mock-subtitle"></div>
                                            <div class="mock-title"></div>
                                            <div class="text-block">
                                                <div class="mock-text long"></div>
                                                <div class="mock-text medium"></div>
                                                <div class="mock-text short"></div>
                                            </div>
                                            <div class="preview-features">
                                                <div class="feature-item">
                                                    <div class="mock-icon">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"/>
                                                        </svg>
                                                    </div>
                                                    <div class="feature-label"></div>
                                                </div>
                                                <div class="feature-item">
                                                    <div class="mock-icon">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                                        </svg>
                                                    </div>
                                                    <div class="feature-label"></div>
                                                </div>
                                                <div class="feature-item">
                                                    <div class="mock-icon">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polygon points="12 2 15 8.5 22 9.3 17 14 18 21 12 17.8 6 21 7 14 2 9.3 9 8.5 12 2"/>
                                                        </svg>
                                                    </div>
                                                    <div class="feature-label"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Services Grid Preview -->
                                        <div class="preview-template preview-services-grid" data-template="services_style" style="display: none;">
                                            <div class="header-area">
                                                <div class="mock-subtitle"></div>
                                                <div class="mock-title"></div>
                                            </div>
                                            <div class="services-grid">
                                                <div class="service-card">
                                                    <div class="mock-icon">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                                                            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-title"></div>
                                                    <div class="card-text"></div>
                                                </div>
                                                <div class="service-card">
                                                    <div class="mock-icon">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-title"></div>
                                                    <div class="card-text"></div>
                                                </div>
                                                <div class="service-card">
                                                    <div class="mock-icon">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <polyline points="12 6 12 12 16 14"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-title"></div>
                                                    <div class="card-text"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Services Checklist Preview -->
                                        <div class="preview-template preview-services-checklist" data-template="services_checklist" style="display: none;">
                                            <div class="mock-image preview-image">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                                    <polyline points="21 15 16 10 5 21"/>
                                                </svg>
                                            </div>
                                            <div class="preview-content">
                                                <div class="mock-subtitle"></div>
                                                <div class="mock-title"></div>
                                                <div class="checklist">
                                                    <div class="check-item">
                                                        <div class="mock-check">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                                <polyline points="20 6 9 17 4 12"/>
                                                            </svg>
                                                        </div>
                                                        <div class="check-label"></div>
                                                    </div>
                                                    <div class="check-item">
                                                        <div class="mock-check">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                                <polyline points="20 6 9 17 4 12"/>
                                                            </svg>
                                                        </div>
                                                        <div class="check-label"></div>
                                                    </div>
                                                    <div class="check-item">
                                                        <div class="mock-check">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                                <polyline points="20 6 9 17 4 12"/>
                                                            </svg>
                                                        </div>
                                                        <div class="check-label"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Gallery Style Preview -->
                                        <div class="preview-template preview-gallery-style" data-template="gallery_style" style="display: none;">
                                            <div class="header-area">
                                                <div class="mock-subtitle"></div>
                                                <div class="mock-title"></div>
                                            </div>
                                            <div class="gallery-grid">
                                                <div class="gallery-card">
                                                    <div class="card-image">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <polyline points="21 15 16 10 5 21"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-title"></div>
                                                        <div class="card-desc"></div>
                                                    </div>
                                                </div>
                                                <div class="gallery-card">
                                                    <div class="card-image">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <polyline points="21 15 16 10 5 21"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-title"></div>
                                                        <div class="card-desc"></div>
                                                    </div>
                                                </div>
                                                <div class="gallery-card">
                                                    <div class="card-image">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <polyline points="21 15 16 10 5 21"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-title"></div>
                                                        <div class="card-desc"></div>
                                                    </div>
                                                </div>
                                                <div class="gallery-card">
                                                    <div class="card-image">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <polyline points="21 15 16 10 5 21"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-title"></div>
                                                        <div class="card-desc"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Gallery Cards Preview (room-card style with overlay) -->
                                        <div class="preview-template preview-gallery-cards" data-template="gallery_cards" style="display: none;">
                                            <div class="header-area">
                                                <div class="mock-subtitle"></div>
                                                <div class="mock-title"></div>
                                            </div>
                                            <div class="gallery-cards-grid">
                                                <div class="gallery-card-overlay">
                                                    <div class="card-image-bg">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <polyline points="21 15 16 10 5 21"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-overlay-content">
                                                        <div class="overlay-title"></div>
                                                        <div class="overlay-desc"></div>
                                                    </div>
                                                </div>
                                                <div class="gallery-card-overlay">
                                                    <div class="card-image-bg">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <polyline points="21 15 16 10 5 21"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-overlay-content">
                                                        <div class="overlay-title"></div>
                                                        <div class="overlay-desc"></div>
                                                    </div>
                                                </div>
                                                <div class="gallery-card-overlay">
                                                    <div class="card-image-bg">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <polyline points="21 15 16 10 5 21"/>
                                                        </svg>
                                                    </div>
                                                    <div class="card-overlay-content">
                                                        <div class="overlay-title"></div>
                                                        <div class="overlay-desc"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dynamic-section-modal-footer">
                            <button type="button" class="btn btn-outline" id="dynamicSectionModalCancel">Annuler</button>
                            <button type="submit" class="btn btn-primary">Créer la section</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Template descriptions for the modal
    const templateDescriptions = <?= json_encode(array_column($templates, 'description', 'code')) ?>;
    </script>
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

    // Background color selector interaction
    const bgColorOptions = document.querySelectorAll('.bg-color-option');
    bgColorOptions.forEach(option => {
        option.addEventListener('click', () => {
            // Remove selected class from all options
            bgColorOptions.forEach(opt => opt.classList.remove('selected'));
            // Add selected class to clicked option
            option.classList.add('selected');
            // Check the radio button
            const radio = option.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    // Image position selector interaction
    const imagePositionOptions = document.querySelectorAll('.image-position-option');
    imagePositionOptions.forEach(option => {
        option.addEventListener('click', () => {
            // Remove selected class from all options
            imagePositionOptions.forEach(opt => opt.classList.remove('selected'));
            // Add selected class to clicked option
            option.classList.add('selected');
            // Check the radio button
            const radio = option.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

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
            const isChecklistMode = featureModal.dataset.checklistMode === '1';
            // Only validate icon selection for non-services_checklist sections
            if (!isChecklistMode && !selectedIconCode.value) {
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
            // Check if this is a services_checklist section (checklist mode)
            const isChecklistMode = featureModal.dataset.checklistMode === '1';
            const itemLabel = isChecklistMode ? 'élément' : 'indicateur';

            // Reset form
            featureForm.reset();
            document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));

            // For services_checklist, always use 'check' icon
            if (isChecklistMode) {
                selectedIconCode.value = 'check';
            } else {
                selectedIconCode.value = '';
            }

            if (mode === 'edit' && data) {
                featureModalTitle.textContent = isChecklistMode ? 'Modifier l\'élément' : 'Modifier l\'indicateur';
                featureAction.value = 'update_feature';
                featureId.value = data.id;
                featureSubmitBtn.textContent = 'Enregistrer';
                featureActiveToggle.style.display = 'flex';

                // Select icon (only for non-services_checklist)
                if (!isChecklistMode) {
                    const iconOption = document.querySelector(`.icon-option[data-icon="${data.icon_code}"]`);
                    if (iconOption) {
                        iconOption.classList.add('selected');
                        selectedIconCode.value = data.icon_code;
                    }
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
                featureModalTitle.textContent = isChecklistMode ? 'Ajouter un élément' : 'Ajouter un indicateur';
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

    // =====================================================
    // SERVICES MODAL FUNCTIONALITY
    // =====================================================

    const serviceModal = document.getElementById('serviceModal');
    const addServiceBtn = document.getElementById('addServiceBtn');
    const serviceModalClose = document.getElementById('serviceModalClose');
    const serviceModalCancel = document.getElementById('serviceModalCancel');
    const serviceForm = document.getElementById('serviceForm');
    const serviceModalTitle = document.getElementById('serviceModalTitle');
    const serviceAction = document.getElementById('serviceAction');
    const serviceId = document.getElementById('serviceId');
    const serviceLabel = document.getElementById('serviceLabel');
    const serviceDescription = document.getElementById('serviceDescription');
    const serviceSelectedIconCode = document.getElementById('serviceSelectedIconCode');
    const serviceSubmitBtn = document.getElementById('serviceSubmitBtn');
    const serviceActiveToggle = document.getElementById('serviceActiveToggle');
    const serviceIsActive = document.getElementById('serviceIsActive');
    const serviceLabelEn = document.getElementById('serviceLabelEn');
    const serviceLabelEs = document.getElementById('serviceLabelEs');
    const serviceLabelIt = document.getElementById('serviceLabelIt');
    const serviceDescriptionEn = document.getElementById('serviceDescriptionEn');
    const serviceDescriptionEs = document.getElementById('serviceDescriptionEs');
    const serviceDescriptionIt = document.getElementById('serviceDescriptionIt');
    const serviceTransToggle = document.getElementById('serviceTransToggle');
    const serviceTransContent = document.getElementById('serviceTransContent');

    if (serviceModal) {
        // Icon selection for services
        document.querySelectorAll('.service-icon-option').forEach(option => {
            option.addEventListener('click', () => {
                document.querySelectorAll('.service-icon-option.selected').forEach(el => el.classList.remove('selected'));
                option.classList.add('selected');
                serviceSelectedIconCode.value = option.dataset.icon;
            });
        });

        // Open modal for new service
        addServiceBtn?.addEventListener('click', () => openServiceModal());

        // Edit service buttons
        document.querySelectorAll('.edit-service-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const data = JSON.parse(btn.dataset.service);
                openServiceModal(data);
            });
        });

        // Close modal
        serviceModalClose?.addEventListener('click', closeServiceModal);
        serviceModalCancel?.addEventListener('click', closeServiceModal);
        serviceModal.addEventListener('click', (e) => {
            if (e.target === serviceModal) closeServiceModal();
        });

        // Translations toggle
        serviceTransToggle?.addEventListener('click', () => {
            serviceTransToggle.classList.toggle('collapsed');
            serviceTransContent.classList.toggle('hidden');
        });

        function openServiceModal(data = null) {
            // Reset form
            serviceForm.reset();
            document.querySelectorAll('.service-icon-option.selected').forEach(el => el.classList.remove('selected'));
            serviceSelectedIconCode.value = '';

            if (data) {
                serviceModalTitle.textContent = 'Modifier le service';
                serviceAction.value = 'update_service';
                serviceId.value = data.id;
                serviceSubmitBtn.textContent = 'Enregistrer';
                serviceActiveToggle.style.display = 'flex';

                // Fill form
                serviceLabel.value = data.label;
                serviceDescription.value = data.description || '';
                serviceIsActive.checked = data.is_active == 1;

                // Select icon
                const iconOption = document.querySelector(`.service-icon-option[data-icon="${data.icon_code}"]`);
                if (iconOption) {
                    iconOption.classList.add('selected');
                    serviceSelectedIconCode.value = data.icon_code;
                }

                // Fill translations
                if (data.translations) {
                    serviceLabelEn.value = data.translations.en?.label || '';
                    serviceDescriptionEn.value = data.translations.en?.description || '';
                    serviceLabelEs.value = data.translations.es?.label || '';
                    serviceDescriptionEs.value = data.translations.es?.description || '';
                    serviceLabelIt.value = data.translations.it?.label || '';
                    serviceDescriptionIt.value = data.translations.it?.description || '';
                }
            } else {
                serviceModalTitle.textContent = 'Ajouter un service';
                serviceAction.value = 'create_service';
                serviceId.value = '';
                serviceSubmitBtn.textContent = 'Ajouter';
                serviceActiveToggle.style.display = 'none';
            }

            serviceModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeServiceModal() {
            serviceModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Services drag and drop reordering
    const servicesList = document.getElementById('servicesList');
    if (servicesList) {
        let draggedService = null;

        servicesList.querySelectorAll('.service-item').forEach(item => {
            const handle = item.querySelector('.service-drag-handle');

            handle.addEventListener('mousedown', () => {
                item.draggable = true;
            });

            item.addEventListener('dragstart', () => {
                draggedService = item;
                item.style.opacity = '0.5';
            });

            item.addEventListener('dragend', () => {
                item.draggable = false;
                item.style.opacity = '';
                draggedService = null;
                saveServicesOrder();
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (draggedService && draggedService !== item) {
                    const rect = item.getBoundingClientRect();
                    const midpoint = rect.top + rect.height / 2;
                    if (e.clientY < midpoint) {
                        servicesList.insertBefore(draggedService, item);
                    } else {
                        servicesList.insertBefore(draggedService, item.nextSibling);
                    }
                }
            });
        });

        function saveServicesOrder() {
            const serviceIds = Array.from(servicesList.querySelectorAll('.service-item'))
                .map(item => item.dataset.serviceId);

            const sectionCode = servicesList.dataset.section;

            fetch('content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reorder_services&csrf_token=<?= h($csrfToken) ?>&section_code=${encodeURIComponent(sectionCode)}&service_ids=${encodeURIComponent(JSON.stringify(serviceIds))}`
            });
        }
    }

    // =====================================================
    // GALLERY MODAL FUNCTIONALITY
    // =====================================================

    const galleryModal = document.getElementById('galleryModal');
    const addGalleryBtn = document.getElementById('addGalleryBtn');
    const galleryModalClose = document.getElementById('galleryModalClose');
    const galleryModalCancel = document.getElementById('galleryModalCancel');
    const galleryForm = document.getElementById('galleryForm');
    const galleryModalTitle = document.getElementById('galleryModalTitle');
    const galleryAction = document.getElementById('galleryAction');
    const galleryItemId = document.getElementById('galleryItemId');
    const galleryTitle = document.getElementById('galleryTitle');
    const galleryDescription = document.getElementById('galleryDescription');
    const gallerySubmitBtn = document.getElementById('gallerySubmitBtn');
    const galleryActiveToggle = document.getElementById('galleryActiveToggle');
    const galleryIsActive = document.getElementById('galleryIsActive');
    const galleryTransToggle = document.getElementById('galleryTransToggle');
    const galleryTransContent = document.getElementById('galleryTransContent');
    const galleryImageInput = document.getElementById('galleryImageInput');
    const galleryImagePreview = document.getElementById('galleryImagePreview');
    const galleryPreviewImg = document.getElementById('galleryPreviewImg');
    const galleryImagePlaceholder = document.getElementById('galleryImagePlaceholder');
    const galleryExistingImage = document.getElementById('galleryExistingImage');

    if (galleryModal) {
        // Toggle translations panel
        let galleryTransOpen = false;
        galleryTransToggle?.addEventListener('click', () => {
            galleryTransOpen = !galleryTransOpen;
            galleryTransContent.style.display = galleryTransOpen ? 'grid' : 'none';
            galleryTransToggle.querySelector('svg').style.transform = galleryTransOpen ? 'rotate(180deg)' : '';
        });
        if (galleryTransContent) galleryTransContent.style.display = 'none';

        // Image upload handling
        galleryImagePreview?.addEventListener('click', () => {
            galleryImageInput?.click();
        });

        galleryImageInput?.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    galleryPreviewImg.src = e.target.result;
                    galleryPreviewImg.style.display = 'block';
                    galleryImagePlaceholder.style.display = 'none';
                    galleryImagePreview.classList.add('has-image');
                };
                reader.readAsDataURL(file);
            }
        });

        // Add new gallery item
        addGalleryBtn?.addEventListener('click', () => openGalleryModal());

        // Edit gallery item
        document.querySelectorAll('.edit-gallery-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const data = JSON.parse(btn.dataset.gallery);
                openGalleryModal(data);
            });
        });

        // Close modal
        galleryModalClose?.addEventListener('click', closeGalleryModal);
        galleryModalCancel?.addEventListener('click', closeGalleryModal);
        galleryModal.addEventListener('click', (e) => {
            if (e.target === galleryModal) closeGalleryModal();
        });

        function openGalleryModal(data = null) {
            galleryForm.reset();

            // Reset image preview
            galleryPreviewImg.style.display = 'none';
            galleryImagePlaceholder.style.display = 'block';
            galleryImagePreview.classList.remove('has-image');
            galleryExistingImage.value = '';

            if (data) {
                // Edit mode
                galleryAction.value = 'update_gallery_item';
                galleryItemId.value = data.id;
                galleryTitle.value = data.title;
                galleryDescription.value = data.description || '';
                galleryModalTitle.textContent = 'Modifier l\'élément';
                gallerySubmitBtn.textContent = 'Enregistrer';
                galleryActiveToggle.style.display = 'flex';
                galleryIsActive.checked = data.is_active == 1;

                // Set existing image
                if (data.image_filename) {
                    galleryExistingImage.value = data.image_filename;
                    galleryPreviewImg.src = '../' + data.image_filename;
                    galleryPreviewImg.style.display = 'block';
                    galleryImagePlaceholder.style.display = 'none';
                    galleryImagePreview.classList.add('has-image');
                }

                // Fill translations
                if (data.translations) {
                    ['en', 'es', 'it'].forEach(lang => {
                        const trans = data.translations[lang];
                        if (trans) {
                            document.getElementById('galleryTitle' + lang.charAt(0).toUpperCase() + lang.slice(1)).value = trans.title || '';
                            document.getElementById('galleryDescription' + lang.charAt(0).toUpperCase() + lang.slice(1)).value = trans.description || '';
                        }
                    });
                }
            } else {
                // Add mode
                galleryAction.value = 'create_gallery_item';
                galleryItemId.value = '';
                galleryModalTitle.textContent = 'Ajouter un élément';
                gallerySubmitBtn.textContent = 'Ajouter';
                galleryActiveToggle.style.display = 'none';
            }

            galleryModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeGalleryModal() {
            galleryModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Gallery drag and drop reordering
    const galleryList = document.getElementById('galleryList');
    if (galleryList) {
        let draggedGallery = null;

        galleryList.querySelectorAll('.gallery-item').forEach(item => {
            const handle = item.querySelector('.gallery-item-drag');

            handle.addEventListener('mousedown', () => {
                item.draggable = true;
            });

            item.addEventListener('dragstart', () => {
                draggedGallery = item;
                item.style.opacity = '0.5';
            });

            item.addEventListener('dragend', () => {
                item.draggable = false;
                item.style.opacity = '';
                draggedGallery = null;
                saveGalleryOrder();
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (draggedGallery && draggedGallery !== item) {
                    const rect = item.getBoundingClientRect();
                    const midX = rect.left + rect.width / 2;
                    if (e.clientX < midX) {
                        galleryList.insertBefore(draggedGallery, item);
                    } else {
                        galleryList.insertBefore(draggedGallery, item.nextSibling);
                    }
                }
            });
        });

        function saveGalleryOrder() {
            const galleryIds = Array.from(galleryList.querySelectorAll('.gallery-item'))
                .map(item => item.dataset.galleryId);

            const sectionCode = galleryList.dataset.section;

            fetch('content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reorder_gallery_items&csrf_token=<?= h($csrfToken) ?>&section_code=${encodeURIComponent(sectionCode)}&gallery_item_ids=${encodeURIComponent(JSON.stringify(galleryIds))}`
            });
        }
    }

    // =====================================================
    // DYNAMIC SECTIONS FUNCTIONALITY
    // =====================================================

    const dynamicSectionModal = document.getElementById('dynamicSectionModal');
    const dynamicSectionForm = document.getElementById('dynamicSectionForm');
    const dynamicSectionPage = document.getElementById('dynamicSectionPage');
    const dynamicSectionModalClose = document.getElementById('dynamicSectionModalClose');
    const dynamicSectionModalCancel = document.getElementById('dynamicSectionModalCancel');
    const templateCodeSelect = document.getElementById('templateCode');
    const templateDescription = document.getElementById('templateDescription');

    if (dynamicSectionModal) {
        // Open modal when clicking "Nouvelle section" buttons
        document.querySelectorAll('.add-section-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const page = btn.dataset.page;
                dynamicSectionPage.value = page;
                document.getElementById('sectionName').value = '';
                dynamicSectionModal.classList.add('active');
                document.body.style.overflow = 'hidden';

                // Initialize the preview based on current selection
                updateSectionPreview(templateCodeSelect?.value);
            });
        });

        // Function to update the section preview
        function updateSectionPreview(code) {
            const previewMock = document.getElementById('sectionPreviewMock');
            if (!previewMock || !code) return;

            // Hide all previews
            previewMock.querySelectorAll('.preview-template').forEach(preview => {
                preview.style.display = 'none';
            });

            // Show the selected preview
            const selectedPreview = previewMock.querySelector(`[data-template="${code}"]`);
            if (selectedPreview) {
                selectedPreview.style.display = 'block';
            }
        }

        // Close modal
        dynamicSectionModalClose?.addEventListener('click', closeDynamicSectionModal);
        dynamicSectionModalCancel?.addEventListener('click', closeDynamicSectionModal);
        dynamicSectionModal.addEventListener('click', (e) => {
            if (e.target === dynamicSectionModal) closeDynamicSectionModal();
        });

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && dynamicSectionModal.classList.contains('active')) {
                closeDynamicSectionModal();
            }
        });

        // Update template description and preview when selection changes
        templateCodeSelect?.addEventListener('change', () => {
            const code = templateCodeSelect.value;
            if (templateDescriptions && templateDescriptions[code]) {
                templateDescription.textContent = templateDescriptions[code];
            }

            // Update the visual preview with animation
            const previewMock = document.getElementById('sectionPreviewMock');
            if (previewMock) {
                previewMock.classList.add('switching');
                setTimeout(() => {
                    updateSectionPreview(code);
                    setTimeout(() => {
                        previewMock.classList.remove('switching');
                    }, 50);
                }, 150);
            }
        });

        function closeDynamicSectionModal() {
            dynamicSectionModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Dynamic sections drag and drop reordering
    document.querySelectorAll('.dynamic-sections-sortable').forEach(container => {
        const page = container.dataset.page;
        let draggedItem = null;

        container.querySelectorAll('.section-btn-wrapper').forEach(wrapper => {
            const handle = wrapper.querySelector('.section-drag-handle');

            // Enable drag only when using the handle
            handle.addEventListener('mousedown', () => {
                wrapper.draggable = true;
            });

            wrapper.addEventListener('dragstart', (e) => {
                draggedItem = wrapper;
                wrapper.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            wrapper.addEventListener('dragend', () => {
                wrapper.draggable = false;
                wrapper.classList.remove('dragging');
                draggedItem = null;
                saveDynamicSectionsOrder(container, page);
            });

            wrapper.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (draggedItem && draggedItem !== wrapper) {
                    const rect = wrapper.getBoundingClientRect();
                    const midX = rect.left + rect.width / 2;
                    if (e.clientX < midX) {
                        container.insertBefore(draggedItem, wrapper);
                    } else {
                        container.insertBefore(draggedItem, wrapper.nextSibling);
                    }
                }
            });
        });
    });

    function saveDynamicSectionsOrder(container, page) {
        const sectionCodes = Array.from(container.querySelectorAll('.section-btn-wrapper'))
            .map(wrapper => wrapper.dataset.sectionCode);

        fetch('content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=reorder_dynamic_sections&csrf_token=<?= h($csrfToken) ?>&page=${encodeURIComponent(page)}&section_codes=${encodeURIComponent(JSON.stringify(sectionCodes))}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to save section order');
            }
        })
        .catch(error => {
            console.error('Error saving section order:', error);
        });
    }
    </script>
</body>
</html>
