<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$student_id = $_SESSION['student_id'];

$query = "SELECT g.*, a.title as assignment_title, a.max_score, c.course_code
          FROM grades g
          JOIN assignments a ON g.assignment_id = a.id
          JOIN courses c ON a.course_id = c.id
          WHERE g.student_id = $student_id
          ORDER BY g.graded_at DESC
          LIMIT 5";

$result = mysqli_query($conn, $query);
$grades = [];

while ($row = mysqli_fetch_assoc($result)) {
    $grades[] = $row;
}

echo json_encode(['success' => true, 'grades' => $grades]);
?>