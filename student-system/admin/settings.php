<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

requireAdminLogin();

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    
    $site_name = mysqli_real_escape_string($conn, $_POST['site_name']);
    $site_description = mysqli_real_escape_string($conn, $_POST['site_description']);
    $items_per_page = (int)$_POST['items_per_page'];
    $allow_registration = isset($_POST['allow_registration']) ? 1 : 0;
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
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
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($settings as $key => $value) {
        // Check if setting exists
        $check = mysqli_query($conn, "SELECT id FROM admin_settings WHERE setting_key = '$key'");
        
        if (mysqli_num_rows($check) > 0) {
            $query = "UPDATE admin_settings SET setting_value = '$value', updated_by = $admin_id WHERE setting_key = '$key'";
            if (mysqli_query($conn, $query)) {
                $success_count++;
            } else {
                $error_count++;
                error_log("Failed to update setting $key: " . mysqli_error($conn));
            }
        } else {
            $query = "INSERT INTO admin_settings (setting_key, setting_value, updated_by) VALUES ('$key', '$value', $admin_id)";
            if (mysqli_query($conn, $query)) {
                $success_count++;
            } else {
                $error_count++;
                error_log("Failed to insert setting $key: " . mysqli_error($conn));
            }
        }
    }
    
    if ($error_count == 0) {
        $message = "All $success_count settings saved successfully!";
        logAdminAction($conn, $admin_id, 'UPDATE_SETTINGS', 'Updated system settings');
    } else {
        $error = "Saved $success_count settings, but $error_count failed. Check error logs.";
    }
}

// Get current settings
$settings = [];
$result = mysqli_query($conn, "SELECT setting_key, setting_value FROM admin_settings");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Default settings
$default_settings = [
    'site_name' => 'Student Portal',
    'site_description' => 'Your Academic Management System',
    'items_per_page' => 10,
    'allow_registration' => 1,
    'maintenance_mode' => 0,
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
foreach ($default_settings as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Get admin name for footer
$admin_name = $_SESSION['admin_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #818cf8;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] {
            --bg-color: #1a202c;
            --text-color: #f7fafc;
            --card-bg: #2d3748;
            --border-color: #4a5568;
        }
        
        body {
            background-color: <?php echo $settings['theme_mode'] == 'dark' ? '#1a202c' : '#f7fafc'; ?>;
            color: <?php echo $settings['theme_mode'] == 'dark' ? '#f7fafc' : '#2d3748'; ?>;
            transition: all 0.3s ease;
        }
        
        .navbar {
            background: <?php echo $settings['theme_mode'] == 'dark' ? '#2d3748' : '#ffffff'; ?>;
            border-bottom: 1px solid <?php echo $settings['theme_mode'] == 'dark' ? '#4a5568' : '#e2e8f0'; ?>;
        }
        
        .navbar .logo {
            color: <?php echo $settings['theme_mode'] == 'dark' ? '#f7fafc' : '#4f46e5'; ?>;
        }
        
        .navbar .nav-menu a {
            color: <?php echo $settings['theme_mode'] == 'dark' ? '#f7fafc' : '#2d3748'; ?>;
        }
        
        .card, .settings-section, .form-container, .stat-card, .course-card {
            background: <?php echo $settings['theme_mode'] == 'dark' ? '#2d3748' : '#ffffff'; ?>;
            border: 1px solid <?php echo $settings['theme_mode'] == 'dark' ? '#4a5568' : '#e2e8f0'; ?>;
            color: <?php echo $settings['theme_mode'] == 'dark' ? '#f7fafc' : '#2d3748'; ?>;
        }
        
        input, select, textarea {
            background: <?php echo $settings['theme_mode'] == 'dark' ? '#1a202c' : '#ffffff'; ?>;
            color: <?php echo $settings['theme_mode'] == 'dark' ? '#f7fafc' : '#2d3748'; ?>;
            border: 1px solid <?php echo $settings['theme_mode'] == 'dark' ? '#4a5568' : '#e2e8f0'; ?>;
        }
        
        .footer {
            background: <?php echo $settings['theme_mode'] == 'dark' ? '#2d3748' : '#ffffff'; ?>;
            color: <?php echo $settings['theme_mode'] == 'dark' ? '#f7fafc' : '#718096'; ?>;
        }
        
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .settings-section {
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .settings-section h2 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid <?php echo $settings['theme_mode'] == 'dark' ? '#4a5568' : '#e2e8f0'; ?>;
            color: #4f46e5;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .theme-preview {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            padding: 15px;
            background: <?php echo $settings['theme_mode'] == 'dark' ? '#1a202c' : '#f7fafc'; ?>;
            border-radius: 8px;
            border: 1px solid <?php echo $settings['theme_mode'] == 'dark' ? '#4a5568' : '#e2e8f0'; ?>;
        }
        
        .theme-option {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
            text-align: center;
        }
        
        .theme-option:hover {
            transform: translateY(-2px);
        }
        
        .theme-option.selected {
            border-color: #4f46e5;
        }
        
        .theme-light {
            background: white;
            color: #333;
            border: 1px solid #e2e8f0;
        }
        
        .theme-dark {
            background: #1a202c;
            color: white;
        }
        
        .theme-auto {
            background: linear-gradient(135deg, white 50%, #1a202c 50%);
            color: #333;
        }
        
        .btn-save {
            background: #4f46e5;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-save:hover {
            background: #818cf8;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #9b2c2c;
            border: 1px solid #feb2b2;
        }
        
        .live-preview {
            margin-top: 20px;
            padding: 20px;
            background: <?php echo $settings['theme_mode'] == 'dark' ? '#1a202c' : '#ffffff'; ?>;
            border: 1px solid <?php echo $settings['theme_mode'] == 'dark' ? '#4a5568' : '#e2e8f0'; ?>;
            border-radius: 8px;
        }
        
        .preview-button {
            background: #4f46e5;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            margin-right: 10px;
            cursor: pointer;
        }
        
        .preview-button.secondary {
            background: #818cf8;
        }
        
        .font-preview {
            font-size: <?php echo $settings['font_size'] == 'small' ? '14px' : ($settings['font_size'] == 'large' ? '18px' : '16px'); ?>;
            margin-top: 15px;
            padding: 10px;
            background: <?php echo $settings['theme_mode'] == 'dark' ? '#2d3748' : '#f7fafc'; ?>;
            border-radius: 5px;
        }
    </style>
</head>
<body data-theme="<?php echo $settings['theme_mode']; ?>">
    <nav class="navbar admin-nav">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">AdminPortal</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="students.php">Students</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="assignments.php">Assignments</a></li>
                <li><a href="submissions.php">Submissions</a></li>
                <li><a href="announcements.php">Announcements</a></li>
                <li><a href="admins.php">Admins</a></li>
                <li><a href="settings.php" class="active">Settings</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container settings-container">
        <h1>System Settings</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="settingsForm">
            <!-- General Settings -->
            <div class="settings-section">
                <h2>⚙️ General Settings</h2>
                <div class="settings-grid">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Site Description</label>
                        <input type="text" class="form-control" name="site_description" value="<?php echo htmlspecialchars($settings['site_description']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Items Per Page</label>
                        <input type="number" class="form-control" name="items_per_page" value="<?php echo $settings['items_per_page']; ?>" min="5" max="100">
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="checkbox" name="allow_registration" value="1" <?php echo $settings['allow_registration'] ? 'checked' : ''; ?>>
                        Allow Student Registration
                    </label>
                    <label style="display: block;">
                        <input type="checkbox" name="maintenance_mode" value="1" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                        Maintenance Mode
                    </label>
                </div>
            </div>

            <!-- Theme Settings -->
            <div class="settings-section">
                <h2>🎨 Theme Settings</h2>
                
                <div class="settings-grid">
                    <div class="form-group">
                        <label>Font Size</label>
                        <select class="form-control" name="font_size" id="fontSize" onchange="updateTheme()">
                            <option value="small" <?php echo $settings['font_size'] == 'small' ? 'selected' : ''; ?>>Small</option>
                            <option value="medium" <?php echo $settings['font_size'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="large" <?php echo $settings['font_size'] == 'large' ? 'selected' : ''; ?>>Large</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Theme Mode</label>
                    <div class="theme-preview">
                        <div class="theme-option theme-light <?php echo $settings['theme_mode'] == 'light' ? 'selected' : ''; ?>" onclick="selectTheme('light')">
                            <strong>☀️ Light Mode</strong>
                            <p style="font-size: 12px; margin-top: 5px;">Light background, dark text</p>
                        </div>
                        <div class="theme-option theme-dark <?php echo $settings['theme_mode'] == 'dark' ? 'selected' : ''; ?>" onclick="selectTheme('dark')">
                            <strong>🌙 Dark Mode</strong>
                            <p style="font-size: 12px; margin-top: 5px; color: #a0aec0;">Dark background, light text</p>
                        </div>
                        <div class="theme-option theme-auto <?php echo $settings['theme_mode'] == 'auto' ? 'selected' : ''; ?>" onclick="selectTheme('auto')">
                            <strong>🔄 Auto</strong>
                            <p style="font-size: 12px; margin-top: 5px;">Follows system preference</p>
                        </div>
                    </div>
                    <input type="hidden" name="theme_mode" id="theme_mode" value="<?php echo $settings['theme_mode']; ?>">
                </div>

                <!-- Live Preview -->
                <div class="live-preview">
                    <h3>Live Preview</h3>
                    <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                        <button class="preview-button">Primary Button</button>
                        <button class="preview-button secondary">Secondary Button</button>
                        <button class="preview-button" style="background: transparent; color: <?php echo $settings['theme_mode'] == 'dark' ? '#f7fafc' : '#4f46e5'; ?>; border: 1px solid #4f46e5;">Outlined Button</button>
                    </div>
                    <div class="font-preview">
                        <strong>Font Size Preview:</strong><br>
                        This is a preview of the current font size setting. 
                        The quick brown fox jumps over the lazy dog.
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="settings-section">
                <h2>📧 Email Settings</h2>
                <div class="settings-grid">
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" class="form-control" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Port</label>
                        <input type="text" class="form-control" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port']); ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="text" class="form-control" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" class="form-control" name="smtp_password" value="<?php echo htmlspecialchars($settings['smtp_password']); ?>">
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="settings-section">
                <h2>🔒 Security Settings</h2>
                <div class="settings-grid">
                    <div class="form-group">
                        <label>Session Timeout (minutes)</label>
                        <input type="number" class="form-control" name="session_timeout" value="<?php echo $settings['session_timeout']; ?>" min="5" max="480">
                    </div>
                    <div class="form-group">
                        <label>Max Login Attempts</label>
                        <input type="number" class="form-control" name="max_login_attempts" value="<?php echo $settings['max_login_attempts']; ?>" min="3" max="10">
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="checkbox" name="two_factor_auth" value="1" <?php echo $settings['two_factor_auth'] ? 'checked' : ''; ?>>
                        Enable Two-Factor Authentication
                    </label>
                    <label style="display: block;">
                        <input type="checkbox" name="password_expiry" value="1" <?php echo $settings['password_expiry'] ? 'checked' : ''; ?>>
                        Force Password Change Every 90 Days
                    </label>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" name="save_settings" class="btn-save">Save All Settings</button>
            </div>
        </form>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Student Portal Admin Panel. Logged in as <?php echo htmlspecialchars($admin_name); ?></p>
    </footer>

    <script>
    // Update theme in real-time
    function updateTheme() {
        const fontSize = document.getElementById('fontSize').value;
        
        // Update font size preview
        const fontPreview = document.querySelector('.font-preview');
        if (fontSize === 'small') {
            fontPreview.style.fontSize = '14px';
        } else if (fontSize === 'large') {
            fontPreview.style.fontSize = '18px';
        } else {
            fontPreview.style.fontSize = '16px';
        }
    }

    // Select theme mode
    function selectTheme(mode) {
        document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('selected'));
        event.target.closest('.theme-option').classList.add('selected');
        document.getElementById('theme_mode').value = mode;
        
        // Apply theme mode
        if (mode === 'dark') {
            document.body.style.backgroundColor = '#1a202c';
            document.body.style.color = '#f7fafc';
            
            // Update navbar
            document.querySelector('.navbar').style.backgroundColor = '#2d3748';
            document.querySelector('.navbar').style.borderBottomColor = '#4a5568';
            
            // Update cards
            document.querySelectorAll('.settings-section').forEach(el => {
                el.style.backgroundColor = '#2d3748';
                el.style.borderColor = '#4a5568';
            });
            
        } else if (mode === 'light') {
            document.body.style.backgroundColor = '#f7fafc';
            document.body.style.color = '#2d3748';
            
            // Update navbar
            document.querySelector('.navbar').style.backgroundColor = '#ffffff';
            document.querySelector('.navbar').style.borderBottomColor = '#e2e8f0';
            
            // Update cards
            document.querySelectorAll('.settings-section').forEach(el => {
                el.style.backgroundColor = '#ffffff';
                el.style.borderColor = '#e2e8f0';
            });
            
        } else if (mode === 'auto') {
            // Check system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                selectTheme('dark');
            } else {
                selectTheme('light');
            }
        }
    }

    // Initialize theme based on saved settings
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = '<?php echo $settings['theme_mode']; ?>';
        if (savedTheme === 'auto') {
            // Check system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.body.setAttribute('data-theme', 'dark');
            }
        }
    });

    // Listen for system theme changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            const currentTheme = document.getElementById('theme_mode').value;
            if (currentTheme === 'auto') {
                if (e.matches) {
                    selectTheme('dark');
                } else {
                    selectTheme('light');
                }
            }
        });
    }

    // Form submission feedback
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        const saveBtn = this.querySelector('button[type="submit"]');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
    });
    </script>
</body>
</html>