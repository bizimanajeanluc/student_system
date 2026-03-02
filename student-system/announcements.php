<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$announcements = getAnnouncements(20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Student Portal</title>
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
        <h1>Announcements</h1>

        <?php if (empty($announcements)): ?>
            <div class="card">
                <p>No announcements available.</p>
            </div>
        <?php else: ?>
            <div class="announcement-list">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="card">
                        <div class="announcement-title" style="font-size: 1.3rem; font-weight: bold; margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($announcement['title']); ?>
                        </div>
                        <div class="announcement-meta" style="color: var(--gray-color); margin-bottom: 1rem;">
                            <span>Posted by: <?php echo htmlspecialchars($announcement['created_by']); ?></span> | 
                            <span><?php echo date('F j, Y g:i A', strtotime($announcement['created_at'])); ?></span>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>