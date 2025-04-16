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

// Define variables and set to empty values
$nama = $email = $nomorHP = $jenisKelamin = $tanggalLahir = $jenisMembership = $durasiMembership = $username = $password = $confirmPassword = "";
$errors = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $nomorHP = trim($_POST['nomorHP']);
    $jenisKelamin = trim($_POST['jenisKelamin']);
    $tanggalLahir = trim($_POST['tanggalLahir']);
    $jenisMembership = trim($_POST['jenisMembership']);
    $durasiMembership = trim($_POST['durasiMembership']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);
    $tanggalMulai = date('Y-m-d'); // Today as the default start date

    // Perform validation
    if (empty($nama)) {
        $errors[] = "Nama tidak boleh kosong";
    }

    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }

    if (empty($nomorHP)) {
        $errors[] = "Nomor HP tidak boleh kosong";
    }

    if (empty($jenisKelamin)) {
        $errors[] = "Jenis kelamin harus dipilih";
    }

    if (empty($tanggalLahir)) {
        $errors[] = "Tanggal lahir tidak boleh kosong";
    }

    if (empty($jenisMembership)) {
        $errors[] = "Jenis membership harus dipilih";
    }

    if (empty($durasiMembership)) {
        $errors[] = "Durasi membership harus dipilih";
    }

    if (empty($username)) {
        $errors[] = "Username tidak boleh kosong";
    } else {
        // Check if username already exists
        $check_username = "SELECT id FROM membership WHERE username = ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Username sudah digunakan";
        }
        $stmt->close();
    }

    if (empty($password)) {
        $errors[] = "Password tidak boleh kosong";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Password dan konfirmasi password tidak cocok";
    }

    // If no errors, insert data
    if (empty($errors)) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new member - menghapus created_at dari query
        $sql = "INSERT INTO membership (nama, email, nomorHP, jenisKelamin, tanggalLahir, jenisMembership, durasiMembership, tanggalMulai, username, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssss", $nama, $email, $nomorHP, $jenisKelamin, $tanggalLahir, $jenisMembership, $durasiMembership, $tanggalMulai, $username, $hashedPassword);

        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Anggota baru berhasil ditambahkan!";
            $_SESSION['admin_message_type'] = "success";
            header("Location: admin-members.php");
            exit();
        } else {
            $errors[] = "Terjadi kesalahan: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Anggota - FREEDOM GYM</title>
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
        .required::after {
            content: " *";
            color: #dc3545;
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
            <li><a href="admin-members.php" class="active"><i class="bi bi-people"></i> Kelola Anggota</a></li>
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
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Tambah Anggota Baru</h3>
            <a href="admin-members.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nama" class="form-label required">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($nama); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nomorHP" class="form-label required">Nomor HP</label>
                            <input type="text" class="form-control" id="nomorHP" name="nomorHP" value="<?php echo htmlspecialchars($nomorHP); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="jenisKelamin" class="form-label required">Jenis Kelamin</label>
                            <select class="form-select" id="jenisKelamin" name="jenisKelamin" required>
                                <option value="" disabled <?php echo empty($jenisKelamin) ? 'selected' : ''; ?>>Pilih Jenis Kelamin</option>
                                <option value="Laki-laki" <?php echo $jenisKelamin === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="Perempuan" <?php echo $jenisKelamin === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tanggalLahir" class="form-label required">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="tanggalLahir" name="tanggalLahir" value="<?php echo htmlspecialchars($tanggalLahir); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="jenisMembership" class="form-label required">Jenis Membership</label>
                            <select class="form-select" id="jenisMembership" name="jenisMembership" required>
                                <option value="" disabled <?php echo empty($jenisMembership) ? 'selected' : ''; ?>>Pilih Jenis Membership</option>
                                <option value="Individual" <?php echo $jenisMembership === 'Individual' ? 'selected' : ''; ?>>Individual</option>
                                <option value="Squad" <?php echo $jenisMembership === 'Squad' ? 'selected' : ''; ?>>Squad</option>
                                <option value="Visit" <?php echo $jenisMembership === 'Visit' ? 'selected' : ''; ?>>Visit</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="durasiMembership" class="form-label required">Durasi Membership</label>
                            <select class="form-select" id="durasiMembership" name="durasiMembership" required>
                                <option value="" disabled <?php echo empty($durasiMembership) ? 'selected' : ''; ?>>Pilih Durasi</option>
                                <option value="1 bulan" <?php echo $durasiMembership === '1 bulan' ? 'selected' : ''; ?>>1 Bulan</option>
                                <option value="3 bulan" <?php echo $durasiMembership === '3 bulan' ? 'selected' : ''; ?>>3 Bulan</option>
                                <option value="6 bulan" <?php echo $durasiMembership === '6 bulan' ? 'selected' : ''; ?>>6 Bulan</option>
                                <option value="12 bulan" <?php echo $durasiMembership === '12 bulan' ? 'selected' : ''; ?>>12 Bulan</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3">Informasi Akun</h5>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label required">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label required">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Minimal 6 karakter</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirmPassword" class="form-label required">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Tambah Anggota
                        </button>
                        <a href="admin-members.php" class="btn btn-outline-secondary ms-2">Batal</a>
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