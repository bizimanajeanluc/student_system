<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

requireAdminLogin();

$message = '';
$error = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = (int)$_POST['submission_id'];
    $student_id = (int)$_POST['student_id'];
    $assignment_id = (int)$_POST['assignment_id'];
    $score = (float)$_POST['score'];
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    
    // Check if grade already exists
    $check = mysqli_query($conn, "SELECT id FROM grades WHERE student_id = $student_id AND assignment_id = $assignment_id");
    
    if (mysqli_num_rows($check) > 0) {
        // Update existing grade
        $query = "UPDATE grades SET score = $score, feedback = '$feedback', graded_at = NOW() 
                  WHERE student_id = $student_id AND assignment_id = $assignment_id";
    } else {
        // Insert new grade
        $query = "INSERT INTO grades (student_id, assignment_id, score, feedback, graded_at) 
                  VALUES ($student_id, $assignment_id, $score, '$feedback', NOW())";
    }
    
    if (mysqli_query($conn, $query)) {
        // Mark submission as graded
        mysqli_query($conn, "UPDATE submissions SET status = 'graded' WHERE id = $submission_id");
        $message = 'Grade saved successfully!';
        logAdminAction($conn, $_SESSION['admin_id'], 'GRADE_SUBMISSION', "Graded submission ID: $submission_id");
    } else {
        $error = 'Failed to save grade: ' . mysqli_error($conn);
    }
}

// Handle delete submission
if (isset($_GET['delete'])) {
    $submission_id = (int)$_GET['delete'];
    
    // Get file path first
    $result = mysqli_query($conn, "SELECT file_path FROM submissions WHERE id = $submission_id");
    if ($row = mysqli_fetch_assoc($result)) {
        $file_path = $row['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $query = "DELETE FROM submissions WHERE id = $submission_id";
    if (mysqli_query($conn, $query)) {
        $message = 'Submission deleted successfully!';
        logAdminAction($conn, $_SESSION['admin_id'], 'DELETE_SUBMISSION', "Deleted submission ID: $submission_id");
    } else {
        $error = 'Failed to delete submission: ' . mysqli_error($conn);
    }
}

// Get filter parameters
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$assignment_filter = isset($_GET['assignment']) ? (int)$_GET['assignment'] : 0;

// Build query for submissions
$query = "SELECT s.*, 
          stu.full_name as student_name, stu.student_id as student_code,
          a.title as assignment_title, a.max_score, a.due_date,
          c.course_code, c.course_name, c.id as course_id,
          g.score as existing_score, g.feedback as existing_feedback
          FROM submissions s
          JOIN students stu ON s.student_id = stu.id
          JOIN assignments a ON s.assignment_id = a.id
          JOIN courses c ON a.course_id = c.id
          LEFT JOIN grades g ON s.assignment_id = g.assignment_id AND g.student_id = s.student_id
          WHERE 1=1";

if ($course_filter > 0) {
    $query .= " AND c.id = $course_filter";
}
if ($status_filter && $status_filter != 'all') {
    $query .= " AND s.status = '$status_filter'";
}
if ($assignment_filter > 0) {
    $query .= " AND a.id = $assignment_filter";
}

$query .= " ORDER BY s.submitted_at DESC";
$submissions = mysqli_query($conn, $query);

// Get courses for filter dropdown
$courses = mysqli_query($conn, "SELECT id, course_code, course_name FROM courses ORDER BY course_code");

// Get assignments for filter dropdown
$assignments = mysqli_query($conn, "SELECT a.id, a.title, c.course_code FROM assignments a JOIN courses c ON a.course_id = c.id ORDER BY a.due_date DESC");

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'graded' THEN 1 ELSE 0 END) as graded,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM submissions";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Submissions - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #4f46e5;
        }
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar select, .filter-bar input {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            min-width: 150px;
        }
        .submissions-grid {
            display: grid;
            gap: 20px;
        }
        .submission-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #4f46e5;
        }
        .submission-card.pending {
            border-left-color: #ed8936;
        }
        .submission-card.graded {
            border-left-color: #48bb78;
        }
        .submission-card.late {
            border-left-color: #f56565;
        }
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .student-info {
            font-size: 14px;
            color: #718096;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending {
            background: #feebc8;
            color: #744210;
        }
        .status-graded {
            background: #c6f6d5;
            color: #22543d;
        }
        .status-late {
            background: #fed7d7;
            color: #9b2c2c;
        }
        .submission-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-item {
            font-size: 14px;
        }
        .detail-item strong {
            color: #4a5568;
            display: block;
            margin-bottom: 5px;
        }
        .grade-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        .grade-input-group {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .grade-input-group .form-group {
            flex: 1;
            min-width: 200px;
        }
        .btn-grade {
            background: #48bb78;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-grade:hover {
            background: #38a169;
        }
        .btn-download {
            background: #4f46e5;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-delete {
            background: #f56565;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin-left: 10px;
        }
        .existing-grade {
            background: #f7fafc;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .no-submissions {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 10px;
            color: #718096;
        }
        .reset-filter {
            color: #4f46e5;
            text-decoration: none;
            font-size: 14px;
            margin-left: 10px;
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
                <li><a href="submissions.php" class="active">Submissions</a></li>
                <li><a href="announcements.php">Announcements</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Student Submissions</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Submissions</h3>
                <div class="number"><?php echo $stats['total'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="number" style="color: #ed8936;"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Graded</h3>
                <div class="number" style="color: #48bb78;"><?php echo $stats['graded'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Late</h3>
                <div class="number" style="color: #f56565;"><?php echo $stats['late'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%; align-items: center;">
                <select name="course">
                    <option value="0">All Courses</option>
                    <?php while($course = mysqli_fetch_assoc($courses)): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="assignment">
                    <option value="0">All Assignments</option>
                    <?php 
                    mysqli_data_seek($assignments, 0);
                    while($assignment = mysqli_fetch_assoc($assignments)): 
                    ?>
                        <option value="<?php echo $assignment['id']; ?>" <?php echo $assignment_filter == $assignment['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="status">
                    <option value="all">All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="graded" <?php echo $status_filter == 'graded' ? 'selected' : ''; ?>>Graded</option>
                    <option value="late" <?php echo $status_filter == 'late' ? 'selected' : ''; ?>>Late</option>
                </select>

                <button type="submit" class="btn btn-primary">Apply Filters</button>
                
                <?php if ($course_filter > 0 || $assignment_filter > 0 || $status_filter != ''): ?>
                    <a href="submissions.php" class="reset-filter">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Submissions List -->
        <?php if (mysqli_num_rows($submissions) == 0): ?>
            <div class="no-submissions">
                <h2>No submissions found</h2>
                <p>There are no student submissions matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="submissions-grid">
                <?php while($sub = mysqli_fetch_assoc($submissions)): 
                    $due_date = strtotime($sub['due_date']);
                    $sub_date = strtotime($sub['submitted_at']);
                    $is_late = $sub_date > $due_date;
                    $card_class = $sub['status'];
                ?>
                    <div class="submission-card <?php echo $card_class; ?>">
                        <div class="submission-header">
                            <div>
                                <h2><?php echo htmlspecialchars($sub['assignment_title']); ?></h2>
                                <p class="student-info">
                                    Student: <?php echo htmlspecialchars($sub['student_name']); ?> 
                                    (<?php echo $sub['student_code']; ?>) - 
                                    Course: <?php echo htmlspecialchars($sub['course_code']); ?>
                                </p>
                            </div>
                            <span class="status-badge status-<?php echo $sub['status']; ?>">
                                <?php echo ucfirst($sub['status']); ?>
                            </span>
                        </div>

                        <div class="submission-details">
                            <div class="detail-item">
                                <strong>Submitted</strong>
                                <?php echo date('M d, Y H:i', $sub_date); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Due Date</strong>
                                <?php echo date('M d, Y H:i', $due_date); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Max Score</strong>
                                <?php echo $sub['max_score']; ?> points
                            </div>
                            <?php if ($is_late && $sub['status'] != 'graded'): ?>
                                <div class="detail-item" style="color: #f56565;">
                                    <strong>⚠️ Late Submission</strong>
                                    <?php echo ceil(($sub_date - $due_date) / 3600); ?> hours late
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($sub['comments']): ?>
                            <div style="background: #f7fafc; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                                <strong>Student Comments:</strong>
                                <p style="margin-top: 5px;"><?php echo nl2br(htmlspecialchars($sub['comments'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                            <a href="<?php echo $sub['file_path']; ?>" download class="btn-download">📥 Download Submission</a>
                            <a href="?delete=<?php echo $sub['id']; ?>" class="btn-delete" onclick="return confirm('Delete this submission?')">🗑️ Delete</a>
                        </div>

                        <?php if ($sub['existing_score']): ?>
                            <div class="existing-grade">
                                <strong>Current Grade:</strong> <?php echo $sub['existing_score']; ?>/<?php echo $sub['max_score']; ?><br>
                                <strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($sub['existing_feedback'] ?: 'No feedback')); ?>
                            </div>
                        <?php endif; ?>

                        <div class="grade-form">
                            <h3><?php echo $sub['existing_score'] ? 'Update Grade' : 'Grade Submission'; ?></h3>
                            <form method="POST">
                                <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $sub['student_id']; ?>">
                                <input type="hidden" name="assignment_id" value="<?php echo $sub['assignment_id']; ?>">
                                
                                <div class="grade-input-group">
                                    <div class="form-group">
                                        <label>Score (0-<?php echo $sub['max_score']; ?>)</label>
                                        <input type="number" class="form-control" name="score" 
                                               value="<?php echo $sub['existing_score'] ?: ''; ?>" 
                                               min="0" max="<?php echo $sub['max_score']; ?>" step="0.5" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Feedback</label>
                                        <textarea class="form-control" name="feedback" rows="2" placeholder="Add comments about the submission..."><?php echo htmlspecialchars($sub['existing_feedback'] ?: ''); ?></textarea>
                                    </div>
                                    <div>
                                        <button type="submit" name="grade_submission" class="btn-grade">
                                            <?php echo $sub['existing_score'] ? 'Update Grade' : 'Submit Grade'; ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>