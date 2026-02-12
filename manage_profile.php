<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$newImageName = null;

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? "";
unset($_SESSION['errors'], $_SESSION['success']);


$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_changes'])) {

    $username = trim($_POST['username']);
    $email    = trim($_POST['gmail']);
    $phone    = trim($_POST['phone_number']);
    $password = trim($_POST['password']);

    $image_sql = "";
    $params = [$username, $email, $password, $phone];
    $types  = "ssss";

   
    if (!empty($_FILES['profile_image']['name'])) {

        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (!in_array($ext, $allowed)) {
            $_SESSION['errors'][] = "Invalid image type.";
            header("Location: manage_profile.php");
            exit;
        }
        $newName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $newPath = "pictures/" . $newName;

        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $newPath)) {
            $_SESSION['errors'][] = "Image upload failed.";
            header("Location: manage_profile.php");
            exit;
        }

        
        if (!empty($user['users_image']) && $user['users_image'] !== 'default.png') {
            $oldPath = "pictures/" . $user['users_image'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $image_sql = ", users_image = ?";
        $params[] = $newName;
        $types .= "s";
        $newImageName = $newName;
    }

    $params[] = $user_id;
    $types .= "i";

    $sql = "UPDATE users 
            SET username=?, gmail=?, password=?, phone_number=? 
            $image_sql 
            WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        $_SESSION['success'] = "Profile updated successfully.";
          if ($newImageName !== null) {
        $_SESSION['users_image'] = $newImageName;
    }

    } else {
        $_SESSION['errors'][] = "Update failed.";
    }

    $stmt->close();
    header("Location: manage_profile.php");
    exit;
}

if (isset($_POST['delete_account'])) {

if (!empty($user['users_image']) && $user['users_image'] !== 'default.png') {
        $path = "pictures/" . $user['users_image'];
        if (file_exists($path)) {
            unlink($path);
        }
    }


    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    session_destroy();
    header("Location: login.php");
    exit;
}

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
<h1 class="page-title">User Profile</h1>

<div class="container mt-5" style="max-width:600px">

<h2 class="text-center mb-4">My Profile</h2>

<?php if ($success): ?>
<div class="alert alert-success text-center"><?= $success ?></div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="alert alert-danger">
<?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">


<div class="text-center mb-3">
<img src="pictures/<?= htmlspecialchars($user['users_image'] ?? 'default.png') ?>"
     class="rounded-circle"
     width="120" height="120"
     style="object-fit:cover;">
</div>

<div class="mb-3">
<label class="form-label">Change Profile Image</label>
<input type="file" name="profile_image" class="form-control">
</div>


<div class="mb-3">
<label class="form-label">Username</label>
<input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
</div>


<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="gmail" class="form-control" value="<?= htmlspecialchars($user['gmail']) ?>" required>
</div>


<div class="mb-3">
<label class="form-label">Password</label>
<input type="text" name="password" class="form-control" value="<?= htmlspecialchars($user['password']) ?>" required>
</div>


<div class="mb-3">
<label class="form-label">Phone Number</label>
<input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number']) ?>" required>
</div>


<button type="submit" name="save_changes" class="btn btn-primary w-100 mb-3">
Save Changes
</button>

</form>


<form method="post" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
<button type="submit" name="delete_account" class="btn btn-danger w-100">
Delete Account
</button>
</form>

</div>



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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" 
crossorigin="anonymous">
</script>

</body>
</html>
