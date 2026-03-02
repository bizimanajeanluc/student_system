<?php
/**
 * Admin Authentication Helper
 */

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']);
}

// Redirect if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ../admin/login.php');
        exit();
    }
}

// Redirect if already logged in
function redirectIfAdminLoggedIn() {
    if (isAdminLoggedIn()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Check if admin has specific role
function hasRole($role) {
    if (!isAdminLoggedIn()) return false;
    
    if ($role == 'super_admin') {
        return $_SESSION['admin_role'] == 'super_admin';
    }
    
    return $_SESSION['admin_role'] == $role || $_SESSION['admin_role'] == 'super_admin';
}

// Require specific role
function requireRole($role) {
    if (!hasRole($role)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: dashboard.php');
        exit();
    }
}

// Log admin action
function logAdminAction($conn, $admin_id, $action, $description = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $query = "INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isss", $admin_id, $action, $description, $ip);
    return mysqli_stmt_execute($stmt);
}

// Get admin by ID
function getAdminById($conn, $admin_id) {
    $query = "SELECT * FROM admins WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Update last login
function updateLastLogin($conn, $admin_id) {
    $query = "UPDATE admins SET last_login = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    return mysqli_stmt_execute($stmt);
}
?>