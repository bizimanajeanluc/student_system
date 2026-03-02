<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if (isset($_GET['id'])) {
    $upload_id = (int)$_GET['id'];
    $student_id = $_SESSION['student_id'];
    
    if (deleteUpload($upload_id, $student_id)) {
        $_SESSION['success_message'] = 'File deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete file.';
    }
}

header('Location: uploads.php');
exit();
?>