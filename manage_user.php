<?php
session_start();
include 'db_connect.php';
$errors=[];
$create_message=[];
$current_page = basename($_SERVER['PHP_SELF']);


if ($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST['create_user'])){
$username  = trim($_POST['username']);
$email     = $_POST['gmail'];
$password  = $_POST['password'];
$user_type = $_POST['user_type'];
$phone     = trim($_POST['phone_number']);
$user_image= null;

if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $errors[] = "This username already exists.";
        }
        $check_stmt->close();
    }

if (!empty($_FILES['users_image']['name'])){

$fileName = $_FILES['users_image']['name'];
$tempName=$_FILES['users_image']['tmp_name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedTypes = array("jpg","jpeg","png","gif");



if(!in_array($ext,$allowedTypes)){
$errors[] ="Your image file type is not allowed.";
} else{
 $uniqueName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
 $targetPath = "pictures/" . $uniqueName;

  if (move_uploaded_file($tempName,$targetPath)){
 $user_image = $uniqueName; 
}
 else {
            $errors[] = "Failed to upload image.";
        }
      }
   }
 if (count($errors)=== 0){
   $sql = $conn->prepare("INSERT INTO users (username, gmail, password, user_type, phone_number, users_image) 
   VALUES (?, ?, ?, ?, ?, ?)");
$sql->bind_param("ssssis",$username,$email,$password,$user_type,$phone,$user_image);
if ($sql->execute()) {
                $create_message[] = "user created successfully and image uploaded.";
            } else {
                $errors[] = "Error creating user: " . $sql->error;
            }

}

}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_user'])) {

    $id        = $_POST['edit_id'];
    $username  = trim($_POST['edit_username']);
    $email     = trim($_POST['edit_gmail']);
    $user_type = $_POST['edit_user_type'];
    $phone     = trim($_POST['edit_phone']);
    $errors =[];
    $update_message = [];

    $image_sql = "";
    $params = [$username, $email, $user_type, $phone];
    $types  = "ssss";

    // Handle image upload
    if (!empty($_FILES['edit_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (in_array($ext, $allowed)) {
            $newName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            if (move_uploaded_file($_FILES['edit_image']['tmp_name'], "pictures/" . $newName)) {
                $image_sql = ", users_image = ?";
                $params[] = $newName;
                $types .= "s";
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image type.";
        }
    }

    $params[] = $id;
    $types .= "i";

    if (empty($errors)) {
        $sql = "UPDATE users 
                SET username=?, gmail=?, user_type=?, phone_number=? 
                $image_sql 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $update_message[] = "User updated successfully.";
            } else {
                $errors[] = "Error updating user: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Failed to prepare statement: " . $conn->error;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];

    $conn->begin_transaction();

    try {
        
        $stmt = $conn->prepare("DELETE FROM activity WHERE user_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

        
        $stmt = $conn->prepare("DELETE FROM comments WHERE user_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

       
        $stmt = $conn->prepare("DELETE FROM participant WHERE user_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

        
        $stmt = $conn->prepare("DELETE FROM notifications WHERE sender_id = ? OR receiver_id = ?");
        $stmt->bind_param("ii", $delete_id, $delete_id);
        $stmt->execute();
        $stmt->close();

        
        $stmt = $conn->prepare("DELETE FROM events WHERE user_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

        
        $stmt = $conn->prepare("DELETE FROM venue WHERE user_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header("Location: manage_user.php?msg=deleted");
        exit();
        $delete_message = "User and all related records deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "Error deleting user: " . $e->getMessage();
    }
}

$sql="SELECT * FROM users";
$result=$conn->query($sql);
$users= $result->fetch_all(MYSQLI_ASSOC);
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
        <h1 class="page-title">Manage User</h1>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUser">
+Add a new user
</button>


<?php
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    echo "<div class='alert alert-success'>User deleted successfully!</div>";
}

if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']); // prevent XSS
    echo "<div class='alert alert-danger'>$error</div>";
}
?>

 <?php if (!empty($create_message)): ?>
    <ul style="color: green; text-align:center; list-style:none; padding:0;">
      <?php foreach ($create_message as $msg): ?>
        <li><?php echo $msg; ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (!empty($update_message)): ?>
    <ul style="color: green; text-align:center; list-style:none; padding:0;">
      <?php foreach ($update_message as $upd): ?>
        <li><?php echo $upd; ?></li>
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

<div class="modal fade" id="createUser" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="usermodalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
    enctype="multipart/form-data">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="usermodalLabel">Modal title</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <div class="mb-3">

         <label for="Username" class="form-label">Username</label>
         <input
            type="text"
            name="username"
            id="Username"
            class="form-control"
            placeholder="Enter Username"
            required/>
</div>

 <div class="mb-3">

         <label for="Password" class="form-label">Password</label>
         <input
            type="password"
            name="password"
            id="Password"
            class="form-control"
            placeholder="Enter Password"
            required/>
</div>

 <div class="mb-3">
  <label for="email" class="form-label">Email</label>
  <input
    type="email"
    name="gmail"
    id="email"
    class="form-control"
    placeholder="Enter email address example: john@gmail.com"
    required/>
</div>

<div class="mb-3">
    <label for="Usertype" class="form-label">User Type</label>
<select name="user_type" id="Usertype" class="form-select" required>
        <option value="" disabled selected>Select User type</option>
        <option value="admin">admin</option>
        <option value="organizer">organizer</option>
        <option value="participant">participant</option>
    </select>
</div>

<div class="mb-3">
  <label for="phone" class="form-label">Phone Number</label>
  <input
  type="tel"
    name="phone_number"
    id="phone"
    class="form-control"
    inputmode="numeric"
    pattern="^\+?[0-9]{10,15}$"
    placeholder="Phone Number Example: 0123456789 or +60123456789"
    required
  />
</div>
 <div class="mb-3">
         <label for="Userimg" class="form-label">Image(Optional)</label>
         <input
            type="file"
            name="users_image"
            id="Userimg"
            class="form-control"
            placeholder="Image"
            accept="image/*"
            />
</div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" name=create_user class="btn btn-primary">Create User</button>
      </div>
</form>
    </div>

</div>
</div>


<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <form method="post" enctype="multipart/form-data">

        <div class="modal-header">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <input type="hidden" name="edit_id" id="edit_id">

          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="edit_username" id="edit_username" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="edit_gmail" id="edit_gmail" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">User Type</label>
            <select name="edit_user_type" id="edit_user_type" class="form-select" required>
              <option value="admin">Admin</option>
              <option value="organizer">Organizer</option>
              <option value="participant">Participant</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" name="edit_phone" id="edit_phone" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Change Image (optional)</label>
             <label class="form-label">Current Image</label><br>
  <img id="edit_current_image" src="" width="100" height="100" class="rounded-circle" style="object-fit: cover;">
            <input type="file" name="edit_image" class="form-control">
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
        </div>

      </form>

    </div>
  </div>
</div>


<?php if(!empty($users)): ?>
<table class="table table-light table-hover">
  <thead>
    <tr>
      <th scope="col">No</th>
      <th scope="col">Username</th>
      <th scope="col">Email</th>
      <th scope="col">Password</th>
      <th scope="col">User type</th>
      <th scope="col">Phone Number</th>
      <th scope="col">User Image</th>
      <th scope="col">Action</th>   
    </tr>
  </thead>
  <tbody>
    <?php
    $i=1;
     foreach ($users as $user):?>
    <tr>
      <th scope="row"><?php echo $i++; ?></th>
      <td><?php echo htmlspecialchars($user['username']) ?></td>
      <td><?php echo htmlspecialchars($user['gmail']); ?></td>
      <td><?php echo htmlspecialchars($user['password']); ?></td>
      <td><?php echo htmlspecialchars($user['user_type']); ?></td>
      <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
      <td><?php if (!empty($user["users_image"])):?>
               <img src="pictures/<?php echo htmlspecialchars($user['users_image']);?>" alt="User Image" class="rounded-circle"
     width="80" height="80"
     style="object-fit: cover;">
               <?php else:?>
               No User Image Provided
               <?php endif; ?></td>
      <td> 
     <button class="btn btn-primary btn-sm editUserBtn"
  data-bs-toggle="modal"
  data-bs-target="#editUserModal"

  data-id="<?php echo $user['id']; ?>"
  data-username="<?php echo htmlspecialchars($user['username']); ?>"
  data-email="<?php echo htmlspecialchars($user['gmail']); ?>"
  data-usertype="<?php echo htmlspecialchars($user['user_type']); ?>"
  data-phone="<?php echo htmlspecialchars($user['phone_number']); ?>"
  data-image="<?php echo htmlspecialchars($user['users_image']); ?>"
  >
  Edit
</button>

  <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this user?');">
    <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
</form>
      </td>
    </tr>
    <?php endforeach;?>
  </tbody>
</table>
<?php else: ?>
<p class="no-events">No users registered at the moment.</p>
<?php endif; ?>

  </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

  document.querySelectorAll(".editUserBtn").forEach(button => {
    button.addEventListener("click", function () {

      document.getElementById("edit_id").value = this.dataset.id;
      document.getElementById("edit_username").value = this.dataset.username;
      document.getElementById("edit_gmail").value = this.dataset.email;
      document.getElementById("edit_user_type").value = this.dataset.usertype;
      document.getElementById("edit_phone").value = this.dataset.phone;
      document.getElementById("edit_current_image").src = "pictures/" + this.dataset.image;

    });
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
