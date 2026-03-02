<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$student_id = $_SESSION['student_id'];

$query = "SELECT a.*, c.course_name, c.course_code 
          FROM assignments a
          JOIN courses c ON a.course_id = c.id
          JOIN enrollments e ON c.id = e.course_id
          WHERE e.student_id = $student_id 
          AND e.status = 'active'
          AND a.due_date > NOW()
          ORDER BY a.due_date
          LIMIT 5";

$result = mysqli_query($conn, $query);
$assignments = [];

while ($row = mysqli_fetch_assoc($result)) {
    $assignments[] = $row;
}

echo json_encode(['success' => true, 'assignments' => $assignments]);
?>