<?php
require_once __DIR__ . '/../includes/config.php';

$test_password = 'admin123';
$username = 'admin';

$query = "SELECT * FROM admins WHERE username = '$username'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 1) {
    $admin = mysqli_fetch_assoc($result);
    
    echo "<h1>Password Test for user: $username</h1>";
    echo "<strong>Stored hash:</strong> " . $admin['password'] . "<br><br>";
    
    if (password_verify($test_password, $admin['password'])) {
        echo "<span style='color:green; font-size:20px;'>✅ Password '$test_password' is CORRECT!</span>";
    } else {
        echo "<span style='color:red; font-size:20px;'>❌ Password '$test_password' is INCORRECT!</span>";
        
        // Generate new hash
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "<br><br><strong>New hash for '$test_password':</strong> $new_hash";
        echo "<br><br>Run this SQL to fix:<br>";
        echo "<code>UPDATE admins SET password = '$new_hash' WHERE username = 'admin';</code>";
    }
} else {
    echo "User '$username' not found!";
}
?>