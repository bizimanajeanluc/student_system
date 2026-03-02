<?php
require_once 'includes/config.php'; // Session already started here
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $student_id = trim($_POST['student_id']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $course = trim($_POST['course']);
    $year_level = (int)$_POST['year_level'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($student_id) || empty($password) || empty($course) || empty($year_level)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if email already exists
        $check_email = mysqli_query($conn, "SELECT id FROM students WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $error = 'Email already exists';
        } else {
            // Check if student ID already exists
            $check_id = mysqli_query($conn, "SELECT id FROM students WHERE student_id = '$student_id'");
            if (mysqli_num_rows($check_id) > 0) {
                $error = 'Student ID already exists';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert student
                $query = "INSERT INTO students (full_name, email, student_id, password, course, year_level) 
                          VALUES ('$full_name', '$email', '$student_id', '$hashed_password', '$course', $year_level)";
                
                if (mysqli_query($conn, $query)) {
                    $success = 'Registration successful! You can now login.';
                    // Clear form
                    $_POST = array();
                } else {
                    $error = 'Registration failed: ' . mysqli_error($conn);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">StudentPortal</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Student Registration</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" class="form-control" id="student_id" name="student_id" 
                           value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="course">Course</label>
                    <input type="text" class="form-control" id="course" name="course" 
                           value="<?php echo isset($_POST['course']) ? htmlspecialchars($_POST['course']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select class="form-control" id="year_level" name="year_level" required>
                        <option value="">Select Year</option>
                        <option value="1" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == 1) ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == 2) ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == 3) ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == 4) ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Password (min 6 characters)</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <p style="text-align: center; margin-top: 1rem;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>