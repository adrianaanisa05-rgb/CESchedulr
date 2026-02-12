<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    

    $stmt = $conn->prepare("SELECT id, username, password, user_type, users_image FROM users WHERE username = ? AND password=?");
    $stmt->bind_param("ss", $username,$password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1 ) {
        $row = $result->fetch_assoc();
         session_regenerate_id(true);

       
        $_SESSION['username'] = $row['username'];
        $_SESSION['user_type'] = $row['user_type'];
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['users_image']=$row['users_image'] ?? null;
        
        if ($row['user_type'] == 'admin') {
         header("Location: manage_user.php");
         exit();
    } 
     else if ($row['user_type'] =='organizer'){
        header("Location: organizer_event.php");
        exit();
     }
    
    else{ 
        header("Location: participant_dashboard.php");
        exit();
    }
    } else {
        $error = "Invalid username or password, Please try again";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
 <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Event Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="styles.css?v=4">
    <title>Login</title>
    <style>
    body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin:0;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
     <div class="login-card">
        <img src="pictures/CESchedulr Logo.png"
         alt="App Logo" class="login-logo mb-1">
        <h2>Welcome Back!</h2>

        <?php if (isset($error)) { echo "<div class='error-message'>$error</div>"; } ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
           <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-check mb-3 show-password">
                <input class="form-check-input" type="checkbox" id="showPassword">
                <label class="form-check-label" for="showPassword">
                    Show Password
                </label>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-login">Login</button>
            </div>

            <a href="user_register.php" class="register-link">Don't have an account? Register here</a>
        </form>
    </div>
    
    <script>
        document.getElementById('showPassword').addEventListener('change', function () {
            const passwordInput = document.getElementById('password');
            passwordInput.type = this.checked ? 'text' : 'password';
        });
    </script>

<script
 src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>