<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$student_id = $_SESSION['student_id'];
$enrolled_courses = getStudentCourses($student_id);

// Get assignments for each course
$course_assignments = [];
foreach ($enrolled_courses as $course) {
    $assignments = getCourseAssignments($course['id']);
    $course_assignments[$course['id']] = $assignments;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .course-detail-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .assignment-list {
            list-style: none;
        }
        .assignment-item {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .assignment-item:last-child {
            border-bottom: none;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-pending { background: #fed7d7; color: #9b2c2c; }
        .badge-submitted { background: #c6f6d5; color: #22543d; }
        .badge-graded { background: #4299e1; color: white; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">StudentPortal</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="courses.php">Browse Courses</a></li>
                <li><a href="my-courses.php" class="active">My Courses</a></li>
                <li><a href="grades.php">My Grades</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>My Enrolled Courses</h1>
        
        <?php if (empty($enrolled_courses)): ?>
            <div class="card" style="text-align: center; padding: 50px;">
                <h2 style="margin-bottom: 20px;">You are not enrolled in any courses yet</h2>
                <p style="margin-bottom: 30px;">Browse our available courses and enroll to start learning!</p>
                <a href="courses.php" class="btn btn-primary">Browse Courses</a>
            </div>
        <?php else: ?>
            <?php foreach ($enrolled_courses as $course): ?>
                <div class="card" style="margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h2><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h2>
                            <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor']); ?></p>
                            <p><strong>Department:</strong> <?php echo htmlspecialchars($course['department']); ?> | <strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                        </div>
                        <a href="course-detail.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">View Course Details</a>
                    </div>
                    
                    <div class="course-detail-grid">
                        <!-- Assignments -->
                        <div>
                            <h3>Assignments</h3>
                            <?php if (empty($course_assignments[$course['id']])): ?>
                                <p style="color: #718096;">No assignments yet.</p>
                            <?php else: ?>
                                <div class="assignment-list">
                                    <?php foreach ($course_assignments[$course['id']] as $assignment): 
                                        $due_date = strtotime($assignment['due_date']);
                                        $now = time();
                                        $status = $due_date < $now ? 'overdue' : 'pending';
                                    ?>
                                        <div class="assignment-item">
                                            <div>
                                                <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                                <p style="font-size: 12px; color: #718096;"><?php echo substr(htmlspecialchars($assignment['description']), 0, 100); ?></p>
                                            </div>
                                            <div style="text-align: right;">
                                                <div>Due: <?php echo date('M d, Y', $due_date); ?></div>
                                                <span class="badge badge-<?php echo $status; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Course Progress -->
                        <div>
                            <h3>Progress</h3>
                            <?php
                            // Calculate progress
                            $total_assignments = count($course_assignments[$course['id']]);
                            $graded_query = "SELECT COUNT(DISTINCT g.assignment_id) as graded 
                                             FROM grades g
                                             JOIN assignments a ON g.assignment_id = a.id
                                             WHERE a.course_id = {$course['id']} AND g.student_id = $student_id";
                            $graded_result = mysqli_query($conn, $graded_query);
                            $graded = mysqli_fetch_assoc($graded_result)['graded'];
                            $progress = $total_assignments > 0 ? round(($graded / $total_assignments) * 100) : 0;
                            ?>
                            <div style="text-align: center; padding: 20px;">
                                <div style="font-size: 48px; font-weight: bold; color: #4299e1;"><?php echo $progress; ?>%</div>
                                <p>Complete</p>
                                <div class="progress-bar" style="margin-top: 10px;">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                                </div>
                                <p style="margin-top: 15px;">
                                    <strong><?php echo $graded; ?></strong> of <strong><?php echo $total_assignments; ?></strong> assignments graded
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Student Portal. All rights reserved.</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>