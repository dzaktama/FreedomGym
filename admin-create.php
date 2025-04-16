<?php
// Include auth_check.php
require_once 'auth_check.php';

// Verifikasi admin sudah login
isAdminLoggedIn();

// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gym";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Cek apakah admin saat ini adalah super_admin
$admin_id = $_SESSION['admin_id'];
$check_level = "SELECT level FROM admin WHERE id_admin = ?";
$stmt = $conn->prepare($check_level);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();

// Jika bukan super_admin, redirect
if ($admin_data['level'] !== 'super_admin') {
    $_SESSION['admin_message'] = "Anda tidak memiliki akses untuk membuat admin baru!";
    $_SESSION['admin_message_type'] = "danger";
    header("Location: admin-dashboard.php");
    exit();
}

// Proses form jika disubmit
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_telp = trim($_POST['no_telp']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $level = trim($_POST['level']);
    
    // Validasi input
    if (empty($username) || empty($nama) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Semua field wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $error_message = "Password dan konfirmasi password tidak sama!";
    } elseif (strlen($password) < 8) {
        $error_message = "Password minimal 8 karakter!";
    } else {
        // Cek apakah username atau email sudah ada
        $check_sql = "SELECT id_admin FROM admin WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Username atau email sudah digunakan!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert data admin baru
            $insert_sql = "INSERT INTO admin (username, password, nama, email, no_telp, level) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssss", $username, $hashed_password, $nama, $email, $no_telp, $level);
            
            if ($stmt->execute()) {
                $success_message = "Admin baru berhasil dibuat!";
            } else {
                $error_message = "Gagal membuat admin baru: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Admin - FREEDOM GYM</title>
    <!-- Bootstrap CSS dan styling lainnya sama seperti halaman admin lain -->
</head>
<body>
    <!-- Sidebar sama seperti halaman admin lain -->
    
    <!-- Main Content -->
    <div class="content">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Tambah Admin Baru</h3>
            <a href="admin-dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Form Tambah Admin</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="no_telp" class="form-label">Nomor Telepon</label>
                        <input type="text" class="form-control" id="no_telp" name="no_telp">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="text-muted">Minimal 8 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="level" class="form-label">Level</label>
                        <select class="form-select" id="level" name="level" required>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Tambah Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>