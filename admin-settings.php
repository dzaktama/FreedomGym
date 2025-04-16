<?php
// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in'])) {
    // If not logged in, redirect to login page
    header("Location: admin-login.php");
    exit();
}

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

// Update admin profile
if (isset($_POST['update_profile'])) {
    $admin_id = $_SESSION['admin_id'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $no_telp = $_POST['no_telp'];
    
    // Check if email is already taken by another admin
    $check_email = "SELECT id_admin FROM admin WHERE email = ? AND id_admin != ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("si", $email, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['admin_message'] = "Email sudah digunakan oleh admin lain!";
        $_SESSION['admin_message_type'] = "danger";
    } else {
        // Update admin profile
        $update_sql = "UPDATE admin SET nama = ?, email = ?, no_telp = ? WHERE id_admin = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $nama, $email, $no_telp, $admin_id);
        
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Profil berhasil diperbarui!";
            $_SESSION['admin_message_type'] = "success";
            
            // Update session data
            $_SESSION['admin_nama'] = $nama;
        } else {
            $_SESSION['admin_message'] = "Gagal memperbarui profil: " . $conn->error;
            $_SESSION['admin_message_type'] = "danger";
        }
    }
}

// Change password
if (isset($_POST['change_password'])) {
    $admin_id = $_SESSION['admin_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current hashed password
    $get_pass = "SELECT password FROM admin WHERE id_admin = ?";
    $stmt = $conn->prepare($get_pass);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Verify current password
    if (password_verify($current_password, $row['password'])) {
        // Check if new password matches confirmation
        if ($new_password === $confirm_password) {
            // Check password strength
            if (strlen($new_password) < 8) {
                $_SESSION['password_message'] = "Password harus minimal 8 karakter!";
                $_SESSION['password_message_type'] = "danger";
            } else {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_pass = "UPDATE admin SET password = ? WHERE id_admin = ?";
                $stmt = $conn->prepare($update_pass);
                $stmt->bind_param("si", $hashed_password, $admin_id);
                
                if ($stmt->execute()) {$_SESSION['password_message'] = "Password berhasil diubah!";
                    $_SESSION['password_message_type'] = "success";
                } else {
                    $_SESSION['password_message'] = "Gagal mengubah password: " . $conn->error;
                    $_SESSION['password_message_type'] = "danger";
                }
            }
        } else {
            $_SESSION['password_message'] = "Password baru dan konfirmasi tidak cocok!";
            $_SESSION['password_message_type'] = "danger";
        }
    } else {
        $_SESSION['password_message'] = "Password saat ini tidak valid!";
        $_SESSION['password_message_type'] = "danger";
    }
}

// System settings
if (isset($_POST['update_system'])) {
    // Create settings table if it doesn't exist
    $create_settings_table = "CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(50) NOT NULL,
        `setting_value` text NOT NULL,
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $conn->query($create_settings_table);
    
    // Get settings from form
    $gym_name = $_POST['gym_name'];
    $gym_address = $_POST['gym_address'];
    $gym_phone = $_POST['gym_phone'];
    $gym_email = $_POST['gym_email'];
    $working_hours = $_POST['working_hours'];
    
    // Update settings
    $settings = [
        'gym_name' => $gym_name,
        'gym_address' => $gym_address,
        'gym_phone' => $gym_phone,
        'gym_email' => $gym_email,
        'working_hours' => $working_hours
    ];
    
    foreach ($settings as $key => $value) {
        // Check if setting exists
        $check_sql = "SELECT id FROM system_settings WHERE setting_key = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing setting
            $update_sql = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
        } else {
            // Insert new setting
            $insert_sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }
    }
    
    $_SESSION['system_message'] = "Pengaturan sistem berhasil diperbarui!";
    $_SESSION['system_message_type'] = "success";
}

// Get admin information
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM admin WHERE id_admin = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();

// Get system settings
$settings = [
    'gym_name' => 'FREEDOM GYM',
    'gym_address' => 'Jl. Merdeka No. 123, Yogyakarta',
    'gym_phone' => '0812-3456-7890',
    'gym_email' => 'info@freedomgym.com',
    'working_hours' => 'Senin - Minggu: 06:00 - 22:00'
];

// Check if settings table exists
$check_settings_table = $conn->query("SHOW TABLES LIKE 'system_settings'");
if ($check_settings_table->num_rows > 0) {
    // Get settings from database
    $get_settings = "SELECT setting_key, setting_value FROM system_settings";
    $settings_result = $conn->query($get_settings);
    
    if ($settings_result->num_rows > 0) {
        while ($row = $settings_result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - FREEDOM GYM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f6f9;
            min-height: 100vh;
        }
        .sidebar {
            background-color: #212529;
            color: white;
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            padding-top: 20px;
            position: fixed;
            z-index: 100;
        }
        .sidebar-header {
            padding: 10px 20px;
            border-bottom: 1px solid #2c3034;
            margin-bottom: 20px;
        }
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        .sidebar-menu a {
            color: #adb5bd;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar-menu a i {
            margin-right: 10px;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .page-header {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 0;
        }
        .nav-tabs .nav-link.active {
            color: #dc3545;
            border-bottom: 2px solid #dc3545;
            background-color: transparent;
        }
        .nav-tabs .nav-link:hover {
            border-color: transparent;
        }
        .settings-section {
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0">FREEDOM GYM</h4>
            <p class="text-muted mb-0">Admin Panel</p>
        </div>
        <ul class="sidebar-menu">
    <li><a href="admin-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
    <li><a href="admin-members.php"><i class="bi bi-people"></i> Kelola Anggota</a></li>
    <li><a href="admin-payments.php"><i class="bi bi-credit-card"></i> Pembayaran</a></li>
    <li><a href="admin-classes.php"><i class="bi bi-calendar-check"></i> Jadwal Kelas</a></li>
    <li><a href="admin-bookings.php"><i class="bi bi-bookmark-check"></i> Pemesanan Kelas</a></li>
    <li><a href="admin-reports.php"><i class="bi bi-clipboard-data"></i> Laporan</a></li>
    <li><a href="admin-settings.php"><i class="bi bi-gear"></i> Pengaturan</a></li>
    <li><a href="logout-admin.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
</ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="page-header">
            <h3 class="mb-0">Pengaturan</h3>
        </div>

        <div class="card">
            <div class="card-header bg-white p-0">
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                            <i class="bi bi-person me-2"></i> Profil Admin
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                            <i class="bi bi-lock me-2"></i> Ubah Password
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab" aria-controls="system" aria-selected="false">
                            <i class="bi bi-gear me-2"></i> Pengaturan Sistem
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="settingsTabsContent">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <div class="settings-section">
                            <h5 class="mb-4">Informasi Profil Admin</h5>
                            
                            <?php if (isset($_SESSION['admin_message'])): ?>
                                <div class="alert alert-<?php echo $_SESSION['admin_message_type']; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $_SESSION['admin_message']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php 
                                    unset($_SESSION['admin_message']);
                                    unset($_SESSION['admin_message_type']);
                                ?>
                            <?php endif; ?>
                            
                            <form action="" method="POST">
                                <div class="row mb-3">
                                    <label for="username" class="col-sm-3 col-form-label">Username</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($admin_data['username']); ?>" readonly>
                                        <small class="text-muted">Username tidak dapat diubah</small>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="nama" class="col-sm-3 col-form-label">Nama</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($admin_data['nama']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="email" class="col-sm-3 col-form-label">Email</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="no_telp" class="col-sm-3 col-form-label">Nomor Telepon</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($admin_data['no_telp']); ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="level" class="col-sm-3 col-form-label">Level Akses</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="level" value="<?php echo ucfirst(str_replace('_', ' ', $admin_data['level'])); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="last_login" class="col-sm-3 col-form-label">Login Terakhir</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="last_login" value="<?php echo $admin_data['last_login'] ? date('d M Y H:i', strtotime($admin_data['last_login'])) : 'Belum pernah login'; ?>" readonly>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Password Tab -->
                    <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                        <div class="settings-section">
                            <h5 class="mb-4">Ubah Password</h5>
                            
                            <?php if (isset($_SESSION['password_message'])): ?>
                                <div class="alert alert-<?php echo $_SESSION['password_message_type']; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $_SESSION['password_message']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php 
                                    unset($_SESSION['password_message']);
                                    unset($_SESSION['password_message_type']);
                                ?>
                            <?php endif; ?>
                            
                            <form action="" method="POST">
                                <div class="row mb-3">
                                    <label for="current_password" class="col-sm-4 col-form-label">Password Saat Ini</label>
                                    <div class="col-sm-8">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="new_password" class="col-sm-4 col-form-label">Password Baru</label>
                                    <div class="col-sm-8">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <small class="text-muted">Password minimal 8 karakter</small>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="confirm_password" class="col-sm-4 col-form-label">Konfirmasi Password</label>
                                    <div class="col-sm-8">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="bi bi-key"></i> Ubah Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- System Settings Tab -->
                    <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                        <div class="settings-section">
                            <h5 class="mb-4">Pengaturan Sistem</h5>
                            
                            <?php if (isset($_SESSION['system_message'])): ?>
                                <div class="alert alert-<?php echo $_SESSION['system_message_type']; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $_SESSION['system_message']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php 
                                    unset($_SESSION['system_message']);
                                    unset($_SESSION['system_message_type']);
                                ?>
                            <?php endif; ?>
                            
                            <form action="" method="POST">
                                <div class="row mb-3">
                                    <label for="gym_name" class="col-sm-3 col-form-label">Nama Gym</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="gym_name" name="gym_name" value="<?php echo htmlspecialchars($settings['gym_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="gym_address" class="col-sm-3 col-form-label">Alamat</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="gym_address" name="gym_address" rows="3" required><?php echo htmlspecialchars($settings['gym_address']); ?></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="gym_phone" class="col-sm-3 col-form-label">Nomor Telepon</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="gym_phone" name="gym_phone" value="<?php echo htmlspecialchars($settings['gym_phone']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="gym_email" class="col-sm-3 col-form-label">Email</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="gym_email" name="gym_email" value="<?php echo htmlspecialchars($settings['gym_email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="working_hours" class="col-sm-3 col-form-label">Jam Operasional</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="working_hours" name="working_hours" value="<?php echo htmlspecialchars($settings['working_hours']); ?>" required>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="update_system" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Pengaturan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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