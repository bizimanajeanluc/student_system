<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$student_id = $_SESSION['student_id'];
$message = '';
$error = '';

// Get student information
$query = "SELECT * FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $course = sanitize($_POST['course']);
        $year_level = (int)$_POST['year_level'];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            $update_query = "UPDATE students SET 
                            full_name = ?, 
                            email = ?, 
                            course = ?, 
                            year_level = ? 
                            WHERE id = ?";
            
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sssii", $full_name, $email, $course, $year_level, $student_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $message = 'Profile updated successfully!';
                $_SESSION['student_name'] = $full_name;
                // Refresh student data
                $student['full_name'] = $full_name;
                $student['email'] = $email;
                $student['course'] = $course;
                $student['year_level'] = $year_level;
            } else {
                $error = 'Failed to update profile.';
            }
            mysqli_stmt_close($update_stmt);
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (password_verify($current_password, $student['password'])) {
            if ($new_password == $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE students SET password = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $student_id);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $message = 'Password changed successfully!';
                    } else {
                        $error = 'Failed to change password.';
                    }
                    mysqli_stmt_close($update_stmt);
                } else {
                    $error = 'New password must be at least 6 characters long.';
                }
            } else {
                $error = 'New passwords do not match.';
            }
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

// Get statistics
$enrolled_courses = getStudentCourses($student_id);
$enrolled_count = count($enrolled_courses);
$uploads_count = count(getStudentUploads($student_id));
$grades_count = count(getStudentGrades($student_id));
$gpa = calculateGPA($student_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">StudentPortal</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="grades.php">Grades</a></li>
                <li><a href="uploads.php">Uploads</a></li>
                <li><a href="announcements.php">Announcements</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>My Profile</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Enrolled Courses</h3>
                <div class="stat-value"><?php echo $enrolled_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Uploads</h3>
                <div class="stat-value"><?php echo $uploads_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Graded Assignments</h3>
                <div class="stat-value"><?php echo $grades_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Current GPA</h3>
                <div class="stat-value"><?php echo number_format($gpa, 2); ?></div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                </div>
                <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                <p>Student ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
            </div>
            
            <div class="tabs" style="padding: 1rem 2rem 0;">
                <div class="tab active" data-tab="info">Profile Information</div>
                <div class="tab" data-tab="security">Security Settings</div>
            </div>

            <div id="info" class="tab-content active" style="padding: 2rem;">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_id">Student ID (cannot be changed)</label>
                        <input type="text" class="form-control" id="student_id" 
                               value="<?php echo htmlspecialchars($student['student_id']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="course">Course</label>
                        <input type="text" class="form-control" id="course" name="course" 
                               value="<?php echo htmlspecialchars($student['course']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select class="form-control" id="year_level" name="year_level" required>
                            <option value="1" <?php echo $student['year_level'] == 1 ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo $student['year_level'] == 2 ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo $student['year_level'] == 3 ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo $student['year_level'] == 4 ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <div id="security" class="tab-content" style="padding: 2rem;">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <small>Must be at least 6 characters long</small>
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

    <script src="assets/js/script.js"></script>
</body>
</html>