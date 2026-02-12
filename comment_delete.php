<?php
session_start();
include 'db_connect.php';

$comment_id = $_POST['comment_id'];
$isOwner = $_POST['is_owner'] === '1';
$user_id = $_SESSION['user_id'];

if ($isOwner) {
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
} else {
    $stmt = $conn->prepare("
        DELETE FROM comments
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $comment_id, $user_id);
}

$stmt->execute();
echo json_encode(['success' => true]);