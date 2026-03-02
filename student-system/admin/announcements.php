<?php
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

requireAdminLogin();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Handle Delete
if ($action == 'delete' && $id) {
    $query = "DELETE FROM announcements WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $message = 'Announcement deleted successfully!';
        logAdminAction($conn, $_SESSION['admin_id'], 'DELETE_ANNOUNCEMENT', "Deleted announcement ID: $id");
        $action = 'list';
    } else {
        $error = 'Error deleting announcement: ' . mysqli_error($conn);
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $created_by = $_SESSION['admin_name'];
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = (int)$_POST['id'];
        $query = "UPDATE announcements SET 
                  title = '$title',
                  content = '$content'
                  WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            $message = 'Announcement updated successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'UPDATE_ANNOUNCEMENT', "Updated: $title");
            $action = 'list';
        } else {
            $error = 'Error updating announcement: ' . mysqli_error($conn);
        }
    } else {
        // Add new
        $query = "INSERT INTO announcements (title, content, created_by) 
                  VALUES ('$title', '$content', '$created_by')";
        
        if (mysqli_query($conn, $query)) {
            $new_id = mysqli_insert_id($conn);
            $message = 'Announcement posted successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'ADD_ANNOUNCEMENT', "Posted: $title");
            $action = 'list';
        } else {
            $error = 'Error adding announcement: ' . mysqli_error($conn);
        }
    }
}

// Get announcement data for edit
$announcement = null;
if ($action == 'edit' && $id) {
    $result = mysqli_query($conn, "SELECT * FROM announcements WHERE id = $id");
    $announcement = mysqli_fetch_assoc($result);
}

// Get all announcements
$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <h1>Manage Announcements</h1>
                <a href="?action=add" class="btn btn-primary">+ New Announcement</a>
            </div>

            <div class="announcement-list">
                <?php while($row = mysqli_fetch_assoc($announcements)): ?>
                    <div class="card" style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between;">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="table-actions">
                                <a href="?action=edit&id=<?php echo $row['id']; ?>" style="color: #4299e1; margin-right: 15px;">Edit</a>
                                <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                                   style="color: #f56565;"
                                   onclick="return confirm('Delete this announcement?')">Delete</a>
                            </div>
                        </div>
                        <div style="color: #718096; font-size: 0.9rem; margin: 5px 0 15px;">
                            By: <?php echo htmlspecialchars($row['created_by']); ?> | 
                            <?php echo date('M d, Y g:i A', strtotime($row['created_at'])); ?>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php elseif ($action == 'add' || $action == 'edit'): ?>
            <h1><?php echo $action == 'add' ? 'New Announcement' : 'Edit Announcement'; ?></h1>
            
            <div class="form-container" style="max-width: 800px;">
                <form method="POST">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Announcement Title *</label>
                        <input type="text" class="form-control" name="title" value="<?php echo $announcement ? htmlspecialchars($announcement['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Content *</label>
                        <textarea class="form-control" name="content" rows="10" required><?php echo $announcement ? htmlspecialchars($announcement['content']) : ''; ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Publish Announcement</button>
                        <a href="announcements.php" class="btn" style="background: #718096; color: white;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>