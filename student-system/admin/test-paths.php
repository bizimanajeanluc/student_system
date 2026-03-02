<?php
echo "<h1>Path Test</h1>";

echo "<h2>Current File:</h2>";
echo "__FILE__: " . __FILE__ . "<br>";
echo "__DIR__: " . __DIR__ . "<br>";

echo "<h2>Testing Paths:</h2>";
$paths_to_test = [
    '../includes/config.php',
    __DIR__ . '/../includes/config.php',
    '../includes/admin_auth.php',
    __DIR__ . '/../includes/admin_auth.php'
];

foreach ($paths_to_test as $path) {
    echo "<br>Testing: $path<br>";
    if (file_exists($path)) {
        echo "✅ EXISTS<br>";
    } else {
        echo "❌ NOT FOUND<br>";
    }
}

echo "<h2>Includes Directory Contents:</h2>";
$includes_path = __DIR__ . '/../includes';
if (is_dir($includes_path)) {
    $files = scandir($includes_path);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "Includes directory not found!";
}
?>