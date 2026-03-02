<?php
require_once __DIR__ . '/../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if username already exists
        $check_query = "SELECT id FROM admins WHERE username = ? OR email = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = 'Username or email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin
            $insert_query = "INSERT INTO admins (username, email, password, full_name, role, is_active) 
                            VALUES (?, ?, ?, ?, 'admin', 1)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $email, $hashed_password, $full_name);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $success = 'Admin registered successfully! You can now login.';
                
                // Log the registration
                $new_id = mysqli_insert_id($conn);
                error_log("New admin registered: ID=$new_id, Username=$username");
            } else {
                $error = 'Registration failed: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Get all admins for display
$admins_query = "SELECT id, username, email, full_name, role, is_active, created_at FROM admins ORDER BY id DESC";
$admins_result = mysqli_query($conn, $admins_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Student Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .register-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5a67d8;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fed7d7;
            color: #9b2c2c;
            border: 1px solid #feb2b2;
        }
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f7fafc;
            padding: 12px;
            text-align: left;
            font-size: 14px;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        tr:hover {
            background: #f7fafc;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-active {
            background: #c6f6d5;
            color: #22543d;
        }
        .badge-inactive {
            background: #fed7d7;
            color: #9b2c2c;
        }
        .badge-super_admin {
            background: #9f7aea;
            color: white;
        }
        .badge-admin {
            background: #4299e1;
            color: white;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: white;
        }
        .login-link a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .debug-info {
            background: #2d3748;
            color: #a0aec0;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Admin Registration</h1>
            <p>Create a new admin account or view existing admins</p>
        </div>
        
        <div class="register-container">
            <h2>Register New Admin</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="e.g., john_admin">
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                               placeholder="e.g., John Doe">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="john@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password * (min 6 characters)</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="********">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="********">
                    </div>
                    
                    <div class="form-group full-width">
                        <button type="submit">Register Admin</button>
                    </div>
                </div>
            </form>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
        
        <div class="table-container">
            <h2 style="margin-bottom: 20px;">📋 Existing Admins</h2>
            
            <?php if (mysqli_num_rows($admins_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Test Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($admin = mysqli_fetch_assoc($admins_result)): ?>
                        <tr>
                            <td><?php echo $admin['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $admin['role']; ?>">
                                    <?php echo ucfirst($admin['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $admin['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                            <td>
                                <form action="test-login.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="username" value="<?php echo $admin['username']; ?>">
                                    <input type="hidden" name="password" value="test123">
                                    <button type="submit" style="background: #48bb78; padding: 5px 10px; font-size: 12px;">Test</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #718096; padding: 20px;">No admins found. Register one above!</p>
            <?php endif; ?>
        </div>
        
        <div class="debug-info">
            <h3 style="color: white; margin-bottom: 10px;">🔧 Debug Information:</h3>
            <?php
            echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
            echo "<strong>Database:</strong> " . (mysqli_ping($conn) ? 'Connected' : 'Disconnected') . "<br>";
            
            // Test query
            $test = mysqli_query($conn, "SELECT COUNT(*) as count FROM admins");
            if ($test) {
                $count = mysqli_fetch_assoc($test);
                echo "<strong>Total Admins:</strong> " . $count['count'] . "<br>";
            } else {
                echo "<strong>Error:</strong> " . mysqli_error($conn) . "<br>";
            }
            ?>
        </div>
    </div>
</body>
</html>