<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h1>Testing Functions</h1>";

// Test if functions exist
$functions = [
    'getStudentCourses',
    'getAllCourses',
    'getCourse',
    'isEnrolled',
    'enrollStudent',
    'getCourseAssignments',
    'getStudentGrades',
    'getStudentUploads',
    'uploadFile',
    'deleteUpload',
    'getAnnouncements',
    'calculateGPA',
    'formatFileSize',
    'getUpcomingAssignments',
    'getRecentGrades'
];

echo "<h2>Function Availability:</h2>";
echo "<ul>";
foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "<li style='color: green;'>✅ $function() exists</li>";
    } else {
        echo "<li style='color: red;'>❌ $function() does NOT exist</li>";
    }
}
echo "</ul>";

// Test database connection
echo "<h2>Database Connection:</h2>";
if ($conn) {
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Check if students table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'students'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✅ Students table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Students table does not exist</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Database not connected</p>";
}

// Test with a sample student ID (if any exists)
$result = mysqli_query($conn, "SELECT id FROM students LIMIT 1");
if (mysqli_num_rows($result) > 0) {
    $student = mysqli_fetch_assoc($result);
    $test_id = $student['id'];
    
    echo "<h2>Testing getStudentCourses with ID $test_id:</h2>";
    $courses = getStudentCourses($test_id);
    echo "<pre>";
    print_r($courses);
    echo "</pre>";
}
?>