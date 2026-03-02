<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'])) {
    $student_id = $_SESSION['student_id'];
    $course_id = (int)$_POST['course_id'];
    
    // Check if already enrolled
    $check_query = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "ii", $student_id, $course_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) == 0) {
        // Not enrolled, proceed with enrollment
        $enroll_query = "INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'active')";
        $enroll_stmt = mysqli_prepare($conn, $enroll_query);
        mysqli_stmt_bind_param($enroll_stmt, "ii", $student_id, $course_id);
        
        if (mysqli_stmt_execute($enroll_stmt)) {
            $_SESSION['success_message'] = "Successfully enrolled in the course!";
        } else {
            $_SESSION['error_message'] = "Enrollment failed: " . mysqli_error($conn);
        }
        mysqli_stmt_close($enroll_stmt);
    } else {
        $_SESSION['error_message'] = "You are already enrolled in this course.";
    }
    mysqli_stmt_close($check_stmt);
}

// Redirect back to courses page
header('Location: courses.php');
exit();
?>