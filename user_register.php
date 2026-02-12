<?php 
session_start();
include 'db_connect.php';

function sanitizeInput($input) {
    $input = trim($input);                    
    $input = stripslashes($input);             
    $input = htmlspecialchars($input);         
    return $input;
}
$errors=[];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_account'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email= $_POST['gmail'];
    $phone=$_POST['phone_number'];
    $phone = str_replace('-', '', $phone);

    $username_pattern = "/^[a-zA-Z0-9-_]{3,}$/";
    $password_pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
    

if (!preg_match($username_pattern, $username)) {
        $errors[] = "Invalid username. Use at least 3 characters: letters, numbers, hyphens, and underscores only.";
    }
    if (!preg_match($password_pattern, $password)) {
        $errors[] = "Password must be at least 8 characters, with uppercase, lowercase, number, and special character.";
    }

    if (!preg_match("/^\+?[0-9]{10,15}$/", $phone)) {
    $errors[] = "Invalid phone number format. Correct phone number format: 0123456789 or +60123456789 .";
}

if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if (empty($confirm_password)) {
        $errors[] = "Please reconfirm your password.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format, Email example: user@example.com.";
    }



if (count($errors) === 0){
$check_sql = "SELECT username, gmail, phone_number FROM users WHERE username = ? OR gmail=? OR phone_number=?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("sss", $username, $email, $phone);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        if ($row['username'] === $username) {
            $errors[] = "Username is already taken.";
        }

        if ($row['gmail'] === $email) {
            $errors[] = "Email is already registered.";
        }

        if ($row['phone_number'] === $phone) {
            $errors[] = "Phone number is already registered.";
        }
    }

    $check_stmt->close();

    if (empty($errors)) {
  

   $stmt = "INSERT INTO users (username,password,gmail,phone_number,user_type) VALUES (?, ?, ?, ?,'participant')";
   $stmt = $conn->prepare($stmt);
    $stmt->bind_param("ssss", $username, $password, $email,$phone);

    if ($stmt->execute()) {
        $create_message = "Account created successfully!, Please proceed to login once you have registered.";
    } else {
        $errors[] = "Error creating user account, Please try again: " . $stmt->error;
    }
    $stmt->close();

}
}
}

$conn->close();
 ?>

 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Event Portal</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=4">

    <style>
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
    .login-logo {
    margin-top: 5rem;  
    margin-bottom: 1rem;
    width: 200px; 
    height: auto;
}
    </style>
</head>
<body>

<div class="login-card">
    <img src="pictures/CESchedulr Logo.png" alt="App Logo" class="login-logo mb-2">

    <h2>Create Account</h2>
    <p class="text-muted mb-3">Register an account before logging in</p>

    
    <?php if (isset($create_message)): ?>
        <div class="alert alert-success text-center">
            <?= $create_message ?>
        </div>
    <?php endif; ?>

    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="user_register.php" method="POST">

        <div class="mb-3 text-start">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Example: JohnDoe or jane_doe123" required>
        </div>

        <div class="mb-3 text-start">
            <label class="form-label">Email</label>
            <input type="email" name="gmail" class="form-control" placeholder="Example: john.doe@example.com" required>
        </div>

        <div class="mb-3 text-start">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone_number" class="form-control" pattern="^\+?[0-9]{10,15}$" 
            placeholder="0123456789 or +60123456789" required>
        </div>

        <div class="mb-3 text-start">
            <label class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Example: Passw0rd! or MySecure@123" required>
        </div>

        <div class="mb-3 text-start">
            <label class="form-label">Reconfirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>

        <div class="form-check mb-3 text-start">
            <input class="form-check-input" type="checkbox" id="showPassword">
            <label class="form-check-label" for="showPassword">
                Show Password
            </label>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" name="create_account" class="btn btn-login">
                Register
            </button>
        </div>

        <a href="Login.php" class="register-link">
            Already have an account? Login here
        </a>
    </form>
</div>

<script>
document.getElementById('showPassword').addEventListener('change', function () {
    const pwd = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const type = this.checked ? 'text' : 'password';

    pwd.type = type;
    confirm.type = type;
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>