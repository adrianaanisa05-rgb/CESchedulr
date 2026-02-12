<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['event_id'])) {
    die("Event ID not provided");
}

$event_id = intval($_POST['event_id']);
$user_id = $_SESSION['user_id'];

$ownerCheck = $conn->prepare("SELECT user_id FROM events WHERE id = ?");
$ownerCheck->bind_param("i", $event_id);
$ownerCheck->execute();
$ownerCheck->bind_result($event_owner_id);
$ownerCheck->fetch();
$ownerCheck->close();

if ($event_owner_id === null) {
    die("Event not found.");
}

if ($event_owner_id == $user_id) {
    header("Location: event_page.php?event_id=" . $event_id);
    exit;
}


$cap = $conn->prepare("SELECT event_capacity FROM events WHERE id = ?");
$cap->bind_param("i", $event_id);
$cap->execute();
$cap->bind_result($max_capacity);
$cap->fetch();
$cap->close();

if ($max_capacity === null) {
    die("Event not found.");
}


$cap_stmt = $conn->prepare("SELECT COUNT(*) FROM participant WHERE event_id = ? AND request_status='accepted'");
$cap_stmt->bind_param("i", $event_id);
$cap_stmt->execute();
$cap_stmt->bind_result($current_count);
$cap_stmt->fetch();
$cap_stmt->close();



if ($current_count >= $max_capacity) {
    echo "<script>
            alert('Sorry! This event is already full.');
            window.location.href = 'organizer_event.php';
          </script>";
    exit;
}



$check = $conn->prepare("SELECT request_status FROM participant WHERE event_id = ? AND user_id = ?");
$check->bind_param("ii", $event_id, $user_id);
$check->execute();
$check->bind_result($approval_status);

if ($check->fetch()) {
    $check->close();

    if ($approval_status === 'pending') {
        
        echo "<script>
            alert('Your request is still pending approval.');
            window.location.href = 'event_forum.php';
        </script>";
        exit;
    }

    if ($approval_status === 'accepted') {
        
        header("Location: event_page.php?event_id=$event_id");
        exit;
    }

    if ($approval_status === 'rejected') {
        
        echo "<script>
            alert('Your request was rejected by the organizer.');
            window.location.href = 'event_forum.php';
        </script>";
        exit;
    }
}
$check->close();



$insert = $conn->prepare("
    INSERT INTO participant 
    (event_id, user_id, full_name, gender, participant_phone, participant_status, remarks, request_status)
    VALUES (?, ?, '', NULL, NULL, 'attend', NULL, 'pending')
");
$insert->bind_param("ii", $event_id, $user_id);

if ($insert->execute()) {
    echo "<script>
        alert('Join request sent! Please wait for organizer approval.');
        window.location.href = 'event_forum.php';
    </script>";
    exit;
} else {
    echo 'Error joining event: ' . $conn->error;
}
?>