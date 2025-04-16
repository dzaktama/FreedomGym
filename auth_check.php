<?php
// auth_check.php - File ini berfungsi sebagai middleware keamanan

// Start session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Function untuk memeriksa apakah user sudah login
 * Jika belum login, redirect ke halaman login
 * 
 * @return bool true jika user sudah login, false jika belum
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header("Location: login.php");
        exit();
        return false;
    }
    return true;
}

/**
 * Function untuk memeriksa apakah user adalah admin
 * Jika bukan admin, redirect ke halaman dashboard user
 * 
 * @return bool true jika user adalah admin, false jika bukan
 */
function isAdmin() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header("Location: dashboard.php");
        exit();
        return false;
    }
    return true;
}

/**
 * Function untuk memeriksa apakah pengunjung adalah admin yang sudah login di sistem admin
 * Jika bukan admin, redirect ke halaman login admin
 * 
 * @return bool true jika pengunjung adalah admin yang sudah login, false jika bukan
 */
function isAdminLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: admin-login.php");
        exit();
        return false;
    }
    return true;
}

/**
 * Function untuk redirect user yang sudah login ke dashboard
 * Berguna untuk halaman login dan register agar user yang sudah login
 * tidak dapat mengakses halaman tersebut lagi
 */
function redirectIfLoggedIn() {
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        header("Location: dashboard.php");
        exit();
    }
}

/**
 * Function untuk logout
 * Menghapus semua session dan redirect ke halaman login
 */
function logout() {
    // Hapus semua data session
    $_SESSION = array();
    
    // Hapus cookie session jika ada
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect ke halaman login
    header("Location: login.php");
    exit();
}

/**
 * Function untuk logout admin
 * Menghapus semua session dan redirect ke halaman login admin
 */
function logoutAdmin() {
    // Hapus semua data session
    $_SESSION = array();
    
    // Hapus cookie session jika ada
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect ke halaman login admin
    header("Location: admin-login.php");
    exit();
}