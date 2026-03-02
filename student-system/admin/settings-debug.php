<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

requireAdminLogin();

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Debug: Show POST data if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h2>Debug: Form Submitted</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['save_settings'])) {
        $site_name = mysqli_real_escape_string($conn, $_POST['site_name']);
        $site_description = mysqli_real_escape_string($conn, $_POST['site_description']);
        $items_per_page = (int)$_POST['items_per_page'];
        $allow_registration = isset($_POST['allow_registration']) ? 1 : 0;
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $theme_primary = mysqli_real_escape_string($conn, $_POST['theme_primary']);
        $theme_secondary = mysqli_real_escape_string($conn, $_POST['theme_secondary']);
        $theme_mode = mysqli_real_escape_string($conn, $_POST['theme_mode']);
        $font_size = mysqli_real_escape_string($conn, $_POST['font_size']);
        $smtp_host = mysqli_real_escape_string($conn, $_POST['smtp_host']);
        $smtp_port = mysqli_real_escape_string($conn, $_POST['smtp_port']);
        $smtp_username = mysqli_real_escape_string($conn, $_POST['smtp_username']);
        $smtp_password = mysqli_real_escape_string($conn, $_POST['smtp_password']);
        $session_timeout = (int)$_POST['session_timeout'];
        $max_login_attempts = (int)$_POST['max_login_attempts'];
        $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
        $password_expiry = isset($_POST['password_expiry']) ? 1 : 0;
        
        $settings = [
            'site_name' => $site_name,
            'site_description' => $site_description,
            'items_per_page' => $items_per_page,
            'allow_registration' => $allow_registration,
            'maintenance_mode' => $maintenance_mode,
            'theme_primary' => $theme_primary,
            'theme_secondary' => $theme_secondary,
            'theme_mode' => $theme_mode,
            'font_size' => $font_size,
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_username' => $smtp_username,
            'smtp_password' => $smtp_password,
            'session_timeout' => $session_timeout,
            'max_login_attempts' => $max_login_attempts,
            'two_factor_auth' => $two_factor_auth,
            'password_expiry' => $password_expiry
        ];
        
        echo "<h3>Attempting to save:</h3>";
        foreach ($settings as $key => $value) {
            // Check if setting exists
            $check = mysqli_query($conn, "SELECT id FROM admin_settings WHERE setting_key = '$key'");
            
            if (mysqli_num_rows($check) > 0) {
                $query = "UPDATE admin_settings SET setting_value = '$value', updated_by = $admin_id WHERE setting_key = '$key'";
                $result = mysqli_query($conn, $query);
                echo "Updating $key: " . ($result ? "✅ SUCCESS" : "❌ FAILED - " . mysqli_error($conn)) . "<br>";
            } else {
                $query = "INSERT INTO admin_settings (setting_key, setting_value, updated_by) VALUES ('$key', '$value', $admin_id)";
                $result = mysqli_query($conn, $query);
                echo "Inserting $key: " . ($result ? "✅ SUCCESS" : "❌ FAILED - " . mysqli_error($conn)) . "<br>";
            }
        }
        
        $message = 'Settings saved successfully!';
        logAdminAction($conn, $admin_id, 'UPDATE_SETTINGS', 'Updated system settings');
    }
}

// Get current settings
$settings = [];
$result = mysqli_query($conn, "SELECT setting_key, setting_value FROM admin_settings");
echo "<h3>Current settings in database:</h3>";
echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
    echo $row['setting_key'] . " => " . $row['setting_value'] . "\n";
}
echo "</pre>";

// Default settings
$default_settings = [
    'site_name' => 'Student Portal',
    'site_description' => 'Your Academic Management System',
    'items_per_page' => 10,
    'allow_registration' => 1,
    'maintenance_mode' => 0,
    'theme_primary' => '#4f46e5',
    'theme_secondary' => '#818cf8',
    'theme_mode' => 'light',
    'font_size' => 'medium',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'session_timeout' => 30,
    'max_login_attempts' => 5,
    'two_factor_auth' => 0,
    'password_expiry' => 0
];

// Merge with defaults
$settings = array_merge($default_settings, $settings);
?>

<!-- Your existing HTML form here (copy from settings.php) -->
<!-- ... -->