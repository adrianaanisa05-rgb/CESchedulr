<?php
session_start();
include 'db_connect.php';

$comment_id = $_POST['comment_id'];
$comment = trim($_POST['comment']);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    UPDATE comments
    SET comment = ?, updated_at = NOW()
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("sii", $comment, $comment_id, $user_id);
$stmt->execute();

echo json_encode(['success' => true, 'comment' => htmlspecialchars($comment)]);