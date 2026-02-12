<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $event_id = intval($_POST['event_id']);
    $participant_id = intval($_POST['participant_id']);
    $status = $_POST['participant_status'];
    $remarks = !empty($_POST['remarks']) ? $_POST['remarks'] : null;

   
    $ownerCheck = $conn->prepare("SELECT user_id FROM events WHERE id = ?");
    $ownerCheck->bind_param("i", $event_id);
    $ownerCheck->execute();
    $ownerCheck->bind_result($owner_id);
    $ownerCheck->fetch();
    $ownerCheck->close();

    if ($owner_id != $_SESSION['user_id']) {
        die("Unauthorized access.");
    }

    $updateSQL = $conn->prepare("UPDATE participant SET participant_status = ?, remarks = ? WHERE event_id = ? AND user_id = ?");
    $updateSQL->bind_param("ssii", $status, $remarks, $event_id, $participant_id);
    
    if ($updateSQL->execute()) {
        header("Location: event_page.php?event_id=$event_id&message=updated");
    } else {
        die("Error updating participant: " . $updateSQL->error);
    }
}