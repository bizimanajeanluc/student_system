<?php
session_start();

// Simple check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../includes/config.php';

// Get admin info
$admin_id = $_SESSION['admin_id'];
$query = "SELECT * FROM admins WHERE id = $admin_id";
$result = mysqli_query($conn, $query);
$admin = mysqli_fetch_assoc($result);

if (!$admin) {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f0f2f5; }
        .navbar { background: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; }
        .navbar h1 { margin: 0; font-size: 20px; }
        .navbar a { color: white; text-decoration: none; margin-left: 20px; }
        .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .welcome { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #4CAF50; }
        .menu { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px; }
        .menu-item { background: white; padding: 20px; border-radius: 8px; text-align: center; text-decoration: none; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .menu-item:hover { background: #4CAF50; color: white; }
        .session-info { background: #e7f3ff; padding: 15px; border-radius: 8px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Admin Dashboard</h1>
        <div>
            <span>Welcome, <?php echo htmlspecialchars($admin['full_name']); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h2>Welcome back, <?php echo htmlspecialchars($admin['full_name']); ?>!</h2>
            <p>Role: <?php echo ucfirst($admin['role']); ?> | Last Login: <?php echo $admin['last_login'] ?? 'First time'; ?></p>
        </div>
        
        <div class="stats">
            <?php
            $tables = ['students', 'courses', 'assignments', 'enrollments'];
            foreach ($tables as $table) {
                $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM $table"))['c'];
                echo "<div class='stat-card'>";
                echo "<h3>Total " . ucfirst($table) . "</h3>";
                echo "<div class='number'>$count</div>";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="menu">
            <a href="students.php" class="menu-item">👥 Students</a>
            <a href="courses.php" class="menu-item">📚 Courses</a>
            <a href="assignments.php" class="menu-item">📝 Assignments</a>
            <a href="announcements.php" class="menu-item">📢 Announcements</a>
            <a href="profile.php" class="menu-item">👤 Profile</a>
        </div>
        
        <div class="session-info">
            <h3>Debug Info:</h3>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
    </div>
</body>
</html>