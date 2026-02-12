<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$errors=[];
$create_message=[];
$current_page = basename($_SERVER['PHP_SELF']);

$sql = "
SELECT e.*, u.username,u.phone_number,u.gmail,v.venue_name,v.venue_address,v.venue_city,v.venue_postcode,v.venue_capacity,v.remark,v.venue_image,
c.club_name,c.club_description,c.club_email,c.club_phone
FROM events e
JOIN users u ON e.user_id = u.id
LEFT JOIN club c ON e.club_id=c.id
LEFT JOIN venue v ON e.venue_id = v.venue_id
WHERE e.approval_status IN ('pending','approved','rejected')
ORDER BY 
    FIELD(e.approval_status,'pending','approved','rejected'),
    e.event_date DESC
";
$result = $conn->query($sql);
$events = $result->fetch_all(MYSQLI_ASSOC);

$pending  = [];
$approved = [];
$rejected = [];

foreach ($events as $event) {
    if ($event['approval_status'] === 'pending') {
        $pending[] = $event;
    } elseif ($event['approval_status'] === 'approved') {
        $approved[] = $event;
    } else {
        $rejected[] = $event;
    }
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
<h1 class="page-title">Approve Events</h1>
<h4 class="mt-4">Pending Events</h4>
<?php if (empty($pending)): ?>
<p class="text-muted">No event has currently been requested for approval.</p>
<?php else: ?>
<table class="table table-hover">
<thead class="table-dark">
<tr>
    <th>Title</th>
    <th>Organizer</th>
    <th>Start Date & Time</th>
    <th>End Date & Time </th>
    <th>Organized By</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($pending as $e): ?>
<tr>
    <td><?= htmlspecialchars($e['title']) ?></td>
    <td><?= htmlspecialchars($e['username']) ?></td>
    <td><?= date('d M Y H:i', strtotime($e['event_date'])) ?></td>
    <td><?= date('d M Y H:i', strtotime($e['end_date'])) ?></td>
    <td><?=htmlspecialchars($e['club_name']?? 'N/A')?></td>
    <td>
        <button class="btn btn-warning btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#eventModal"
             data-event='<?= htmlspecialchars(json_encode($e), ENT_QUOTES, "UTF-8") ?>'>
            Review event
        </button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>


<h4 class="mt-5">Accepted Events</h4>
<?php if (empty($approved)): ?>
<p class="text-muted">No event has currently been approved.</p>
<?php else: ?>
<table class="table table-striped">
<thead class="table-success">
<tr>
    <th>Title</th>
    <th>Organizer</th>
    <th>Start Date & Time</th>
     <th>End Date & Time </th>
    <th>Organized By</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($approved as $e): ?>
<tr>
    <td><?= htmlspecialchars($e['title']) ?></td>
    <td><?= htmlspecialchars($e['username']) ?></td>
    <td><?= date('d M Y', strtotime($e['event_date'])) ?></td>
    <td><?= date('d M Y', strtotime($e['end_date'])) ?></td>
    <td><?=htmlspecialchars($e['club_name']?? 'N/A')?></td>
    <td><span class="badge bg-success">Approved</span></td>
    </td>
     <td>
        <button class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#eventModal"
             data-event='<?= htmlspecialchars(json_encode($e), ENT_QUOTES, "UTF-8") ?>'>
            View
        </button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>


<h4 class="mt-5">Rejected Events</h4>
<?php if (empty($rejected)): ?>
<p class="text-muted">No event has currently been rejected.</p>
<?php else: ?>
<table class="table table-bordered">
<thead class="table-danger">
<tr>
    <th>Title</th>
    <th>Organizer</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($rejected as $e): ?>
<tr>
    <td><?= htmlspecialchars($e['title']) ?></td>
    <td><?= htmlspecialchars($e['username']) ?></td>
    <td><span class="badge bg-danger">Rejected</span></td>
    <td>
        <button class="btn btn-secondary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#eventModal"
             data-event='<?= htmlspecialchars(json_encode($e), ENT_QUOTES, "UTF-8") ?>'>
            View
        </button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>


<div class="modal fade" id="eventModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content">

<div class="modal-header bg-dark text-white">
    <h5 class="modal-title">Event Details</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

    <div id="eventDetails"></div>

</div>

<div class="modal-footer">
    <form method="POST" action="handle_approval.php" class="d-inline w-100">
        <input type="hidden" name="event_id" id="modal_event_id">
        <input type="hidden" name="action" id="modal_action">

        <div id="remarkSection">
        <textarea name="remark" id="modal_remark" class="form-control mb-2" 
        rows="4"placeholder="Write a reason for approval or rejection (required) Please contact the organizer before approval" required></textarea>
    </div>
    <div id="actionButtons">
        <button type="submit" class="btn btn-success me-2" onclick="document.getElementById('modal_action').value='approved'">Approve</button>
        <button type="submit" class="btn btn-danger" onclick="document.getElementById('modal_action').value='rejected'">Reject</button>
</div>
    </form>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

</div>
</div>
</div>



</div>
</div>


<script>
const eventModal = document.getElementById('eventModal');

eventModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;

    let eventData;
    try {
        eventData = JSON.parse(button.getAttribute('data-event'));
    } catch (e) {
        console.error("Invalid event data", e);
        document.getElementById('eventDetails').innerHTML =
            "<div class='text-danger'>Failed to load event details.</div>";
        return;
    }

    document.getElementById('eventDetails').innerHTML = `
        <h4>${eventData.title}</h4>
        <p><strong>Organizer:</strong> ${eventData.username}</p>
        <p><strong>Start Date & Time:</strong> ${eventData.event_date}</p>
        <p><strong>End Date & Time:</strong> ${eventData.end_date}</p>
        <p><strong>Capacity:</strong> ${eventData.event_capacity}</p>
        <p><strong>Status:</strong> ${eventData.event_status}</p>
        <p><strong>Contact:</strong> ${eventData.contact_number && eventData.contact_number.trim() !== '' ? eventData.contact_number : 'N/A'}</p>
         <p><strong>Owner Phone Number:</strong> ${eventData.phone_number ?? 'N/A'}</p>
        <p><strong>Owner Email:</strong> ${eventData.gmail ?? 'N/A'}</p>
        <p><strong>Organized By":</strong> ${eventData.club_name ?? 'N/A'}</p>
        <p><strong> Club Description":</strong> ${eventData.club_description ?? 'N/A'}</p>
        <p><strong> Club Email":</strong> ${eventData.club_email ?? 'N/A'}</p>
        <p><strong> Club Phone Number":</strong> ${eventData.club_phone ?? 'N/A'}</p>


    <hr>

    <h5 class="mt-3">Venue Information</h5>

    ${
        eventData.venue_name
        ? `
            <p><strong>Venue Name:</strong> ${eventData.venue_name}</p>
            <p><strong>Address:</strong> ${eventData.venue_address}</p>
            <p><strong>City:</strong> ${eventData.venue_city}</p>
            <p><strong>Postcode:</strong> ${eventData.venue_postcode}</p>
            <p><strong>Venue Capacity:</strong> ${eventData.venue_capacity}</p>
            <p><strong>Remark:</strong> ${eventData.remark ?? 'None'}</p>
        `
        : `<p class="text-muted">No venue assigned</p>`
    }

    ${
        eventData.venue_image
        ? `<h5 class="mt-3">Venue Image</h5>
        <img src="venue/${eventData.venue_image}" class="img-fluid rounded mt-3">`
        : `<p class="text-muted">No venue image is displayed</p>`
    }

    <hr>
    <h5> Event Description</h5>
        <p>${eventData.event_description ?? ''}</p>
        ${
            eventData.event_image
            ? `<h5 class="mt-3">Event Poster</h5>
            <img src="uploads/${eventData.event_image}" class="img-fluid rounded mt-3">`
            : `<p class="text-muted">No event image is displayed</p>`
        }
    `;

    document.getElementById('modal_event_id').value = eventData.id;
    document.getElementById('modal_event_id_reject').value = eventData.id;
});
</script>

<script>
const modal = document.getElementById('eventModal');

modal.addEventListener('show.bs.modal', e => {
    const data = JSON.parse(e.relatedTarget.dataset.event);

    document.getElementById('modal_event_id').value = data.id;

    const actionArea = document.getElementById('actionButtons');
    const remark = document.getElementById('remarkSection');

    if (data.approval_status === 'pending') {
        actionArea.style.display = 'block';
        remark.style.display = 'block';
    } else {
        actionArea.style.display = 'none';
        remark.style.display = 'none';
    }
});
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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" 
 crossorigin="anonymous">
</script>
</body>
</html>