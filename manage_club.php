<?php
session_start();
include 'db_connect.php';
$errors = [];
$success_message = [];
$current_page = basename($_SERVER['PHP_SELF']);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_club'])) {
    $club_name = trim($_POST['club_name']);
    $club_description = trim($_POST['club_description']);
    $club_email = trim($_POST['club_email']);
    $club_phone = trim($_POST['club_phone']);
    $created_by = $_SESSION['user_id'];

    if (empty($club_name)) {
        $errors[] = "Club name is required.";
    }

if (!filter_var($club_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

if (!preg_match("/^\+?[0-9]{10,15}$/", $club_phone)) {
    $errors[] = "Invalid phone number.";
}

$check = $conn->prepare("SELECT id FROM club WHERE club_name = ?");
$check->bind_param("s", $club_name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $errors[] = "Club name already exists.";
}
else {
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO club (club_name, club_description, club_email, club_phone, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", $club_name, $club_description, $club_email, $club_phone, $created_by);

        if ($stmt->execute()) {
            $success_message[] = "Club created successfully!";
        } else {
            $errors[] = "Club already exists.";
        }
    }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_club'])) {

    $club_id = (int)$_POST['edit_club_id'];
    $club_name = trim($_POST['edit_club_name']);
    $club_description = trim($_POST['edit_club_description']);
    $club_email = trim($_POST['edit_club_email']);
    $club_phone = trim($_POST['edit_club_phone']);
    $club_status = $_POST['edit_club_status'];


    $check = $conn->prepare("
    SELECT id FROM club 
    WHERE club_name = ? AND id != ?
");
$check->bind_param("si", $club_name, $club_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $errors[] = "Another club with this name already exists.";
}

    if (empty($club_name)) {
        $errors[] = "Club name is required.";
    }

    if (!filter_var($club_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!preg_match("/^\+?[0-9]{10,15}$/", $club_phone)) {
        $errors[] = "Invalid phone number.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE club 
            SET club_name=?, club_description=?, club_email=?, club_phone=?, status=?
            WHERE id=?
        ");
        $stmt->bind_param("sssssi",
            $club_name,
            $club_description,
            $club_email,
            $club_phone,
            $club_status,
            $club_id
        );

        if ($stmt->execute()) {
            $success_message[] = "Club updated successfully!";
        } else {
            $errors[] = "Failed to update club.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['disable_club'])) {

    $club_id = (int)$_POST['club_id'];

    $stmt = $conn->prepare("
        UPDATE club 
        SET status = 'inactive' 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->bind_param("i", $club_id);

    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $success_message[] = "Club has been disabled successfully.";
    } else {
        $errors[] = "Club is already inactive or does not exist.";
    }
}

$result = $conn->query("
    SELECT c.*, u.username
    FROM club c
    JOIN users u ON c.created_by = u.id
    ORDER BY c.created_at DESC
");
$clubs = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();

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
        <h1 class="page-title">Manage Club</h1>




<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#clubModal">
  + Create Club
</button>

<div class="modal fade" id="clubModal">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
      enctype="multipart/form-data"
    class="modal-content">
      <div class="modal-header">
        <h5>Create Club</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        
     <div class="row">
    <div class="col-md-6 mb-3">
    <label class="form-label">Club Name</label>
    <input 
    type="text"
    name="club_name" 
    class="form-control"
     placeholder="Club Name" required>
    </div>


    <div class="col-md-6 mb-3">
        <label class="form-label">Club Email</label>
        <input 
        type="email"
        name="club_email" 
        class="form-control" 
        placeholder="Enter email address example: john@gmail.com"
        required>
</div>
</div>
    <div class="mb-3">
    <label class="form-label">Club Description</label>
        <textarea name="club_description" 
        class="form-control" 
        rows="4"
        placeholder="Description"
        ></textarea>
</div>
    <div class="mb-3">
        <label class="form-label">Club Phone Number</label>
        <input 
        type="tel"
        name="club_phone" 
        class="form-control"
        inputmode="numeric"
        pattern="^\+?[0-9]{10,15}$" 
        placeholder="Phone Number Example: 0123456789 or +60123456789"
        required>
      </div>

</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type= "submit" name="create_club" class="btn btn-primary">Save</button>
      </div>

    </form>
  </div>
</div>

<div class="modal fade" id="editClubModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit Club</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" name="edit_club_id" id="edit_club_id">

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Club Name</label>
            <input type="text" name="edit_club_name" id="edit_club_name" class="form-control" required>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Club Email</label>
            <input type="email" name="edit_club_email" id="edit_club_email" class="form-control" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Club Description</label>
          <textarea name="edit_club_description" id="edit_club_description"
            class="form-control" rows="4"></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Club Phone</label>
          <input type="tel" name="edit_club_phone" id="edit_club_phone"
            class="form-control" pattern="^\+?[0-9]{10,15}$" required>
        </div>

        <div class="mb-3">
     <label class="form-label">Status</label>
    <select name="edit_club_status" id="edit_club_status" class="form-select" required>
    <option value="active">Active</option>
    <option value="inactive">Inactive</option>
  </select>
    </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="update_club" class="btn btn-primary">Update</button>
      </div>

    </form>
  </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
        <?php foreach ($success_message as $msg): ?>
            <div><?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if(!empty($clubs)): ?>
<table class="table table-light table-hover">
<thead>
<tr>
  <th>No</th>
  <th>Club Name</th>
  <th>Email</th>
  <th>Phone</th>
  <th>Status</th>
  <th>Created By</th>
  <th>Action</th>
</tr>
</thead>

<tbody>
<?php $i=1; foreach ($clubs as $club): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($club['club_name']) ?></td>
<td><?= $club['club_email'] ?></td>
<td><?= $club['club_phone'] ?></td>
<td>
  <span class="badge bg-<?= $club['status']=='active'?'success':'secondary' ?>">
    <?= ucfirst($club['status']) ?>
  </span>
</td>
<td><?= htmlspecialchars($club['username']) ?></td>
<td>
  <button class="btn btn-sm btn-warning editClubBtn"
    data-id="<?= $club['id'] ?>"
    data-name="<?= htmlspecialchars($club['club_name']) ?>"
    data-desc="<?= htmlspecialchars($club['club_description']) ?>"
    data-email="<?= htmlspecialchars($club['club_email']) ?>"
    data-phone="<?= htmlspecialchars($club['club_phone']) ?>"
    data-status="<?= htmlspecialchars($club['status']) ?>"
    data-bs-toggle="modal"
    data-bs-target="#editClubModal">Edit</button>

  <form method="POST" style="display:inline;">
    <input type="hidden" name="club_id" value="<?= $club['id'] ?>">

     <?php if ($club['status'] === 'active'): ?>
    <button name="disable_club" class="btn btn-sm btn-danger"
      onclick="return confirm('Disable this club?')">Disable
    </button>
    <?php else: ?>
      <button class="btn btn-sm btn-secondary" disabled>
        Inactive
      </button>
  <?php endif; ?>
  </form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p class="text-center">No club registered at the moment.</p>
<?php endif; ?>

</div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".editClubBtn").forEach(btn => {
        btn.addEventListener("click", function () {

            document.getElementById("edit_club_id").value = this.dataset.id;
            document.getElementById("edit_club_name").value = this.dataset.name;
            document.getElementById("edit_club_description").value = this.dataset.desc;
            document.getElementById("edit_club_email").value = this.dataset.email;
            document.getElementById("edit_club_phone").value = this.dataset.phone;
            document.getElementById("edit_club_status").value = this.dataset.status;

        });
    });
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