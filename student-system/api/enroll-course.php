<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$course_id = isset($data['course_id']) ? (int)$data['course_id'] : 0;
$student_id = $_SESSION['student_id'];

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Course ID required']);
    exit();
}

if (isEnrolled($student_id, $course_id)) {
    echo json_encode(['success' => false, 'message' => 'Already enrolled in this course']);
    exit();
}

if (enrollStudent($student_id, $course_id)) {
    echo json_encode(['success' => true, 'message' => 'Successfully enrolled']);
} else {
    echo json_encode(['success' => false, 'message' => 'Enrollment failed']);
}
?>