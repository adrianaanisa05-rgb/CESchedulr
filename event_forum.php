<?php
session_start();
include 'db_connect.php';
$errors=[];
$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'];

$limit = 5; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); 
$offset = ($page - 1) * $limit;

$sql = "
SELECT e.*, u.username,c.club_name
FROM events e
JOIN users u ON e.user_id = u.id
LEFT JOIN club c ON e.club_id = c.id
WHERE e.approval_status = 'approved'
ORDER BY e.event_date DESC
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);

$sqc="SELECT COUNT(id) FROM events WHERE approval_status = 'approved'";
$res=$conn->query($sqc);
$row=$res->fetch_row();
$total_events=$row[0];

$total_pages = ceil($total_events / $limit);


$conn->close();
?>
<!DOCTYPE html>
<html Lang="en">
<head>
<meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title>Participant Dashboard</title>
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
 <link rel="stylesheet" href="styles.css?v=4"> 
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" 
 rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

<style>
.highlight {
  background-color: yellow;
  font-weight: 600;
  border-radius: 2px;
  padding: 0 2px;
}
</style>

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

 <!--Main Content-->
<div class="main-content" id="mainContent">
<div class="header">
<h1 class="page-title">Browse Event</h1>
<section class="available-events"> <h2 class="section-title">Available Event: <?php echo $total_events ?></h2>
<h2 class="section-subtitle">Browse and join events that interest you</h2>


<input
  type="text"
  id="searchInput"
  class="form-control mb-3"
  placeholder="Search events..."
>

<table class="table table-light table-hover">
  <thead>
    <tr>
      <th scope="col">No</th>
      <th scope="col">Poster</th>
      <th scope="col">Event Title</th>
      <th scope="col">Date</th>
      <th scope="col">Status</th>
      <th scope="col">Created By</th>
      <th scope="col">Organized By</th>
      <th scope="col">Action</th>
      
    </tr>
  </thead>
  <tbody id="eventTableBody">
    <?php
    $i=$offset + 1;
     foreach ($events as $event):?>
    <tr>
      <th scope="row"><?php echo $i++; ?></th>
      <td><?php if (!empty($event["event_image"])):?>
               <img src="uploads/<?php echo htmlspecialchars($event['event_image']);?>" alt="Event Image" width="250" height="300"
               style="border-radius: 8px;">
               <?php else:?>
               No Image Provided
               <?php endif; ?>
            </td>
<td class="searchable"><?php echo htmlspecialchars($event['title']); ?></td>
<td class="searchable"><?php echo htmlspecialchars($event['event_date']); ?></td>
<td class="searchable"><?php echo htmlspecialchars($event['event_status']);?></td>
<td class="searchable"><?php echo htmlspecialchars($event['username']) ?></td>
<td class="searchable"><?php echo htmlspecialchars($event['club_name'] ?? 'N/A'); ?></td>
      <td> <button 
    class="btn btn-primary btn-sm" 
    data-bs-toggle="modal" 
    data-bs-target="#eventModal" 
    data-id="<?php echo $event['id']; ?>">
    View Event
</button>

</td>
    </tr>
    <?php endforeach;?>
  </tbody>
</table>

<?php if ($total_pages > 1): ?>
<nav class="mt-4">
  <ul class="pagination justify-content-center">

    <!-- Previous -->
    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
      <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
    </li>

    <!-- Page Numbers -->
    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
      <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
        <a class="page-link" href="?page=<?php echo $p; ?>">
          <?php echo $p; ?>
        </a>
      </li>
    <?php endfor; ?>

    <!-- Next -->
    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
      <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
    </li>

  </ul>
</nav>
<?php endif; ?>


</section>

 

</div>
</div>
<!-- Event Modal -->
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
        <button type="button" id=joinEventBtn class="btn btn-primary">Join Event</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
 document.addEventListener("DOMContentLoaded", () => 
 {
 const eventModal = document.getElementById("eventModal");

eventModal.addEventListener("show.bs.modal", function(event) {
    let button = event.relatedTarget;
    let eventId = button.getAttribute("data-id");

    // Show loading
    document.getElementById("eventModalContent").innerHTML =
        "<div class='text-center p-3'>Loading...</div>";

    // AJAX fetch event details
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
            } else if (status.toLowerCase() === "concluded") {
                joinBtn.disabled = true;
                joinBtn.classList.add("btn-secondary");
                joinBtn.classList.remove("btn-primary");
                joinBtn.textContent = "Event Concluded";
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

            // redirect user
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
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchInput");
  const tableBody = document.getElementById("eventTableBody");

  let typingTimer;
  const delay = 400;

  function escapeRegex(text) {
  return text.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

  function highlightText(keyword) {
    if (!keyword) return;
    const safeKeyword = escapeRegex(keyword);
    const regex = new RegExp(`(${safeKeyword})`, "gi");

    document.querySelectorAll(".searchable").forEach(el => {
    el.innerHTML = el.textContent.replace(
      regex,
      `<span class="highlight">$1</span>`
    );
  });
}

  searchInput.addEventListener("keyup", () => {
    clearTimeout(typingTimer);

    typingTimer = setTimeout(() => {
      const keyword = searchInput.value.trim();

      fetch("search_events.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "search=" + encodeURIComponent(keyword)
      })
      .then(res => res.text())
      .then(html => {
        tableBody.innerHTML = html;
        highlightText(keyword);
      });
    }, delay);
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" 
 crossorigin="anonymous">
 </script>

</body>
</html>
