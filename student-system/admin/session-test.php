<?php
session_start();

echo "<h1>📊 Session Test</h1>";

echo "<h2>Current Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Session Functions:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Status: ";
switch (session_status()) {
    case PHP_SESSION_DISABLED:
        echo "Disabled";
        break;
    case PHP_SESSION_NONE:
        echo "None (no session)";
        break;
    case PHP_SESSION_ACTIVE:
        echo "Active";
        break;
}
echo "<br>";

echo "<h2>Actions:</h2>";
echo "<ul>";
echo "<li><a href='?action=set'>Set Test Session</a></li>";
echo "<li><a href='?action=unset'>Unset Session</a></li>";
echo "<li><a href='?action=destroy'>Destroy Session</a></li>";
echo "</ul>";

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'set') {
        $_SESSION['test'] = 'This is a test';
        $_SESSION['time'] = date('Y-m-d H:i:s');
        echo "<p style='color:green'>✅ Test session set!</p>";
    } elseif ($_GET['action'] == 'unset') {
        unset($_SESSION['test']);
        unset($_SESSION['time']);
        echo "<p style='color:orange'>⚠️ Test session unset!</p>";
    } elseif ($_GET['action'] == 'destroy') {
        session_destroy();
        echo "<p style='color:red'>⚠️ Session destroyed!</p>";
        echo "<meta http-equiv='refresh' content='2'>";
    }
}
?>