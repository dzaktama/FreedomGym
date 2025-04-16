<?php
// Start session
session_start();

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root"; // Change with your database username
$password = ""; // Change with your database password
$dbname = "gym";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION["login_error"] = "Username dan password wajib diisi!";
        header("Location: login.php");
        exit();
    }
    
    // Check user in membership table
    $sql = "SELECT id, nama, email, username, password FROM membership WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, start a new session
            
            // Store data in session variables
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["nama"] = $user["nama"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["logged_in"] = true;
            
            // Redirect to index page instead of dashboard
            header("Location: index.php");
            exit();
        } else {
            // Password is incorrect
            $_SESSION["login_error"] = "Username atau password salah!";
            header("Location: login.php");
            exit();
        }
    } else {
        // Username not found
        $_SESSION["login_error"] = "Username atau password salah!";
        header("Location: login.php");
        exit();
    }
    
    $stmt->close();
}

$conn->close();
?>