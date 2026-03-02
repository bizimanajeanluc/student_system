<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$student_id = $_SESSION['student_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify enrollment
if (!isEnrolled($student_id, $course_id)) {
    header('Location: courses.php');
    exit();
}

// Get course details
$course = getCourse($course_id);
if (!$course) {
    header('Location: courses.php');
    exit();
}

// Get assignments
$assignments = getCourseAssignments($course_id);

// Get grades for this course
$grades = [];
$grades_query = "SELECT g.*, a.title, a.max_score 
                 FROM grades g
                 JOIN assignments a ON g.assignment_id = a.id
                 WHERE g.student_id = ? AND a.course_id = ?";
$stmt = mysqli_prepare($conn, $grades_query);
mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $grades[$row['assignment_id']] = $row;
}

// Calculate progress
$total_assignments = count($assignments);
$graded_assignments = count($grades);
$progress = $total_assignments > 0 ? round(($graded_assignments / $total_assignments) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['course_name']); ?> - Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .course-header {
            background: linear-gradient(135deg, #4299e1 0%, #667eea 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .info-card h3 {
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .info-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #4299e1;
        }
        .assignment-table {
            width: 100%;
            border-collapse: collapse;
        }
        .assignment-table th {
            background: #f7fafc;
            padding: 12px;
            text-align: left;
        }
        .assignment-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .grade-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .grade-A { background: #c6f6d5; color: #22543d; }
        .grade-B { background: #bee3f8; color: #2c5282; }
        .grade-C { background: #feebc8; color: #744210; }
        .grade-D { background: #fed7d7; color: #9b2c2c; }
        .grade-F { background: #e2e8f0; color: #4a5568; }
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
                <li><a href="my-courses.php">My Courses</a></li>
                <li><a href="grades.php">My Grades</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="course-header">
            <h1><?php echo htmlspecialchars($course['course_name']); ?></h1>
            <p><?php echo htmlspecialchars($course['course_code']); ?> | <?php echo $course['credits']; ?> Credits</p>
            <p>Instructor: <?php echo htmlspecialchars($course['instructor']); ?></p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h3>Department</h3>
                <div class="value"><?php echo htmlspecialchars($course['department']); ?></div>
            </div>
            <div class="info-card">
                <h3>Semester</h3>
                <div class="value"><?php echo $course['semester']; ?> <?php echo $course['academic_year']; ?></div>
            </div>
            <div class="info-card">
                <h3>Assignments</h3>
                <div class="value"><?php echo $total_assignments; ?></div>
            </div>
            <div class="info-card">
                <h3>Progress</h3>
                <div class="value"><?php echo $progress; ?>%</div>
            </div>
        </div>

        <div class="card">
            <h2>Course Description</h2>
            <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
        </div>

        <div class="card">
            <h2>Assignments & Grades</h2>
            
            <?php if (empty($assignments)): ?>
                <p style="text-align: center; padding: 40px; color: #718096;">
                    No assignments available for this course yet.
                </p>
            <?php else: ?>
                <table class="assignment-table">
                    <thead>
                        <tr>
                            <th>Assignment</th>
                            <th>Type</th>
                            <th>Due Date</th>
                            <th>Max Score</th>
                            <th>Your Score</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): 
                            $grade = isset($grades[$assignment['id']]) ? $grades[$assignment['id']] : null;
                            $score = $grade ? $grade['score'] : '-';
                            $percentage = $grade ? round(($grade['score'] / $assignment['max_score']) * 100) : 0;
                            
                            if ($percentage >= 90) $letter = 'A';
                            elseif ($percentage >= 80) $letter = 'B';
                            elseif ($percentage >= 70) $letter = 'C';
                            elseif ($percentage >= 60) $letter = 'D';
                            elseif ($percentage > 0) $letter = 'F';
                            else $letter = '-';
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($assignment['title']); ?></strong></td>
                                <td><?php echo ucfirst($assignment['assignment_type']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></td>
                                <td><?php echo $assignment['max_score']; ?></td>
                                <td><?php echo $score; ?></td>
                                <td>
                                    <?php if ($letter != '-'): ?>
                                        <span class="grade-badge grade-<?php echo $letter; ?>">
                                            <?php echo $letter; ?> (<?php echo $percentage; ?>%)
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #718096;">Not graded</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="my-courses.php" class="btn btn-primary">← Back to My Courses</a>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Student Portal. All rights reserved.</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>