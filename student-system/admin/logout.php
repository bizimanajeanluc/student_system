<?php
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

if (isset($_SESSION['admin_id'])) {
    logAdminAction($conn, $_SESSION['admin_id'], 'LOGOUT', 'Admin logged out');
}

// Clear all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>