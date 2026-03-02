<?php
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

requireAdminLogin();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Handle Delete
if ($action == 'delete' && $id) {
    // Check if student has enrollments
    $check = mysqli_query($conn, "SELECT id FROM enrollments WHERE student_id = $id");
    if (mysqli_num_rows($check) > 0) {
        $error = 'Cannot delete student with existing enrollments. Deactivate instead.';
    } else {
        $query = "DELETE FROM students WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = 'Student deleted successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'DELETE_STUDENT', "Deleted student ID: $id");
            $action = 'list';
        } else {
            $error = 'Error deleting student: ' . mysqli_error($conn);
        }
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $year_level = (int)$_POST['year_level'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = (int)$_POST['id'];
        $query = "UPDATE students SET 
                  full_name = '$full_name',
                  email = '$email',
                  student_id = '$student_id',
                  course = '$course',
                  year_level = $year_level,
                  is_active = $is_active
                  WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            $message = 'Student updated successfully!';
            logAdminAction($conn, $_SESSION['admin_id'], 'UPDATE_STUDENT', "Updated student ID: $id");
            $action = 'list';
        } else {
            $error = 'Error updating student: ' . mysqli_error($conn);
        }
    } else {
        // Add new
        $password = password_hash('student123', PASSWORD_DEFAULT);
        $query = "INSERT INTO students (full_name, email, student_id, password, course, year_level, is_active) 
                  VALUES ('$full_name', '$email', '$student_id', '$password', '$course', $year_level, $is_active)";
        
        if (mysqli_query($conn, $query)) {
            $new_id = mysqli_insert_id($conn);
            $message = 'Student added successfully! Default password: student123';
            logAdminAction($conn, $_SESSION['admin_id'], 'ADD_STUDENT', "Added student ID: $new_id");
            $action = 'list';
        } else {
            $error = 'Error adding student: ' . mysqli_error($conn);
        }
    }
}

// Get student data for edit
$student = null;
if ($action == 'edit' && $id) {
    $result = mysqli_query($conn, "SELECT * FROM students WHERE id = $id");
    $student = mysqli_fetch_assoc($result);
    if (!$student) {
        $action = 'list';
        $error = 'Student not found.';
    }
}

// Get all students
$students = mysqli_query($conn, "SELECT * FROM students ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #c6f6d5;
            color: #22543d;
        }
        .status-inactive {
            background: #fed7d7;
            color: #9b2c2c;
        }
        .table-actions a {
            margin: 0 5px;
            text-decoration: none;
        }
        .filter-bar {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .search-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <nav class="navbar admin-nav">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">AdminPortal</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="students.php">Students</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="assignments.php">Assignments</a></li>
                <li><a href="announcements.php">Announcements</a></li>
                <li><a href="admins.php">Admins</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <div class="action-bar">
                <h1>Manage Students</h1>
                <a href="?action=add" class="btn btn-primary">+ Add New Student</a>
            </div>

            <div class="filter-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name, email, or student ID...">
                <select id="statusFilter" class="form-control" style="width: auto;">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="card">
                <table class="grades-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Student ID</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($students)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['course']); ?></td>
                            <td><?php echo $row['year_level']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <a href="?action=edit&id=<?php echo $row['id']; ?>" style="color: #4299e1;">Edit</a>
                                <?php if (hasRole('super_admin')): ?>
                                <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                                   style="color: #f56565;"
                                   onclick="return confirm('Are you sure? This action cannot be undone.')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <script>
            document.getElementById('searchInput').addEventListener('keyup', filterTable);
            document.getElementById('statusFilter').addEventListener('change', filterTable);

            function filterTable() {
                let searchValue = document.getElementById('searchInput').value.toLowerCase();
                let statusValue = document.getElementById('statusFilter').value;
                let rows = document.querySelectorAll('#studentsTable tbody tr');
                
                rows.forEach(row => {
                    let text = row.textContent.toLowerCase();
                    let statusCell = row.cells[6].textContent.trim().toLowerCase();
                    
                    let matchesSearch = text.includes(searchValue);
                    let matchesStatus = statusValue === 'all' || 
                                       (statusValue === 'active' && statusCell.includes('active')) ||
                                       (statusValue === 'inactive' && statusCell.includes('inactive'));
                    
                    row.style.display = matchesSearch && matchesStatus ? '' : 'none';
                });
            }
            </script>

        <?php elseif ($action == 'add' || $action == 'edit'): ?>
            <h1><?php echo $action == 'add' ? 'Add New Student' : 'Edit Student'; ?></h1>
            
            <div class="form-container" style="max-width: 600px;">
                <form method="POST">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo $student ? htmlspecialchars($student['full_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $student ? htmlspecialchars($student['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_id">Student ID *</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" 
                               value="<?php echo $student ? htmlspecialchars($student['student_id']) : ''; ?>" required>
                        <?php if ($action == 'add'): ?>
                            <small>Default password: <strong>student123</strong></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="course">Course *</label>
                        <input type="text" class="form-control" id="course" name="course" 
                               value="<?php echo $student ? htmlspecialchars($student['course']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="year_level">Year Level *</label>
                        <select class="form-control" id="year_level" name="year_level" required>
                            <option value="">Select Year</option>
                            <option value="1" <?php echo ($student && $student['year_level'] == 1) ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo ($student && $student['year_level'] == 2) ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo ($student && $student['year_level'] == 3) ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo ($student && $student['year_level'] == 4) ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" <?php echo (!$student || $student['is_active']) ? 'checked' : ''; ?>>
                            Active Account
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'add' ? 'Add Student' : 'Update Student'; ?>
                        </button>
                        <a href="students.php" class="btn" style="background: #718096; color: white;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>