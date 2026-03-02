<?php
require_once 'includes/config.php'; // Session already started here
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">StudentPortal</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="courses.php">Courses</a></li>
                    <li><a href="grades.php">Grades</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="hero-section">
            <h1 class="hero-title">Welcome to Student Portal</h1>
            <p class="hero-subtitle">Your one-stop solution for managing student information and academic journey</p>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-primary">Get Started</a>
                <a href="login.php" class="btn btn-primary" style="background: transparent; border: 2px solid white; margin-left: 1rem;">Login</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <?php endif; ?>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3>Course Management</h3>
                <p>Easily manage your courses, enroll in new classes, and track your academic progress</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Grade Tracking</h3>
                <p>Monitor your grades, calculate GPA, and analyze your performance with visual charts</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📁</div>
                <h3>File Uploads</h3>
                <p>Submit assignments, upload documents, and manage all your academic files in one place</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📢</div>
                <h3>Announcements</h3>
                <p>Stay updated with the latest news, events, and important announcements</p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Student Portal. All rights reserved.</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>