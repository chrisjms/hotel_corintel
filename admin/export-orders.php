<?php
/**
 * Room Service Orders Export
 * Generates CSV and PDF exports with filtering
 * Hotel Corintel
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$hotelName = getHotelName();
$contactInfo = getContactInfo();

// Get export format
$format = $_GET['format'] ?? 'csv';
if (!in_array($format, ['csv', 'pdf'])) {
    $format = 'csv';
}

// Get filters from query params
$statusFilter = $_GET['status'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'delivery_datetime';
$sortOrder = $_GET['order'] ?? 'DESC';
$deliveryDateFilter = $_GET['delivery_date'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

// Validate sort parameters
$validSortColumns = ['id', 'room_number', 'delivery_datetime', 'created_at', 'total_amount', 'status'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'delivery_datetime';
}
$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Fetch orders with filters
$orders = getOrdersForExport($statusFilter, $sortBy, $sortOrder, $deliveryDateFilter, $dateFrom, $dateTo);

// Get statuses and payment methods for display
$statuses = getRoomServiceOrderStatuses();
$paymentMethods = getRoomServicePaymentMethods();

// Generate filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$filename = "commandes_room_service_{$timestamp}";

if ($format === 'csv') {
    exportCSV($orders, $filename, $statuses, $paymentMethods);
} else {
    exportPDF($orders, $filename, $statuses, $paymentMethods, $statusFilter, $dateFrom, $dateTo);
}

/**
 * Fetch orders for export with extended date filtering
 */
function getOrdersForExport($status, $sortBy, $sortOrder, $deliveryDate, $dateFrom, $dateTo) {
    try {
        $pdo = getDatabase();

        $sql = "SELECT o.*,
                GROUP_CONCAT(CONCAT(oi.item_name, ' x', oi.quantity) SEPARATOR ', ') as items_summary
                FROM room_service_orders o
                LEFT JOIN room_service_order_items oi ON o.id = oi.order_id
                WHERE 1=1";
        $params = [];

        // Status filter
        if ($status !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }

        // Delivery date filter
        if ($deliveryDate) {
            $sql .= " AND DATE(o.delivery_datetime) = ?";
            $params[] = $deliveryDate;
        }

        // Date range filter (by created_at)
        if ($dateFrom) {
            $sql .= " AND DATE(o.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(o.created_at) <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY o.id";
        $sql .= " ORDER BY o.{$sortBy} {$sortOrder}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Export orders to CSV format
 */
function exportCSV($orders, $filename, $statuses, $paymentMethods) {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV header row
    fputcsv($output, [
        'ID Commande',
        'Chambre',
        'Nom Client',
        'Telephone',
        'Date Commande',
        'Date Livraison',
        'Statut',
        'Mode Paiement',
        'Articles',
        'Montant Total (EUR)',
        'Notes'
    ], ';');

    // Data rows
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['id'],
            $order['room_number'],
            $order['guest_name'] ?? '',
            $order['phone'] ?? '',
            date('d/m/Y H:i', strtotime($order['created_at'])),
            $order['delivery_datetime'] ? date('d/m/Y H:i', strtotime($order['delivery_datetime'])) : '',
            $statuses[$order['status']] ?? $order['status'],
            $paymentMethods[$order['payment_method']] ?? $order['payment_method'],
            $order['items_summary'] ?? '',
            number_format($order['total_amount'], 2, ',', ''),
            str_replace(["\r", "\n"], ' ', $order['notes'] ?? '')
        ], ';');
    }

    fclose($output);
    exit;
}

/**
 * Export orders to PDF format
 */
function exportPDF($orders, $filename, $statuses, $paymentMethods, $statusFilter, $dateFrom, $dateTo) {
    // Calculate totals
    $totalAmount = 0;
    $totalOrders = count($orders);
    $statusCounts = [];

    foreach ($orders as $order) {
        $totalAmount += $order['total_amount'];
        $status = $order['status'];
        $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    }

    // Build filter description
    $filterDesc = [];
    if ($statusFilter !== 'all') {
        $filterDesc[] = 'Statut: ' . ($statuses[$statusFilter] ?? $statusFilter);
    }
    if ($dateFrom) {
        $filterDesc[] = 'Du: ' . date('d/m/Y', strtotime($dateFrom));
    }
    if ($dateTo) {
        $filterDesc[] = 'Au: ' . date('d/m/Y', strtotime($dateTo));
    }
    $filterText = !empty($filterDesc) ? implode(' | ', $filterDesc) : 'Toutes les commandes';

    // Generate HTML for PDF
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Commandes Room Service</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Helvetica Neue", Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .container { max-width: 100%; padding: 20px; }
        .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #8B5A2B; }
        .header h1 { font-size: 22px; color: #8B5A2B; margin-bottom: 5px; }
        .header .subtitle { font-size: 12px; color: #666; }
        .header .date { font-size: 10px; color: #999; margin-top: 5px; }
        .filters { background: #f8f8f8; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 10px; color: #666; }
        .summary { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .summary-box { background: #f5f5f5; padding: 12px 15px; border-radius: 4px; text-align: center; width: 23%; display: inline-block; margin-right: 2%; }
        .summary-box:last-child { margin-right: 0; }
        .summary-box .value { font-size: 18px; font-weight: bold; color: #8B5A2B; }
        .summary-box .label { font-size: 9px; color: #666; text-transform: uppercase; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #8B5A2B; color: white; padding: 10px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 10px 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 9px; font-weight: 600; }
        .status-pending { background: rgba(237, 137, 54, 0.15); color: #C05621; }
        .status-confirmed { background: rgba(66, 153, 225, 0.15); color: #2B6CB0; }
        .status-preparing { background: rgba(159, 122, 234, 0.15); color: #6B46C1; }
        .status-delivered { background: rgba(72, 187, 120, 0.15); color: #276749; }
        .status-cancelled { background: rgba(245, 101, 101, 0.15); color: #C53030; }
        .price { font-weight: 600; color: #8B5A2B; }
        .items { font-size: 10px; color: #666; max-width: 200px; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; text-align: center; font-size: 9px; color: #999; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .container { padding: 0; }
            .print-hint { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($hotelName) . ' - Room Service</h1>
            <div class="subtitle">Rapport des Commandes</div>
            <div class="date">Genere le ' . date('d/m/Y a H:i') . '</div>
        </div>

        <div class="filters">Filtres appliques: ' . htmlspecialchars($filterText) . '</div>

        <div class="summary">
            <div class="summary-box">
                <div class="value">' . $totalOrders . '</div>
                <div class="label">Commandes</div>
            </div>
            <div class="summary-box">
                <div class="value">' . number_format($totalAmount, 2, ',', ' ') . ' EUR</div>
                <div class="label">Chiffre d\'affaires</div>
            </div>
            <div class="summary-box">
                <div class="value">' . ($totalOrders > 0 ? number_format($totalAmount / $totalOrders, 2, ',', ' ') : '0,00') . ' EUR</div>
                <div class="label">Panier moyen</div>
            </div>
            <div class="summary-box">
                <div class="value">' . ($statusCounts['delivered'] ?? 0) . '</div>
                <div class="label">Livrees</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Chambre</th>
                    <th>Client</th>
                    <th>Livraison</th>
                    <th>Articles</th>
                    <th>Statut</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($orders as $order) {
        $statusClass = 'status-' . $order['status'];
        $statusLabel = $statuses[$order['status']] ?? $order['status'];
        $deliveryDate = $order['delivery_datetime'] ? date('d/m/Y H:i', strtotime($order['delivery_datetime'])) : '-';
        $items = htmlspecialchars($order['items_summary'] ?? '-');
        if (strlen($items) > 50) {
            $items = substr($items, 0, 47) . '...';
        }

        $html .= '
                <tr>
                    <td>' . $order['id'] . '</td>
                    <td><strong>' . htmlspecialchars($order['room_number']) . '</strong></td>
                    <td>' . htmlspecialchars($order['guest_name'] ?? '-') . '</td>
                    <td>' . $deliveryDate . '</td>
                    <td class="items">' . $items . '</td>
                    <td><span class="status ' . $statusClass . '">' . htmlspecialchars($statusLabel) . '</span></td>
                    <td class="text-right price">' . number_format($order['total_amount'], 2, ',', ' ') . ' EUR</td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>

        <div class="footer">
            ' . htmlspecialchars($hotelName) . ' - ' . htmlspecialchars(getFormattedAddress(false)) . (!empty($contactInfo['phone']) ? ' - Tel: ' . htmlspecialchars($contactInfo['phone']) : '') . '
        </div>
        <div class="print-hint" style="text-align: center; margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 6px; font-size: 12px; color: #856404;">
            <strong>Pour sauvegarder en PDF:</strong> Utilisez Ctrl+P (Windows) ou Cmd+P (Mac), puis selectionnez "Enregistrer au format PDF"
        </div>
    </div>
    <script>
        // Auto-open print dialog after page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        // Hide print hint when printing
        window.onbeforeprint = function() {
            document.querySelector(".print-hint").style.display = "none";
        };
        window.onafterprint = function() {
            document.querySelector(".print-hint").style.display = "block";
        };
    </script>
</body>
</html>';

    // Set headers for HTML download (can be printed as PDF from browser)
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $filename . '.html"');

    echo $html;
    exit;
}
