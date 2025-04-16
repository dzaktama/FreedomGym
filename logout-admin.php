<?php
// Start session
session_start();

// Unset all session variables related to admin
$_SESSION = array_filter($_SESSION, function($key) {
    return strpos($key, 'admin_') !== 0;
}, ARRAY_FILTER_USE_KEY);

// Redirect to login page
header("Location: admin-login.php");
exit();
?>