<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['event_id'], $_POST['action'], $_POST['remark'])) {
    die("Invalid request.");
}

$event_id = (int) $_POST['event_id'];
$action   = $_POST['action'];
$remark   = trim($_POST['remark']);

if (!in_array($action, ['approved', 'rejected'])) {
    die("Invalid action.");
}


$stmt = $conn->prepare("SELECT id, title, user_id FROM events WHERE id=? AND approval_status='pending'");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) die("Event not found or already processed.");


$update = $conn->prepare("UPDATE events SET approval_status=?, admin_remark=? WHERE id=?");
$update->bind_param("ssi", $action, $remark, $event_id);
$update->execute();
$update->close();


$notification_title = "Event {$action}: {$event['title']}";
$notification_message = "Your event \"{$event['title']}\" has been {$action}. Admin Remark: {$remark}";
$sender_id = $_SESSION['user_id'];       
$receiver_id = $event['user_id'];        
$type = 'announcement';

$notify = $conn->prepare("INSERT INTO notifications (event_id, sender_id, receiver_id, title, message, type) VALUES (?, ?, ?, ?, ?, ?)");
$notify->bind_param("iiisss", $event_id, $sender_id, $receiver_id, $notification_title, $notification_message, $type);
$notify->execute();
$notify->close();

header("Location: admin_approval.php?status=$action");
exit();