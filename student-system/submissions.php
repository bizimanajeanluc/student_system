<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$student_id = $_SESSION['student_id'];
$message = '';
$error = '';

// Handle new submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['submission_file'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    $comments = mysqli_real_escape_string($conn, $_POST['comments']);
    
    // Check if already submitted
    $check = mysqli_query($conn, "SELECT id FROM submissions WHERE student_id = $student_id AND assignment_id = $assignment_id");
    
    if (mysqli_num_rows($check) > 0) {
        $error = 'You have already submitted this assignment.';
    } else {
        $file = $_FILES['submission_file'];
        
        // Validate file
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed', 'text/plain'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['pdf', 'doc', 'docx', 'zip', 'txt'];
        
        if (!in_array($file_ext, $allowed_exts) && !in_array($file['type'], $allowed_types)) {
            $error = 'Invalid file type. Allowed: PDF, DOC, DOCX, ZIP, TXT';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $error = 'File size must be less than 10MB';
        } else {
            // Upload submission
            $target_dir = "assets/submissions/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_name = time() . '_' . $student_id . '_' . $assignment_id . '.' . $file_ext;
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                // Check if late
                $assignment_query = mysqli_query($conn, "SELECT due_date FROM assignments WHERE id = $assignment_id");
                $assignment = mysqli_fetch_assoc($assignment_query);
                $status = (time() > strtotime($assignment['due_date'])) ? 'late' : 'pending';
                
                $query = "INSERT INTO submissions (student_id, assignment_id, file_name, file_path, comments, status) 
                          VALUES ($student_id, $assignment_id, '$file_name', '$target_file', '$comments', '$status')";
                
                if (mysqli_query($conn, $query)) {
                    $message = 'Assignment submitted successfully!';
                } else {
                    $error = 'Failed to save submission.';
                }
            } else {
                $error = 'Failed to upload file.';
            }
        }
    }
}

// Get student's courses for dropdown
$courses = getStudentCourses($student_id);

// Get student's submissions with details - IMPROVED QUERY
$submissions_query = "SELECT s.*, a.title as assignment_title, a.max_score, a.due_date,
                      c.course_code, c.course_name,
                      g.score, g.feedback as grade_feedback, g.graded_at
                      FROM submissions s
                      JOIN assignments a ON s.assignment_id = a.id
                      JOIN courses c ON a.course_id = c.id
                      LEFT JOIN grades g ON s.assignment_id = g.assignment_id AND g.student_id = s.student_id
                      WHERE s.student_id = $student_id
                      ORDER BY s.submitted_at DESC";
$submissions = mysqli_query($conn, $submissions_query);

// Get available assignments (not submitted yet)
$available_assignments = [];
foreach ($courses as $course) {
    $assignments = getCourseAssignments($course['id']);
    foreach ($assignments as $assignment) {
        $check = mysqli_query($conn, "SELECT id FROM submissions WHERE student_id = $student_id AND assignment_id = {$assignment['id']}");
        if (mysqli_num_rows($check) == 0) {
            $assignment['course_code'] = $course['course_code'];
            $assignment['course_name'] = $course['course_name'];
            $available_assignments[] = $assignment;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .submissions-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-top: 30px;
        }
        .submit-form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .submissions-list {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .submission-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
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
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #feebc8; color: #744210; }
        .status-graded { background: #c6f6d5; color: #22543d; }
        .status-late { background: #fed7d7; color: #9b2c2c; }
        
        /* Grade Display Styles */
        .grade-display {
            margin-top: 15px;
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .grade-header {
            background: linear-gradient(135deg, #4f46e5 0%, #818cf8 100%);
            color: white;
            padding: 10px 15px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .grade-score {
            font-size: 20px;
            font-weight: bold;
            color: white;
        }
        .grade-content {
            padding: 15px;
        }
        .grade-feedback {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #4f46e5;
            margin-top: 10px;
        }
        .feedback-label {
            font-weight: 600;
            color: #4f46e5;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .feedback-text {
            color: #2d3748;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .grade-meta {
            font-size: 12px;
            color: #718096;
            margin-top: 10px;
            text-align: right;
        }
        .no-feedback {
            color: #a0aec0;
            font-style: italic;
            padding: 10px;
            text-align: center;
        }
        .file-info {
            background: #f7fafc;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 13px;
            border: 1px solid #e2e8f0;
        }
        .btn-download {
            background: #4f46e5;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
            margin-left: 10px;
        }
        .btn-download:hover {
            background: #818cf8;
        }
        .comment-bubble {
            background: #f0f4ff;
            border-radius: 8px;
            padding: 8px 12px;
            margin: 8px 0;
            font-size: 13px;
            border-left: 3px solid #4f46e5;
        }
        .comment-author {
            font-weight: 600;
            color: #4f46e5;
            font-size: 12px;
            margin-bottom: 3px;
        }
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
                <li><a href="submissions.php" class="active">Submissions</a></li>
                <li><a href="uploads.php">Uploads</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>My Submissions</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="submissions-container">
            <!-- Submit New Assignment -->
            <div class="submit-form">
                <h2>Submit Assignment</h2>
                
                <?php if (empty($available_assignments)): ?>
                    <div class="no-assignments">
                        <p>You have no pending assignments to submit.</p>
                        <p style="margin-top: 10px;">All assignments have been submitted or you're not enrolled in any courses with pending assignments.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Select Assignment</label>
                            <select class="form-control" name="assignment_id" required>
                                <option value="">Choose assignment...</option>
                                <?php foreach ($available_assignments as $assignment): ?>
                                    <option value="<?php echo $assignment['id']; ?>">
                                        <?php echo $assignment['course_code'] . ' - ' . $assignment['title']; ?> 
                                        (Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload File</label>
                            <input type="file" class="form-control" name="submission_file" required accept=".pdf,.doc,.docx,.zip,.txt">
                            <small>Supported: PDF, DOC, DOCX, ZIP, TXT (Max 10MB)</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Comments (Optional)</label>
                            <textarea class="form-control" name="comments" rows="3" placeholder="Add any notes for the instructor..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Submit Assignment</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- My Submissions -->
            <div class="submissions-list">
                <h2>My Submissions</h2>
                
                <?php if (mysqli_num_rows($submissions) == 0): ?>
                    <div class="no-assignments">
                        <p>No submissions yet.</p>
                        <p>Use the form to submit your first assignment.</p>
                    </div>
                <?php else: ?>
                    <?php while($sub = mysqli_fetch_assoc($submissions)): 
                        $due_date = strtotime($sub['due_date']);
                        $sub_date = strtotime($sub['submitted_at']);
                        $is_late = $sub_date > $due_date;
                    ?>
                        <div class="submission-card <?php echo $sub['status']; ?>">
                            <div class="submission-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($sub['assignment_title']); ?></h3>
                                    <p style="font-size: 13px; color: #718096;">
                                        <?php echo $sub['course_code']; ?> | Due: <?php echo date('M d, Y', $due_date); ?>
                                    </p>
                                </div>
                                <span class="status-badge status-<?php echo $sub['status']; ?>">
                                    <?php echo ucfirst($sub['status']); ?>
                                </span>
                            </div>
                            
                            <div class="file-info">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>
                                        <strong>Submitted:</strong> <?php echo date('M d, Y H:i', $sub_date); ?>
                                    </span>
                                    <a href="<?php echo $sub['file_path']; ?>" download class="btn-download">📥 Download</a>
                                </div>
                                <?php if ($sub['comments']): ?>
                                    <div class="comment-bubble">
                                        <div class="comment-author">Your note:</div>
                                        <?php echo nl2br(htmlspecialchars($sub['comments'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($sub['score'] !== null): ?>
                                <div class="grade-display">
                                    <div class="grade-header">
                                        <span>Grade Received</span>
                                        <span class="grade-score"><?php echo $sub['score']; ?>/<?php echo $sub['max_score']; ?></span>
                                    </div>
                                    <div class="grade-content">
                                        <?php if ($sub['grade_feedback']): ?>
                                            <div class="grade-feedback">
                                                <div class="feedback-label">
                                                    <span>📝 Instructor Feedback</span>
                                                </div>
                                                <div class="feedback-text">
                                                    <?php echo nl2br(htmlspecialchars($sub['grade_feedback'])); ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-feedback">
                                                No feedback provided by instructor.
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="grade-meta">
                                            Graded on: <?php echo date('F j, Y \a\t g:i A', strtotime($sub['graded_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Student Portal. All rights reserved.</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>