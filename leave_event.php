<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_event'], $_POST['event_id'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = intval($_POST['event_id']);

    
    $sql = $conn->prepare("DELETE FROM participant WHERE user_id = ? AND event_id = ?");
    $sql->bind_param("ii", $user_id, $event_id);

    if ($sql->execute()) {
         $deleteComments = $conn->prepare("
        DELETE FROM comments
        WHERE event_id = ? AND user_id = ?
    ");
    $deleteComments->bind_param("ii", $event_id, $user_id);
    $deleteComments->execute();
    $deleteComments->close();
    $status_message = "You have successfully left the event.";

        $deleteAnnouncements = $conn->prepare("
        DELETE FROM notifications
        WHERE event_id = ?
          AND receiver_id = ?
          AND type = 'announcement'
    ");
    $deleteAnnouncements->bind_param("ii", $event_id, $user_id);
    $deleteAnnouncements->execute();
    $deleteAnnouncements->close();

    $notif = $conn->prepare("
            INSERT INTO notifications (receiver_id, sender_id, event_id, type, title, message, created_at)
            VALUES (?, ?, ?, 'action', 'Event Left', ?, NOW())
        ");
        $notif->bind_param("iiis", $user_id, $user_id, $event_id, $status_message);
        $notif->execute();

    } else {
        $status_message = "Failed to leave the event: " . $sql->error;

        // Add failure notification to inbox
        $notif = $conn->prepare("
            INSERT INTO notifications (receiver_id, sender_id, event_id, type, title, message, created_at)
            VALUES (?, ?, ?, 'action', 'Event Leave Failed', ?, NOW())
        ");
        $notif->bind_param("iiis", $user_id, $user_id, $event_id, $status_message);
        $notif->execute();
    }

    header("Location: participant_inbox.php"); 
    exit();
} else {
    header("Location: event_forum.php");
    exit();
}
?>