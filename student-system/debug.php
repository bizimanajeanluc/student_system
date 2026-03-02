<?php
require_once 'includes/config.php';

echo "<h1>Student System Debug</h1>";

// Check PHP version
echo "<h2>PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQLi Extension: " . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not Loaded') . "<br>";
echo "Session Extension: " . (extension_loaded('session') ? '✅ Loaded' : '❌ Not Loaded') . "<br>";

// Check database connection
echo "<h2>Database Connection</h2>";
if ($conn) {
    echo "✅ Database connected successfully!<br>";
    echo "Database host: " . DB_HOST . "<br>";
    echo "Database name: " . DB_NAME . "<br>";
} else {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "<br>";
}

// Check if database exists
$db_check = mysqli_select_db($conn, DB_NAME);
if ($db_check) {
    echo "✅ Database '" . DB_NAME . "' exists!<br>";
} else {
    echo "❌ Database '" . DB_NAME . "' not found!<br>";
}

// List all tables
echo "<h2>Database Tables</h2>";
$tables = mysqli_query($conn, "SHOW TABLES");
if ($tables && mysqli_num_rows($tables) > 0) {
    echo "<ul>";
    while ($table = mysqli_fetch_array($tables)) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "❌ No tables found! Please run the SQL setup.<br>";
}

// Check students table structure
echo "<h2>Students Table Structure</h2>";
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'students'");
if (mysqli_num_rows($table_check) > 0) {
    echo "✅ Students table exists!<br>";
    
    // Show table structure
    $columns = mysqli_query($conn, "DESCRIBE students");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($col = mysqli_fetch_assoc($columns)) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count students
    $count = mysqli_query($conn, "SELECT COUNT(*) as total FROM students");
    $row = mysqli_fetch_assoc($count);
    echo "📊 Number of students in database: " . $row['total'] . "<br>";
    
    // Show sample data
    if ($row['total'] > 0) {
        $sample = mysqli_query($conn, "SELECT id, full_name, email, student_id FROM students LIMIT 1");
        $student = mysqli_fetch_assoc($sample);
        echo "<h3>Sample Student:</h3>";
        echo "<pre>";
        print_r($student);
        echo "</pre>";
    }
} else {
    echo "❌ Students table does not exist!<br>";
}

// Test registration functionality
echo "<h2>Test Registration Form</h2>";
echo "Registration page should be accessible at: <a href='register.php'>register.php</a><br>";

// Test login functionality
echo "<h2>Test Login Form</h2>";
echo "Login page should be accessible at: <a href='login.php'>login.php</a><br>";

// Check session
echo "<h2>Session Status</h2>";
echo "Session ID: " . (session_id() ?: 'No active session') . "<br>";
if (isset($_SESSION['student_id'])) {
    echo "✅ User is logged in (ID: " . $_SESSION['student_id'] . ")<br>";
} else {
    echo "❌ No user logged in<br>";
}

// Check file permissions
echo "<h2>File Permissions</h2>";
$files_to_check = [
    'includes/config.php',
    'includes/auth.php',
    'includes/functions.php',
    'register.php',
    'login.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing!<br>";
    }
}

// Test password hashing
echo "<h2>Password Hashing Test</h2>";
$test_password = "password123";
$hashed = password_hash($test_password, PASSWORD_DEFAULT);
echo "Test password: $test_password<br>";
echo "Hashed password: $hashed<br>";
echo "Verification: " . (password_verify($test_password, $hashed) ? '✅ Works' : '❌ Fails') . "<br>";

// Check PHP error log
echo "<h2>PHP Error Log</h2>";
$log_errors = ini_get('log_errors');
$error_log = ini_get('error_log');
echo "Log errors: " . ($log_errors ? 'Enabled' : 'Disabled') . "<br>";
echo "Error log file: " . ($error_log ?: 'Not set') . "<br>";
?>