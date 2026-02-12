<?php
session_start();
include 'db_connect.php';
$errors=[];
$create_message=[];
$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id']; 

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM participant 
    WHERE user_id = ? AND request_status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM participant 
    WHERE user_id = ? AND request_status = 'accepted'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$acceptedCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM participant 
    WHERE user_id = ? AND request_status = 'rejected'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rejectedCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM notifications 
    WHERE receiver_id = ? AND is_read = 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unreadCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$filterStatus = $_GET['status'] ?? 'all';

$whereClause = "WHERE p.user_id = ?";
$types = "i";
$params = [$user_id];

if ($filterStatus !== 'all') {
    $whereClause .= " AND p.request_status = ?";
    $types .= "s";
    $params[] = $filterStatus;
}

$sql = "
    SELECT 
        p.*,
        e.title,
        e.event_description,
        e.event_date
    FROM participant p
    JOIN events e ON p.event_id = e.id
    $whereClause
    ORDER BY 
        CASE p.request_status
            WHEN 'accepted' THEN 1
            WHEN 'pending' THEN 2
            WHEN 'rejected' THEN 3
        END,
        e.event_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();
$participantEvents = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


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
<h1 class="page-title">Participant Dashboard</h1>




<div class="row g-4 mt-4">

  <!-- Pending -->
  <div class="col-md-3 col-sm-6">
    <div class="card text-center shadow-sm border-0">
      <div class="card-body">
        <span class="material-symbols-outlined text-warning fs-1">
          hourglass_empty
        </span>
        <h6 class="mt-2 text-muted">Pending Requests</h6>
        <h2 class="fw-bold"><?php echo $pendingCount ?? 0; ?></h2>
      </div>
    </div>
  </div>

  <!-- Accepted -->
  <div class="col-md-3 col-sm-6">
    <div class="card text-center shadow-sm border-0">
      <div class="card-body">
        <span class="material-symbols-outlined text-success fs-1">
          check_circle
        </span>
        <h6 class="mt-2 text-muted">Accepted Requests</h6>
        <h2 class="fw-bold"><?php echo $acceptedCount ?? 0; ?></h2>
      </div>
    </div>
  </div>

  <!-- Rejected -->
  <div class="col-md-3 col-sm-6">
    <div class="card text-center shadow-sm border-0">
      <div class="card-body">
        <span class="material-symbols-outlined text-danger fs-1">
          cancel
        </span>
        <h6 class="mt-2 text-muted">Rejected Requests</h6>
        <h2 class="fw-bold"><?php echo $rejectedCount ?? 0; ?></h2>
      </div>
    </div>
  </div>

  <!-- Notifications -->
  <div class="col-md-3 col-sm-6">
    <div class="card text-center shadow-sm border-0">
      <div class="card-body">
        <span class="material-symbols-outlined text-primary fs-1">
          notifications
        </span>
        <h6 class="mt-2 text-muted">Unread Notifications</h6>
        <h2 class="fw-bold"><?php echo $unreadCount ?? 0; ?></h2>
      </div>
    </div>
  </div>

</div>


<div class="container-fluid mt-4">

  <h4 class="mb-3">My Joined Events</h4>

<form method="GET" class="d-flex mb-3">
  <select name="status"
          class="form-select w-auto"
          onchange="this.form.submit()">

    <option value="all"
      <?= ($_GET['status'] ?? 'all') === 'all' ? 'selected' : '' ?>>
      All Requests
    </option>

    <option value="pending"
      <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>
      Pending
    </option>

    <option value="accepted"
      <?= ($_GET['status'] ?? '') === 'accepted' ? 'selected' : '' ?>>
      Accepted
    </option>

    <option value="rejected"
      <?= ($_GET['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>
      Rejected
    </option>

  </select>
</form>

  <?php if (empty($participantEvents)): ?>
    <div class="no-events text-center mt-4">
       <?php
      if ($filterStatus === 'pending') {
          echo "No pending join event requests.";
      } elseif ($filterStatus === 'accepted') {
          echo "No accepted events found.";
      } elseif ($filterStatus === 'rejected') {
          echo "No rejected requests found.";
      } else {
          echo "You have not joined any events yet.";
      }
    ?>
    </div>
  <?php else: ?>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle text-center">
      <thead class="table-light">
        <tr>
          <th>No</th>
          <th>Event Title</th>
          <th>Description</th>
          <th>Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; foreach ($participantEvents as $event): ?>
          <tr>
            <td><?php echo $i++; ?></td>

            <td class="fw-bold">
              <?php echo htmlspecialchars($event['title']); ?>
            </td>

            <td class="text-start">
              <?php echo htmlspecialchars($event['event_description']); ?>
            </td>

            <td>
              <?php echo date("M d, Y H:i", strtotime($event['event_date'])); ?>
            </td>

            <td>
              <?php
                if ($event['request_status'] === 'accepted') {
                  echo '<span class="badge bg-success">Accepted</span>';
                } elseif ($event['request_status'] === 'pending') {
                  echo '<span class="badge bg-warning text-dark">Pending</span>';
                } else {
                  echo '<span class="badge bg-danger">Rejected</span>';
                }
              ?>
            </td>
            <td>
<?php if ($event['request_status'] === 'accepted'): ?>
        <button 
            class="btn btn-primary btn-sm" 
            data-bs-toggle="modal" 
            data-bs-target="#eventModal" 
            data-id="<?php echo $event['event_id']; ?>"
        >
    View Event
</button>
<?php else: ?>
<span class="text-muted small">Not Allowed</span>
<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php endif; ?>
</div>

</div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="eventModalContent">
        <div class="text-center p-3">Loading...</div>
      </div>
      <div class="modal-footer">
        <button type="button" id=joinEventBtn class="btn btn-primary">View Event</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", () => 
{ const eventModal = document.getElementById("eventModal");

eventModal.addEventListener("show.bs.modal", function(event) {
    let button = event.relatedTarget;
    let eventId = button.getAttribute("data-id");

    
    document.getElementById("eventModalContent").innerHTML =
        "<div class='text-center p-3'>Loading...</div>";

    
    fetch("get_event.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + eventId
    })
    .then(response => response.text())
    .then(data => {

        document.getElementById("eventModalContent").innerHTML = data;

        
        let status = document.getElementById("modal_event_status").value;
        let joinBtn = document.getElementById("joinEventBtn");

        if (status.toLowerCase() === "private") {
            joinBtn.disabled = true;
            joinBtn.classList.add("btn-secondary");
            joinBtn.classList.remove("btn-primary");
            joinBtn.textContent = "Private Event – Invite Required";
        } else {
            joinBtn.disabled = false;
            joinBtn.classList.remove("btn-secondary");
            joinBtn.classList.add("btn-primary");
            joinBtn.textContent = "Join Event";
        }

    })
    .catch(err => {
        console.error(err);
        document.getElementById("eventModalContent").innerHTML =
            "<div class='text-danger text-center p-3'>Failed to load event details.</div>";
    });

});
    
    document.addEventListener("click", function(e) {
        if (e.target && e.target.id === "joinEventBtn") {

            let eventIdField = document.getElementById("modal_event_id");
            if (!eventIdField) {
                alert("Event ID missing!");
                return;
            }

            let eventId = eventIdField.value;

           
             const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'join_event.php';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'event_id';
        input.value = eventId;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();

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