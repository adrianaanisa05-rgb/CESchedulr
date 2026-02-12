<?php
include 'db_connect.php';
header('Content-Type: application/json');

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$months = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

$sql = "
    SELECT 
        c.club_name,
        MONTH(e.event_date) AS month_num,
        MONTHNAME(e.event_date) AS month_name,
        COUNT(e.id) AS total_events
    FROM club c
    LEFT JOIN events e 
        ON e.club_id = c.id
        AND e.approval_status = 'approved'
        AND YEAR(e.event_date) = $year
    GROUP BY c.id, MONTH(e.event_date)
    ORDER BY month_num
";

$result = $conn->query($sql);

$clubs = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $club = $row['club_name'];
    $month = $row['month_name'];

    if (!in_array($club, $clubs)) {
        $clubs[] = $club;
    }

    $data[$club][$month] = (int)$row['total_events'];
}

// Ensure all months exist for each club
foreach ($clubs as $club) {
    foreach ($months as $month) {
        if (!isset($data[$club][$month])) {
            $data[$club][$month] = 0;
        }
    }
}

echo json_encode([
    'months' => $months,
    'clubs' => $clubs,
    'data' => $data
]);