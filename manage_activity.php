<?php
session_start();
include 'db_connect.php';
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? [];
$current_page = basename($_SERVER['PHP_SELF']);
$user_id=$_SESSION['user_id'];
unset($_SESSION['errors'], $_SESSION['success']);

$eventSQL = $conn->prepare("
    SELECT id, title, event_date,event_status,approval_status 
    FROM events 
    WHERE user_id = ?
    ORDER BY event_date ASC
");
$eventSQL->bind_param("i", $user_id);
$eventSQL->execute();
$events = $eventSQL->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity'])) {

    $event_id = $_POST['event_id'];
    $activity_date = $_POST['activity_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    if (empty($event_id) || empty($activity_date) || empty($start_time) || empty($end_time)) {
        $_SESSION['errors'][] = "Please fill in all required fields.";
         header("Location: manage_activity.php?event_id=". $event_id);
         exit;
    } else {
      
    if ($start_time >= $end_time) {
    $_SESSION['errors'][] = "End time must be later than start time.";
    header("Location: manage_activity.php?event_id=". $event_id);
    exit;
}
       $checkSQL = $conn->prepare("
    SELECT 1
    FROM activity
    WHERE event_id = ?
      AND activity_date = ?
      AND start_time < ?
      AND end_time > ?
");
       $checkSQL->bind_param(
    "isss",
    $event_id,
    $activity_date,
    $end_time,     
    $start_time    
);
        $checkSQL->execute();
        $result = $checkSQL->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['errors'][] = "This time slot is already occupied by another activity on this date.";
            header("Location: manage_activity.php?event_id=". $event_id);
            exit;

        }else {
        $insertSQL = $conn->prepare("
            INSERT INTO activity 
            (event_id, title, description, activity_date, start_time, end_time, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertSQL->bind_param(
            "isssssi",
            $event_id,
            $title,
            $description,
            $activity_date,
            $start_time,
            $end_time,
            $user_id
        );

        if ($insertSQL->execute()) {
            $_SESSION['success'][] = "Activity added successfully.";
        } else {
            $_SESSION['errors'][] = "Failed to add activity.";
        }
    header("Location: manage_activity.php?event_id=". $event_id);
    exit;
    }
    }
}



$selected_event = $_POST['event_id'] ?? $_GET['event_id'] ?? null;
$activitiesByDay = [];


$eventLock = null;

if ($selected_event) {
    $lockSQL = $conn->prepare("
        SELECT event_status, approval_status
        FROM events
        WHERE id = ? AND user_id = ?
    ");
    $lockSQL->bind_param("ii", $selected_event, $user_id);
    $lockSQL->execute();
    $eventLock = $lockSQL->get_result()->fetch_assoc();
}

$isLocked = false;

if ($eventLock) {
    $isLocked =
        $eventLock['event_status'] === 'concluded' ||
        $eventLock['approval_status'] === 'rejected';
}

if ($selected_event) {
    $activitySQL = $conn->prepare("
        SELECT *
        FROM activity
        WHERE event_id = ?
        ORDER BY activity_date ASC, start_time ASC
    ");
    $activitySQL->bind_param("i", $selected_event);
    $activitySQL->execute();
    $activities = $activitySQL->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($activities as $activity) {
        $activitiesByDay[$activity['activity_date']][] = $activity;
    }
}

if (isset($_POST['update_activity']) && !empty($_POST['edit_activity_id'])) {
    $edit_id = $_POST['edit_activity_id'];
    $title = $_POST['edit_title'];
    $description = $_POST['edit_description'];
    $activity_date = $_POST['edit_activity_date'];
    $start_time = $_POST['edit_start_time'];
    $end_time = $_POST['edit_end_time'];


     if (empty($activity_date) || empty($start_time) || empty($end_time)) {
        $_SESSION['errors'][] = "Please fill in all required fields.";
    } else {
     $checkSQL = $conn->prepare("
    SELECT 1 FROM activity
    WHERE event_id = ?
      AND activity_date = ?
      AND id != ?
      AND start_time < ?
      AND end_time > ?
");

$checkSQL->bind_param(
    "isiss",
    $selected_event,     
    $activity_date,      
    $edit_id,            
    $end_time,           
    $start_time          
);

$checkSQL->execute();
$result = $checkSQL->get_result();

if ($result->num_rows > 0) {
    $_SESSION['errors'][] = "This time slot is already occupied by another activity on this date.";
}
        else {
    $updateSQL = $conn->prepare("
        UPDATE activity
        SET title = ?, description = ?, activity_date = ?, start_time = ?, end_time = ?
        WHERE id = ? AND event_id = ?
    ");
    $updateSQL->bind_param("sssssii", $title, $description, $activity_date, $start_time, $end_time, $edit_id, $selected_event);
    if ($updateSQL->execute()) {
       $_SESSION['success'][] = "Activity updated successfully.";
    } else {
        $_SESSION['errors'][] = "Failed to update activity.";
        
    }
    header("Location: manage_activity.php?event_id=". $selected_event);
    exit;
}
    }
  }

if (isset($_POST['delete_activity']) && !empty($_POST['delete_activity_id'])) {
    $del_id = $_POST['delete_activity_id'];
    $delSQL = $conn->prepare("DELETE FROM activity WHERE id = ? AND event_id = ? AND user_id=?");
    $delSQL->bind_param("iii", $del_id, $selected_event, $user_id);
    if ($delSQL->execute()) {
        $_SESSION['success'][] = "Activity deleted successfully.";
    } else {
        $_SESSION['errors'][] = "Failed to delete activity.";
        
    }
    header("Location: manage_activity.php?event_id=" . $selected_event);
    exit;
  }



?>
<!DOCTYPE html> 
<html lang="en"> <head> 
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title>Event Dashboard</title> 
<!-- Google Material Icons --> 
 <link rel="stylesheet" href="styles.css?v=4"> 
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" 
 rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>

<?php
if ($_SESSION['user_type'] === 'admin') {
    include 'partials/sidebar_admin.php';
} elseif ($_SESSION['user_type'] === 'organizer') {
    include 'partials/sidebar_organizer.php';
} else {
    include 'partials/sidebar_participant.php';
}
?>

<div class="main-content" id="mainContent">
    <div class="header">
        <h1 class="page-title">My Activitiy Timeline</h1>

<div class="container mt-5">

<h3 class="mb-4">Manage Activity Timeline</h3>


<?php foreach ($errors as $e): ?>
<div class="alert alert-danger"><?= $e ?></div>
<?php endforeach; ?>

<?php foreach ($success as $s): ?>
<div class="alert alert-success"><?= $s ?></div>
<?php endforeach; ?>


<form method="GET" class="mb-4">
<label class="form-label fw-bold">Select Event</label>
<select name="event_id" class="form-select" onchange="this.form.submit()">
<option value="">-- Select Event --</option>
<?php foreach ($events as $event): ?>
<option value="<?= $event['id']; ?>"
<?= ($selected_event == $event['id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($event['title']); ?>
</option>
<?php endforeach; ?>
</select>
</form>

<?php if ($selected_event): ?>

<?php if ($isLocked): ?>
<div class="alert alert-warning text-center">
    This event is <strong>
    <?= $eventLock['event_status'] === 'concluded' ? 'concluded' : 'rejected' ?>
    </strong>. Activities can no longer be modified.
</div>
<?php endif; ?>

<div class="card mb-4 shadow-sm">
<div class="card-header bg-primary text-white">
Add Activity
</div>

<div class="card-body">
<form method="POST">

<input type="hidden" name="event_id" value="<?= $selected_event ?>">

<div class="row">
<div class="col-md-4 mb-3">
<label class="form-label">Activity Date</label>
<input type="date" name="activity_date" class="form-control" required <?= $isLocked ? 'disabled' : '' ?>>
</div>

<div class="col-md-4 mb-3">
<label class="form-label">Start Time</label>
<input type="time" name="start_time" class="form-control" required <?= $isLocked ? 'disabled' : '' ?>>
</div>

<div class="col-md-4 mb-3">
<label class="form-label">End Time</label>
<input type="time" name="end_time" class="form-control" required <?= $isLocked ? 'disabled' : '' ?>>
</div>
</div>

<div class="mb-3">
<label class="form-label">Title</label>
<input type="text" name="title" class="form-control" placeholder="Example: Ceremonial speech" <?= $isLocked ? 'disabled' : '' ?>>
</div>

<div class="mb-3">
<label class="form-label">Description</label>
<textarea name="description" class="form-control" rows="5" placeholder="Insert activity description here" <?= $isLocked ? 'disabled' : '' ?>></textarea>
</div>

<button type="submit" name="add_activity" class="btn btn-success" <?= $isLocked ? 'disabled' : '' ?>>
+ Add Activity
</button>

</form>
</div>
</div>


<?php foreach ($activitiesByDay as $date => $items): ?>
<div class="card mb-3 shadow-sm">
<div class="card-header fw-bold">
📅 <?= date("F d, Y", strtotime($date)); ?>
</div>

<ul class="list-group list-group-flush">
<?php foreach ($items as $item): ?>
<li class="list-group-item d-flex justify-content-between align-items-start">
    <div>
        <strong><?= date("h:i A", strtotime($item['start_time'])); ?> – <?= date("h:i A", strtotime($item['end_time'])); ?></strong><br>
        <?= htmlspecialchars($item['title']); ?>
        <?php if (!empty($item['description'])): ?>
        <div class="text-muted small"><?= nl2br(htmlspecialchars($item['description'])); ?></div>
        <?php endif; ?>
    </div>

    <div>
        <button type="button" class="btn btn-sm btn-primary" <?= $isLocked ? 'disabled' : '' ?> data-bs-toggle="modal" data-bs-target="#editModal<?= $item['id'] ?>">
            Edit
        </button>

        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this activity?');">
            <input type="hidden" name="delete_activity_id" value="<?= $item['id'] ?>">
            <button type="submit" name="delete_activity" class="btn btn-sm btn-danger">Delete</button>
        </form>
    </div>
</li>


<div class="modal fade" id="editModal<?= $item['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $item['id'] ?>" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel<?= $item['id'] ?>">Edit Activity</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="event_id" value="<?= $selected_event ?>">
            <input type="hidden" name="edit_activity_id" value="<?= $item['id'] ?>">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="edit_title" class="form-control" value="<?= htmlspecialchars($item['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="edit_description" class="form-control" rows="3"><?= htmlspecialchars($item['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="edit_activity_date" class="form-control" value="<?= $item['activity_date']; ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="edit_start_time" class="form-control" value="<?= date('H:i', strtotime($item['start_time'])); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">End Time</label>
                    <input type="time" name="edit_end_time" class="form-control" value="<?= date('H:i', strtotime($item['end_time'])); ?>" required>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update_activity" class="btn btn-success" <?= $isLocked ? 'disabled' : '' ?>>Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>
</ul>
</div>



<?php endforeach; ?>

<?php endif; ?>

</div>



</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
const mainContent = document.getElementById("mainContent");


sidebar.addEventListener("mouseenter", () => {
    sidebar.classList.remove("collapsed");
    mainContent.style.marginLeft = "250px"; 
});

sidebar.addEventListener("mouseleave", () => {
    sidebar.classList.add("collapsed");
    mainContent.style.marginLeft = "60px"; 
});


const toggleBtn = document.getElementById("sidebarToggle");
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
    if (sidebar.classList.contains("collapsed")) {
        mainContent.style.marginLeft = "60px";
    } else {
        mainContent.style.marginLeft = "250px";
    }
});
});
</script>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" 
 crossorigin="anonymous">
</script>
</body> 
</html> 