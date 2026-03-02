<?php
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

requireAdminLogin();

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Get admin info
$admin = getAdminById($conn, $admin_id);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        $query = "UPDATE admins SET full_name = ?, email = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $full_name, $email, $admin_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['admin_name'] = $full_name;
            $message = 'Profile updated successfully!';
            logAdminAction($conn, $admin_id, 'PROFILE_UPDATE', 'Updated profile information');
            $admin['full_name'] = $full_name;
            $admin['email'] = $email;
        } else {
            $error = 'Failed to update profile.';
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if (password_verify($current, $admin['password'])) {
            if ($new == $confirm) {
                if (strlen($new) >= 6) {
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    $query = "UPDATE admins SET password = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "si", $hash, $admin_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $message = 'Password changed successfully!';
                        logAdminAction($conn, $admin_id, 'PASSWORD_CHANGE', 'Changed password');
                    } else {
                        $error = 'Failed to change password.';
                    }
                } else {
                    $error = 'Password must be at least 6 characters.';
                }
            } else {
                $error = 'New passwords do not match.';
            }
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #667eea;
            font-weight: bold;
        }
        .profile-body {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-label {
            width: 150px;
            font-weight: 600;
            color: #718096;
        }
        .info-value {
            flex: 1;
            color: #2d3748;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            color: #718096;
            font-weight: 500;
        }
        .tab.active {
            color: #667eea;
            border-bottom: 2px solid #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <nav class="navbar admin-nav">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">AdminPortal</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="students.php">Students</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="assignments.php">Assignments</a></li>
                <li><a href="announcements.php">Announcements</a></li>
                <li><a href="admins.php">Admins</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container profile-container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
            </div>
            <h2><?php echo htmlspecialchars($admin['full_name']); ?></h2>
            <p><?php echo ucfirst($admin['role']); ?> | Username: <?php echo htmlspecialchars($admin['username']); ?></p>
        </div>

        <div class="profile-body">
            <div class="tabs">
                <div class="tab active" onclick="showTab('info')">Profile Information</div>
                <div class="tab" onclick="showTab('security')">Security Settings</div>
            </div>

            <div id="info" class="tab-content active">
                <form method="POST">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username (cannot be changed)</label>
                        <input type="text" class="form-control" id="username" 
                               value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" class="form-control" id="role" 
                               value="<?php echo ucfirst($admin['role']); ?>" disabled>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <div id="security" class="tab-content">
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <small>Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>