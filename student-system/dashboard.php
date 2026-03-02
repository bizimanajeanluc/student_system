<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Get student information
$query = "SELECT * FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

// Get enrolled courses
$enrolled_courses = getStudentCourses($student_id);
$enrolled_count = count($enrolled_courses);

// Get recent grades
$recent_grades = getRecentGrades($student_id, 5);

// Get upcoming assignments
$upcoming_assignments = getUpcomingAssignments($student_id, 5);

// Calculate GPA
$gpa = calculateGPA($student_id);

// Get announcements
$announcements = getAnnouncements(3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #4299e1;
        }
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .course-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .course-header {
            background: linear-gradient(135deg, #4299e1 0%, #667eea 100%);
            color: white;
            padding: 15px;
        }
        .course-body {
            padding: 15px;
        }
        .course-footer {
            padding: 15px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .grade-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .grade-item:last-child {
            border-bottom: none;
        }
        .grade-score {
            font-weight: bold;
            color: #48bb78;
        }
        .assignment-item {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .assignment-item:last-child {
            border-bottom: none;
        }
        .days-left {
            font-size: 12px;
            color: #f56565;
        }
        .btn-enroll {
            background: #48bb78;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-enroll:hover {
            background: #38a169;
        }
        .quick-links {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        .quick-link {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #2d3748;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        .quick-link:hover {
            background: #4299e1;
            color: white;
            border-color: #4299e1;
        }
        .quick-link i {
            font-size: 24px;
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">StudentPortal</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="courses.php">Browse Courses</a></li>
                <li><a href="my-courses.php">My Courses</a></li>
                <li><a href="grades.php">My Grades</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-card">
            <h1>Welcome back, <?php echo htmlspecialchars($student['full_name']); ?>!</h1>
            <p>Student ID: <?php echo htmlspecialchars($student['student_id']); ?> | Course: <?php echo htmlspecialchars($student['course']); ?> | Year: <?php echo $student['year_level']; ?></p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Enrolled Courses</h3>
                <div class="number"><?php echo $enrolled_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Current GPA</h3>
                <div class="number"><?php echo number_format($gpa, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Upcoming Tasks</h3>
                <div class="number"><?php echo count($upcoming_assignments); ?></div>
            </div>
            <div class="stat-card">
                <h3>Graded Items</h3>
                <div class="number"><?php echo count($recent_grades); ?></div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <a href="my-courses.php" class="quick-link">
                <i>📚</i>
                My Courses
            </a>
            <a href="courses.php" class="quick-link">
                <i>🔍</i>
                Browse Courses
            </a>
            <a href="grades.php" class="quick-link">
                <i>📊</i>
                View Grades
            </a>
            <a href="profile.php" class="quick-link">
                <i>👤</i>
                My Profile
            </a>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Left Column - Courses and Assignments -->
            <div>
                <!-- Enrolled Courses -->
                <div class="card">
                    <h2>My Enrolled Courses</h2>
                    <?php if (empty($enrolled_courses)): ?>
                        <p style="text-align: center; padding: 20px;">
                            You are not enrolled in any courses yet.
                        </p>
                        <p style="text-align: center;">
                            <a href="courses.php" class="btn btn-primary">Browse Available Courses</a>
                        </p>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach (array_slice($enrolled_courses, 0, 3) as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
                                        <p><?php echo htmlspecialchars($course['course_name']); ?></p>
                                    </div>
                                    <div class="course-body">
                                        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor']); ?></p>
                                        <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                    </div>
                                    <div class="course-footer">
                                        <span>Enrolled: <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></span>
                                        <a href="course-detail.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-small">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($enrolled_courses) > 3): ?>
                            <p style="text-align: center; margin-top: 15px;">
                                <a href="my-courses.php" class="btn btn-primary">View All <?php echo count($enrolled_courses); ?> Courses</a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Assignments -->
                <div class="card" style="margin-top: 20px;">
                    <h2>Upcoming Assignments</h2>
                    <?php if (empty($upcoming_assignments)): ?>
                        <p style="text-align: center; padding: 20px; color: #718096;">
                            No upcoming assignments. Enjoy your break! 🎉
                        </p>
                    <?php else: ?>
                        <?php foreach ($upcoming_assignments as $assignment): 
                            $days_left = ceil((strtotime($assignment['due_date']) - time()) / (60 * 60 * 24));
                            $urgency = $days_left <= 2 ? '#f56565' : ($days_left <= 5 ? '#ed8936' : '#48bb78');
                        ?>
                            <div class="assignment-item">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                        <p style="font-size: 13px; color: #718096;">
                                            <?php echo $assignment['course_code']; ?> - <?php echo $assignment['course_name']; ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: bold; color: <?php echo $urgency; ?>;">
                                            <?php echo $days_left; ?> days left
                                        </div>
                                        <small style="color: #718096;">
                                            Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <p style="text-align: center; margin-top: 15px;">
                            <a href="my-courses.php" class="btn btn-primary">View All Assignments</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Grades and Announcements -->
            <div>
                <!-- Recent Grades -->
                <div class="card">
                    <h2>Recent Grades</h2>
                    <?php if (empty($recent_grades)): ?>
                        <p style="text-align: center; padding: 20px; color: #718096;">
                            No grades available yet.
                        </p>
                    <?php else: ?>
                        <?php foreach ($recent_grades as $grade): 
                            $percentage = round(($grade['score'] / $grade['max_score']) * 100);
                            $grade_color = $percentage >= 80 ? '#48bb78' : ($percentage >= 60 ? '#ed8936' : '#f56565');
                        ?>
                            <div class="grade-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($grade['assignment_title']); ?></strong>
                                    <p style="font-size: 12px; color: #718096;"><?php echo $grade['course_code']; ?></p>
                                </div>
                                <div class="grade-score" style="color: <?php echo $grade_color; ?>;">
                                    <?php echo $grade['score']; ?>/<?php echo $grade['max_score']; ?> (<?php echo $percentage; ?>%)
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <p style="text-align: center; margin-top: 15px;">
                            <a href="grades.php" class="btn btn-primary">View All Grades</a>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Announcements -->
                <div class="card" style="margin-top: 20px;">
                    <h2>Latest Announcements</h2>
                    <?php if (empty($announcements)): ?>
                        <p style="text-align: center; padding: 20px; color: #718096;">
                            No announcements at this time.
                        </p>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div style="padding: 10px; border-bottom: 1px solid #e2e8f0;">
                                <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                <p style="font-size: 13px; color: #4a5568;">
                                    <?php echo substr(htmlspecialchars($announcement['content']), 0, 100); ?>...
                                </p>
                                <small style="color: #718096;">
                                    Posted: <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                        <p style="text-align: center; margin-top: 15px;">
                            <a href="announcements.php" class="btn btn-primary">View All Announcements</a>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="card" style="margin-top: 20px;">
                    <h2>Quick Actions</h2>
                    <div style="display: grid; gap: 10px;">
                        <a href="courses.php" class="btn btn-primary">🔍 Browse Available Courses</a>
                        <a href="my-courses.php" class="btn btn-primary">📚 View My Courses</a>
                        <a href="profile.php" class="btn btn-primary">👤 Update Profile</a>
                        <?php if ($enrolled_count == 0): ?>
                            <a href="courses.php" class="btn btn-success" style="background: #48bb78;">🎓 Enroll in Your First Course</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Student Portal. All rights reserved.</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>