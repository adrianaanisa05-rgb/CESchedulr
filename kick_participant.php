<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $event_id       = intval($_POST['event_id']);
    $participant_id = intval($_POST['participant_id']); 
    $reason         = trim($_POST['reason']);
    $owner_id       = $_SESSION['user_id'];

    if (empty($reason)) {
        die("Kick reason is required.");
    }

    $checkOwner = $conn->prepare("
        SELECT id FROM events 
        WHERE id = ? AND user_id = ?
    ");
    $checkOwner->bind_param("ii", $event_id, $owner_id);
    $checkOwner->execute();
    $checkOwner->store_result();

    if ($checkOwner->num_rows === 0) {
        die("Unauthorized action.");
    }
    $checkOwner->close();

    
    $stmt = $conn->prepare("
        DELETE FROM participant 
        WHERE event_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $event_id, $participant_id);
    $stmt->execute();
    $stmt->close();

$deleteComments = $conn->prepare("
DELETE FROM comments
WHERE event_id = ? AND user_id = ?
");
$deleteComments->bind_param("ii", $event_id, $participant_id);
$deleteComments->execute();
$deleteComments->close();

    
    $deleteAnnouncements = $conn->prepare("
        DELETE FROM notifications
        WHERE event_id = ?
          AND receiver_id = ?
          AND type = 'announcement'
    ");
    $deleteAnnouncements->bind_param("ii", $event_id, $participant_id);
    $deleteAnnouncements->execute();
    $deleteAnnouncements->close();

    $title   = "Removed from Event";
    $message = "You have been removed from the event.\n\nReason:\n" . $reason;

    $notify = $conn->prepare("
        INSERT INTO notifications
        (event_id, sender_id, receiver_id, title, message, type)
        VALUES (?, ?, ?, ?, ?, 'action')
    ");
    $notify->bind_param(
        "iiiss",
        $event_id,
        $owner_id,
        $participant_id,
        $title,
        $message
    );
    $notify->execute();
    $notify->close();

    header("Location: event_page.php?event_id=" . $event_id . "&message=kicked");
    exit();
}