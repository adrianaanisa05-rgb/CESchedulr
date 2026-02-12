<?php
session_start();
include 'db_connect.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['updates_event'])) {

    $event_id = $_POST['event_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = trim($_POST['event_date']);
    $end_date = trim($_POST['end_date']);
    $capacity = (int)$_POST['capacity'];
    $status = $_POST['event_status'];
    $approval_status = $_POST['approval_status'];
    $contact = $_POST['contact_number'];
    $venue_id = !empty($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;
    $club_id = !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null;

    $start_ts = strtotime($date);
    $end_ts = strtotime($end_date);
    $min_ts = strtotime('2025-01-01');

    // Fetch current event data
    $stmt = $conn->prepare("SELECT approval_status, club_id FROM events WHERE id=?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $currentEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    if ($start_ts === false || $end_ts === false) {
    $errors[] = "Invalid date format.";
} else {
    if ($end_ts <= $start_ts) {
        $errors[] = "End date must be after start date.";
    }
    if ($start_ts < $min_ts) {
        $errors[] = "Event start date cannot be before 2025.";
    }
}

    // Validation: venue overlapping
   if (!empty($venue_id)) {
        $overlapSql = $conn->prepare("
            SELECT events.title, events.event_date, events.end_date, venue.venue_name
            FROM events
            LEFT JOIN venue ON events.venue_id = venue.venue_id
            WHERE events.venue_id = ?
              AND events.id != ?
              AND events.approval_status != 'rejected'
              AND (events.event_date < ? AND events.end_date > ?)
        ");
        $overlapSql->bind_param("iiss", $venue_id, $event_id, $end_date, $date);
        $overlapSql->execute();
        $overlapResult = $overlapSql->get_result();

        if ($overlapResult->num_rows > 0) {
            $overlappingEvents = [];
            while ($row = $overlapResult->fetch_assoc()) {
                $overlappingEvents[] = $row;
            }

            $message = "This event overlaps with the following events at the same venue: ";
            foreach ($overlappingEvents as $event) {
                $message .= htmlspecialchars($event['title']) . " | ";
                $message .= date("M d, Y H:i", strtotime($event['event_date'])) . " - ";
                $message .= date("M d, Y H:i", strtotime($event['end_date'])) . " | ";
                $message .= htmlspecialchars($event['venue_name'] ?? 'N/A');
            }

            $errors[] = $message;
        }

        $overlapSql->close();
    }

    // Handle image upload if new image is provided
    $event_image = null;
    if (!empty($_FILES['event_image']['name'])) {
        $fileName = $_FILES['event_image']['name'];
        $tempName = $_FILES['event_image']['tmp_name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];

        if (!in_array($ext, $allowedTypes)) {
            $errors[] = "Invalid image type. Allowed types: JPG, JPEG, PNG, GIF.";
        } else {
            $uniqueName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $targetPath = "uploads/" . $uniqueName;
            if (!move_uploaded_file($tempName, $targetPath)) {
                $errors[] = "Failed to upload image.";
            } else {
                $event_image = $uniqueName;
            }
        }
    }

    // If there are errors, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header("Location: admin_dashboard.php");
        exit;
    }

    // Prepare update query
    if ($event_image !== null) {
        $stmt = $conn->prepare("
            UPDATE events 
            SET title=?, event_description=?, event_date=?, end_date=?, event_capacity=?, event_image=?, event_status=?, contact_number=?, venue_id=?, club_id=?, approval_status=?
            WHERE id=?
        ");
        $stmt->bind_param(
            "ssssisssiisi",
            $title,
            $description,
            $date,
            $end_date,
            $capacity,
            $event_image,
            $status,
            $contact,
            $venue_id,
            $club_id,
            $approval_status,
            $event_id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE events 
            SET title=?, event_description=?, event_date=?, end_date=?, event_capacity=?, event_status=?, contact_number=?, venue_id=?, club_id=?, approval_status=?
            WHERE id=?
        ");
        $stmt->bind_param(
            "ssssissiisi",
            $title,
            $description,
            $date,
            $end_date,
            $capacity,
            $status,
            $contact,
            $venue_id,
            $club_id,
            $approval_status,
            $event_id
        );
    }

    // Execute update
    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['success'] = "Event updated successfully.";
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $_SESSION['form_errors'] = ["Failed to update event."];
        header("Location: admin_dashboard.php");
        exit;
    }
}
?>