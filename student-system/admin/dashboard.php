<?php
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

requireAdminLogin();

$admin_id = $_SESSION['admin_id'];
$admin = getAdminById($conn, $admin_id);

// Get statistics
$stats = [];

// Total students
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM students");
$stats['total_students'] = mysqli_fetch_assoc($result)['total'];

// Total courses
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM courses");
$stats['total_courses'] = mysqli_fetch_assoc($result)['total'];

// Total assignments
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM assignments");
$stats['total_assignments'] = mysqli_fetch_assoc($result)['total'];

// Total enrollments
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM enrollments");
$stats['total_enrollments'] = mysqli_fetch_assoc($result)['total'];

// Recent students
$recent_students = mysqli_query($conn, "SELECT * FROM students ORDER BY created_at DESC LIMIT 5");

// Recent logs
$logs_query = "SELECT l.*, a.username, a.full_name 
               FROM admin_logs l 
               JOIN admins a ON l.admin_id = a.id 
               ORDER BY l.created_at DESC LIMIT 10";
$recent_logs = mysqli_query($conn, $logs_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-nav {
            background: #2c3e50;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-nav .logo {
            color: white;
        }
        .admin-nav .nav-menu a {
            color: #ecf0f1;
        }
        .admin-nav .nav-menu a:hover {
            color: #3498db;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #4299e1;
        }
        .stat-card h3 {
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #2d3748;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        .action-btn {
            display: block;
            padding: 20px;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: #2d3748;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-color: #4299e1;
        }
        .action-btn i {
            font-size: 32px;
            display: block;
            margin-bottom: 10px;
            color: #4299e1;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #2d3748;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .recent-table {
            width: 100%;
            border-collapse: collapse;
        }
        .recent-table th {
            text-align: left;
            padding: 10px;
            background: #f7fafc;
            font-size: 13px;
            color: #718096;
        }
        .recent-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .recent-table tr:hover {
            background: #f7fafc;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-super_admin {
            background: #9f7aea;
            color: white;
        }
        .badge-admin {
            background: #4299e1;
            color: white;
        }
        .badge-moderator {
            background: #48bb78;
            color: white;
        }
        .log-item {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .log-time {
            color: #718096;
            font-size: 11px;
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

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Welcome, <?php echo htmlspecialchars($admin['full_name']); ?>!</h1>
            <div>
                <span class="badge badge-<?php echo $admin['role']; ?>"><?php echo ucfirst($admin['role']); ?></span>
                <span style="margin-left: 10px; color: #718096;">Last login: <?php echo $admin['last_login'] ? date('M d, Y H:i', strtotime($admin['last_login'])) : 'First login'; ?></span>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <div class="number"><?php echo $stats['total_students']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Courses</h3>
                <div class="number"><?php echo $stats['total_courses']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Assignments</h3>
                <div class="number"><?php echo $stats['total_assignments']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Enrollments</h3>
                <div class="number"><?php echo $stats['total_enrollments']; ?></div>
            </div>
        </div>

        <h2>Quick Actions</h2>
        <div class="quick-actions">
            <a href="students.php?action=add" class="action-btn">
                <i>👤</i>
                Add Student
            </a>
            <a href="courses.php?action=add" class="action-btn">
                <i>📚</i>
                Add Course
            </a>
            <a href="assignments.php?action=add" class="action-btn">
                <i>📝</i>
                Add Assignment
            </a>
            <a href="announcements.php?action=add" class="action-btn">
                <i>📢</i>
                Post Announcement
            </a>
            <a href="admins.php?action=add" class="action-btn">
                <i>👥</i>
                Add Admin
            </a>
        </div>

        <div class="dashboard-grid">
            <!-- Recent Students -->
            <div class="card">
                <h2>Recent Students</h2>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($student = mysqli_fetch_assoc($recent_students)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['course']); ?></td>
                            <td><?php echo $student['year_level']; ?></td>
                            <td>
                                <a href="students.php?action=edit&id=<?php echo $student['id']; ?>" style="color: #4299e1;">Edit</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div style="margin-top: 15px;">
                    <a href="students.php" class="btn btn-primary">View All Students</a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <h2>Recent Activity</h2>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php while($log = mysqli_fetch_assoc($recent_logs)): ?>
                    <div class="log-item">
                        <div><strong><?php echo htmlspecialchars($log['full_name']); ?></strong> (<?php echo $log['username']; ?>)</div>
                        <div style="font-size: 12px;"><?php echo htmlspecialchars($log['action']); ?> - <?php echo htmlspecialchars($log['description']); ?></div>
                        <div class="log-time"><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Student Portal Admin Panel. All rights reserved.</p>
    </footer>
</body>
</html>