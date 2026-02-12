<?php
session_start();
include 'db_connect.php';
$errors=[];
$current_page = basename($_SERVER['PHP_SELF']);
$create_message=[];

function displayValue($value) {
    return !empty($value) ? htmlspecialchars($value) : '<span style="color:red;">Not provided.</span>';
}

if (!isset($_GET['event_id'])) {
    header("Location: event_forum.php");
    exit();
}

$event_id = intval($_GET['event_id']);
$participant_id = $_SESSION['user_id'];


$sql= $conn->prepare("SELECT e.*,u.username,venue_name,venue_address,venue_city,venue_postcode,venue_image,c.club_name,c.club_email,c.club_phone
 FROM events e 
 JOIN users u ON e.user_id=u.id 
LEFT JOIN venue v ON e.venue_id = v.venue_id
LEFT JOIN club c ON e.club_id = c.id
 WHERE e.id=?
 ");
$sql->bind_param("i", $event_id);
$sql->execute();
$result=$sql->get_result();
$event=$result->fetch_assoc();

$activitySQL = $conn->prepare("SELECT * FROM activity WHERE event_id = ? ORDER BY activity_date ASC, start_time ASC");
$activitySQL->bind_param("i", $event_id);
$activitySQL->execute();
$activities = $activitySQL->get_result()->fetch_all(MYSQLI_ASSOC);


$activitiesByDate = [];
foreach ($activities as $activity) {
    $activitiesByDate[$activity['activity_date']][] = $activity;
}

if(!$event){
    header("Location: event_forum.php");
    exit();
}

$isEventOwner = (
    isset($_SESSION['user_id'], $_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'organizer' &&
    $_SESSION['user_id'] == $event['user_id']
);

if ($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST['create_participant'])){
$full_name=$_POST['full_name'];
$gender=$_POST['gender'];
$participant_phone=$_POST['participant_phone'];
$participant_status=$_POST['participant_status'];

if ($participant_status === "attend") {
        $remarks = null;
} else{
 $remarks = !empty($_POST['remarks']) ? $_POST['remarks'] : null;
}
$sql= $conn->prepare(" UPDATE participant 
        SET full_name = ?, gender = ?, participant_phone = ?, participant_status = ?, remarks = ?
        WHERE event_id = ? AND user_id = ?");
$sql->bind_param("sssssii", $full_name, $gender, $participant_phone, $participant_status, $remarks, $event_id, $participant_id);
if ($sql->execute()){
    $create_message[] = "Participant details update successfully!";
}
else{
    $errors[]="Error updating participant details. Please try again: " . $sql->error;
}
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['conclude_event']) && $isEventOwner) {
    
    $updateEvent = $conn->prepare("UPDATE events SET event_status = 'concluded' WHERE id = ?");
    $updateEvent->bind_param("i", $event_id);
    
    if ($updateEvent->execute()) {
       
        $announcementTitle = "Event Concluded";
        $announcementMessage = "The event '".htmlspecialchars($event['title'])."' has been concluded.";
        
        $participantsSQL = $conn->prepare("SELECT user_id FROM participant WHERE event_id = ?");
        $participantsSQL->bind_param("i", $event_id);
        $participantsSQL->execute();
        $participantsResult = $participantsSQL->get_result();
        $sender_id = $_SESSION['user_id'];
        while ($participant = $participantsResult->fetch_assoc()) {
            $insertNotification = $conn->prepare("INSERT INTO notifications (event_id,sender_id, receiver_id, title, message, type) VALUES (?, ?, ?, ?, ?, 'announcement')");
            $insertNotification->bind_param("iiiss", $event_id,$sender_id, $participant['user_id'], $announcementTitle, $announcementMessage);
            $insertNotification->execute();
        }

        header("Location: event_page.php?event_id=$event_id&concluded=1");
        exit();
    } else {
        $errors[] = "Failed to conclude the event: " . $updateEvent->error;
    }
}

$selectsql= $conn->prepare("SELECT p.*, u.username,u.phone_number FROM participant p JOIN users u ON p.user_id=u.id WHERE p.event_id = ? AND request_status='accepted'");
$selectsql->bind_param("i", $event_id);
$selectsql->execute();
$participants = $selectsql->get_result()->fetch_all(MYSQLI_ASSOC);

$participantSQL = $conn->prepare("
    SELECT p.*, u.username, u.phone_number 
    FROM participant p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.event_id = ? AND p.user_id = ?
");
$participantSQL->bind_param("ii", $event_id, $participant_id);
$participantSQL->execute();
$currentParticipant = $participantSQL->get_result()->fetch_assoc();

$countsql=$conn->prepare("SELECT COUNT(*) FROM participant WHERE event_id = ? AND request_status='accepted'");
$countsql->bind_param("i",$event_id);
$countsql->execute();
$countsql->bind_result($participant_count);
$countsql->fetch();
$countsql->close();

$requestSQL = $conn->prepare("
    SELECT p.*, u.username, u.phone_number
    FROM participant p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.event_id = ? AND p.request_status = 'pending'
");
$requestSQL->bind_param("i", $event_id);
$requestSQL->execute();
$requests = $requestSQL->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->query("
    UPDATE events
    SET event_status = 'concluded'
    WHERE end_date <= NOW()
      AND event_status != 'concluded'
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title>Participant Dashboard</title>
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
 <link rel="stylesheet" href="styles.css?v=4"> 
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
<h1 class="page-title">Event Page</h1>

<div class="container mt-4">

    <!-- Event Box -->
     
    <div class="event-details-box shadow p-4 mb-4">
        <h2 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h2>
        <?php if (!empty($event['event_image'])): ?>
        <img src="uploads/<?php echo htmlspecialchars($event['event_image']);?>"
         alt="Event Image" width="350" height="400" class="event-image mb-3">
        <?php else: ?>
        <div class="event-image mb-3 text-muted" style="width:350px; height:400px; display:flex; align-items:center; justify-content:center; border:1px solid #ccc;">
            No Poster Provided
        </div>
        <?php endif; ?>
        <div class="event-info">
  <p>
    <strong> Date:</strong> <?= !empty($event['event_date']) ? date('l, F j, Y h:i A', strtotime($event['event_date'])) : 'Not provided'; ?> 
    - 
     <?= !empty($event['end_date']) ? date('l, F j, Y h:i A', strtotime($event['end_date'])) : 'Not provided'; ?>
  </p>
        <p><strong>Description:</strong> <?= displayValue($event['event_description']); ?></p>
        <p><strong>Organized By:</strong> <?= displayValue($event['club_name']); ?></p>
        <p><strong>Club Email:</strong> <?= displayValue($event['club_email']); ?></p>
        <p><strong>Club Phone:</strong> <?= displayValue($event['club_phone']); ?></p>

         <?php if (!empty($event['venue_image'])): ?>
          <p><strong>Venue Image:</strong></p>
        <img src="venue/<?php echo htmlspecialchars($event['venue_image']);?>"
         alt="Venue Image" width="350" height="400" class="event-image mb-3">
        <?php else: ?>
        <p><strong>Venue Image:</strong></p>
        <div class="event-image mb-3 text-muted" style="width:350px; height:400px; display:flex; align-items:center; justify-content:center; border:1px solid #ccc;">
            No Image Provided
        </div>
        <?php endif; ?>
        <p><strong>Venue Name:</strong> <?= displayValue($event['venue_name']); ?></p>
        <p><strong>Address:</strong> <?= displayValue($event['venue_address']); ?></p>
        <p><strong>City:</strong> <?= displayValue($event['venue_city']); ?></p>
        <p><strong>Postcode:</strong> <?= displayValue($event['venue_postcode']); ?></p>
        <p><strong>Created By:</strong> <?= displayValue($event['username']); ?></p>
    </div>
        </div>

<div class="container mt-4">
    <h3>Event Activities</h3>

    <?php if (empty($activitiesByDate)): ?>
        <div class="alert alert-info">
            No activities have been scheduled for this event yet.
        </div>
    <?php else: ?>
        <?php foreach ($activitiesByDate as $date => $activityList): ?>
            <div class="activity-date-box mb-3">
                <h5><?= date("l, F d, Y", strtotime($date)); ?></h5>
            </div>
            <div class="activity-row-wrapper">
                <div class="activity-row">
                    <?php foreach ($activityList as $activity): ?>
                        <div class="activity-item activity-timeline-item mb-3 p-3 shadow-sm">
                            <div class="activity-time mb-1">
                                <strong><?= date("h:i A", strtotime($activity['start_time'])) ?> - <?= date("h:i A", strtotime($activity['end_time'])) ?></strong>
                            </div>
                            <div class="activity-title mb-1">
                                <strong><?= htmlspecialchars($activity['title']) ?></strong>
                            </div>
                            <div class="activity-desc text-muted">
                                <?= htmlspecialchars($activity['description'] ?? 'No description provided') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($activityList) > 4): ?>
                    <div class="activity-controls mt-2">
                        <button class="btn btn-secondary prev-btn">Prev</button>
                        <button class="btn btn-primary next-btn">Next</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

        <!-- Participant Count Box -->
        <div class="participant-counter mt-4">
            <h4> <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 26px; margin-right: 5px;">group</span>
            Participants Joined</h4>
            <div class="counter-box">
                <span class="count-number">
                    <?php echo $participant_count; ?>
                </span>
                <span class="count-separator">/</span>
                <span class="count-total">
                    <?php echo $event['event_capacity']; ?>
                </span>
            </div>
        </div>
    </div>

</div>
<?php if ($event['event_status'] !== 'concluded'): ?>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
+Update Participant details
</button>
<?php endif ; ?>

<?php if ($isEventOwner && $event['event_status'] !== 'concluded'): ?>
<a><button class="btn btn-warning mt-3" data-bs-toggle="modal" data-bs-target="#announcementModal">
    Send Announcement to Participants 
</button></a>
 <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#inviteModal">
  Send Invitation
</button>
 <?php endif; ?>
 <?php if ($isEventOwner && $event['event_status'] !== 'concluded'): ?>
    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to conclude this event? This action cannot be undone.');">
        <input type="hidden" name="conclude_event" value="1">
        <button type="submit" class="btn btn-danger mt-3">Conclude Event</button>
    </form>
<?php endif; ?>

<div class="modal fade" id="inviteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <form method="POST" action="send_invitation.php">
        <div class="modal-header">
          <h5 class="modal-title">Invite Participant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

        </div>

        <div class="modal-body">

          <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

          <div class="mb-3">
            <label class="form-label">Select User to Invite</label>
            <select name="receiver_id" class="form-select" required>
              <option value="" disabled selected>Select user</option>

              <?php
              // Fetch inviteable users
              $inviteSQL = $conn->prepare("
                 SELECT id, username FROM users
                 WHERE id NOT IN 
                 (SELECT user_id FROM participant WHERE event_id = ?) AND id NOT IN 
                 (SELECT receiver_id FROM notifications WHERE event_id = ? AND type = 'invitation'
                 AND response = 'pending')
                  AND id != ?");
              $inviteSQL->bind_param("iii", $event_id, $event_id, $_SESSION['user_id']);
              $inviteSQL->execute();
              $inviteResult = $inviteSQL->get_result();

              while ($user = $inviteResult->fetch_assoc()):
              ?>
                <option value="<?php echo $user['id']; ?>">
                    <?php echo htmlspecialchars($user['username']); ?>
                </option>
              <?php endwhile; ?>

            </select>
          </div>

        </div>

        <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary">Send Invitation</button>
        </div>

      </form>

    </div>
  </div>
</div>


<?php if (isset($_GET['announcement_sent']) && $_GET['announcement_sent'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
         Announcement has been sent successfully to all participants.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['message']) && $_GET['message'] === 'updated'): ?>
<div class="alert alert-success text-center">
    Participant status updated successfully.
</div>
<?php endif; ?>

<?php if (isset($_GET['message']) && $_GET['message'] === 'kicked'): ?>
<div class="alert alert-warning text-center">
    Participant has been kicked and notified.
</div>
<?php endif; ?>

<?php if (isset($_GET['invite'])): ?>
    <?php if ($_GET['invite'] === 'success'): ?>
        <div class="alert alert-success text-center">
            Invitation sent successfully.
        </div>
    <?php elseif ($_GET['invite'] === 'error'): ?>
        <div class="alert alert-danger text-center">
            Failed to send invitation. Please try again.
        </div>
    <?php endif; ?>
<?php endif; ?>

 <?php if (!empty($create_message)): ?>
    <ul style="color: green; text-align:center; list-style:none; padding:0;">
      <?php foreach ($create_message as $msg): ?>
        <li><?php echo $msg; ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <ul style="color:red; text-align:center; list-style:none; padding:0;">
      <?php foreach($errors as $error): ?>
        <li><?php echo $error; ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (isset($_GET['concluded']) && $_GET['concluded'] == 1): ?>
<div class="alert alert-success text-center">
    This event has been concluded. Participants have been notified.
</div>
<?php endif; ?>



<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="staticBackdropLabel">Participant details </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="event_page.php?event_id=<?php echo $event_id; ?>">
    <div class="mb-3">
         <label for="FullName" class="form-label">Full Name</label>
         <input
            type="text"
            name="full_name"
            id="FullName"
            class="form-control"
            placeholder="Enter Your Full Name"
            value="<?= htmlspecialchars($currentParticipant['full_name'] ?? '') ?>"
            required/>
</div>
<div class="mb-3">
         <label for="Gender" class="form-label">Gender</label>
       <select name="gender" id="Gender" class="form-select" required>
          <option value="" disabled <?= empty($currentParticipant['gender']) ? 'selected' : '' ?>>Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select>
</div>

<div class="mb-3">
    <label for="PhoneNumber" class="form-label">Phone Number</label>
    <input type="tel"
           class="form-control"
           name="participant_phone"
           id="PhoneNumber"
           inputmode="numeric"
           pattern="^\+?[0-9]{10,15}$"
           placeholder="Phone Number Example: 0123456789 or +60123456789"
           value="<?= htmlspecialchars($currentParticipant['participant_phone'] ?? '') ?>"
           required>
</div>

<div class="mb-3">
         <label for="Status" class="form-label">Select Participation status</label>
       <select name="participant_status" id="Status" class="form-select" required>
        <option value="" disabled <?= empty($currentParticipant['participant_status']) ? 'selected' : '' ?>>Select Status</option>
        <option value="attend">attend</option>
        <option value="absent">absent</option>
        <option value="late">late</option>
    </select>
</div>

<div class="mb-3">
<div class="mb-3" id="remarksContainer" style="display: <?= !empty($currentParticipant['remarks']) ? 'block' : 'none' ?>;">
    <label for="remark" class="form-label">Remarks</label>
    <textarea class="form-control"
              name="remarks"
              id="remark"
              rows="5"
              placeholder="Enter any remarks here"><?= htmlspecialchars($currentParticipant['remarks'] ?? '') ?></textarea>
</div>

</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="Submit" name="create_participant" class="btn btn-primary">Submit</button>
      </div>
</form>
    </div>
  </div>
</div>

<div class="modal fade" id="announcementModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <form method="POST" action="send_announcement.php">
        <div class="modal-header">
          <h5 class="modal-title">Send Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

          <div class="mb-3">
            <label class="form-label">Announcement Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="5" required></textarea>
          </div>

        </div>

        <div class="modal-footer">
          <button type="submit" name="send_announcement" class="btn btn-warning">Send</button>
        </div>

        
      </form>

    </div>
  </div>
</div>


<?php if ($isEventOwner): ?>
<h3>Participant Requests</h3>
<?php if (!empty($requests)): ?>
<table class="table table-light table-hover">
    <thead>
        <tr>
            <th>No</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Gender</th>
            <th>User Phone Number</th>
            <th>Phone</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; foreach ($requests as $req): ?>
        <tr>
            <td><?= $i++; ?></td>
            <td><?= htmlspecialchars($req['username']); ?></td>
            <td><?= htmlspecialchars($req['full_name'] ?: '-') ?></td>
            <td><?= htmlspecialchars($req['gender'] ?: '-') ?></td>
            <td><?= htmlspecialchars($req['phone_number'] ?: '-') ?></td>
            <td><?= htmlspecialchars($req['participant_phone'] ?: '-') ?></td>
            <td>
                <form method="POST" action="handle_request.php" style="display:inline-block;">
                    <input type="hidden" name="participant_id" value="<?= $req['id'] ?>">
                    <input type="hidden" name="action" value="accept">
                    <button type="submit" class="btn btn-success btn-sm">Accept</button>
                </form>
                <form method="POST" action="handle_request.php" style="display:inline-block;">
                    <input type="hidden" name="participant_id" value="<?= $req['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>No pending participant requests.</p>
<?php endif; ?>
<?php endif; ?>

<h3> Accepted Participant </h3>
<?php if(!empty($participants)): ?>
<table class="table table-light table-hover">
  <thead>
    <tr>
      <th scope="col">No</th>
      <th scope="col">Full Name</th>
      <th scope="col">Gender</th>
      <th scope="col">Phone Number</th>
      <th scope="col">User Phone Number</th>
      <th scope="col">Status</th>
      <th scope="col">Remarks</th>
      <th scope="col">Created By</th>
      <th scope="col">Joined At</th>
      <th scope="col">Action</th>   
    </tr>
  </thead>
  <tbody>
    <?php
    $i=1;
     foreach ($participants as $participant):?>
    <tr>
      <th scope="row"><?php echo $i++; ?></th>
      <td><?php echo htmlspecialchars($participant['full_name']) ?></td>
      <td><?php echo htmlspecialchars($participant['gender']); ?></td>
      <td><?php echo htmlspecialchars($participant['participant_phone']); ?></td>
      <td><?php echo htmlspecialchars($participant['phone_number']); ?></td>
      <td><?php 
      $status=$participant['participant_status'];
      if ($status === "attend") {
            echo "<span class='text-success fw-bold'>Attend</span>";
        } elseif ($status === "absent") {
            echo "<span class='text-danger fw-bold'>Absent</span>";
        } elseif ($status === "late") {
            echo "<span class='text-danger fw-bold'>Late</span>";
        } else {
            echo htmlspecialchars($status);
        }
      
      ?></td>
      <td><?php if (!empty($participant["remarks"])):?>
        <?php echo htmlspecialchars($participant['remarks']);?>
    <?php else:?>
               No Remarks provided
    <?php endif; ?>
    </td>
      <td><?php echo htmlspecialchars($participant['username']) ?></td>
      <td><?php echo htmlspecialchars($participant['joined_date']) ?></td>
      <td> 
        <?php if ($participant['user_id'] == $_SESSION['user_id']): ?>
    <form method="POST" action="leave_event.php" onsubmit="return confirm('Are you sure you want to leave this event?');">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        <button type="submit" name="leave_event" class="btn btn-danger btn-sm">Leave Event</button>
    </form>

    <?php elseif ($isEventOwner): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editStatusModal<?= $participant['user_id'] ?>">
    Edit Status
  </button>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#kickModal<?= $participant['user_id'] ?>">Kick</button>
    

    <div class="modal fade" id="kickModal<?= $participant['user_id'] ?>" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST" action="kick_participant.php">
            <div class="modal-header">
              <h5 class="modal-title">Kick Participant</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="event_id" value="<?= $event_id ?>">
              <input type="hidden" name="participant_id" value="<?= $participant['user_id'] ?>">
              <div class="mb-3">
                <label for="reason<?= $participant['user_id'] ?>" class="form-label">Reason for Kick</label>
                <textarea name="reason" id="reason<?= $participant['user_id'] ?>" class="form-control" rows="4" required></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger">Kick Participant</button>
            </div>
          </form>
        </div>
      </div>
    </div>

     <div class="modal fade" id="editStatusModal<?= $participant['user_id'] ?>" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="update_participant_status.php">
          <div class="modal-header">
            <h5 class="modal-title">Update Participant Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            <input type="hidden" name="participant_id" value="<?= $participant['user_id'] ?>">

            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="participant_status" class="form-select" required>
                <option value="attend" <?= $participant['participant_status']=='attend' ? 'selected' : '' ?>>Attend</option>
                <option value="absent" <?= $participant['participant_status']=='absent' ? 'selected' : '' ?>>Absent</option>
                <option value="late" <?= $participant['participant_status']=='late' ? 'selected' : '' ?>>Late</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Remark (Optional)</label>
              <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($participant['remarks']) ?></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_status" class="btn btn-primary">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php else: ?>
    -
<?php endif; ?>
      </td>
    </tr>
    <?php endforeach;?>
  </tbody>
</table>
<?php else: ?>
<p class="no-events">No participant entered the event at the moment.</p>
<?php endif; ?>


<h3>Event Comments</h3>

<div id="commentList"></div>

<form id="commentForm" class="mb-3">
    <textarea class="form-control mb-2"
              id="commentInput"
              rows="3"
              placeholder="Write a comment..."
              required></textarea>
    <button class="btn btn-primary">Post Comment</button>
</form>


</div>
</div>




<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">Delete Comment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        Are you sure you want to delete this comment?  
        <br><small class="text-muted">This action cannot be undone.</small>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
          Delete
        </button>
      </div>
    </div>
  </div>
</div>




<script>
document.addEventListener("DOMContentLoaded", function() {
    const statusSelect = document.getElementById("Status");
    const remarksContainer = document.getElementById("remarksContainer");
    const remarksTextarea = document.getElementById("remark");

    statusSelect.addEventListener("change", function() {
        if (statusSelect.value === "absent" || statusSelect.value === "late") {
            remarksContainer.style.display = "block";
            remarksTextarea.required = true;
        } else {
            remarksContainer.style.display = "none";
            remarksTextarea.required = false;
            remarksTextarea.value = ""; // clear if hiding
        }
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
const mainContent = document.getElementById("mainContent");

// Hover expand/collapse
sidebar.addEventListener("mouseenter", () => {
    sidebar.classList.remove("collapsed");
    mainContent.style.marginLeft = "250px"; // expanded width
});

sidebar.addEventListener("mouseleave", () => {
    sidebar.classList.add("collapsed");
    mainContent.style.marginLeft = "60px"; // collapsed width
});

// Optional: toggle button logic
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    const pageSize = 4;

    document.querySelectorAll(".activity-row-wrapper").forEach(wrapper => {
        const row = wrapper.querySelector(".activity-row");
        const items = row.querySelectorAll(".activity-item");
        let page = 0;

        function showPage() {
            items.forEach((item, index) => {
                item.style.display =
                    index >= page * pageSize && index < (page + 1) * pageSize
                        ? "block"
                        : "none";
            });
        }

        showPage(); // show first page

        const prevBtn = wrapper.querySelector(".prev-btn");
        const nextBtn = wrapper.querySelector(".next-btn");

        if(prevBtn && nextBtn){
            prevBtn.addEventListener("click", () => {
                if (page > 0) {
                    page--;
                    showPage();
                }
            });

            nextBtn.addEventListener("click", () => {
                if ((page + 1) * pageSize < items.length) {
                    page++;
                    showPage();
                }
            });
        }
    });
});
</script>

<script>
const eventId = <?= $event_id ?>;

function loadComments() {
    fetch(`comment_fetch.php?event_id=${eventId}`)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById("commentList");
            list.innerHTML = "";

            if (data.comments.length === 0) {
                list.innerHTML = `
                    <div class="alert alert-secondary text-center">
                        There are no comments being posted currently.
                    </div>
                `;
                return;
            }

 data.comments.forEach(c => {
 list.innerHTML += `
  <div class="card mb-2" id="comment-${c.id}">
   <div class="card-body">
  <div class="d-flex justify-content-between">
  <div class="d-flex align-items-center gap-2">
  ${c.users_image ? `
    <img 
        src="pictures/${c.users_image}"
        class="rounded-circle"
        width="40"
        height="40"
        style="object-fit:cover;"
    >
` : `
    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
         style="width:40px;height:40px;">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="white">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
        </svg>
    </div>
`}
  <strong>${c.username}</strong>
  <small class="text-muted ms-2">
  ${new Date(c.created_at).toLocaleString()}
  </small>
</div>

${EVENT_STATUS !== "concluded" && c.user_id == data.currentUser ? `
<span class="material-symbols-outlined text-secondary"
 style="cursor:pointer"
 onclick="enableEdit(${c.id})">
 edit
</span>` : ""}
</div>

<p class="mt-2" id="comment-text-${c.id}">
 ${c.comment}
 </p>

<div id="edit-box-${c.id}" class="d-none">
<textarea class="form-control mb-2"
 id="edit-input-${c.id}">${c.comment}</textarea>

<button class="btn btn-sm btn-primary"
onclick="saveEdit(${c.id})">
Save
</button>

<button class="btn btn-sm btn-secondary"
onclick="cancelEdit(${c.id})">
Cancel
 </button>
</div>

  ${EVENT_STATUS !== "concluded" && (data.isOwner || c.user_id == data.currentUser) ? `
  <span class="material-symbols-outlined text-danger mt-2"
  style="cursor:pointer"
  onclick="openDeleteModal(${c.id}, ${data.isOwner ? 1 : 0})">
  delete
</span>` : ""}
</div>
</div>`;
 });
});
}

document.getElementById("commentForm").addEventListener("submit", e => {

   if (EVENT_STATUS === "concluded") {
        e.preventDefault();
        return;
    }

    e.preventDefault();
    const comment = commentInput.value.trim();
    if (!comment) return;

    fetch("comment_add.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `event_id=${eventId}&comment=${encodeURIComponent(comment)}`
    }).then(() => {
        commentInput.value = "";
        loadComments();
    });
});

function enableEdit(id) {
    document.getElementById(`comment-text-${id}`).classList.add("d-none");
    document.getElementById(`edit-box-${id}`).classList.remove("d-none");
}

function cancelEdit(id) {
    document.getElementById(`edit-box-${id}`).classList.add("d-none");
    document.getElementById(`comment-text-${id}`).classList.remove("d-none");
}

function saveEdit(id) {
    const updated = document.getElementById(`edit-input-${id}`).value.trim();
    if (!updated) return alert("Comment cannot be empty");

    fetch("comment_edit.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `comment_id=${id}&comment=${encodeURIComponent(updated)}`
    }).then(loadComments);
}

function deleteComment(id, isOwner) {
    if (!confirm("Delete this comment?")) return;

    fetch("comment_delete.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `comment_id=${id}&is_owner=${isOwner}`
    }).then(loadComments);
}

loadComments();
</script>
    
<script>
let deleteCommentId = null;
let deleteIsOwner = 0;

function openDeleteModal(id, isOwner) {
    deleteCommentId = id;
    deleteIsOwner = isOwner;

    const modal = new bootstrap.Modal(
        document.getElementById("deleteConfirmModal")
    );
    modal.show();
}

document.getElementById("confirmDeleteBtn").addEventListener("click", () => {
    if (!deleteCommentId) return;

    fetch("comment_delete.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `comment_id=${deleteCommentId}&is_owner=${deleteIsOwner}`
    }).then(() => {
        deleteCommentId = null;
        loadComments();

        bootstrap.Modal
            .getInstance(document.getElementById("deleteConfirmModal"))
            .hide();
    });
});
</script>

<script>
    const EVENT_STATUS = "<?= strtolower($event['event_status']) ?>";
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const commentForm = document.getElementById("commentForm");
    const commentInput = document.getElementById("commentInput");
    const postButton = commentForm.querySelector("button");

    if (EVENT_STATUS === "concluded") {
       
        commentInput.disabled = true;
        commentInput.placeholder = "Comments are disabled because this event has concluded.";

       
        postButton.disabled = true;
        postButton.classList.remove("btn-primary");
        postButton.classList.add("btn-secondary");
        postButton.textContent = "Event Concluded";
    }
});
</script>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" 
 crossorigin="anonymous">
</script>

</body>
</html>
