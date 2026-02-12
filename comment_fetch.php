<?php
session_start();
include 'db_connect.php';

$event_id = $_GET['event_id'];
$user_id = $_SESSION['user_id'];
$isOwner = ($_SESSION['user_type'] === 'admin');

$sql = $conn->prepare("
    SELECT c.*, u.username, u.users_image
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.event_id = ?
    ORDER BY c.created_at ASC
");
$sql->bind_param("i", $event_id);
$sql->execute();
$comments = $sql->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'comments' => $comments,
    'currentUser' => $user_id,
    'isOwner' => $isOwner
]);