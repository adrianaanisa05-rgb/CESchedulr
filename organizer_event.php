<?php 
session_start(); 
include 'db_connect.php';
$errors=[];
$create_message=[];
$current_page = basename($_SERVER['PHP_SELF']);

$user_id = $_SESSION['user_id']; 


$conn->query("
    UPDATE events
    SET event_status = 'concluded'
    WHERE end_date <= NOW()
      AND event_status != 'concluded'
");

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM events 
    WHERE user_id = ? AND approval_status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();


$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM events 
    WHERE user_id = ? AND approval_status = 'approved'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$approvedCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();


$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM events 
    WHERE user_id = ? AND approval_status = 'rejected'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rejectedCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();


$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM events
    WHERE user_id = ?
      AND MONTH(event_date) = MONTH(CURRENT_DATE())
      AND YEAR(event_date) = YEAR(CURRENT_DATE())
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthlyCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();



if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_event'])) {

    $event_id = (int) $_POST['event_id'];
    $user_id  = $_SESSION['user_id'];

    
    $stmt = $conn->prepare(
        "SELECT event_image FROM events WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event  = $result->fetch_assoc();
    $stmt->close();

    if ($event) {

        
        if (!empty($event['event_image'])) {
            $imagePath = "uploads/" . $event['event_image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $stmt = $conn->prepare("DELETE FROM notifications WHERE event_id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->close();

         $stmt = $conn->prepare("DELETE FROM activity WHERE event_id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->close();

        
        $stmt = $conn->prepare("DELETE FROM comments WHERE event_id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->close();

       
        $stmt = $conn->prepare("DELETE FROM participant WHERE event_id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->close();
       
        $stmt = $conn->prepare(
            "DELETE FROM events WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $event_id, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: organizer_event.php?message=deleted");
        exit;
    } else {
        header("Location: organizer_event.php?error=delete_failed");
        exit;
    }
}


$clubStmt = $conn->prepare("
    SELECT id, club_name
    FROM club
    WHERE status = 'active'
    ORDER BY club_name ASC
");
$clubStmt->execute();
$clubs = $clubStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$clubStmt->close();

$venue_sql = $conn->prepare("
    SELECT venue_id, venue_name 
    FROM venue 
    WHERE user_id = ?
");
$venue_sql->bind_param("i", $user_id);
$venue_sql->execute();
$venue_result = $venue_sql->get_result();
$venues = $venue_result->fetch_all(MYSQLI_ASSOC);


if ($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST['create_event'])){
$title= $_POST['title'];

$event_description=$_POST['event_description'];

$event_date=$_POST['event_date'];

$end_date=$_POST['end_date'];

$event_capacity=$_POST['event_capacity'];

$user_id=$_SESSION['user_id'];

$event_status=$_POST['event_status'];

$contact_number=$_POST['contact_number'];

$club_id = !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null;

$venue_id = !empty($_POST['venue_id']) ? $_POST['venue_id'] : null;

 $checkSql = $conn->prepare("SELECT id FROM events WHERE title = ?");
    $checkSql->bind_param("s", $title);
    $checkSql->execute();
    $checkResult = $checkSql->get_result();

    if ($checkResult->num_rows > 0) {
        $errors[] = "The event title '$title' has already been taken. Please choose a different title.";
    
    } else{
if (strtotime($end_date) <= strtotime($event_date)) {
        $errors[] = "End date must be after start date.";
    }

if (!empty($club_id)) {
    $clubCheck = $conn->prepare("
        SELECT id FROM club WHERE id = ? AND status = 'active'
    ");
    $clubCheck->bind_param("i", $club_id);
    $clubCheck->execute();
    $clubCheck->store_result();

    if ($clubCheck->num_rows === 0) {
        $errors[] = "Selected club is no longer active.";
    }
    $clubCheck->close();
}
  
if (!empty($venue_id)) {
    $overlapSql = $conn->prepare("
        SELECT id, title, event_date, end_date
        FROM events
        WHERE venue_id = ?
          AND approval_status != 'rejected'
          AND (event_date < ? AND end_date > ?)
    ");

    $overlapSql->bind_param("iss", $venue_id, $end_date, $event_date);
    $overlapSql->execute();
    $overlapResult = $overlapSql->get_result();

    if ($overlapResult->num_rows > 0) {
        $overlappingEvents = [];
        while ($row = $overlapResult->fetch_assoc()) {
            $overlappingEvents[] = $row;
        }

        $message = "Another event is already scheduled at this venue during the selected time: ";
        foreach ($overlappingEvents as $event) {
            $message .= htmlspecialchars($event['title']) . " | ";
            $message .= date("M d, Y H:i", strtotime($event['event_date'])) . " - ";
            $message .= date("M d, Y H:i", strtotime($event['end_date'])) . " | ";
        }

        $errors[] = $message;
    }

    $overlapSql->close();
}
$event_image= null;

if (!empty($_FILES['event_image']['name'])){

$fileName = $_FILES['event_image']['name'];
$tempName=$_FILES['event_image']['tmp_name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedTypes = array("jpg","jpeg","png","gif");



if(!in_array($ext,$allowedTypes)){
$errors[] ="Your image file type is not allowed.";
} else{
 $uniqueName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
 $targetPath = "uploads/" . $uniqueName;

  if (move_uploaded_file($tempName,$targetPath)){
 $event_image = $uniqueName; 
}
 else {
            $errors[] = "Failed to upload image.";
        }
      }
   }
   if (count($errors)=== 0){
   $sql = $conn->prepare("INSERT INTO events (title, event_description, event_date, end_date, event_capacity, event_image,user_id, event_status,contact_number, venue_id, club_id) 
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$sql->bind_param("ssssisissii", $title,$event_description,$event_date,$end_date,$event_capacity,$event_image,$user_id, $event_status, $contact_number, $venue_id, $club_id);
if ($sql->execute()) {
                $create_message[] = "Event created successfully and image uploaded.";
            } else {
                $errors[] = "Error creating event: " . $sql->error;
            }
          }
}
$checkSql->close();
}

$filterStatus = $_GET['status'] ?? 'all';

$whereClause = "WHERE e.user_id = ?";
$types = "i";
$params = [$user_id];

if ($filterStatus !== 'all') {
    $whereClause .= " AND e.approval_status = ?";
    $types .= "s";
    $params[] = $filterStatus;
}


$sql=$conn->prepare("
SELECT e.*, u.username, u.phone_number, v.venue_name,c.club_name
FROM events e
JOIN users u ON e.user_id = u.id
LEFT JOIN venue v ON e.venue_id = v.venue_id
LEFT JOIN club c ON e.club_id = c.id
$whereClause
ORDER BY 
    CASE e.approval_status
        WHEN 'approved' THEN 1
        WHEN 'pending' THEN 2
        WHEN 'rejected' THEN 3
        ELSE 4
    END,
    e.event_date DESC");
$sql->bind_param($types, ...$params);
$sql->execute();
$result = $sql->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

 ?>





 <!DOCTYPE html> 
<html lang="en"> <head> 
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title>Event Dashboard</title> 
 <link rel="stylesheet" href="styles.css?v=4"> 
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" 
 rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
 <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
            enctype="multipart/form-data">
      <div class="modal-header">

        <h1 class="modal-title fs-5" id="exampleModalLabel">Create Event</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">  
      <div class="mb-3">

         <label for="Eventname" class="form-label">Event title</label>
         <input
            type="text"
            name="title"
            id="Eventname"
            class="form-control"
            placeholder="Enter Event Title"
            required/>
</div>


          <div class="mb-3">
         <label for="EventDesc" class="form-label">Event description</label>
         
            <textarea class="form-control"
            name="event_description"
            id="EventDesc"
            rows="5"
            placeholder="Description"
            required></textarea>
</div>
  
            <div class="mb-3">
         <label for="EventDate" class="form-label">Date & Time</label>
         <input
            type="text"
            name="event_date"
            id="EventDate"
            class="form-control"
            placeholder="Date & Time"
            required/>
</div>

 <div class="mb-3">
         <label for="EndDate" class="form-label">End Date & Time</label>
         <input
            type="text"
            name="end_date"
            id="EndDate"
            class="form-control"
            placeholder="Event End Date & Time"
            required
            disabled
            />
</div>

            <div class="mb-3">
         <label for="EventCap" class="form-label">Capacity</label>
         <input
            type="number"
            name="event_capacity"
            id="EventCap"
            class="form-control"
            placeholder="Capacity"
            required/>
</div>
            <div class="mb-3">
         <label for="Eventimg" class="form-label">Poster (Optional)</label>
         <input
            type="file"
            name="event_image"
            id="Eventimg"
            class="form-control"
            placeholder="Image"
            accept="image/*"
            />
</div>
         <div class="mb-3">
         <label for="Status" class="form-label">Event Status</label>
       <select name="event_status" id="Status" class="form-select" required>
        <option value="" disabled selected>Select Status</option>
        <option value="public">public</option>
        <option value="private">private</option>
    </select>
</div>

 <div class="mb-3">
         <label for="ContactNum" class="form-label">Contact Number(Optional)</label>
         
            <textarea class="form-control"
            name="contact_number"
            id="ContactNum"
            rows="5"
            placeholder="Insert all contact number here"
           ></textarea>
</div>

<div class="mb-3">
  <label for="CreateVenue" class="form-label">Venue (Can be edited after event created)</label>
  <select name="venue_id" id="CreateVenue" class="form-select">
    <option value="" selected>Select Venue</option> <!-- null default -->
    <?php foreach ($venues as $venue): ?>
      <option value="<?php echo $venue['venue_id']; ?>">
        <?php echo htmlspecialchars($venue['venue_name']); ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>
      
<div class="mb-3">
  <label for="club_id" class="form-label">Club (Can be edited when club has been created)</label>
  <select name="club_id" id="club_id" class="form-select" required>
    <option value="" disabled selected>Select Club (Admin Contact: 010-351-7757)</option>
    <?php foreach ($clubs as $club): ?>
      <option value="<?= $club['id']; ?>">
        <?= htmlspecialchars($club['club_name']); ?>
      </option>
    <?php endforeach; ?>

  </select>
</div>

    </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" name="create_event" class="btn btn-primary">Submit</button>
      </div>
</form>
    </div>
  </div>
</div>





<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editEventForm" method="post" action="edit_event.php" enctype="multipart/form-data">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="editEventModalLabel">Edit Event</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="event_id" id="editEventId">

          <div class="mb-3">
            <label for="editTitle" class="form-label">Event Title</label>
            <input type="text" name="title" id="editTitle" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="editDescription" class="form-label">Event Description</label>
            <textarea name="event_description" id="editDescription" class="form-control" rows="4" required></textarea>
          </div>

          <div class="mb-3">
            <label for="editDate" class="form-label">Date & Time</label>
            <input type="text" name="event_date" id="editDate" class="form-control" required>
          </div>

          <div class="mb-3">
             <label for="editEndDate" class="form-label">End Date & Time</label>
             <input
             type="text"
             name="end_date"
             id="editEndDate"
             class="form-control"
             required>
            </div>

          <div class="mb-3">
            <label for="editCapacity" class="form-label">Capacity</label>
            <input type="number" name="event_capacity" id="editCapacity" class="form-control" required>
          </div>

          <div class="mb-3">
  <label for="editImage" class="form-label">Poster (Optional)</label>
  <input type="file" name="event_image" id="editImage" class="form-control" accept="image/*">
  
  
  <div id="currentImageContainer" class="mt-2">
    <p>Current Image: <span id="currentImageName">No image uploaded</span></p>
    <img id="currentImagePreview" src="" alt="Current Event Image" style="max-width: 250px; max-height: 300px; border-radius:8px; display:none;">
  </div>
</div>

          <div class="mb-3">
            <label for="editStatus" class="form-label">Event Status</label>
            <select name="event_status" id="editStatus" class="form-select" required>
              <option value="public">Public</option>
              <option value="private">Private</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="editContact" class="form-label">Contact Number</label>
            <textarea name="contact_number" id="editContact" class="form-control" rows="2"></textarea>
          </div>

          <div class="mb-3">
            <label for="editVenue" class="form-label">Venue</label>
            <select name="venue_id" id="editVenue" class="form-select" required>
              <option value="" disabled selected>Select Venue</option>
              <?php foreach ($venues as $venue): ?>
                <option value="<?php echo $venue['venue_id']; ?>">
                  <?php echo htmlspecialchars($venue['venue_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="editClub" class="form-label">Club (Admin Contact: 010-351-7757)</label>
            <select name="club_id" id="editClub" class="form-select">
              <option value="" disabled>Select Club</option>
              <?php foreach ($clubs as $club): ?>
                <option value="<?= $club['id']; ?>">
                  <?= htmlspecialchars($club['club_name']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="update_event" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="row g-4 mb-4">

  <!-- Pending -->
  <div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 text-center">
      <div class="card-body">
        <span class="material-symbols-outlined text-warning fs-1">
          hourglass_top
        </span>
        <h6 class="text-muted mt-2">Pending Events</h6>
        <h2 class="fw-bold text-warning">
          <?php echo $pendingCount ?? 0; ?>
        </h2>
      </div>
    </div>
  </div>

  <!-- Approved -->
  <div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 text-center">
      <div class="card-body">
        <span class="material-symbols-outlined text-success fs-1">
          check_circle
        </span>
        <h6 class="text-muted mt-2">Approved Events</h6>
        <h2 class="fw-bold text-success">
          <?php echo $approvedCount ?? 0; ?>
        </h2>
      </div>
    </div>
  </div>

  <!-- Rejected -->
  <div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 text-center">
      <div class="card-body">
        <span class="material-symbols-outlined text-danger fs-1">
          cancel
        </span>
        <h6 class="text-muted mt-2">Rejected Events</h6>
        <h2 class="fw-bold text-danger">
          <?php echo $rejectedCount ?? 0; ?>
        </h2>
      </div>
    </div>
  </div>

  <!-- Monthly -->
  <div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 text-center">
      <div class="card-body">
        <span class="material-symbols-outlined text-primary fs-1">
          calendar_month
        </span>
        <h6 class="text-muted mt-2">Events This Month</h6>
        <h2 class="fw-bold text-primary">
          <?php echo $monthlyCount ?? 0; ?>
        </h2>
      </div>
    </div>
  </div>

</div>
<h1 class="page-title">Your event :</h1>
 
 <section class="available-events"> <h2 class="section-title">Event Created:</h2>
<button type="button" class="btn btn-primary me-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
  +Create Event
</button> 
          <?php if (isset($_GET['message']) && $_GET['message'] === 'updated'): ?>
  <div class="alert alert-success text-center">
    Event updated successfully!
  </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'update_failed'): ?>
  <div class="alert alert-danger text-center">
    Failed to update event. Please try again.
  </div>
<?php endif; ?>

<?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
  <div class="alert alert-success text-center">
    Event deleted successfully.
  </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
  <div class="alert alert-danger text-center">
    Failed to delete event.
  </div>
<?php endif; ?>

<?php if (!empty($_SESSION['form_errors'])): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($_SESSION['form_errors'] as $err): ?>
        <li><?php echo htmlspecialchars($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php unset($_SESSION['form_errors']); ?>
<?php endif; ?>

         <?php if (!empty($create_message)): ?>
    <ul style="color: green; text-align:center; list-style:none; padding:0;">
      <?php foreach ($create_message as $msg): ?>
        <li><?php echo $msg; ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger text-center">
        <ul class="mb-0 list-unstyled">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

 <div class="container-fluid mt-4">
   <form method="GET" class="d-flex mb-3">
      <select name="status"
              class="form-select w-auto"
              onchange="this.form.submit()">
        <option value="all" <?= ($filterStatus ?? 'all') === 'all' ? 'selected' : '' ?>>All Events</option>
        <option value="pending" <?= ($filterStatus ?? '') === 'pending' ? 'selected' : '' ?>>Pending Events</option>
        <option value="approved" <?= ($filterStatus ?? '') === 'approved' ? 'selected' : '' ?>>Approved Events</option>
        <option value="rejected" <?= ($filterStatus ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected Events</option>
      </select>
    </form>
      </div>
          <div class="events-grid">
            <div class="d-flex justify-content-center">

             <?php if (!empty($events)): ?> 
              <div class="table-responsive w-100">
               <table class="table table-striped text-center">
                  <thead>
                     <tr>
                        <th>Id</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>End Date</th>
                        <th>Capacity</th>
                        <th>Poster</th>
                        <th>Venue</th>
                        <th>Club Name</th>
                        <th>Status</th>
                        <th>Approval Status</th>
                        <th>Admin Remark</th>
                        <th>Actions</th>
             </tr>
             </thead>
             <tbody>
               <?php 
               $i=1; 
               foreach ($events as $event): ?> 
                <tr>
               <td scope="row"><?php echo $i++; ?></td>
               <td><?php echo htmlspecialchars($event['title']); ?></td>
               <td><?php echo htmlspecialchars($event['event_description']); ?></td>
              <td><?php echo htmlspecialchars($event['event_date']); ?></td>
              <td><?php echo htmlspecialchars($event['end_date']); ?></td>
              <td><?php echo htmlspecialchars($event['event_capacity']); ?></td>
              <td>
               <?php if (!empty($event["event_image"])):?>
               <img src="uploads/<?php echo htmlspecialchars($event['event_image']);?>" alt="Event Image" width="250" height="300"
               style="border-radius: 8px;">
               <?php else:?>
               No Image Provided
               <?php endif; ?>
               </td>
               <td><?php echo htmlspecialchars($event['venue_name'] ?? 'N/A'); ?></td>
               <td><?php echo htmlspecialchars($event['club_name'] ?? 'N/A'); ?></td>
               <td><?php echo htmlspecialchars($event['event_status']?? 'N/A');?></td>
                <td><?php $status = $event['approval_status'];
                if ($status === 'pending') {
                  echo '<span class="badge bg-warning text-dark">Pending</span>';
                } elseif ($status === 'approved') {
                  echo '<span class="badge bg-success">Approved</span>';
                } elseif ($status === 'rejected') {
                  echo '<span class="badge bg-danger">Rejected</span>';
                }
                ?>
                
                </td>
                <td><?php echo htmlspecialchars($event['admin_remark']); ?></td>
               <td>
 <div class="d-flex align-items-center ">
<?php 
if ($event['approval_status'] === 'approved') {
   
    echo '<a href="event_page.php?event_id=' . $event['id'] . '" 
             class="btn btn-sm btn-outline-success me-1">
             Enter Event
          </a>';
} else {
    
  echo '<button class="btn btn-secondary btn-sm me-1" disabled 
       style="padding: 0.2rem 0.4rem; font-size: 0.75rem;" 
       title="You still have no access">
       Event Disabled
      </button>';
}
?>
<?php if ($event['event_status'] === 'declined' || $event['event_status'] === 'rejected'|| $event['event_status'] === 'concluded'): ?>
    <button class="btn btn-secondary btn-sm me-1" disabled>
        Edit Disabled
    </button>
<?php else: ?>
    <button class="btn btn-primary btn-sm me-1 edit-btn"
  data-id="<?php echo $event['id']; ?>"
  data-title="<?php echo htmlspecialchars($event['title']); ?>"
  data-description="<?php echo htmlspecialchars($event['event_description']); ?>"
  data-date="<?php echo $event['event_date']; ?>"
  data-enddate="<?php echo $event['end_date']; ?>"
  data-capacity="<?php echo $event['event_capacity']; ?>"
  data-status="<?php echo $event['event_status']; ?>"
  data-contact="<?php echo htmlspecialchars($event['contact_number']); ?>"
  data-venue="<?php echo $event['venue_id']; ?>"
  data-club="<?= $event['club_id']; ?>"
  data-approval="<?= $event['approval_status']; ?>" 
   data-image="<?php echo !empty($event['event_image']) ? $event['event_image'] : ''; ?>"
  data-bs-toggle="modal" data-bs-target="#editEventModal">
  Edit
</button>
<?php endif; ?>
        <form method="post" style="display:inline;"
      onsubmit="return confirm('Are you sure you want to delete this event? This action cannot be undone.');">

    <input type="hidden" name="event_id"
           value="<?php echo $event['id']; ?>">

    <button type="submit" name="delete_event"
            class="btn btn-sm btn-danger">
        Delete
    </button>
</form>
</div>
</td>
</tr>

<?php endforeach; ?> 
                
</tbody>
</table> 
</div>
<?php else: ?>
    <p class="no-events text-center mt-4">
    <?php
      if ($filterStatus === 'approved') {
          echo "No approved events found.";
      } elseif ($filterStatus === 'rejected') {
          echo "No rejected events found.";
      } elseif ($filterStatus === 'pending') {
          echo "No events are currently pending approval.";
      } 
      else {
          echo "No events made at the moment.";
      }
    ?>
  </p>
<?php endif; ?>
                </div>
                </div>
                   </section>
                   
                
                

  <!-- JS --> 
  
<script>
document.addEventListener("DOMContentLoaded", () => {
  // === Fill the edit modal with event data ===
  const editButtons = document.querySelectorAll(".edit-btn");

  editButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      // Fill basic fields
      document.getElementById("editEventId").value = btn.getAttribute("data-id");
      document.getElementById("editTitle").value = btn.getAttribute("data-title");
      document.getElementById("editDescription").value = btn.getAttribute("data-description");
      document.getElementById("editDate").value = btn.getAttribute("data-date");
      document.getElementById("editEndDate").value = btn.getAttribute("data-enddate");
      document.getElementById("editCapacity").value = btn.getAttribute("data-capacity");
      document.getElementById("editContact").value = btn.getAttribute("data-contact");
      document.getElementById("editVenue").value = btn.getAttribute("data-venue");
      document.getElementById("editClub").value = btn.dataset.club;

      // === Handle image preview ===
      const imageFile = btn.getAttribute("data-image");
      const preview = document.getElementById("currentImagePreview");
      const imageName = document.getElementById("currentImageName");
      const status = btn.dataset.approval;

      if (imageFile) {
        preview.src = "uploads/" + imageFile;
        preview.style.display = "block";
        imageName.textContent = imageFile;
      } else {
        preview.style.display = "none";
        imageName.textContent = "No image uploaded";
      }

        const fieldsToToggle = [
        "editTitle",
        "editDescription",
        "editDate",
        "editEndDate",
        "editCapacity",
        "editStatus",
        "editClub"
      ];

      fieldsToToggle.forEach(id => {
        const field = document.getElementById(id);
        if (status === "approved") {
          field.disabled = true;
        } else {
          field.disabled = false;
        }
     

      });
    });
  });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const eventDateInput = document.getElementById("EventDate");
    const endDateInput = document.getElementById("EndDate");

    // Set min of event_date to current datetime
    const now = new Date();
    const formattedNow = now.toISOString().slice(0,16); // "YYYY-MM-DDTHH:MM"
    eventDateInput.min = formattedNow;

    // Enable end_date only after event_date is picked
    eventDateInput.addEventListener("change", () => {
        if (eventDateInput.value) {
            endDateInput.disabled = false;
            endDateInput.min = eventDateInput.value;

            // Reset end_date if it's earlier than event_date
            if(endDateInput.value && endDateInput.value < eventDateInput.value){
                endDateInput.value = eventDateInput.value;
            }
        } else {
            endDateInput.disabled = true;
            endDateInput.value = ""; // clear end_date if event_date is cleared
        }
    });
});

</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const eventDateInput = document.getElementById("EventDate");
    const endDateInput = document.getElementById("EndDate");

    const now = new Date();

    // Flatpickr for EventDate
    flatpickr(eventDateInput, {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: now,
        time_24hr: true,
        closeOnSelect: true,    // close after picking date/time
        allowInput: true,       // allow manual typing
        monthSelectorType: "dropdown", // enable month dropdown
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                endDateInput.disabled = false;
                // Flatpickr for EndDate
                flatpickr(endDateInput, {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    minDate: selectedDates[0],
                    time_24hr: true,
                    closeOnSelect: true,
                    allowInput: true,
                    monthSelectorType: "dropdown",
                });

                // Optional: close EventDate picker after selection
                instance.close();
            } else {
                endDateInput.disabled = true;
                endDateInput.value = "";
            }
        }
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const editEventDate = document.getElementById("editDate");
    const editEndDate = document.getElementById("editEndDate");

    const endDatePicker = flatpickr(editEndDate, {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true,
        allowInput: true,
        monthSelectorType: "dropdown",
        disableMobile: true
    });

    flatpickr(editEventDate, {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true,
        allowInput: true,
        monthSelectorType: "dropdown",
        disableMobile: true,
         minDate: "2025-01-01",
        onChange: function (selectedDates) {

            if (selectedDates.length > 0) {
                const startDate = selectedDates[0];

                editEndDate.disabled = false;
                endDatePicker.set("minDate", startDate);

                
                if (
                    editEndDate.value &&
                    new Date(editEndDate.value) < startDate
                ) {
                    editEndDate.value = "";
                }

            } else {
                
                editEndDate.disabled = true;
                editEndDate.value = "";
                endDatePicker.set("minDate", null);
            }
        }
    });
});
</script>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" 
crossorigin="anonymous">
</script>
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
</body> 
</html> 
