<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$student_id = $_SESSION['student_id'];
$grades = getStudentGrades($student_id);
$gpa = calculateGPA($student_id);

// Group grades by course
$course_grades = [];
foreach ($grades as $grade) {
    if (!isset($course_grades[$grade['course_code']])) {
        $course_grades[$grade['course_code']] = [
            'name' => $grade['course_name'],
            'grades' => [],
            'total' => 0,
            'count' => 0
        ];
    }
    $course_grades[$grade['course_code']]['grades'][] = $grade;
    $course_grades[$grade['course_code']]['total'] += ($grade['score'] / $grade['max_score']) * 100;
    $course_grades[$grade['course_code']]['count']++;
}

// Calculate overall statistics
$total_score = 0;
$total_max = 0;
foreach ($grades as $grade) {
    $total_score += $grade['score'];
    $total_max += $grade['max_score'];
}
$overall_percentage = $total_max > 0 ? round(($total_score / $total_max) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .gpa-card {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        .gpa-card .gpa-value {
            font-size: 72px;
            font-weight: bold;
            line-height: 1;
            margin: 10px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .course-grade-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .course-average {
            font-size: 24px;
            font-weight: bold;
            color: #4299e1;
        }
        .grade-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .grade-row:last-child {
            border-bottom: none;
        }
        .grade-score {
            font-weight: bold;
        }
        .grade-A { color: #48bb78; }
        .grade-B { color: #4299e1; }
        .grade-C { color: #ed8936; }
        .grade-D { color: #f56565; }
        .grade-F { color: #718096; }
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
                <li><a href="grades.php" class="active">My Grades</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>My Grades</h1>

        <!-- GPA Card -->
        <div class="gpa-card">
            <h2>Current GPA</h2>
            <div class="gpa-value"><?php echo number_format($gpa, 2); ?></div>
            <p>Overall Average: <?php echo $overall_percentage; ?>% (<?php echo $total_score; ?>/<?php echo $total_max; ?> points)</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Assignments</h3>
                <div class="number"><?php echo count($grades); ?></div>
            </div>
            <div class="stat-card">
                <h3>Courses</h3>
                <div class="number"><?php echo count($course_grades); ?></div>
            </div>
            <div class="stat-card">
                <h3>A's Earned</h3>
                <div class="number" style="color: #48bb78;">
                    <?php 
                    $a_count = 0;
                    foreach ($grades as $grade) {
                        if (($grade['score'] / $grade['max_score']) >= 0.9) $a_count++;
                    }
                    echo $a_count;
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Highest Score</h3>
                <div class="number" style="color: #48bb78;">
                    <?php
                    $highest = 0;
                    foreach ($grades as $grade) {
                        $pct = ($grade['score'] / $grade['max_score']) * 100;
                        if ($pct > $highest) $highest = $pct;
                    }
                    echo round($highest) . '%';
                    ?>
                </div>
            </div>
        </div>

        <!-- Grades by Course -->
        <?php if (empty($grades)): ?>
            <div class="card" style="text-align: center; padding: 60px;">
                <h2 style="margin-bottom: 20px;">No Grades Available Yet</h2>
                <p style="color: #718096; margin-bottom: 30px;">Once your instructors grade your assignments, they will appear here.</p>
                <a href="my-courses.php" class="btn btn-primary">View My Courses</a>
            </div>
        <?php else: ?>
            <?php foreach ($course_grades as $code => $course): 
                $course_avg = round($course['total'] / $course['count'], 1);
            ?>
                <div class="course-grade-card">
                    <div class="course-header">
                        <div>
                            <h2><?php echo $code; ?> - <?php echo htmlspecialchars($course['name']); ?></h2>
                            <p style="color: #718096;"><?php echo $course['count']; ?> assignments</p>
                        </div>
                        <div class="course-average"><?php echo $course_avg; ?>%</div>
                    </div>
                    
                    <?php foreach ($course['grades'] as $grade): 
                        $percentage = round(($grade['score'] / $grade['max_score']) * 100);
                        $grade_class = $percentage >= 90 ? 'A' : ($percentage >= 80 ? 'B' : ($percentage >= 70 ? 'C' : ($percentage >= 60 ? 'D' : 'F')));
                    ?>
                        <div class="grade-row">
                            <div>
                                <strong><?php echo htmlspecialchars($grade['assignment_title']); ?></strong>
                                <p style="font-size: 12px; color: #718096;"><?php echo ucfirst($grade['assignment_type']); ?></p>
                            </div>
                            <div class="grade-score grade-<?php echo $grade_class; ?>">
                                <?php echo $grade['score']; ?>/<?php echo $grade['max_score']; ?> (<?php echo $percentage; ?>%)
                            </div>
                        </div>
                    <?php endforeach; ?>
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