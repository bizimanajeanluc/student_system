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
    // Check if assignment has grades
    $check = mysqli_query($conn, "SELECT id FROM grades WHERE assignment_id = $id");
    if (mysqli_num_rows($check) > 0) {
        $error = 'Cannot delete assignment with existing grades.';
    } else {
        $query = "DELETE FROM assignments WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = 'Assignment deleted successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'DELETE_ASSIGNMENT', "Deleted assignment ID: $id");
            $action = 'list';
        } else {
            $error = 'Error deleting assignment: ' . mysqli_error($conn);
        }
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = (int)$_POST['course_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $max_score = (int)$_POST['max_score'];
    $assignment_type = mysqli_real_escape_string($conn, $_POST['assignment_type']);
    $due_date = $_POST['due_date'];
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = (int)$_POST['id'];
        $query = "UPDATE assignments SET 
                  course_id = $course_id,
                  title = '$title',
                  description = '$description',
                  max_score = $max_score,
                  assignment_type = '$assignment_type',
                  due_date = '$due_date'
                  WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            $message = 'Assignment updated successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'UPDATE_ASSIGNMENT', "Updated assignment: $title");
            $action = 'list';
        } else {
            $error = 'Error updating assignment: ' . mysqli_error($conn);
        }
    } else {
        // Add new
        $query = "INSERT INTO assignments (course_id, title, description, max_score, assignment_type, due_date) 
                  VALUES ($course_id, '$title', '$description', $max_score, '$assignment_type', '$due_date')";
        
        if (mysqli_query($conn, $query)) {
            $new_id = mysqli_insert_id($conn);
            $message = 'Assignment added successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'ADD_ASSIGNMENT', "Added assignment: $title");
            $action = 'list';
        } else {
            $error = 'Error adding assignment: ' . mysqli_error($conn);
        }
    }
}

// Get assignment data for edit
$assignment = null;
if ($action == 'edit' && $id) {
    $result = mysqli_query($conn, "SELECT * FROM assignments WHERE id = $id");
    $assignment = mysqli_fetch_assoc($result);
    if (!$assignment) {
        $action = 'list';
        $error = 'Assignment not found.';
    }
}

// Get all assignments with course info
$query = "SELECT a.*, c.course_code, c.course_name 
          FROM assignments a 
          JOIN courses c ON a.course_id = c.id 
          ORDER BY a.due_date DESC";
$assignments = mysqli_query($conn, $query);

// Get courses for dropdown
$courses_list = mysqli_query($conn, "SELECT id, course_code, course_name FROM courses ORDER BY course_code ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .table-actions a { margin: 0 5px; text-decoration: none; }
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
            <div class="action-bar">
                <h1>Manage Assignments</h1>
                <a href="?action=add" class="btn btn-primary">+ Add New Assignment</a>
            </div>

            <div class="card">
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Title</th>
                            <th>Course</th>
                            <th>Type</th>
                            <th>Max Score</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($assignments)): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <td><?php echo ucfirst($row['assignment_type']); ?></td>
                            <td><?php echo $row['max_score']; ?></td>
                            <td class="table-actions">
                                <a href="grade-assignment.php?id=<?php echo $row['id']; ?>" style="color: #48bb78; font-weight: bold;">Grade</a>
                                <a href="?action=edit&id=<?php echo $row['id']; ?>" style="color: #4299e1;">Edit</a>
                                <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                                   style="color: #f56565;"
                                   onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action == 'add' || $action == 'edit'): ?>
            <h1><?php echo $action == 'add' ? 'Add New Assignment' : 'Edit Assignment'; ?></h1>
            
            <div class="form-container" style="max-width: 800px;">
                <form method="POST">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $assignment['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Course *</label>
                        <select class="form-control" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php 
                            mysqli_data_seek($courses_list, 0);
                            while($c = mysqli_fetch_assoc($courses_list)): 
                            ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($assignment && $assignment['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" class="form-control" name="title" value="<?php echo $assignment ? htmlspecialchars($assignment['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="4"><?php echo $assignment ? htmlspecialchars($assignment['description']) : ''; ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Max Score *</label>
                            <input type="number" class="form-control" name="max_score" value="<?php echo $assignment ? $assignment['max_score'] : '100'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select class="form-control" name="assignment_type">
                                <option value="assignment" <?php echo ($assignment && $assignment['assignment_type'] == 'assignment') ? 'selected' : ''; ?>>Assignment</option>
                                <option value="quiz" <?php echo ($assignment && $assignment['assignment_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                                <option value="exam" <?php echo ($assignment && $assignment['assignment_type'] == 'exam') ? 'selected' : ''; ?>>Exam</option>
                                <option value="project" <?php echo ($assignment && $assignment['assignment_type'] == 'project') ? 'selected' : ''; ?>>Project</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Due Date *</label>
                            <input type="datetime-local" class="form-control" name="due_date" value="<?php echo $assignment ? date('Y-m-d\TH:i', strtotime($assignment['due_date'])) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Save Assignment</button>
                        <a href="assignments.php" class="btn" style="background: #718096; color: white;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>