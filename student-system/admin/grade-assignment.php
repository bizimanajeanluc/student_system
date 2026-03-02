<?php
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

requireAdminLogin();

$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$assignment_id) {
    header('Location: assignments.php');
    exit();
}

// Get assignment info
$query = "SELECT a.*, c.course_code, c.course_name 
          FROM assignments a 
          JOIN courses c ON a.course_id = c.id 
          WHERE a.id = $assignment_id";
$assignment_result = mysqli_query($conn, $query);
$assignment = mysqli_fetch_assoc($assignment_result);

if (!$assignment) {
    header('Location: assignments.php');
    exit();
}

$message = '';
$error = '';

// Handle Grading
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_grade'])) {
    $student_id = (int)$_POST['student_id'];
    $score = (float)$_POST['score'];
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    
    // Check if grade already exists
    $check = mysqli_query($conn, "SELECT id FROM grades WHERE assignment_id = $assignment_id AND student_id = $student_id");
    
    if (mysqli_num_rows($check) > 0) {
        $grade_id = mysqli_fetch_assoc($check)['id'];
        $query = "UPDATE grades SET score = $score, feedback = '$feedback', graded_at = NOW() WHERE id = $grade_id";
    } else {
        $query = "INSERT INTO grades (student_id, assignment_id, score, feedback, graded_at) 
                  VALUES ($student_id, $assignment_id, $score, '$feedback', NOW())";
    }
    
    if (mysqli_query($conn, $query)) {
        $message = 'Grade saved successfully!';
        logAdminAction($conn, $_SESSION['admin_id'], 'GRADE_SUBMISSION', "Graded assignment ID: $assignment_id for student ID: $student_id");
    } else {
        $error = 'Error saving grade: ' . mysqli_error($conn);
    }
}

// Get all enrolled students and their submissions/grades
$query = "SELECT s.id as student_id, s.full_name, s.student_id as student_reg_id,
          u.file_path, u.upload_date,
          g.score, g.feedback, g.graded_at
          FROM enrollments e
          JOIN students s ON e.student_id = s.id
          LEFT JOIN uploads u ON (u.student_id = s.id AND u.assignment_id = $assignment_id)
          LEFT JOIN grades g ON (g.student_id = s.id AND g.assignment_id = $assignment_id)
          WHERE e.course_id = " . $assignment['course_id'] . "
          ORDER BY s.full_name ASC";
$submissions = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Assignment - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .grade-form { display: flex; gap: 10px; align-items: flex-start; }
        .score-input { width: 80px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .feedback-input { flex: 1; padding: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .assignment-info { background: #f7fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 5px solid #4299e1; }
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
        <div style="margin-bottom: 20px;">
            <a href="assignments.php" style="text-decoration: none; color: #718096;">← Back to Assignments</a>
        </div>

        <div class="assignment-info">
            <h1>Grading: <?php echo htmlspecialchars($assignment['title']); ?></h1>
            <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></p>
            <p><strong>Max Score:</strong> <?php echo $assignment['max_score']; ?> | <strong>Due Date:</strong> <?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <table class="grades-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Submission</th>
                        <th>Status</th>
                        <th>Grade / Score</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($submissions)): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                            <small style="color: #718096;"><?php echo $row['student_reg_id']; ?></small>
                        </td>
                        <td>
                            <?php if ($row['file_path']): ?>
                                <a href="../<?php echo $row['file_path']; ?>" target="_blank" style="color: #4299e1;">View File</a><br>
                                <small style="color: #718096;">Uploaded: <?php echo date('M d, H:i', strtotime($row['upload_date'])); ?></small>
                                <?php if (strtotime($row['upload_date']) > strtotime($assignment['due_date'])): ?>
                                    <span style="color: #f56565; font-size: 11px; font-weight: bold;">(LATE)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #a0aec0;">No submission</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['score'] !== null): ?>
                                <span style="color: #48bb78; font-weight: bold;">Graded</span>
                            <?php else: ?>
                                <span style="color: #718096;">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td colspan="2">
                            <form method="POST" class="grade-form">
                                <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                <input type="number" name="score" step="0.01" max="<?php echo $assignment['max_score']; ?>" 
                                       placeholder="Score" class="score-input" required
                                       value="<?php echo $row['score']; ?>">
                                <span style="align-self: center;">/ <?php echo $assignment['max_score']; ?></span>
                                <input type="text" name="feedback" placeholder="Add feedback..." class="feedback-input"
                                       value="<?php echo htmlspecialchars($row['feedback'] ?: ''); ?>">
                                <button type="submit" name="save_grade" class="btn btn-primary" style="padding: 5px 15px;">Save</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>