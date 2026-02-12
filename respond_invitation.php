<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['user_id'];

if (!isset($_POST['notification_id'])) {
    header("Location: participant_inbox.php");
    exit;
}

$notification_id = (int)$_POST['notification_id'];


$stmt = $conn->prepare("
    SELECT event_id 
    FROM notifications 
    WHERE id = ? AND receiver_id = ? AND response = 'pending'
");
$stmt->bind_param("ii", $notification_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    header("Location: participant_inbox.php");
    exit;
}

$event_id = (int)$row['event_id'];


if (isset($_POST['accept'])) {

    
    $check = $conn->prepare("
        SELECT id FROM participant 
        WHERE event_id=? AND user_id=?
    ");
    $check->bind_param("ii", $event_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $insert = $conn->prepare("
            INSERT INTO participant (event_id, user_id, participant_status)
            VALUES (?, ?, 'attend')
        ");
        $insert->bind_param("ii", $event_id, $user_id);
        $insert->execute();
    }

    
    $update = $conn->prepare("
        UPDATE notifications 
        SET is_read=1, response='accepted'
        WHERE id=?
    ");
    $update->bind_param("i", $notification_id);
    $update->execute();

    header("Location: participant_inbox.php");
    exit;
}


if (isset($_POST['decline'])) {

    $update = $conn->prepare("
        UPDATE notifications 
        SET is_read=1, response='declined'
        WHERE id=?
    ");
    $update->bind_param("i", $notification_id);
    $update->execute();

    header("Location: participant_inbox.php");
    exit;
}