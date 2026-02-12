<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) exit;

$event_id = $_POST['event_id'];
$comment = trim($_POST['comment']);
$user_id = $_SESSION['user_id'];

if ($comment === '') exit;

$stmt = $conn->prepare("
    INSERT INTO comments (event_id, user_id, comment)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iis", $event_id, $user_id, $comment);
$stmt->execute();

echo json_encode([
    'id' => $stmt->insert_id,
    'username' => $_SESSION['username'],
    'comment' => htmlspecialchars($comment),
    'created_at' => date("Y-m-d H:i:s"),
    'user_id' => $user_id
]);