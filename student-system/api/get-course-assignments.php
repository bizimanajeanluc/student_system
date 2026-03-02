<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Course ID required']);
    exit();
}

$query = "SELECT id, title, due_date FROM assignments WHERE course_id = ? ORDER BY due_date";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $course_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$assignments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $assignments[] = $row;
}

echo json_encode(['success' => true, 'assignments' => $assignments]);
?>