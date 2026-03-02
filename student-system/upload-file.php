<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
    $student_id = $_SESSION['student_id'];
    $course_id = (int)$_POST['course_id'];
    $assignment_id = isset($_POST['assignment_id']) && $_POST['assignment_id'] ? (int)$_POST['assignment_id'] : null;
    
    $files = $_FILES['files'];
    $success_count = 0;
    $error_count = 0;
    
    for ($i = 0; $i < count($files['name']); $i++) {
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];
        
        if (uploadFile($student_id, $course_id, $assignment_id, $file)) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    if ($success_count > 0) {
        $_SESSION['message'] = "$success_count file(s) uploaded successfully!";
    }
    
    if ($error_count > 0) {
        $_SESSION['error'] = "$error_count file(s) failed to upload.";
    }
}

header('Location: uploads.php');
exit();
?>