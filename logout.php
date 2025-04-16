<?php
// Start session
session_start();

// Unset variables
$_SESSION = array();

// menyelesaikan session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// destroy the session
session_destroy();

// Redirect to index page
header("Location: index.php");
exit();
?>