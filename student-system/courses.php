<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$student_id = $_SESSION['student_id'];

// Get all courses
$all_courses = getAllCourses();

// Get enrolled courses
$enrolled_courses = getStudentCourses($student_id);
$enrolled_ids = array_column($enrolled_courses, 'id');

// Handle messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .course-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .course-header {
            background: linear-gradient(135deg, #4299e1 0%, #667eea 100%);
            color: white;
            padding: 20px;
            position: relative;
        }
        .course-code {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .course-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .course-credits {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .course-body {
            padding: 20px;
        }
        .course-info {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            color: #718096;
            font-size: 14px;
        }
        .course-description {
            color: #4a5568;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .course-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        .btn-enroll {
            background: #48bb78;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .btn-enroll:hover {
            background: #38a169;
        }
        .btn-enrolled {
            background: #c6f6d5;
            color: #22543d;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #9ae6b4;
        }
        .search-bar {
            display: flex;
            gap: 10px;
            margin: 30px 0;
        }
        .search-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            font-size: 16px;
        }
        .filter-select {
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            background: white;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .message-error {
            background: #fed7d7;
            color: #9b2c2c;
            border: 1px solid #feb2b2;
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
                <li><a href="courses.php" class="active">Browse Courses</a></li>
                <li><a href="my-courses.php">My Courses</a></li>
                <li><a href="grades.php">My Grades</a></li>
                <li><a href="uploads.php">Uploads</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Browse Available Courses</h1>
        
        <?php if ($success_message): ?>
            <div class="message message-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message message-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="Search courses by name, code, or instructor...">
            <select id="deptFilter" class="filter-select">
                <option value="all">All Departments</option>
                <option value="Computer Science">Computer Science</option>
                <option value="Mathematics">Mathematics</option>
                <option value="Physics">Physics</option>
            </select>
        </div>

        <div class="courses-grid" id="coursesGrid">
            <?php foreach ($all_courses as $course): 
                $is_enrolled = in_array($course['id'], $enrolled_ids);
            ?>
                <div class="course-card" 
                     data-name="<?php echo strtolower($course['course_name']); ?>"
                     data-code="<?php echo strtolower($course['course_code']); ?>"
                     data-instructor="<?php echo strtolower($course['instructor']); ?>"
                     data-dept="<?php echo strtolower($course['department']); ?>">
                    
                    <div class="course-header">
                        <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                        <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                        <div class="course-credits"><?php echo $course['credits']; ?> Credits</div>
                    </div>
                    
                    <div class="course-body">
                        <div class="course-info">
                            <span>👨‍🏫 <?php echo htmlspecialchars($course['instructor']); ?></span>
                            <span>📚 <?php echo htmlspecialchars($course['department']); ?></span>
                        </div>
                        
                        <div class="course-description">
                            <?php echo htmlspecialchars(substr($course['description'], 0, 150)) . '...'; ?>
                        </div>
                        
                        <div class="course-footer">
                            <span>📅 <?php echo $course['semester']; ?> <?php echo $course['academic_year']; ?></span>
                            
                            <?php if ($is_enrolled): ?>
                                <span class="btn-enrolled">✓ Enrolled</span>
                            <?php else: ?>
                                <form action="enroll.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" class="btn-enroll">Enroll Now</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    document.getElementById('searchInput').addEventListener('keyup', filterCourses);
    document.getElementById('deptFilter').addEventListener('change', filterCourses);

    function filterCourses() {
        let searchValue = document.getElementById('searchInput').value.toLowerCase();
        let deptValue = document.getElementById('deptFilter').value.toLowerCase();
        let courses = document.querySelectorAll('.course-card');
        
        courses.forEach(course => {
            let name = course.dataset.name;
            let code = course.dataset.code;
            let instructor = course.dataset.instructor;
            let dept = course.dataset.dept;
            
            let matchesSearch = name.includes(searchValue) || 
                               code.includes(searchValue) || 
                               instructor.includes(searchValue);
            let matchesDept = deptValue === 'all' || dept === deptValue;
            
            course.style.display = matchesSearch && matchesDept ? 'block' : 'none';
        });
    }
    </script>

    <footer class="footer">
        <p>&copy; 2024 Student Portal. All rights reserved.</p>
    </footer>
</body>
</html>