<?php
session_start();
include 'db_connect.php';
$current_page = basename($_SERVER['PHP_SELF']);

$totalEventsQuery = $conn->query("SELECT COUNT(*) AS total_events FROM events WHERE approval_status='approved'");
$totalEvents = $totalEventsQuery->fetch_assoc()['total_events'];


$totalClubsQuery = $conn->query("SELECT COUNT(*) AS total_clubs FROM club WHERE status = 'active'"); 
$totalClubs = $totalClubsQuery->fetch_assoc()['total_clubs'];


$pendingEventsQuery = $conn->query("SELECT COUNT(*) AS pending_events FROM events WHERE approval_status = 'pending'");
$pendingEvents = $pendingEventsQuery->fetch_assoc()['pending_events'];


$totalUsersQuery = $conn->query("SELECT COUNT(*) AS total_users FROM users");
$totalUsers = $totalUsersQuery->fetch_assoc()['total_users'];



if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_event'])) {

    $event_id = (int) $_POST['event_id'];
    $user_id  = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];

    
    if ($user_type === 'admin') {

        $stmt = $conn->prepare("SELECT event_image FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        $stmt->close();

    } else {
        
        $stmt = $conn->prepare(
            "SELECT event_image FROM events WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $event_id, $user_id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$event) {
        $_SESSION['form_errors'] = ["You are not allowed to delete this event."];
        header("Location: admin_dashboard.php");
        exit;
    }

    
    if (!empty($event['event_image'])) {
        $imagePath = "uploads/" . $event['event_image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    
    $tables = ['notifications', 'activity', 'comments', 'participant'];

    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM {$table} WHERE event_id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->close();
    }

    
    if ($user_type === 'admin') {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $event_id, $user_id);
    }

    $stmt->execute();

     if (!$stmt->execute()) {
        $_SESSION['form_errors'][] = "Failed to delete the event.";
        $stmt->close();
        header("Location: admin_dashboard.php");
        exit;
    }

    $stmt->close();

    $_SESSION['success'] = "Event deleted successfully.";

    
    header("Location: " . ($user_type === 'admin' ? "admin_dashboard.php" : "organizer_event.php"));
    exit;
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
");
$venue_sql->execute();
$venue_result = $venue_sql->get_result();
$venues = $venue_result->fetch_all(MYSQLI_ASSOC);


$sql = $conn->prepare("
    SELECT e.*, c.club_name, v.venue_name, u.username, u.phone_number
    FROM events e
    JOIN users u ON e.user_id = u.id
    LEFT JOIN club c ON e.club_id = c.id
    LEFT JOIN venue v ON e.venue_id = v.venue_id
    ORDER BY 
    CASE e.approval_status
        WHEN 'approved' THEN 1
        WHEN 'pending' THEN 2
        WHEN 'rejected' THEN 3
        ELSE 4
    END,
    e.event_date DESC
");
$sql->execute();
$result = $sql->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);

$sql->close();
$conn->close();
?>
<!DOCTYPE html> 
<html lang="en"> <head> 
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title>Admin Dashboard</title> 
<!-- Google Material Icons --> 
 <link rel="stylesheet" href="styles.css?v=4"> 
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" 
 rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
 <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
<h1 class="page-title">Admin Dashboard</h1>

<div class="container mt-3">
<?php
if (!empty($_SESSION['success'])) {
    echo '<div class="alert alert-success">'.htmlspecialchars($_SESSION['success']).'</div>';
    unset($_SESSION['success']);
}
if (!empty($_SESSION['form_errors'])) {
    foreach ($_SESSION['form_errors'] as $error) {
        echo '<div class="alert alert-danger">'.htmlspecialchars($error).'</div>';
    }
    unset($_SESSION['form_errors']);
}
?>
</div>

<div class="row mb-4 text-center">
    <div class="col-md-3">
        <div class="card shadow-sm p-3">
            <span class="material-symbols-outlined" style="font-size: 40px; color:#007bff;">
                event
            </span>
            <h5 class="mt-2">Total Events</h5>
            <h2><?php echo $totalEvents; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm p-3">
            <span class="material-symbols-outlined" style="font-size: 40px; color:#28a745;">
                groups
            </span>
            <h5 class="mt-2">Active Clubs</h5>
            <h2><?php echo $totalClubs; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm p-3">
            <span class="material-symbols-outlined" style="font-size: 40px; color:#ffc107;">
                schedule
            </span>
            <h5 class="mt-2">Pending Events</h5>
            <h2><?php echo $pendingEvents; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm p-3">
            <span class="material-symbols-outlined" style="font-size: 40px; color:#dc3545;">
                person
            </span>
            <h5 class="mt-2">Total Users</h5>
            <h2><?php echo $totalUsers; ?></h2>
        </div>
    </div>
</div>

<div class="container mt-4">
    <h4 class="mb-4 text-center">Monthly event count by year from each club</h4>

<div class="d-flex justify-content-end mb-3">
    <select id="yearFilter" class="form-select w-auto ms-2">
        <?php
        $currentYear = date('Y');      
        $startYear = 2025; 
        $endYear = $currentYear + 3;   

        for ($y = $startYear; $y <= $endYear; $y++) {  // loop up instead of down
            $selected = ($y === $currentYear) ? 'selected' : '';
            echo "<option value='$y' $selected>$y</option>";
        }
        ?>
    </select>
</div>
    <div class="card shadow-sm">
        <div class="card-body">
            <canvas id="clubEventChart"></canvas>
        </div>
    </div>
</div>
<div class="events-grid">
<div class="d-flex justify-content-center">


<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editEventForm" method="post" action="admin_edit_event.php" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="event_id" id="editEventId">

          <div class="mb-3">
            <label for="editTitle" class="form-label">Title</label>
            <input type="text" class="form-control" name="title" id="editTitle" required>
          </div>

          <div class="mb-3">
            <label for="editDescription" class="form-label">Description</label>
            <textarea class="form-control" name="description" id="editDescription" rows="4" required></textarea>
          </div>

          <div class="mb-3">
            <label for="editDate" class="form-label">Event Start Date & Time</label>
            <input type="text" class="form-control" name="event_date" id="editDate" required>
          </div>

          <div class="mb-3">
            <label for="editEndDate" class="form-label">Event End Date & Time</label>
            <input type="text" class="form-control" name="end_date" id="editEndDate" required >
          </div>

          <div class="mb-3">
            <label for="editCapacity" class="form-label">Capacity</label>
            <input type="number" class="form-control" name="capacity" id="editCapacity" required>
          </div>

         <div class="mb-3">
            <label for="editStatus" class="form-label">Event Status</label>
            <select name="event_status" id="editStatus" class="form-select" required>
              <option value="public">Public</option>
              <option value="private">Private</option>
              <option value="concluded">Concluded</option>
            </select>
          </div>

        <div class="mb-3">
  <label for="editApproval" class="form-label">Approval Status</label>
  <select class="form-select" name="approval_status" id="editApproval">
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
  </select>
</div>

          <div class="mb-3">
            <label for="editContact" class="form-label">Contact Number</label>
            <textarea name="contact_number" id="editContact" class="form-control" rows="4"></textarea>
          </div>

          <div class="mb-3">
            <label for="editVenue" class="form-label">Venue</label>
            <select name="venue_id" id="editVenue" class="form-select" >
              <option value="" disabled selected>Select Venue</option>
              <?php foreach ($venues as $venue): ?>
                <option value="<?php echo $venue['venue_id']; ?>">
                  <?php echo htmlspecialchars($venue['venue_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

           <div class="mb-3">
            <label for="editClub" class="form-label">Club Name</label>
            <select name="club_id" id="editClub" class="form-select">
              <option value="" disabled>Select Club</option>
              <?php foreach ($clubs as $club): ?>
                <option value="<?= $club['id']; ?>">
                  <?= htmlspecialchars($club['club_name']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

          <div class="mb-3">
  <label for="editImage" class="form-label">Poster (Optional)</label>
  <input type="file" name="event_image" id="editImage" class="form-control" accept="image/*">
  
  
  <div id="currentImageContainer" class="mt-2">
    <p>Current Image: <span id="currentImageName">No image uploaded</span></p>
    <img id="currentImagePreview" src="" alt="Current Event Image" style="max-width: 250px; max-height: 300px; border-radius:8px; display:none;">
  </div>
</div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="updates_event" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>



<?php if (!empty($events)): ?>
<div class="table-responsive mt-4">
<table class="table table-striped table-bordered align-middle">
    <thead class="table-dark text-center">
        <tr>
            <th>No </th>
            <th>Poster Image</th>
            <th>Title</th>
            <th>Event Description </th>
            <th>Start Time & Date</th>
            <th>End Time & Date</th>
            <th>Capacity</th>
            <th>Venue</th>
            <th>Club Name</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Approval Status</th>
            
            <th>Created By</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $i=1; 
        foreach ($events as $event): ?> 
        <tr>
    <td scope="row"><?php echo $i++; ?></td>
    <td>
       <?php if (!empty($event["event_image"])):?>
               <img src="uploads/<?php echo htmlspecialchars($event['event_image']);?>" alt="Event Image" width="250" height="300"
               style="border-radius: 8px;">
               <?php else:?>
               No Image Provided
               <?php endif; ?>
    </td>

    <td><?= htmlspecialchars($event['title']) ?></td>
    <td><?php echo htmlspecialchars($event['event_description']); ?></td>
    <td><?= date("M d, Y H:i", strtotime($event['event_date'])) ?></td>
    <td><?= date("M d, Y H:i", strtotime($event['end_date'])) ?></td>
    <td class="text-center"><?= $event['event_capacity'] ?></td>
    <td><?php echo htmlspecialchars($event['venue_name'] ?? 'N/A'); ?></td>
    <td><?php echo htmlspecialchars($event['club_name'] ?? 'N/A'); ?></td>
    <td> <?= !empty($event['contact_number']) ? htmlspecialchars($event['contact_number']) : 'N/A'; ?></td>
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
     
     <td><?php echo htmlspecialchars($event['username'] ?? 'N/A'); ?></td>

    <td class="text-center">
         <button
        class="btn btn-sm btn-primary edit-btn"
        data-id="<?= $event['id'] ?>"
        data-title="<?= htmlspecialchars($event['title']) ?>"
        data-description="<?= htmlspecialchars($event['event_description']) ?>"
        data-date="<?= $event['event_date'] ?>"
        data-enddate="<?= $event['end_date'] ?>"
        data-capacity="<?= $event['event_capacity'] ?>"
        data-status="<?= $event['event_status'] ?>"
        data-approval="<?= $event['approval_status'] ?>"
        data-contact="<?= htmlspecialchars($event['contact_number']) ?>"
        data-venue="<?= $event['venue_id'] ?>"
        data-club="<?= $event['club_id'] ?>"
        data-image="<?= $event['event_image'] ?>"
        data-bs-toggle="modal"
        data-bs-target="#editEventModal">
        Edit
    </button>
        <form method="post" style="display:inline;"
      onsubmit="return confirm('Are you sure you want to delete this event? This action cannot be undone.');">
    <input type="hidden" name="event_id"
           value="<?php echo $event['id']; ?>">

    <button type="submit" name="delete_event"
            class="btn btn-sm btn-danger">
        Delete
    </button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="15" class="text-center">No events found.</td>
</tr>
<?php endif; ?>
    </tbody>
</table>
</div>
</div>
</div>




</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const canvas = document.getElementById('clubEventChart');
    const ctx = canvas.getContext('2d');
    let clubEventChart;

   function loadChart() {
    const year = document.getElementById('yearFilter').value;

    fetch(`get_chart_data.php?year=${year}`)
        .then(res => res.json())
        .then(data => {
            const labels = data.months;

            const datasets = data.clubs.map((club, index) => ({
                label: club,
                data: labels.map(month => data.data[club][month]),
                backgroundColor: `hsl(${index * 60}, 70%, 60%)`
            }));

            if (!clubEventChart) {
                clubEventChart = new Chart(ctx, {
                    type: 'bar',
                    data: { labels, datasets },
                    options: {
                        scales: {
                            x: { title: { display: true, text: 'Month' } },
                            y: { beginAtZero: true, title: { display: true, text: 'Event Count' } }
                        }
                    }
                });
            } else {
                clubEventChart.data.labels = labels;
                clubEventChart.data.datasets = datasets;
                clubEventChart.update();
            }
        });
}
    // ✅ Initial chart load
    loadChart();

    // ✅ Update chart when filter changes
    document.getElementById('yearFilter').addEventListener('change', function () {
    loadChart();
});
});
</script>


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
      document.getElementById("editStatus").value = btn.getAttribute("data-status");
      document.getElementById("editApproval").value = btn.getAttribute("data-approval");
      document.getElementById("editContact").value = btn.getAttribute("data-contact");
      document.getElementById("editVenue").value = btn.getAttribute("data-venue");
      document.getElementById("editClub").value = btn.dataset.club;

      // === Handle image preview ===
      const imageFile = btn.getAttribute("data-image");
      const preview = document.getElementById("currentImagePreview");
      const imageName = document.getElementById("currentImageName");

      if (imageFile) {
        preview.src = "uploads/" + imageFile;
        preview.style.display = "block";
        imageName.textContent = imageFile;
      } else {
        preview.style.display = "none";
        imageName.textContent = "No image uploaded";
      }

     

     
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" 
 crossorigin="anonymous">
 </script>
</body>
</html>