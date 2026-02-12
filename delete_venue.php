<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['venue_id'])) {
    $venue_id = intval($_POST['venue_id']); // sanitize

    $stmt = $conn->prepare("DELETE FROM venue WHERE venue_id=? AND user_id=?");
    $stmt->bind_param("ii", $venue_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        // Redirect back to Manage Venue page with success
        header("Location: manage_venue.php?message=deleted");
        exit;
    } else {
        // Redirect back with error
        header("Location: manage_venue.php?error=delete_failed");
        exit;
    }
} else {
    // If accessed directly without POST
    header("Location: manage_venue.php");
    exit;
}
?>