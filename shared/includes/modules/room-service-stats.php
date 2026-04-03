<?php
/**
 * Room Service Statistics Functions
 * Analytics, revenue, peaks, top items, period comparison
 */

/**
 * Get comprehensive room service statistics for a given period
 * @param string $period 'day', 'week', 'month', 'year'
 * @param string|null $date Reference date (defaults to today)
 * @return array
 */
function getRoomServicePeriodStats(string $period = 'day', ?string $date = null): array {
    $pdo = getDatabase();
    $refDate = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');

    // Calculate date ranges based on period
    switch ($period) {
        case 'week':
            $startDate = date('Y-m-d', strtotime('monday this week', strtotime($refDate)));
            $endDate = date('Y-m-d', strtotime('sunday this week', strtotime($refDate)));
            $prevStartDate = date('Y-m-d', strtotime('-1 week', strtotime($startDate)));
            $prevEndDate = date('Y-m-d', strtotime('-1 week', strtotime($endDate)));
            break;
        case 'month':
            $startDate = date('Y-m-01', strtotime($refDate));
            $endDate = date('Y-m-t', strtotime($refDate));
            $prevStartDate = date('Y-m-01', strtotime('-1 month', strtotime($refDate)));
            $prevEndDate = date('Y-m-t', strtotime('-1 month', strtotime($refDate)));
            break;
        case 'year':
            $startDate = date('Y-01-01', strtotime($refDate));
            $endDate = date('Y-12-31', strtotime($refDate));
            $prevStartDate = date('Y-01-01', strtotime('-1 year', strtotime($refDate)));
            $prevEndDate = date('Y-12-31', strtotime('-1 year', strtotime($refDate)));
            break;
        default: // day
            $startDate = $refDate;
            $endDate = $refDate;
            $prevStartDate = date('Y-m-d', strtotime('-1 day', strtotime($refDate)));
            $prevEndDate = $prevStartDate;
    }

    // Current period stats
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue,
            COALESCE(AVG(CASE WHEN status != 'cancelled' THEN total_amount END), 0) as avg_order_value,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
        FROM room_service_orders
        WHERE DATE(created_at) BETWEEN ? AND ? AND hotel_id = ?
    ");
    $stmt->execute([$startDate, $endDate, getHotelId()]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    // Previous period stats (for comparison)
    $stmt->execute([$prevStartDate, $prevEndDate, getHotelId()]);
    $previous = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate percentage changes
    $revenueChange = $previous['revenue'] > 0
        ? (($current['revenue'] - $previous['revenue']) / $previous['revenue']) * 100
        : ($current['revenue'] > 0 ? 100 : 0);
    $ordersChange = $previous['total_orders'] > 0
        ? (($current['total_orders'] - $previous['total_orders']) / $previous['total_orders']) * 100
        : ($current['total_orders'] > 0 ? 100 : 0);

    return [
        'period' => $period,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'current' => [
            'total_orders' => (int)$current['total_orders'],
            'revenue' => (float)$current['revenue'],
            'avg_order_value' => (float)$current['avg_order_value'],
            'delivered_orders' => (int)$current['delivered_orders'],
            'cancelled_orders' => (int)$current['cancelled_orders'],
            'delivery_rate' => $current['total_orders'] > 0
                ? round(($current['delivered_orders'] / $current['total_orders']) * 100, 1)
                : 0
        ],
        'previous' => [
            'total_orders' => (int)$previous['total_orders'],
            'revenue' => (float)$previous['revenue'],
            'start_date' => $prevStartDate,
            'end_date' => $prevEndDate
        ],
        'changes' => [
            'revenue_percent' => round($revenueChange, 1),
            'orders_percent' => round($ordersChange, 1)
        ]
    ];
}

/**
 * Get daily revenue data for charts
 * @param int $days Number of days to fetch
 * @param string|null $endDate End date (defaults to today)
 * @return array
 */
function getRoomServiceDailyRevenue(int $days = 30, ?string $endDate = null): array {
    $pdo = getDatabase();
    $end = $endDate ? date('Y-m-d', strtotime($endDate)) : date('Y-m-d');
    $start = date('Y-m-d', strtotime("-{$days} days", strtotime($end)));

    $stmt = $pdo->prepare("
        SELECT
            DATE(created_at) as date,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE DATE(created_at) BETWEEN ? AND ? AND hotel_id = ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$start, $end, getHotelId()]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fill in missing dates with zero values
    $data = [];
    $current = strtotime($start);
    $endTime = strtotime($end);

    $resultsByDate = [];
    foreach ($results as $row) {
        $resultsByDate[$row['date']] = $row;
    }

    while ($current <= $endTime) {
        $dateStr = date('Y-m-d', $current);
        $data[] = [
            'date' => $dateStr,
            'label' => date('d/m', $current),
            'orders' => isset($resultsByDate[$dateStr]) ? (int)$resultsByDate[$dateStr]['orders'] : 0,
            'revenue' => isset($resultsByDate[$dateStr]) ? (float)$resultsByDate[$dateStr]['revenue'] : 0
        ];
        $current = strtotime('+1 day', $current);
    }

    return $data;
}

/**
 * Get weekly revenue data for charts
 * @param int $weeks Number of weeks to fetch
 * @return array
 */
function getRoomServiceWeeklyRevenue(int $weeks = 12): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            TO_CHAR(created_at, 'IYYY-IW') as year_week,
            MIN(DATE(created_at)) as week_start,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 week' AND hotel_id = ?
        GROUP BY TO_CHAR(created_at, 'IYYY-IW')
        ORDER BY year_week ASC
    ");
    $stmt->execute([$weeks, getHotelId()]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(function($row) {
        return [
            'week' => $row['year_week'],
            'week_start' => $row['week_start'],
            'label' => 'S' . substr($row['year_week'], -2),
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue']
        ];
    }, $results);
}

/**
 * Get monthly revenue data for charts
 * @param int $months Number of months to fetch
 * @return array
 */
function getRoomServiceMonthlyRevenue(int $months = 12): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            TO_CHAR(created_at, 'YYYY-MM') as month,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 month' AND hotel_id = ?
        GROUP BY TO_CHAR(created_at, 'YYYY-MM')
        ORDER BY month ASC
    ");
    $stmt->execute([$months, getHotelId()]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    return array_map(function($row) use ($monthNames) {
        $monthNum = (int)substr($row['month'], 5, 2);
        return [
            'month' => $row['month'],
            'label' => $monthNames[$monthNum - 1],
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue']
        ];
    }, $results);
}

/**
 * Get yearly revenue data for charts (monthly breakdown for a specific year)
 * Returns all 12 months with revenue data, including empty months as 0
 * @param int|null $year The year to fetch (defaults to current year)
 * @return array
 */
function getRoomServiceYearlyRevenue(?int $year = null): array {
    $pdo = getDatabase();
    $year = $year ?? (int)date('Y');

    // Query monthly data for the specified year
    $stmt = $pdo->prepare("
        SELECT
            EXTRACT(MONTH FROM created_at) as month_num,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE EXTRACT(YEAR FROM created_at) = ? AND hotel_id = ?
        GROUP BY EXTRACT(MONTH FROM created_at)
        ORDER BY month_num ASC
    ");
    $stmt->execute([$year, getHotelId()]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Index results by month number for easy lookup
    $resultsByMonth = [];
    foreach ($results as $row) {
        $resultsByMonth[(int)$row['month_num']] = $row;
    }

    // French month names
    $monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    // Build array with all 12 months (including empty ones as 0)
    $data = [];
    for ($month = 1; $month <= 12; $month++) {
        $data[] = [
            'month' => $month,
            'month_str' => sprintf('%04d-%02d', $year, $month),
            'label' => $monthNames[$month - 1],
            'orders' => isset($resultsByMonth[$month]) ? (int)$resultsByMonth[$month]['orders'] : 0,
            'revenue' => isset($resultsByMonth[$month]) ? (float)$resultsByMonth[$month]['revenue'] : 0
        ];
    }

    return $data;
}

/**
 * Get peak hours analysis
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServicePeakHours(int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            EXTRACT(HOUR FROM delivery_datetime) as hour,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 day' AND hotel_id = ?
        GROUP BY EXTRACT(HOUR FROM delivery_datetime)
        ORDER BY hour ASC
    ");
    $stmt->execute([$days, getHotelId()]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fill all 24 hours
    $data = array_fill(0, 24, ['orders' => 0, 'revenue' => 0]);
    foreach ($results as $row) {
        $data[(int)$row['hour']] = [
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue']
        ];
    }

    $hourlyData = [];
    for ($h = 0; $h < 24; $h++) {
        $hourlyData[] = [
            'hour' => $h,
            'label' => sprintf('%02d:00', $h),
            'orders' => $data[$h]['orders'],
            'revenue' => $data[$h]['revenue']
        ];
    }

    // Find peak hour
    $peakHour = 0;
    $maxOrders = 0;
    foreach ($hourlyData as $item) {
        if ($item['orders'] > $maxOrders) {
            $maxOrders = $item['orders'];
            $peakHour = $item['hour'];
        }
    }

    return [
        'data' => $hourlyData,
        'peak_hour' => $peakHour,
        'peak_hour_label' => sprintf('%02d:00 - %02d:00', $peakHour, ($peakHour + 1) % 24)
    ];
}

/**
 * Get peak days analysis
 * @param int $weeks Number of weeks to analyze
 * @return array
 */
function getRoomServicePeakDays(int $weeks = 8): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            EXTRACT(DOW FROM created_at) as day_num,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 week' AND hotel_id = ?
        GROUP BY EXTRACT(DOW FROM created_at)
        ORDER BY day_num ASC
    ");
    $stmt->execute([$weeks, getHotelId()]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // PostgreSQL DOW: 0=Sunday, 1=Monday, ..., 6=Saturday
    $dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    $fullDayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    // Fill all 7 days (0-6 for PostgreSQL DOW)
    $data = array_fill(0, 7, ['orders' => 0, 'revenue' => 0]);
    foreach ($results as $row) {
        $data[(int)$row['day_num']] = [
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue']
        ];
    }

    $dailyData = [];
    $peakDay = 0;
    $maxOrders = 0;
    for ($d = 0; $d <= 6; $d++) {
        $dailyData[] = [
            'day' => $d,
            'label' => $dayNames[$d],
            'full_name' => $fullDayNames[$d],
            'orders' => $data[$d]['orders'],
            'revenue' => $data[$d]['revenue']
        ];
        if ($data[$d]['orders'] > $maxOrders) {
            $maxOrders = $data[$d]['orders'];
            $peakDay = $d;
        }
    }

    return [
        'data' => $dailyData,
        'peak_day' => $peakDay,
        'peak_day_name' => $fullDayNames[$peakDay]
    ];
}

/**
 * Get top selling items
 * @param int $limit Number of items to return
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServiceTopItems(int $limit = 10, int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            oi.item_name,
            oi.item_id,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.subtotal) as total_revenue,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM room_service_order_items oi
        JOIN room_service_orders o ON oi.order_id = o.id
        WHERE o.created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 day'
            AND o.status != 'cancelled'
            AND o.hotel_id = ?
        GROUP BY oi.item_id, oi.item_name
        ORDER BY total_quantity DESC
        LIMIT ?
    ");
    $stmt->execute([$days, getHotelId(), $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get revenue by category
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServiceRevenueByCategory(int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(i.category, 'general') as category,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.subtotal) as total_revenue,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM room_service_order_items oi
        JOIN room_service_orders o ON oi.order_id = o.id
        LEFT JOIN room_service_items i ON oi.item_id = i.id
        WHERE o.created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 day'
            AND o.status != 'cancelled'
            AND o.hotel_id = ?
        GROUP BY COALESCE(i.category, 'general')
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$days, getHotelId()]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = getRoomServiceCategories();
    return array_map(function($row) use ($categories) {
        return [
            'category' => $row['category'],
            'category_name' => $categories[$row['category']] ?? ucfirst($row['category']),
            'total_quantity' => (int)$row['total_quantity'],
            'total_revenue' => (float)$row['total_revenue'],
            'order_count' => (int)$row['order_count']
        ];
    }, $results);
}

/**
 * Get payment method breakdown
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServicePaymentBreakdown(int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            payment_method,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_revenue
        FROM room_service_orders
        WHERE created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 day'
            AND status != 'cancelled'
            AND hotel_id = ?
        GROUP BY payment_method
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$days, getHotelId()]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $methods = getRoomServicePaymentMethods();
    return array_map(function($row) use ($methods) {
        return [
            'method' => $row['payment_method'],
            'method_name' => $methods[$row['payment_method']] ?? $row['payment_method'],
            'order_count' => (int)$row['order_count'],
            'total_revenue' => (float)$row['total_revenue']
        ];
    }, $results);
}

/**
 * Get order status breakdown
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServiceStatusBreakdown(int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            status,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_amount
        FROM room_service_orders
        WHERE created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 day'
            AND hotel_id = ?
        GROUP BY status
        ORDER BY order_count DESC
    ");
    $stmt->execute([$days, getHotelId()]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $statuses = getRoomServiceOrderStatuses();
    return array_map(function($row) use ($statuses) {
        return [
            'status' => $row['status'],
            'status_name' => $statuses[$row['status']] ?? $row['status'],
            'order_count' => (int)$row['order_count'],
            'total_amount' => (float)$row['total_amount']
        ];
    }, $results);
}

/**
 * Get best performing period
 * @param string $periodType 'day', 'week', 'month'
 * @param int $lookback Number of periods to look back
 * @return array|null
 */
function getRoomServiceBestPeriod(string $periodType = 'day', int $lookback = 30): ?array {
    $pdo = getDatabase();

    switch ($periodType) {
        case 'week':
            $groupBy = "TO_CHAR(created_at, 'IYYY-IW')";
            $dateFormat = '%Y-W%u';
            break;
        case 'month':
            $groupBy = "TO_CHAR(created_at, 'YYYY-MM')";
            $dateFormat = '%Y-%m';
            break;
        default:
            $groupBy = 'DATE(created_at)';
            $dateFormat = '%Y-%m-%d';
    }

    $stmt = $pdo->prepare("
        SELECT
            {$groupBy} as period,
            MIN(DATE(created_at)) as period_start,
            COUNT(*) as orders,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as revenue
        FROM room_service_orders
        WHERE created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 {$periodType}' AND hotel_id = ?
        GROUP BY {$groupBy}
        ORDER BY revenue DESC
        LIMIT 1
    ");
    $stmt->execute([$lookback, getHotelId()]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return null;
    }

    return [
        'period' => $result['period'],
        'period_start' => $result['period_start'],
        'orders' => (int)$result['orders'],
        'revenue' => (float)$result['revenue']
    ];
}

/**
 * Get room statistics (which rooms order the most)
 * @param int $limit Number of rooms to return
 * @param int $days Number of days to analyze
 * @return array
 */
function getRoomServiceTopRooms(int $limit = 10, int $days = 30): array {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        SELECT
            room_number,
            COUNT(*) as order_count,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as total_revenue,
            COALESCE(AVG(CASE WHEN status != 'cancelled' THEN total_amount END), 0) as avg_order_value
        FROM room_service_orders
        WHERE created_at >= CURRENT_DATE - CAST(? AS INTEGER) * INTERVAL '1 day' AND hotel_id = ?
        GROUP BY room_number
        ORDER BY total_revenue DESC
        LIMIT ?
    ");
    $stmt->execute([$days, getHotelId(), $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
