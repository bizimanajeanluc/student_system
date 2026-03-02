<?php
// No session_start() here - it's already in config.php
require_once 'config.php';

// Get student's enrolled courses
function getStudentCourses($student_id) {
    global $conn;
    $courses = [];
    
    $query = "SELECT c.*, e.status, e.enrollment_date 
              FROM courses c 
              JOIN enrollments e ON c.id = e.course_id 
              WHERE e.student_id = ? AND e.status = 'active'
              ORDER BY c.course_code";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return $courses;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $courses[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $courses;
}

// Get all available courses
function getAllCourses() {
    global $conn;
    $courses = [];
    
    $query = "SELECT * FROM courses ORDER BY course_code";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return $courses;
    }
    
    while ($row = mysqli_fetch_assoc($result)) {
        $courses[] = $row;
    }
    
    return $courses;
}

// Get single course details
function getCourse($course_id) {
    global $conn;
    
    $query = "SELECT * FROM courses WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

// Check if student is enrolled in a course
function isEnrolled($student_id, $course_id) {
    global $conn;
    
    $query = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0;
}

// Enroll student in course
function enrollStudent($student_id, $course_id) {
    global $conn;
    
    $query = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    return mysqli_stmt_execute($stmt);
}

// Get course assignments
function getCourseAssignments($course_id) {
    global $conn;
    $assignments = [];
    
    $query = "SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return $assignments;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $assignments[] = $row;
    }
    
    return $assignments;
}

// Get student's grades
function getStudentGrades($student_id) {
    global $conn;
    $grades = [];
    
    $query = "SELECT g.*, a.title as assignment_title, a.max_score, a.assignment_type,
              c.course_code, c.course_name, c.credits
              FROM grades g
              JOIN assignments a ON g.assignment_id = a.id
              JOIN courses c ON a.course_id = c.id
              WHERE g.student_id = ?
              ORDER BY g.graded_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return $grades;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $grades[] = $row;
    }
    
    return $grades;
}

// Get student's uploads
function getStudentUploads($student_id) {
    global $conn;
    $uploads = [];
    
    $query = "SELECT u.*, c.course_code, c.course_name, a.title as assignment_title
              FROM uploads u
              LEFT JOIN courses c ON u.course_id = c.id
              LEFT JOIN assignments a ON u.assignment_id = a.id
              WHERE u.student_id = ?
              ORDER BY u.upload_date DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return $uploads;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $uploads[] = $row;
    }
    
    return $uploads;
}

// Upload file
function uploadFile($student_id, $course_id, $assignment_id, $file) {
    global $conn;
    
    $target_dir = "assets/uploads/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($file['name']);
    $target_file = $target_dir . $file_name;
    $file_size = $file['size'];
    $file_type = $file['type'];
    
    // Validate file size (max 5MB)
    if ($file_size > 5 * 1024 * 1024) {
        return false;
    }
    
    // Validate file type
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 
                      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                      'text/plain'];
    if (!in_array($file_type, $allowed_types)) {
        return false;
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $query = "INSERT INTO uploads (student_id, course_id, assignment_id, file_name, file_path, file_size, file_type) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "iisssis", 
            $student_id, 
            $course_id, 
            $assignment_id, 
            $file_name, 
            $target_file, 
            $file_size, 
            $file_type
        );
        
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    return false;
}

// Delete upload
function deleteUpload($upload_id, $student_id) {
    global $conn;
    
    // Get file path first
    $query = "SELECT file_path FROM uploads WHERE id = ? AND student_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $upload_id, $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Delete file from server
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
        
        // Delete from database
        $delete_query = "DELETE FROM uploads WHERE id = ? AND student_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        if ($delete_stmt === false) {
            return false;
        }
        
        mysqli_stmt_bind_param($delete_stmt, "ii", $upload_id, $student_id);
        $result = mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
        return $result;
    }
    
    return false;
}

// Get announcements
function getAnnouncements($limit = 10) {
    global $conn;
    $announcements = [];
    
    $query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return $announcements;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
    
    return $announcements;
}

// Calculate GPA
function calculateGPA($student_id) {
    global $conn;
    
    $query = "SELECT g.score, a.max_score, c.credits
              FROM grades g
              JOIN assignments a ON g.assignment_id = a.id
              JOIN courses c ON a.course_id = c.id
              WHERE g.student_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return 0;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $total_points = 0;
    $total_credits = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $percentage = ($row['score'] / $row['max_score']) * 100;
        
        // Convert percentage to grade points (4.0 scale)
        if ($percentage >= 90) $grade_points = 4.0;
        elseif ($percentage >= 80) $grade_points = 3.0;
        elseif ($percentage >= 70) $grade_points = 2.0;
        elseif ($percentage >= 60) $grade_points = 1.0;
        else $grade_points = 0.0;
        
        $total_points += $grade_points * $row['credits'];
        $total_credits += $row['credits'];
    }
    
    return $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;
}

// Format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Get upcoming assignments for a student
function getUpcomingAssignments($student_id, $limit = 5) {
    global $conn;
    $assignments = [];
    
    $query = "SELECT a.*, c.course_name, c.course_code 
              FROM assignments a
              JOIN courses c ON a.course_id = c.id
              JOIN enrollments e ON c.id = e.course_id
              WHERE e.student_id = ? 
              AND e.status = 'active'
              AND a.due_date > NOW()
              ORDER BY a.due_date
              LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return $assignments;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $assignments[] = $row;
    }
    
    return $assignments;
}

// Get recent grades for a student
function getRecentGrades($student_id, $limit = 5) {
    global $conn;
    $grades = [];
    
    $query = "SELECT g.*, a.title as assignment_title, a.max_score, c.course_code
              FROM grades g
              JOIN assignments a ON g.assignment_id = a.id
              JOIN courses c ON a.course_id = c.id
              WHERE g.student_id = ?
              ORDER BY g.graded_at DESC
              LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return $grades;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $grades[] = $row;
    }
    
    return $grades;
}
?>