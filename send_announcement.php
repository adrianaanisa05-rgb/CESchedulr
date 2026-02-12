<?php
session_start();
include 'db_connect.php';

if (!isset($_POST['send_announcement'])) {
    header('Location: event_forum.php');
    exit();
}

$event_id = $_POST['event_id'];
$title = $_POST['title'];
$message = $_POST['message'];
$sender_id = $_SESSION['user_id'];


$sql = $conn->prepare("SELECT user_id FROM participant WHERE event_id = ?");
$sql->bind_param("i", $event_id);
$sql->execute();
$result = $sql->get_result();
$participants = $result->fetch_all(MYSQLI_ASSOC);


$insert = $conn->prepare(
    "INSERT INTO notifications (event_id, sender_id, receiver_id, title, message)
     VALUES (?, ?, ?, ?, ?)"
);

foreach ($participants as $p) {
    $receiver_id = $p['user_id'];
    $insert->bind_param("iiiss", $event_id, $sender_id, $receiver_id, $title, $message);
    $insert->execute();
}

header("Location: event_page.php?event_id=$event_id&announcement_sent=1");
exit();
?>