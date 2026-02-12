<?php
session_start();
include 'db_connect.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


if (!isset($_POST['participant_id'], $_POST['action'])) {
    die("Invalid request");
}

$participant_id = (int) $_POST['participant_id'];
$action = $_POST['action'];


$stmt = $conn->prepare(
    "SELECT event_id, user_id FROM participant WHERE id = ?"
);
$stmt->bind_param("i", $participant_id);
$stmt->execute();
$stmt->bind_result($event_id, $participant_user_id);
$stmt->fetch();
$stmt->close();


$stmt = $conn->prepare(
    "SELECT user_id, title FROM events WHERE id = ?"
);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$stmt->bind_result($organizer_id, $event_title);
$stmt->fetch();
$stmt->close();


if ($_SESSION['user_id'] != $organizer_id) {
    die("You are not authorized to perform this action.");
}


if ($action === "accept") {
    $status = 'accepted';
    $response = 'accepted';
} elseif ($action === "reject") {
    $status = 'rejected';
    $response = 'declined';
} else {
    die("Invalid action");
}


$update = $conn->prepare(
    "UPDATE participant SET request_status = ? WHERE id = ?"
);
$update->bind_param("si", $status, $participant_id);

if ($update->execute()) {

    
    $notify = $conn->prepare(
        "INSERT INTO notifications
        (event_id, sender_id, receiver_id, title, message, type, response)
        VALUES (?, ?, ?, ?, ?, 'action', ?)"
    );

    $title = "Participation Request " . ucfirst($status);
    $message = "Your request to join the event \"{$event_title}\" has been {$status}.";

    $notify->bind_param(
        "iiisss",
        $event_id,
        $_SESSION['user_id'],      
        $participant_user_id,     
        $title,
        $message,
        $response
    );

    $notify->execute();
    $notify->close();

    header("Location: event_page.php?event_id=" . $event_id);
    exit();

} else {
    echo "Error updating request: " . $conn->error;
}
?>