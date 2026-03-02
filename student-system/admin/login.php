<?php
require_once __DIR__ . '/../includes/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location:dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Find user by username or email
        $query = "SELECT * FROM admins WHERE (username = ? OR email = ?) AND is_active = 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $admin = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $admin['password'])) {
                // Set session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Update last login
                $update = "UPDATE admins SET last_login = NOW() WHERE id = " . $admin['id'];
                mysqli_query($conn, $update);
                
                $success = 'Login successful! Redirecting...';
                
                // Use JavaScript redirect to ensure it works
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'dashboard.php';
                    }, 1500);
                </script>";
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; text-align: center; margin: 10px 0; }
        .success { color: green; text-align: center; margin: 10px 0; }
        .info { background: #e7f3ff; padding: 10px; border-radius: 4px; margin-top: 15px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" value="admin123" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <div class="info">
            <strong>Test Credentials:</strong><br>
            admin / admin123<br>
            <a href="register.php">Register New Admin</a>
        </div>
    </div>
</body>
</html>