<?php
require_once 'includes/config.php'; // Session already started here

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>