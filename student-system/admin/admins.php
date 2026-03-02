<?php
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

requireAdminLogin();
requireRole('super_admin');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Handle Delete
if ($action == 'delete' && $id) {
    if ($id == $_SESSION['admin_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $query = "DELETE FROM admins WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = 'Admin deleted successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'DELETE_ADMIN', "Deleted admin ID: $id");
            $action = 'list';
        } else {
            $error = 'Error deleting admin: ' . mysqli_error($conn);
        }
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = (int)$_POST['id'];
        $query = "UPDATE admins SET 
                  full_name = '$full_name',
                  email = '$email',
                  username = '$username',
                  role = '$role',
                  is_active = $is_active
                  WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            // Update password if provided
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE admins SET password = '$password' WHERE id = $id");
            }
            
            $message = 'Admin updated successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'UPDATE_ADMIN', "Updated admin: $username");
            $action = 'list';
        } else {
            $error = 'Error updating admin: ' . mysqli_error($conn);
        }
    } else {
        // Add new
        $password = password_hash($_POST['password'] ?: 'admin123', PASSWORD_DEFAULT);
        $query = "INSERT INTO admins (full_name, email, username, password, role, is_active) 
                  VALUES ('$full_name', '$email', '$username', '$password', '$role', $is_active)";
        
        if (mysqli_query($conn, $query)) {
            $new_id = mysqli_insert_id($conn);
            $message = 'Admin added successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'ADD_ADMIN', "Added admin: $username");
            $action = 'list';
        } else {
            $error = 'Error adding admin: ' . mysqli_error($conn);
        }
    }
}

// Get admin data for edit
$admin_data = null;
if ($action == 'edit' && $id) {
    $result = mysqli_query($conn, "SELECT * FROM admins WHERE id = $id");
    $admin_data = mysqli_fetch_assoc($result);
}

// Get all admins
$admins = mysqli_query($conn, "SELECT * FROM admins ORDER BY role, full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 12px; }
        .badge-super_admin { background: #9f7aea; color: white; }
        .badge-admin { background: #4299e1; color: white; }
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

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Manage Administrators</h1>
                <a href="?action=add" class="btn btn-primary">+ Add New Admin</a>
            </div>

            <div class="card">
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($admins)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><span class="badge badge-<?php echo $row['role']; ?>"><?php echo strtoupper($row['role']); ?></span></td>
                            <td><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td><?php echo $row['last_login'] ? date('M d, Y H:i', strtotime($row['last_login'])) : 'Never'; ?></td>
                            <td class="table-actions">
                                <a href="?action=edit&id=<?php echo $row['id']; ?>" style="color: #4299e1; margin-right: 10px;">Edit</a>
                                <?php if ($row['id'] != $_SESSION['admin_id']): ?>
                                <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                                   style="color: #f56565;"
                                   onclick="return confirm('Are you sure?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action == 'add' || $action == 'edit'): ?>
            <h1><?php echo $action == 'add' ? 'Add New Administrator' : 'Edit Administrator'; ?></h1>
            
            <div class="form-container" style="max-width: 600px;">
                <form method="POST">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $admin_data['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo $admin_data ? htmlspecialchars($admin_data['full_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" class="form-control" name="email" value="<?php echo $admin_data ? htmlspecialchars($admin_data['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" class="form-control" name="username" value="<?php echo $admin_data ? htmlspecialchars($admin_data['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Password <?php echo $action == 'add' ? '*' : '(leave blank to keep current)'; ?></label>
                        <input type="password" class="form-control" name="password" <?php echo $action == 'add' ? 'required' : ''; ?>>
                        <?php if ($action == 'add'): ?>
                            <small>Default if blank: admin123</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control" name="role">
                            <option value="admin" <?php echo ($admin_data && $admin_data['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="super_admin" <?php echo ($admin_data && $admin_data['role'] == 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" <?php echo (!$admin_data || $admin_data['is_active']) ? 'checked' : ''; ?>>
                            Active Account
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Save Administrator</button>
                        <a href="admins.php" class="btn" style="background: #718096; color: white;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>