<?php
// /admin/get_user_growth_data.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only allow admins to access this data.
requireRole('admin');

// 2. Prepare an array to hold the last 7 days with a default count of 0.
$userGrowthData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    // Use a short, readable date format for the chart labels
    $userGrowthData[date('M d', strtotime($date))] = 0;
}

// 3. Query the database for users created in the last 7 days, grouped by date.
$query = "
    SELECT
        DATE(created_at) as registration_date,
        COUNT(id) as new_users
    FROM
        users
    WHERE
        created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY
        DATE(created_at)
    ORDER BY
        registration_date ASC
";

$result = $conn->query($query);

// 4. Populate the array with the actual data from the database.
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $formattedDate = date('M d', strtotime($row['registration_date']));
        if (array_key_exists($formattedDate, $userGrowthData)) {
            $userGrowthData[$formattedDate] = (int)$row['new_users'];
        }
    }
}

// 5. Separate the keys (labels) and values (data) for Chart.js.
$labels = array_keys($userGrowthData);
$data = array_values($userGrowthData);

// 6. Send the final JSON response.
echo json_encode([
    'labels' => $labels,
    'data' => $data
]);

$conn->close();

?>