<?php
/**
 * Hotel Rooms Management
 * Hotel Corintel - CRUD for rooms
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$admin = getCurrentAdmin();
$unreadMessages = getUnreadMessagesCount();
$pendingOrders = getPendingOrdersCount();
$csrfToken = generateCsrfToken();
$hotelName = getHotelName();

// Handle POST requests
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expirée. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create':
                $roomNumber = trim($_POST['room_number'] ?? '');
                $floor = isset($_POST['floor']) && $_POST['floor'] !== '' ? intval($_POST['floor']) : null;
                $roomType = $_POST['room_type'] ?? ROOM_TYPE_DOUBLE;
                $capacity = intval($_POST['capacity'] ?? 2);
                $bedCount = intval($_POST['bed_count'] ?? 1);
                $surfaceArea = isset($_POST['surface_area']) && $_POST['surface_area'] !== '' ? floatval($_POST['surface_area']) : null;
                $status = $_POST['status'] ?? ROOM_STATUS_AVAILABLE;
                $housekeepingStatus = $_POST['housekeeping_status'] ?? HOUSEKEEPING_CLEANED;
                $notes = trim($_POST['notes'] ?? '');

                // Parse amenities
                $amenities = [];
                if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
                    foreach ($_POST['amenities'] as $amenity) {
                        $amenities[$amenity] = true;
                    }
                }

                // Validation
                if (empty($roomNumber)) {
                    $message = 'Le numéro de chambre est obligatoire.';
                    $messageType = 'error';
                } elseif (roomNumberExists($roomNumber)) {
                    $message = 'Ce numéro de chambre existe déjà.';
                    $messageType = 'error';
                } elseif (!in_array($roomType, array_keys(getRoomTypes()))) {
                    $message = 'Type de chambre invalide.';
                    $messageType = 'error';
                } else {
                    $createError = null;
                    $roomId = createRoom([
                        'room_number' => $roomNumber,
                        'floor' => $floor,
                        'room_type' => $roomType,
                        'capacity' => $capacity,
                        'bed_count' => $bedCount,
                        'surface_area' => $surfaceArea,
                        'status' => $status,
                        'housekeeping_status' => $housekeepingStatus,
                        'amenities' => $amenities,
                        'notes' => $notes,
                        'is_active' => 1
                    ], $createError);

                    if ($roomId) {
                        $message = 'Chambre créée avec succès.';
                        $messageType = 'success';
                    } else {
                        $message = $createError ?: 'Erreur lors de la création de la chambre.';
                        $messageType = 'error';
                    }
                }
                break;

            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $roomNumber = trim($_POST['room_number'] ?? '');
                $floor = isset($_POST['floor']) && $_POST['floor'] !== '' ? intval($_POST['floor']) : null;
                $roomType = $_POST['room_type'] ?? ROOM_TYPE_DOUBLE;
                $capacity = intval($_POST['capacity'] ?? 2);
                $bedCount = intval($_POST['bed_count'] ?? 1);
                $surfaceArea = isset($_POST['surface_area']) && $_POST['surface_area'] !== '' ? floatval($_POST['surface_area']) : null;
                $status = $_POST['status'] ?? ROOM_STATUS_AVAILABLE;
                $housekeepingStatus = $_POST['housekeeping_status'] ?? HOUSEKEEPING_CLEANED;
                $notes = trim($_POST['notes'] ?? '');
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                // Parse amenities
                $amenities = [];
                if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
                    foreach ($_POST['amenities'] as $amenity) {
                        $amenities[$amenity] = true;
                    }
                }

                // Validation
                if (empty($roomNumber)) {
                    $message = 'Le numéro de chambre est obligatoire.';
                    $messageType = 'error';
                } elseif (roomNumberExists($roomNumber, $id)) {
                    $message = 'Ce numéro de chambre existe déjà.';
                    $messageType = 'error';
                } else {
                    $success = updateRoom($id, [
                        'room_number' => $roomNumber,
                        'floor' => $floor,
                        'room_type' => $roomType,
                        'capacity' => $capacity,
                        'bed_count' => $bedCount,
                        'surface_area' => $surfaceArea,
                        'status' => $status,
                        'housekeeping_status' => $housekeepingStatus,
                        'amenities' => $amenities,
                        'notes' => $notes,
                        'is_active' => $isActive
                    ]);

                    if ($success) {
                        $message = 'Chambre mise à jour.';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de la mise à jour.';
                        $messageType = 'error';
                    }
                }
                break;

            case 'update_status':
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? '';

                if (updateRoomStatus($id, $status)) {
                    $message = 'Statut mis à jour.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de la mise à jour du statut.';
                    $messageType = 'error';
                }
                break;

            case 'update_housekeeping':
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['housekeeping_status'] ?? '';

                if (updateRoomHousekeepingStatus($id, $status)) {
                    $message = 'Statut ménage mis à jour.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                $hardDelete = isset($_POST['hard_delete']) && $_POST['hard_delete'] === '1';

                if (deleteRoom($id, $hardDelete)) {
                    $message = $hardDelete ? 'Chambre supprimée définitivement.' : 'Chambre désactivée.';
                    $messageType = 'success';
                } else {
                    $message = 'Erreur lors de la suppression.';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get filters from query string
$filters = [];
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['housekeeping']) && $_GET['housekeeping'] !== '') {
    $filters['housekeeping_status'] = $_GET['housekeeping'];
}
if (isset($_GET['floor']) && $_GET['floor'] !== '') {
    $filters['floor'] = intval($_GET['floor']);
}
if (isset($_GET['type']) && $_GET['type'] !== '') {
    $filters['room_type'] = $_GET['type'];
}
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['show_inactive'])) {
    $filters['is_active'] = null; // Show all
}

// Get rooms
$rooms = getRooms($filters);
$stats = getRoomStatistics();
$floors = getRoomFloors();
$roomStatuses = getRoomStatuses();
$housekeepingStatuses = getHousekeepingStatuses();
$roomTypes = getRoomTypes();

// Available amenities list
$availableAmenities = [
    'wifi' => 'WiFi',
    'tv' => 'Télévision',
    'minibar' => 'Minibar',
    'safe' => 'Coffre-fort',
    'air_conditioning' => 'Climatisation',
    'balcony' => 'Balcon',
    'bathtub' => 'Baignoire',
    'shower' => 'Douche',
    'hairdryer' => 'Sèche-cheveux',
    'desk' => 'Bureau',
    'iron' => 'Fer à repasser',
    'coffee_maker' => 'Machine à café'
];

// Edit mode
$editRoom = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editRoom = getRoomById(intval($_GET['edit']));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Chambres - <?= h($hotelName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .room-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--admin-primary);
        }
        .stat-card .stat-label {
            font-size: 0.75rem;
            color: var(--admin-text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
        }
        .filters-bar select,
        .filters-bar input {
            padding: 0.5rem;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .badge-success { background: #48BB78; color: white; }
        .badge-info { background: #4299E1; color: white; }
        .badge-warning { background: #ED8936; color: white; }
        .badge-error { background: #E53E3E; color: white; }
        .badge-primary { background: var(--admin-primary); color: white; }
        .badge-default { background: #A0AEC0; color: white; }
        .room-form {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.5rem;
        }
        .amenity-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .amenity-checkbox input {
            width: auto;
        }
        .room-table {
            width: 100%;
            border-collapse: collapse;
        }
        .room-table th,
        .room-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--admin-border);
        }
        .room-table th {
            background: var(--admin-bg);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .room-table tr:hover {
            background: var(--admin-bg);
        }
        .room-number {
            font-weight: 600;
            font-size: 1rem;
        }
        .status-select {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 4px;
            border: 1px solid var(--admin-border);
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .btn-icon {
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-icon svg {
            width: 16px;
            height: 16px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .section-header h2 {
            margin: 0;
            font-size: 1.25rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--admin-text-light);
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--admin-border);
        }
        /* QR Code Modal */
        .qr-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .qr-modal-overlay.active {
            display: flex;
        }
        .qr-modal {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .qr-modal h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            color: var(--admin-text);
        }
        .qr-modal .room-label {
            font-size: 0.875rem;
            color: var(--admin-text-light);
            margin-bottom: 1.5rem;
        }
        .qr-code-container {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .qr-code-container canvas {
            display: block;
        }
        .qr-url {
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            padding: 0.75rem;
            font-size: 0.75rem;
            word-break: break-all;
            margin-bottom: 1rem;
            font-family: monospace;
        }
        .qr-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .qr-actions .btn {
            flex: 1;
            min-width: 100px;
        }
        .btn-qr {
            background: #805AD5;
            color: white;
        }
        .btn-qr:hover {
            background: #6B46C1;
        }
        /* QR Actions Bar */
        .qr-actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #805AD5 0%, #6B46C1 100%);
            border-radius: 8px;
            margin-bottom: 1.5rem;
            color: white;
        }
        .qr-actions-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        .qr-actions-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .qr-actions-bar .btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
        }
        .qr-actions-bar .btn:hover {
            background: rgba(255,255,255,0.3);
        }
        /* Enhanced QR Modal */
        .qr-modal {
            background: white;
            border-radius: 16px;
            padding: 0;
            max-width: 480px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
        }
        .qr-modal-header {
            background: linear-gradient(135deg, #8B6F47 0%, #6B5635 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
            border-radius: 16px 16px 0 0;
        }
        .qr-modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-family: 'Cormorant Garamond', serif;
        }
        .qr-modal-header p {
            margin: 0.25rem 0 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }
        .qr-modal-body {
            padding: 1.5rem;
        }
        .qr-preview {
            background: #FAF6F0;
            border: 2px solid #8B6F47;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .qr-preview .hotel-brand {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1rem;
            color: #8B6F47;
            margin-bottom: 0.5rem;
        }
        .qr-preview .room-label {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }
        .qr-code-wrapper {
            display: inline-block;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .qr-preview .scan-instruction {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #666;
        }
        .qr-url-display {
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            padding: 0.75rem;
            font-size: 0.7rem;
            word-break: break-all;
            font-family: monospace;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .qr-url-display code {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .qr-url-display button {
            flex-shrink: 0;
        }
        /* Download Section */
        .qr-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--admin-text-light);
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        .qr-download-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }
        .qr-download-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        .qr-download-btn:hover {
            border-color: var(--admin-primary);
            background: var(--admin-bg);
        }
        .qr-download-btn svg {
            width: 18px;
            height: 18px;
            color: var(--admin-primary);
        }
        /* Print Formats */
        .qr-print-formats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }
        .qr-format-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 0.5rem;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.75rem;
            text-align: center;
        }
        .qr-format-btn:hover {
            border-color: #805AD5;
            background: #F7F5FF;
        }
        .qr-format-btn svg {
            width: 32px;
            height: 32px;
            color: #805AD5;
        }
        .qr-format-btn span {
            font-weight: 500;
        }
        .qr-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--admin-border);
            display: flex;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

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

                <div class="nav-separator">Activité</div>
                <a href="room-service-orders.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    Commandes
                    <?php if ($pendingOrders > 0): ?>
                        <span class="badge" style="background: #E53E3E; color: white; margin-left: auto;"><?= $pendingOrders ?></span>
                    <?php endif; ?>
                </a>
                <a href="room-service-messages.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge" style="background: #E53E3E; color: white; margin-left: auto;"><?= $unreadMessages ?></span>
                    <?php endif; ?>
                </a>

                <div class="nav-separator">Room Service</div>
                <a href="room-service-categories.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/>
                        <line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                    Catégories
                </a>
                <a href="room-service-items.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                        <line x1="6" y1="1" x2="6" y2="4"/>
                        <line x1="10" y1="1" x2="10" y2="4"/>
                        <line x1="14" y1="1" x2="14" y2="4"/>
                    </svg>
                    Articles
                </a>
                <a href="room-service-stats.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Statistiques
                </a>

                <div class="nav-separator">Contenu</div>
                <a href="content.php?tab=general" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Général
                </a>
                <a href="content.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="9" y1="21" x2="9" y2="9"/>
                    </svg>
                    Sections
                </a>
                <a href="theme.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a10 10 0 0 0 0 20"/>
                        <path d="M12 2c-2.5 2.5-4 6-4 10s1.5 7.5 4 10"/>
                    </svg>
                    Thème
                </a>

                <div class="nav-separator">Administration</div>
                <a href="rooms.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <rect x="9" y="13" width="6" height="9"/>
                    </svg>
                    Chambres
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

        <main class="admin-main">
            <header class="admin-header">
                <button class="menu-toggle" id="menuToggle" aria-label="Menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <h1>Gestion des Chambres</h1>
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

                <!-- Statistics -->
                <div class="room-stats">
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total'] ?></div>
                        <div class="stat-label">Total Chambres</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: #48BB78;"><?= $stats['by_status'][ROOM_STATUS_AVAILABLE] ?? 0 ?></div>
                        <div class="stat-label">Disponibles</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: #4299E1;"><?= $stats['by_status'][ROOM_STATUS_OCCUPIED] ?? 0 ?></div>
                        <div class="stat-label">Occupées</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: #ED8936;"><?= $stats['by_status'][ROOM_STATUS_MAINTENANCE] ?? 0 ?></div>
                        <div class="stat-label">Maintenance</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: #ED8936;"><?= $stats['by_housekeeping'][HOUSEKEEPING_PENDING] ?? 0 ?></div>
                        <div class="stat-label">Ménage en attente</div>
                    </div>
                </div>

                <!-- Add/Edit Form -->
                <div class="room-form">
                    <h3><?= $editRoom ? 'Modifier la chambre' : 'Ajouter une chambre' ?></h3>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        <input type="hidden" name="action" value="<?= $editRoom ? 'update' : 'create' ?>">
                        <?php if ($editRoom): ?>
                            <input type="hidden" name="id" value="<?= $editRoom['id'] ?>">
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="room_number">Numéro de chambre *</label>
                                <input type="text" id="room_number" name="room_number" required
                                       value="<?= h($editRoom['room_number'] ?? '') ?>"
                                       placeholder="ex: 101, 201A, Suite-1">
                            </div>

                            <div class="form-group">
                                <label for="floor">Étage</label>
                                <input type="number" id="floor" name="floor" min="0" max="99"
                                       value="<?= h($editRoom['floor'] ?? '') ?>"
                                       placeholder="ex: 1, 2, 3">
                            </div>

                            <div class="form-group">
                                <label for="room_type">Type de chambre</label>
                                <select id="room_type" name="room_type">
                                    <?php foreach ($roomTypes as $type => $label): ?>
                                        <option value="<?= $type ?>" <?= ($editRoom['room_type'] ?? ROOM_TYPE_DOUBLE) === $type ? 'selected' : '' ?>>
                                            <?= h($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="capacity">Capacité (personnes)</label>
                                <input type="number" id="capacity" name="capacity" min="1" max="10"
                                       value="<?= h($editRoom['capacity'] ?? 2) ?>">
                            </div>

                            <div class="form-group">
                                <label for="bed_count">Nombre de lits</label>
                                <input type="number" id="bed_count" name="bed_count" min="1" max="10"
                                       value="<?= h($editRoom['bed_count'] ?? 1) ?>">
                            </div>

                            <div class="form-group">
                                <label for="surface_area">Surface (m²)</label>
                                <input type="number" id="surface_area" name="surface_area" min="0" step="0.1"
                                       value="<?= h($editRoom['surface_area'] ?? '') ?>"
                                       placeholder="ex: 25.5">
                            </div>

                            <div class="form-group">
                                <label for="status">Statut</label>
                                <select id="status" name="status">
                                    <?php foreach ($roomStatuses as $status => $label): ?>
                                        <option value="<?= $status ?>" <?= ($editRoom['status'] ?? ROOM_STATUS_AVAILABLE) === $status ? 'selected' : '' ?>>
                                            <?= h($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="housekeeping_status">Statut ménage</label>
                                <select id="housekeeping_status" name="housekeeping_status">
                                    <?php foreach ($housekeepingStatuses as $status => $label): ?>
                                        <option value="<?= $status ?>" <?= ($editRoom['housekeeping_status'] ?? HOUSEKEEPING_CLEANED) === $status ? 'selected' : '' ?>>
                                            <?= h($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Équipements</label>
                            <div class="amenities-grid">
                                <?php
                                $currentAmenities = $editRoom['amenities'] ?? [];
                                foreach ($availableAmenities as $key => $label):
                                ?>
                                    <label class="amenity-checkbox">
                                        <input type="checkbox" name="amenities[]" value="<?= $key ?>"
                                               <?= isset($currentAmenities[$key]) && $currentAmenities[$key] ? 'checked' : '' ?>>
                                        <?= h($label) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes internes</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Notes visibles uniquement par l'administration..."><?= h($editRoom['notes'] ?? '') ?></textarea>
                        </div>

                        <?php if ($editRoom): ?>
                            <div class="form-group">
                                <label class="amenity-checkbox">
                                    <input type="checkbox" name="is_active" value="1"
                                           <?= ($editRoom['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    Chambre active
                                </label>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?= $editRoom ? 'Mettre à jour' : 'Ajouter la chambre' ?>
                            </button>
                            <?php if ($editRoom): ?>
                                <a href="rooms.php" class="btn btn-outline">Annuler</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Filters -->
                <div class="filters-bar">
                    <form method="GET" action="" style="display: contents;">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <?php foreach ($roomStatuses as $status => $label): ?>
                                <option value="<?= $status ?>" <?= ($_GET['status'] ?? '') === $status ? 'selected' : '' ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="housekeeping" onchange="this.form.submit()">
                            <option value="">Tous les états ménage</option>
                            <?php foreach ($housekeepingStatuses as $status => $label): ?>
                                <option value="<?= $status ?>" <?= ($_GET['housekeeping'] ?? '') === $status ? 'selected' : '' ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="floor" onchange="this.form.submit()">
                            <option value="">Tous les étages</option>
                            <?php foreach ($floors as $floor): ?>
                                <option value="<?= $floor ?>" <?= (isset($_GET['floor']) && $_GET['floor'] !== '' && intval($_GET['floor']) === intval($floor)) ? 'selected' : '' ?>>
                                    Étage <?= $floor ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="type" onchange="this.form.submit()">
                            <option value="">Tous les types</option>
                            <?php foreach ($roomTypes as $type => $label): ?>
                                <option value="<?= $type ?>" <?= ($_GET['type'] ?? '') === $type ? 'selected' : '' ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="text" name="search" placeholder="Rechercher..."
                               value="<?= h($_GET['search'] ?? '') ?>">

                        <button type="submit" class="btn btn-sm btn-outline">Filtrer</button>

                        <?php if (!empty($filters)): ?>
                            <a href="rooms.php" class="btn btn-sm btn-outline">Réinitialiser</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- QR Actions Bar -->
                <?php if (!empty($rooms)): ?>
                <div class="qr-actions-bar">
                    <span class="qr-actions-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/>
                        </svg>
                        QR Codes Room Service
                    </span>
                    <div class="qr-actions-buttons">
                        <button type="button" class="btn btn-sm btn-qr" onclick="printAllQrCodes('sticker')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                <rect x="6" y="14" width="12" height="8"/>
                            </svg>
                            Imprimer tous les QR
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Rooms List -->
                <div class="data-card">
                    <?php if (empty($rooms)): ?>
                        <div class="empty-state">
                            <p>Aucune chambre trouvée.</p>
                            <p>Ajoutez votre première chambre à l'aide du formulaire ci-dessus.</p>
                        </div>
                    <?php else: ?>
                        <table class="room-table">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Étage</th>
                                    <th>Type</th>
                                    <th>Capacité</th>
                                    <th>Statut</th>
                                    <th>Ménage</th>
                                    <th>Dernier scan</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td>
                                            <span class="room-number"><?= h($room['room_number']) ?></span>
                                        </td>
                                        <td><?= $room['floor'] !== null ? $room['floor'] : '-' ?></td>
                                        <td><?= h($roomTypes[$room['room_type']] ?? $room['room_type']) ?></td>
                                        <td><?= $room['capacity'] ?> pers. / <?= $room['bed_count'] ?> lit(s)</td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" value="<?= $room['id'] ?>">
                                                <select name="status" class="status-select <?= getRoomStatusBadgeClass($room['status']) ?>" onchange="this.form.submit()">
                                                    <?php foreach ($roomStatuses as $status => $label): ?>
                                                        <option value="<?= $status ?>" <?= $room['status'] === $status ? 'selected' : '' ?>>
                                                            <?= h($label) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                <input type="hidden" name="action" value="update_housekeeping">
                                                <input type="hidden" name="id" value="<?= $room['id'] ?>">
                                                <select name="housekeeping_status" class="status-select <?= getHousekeepingStatusBadgeClass($room['housekeeping_status']) ?>" onchange="this.form.submit()">
                                                    <?php foreach ($housekeepingStatuses as $status => $label): ?>
                                                        <option value="<?= $status ?>" <?= $room['housekeeping_status'] === $status ? 'selected' : '' ?>>
                                                            <?= h($label) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <?php if (!empty($room['last_scan_at'])): ?>
                                                <span title="<?= date('d/m/Y H:i', strtotime($room['last_scan_at'])) ?>" style="font-size: 0.8rem; color: var(--admin-text-light);">
                                                    <?= date('d/m H:i', strtotime($room['last_scan_at'])) ?>
                                                </span>
                                                <?php if (!empty($room['total_scans'])): ?>
                                                <br><span style="font-size: 0.7rem; color: #805AD5;"><?= $room['total_scans'] ?> scan(s)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="font-size: 0.8rem; color: var(--admin-text-light);">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-sm btn-qr btn-icon"
                                                        title="QR Code Room Service"
                                                        onclick="showQrCode(<?= $room['id'] ?>, '<?= h($room['room_number']) ?>', '<?= h(generateRoomServiceUrl($room['id'], $room['room_number'])) ?>')">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="3" y="3" width="7" height="7"/>
                                                        <rect x="14" y="3" width="7" height="7"/>
                                                        <rect x="3" y="14" width="7" height="7"/>
                                                        <rect x="14" y="14" width="3" height="3"/>
                                                        <rect x="18" y="14" width="3" height="3"/>
                                                        <rect x="14" y="18" width="3" height="3"/>
                                                        <rect x="18" y="18" width="3" height="3"/>
                                                    </svg>
                                                </button>
                                                <a href="?edit=<?= $room['id'] ?>" class="btn btn-sm btn-outline btn-icon" title="Modifier">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                    </svg>
                                                </a>
                                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir désactiver cette chambre ?');">
                                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $room['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline btn-icon" title="Désactiver" style="color: var(--admin-error);">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="3 6 5 6 21 6"/>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- QR Code Modal -->
    <div class="qr-modal-overlay" id="qrModalOverlay" onclick="closeQrModal(event)">
        <div class="qr-modal" onclick="event.stopPropagation()">
            <div class="qr-modal-header">
                <h3><?= h($hotelName) ?></h3>
                <p>QR Code Room Service - Chambre <span id="qrRoomNumber"></span></p>
            </div>
            <div class="qr-modal-body">
                <!-- QR Preview with Branding -->
                <div class="qr-preview" id="qrPreview">
                    <div class="hotel-brand"><?= h($hotelName) ?></div>
                    <div class="room-label">Chambre <span id="qrRoomLabel"></span></div>
                    <div class="qr-code-wrapper">
                        <div id="qrCodeContainer"></div>
                    </div>
                    <p class="scan-instruction">Scannez pour accéder au Room Service</p>
                </div>

                <!-- URL Display -->
                <div class="qr-url-display">
                    <code id="qrUrl"></code>
                    <button type="button" class="btn btn-sm btn-outline" onclick="copyQrUrl()" title="Copier">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                    </button>
                </div>

                <!-- Download Options -->
                <div class="qr-section-title">Télécharger</div>
                <div class="qr-download-grid">
                    <button type="button" class="qr-download-btn" onclick="downloadQrCode('png')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        PNG (Standard)
                    </button>
                    <button type="button" class="qr-download-btn" onclick="downloadQrCode('png-hd')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        PNG HD (Print)
                    </button>
                </div>

                <!-- Print Formats -->
                <div class="qr-section-title">Imprimer</div>
                <div class="qr-print-formats">
                    <button type="button" class="qr-format-btn" onclick="printQrCode('sticker')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="6" y="6" width="12" height="12" rx="2"/>
                            <rect x="9" y="9" width="6" height="6" fill="currentColor" opacity="0.3"/>
                        </svg>
                        <span>Sticker</span>
                        <small>5x5 cm</small>
                    </button>
                    <button type="button" class="qr-format-btn" onclick="printQrCode('tent')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 6h16v12H4z"/>
                            <path d="M4 12h16" stroke-dasharray="2 2"/>
                            <rect x="8" y="14" width="8" height="3" fill="currentColor" opacity="0.3"/>
                        </svg>
                        <span>Chevalet</span>
                        <small>Table tent</small>
                    </button>
                    <button type="button" class="qr-format-btn" onclick="printQrCode('poster')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <rect x="7" y="7" width="10" height="10" fill="currentColor" opacity="0.3"/>
                            <line x1="7" y1="19" x2="17" y2="19"/>
                        </svg>
                        <span>Affiche</span>
                        <small>A5 / A4</small>
                    </button>
                </div>
            </div>
            <div class="qr-modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeQrModal()">Fermer</button>
            </div>
        </div>
    </div>

    <!-- QRCode.js library (davidshimjs) -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <script>
        // Hotel configuration
        const hotelName = <?= json_encode($hotelName) ?>;
        const hotelColors = {
            primary: '#8B6F47',
            primaryDark: '#6B5635',
            accent: '#5C7C5E',
            cream: '#FAF6F0'
        };

        // All rooms data for batch printing
        const allRooms = <?= json_encode(array_map(function($r) {
            return [
                'id' => $r['id'],
                'room_number' => $r['room_number'],
                'url' => generateRoomServiceUrl($r['id'], $r['room_number'])
            ];
        }, $rooms)) ?>;

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('adminSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarClose = document.getElementById('sidebarClose');

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        menuToggle.addEventListener('click', openSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);
        sidebarClose.addEventListener('click', closeSidebar);

        // Auto-hide alerts
        document.querySelectorAll('.alert').forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });

        // QR Code State
        let currentQrUrl = '';
        let currentRoomNumber = '';
        let currentRoomId = '';

        // Show QR Code Modal
        function showQrCode(roomId, roomNumber, url) {
            currentQrUrl = url;
            currentRoomNumber = roomNumber;
            currentRoomId = roomId;

            document.getElementById('qrRoomNumber').textContent = roomNumber;
            document.getElementById('qrRoomLabel').textContent = roomNumber;
            document.getElementById('qrUrl').textContent = url;

            // Generate QR code
            const container = document.getElementById('qrCodeContainer');
            container.innerHTML = '';

            try {
                new QRCode(container, {
                    text: url,
                    width: 160,
                    height: 160,
                    colorDark: hotelColors.primary,
                    colorLight: '#FFFFFF',
                    correctLevel: QRCode.CorrectLevel.H
                });
            } catch (error) {
                console.error('QR Code error:', error);
                container.innerHTML = '<p style="color: red;">Erreur QR</p>';
            }

            document.getElementById('qrModalOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeQrModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('qrModalOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Copy URL
        function copyQrUrl() {
            navigator.clipboard.writeText(currentQrUrl).then(function() {
                const btn = event.target.closest('button');
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><polyline points="20 6 9 17 4 12"/></svg>';
                btn.style.background = '#48BB78';
                btn.style.color = 'white';
                setTimeout(() => {
                    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            }).catch(err => alert('Copie impossible: ' + currentQrUrl));
        }

        // Get QR Image Source
        function getQrImageSrc(containerId = 'qrCodeContainer') {
            const container = document.getElementById(containerId);
            const img = container.querySelector('img');
            if (img && img.src) return img.src;
            const canvas = container.querySelector('canvas');
            if (canvas) return canvas.toDataURL('image/png');
            return null;
        }

        // Generate high-res QR code
        function generateHDQrCode(url, size = 400) {
            return new Promise((resolve) => {
                const tempDiv = document.createElement('div');
                tempDiv.style.position = 'absolute';
                tempDiv.style.left = '-9999px';
                document.body.appendChild(tempDiv);

                new QRCode(tempDiv, {
                    text: url,
                    width: size,
                    height: size,
                    colorDark: hotelColors.primary,
                    colorLight: '#FFFFFF',
                    correctLevel: QRCode.CorrectLevel.H
                });

                setTimeout(() => {
                    const canvas = tempDiv.querySelector('canvas');
                    const img = tempDiv.querySelector('img');
                    const src = canvas ? canvas.toDataURL('image/png') : (img ? img.src : null);
                    document.body.removeChild(tempDiv);
                    resolve(src);
                }, 100);
            });
        }

        // Download QR Code
        async function downloadQrCode(format) {
            const size = format === 'png-hd' ? 800 : 400;
            const qrSrc = await generateHDQrCode(currentQrUrl, size);

            if (!qrSrc) {
                alert('Impossible de générer le QR code');
                return;
            }

            // Create branded QR image
            const canvas = document.createElement('canvas');
            const padding = Math.round(size * 0.15);
            canvas.width = size + padding * 2;
            canvas.height = size + padding * 2 + Math.round(size * 0.35);
            const ctx = canvas.getContext('2d');

            // Background
            ctx.fillStyle = hotelColors.cream;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Border
            ctx.strokeStyle = hotelColors.primary;
            ctx.lineWidth = 3;
            ctx.strokeRect(10, 10, canvas.width - 20, canvas.height - 20);

            // Hotel name
            ctx.fillStyle = hotelColors.primary;
            ctx.font = `${Math.round(size * 0.06)}px Georgia, serif`;
            ctx.textAlign = 'center';
            ctx.fillText(hotelName, canvas.width / 2, padding + Math.round(size * 0.08));

            // Room number
            ctx.fillStyle = '#333';
            ctx.font = `bold ${Math.round(size * 0.08)}px Georgia, serif`;
            ctx.fillText('Chambre ' + currentRoomNumber, canvas.width / 2, padding + Math.round(size * 0.18));

            // QR Code
            const qrImg = new Image();
            qrImg.onload = function() {
                ctx.drawImage(qrImg, padding, padding + Math.round(size * 0.22), size, size);

                // Instruction
                ctx.fillStyle = '#666';
                ctx.font = `${Math.round(size * 0.04)}px Arial, sans-serif`;
                ctx.fillText('Scannez pour accéder au Room Service', canvas.width / 2, canvas.height - padding);

                // Download
                const link = document.createElement('a');
                link.download = `qr-chambre-${currentRoomNumber}${format === 'png-hd' ? '-hd' : ''}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            };
            qrImg.src = qrSrc;
        }

        // Print QR Code with different formats
        function printQrCode(format) {
            const qrSrc = getQrImageSrc();
            if (!qrSrc) {
                alert('Impossible de générer l\'impression');
                return;
            }

            const printWindow = window.open('', '_blank');
            let html = '';

            if (format === 'sticker') {
                html = getStickerTemplate(qrSrc, currentRoomNumber);
            } else if (format === 'tent') {
                html = getTentCardTemplate(qrSrc, currentRoomNumber);
            } else if (format === 'poster') {
                html = getPosterTemplate(qrSrc, currentRoomNumber);
            }

            printWindow.document.write(html);
            printWindow.document.close();
        }

        // Sticker Template (5x5cm)
        function getStickerTemplate(qrSrc, roomNumber) {
            return `<!DOCTYPE html><html><head><title>Sticker - Chambre ${roomNumber}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                @page { size: 50mm 50mm; margin: 0; }
                body { width: 50mm; height: 50mm; display: flex; align-items: center; justify-content: center; font-family: Georgia, serif; }
                .sticker { width: 48mm; height: 48mm; border: 1px solid ${hotelColors.primary}; border-radius: 4mm; padding: 2mm; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; background: ${hotelColors.cream}; }
                .hotel { font-size: 6pt; color: ${hotelColors.primary}; margin-bottom: 1mm; }
                .room { font-size: 9pt; font-weight: bold; margin-bottom: 2mm; }
                .qr img { width: 28mm; height: 28mm; }
                .scan { font-size: 5pt; color: #666; margin-top: 1mm; }
            </style></head>
            <body><div class="sticker">
                <div class="hotel">${hotelName}</div>
                <div class="room">Chambre ${roomNumber}</div>
                <div class="qr"><img src="${qrSrc}"></div>
                <div class="scan">Scannez pour le Room Service</div>
            </div></body></html>`;
        }

        // Tent Card Template (folding table tent)
        function getTentCardTemplate(qrSrc, roomNumber) {
            return `<!DOCTYPE html><html><head><title>Chevalet - Chambre ${roomNumber}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                @page { size: A5 landscape; margin: 0; }
                body { font-family: Georgia, serif; }
                .tent { width: 210mm; height: 148mm; display: flex; }
                .side { width: 105mm; height: 148mm; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10mm; border: 1px dashed #ccc; }
                .side:first-child { transform: rotate(180deg); }
                .hotel { font-size: 14pt; color: ${hotelColors.primary}; letter-spacing: 2px; margin-bottom: 5mm; }
                .room { font-size: 24pt; font-weight: bold; color: #333; margin-bottom: 8mm; }
                .qr { padding: 5mm; background: white; border-radius: 3mm; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .qr img { width: 50mm; height: 50mm; }
                .scan { font-size: 10pt; color: #666; margin-top: 6mm; text-align: center; line-height: 1.4; }
                .fold-line { position: absolute; left: 50%; top: 0; bottom: 0; border-left: 1px dashed #aaa; }
                .fold-label { position: absolute; left: 50%; top: 5mm; transform: translateX(-50%); font-size: 7pt; color: #aaa; }
            </style></head>
            <body>
                <div class="fold-label">--- plier ici ---</div>
                <div class="fold-line"></div>
                <div class="tent">
                    <div class="side">
                        <div class="hotel">${hotelName}</div>
                        <div class="room">Chambre ${roomNumber}</div>
                        <div class="qr"><img src="${qrSrc}"></div>
                        <div class="scan">Scannez ce QR code<br>pour accéder au Room Service</div>
                    </div>
                    <div class="side">
                        <div class="hotel">${hotelName}</div>
                        <div class="room">Chambre ${roomNumber}</div>
                        <div class="qr"><img src="${qrSrc}"></div>
                        <div class="scan">Scannez ce QR code<br>pour accéder au Room Service</div>
                    </div>
                </div>
            <script>window.onload = function() { window.print(); }<\/script>
            </body></html>`;
        }

        // Poster Template (A5/A4)
        function getPosterTemplate(qrSrc, roomNumber) {
            return `<!DOCTYPE html><html><head><title>Affiche - Chambre ${roomNumber}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                @page { size: A5; margin: 0; }
                body { width: 148mm; height: 210mm; font-family: Georgia, serif; background: ${hotelColors.cream}; display: flex; align-items: center; justify-content: center; }
                .poster { width: 130mm; height: 190mm; border: 3px solid ${hotelColors.primary}; border-radius: 8mm; padding: 15mm; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; background: white; }
                .hotel { font-size: 16pt; color: ${hotelColors.primary}; letter-spacing: 3px; margin-bottom: 8mm; text-transform: uppercase; }
                .divider { width: 30mm; height: 1px; background: ${hotelColors.primary}; margin-bottom: 8mm; }
                .room { font-size: 32pt; font-weight: bold; color: #333; margin-bottom: 12mm; }
                .qr { padding: 8mm; background: white; border: 2px solid ${hotelColors.cream}; border-radius: 4mm; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 10mm; }
                .qr img { width: 70mm; height: 70mm; }
                .title { font-size: 14pt; color: ${hotelColors.primary}; font-weight: bold; margin-bottom: 4mm; }
                .scan { font-size: 11pt; color: #666; line-height: 1.5; }
                .footer { margin-top: auto; font-size: 8pt; color: #999; }
            </style></head>
            <body>
                <div class="poster">
                    <div class="hotel">${hotelName}</div>
                    <div class="divider"></div>
                    <div class="room">Chambre ${roomNumber}</div>
                    <div class="qr"><img src="${qrSrc}"></div>
                    <div class="title">Room Service</div>
                    <div class="scan">Scannez ce QR code avec votre téléphone<br>pour découvrir notre carte et passer commande</div>
                    <div class="footer">Service disponible 24h/24</div>
                </div>
            <script>window.onload = function() { window.print(); }<\/script>
            </body></html>`;
        }

        // Print All QR Codes
        async function printAllQrCodes(format) {
            if (allRooms.length === 0) {
                alert('Aucune chambre à imprimer');
                return;
            }

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`<!DOCTYPE html><html><head><title>Tous les QR Codes</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Georgia, serif; }
                .loading { text-align: center; padding: 50px; font-size: 18px; }
            </style></head><body><div class="loading">Génération des QR codes en cours...</div></body></html>`);

            // Generate all QR codes
            const qrCodes = [];
            for (const room of allRooms) {
                const qrSrc = await generateHDQrCode(room.url, 200);
                qrCodes.push({ ...room, qrSrc });
            }

            // Build print page based on format
            let html = '';
            if (format === 'sticker') {
                html = getAllStickersTemplate(qrCodes);
            } else {
                html = getAllStickersTemplate(qrCodes); // Default to stickers
            }

            printWindow.document.open();
            printWindow.document.write(html);
            printWindow.document.close();
        }

        // All Stickers Template (grid layout)
        function getAllStickersTemplate(qrCodes) {
            const stickers = qrCodes.map(qr => `
                <div class="sticker">
                    <div class="hotel">${hotelName}</div>
                    <div class="room">Ch. ${qr.room_number}</div>
                    <div class="qr"><img src="${qr.qrSrc}"></div>
                    <div class="scan">Room Service</div>
                </div>
            `).join('');

            return `<!DOCTYPE html><html><head><title>QR Codes - Toutes les chambres</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                @page { margin: 10mm; }
                body { font-family: Georgia, serif; }
                .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5mm; padding: 5mm; }
                .sticker { width: 45mm; height: 45mm; border: 1px solid ${hotelColors.primary}; border-radius: 3mm; padding: 2mm; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; background: ${hotelColors.cream}; page-break-inside: avoid; }
                .hotel { font-size: 5pt; color: ${hotelColors.primary}; margin-bottom: 1mm; }
                .room { font-size: 8pt; font-weight: bold; margin-bottom: 1mm; }
                .qr img { width: 25mm; height: 25mm; }
                .scan { font-size: 5pt; color: #666; margin-top: 1mm; }
            </style></head>
            <body>
                <div class="grid">${stickers}</div>
                <script>window.onload = function() { window.print(); }<\/script>
            </body></html>`;
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeQrModal();
        });
    </script>
</body>
</html>
