<?php
session_start();
include 'db_connect.php';

$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['event_id'], $_POST['receiver_id'])) {
        $errors[] = "Invalid request.";
    } else {
        $event_id = intval($_POST['event_id']);
        $sender_id = $_SESSION['user_id'];
        $receiver_id = intval($_POST['receiver_id']);

        
        $eventSQL = $conn->prepare("SELECT title FROM events WHERE id = ?");
        $eventSQL->bind_param("i", $event_id);
        $eventSQL->execute();
        $eventResult = $eventSQL->get_result()->fetch_assoc();
        $event_title = $eventResult['title'] ?? 'Event';

      
        $insertSQL = $conn->prepare("
            INSERT INTO notifications (event_id, sender_id, receiver_id, title, message, type) 
            VALUES (?, ?, ?, ?, ?, 'invitation')
        ");
        $title = "Invitation to join: " . $event_title;
        $message = "You are invited to join the event '$event_title'. Please respond to this invitation.";

        $insertSQL->bind_param("iiiss", $event_id, $sender_id, $receiver_id, $title, $message);

        if ($insertSQL->execute()) {
          header("Location: event_page.php?event_id=$event_id&invite=success");
        } else {
           header("Location: event_page.php?event_id=$event_id&invite=error");
        }
        exit();
    }
}

if (!empty($errors)) {
    foreach ($errors as $err) echo "<p style='color:red;'>$err</p>";
}
if (!empty($success)) {
    foreach ($success as $msg) echo "<p style='color:green;'>$msg</p>";

}
?>