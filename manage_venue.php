<?php
session_start();
include 'db_connect.php';
$errors=[];
$success_message=[];
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST['create_venue'])){
$venue_name=$_POST['venue_name'];
$venue_city=$_POST['venue_city'];
$venue_address=$_POST['venue_address'];
$venue_postcode=$_POST['venue_postcode'];
$venue_capacity=$_POST['venue_capacity'];
$remark=!empty($_POST['remark']) ? trim($_POST['remark']) : null;
$user_id=$_SESSION['user_id'];
$venue_image=null;

if (!empty($_FILES['venue_image']['name'])) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['venue_image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {    
            $errors[] = "Your image file type is not allowed.";
        } else {
            $newName = time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
            $path = "venue/".$newName;

            if (move_uploaded_file($_FILES['venue_image']['tmp_name'], $path)) {
                $venue_image = $newName;
            } else {
                $errors[] = "Image upload failed.";
            }
        }
    }
if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO venue
            (user_id, venue_name, venue_address, venue_city, venue_postcode, venue_capacity, remark, venue_image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
         $stmt->bind_param("issssiss" , $user_id, $venue_name, $venue_address,$venue_city,$venue_postcode,$venue_capacity,
         $remark,
         $venue_image
        );
        if ($stmt->execute()){
        $success_message[]="Venue created successfully!";
        }
        else{
            $errors[]="Error creating venue, Please try again:". $stmt->error;
        }
}
}

if ($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST['update_venue'])){
    $id = $_POST['edit_id'];
    $venue_name = $_POST['edit_venue_name'];
    $venue_city = $_POST['edit_venue_city'];
    $venue_address = $_POST['edit_venue_address'];
    $venue_postcode = $_POST['edit_venue_postcode'];
    $venue_capacity = $_POST['edit_venue_capacity'];
    $remark = !empty($_POST['edit_remark']) ? trim($_POST['edit_remark']) : null;

    $venue_image = null;
    $image_sql = "";
    $params = [$venue_name, $venue_address, $venue_city, $venue_postcode, $venue_capacity, $remark];
    $types = "sssiss";

    // Handle image upload
    if (!empty($_FILES['edit_venue_image']['name'])) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['edit_venue_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newName = time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
            if (move_uploaded_file($_FILES['edit_venue_image']['tmp_name'], "venue/".$newName)) {
                $venue_image = $newName;
                $image_sql = ", venue_image=?";
                $params[] = $venue_image;
                $types .= "s";
            } else {
                $errors[] = "Image upload failed.";
            }
        } else {
            $errors[] = "Invalid image type.";
        }
    }

    $params[] = $id;
    $types .= "i";

   if (empty($errors)) {
    $sql = "UPDATE venue SET venue_name=?, venue_address=?, venue_city=?, venue_postcode=?, venue_capacity=?, remark=? $image_sql WHERE venue_id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $success_message[] = "Venue updated successfully!";
        } else {
            $errors[] = "Error updating venue: ".$stmt->error;
        }
    } else {
        $errors[] = "Failed to prepare statement: ".$conn->error;
    }
}


}

$sql = $conn->prepare("
    SELECT v.*, u.username
    FROM venue v
    JOIN users u ON v.user_id = u.id
    WHERE v.user_id = ?
");
$sql->bind_param("i", $_SESSION['user_id']);
$sql->execute();
$venues = $sql->get_result()->fetch_all(MYSQLI_ASSOC);
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
        <h1 class="page-title">Manage Venue</h1>
 <?php
        if (isset($_GET['message']) && $_GET['message'] == 'deleted') {
    echo '<div class="alert alert-success">Venue deleted successfully!</div>';
}

if (isset($_GET['error']) && $_GET['error'] == 'delete_failed') {
    echo '<div class="alert alert-danger">Failed to delete venue. Please try again.</div>';
} ?>

 <button 
      type="button" 
      class="btn btn-primary mb-3"
      data-bs-toggle="modal"
      data-bs-target="#venueModal">
      + Create Venue
    </button>

<div class="modal fade" id="venueModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
      enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Create Venue</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Venue Name</label>
              <input type="text" name="venue_name" class="form-control"placeholder="Enter venue name here"
               required>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">City</label>
              <input type="text" name="venue_city" class="form-control" placeholder="Enter city details here" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="venue_address" class="form-control" placeholder="Enter venue address here"
            required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Postcode</label>
              <input type="text" name="venue_postcode" class="form-control" placeholder="Enter postcode here"
               required>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Capacity</label>
              <input type="number" name="venue_capacity" class="form-control" placeholder="Enter capacity here" 
              required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Remark (Optional)</label>
            <textarea name="remark" class="form-control"
            rows="5"placeholder="Enter remarks here"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Venue Image (Optional)</label>
            <input type="file" name="venue_image" class="form-control" accept="image/*">
          </div>

        </div>

        <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="create_venue" class="btn btn-primary">
            Submit Venue
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<div class="modal fade" id="editVenueModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Edit Venue</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="edit_id" id="edit_id">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Venue Name</label>
              <input type="text" name="edit_venue_name" id="edit_venue_name" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">City</label>
              <input type="text" name="edit_venue_city" id="edit_venue_city" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="edit_venue_address" id="edit_venue_address" class="form-control" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Postcode</label>
              <input type="text" name="edit_venue_postcode" id="edit_venue_postcode" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Capacity</label>
              <input type="number" name="edit_venue_capacity" id="edit_venue_capacity" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Remark (Optional)</label>
            <textarea name="edit_remark" id="edit_remark" class="form-control" rows="5"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Current Image</label><br>
            <img id="edit_current_image" src="" width="200" height="150" style="border-radius:8px; object-fit:cover;">
          </div>

          <div class="mb-3">
            <label class="form-label">Change Venue Image (Optional)</label>
            <input type="file" name="edit_venue_image" class="form-control" accept="image/*">
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update_venue" class="btn btn-primary">Update Venue</button>
        </div>
      </form>

    </div>
  </div>
</div>

<table class="table table-light table-hover">
  <thead>
    <tr>
      <th scope="col">No</th>
      <th scope="col">Venue Image</th>
      <th scope="col">Venue Name</th>
      <th scope="col">City</th>
      <th scope="col">Address</th>
      <th scope="col">Postcode</th>
      <th scope="col">Capacity</th>
      <th scope="col">Remark</th>
      <th scope="col">Created By</th>
      <th scope="col">Action</th>

      
    </tr>
  </thead>
  <tbody>
<?php
    $i=1;
    if (!empty($venues)) {
     foreach ($venues as $venue):?>
    <tr>
      <th scope="row"><?php echo $i++; ?></th>
      <td><?php if (!empty($venue["venue_image"])):?>
               <img src="venue/<?php echo htmlspecialchars($venue['venue_image']);?>" alt="Venue Image" width="400" height="300"
               style="border-radius: 8px;">
               <?php else:?>
               No Image Provided
               <?php endif; ?>
            </td>
        <td><?php echo htmlspecialchars($venue['venue_name']); ?></td>
        <td><?php echo htmlspecialchars($venue['venue_city']); ?></td>
        <td><?php echo htmlspecialchars($venue['venue_address']); ?></td>
        <td><?php echo htmlspecialchars($venue['venue_postcode']); ?></td>
        <td><?php echo htmlspecialchars($venue['venue_capacity']); ?></td>
        <td><?php if (!empty($venue['remark'])):?> <?php echo htmlspecialchars($venue['remark']);?>
        <?php else:?> No Remark Provided <?php endif;?>
        </td>
       <td><?php echo htmlspecialchars($venue['username']); ?></td>
        <td><button class="btn btn-primary btn-sm editVenueBtn"
          data-bs-toggle="modal"
          data-bs-target="#editVenueModal"
          data-id="<?php echo $venue['venue_id']; ?>"
          data-name="<?php echo htmlspecialchars($venue['venue_name']); ?>"
          data-city="<?php echo htmlspecialchars($venue['venue_city']); ?>"
          data-address="<?php echo htmlspecialchars($venue['venue_address']); ?>"
          data-postcode="<?php echo htmlspecialchars($venue['venue_postcode']); ?>"
          data-capacity="<?php echo htmlspecialchars($venue['venue_capacity']); ?>"
          data-remark="<?php echo htmlspecialchars($venue['remark']); ?>"
          data-image="<?php echo htmlspecialchars($venue['venue_image']); ?>"
          >Edit
        </button>
       <form method="POST" action="delete_venue.php" style="display:inline;">
    <input type="hidden" name="venue_id" value="<?php echo $venue['venue_id']; ?>">
    <button type="submit" class="btn btn-danger btn-sm" 
        onclick="return confirm('Are you sure you want to delete this venue?');">
        Delete
    </button>
</form>
        </td>
    </tr>
    <?php endforeach;
    } else { ?>
        <tr>
            <td colspan="10" class="text-center">No venues created yet.</td>
        </tr>
    <?php } ?>
</tbody>
 </table>



</div>
</div>

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
document.addEventListener("DOMContentLoaded", function() {
  document.querySelectorAll(".editVenueBtn").forEach(button => {
    button.addEventListener("click", function() {
      const id       = this.dataset.id;
      const name     = this.dataset.name;
      const city     = this.dataset.city;
      const address  = this.dataset.address;
      const postcode = this.dataset.postcode;
      const capacity = this.dataset.capacity;
      const remark   = this.dataset.remark;
      const image    = this.dataset.image;

      document.getElementById("edit_id").value = id;
      document.getElementById("edit_venue_name").value = name;
      document.getElementById("edit_venue_city").value = city;
      document.getElementById("edit_venue_address").value = address;
      document.getElementById("edit_venue_postcode").value = postcode;
      document.getElementById("edit_venue_capacity").value = capacity;
      document.getElementById("edit_remark").value = remark || '';
      document.getElementById("edit_current_image").src = image ? "venue/" + image : "";
    });
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" 
 crossorigin="anonymous">
</script>
</body> 
</html> 

