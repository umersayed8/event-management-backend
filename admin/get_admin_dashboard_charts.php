<?php
// /admin/get_admin_dashboard_charts.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('admin');

// Helper function to process query results for the last 7 days
function process_daily_data($conn, $query) {
    $dataMap = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('M d', strtotime("-$i days"));
        $dataMap[$date] = 0;
    }

    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $formattedDate = date('M d', strtotime($row['date']));
            if (array_key_exists($formattedDate, $dataMap)) {
                $dataMap[$formattedDate] = (float)$row['value'];
            }
        }
    }
    return [
        'labels' => array_keys($dataMap),
        'data' => array_values($dataMap)
    ];
}

// 1. User Growth Data
$userGrowthQuery = "
    SELECT DATE(created_at) as date, COUNT(id) as value
    FROM users
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
";
$userGrowthData = process_daily_data($conn, $userGrowthQuery);

// 2. Ticket Sales Trend Data
$ticketSalesQuery = "
    SELECT DATE(created_at) as date, SUM(quantity) as value
    FROM tickets
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY AND status = 'confirmed'
    GROUP BY DATE(created_at)
";
$ticketSalesData = process_daily_data($conn, $ticketSalesQuery);

// 3. Event Frequency Data
$eventFrequencyQuery = "
    SELECT DATE(created_at) as date, COUNT(id) as value
    FROM events
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
";
$eventFrequencyData = process_daily_data($conn, $eventFrequencyQuery);

// 4. Revenue Trend Data
$revenueTrendQuery = "
    SELECT DATE(created_at) as date, SUM(amount_paid) as value
    FROM tickets
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY AND status = 'confirmed'
    GROUP BY DATE(created_at)
";
$revenueTrendData = process_daily_data($conn, $revenueTrendQuery);


// 5. Combine all chart data into a single JSON response
echo json_encode([
    'user_growth' => $userGrowthData,
    'ticket_sales' => $ticketSalesData,
    'event_frequency' => $eventFrequencyData,
    'revenue_trend' => $revenueTrendData
]);

$conn->close();
?>

