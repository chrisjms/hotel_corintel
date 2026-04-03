<?php
/**
 * VAT & Financial Functions
 * VAT rates, price calculations, financial statistics
 */

// VAT rate constant
if (!defined('DEFAULT_VAT_RATE')) define('DEFAULT_VAT_RATE', 10.0);

/**
 * Get the default VAT rate (stored in settings or fallback to constant)
 * @return float VAT rate as percentage (e.g., 10.0 for 10%)
 */
function getDefaultVatRate(): float {
    return (float) getSetting('default_vat_rate', DEFAULT_VAT_RATE);
}

/**
 * Set the default VAT rate
 * @param float $rate VAT rate as percentage
 * @return bool Success status
 */
function setDefaultVatRate(float $rate): bool {
    if ($rate < 0 || $rate > 100) {
        return false;
    }
    return setSetting('default_vat_rate', (string) $rate);
}

/**
 * Get VAT rate for a specific category
 * Categories can have their own VAT rates (e.g., alcohol at 20%, food at 10%)
 * @param string $categoryCode Category code
 * @return float VAT rate as percentage
 */
function getCategoryVatRate(string $categoryCode): float {
    $categoryRate = getSetting('vat_rate_' . $categoryCode, null);
    if ($categoryRate !== null && $categoryRate !== '') {
        return (float) $categoryRate;
    }
    return getDefaultVatRate();
}

/**
 * Set VAT rate for a specific category
 * @param string $categoryCode Category code
 * @param float|null $rate VAT rate (null to use default)
 * @return bool Success status
 */
function setCategoryVatRate(string $categoryCode, ?float $rate): bool {
    if ($rate === null) {
        // Remove custom rate, will use default
        try {
            $pdo = getDatabase();
            $stmt = $pdo->prepare('DELETE FROM settings WHERE setting_key = ? AND hotel_id = ?');
            return $stmt->execute(['vat_rate_' . $categoryCode, getHotelId()]);
        } catch (PDOException $e) {
            return false;
        }
    }
    if ($rate < 0 || $rate > 100) {
        return false;
    }
    return setSetting('vat_rate_' . $categoryCode, (string) $rate);
}

/**
 * Get all VAT rates (default + per category)
 * @return array Array with 'default' and category codes as keys
 */
function getAllVatRates(): array {
    $rates = ['default' => getDefaultVatRate()];
    $categories = getRoomServiceCategories();

    foreach ($categories as $code => $name) {
        $customRate = getSetting('vat_rate_' . $code, null);
        $rates[$code] = $customRate !== null && $customRate !== ''
            ? (float) $customRate
            : null; // null means "use default"
    }

    return $rates;
}

/**
 * Calculate price excluding tax (HT) from price including tax (TTC)
 * Uses French accounting standards for rounding
 *
 * Formula: HT = TTC / (1 + VAT/100)
 *
 * @param float $priceTTC Price including tax
 * @param float $vatRate VAT rate as percentage (default: use default rate)
 * @return float Price excluding tax, rounded to 2 decimal places
 */
function calculatePriceHT(float $priceTTC, ?float $vatRate = null): float {
    $vatRate = $vatRate ?? getDefaultVatRate();
    if ($vatRate <= 0) {
        return round($priceTTC, 2);
    }
    $priceHT = $priceTTC / (1 + ($vatRate / 100));
    return round($priceHT, 2);
}

/**
 * Calculate price including tax (TTC) from price excluding tax (HT)
 * Uses French accounting standards for rounding
 *
 * Formula: TTC = HT * (1 + VAT/100)
 *
 * @param float $priceHT Price excluding tax
 * @param float $vatRate VAT rate as percentage (default: use default rate)
 * @return float Price including tax, rounded to 2 decimal places
 */
function calculatePriceTTC(float $priceHT, ?float $vatRate = null): float {
    $vatRate = $vatRate ?? getDefaultVatRate();
    $priceTTC = $priceHT * (1 + ($vatRate / 100));
    return round($priceTTC, 2);
}

/**
 * Calculate VAT amount from price including tax (TTC)
 *
 * Formula: VAT = TTC - HT = TTC - (TTC / (1 + VAT/100))
 *
 * @param float $priceTTC Price including tax
 * @param float $vatRate VAT rate as percentage (default: use default rate)
 * @return float VAT amount, rounded to 2 decimal places
 */
function calculateVATAmount(float $priceTTC, ?float $vatRate = null): float {
    $vatRate = $vatRate ?? getDefaultVatRate();
    if ($vatRate <= 0) {
        return 0.00;
    }
    $priceHT = calculatePriceHT($priceTTC, $vatRate);
    return round($priceTTC - $priceHT, 2);
}

/**
 * Get price breakdown (TTC, HT, VAT) for a given TTC price
 * @param float $priceTTC Price including tax
 * @param float|null $vatRate VAT rate as percentage (default: use default rate)
 * @return array ['ttc' => float, 'ht' => float, 'vat' => float, 'vat_rate' => float]
 */
function getPriceBreakdown(float $priceTTC, ?float $vatRate = null): array {
    $vatRate = $vatRate ?? getDefaultVatRate();
    $priceHT = calculatePriceHT($priceTTC, $vatRate);
    $vatAmount = round($priceTTC - $priceHT, 2);

    return [
        'ttc' => round($priceTTC, 2),
        'ht' => $priceHT,
        'vat' => $vatAmount,
        'vat_rate' => $vatRate
    ];
}

/**
 * Format price for display (French format)
 * @param float $price Price value
 * @param bool $includeSymbol Include € symbol
 * @return string Formatted price
 */
function formatPrice(float $price, bool $includeSymbol = true): string {
    $formatted = number_format($price, 2, ',', ' ');
    return $includeSymbol ? $formatted . ' €' : $formatted;
}

/**
 * Get VAT statistics for a date range
 * Calculates total revenue TTC, HT, and VAT collected
 *
 * @param string $startDate Start date (Y-m-d)
 * @param string $endDate End date (Y-m-d)
 * @return array Financial statistics with TTC, HT, VAT breakdown
 */
function getVATStatistics(string $startDate, string $endDate): array {
    $pdo = getDatabase();

    // Get all completed orders with their items
    $stmt = $pdo->prepare("
        SELECT
            o.id as order_id,
            o.total_amount,
            o.created_at,
            oi.item_id,
            oi.item_price,
            oi.quantity,
            oi.subtotal,
            COALESCE(i.category, 'general') as category
        FROM room_service_orders o
        JOIN room_service_order_items oi ON o.id = oi.order_id
        LEFT JOIN room_service_items i ON oi.item_id = i.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
            AND o.status NOT IN ('cancelled')
            AND o.hotel_id = ?
        ORDER BY o.id
    ");
    $stmt->execute([$startDate, $endDate, getHotelId()]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalTTC = 0;
    $totalHT = 0;
    $totalVAT = 0;
    $vatByCategory = [];
    $vatByRate = [];

    foreach ($items as $item) {
        $category = $item['category'];
        $vatRate = getCategoryVatRate($category);
        $itemTTC = (float) $item['subtotal'];
        $itemHT = calculatePriceHT($itemTTC, $vatRate);
        $itemVAT = round($itemTTC - $itemHT, 2);

        $totalTTC += $itemTTC;
        $totalHT += $itemHT;
        $totalVAT += $itemVAT;

        // By category
        if (!isset($vatByCategory[$category])) {
            $vatByCategory[$category] = [
                'category' => $category,
                'vat_rate' => $vatRate,
                'total_ttc' => 0,
                'total_ht' => 0,
                'total_vat' => 0
            ];
        }
        $vatByCategory[$category]['total_ttc'] += $itemTTC;
        $vatByCategory[$category]['total_ht'] += $itemHT;
        $vatByCategory[$category]['total_vat'] += $itemVAT;

        // By VAT rate
        $rateKey = (string) $vatRate;
        if (!isset($vatByRate[$rateKey])) {
            $vatByRate[$rateKey] = [
                'vat_rate' => $vatRate,
                'total_ttc' => 0,
                'total_ht' => 0,
                'total_vat' => 0
            ];
        }
        $vatByRate[$rateKey]['total_ttc'] += $itemTTC;
        $vatByRate[$rateKey]['total_ht'] += $itemHT;
        $vatByRate[$rateKey]['total_vat'] += $itemVAT;
    }

    // Round final totals
    $totalTTC = round($totalTTC, 2);
    $totalHT = round($totalHT, 2);
    $totalVAT = round($totalVAT, 2);

    // Round category totals
    foreach ($vatByCategory as &$cat) {
        $cat['total_ttc'] = round($cat['total_ttc'], 2);
        $cat['total_ht'] = round($cat['total_ht'], 2);
        $cat['total_vat'] = round($cat['total_vat'], 2);
    }

    // Round rate totals
    foreach ($vatByRate as &$rate) {
        $rate['total_ttc'] = round($rate['total_ttc'], 2);
        $rate['total_ht'] = round($rate['total_ht'], 2);
        $rate['total_vat'] = round($rate['total_vat'], 2);
    }

    return [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'totals' => [
            'ttc' => $totalTTC,
            'ht' => $totalHT,
            'vat' => $totalVAT
        ],
        'by_category' => array_values($vatByCategory),
        'by_rate' => array_values($vatByRate)
    ];
}

/**
 * Get room service financial statistics for a period
 * Extended version of getRoomServicePeriodStats with VAT breakdown
 *
 * @param string $period Period type: 'day', 'week', 'month', 'year'
 * @param string|null $date Reference date (defaults to today)
 * @return array Extended statistics with VAT information
 */
function getRoomServiceFinancialStats(string $period = 'day', ?string $date = null): array {
    $refDate = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');

    // Calculate date ranges
    switch ($period) {
        case 'week':
            $startDate = date('Y-m-d', strtotime('monday this week', strtotime($refDate)));
            $endDate = date('Y-m-d', strtotime('sunday this week', strtotime($refDate)));
            $prevStart = date('Y-m-d', strtotime('-1 week', strtotime($startDate)));
            $prevEnd = date('Y-m-d', strtotime('-1 week', strtotime($endDate)));
            break;
        case 'month':
            $startDate = date('Y-m-01', strtotime($refDate));
            $endDate = date('Y-m-t', strtotime($refDate));
            $prevStart = date('Y-m-01', strtotime('-1 month', strtotime($refDate)));
            $prevEnd = date('Y-m-t', strtotime('-1 month', strtotime($refDate)));
            break;
        case 'year':
            $startDate = date('Y-01-01', strtotime($refDate));
            $endDate = date('Y-12-31', strtotime($refDate));
            $prevStart = date('Y-01-01', strtotime('-1 year', strtotime($refDate)));
            $prevEnd = date('Y-12-31', strtotime('-1 year', strtotime($refDate)));
            break;
        default: // day
            $startDate = $refDate;
            $endDate = $refDate;
            $prevStart = date('Y-m-d', strtotime('-1 day', strtotime($refDate)));
            $prevEnd = $prevStart;
    }

    // Get VAT statistics for current and previous period
    $current = getVATStatistics($startDate, $endDate);
    $previous = getVATStatistics($prevStart, $prevEnd);

    // Get order counts
    $pdo = getDatabase();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as order_count
        FROM room_service_orders
        WHERE DATE(created_at) BETWEEN ? AND ?
            AND status NOT IN ('cancelled')
            AND hotel_id = ?
    ");
    $stmt->execute([$startDate, $endDate, getHotelId()]);
    $currentOrders = (int) $stmt->fetchColumn();

    $stmt->execute([$prevStart, $prevEnd, getHotelId()]);
    $previousOrders = (int) $stmt->fetchColumn();

    // Calculate changes
    $revenueTTCChange = $previous['totals']['ttc'] > 0
        ? (($current['totals']['ttc'] - $previous['totals']['ttc']) / $previous['totals']['ttc']) * 100
        : ($current['totals']['ttc'] > 0 ? 100 : 0);

    $ordersChange = $previousOrders > 0
        ? (($currentOrders - $previousOrders) / $previousOrders) * 100
        : ($currentOrders > 0 ? 100 : 0);

    return [
        'period' => $period,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'current' => [
            'order_count' => $currentOrders,
            'revenue_ttc' => $current['totals']['ttc'],
            'revenue_ht' => $current['totals']['ht'],
            'vat_collected' => $current['totals']['vat'],
            'avg_order_ttc' => $currentOrders > 0 ? round($current['totals']['ttc'] / $currentOrders, 2) : 0,
            'by_category' => $current['by_category'],
            'by_rate' => $current['by_rate']
        ],
        'previous' => [
            'start_date' => $prevStart,
            'end_date' => $prevEnd,
            'order_count' => $previousOrders,
            'revenue_ttc' => $previous['totals']['ttc'],
            'revenue_ht' => $previous['totals']['ht'],
            'vat_collected' => $previous['totals']['vat']
        ],
        'changes' => [
            'revenue_ttc_percent' => round($revenueTTCChange, 1),
            'orders_percent' => round($ordersChange, 1)
        ]
    ];
}
