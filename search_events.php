<?php
session_start();
include 'db_connect.php';

$search = $_POST['search'] ?? '';
$searchLike = "%" . $search . "%";

$sql = "
SELECT e.*, u.username,c.club_name
FROM events e
JOIN users u ON e.user_id = u.id
LEFT JOIN club c ON e.club_id = c.id
WHERE e.approval_status = 'approved'
AND (
    e.title LIKE ?
    OR e.event_date LIKE ?
    OR u.username LIKE ?
    OR e.event_status LIKE ?
    OR c.club_name LIKE ?
)
ORDER BY e.event_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $searchLike, $searchLike, $searchLike, $searchLike, $searchLike);
$stmt->execute();

$result = $stmt->get_result();

$i = 1;

if ($result->num_rows === 0) {
    echo "<tr>
            <td colspan='7' class='text-center text-muted'>
              No events found
            </td>
          </tr>";
    exit;
}

while ($event = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$i}</td>
        <td>";

    if (!empty($event['event_image'])) {
        echo "<img src='uploads/" . htmlspecialchars($event['event_image']) . "' 
              width='150' style='border-radius:8px'>";
    } else {
        echo "No Image";
    }

    echo "</td>
        <td class='searchable'>" . htmlspecialchars($event['title']) . "</td>
        <td class='searchable'>" . htmlspecialchars($event['event_date']) . "</td>
        <td class='searchable'>" . htmlspecialchars($event['event_status']) . "</td>
        <td class='searchable'>" . htmlspecialchars($event['username']) . "</td>
        <td class='searchable'>" . htmlspecialchars($event['club_name'] ?? 'N/A') . "</td>
        <td>
          <button class='btn btn-primary btn-sm'
            data-bs-toggle='modal'
            data-bs-target='#eventModal'
            data-id='{$event['id']}'>
            View Event
          </button>
        </td>
    </tr>";

    $i++;
}

$stmt->close();
$conn->close();