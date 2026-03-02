<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$student_id = $_SESSION['student_id'];
$message = '';
$error = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $assignment_id = isset($_POST['assignment_id']) && !empty($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : null;
    
    if ($course_id == 0) {
        $error = 'Please select a course.';
    } elseif ($_FILES['file']['error'] != 0) {
        $error = 'Error uploading file. Please try again.';
    } else {
        $file = [
            'name' => $_FILES['file']['name'],
            'type' => $_FILES['file']['type'],
            'tmp_name' => $_FILES['file']['tmp_name'],
            'error' => $_FILES['file']['error'],
            'size' => $_FILES['file']['size']
        ];
        
        if (uploadFile($student_id, $course_id, $assignment_id, $file)) {
            $message = 'File uploaded successfully!';
        } else {
            $error = 'Failed to upload file. Make sure it\'s under 5MB and a valid file type.';
        }
    }
}

// Get student's uploads
$uploads = getStudentUploads($student_id);

// Get enrolled courses for dropdown
$enrolled_courses = getStudentCourses($student_id);

// Get course assignments if course selected
$selected_course = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$assignments = [];
if ($selected_course > 0) {
    $assignments = getCourseAssignments($selected_course);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Uploads - Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .upload-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .upload-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .uploads-list {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .file-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .file-icon {
            font-size: 24px;
        }
        .file-details {
            flex: 1;
        }
        .file-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .file-meta {
            font-size: 12px;
            color: #718096;
        }
        .file-actions {
            display: flex;
            gap: 10px;
        }
        .btn-download {
            background: #4299e1;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-delete {
            background: #f56565;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            margin: 20px 0;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        .upload-area:hover {
            border-color: #4299e1;
        }
        .upload-area i {
            font-size: 48px;
            color: #4299e1;
            margin-bottom: 15px;
            display: block;
        }
        #fileInput {
            display: none;
        }
        .selected-file {
            background: #f7fafc;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">StudentPortal</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="courses.php">Browse Courses</a></li>
                <li><a href="my-courses.php">My Courses</a></li>
                <li><a href="grades.php">My Grades</a></li>
                <li><a href="uploads.php" class="active">Uploads</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>File Uploads</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="upload-container">
            <!-- Upload Form -->
            <div class="upload-form">
                <h2>Upload New File</h2>
                
                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="course">Select Course</label>
                        <select class="form-control" id="course" name="course_id" required onchange="loadAssignments(this.value)">
                            <option value="">Choose a course...</option>
                            <?php foreach ($enrolled_courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assignment">Assignment (Optional)</label>
                        <select class="form-control" id="assignment" name="assignment_id">
                            <option value="">General upload (no specific assignment)</option>
                        </select>
                    </div>

                    <div class="upload-area" id="uploadArea">
                        <i>📁</i>
                        <h3>Click or drag file to upload</h3>
                        <p style="color: #718096;">Supported formats: PDF, DOC, DOCX, JPG, PNG (Max 5MB)</p>
                        <input type="file" id="fileInput" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    </div>

                    <div id="selectedFile" class="selected-file" style="display: none;">
                        <div>
                            <strong id="fileName"></strong>
                            <br>
                            <small id="fileSize"></small>
                        </div>
                        <button type="button" onclick="clearFile()" style="background: none; border: none; color: #f56565; cursor: pointer;">✕ Remove</button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px;" id="submitBtn">Upload File</button>
                </form>
            </div>

            <!-- Uploads List -->
            <div class="uploads-list">
                <h2>My Uploads</h2>
                
                <?php if (empty($uploads)): ?>
                    <p style="text-align: center; color: #718096; padding: 40px;">
                        No files uploaded yet.
                    </p>
                <?php else: ?>
                    <?php foreach ($uploads as $upload): ?>
                        <div class="file-item">
                            <div class="file-info">
                                <span class="file-icon">
                                    <?php
                                    $ext = strtolower(pathinfo($upload['file_name'], PATHINFO_EXTENSION));
                                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) echo '🖼️';
                                    elseif ($ext == 'pdf') echo '📄';
                                    elseif (in_array($ext, ['doc', 'docx'])) echo '📝';
                                    else echo '📁';
                                    ?>
                                </span>
                                <div class="file-details">
                                    <div class="file-name"><?php echo htmlspecialchars($upload['file_name']); ?></div>
                                    <div class="file-meta">
                                        <?php echo htmlspecialchars($upload['course_code']); ?> |
                                        <?php echo $upload['assignment_title'] ? htmlspecialchars($upload['assignment_title']) : 'General'; ?> |
                                        <?php echo formatFileSize($upload['file_size']); ?> |
                                        <?php echo date('M d, Y', strtotime($upload['upload_date'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="file-actions">
                                <a href="<?php echo $upload['file_path']; ?>" download class="btn-download">Download</a>
                                <a href="delete-upload.php?id=<?php echo $upload['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const selectedFileDiv = document.getElementById('selectedFile');
    const fileNameSpan = document.getElementById('fileName');
    const fileSizeSpan = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');

    // Click to upload
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#4299e1';
        uploadArea.style.background = '#ebf8ff';
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '#cbd5e0';
        uploadArea.style.background = 'white';
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#cbd5e0';
        uploadArea.style.background = 'white';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateFileInfo();
        }
    });

    // File input change
    fileInput.addEventListener('change', updateFileInfo);

    function updateFileInfo() {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            
            // Check file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                fileInput.value = '';
                return;
            }
            
            fileNameSpan.textContent = file.name;
            fileSizeSpan.textContent = formatFileSize(file.size);
            selectedFileDiv.style.display = 'flex';
            uploadArea.style.display = 'none';
        }
    }

    function clearFile() {
        fileInput.value = '';
        selectedFileDiv.style.display = 'none';
        uploadArea.style.display = 'block';
    }

    function formatFileSize(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        }
        return bytes + ' bytes';
    }

    function loadAssignments(courseId) {
        if (!courseId) return;
        
        fetch(`api/get-course-assignments.php?course_id=${courseId}`)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('assignment');
                select.innerHTML = '<option value="">General upload (no specific assignment)</option>';
                
                if (data.success && data.assignments.length > 0) {
                    data.assignments.forEach(assignment => {
                        const option = document.createElement('option');
                        option.value = assignment.id;
                        option.textContent = `${assignment.title} (Due: ${new Date(assignment.due_date).toLocaleDateString()})`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Form validation
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        if (!fileInput.files.length > 0) {
            e.preventDefault();
            alert('Please select a file to upload.');
        }
    });
    </script>

    <footer class="footer">
        <p>&copy; 2024 Student Portal. All rights reserved.</p>
    </footer>
</body>
</html>