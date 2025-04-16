<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gym";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process login request
if (isset($_POST["submit"])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // Check credentials against admin table
        $sql = "SELECT id_admin, username, password, nama FROM admin WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Langsung bandingkan password tanpa menggunakan password_verify
            if ($password === $row['password']) {
                // Set admin session variables
                $_SESSION['admin_id'] = $row['id_admin'];
                $_SESSION['admin_nama'] = $row['nama'];
                $_SESSION['admin_logged_in'] = true;
                
                // Update last login time
                $update_login = "UPDATE admin SET last_login = NOW() WHERE id_admin = ?";
                $stmt = $conn->prepare($update_login);
                $stmt->bind_param("i", $row['id_admin']);
                $stmt->execute();
                
                // Redirect to admin dashboard
                header("Location: admin-dashboard.php");
                exit();
            } else {
                $_SESSION['admin_login_error'] = "Password tidak valid!";
            }
        } else {
            $_SESSION['admin_login_error'] = "Username tidak ditemukan atau Anda bukan Admin!";
        }
        $stmt->close();
    } else {
        $_SESSION['admin_login_error'] = "Username dan password wajib diisi!";
    }
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - FREEDOM GYM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-image: url('assets/img/hero/h1_hero.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-color: rgba(0, 0, 0, 0.9);
            background-blend-mode: lighten;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
            padding: 30px;
        }
        .btn-primary {
            background-color: #dc3545;
            border-color: #dc3545;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        .btn-back {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: rgba(255, 255, 255, 0.7);
            border: 1px solid #dc3545;
            color: #dc3545;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background-color: #dc3545;
            color: white;
        }
        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Tombol Kembali -->
        <a href="index.php" class="btn btn-back">
            <i class="bi bi-arrow-left fs-4"></i>
        </a>
        <div class="text-center mb-4">
            <h2 class="fw-bold">Login Admin</h2>
            <p class="text-muted">FREEDOM GYM</p>
        </div>
        
        <?php
        // Display error message if any
        if (isset($_SESSION['admin_login_error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['admin_login_error'] . '</div>';
            unset($_SESSION['admin_login_error']);
        }
        ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="showPassword">
                <label class="form-check-label" for="showPassword">Show Password</label>
            </div>
            <div class="d-grid">
                <button type="submit" name="submit" class="btn btn-primary btn-lg">Login</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <a href="index.php" class="text-muted text-decoration-none">Kembali ke Beranda</a>
        </div>
    </div>

    <script>
        document.getElementById('showPassword').addEventListener('change', function() {
            const passwordInput = document.getElementById('password');
            passwordInput.type = this.checked ? 'text' : 'password';
        });
    </script>
</body>
</html>