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
    // Check if course has enrollments
    $check = mysqli_query($conn, "SELECT id FROM enrollments WHERE course_id = $id");
    if (mysqli_num_rows($check) > 0) {
        $error = 'Cannot delete course with active enrollments.';
    } else {
        $query = "DELETE FROM courses WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = 'Course deleted successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'DELETE_COURSE', "Deleted course ID: $id");
            $action = 'list';
        } else {
            $error = 'Error deleting course: ' . mysqli_error($conn);
        }
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_code = mysqli_real_escape_string($conn, $_POST['course_code']);
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $instructor = mysqli_real_escape_string($conn, $_POST['instructor']);
    $credits = (int)$_POST['credits'];
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = (int)$_POST['id'];
        $query = "UPDATE courses SET 
                  course_code = '$course_code',
                  course_name = '$course_name',
                  description = '$description',
                  instructor = '$instructor',
                  credits = $credits,
                  department = '$department',
                  semester = '$semester',
                  academic_year = '$academic_year'
                  WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            $message = 'Course updated successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'UPDATE_COURSE', "Updated course: $course_code");
            $action = 'list';
        } else {
            $error = 'Error updating course: ' . mysqli_error($conn);
        }
    } else {
        // Add new
        $query = "INSERT INTO courses (course_code, course_name, description, instructor, credits, department, semester, academic_year) 
                  VALUES ('$course_code', '$course_name', '$description', '$instructor', $credits, '$department', '$semester', '$academic_year')";
        
        if (mysqli_query($conn, $query)) {
            $new_id = mysqli_insert_id($conn);
            $message = 'Course added successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'ADD_COURSE', "Added course: $course_code");
            $action = 'list';
        } else {
            $error = 'Error adding course: ' . mysqli_error($conn);
        }
    }
}

// Get course data for edit
$course = null;
if ($action == 'edit' && $id) {
    $result = mysqli_query($conn, "SELECT * FROM courses WHERE id = $id");
    $course = mysqli_fetch_assoc($result);
    if (!$course) {
        $action = 'list';
        $error = 'Course not found.';
    }
}

// Get all courses
$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_code ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin</title>
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
                <h1>Manage Courses</h1>
                <a href="?action=add" class="btn btn-primary">+ Add New Course</a>
            </div>

            <div class="card">
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Instructor</th>
                            <th>Credits</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($courses)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['course_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['instructor']); ?></td>
                            <td><?php echo $row['credits']; ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['semester']); ?></td>
                            <td class="table-actions">
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
            <h1><?php echo $action == 'add' ? 'Add New Course' : 'Edit Course'; ?></h1>
            
            <div class="form-container" style="max-width: 800px;">
                <form method="POST">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $course['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                        <div class="form-group">
                            <label>Course Code *</label>
                            <input type="text" class="form-control" name="course_code" value="<?php echo $course ? htmlspecialchars($course['course_code']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Course Name *</label>
                            <input type="text" class="form-control" name="course_name" value="<?php echo $course ? htmlspecialchars($course['course_name']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="4"><?php echo $course ? htmlspecialchars($course['description']) : ''; ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Instructor *</label>
                            <input type="text" class="form-control" name="instructor" value="<?php echo $course ? htmlspecialchars($course['instructor']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Credits *</label>
                            <input type="number" class="form-control" name="credits" value="<?php echo $course ? $course['credits'] : '3'; ?>" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" class="form-control" name="department" value="<?php echo $course ? htmlspecialchars($course['department']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Semester</label>
                            <select class="form-control" name="semester">
                                <option value="Fall" <?php echo ($course && $course['semester'] == 'Fall') ? 'selected' : ''; ?>>Fall</option>
                                <option value="Spring" <?php echo ($course && $course['semester'] == 'Spring') ? 'selected' : ''; ?>>Spring</option>
                                <option value="Summer" <?php echo ($course && $course['semester'] == 'Summer') ? 'selected' : ''; ?>>Summer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Academic Year</label>
                            <input type="text" class="form-control" name="academic_year" value="<?php echo $course ? htmlspecialchars($course['academic_year']) : '2023-2024'; ?>">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Save Course</button>
                        <a href="courses.php" class="btn" style="background: #718096; color: white;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>