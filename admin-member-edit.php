<?php
// Start session
session_start();

// Include auth_check.php
require_once 'auth_check.php';

// Verifikasi admin sudah login
isAdminLoggedIn();

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

$errors = [];

// Check if member ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['admin_message'] = "ID anggota tidak valid!";
    $_SESSION['admin_message_type'] = "danger";
    header("Location: admin-members.php");
    exit();
}

$member_id = intval($_GET['id']);

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
    $tanggalMulai = trim($_POST['tanggalMulai']);
    $username = trim($_POST['username']);
    $newPassword = trim($_POST['password']);
    
    // Basic validation
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
    
    if (empty($tanggalMulai)) {
        $errors[] = "Tanggal mulai tidak boleh kosong";
    }
    
    if (empty($username)) {
        $errors[] = "Username tidak boleh kosong";
    } else {
        // Check if username already exists but exclude current member
        $check_username = "SELECT id FROM membership WHERE username = ? AND id != ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("si", $username, $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Username sudah digunakan oleh anggota lain";
        }
        $stmt->close();
    }
    
    // If password is provided, check length
    if (!empty($newPassword) && strlen($newPassword) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }
    
    // If no errors, update data
    if (empty($errors)) {
        if (!empty($newPassword)) {
            // Update with new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $update_sql = "UPDATE membership SET 
                          nama = ?, 
                          email = ?, 
                          nomorHP = ?, 
                          jenisKelamin = ?, 
                          tanggalLahir = ?, 
                          jenisMembership = ?, 
                          durasiMembership = ?, 
                          tanggalMulai = ?, 
                          username = ?, 
                          password = ? 
                          WHERE id = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssssssssi", $nama, $email, $nomorHP, $jenisKelamin, $tanggalLahir, $jenisMembership, $durasiMembership, $tanggalMulai, $username, $hashedPassword, $member_id);
        } else {
            // Update without changing password
            $update_sql = "UPDATE membership SET 
                          nama = ?, 
                          email = ?, 
                          nomorHP = ?, 
                          jenisKelamin = ?, 
                          tanggalLahir = ?, 
                          jenisMembership = ?, 
                          durasiMembership = ?, 
                          tanggalMulai = ?, 
                          username = ? 
                          WHERE id = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssssssssi", $nama, $email, $nomorHP, $jenisKelamin, $tanggalLahir, $jenisMembership, $durasiMembership, $tanggalMulai, $username, $member_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Data anggota berhasil diperbarui!";
            $_SESSION['admin_message_type'] = "success";
            header("Location: admin-member-view.php?id=" . $member_id);
            exit();
        } else {
            $errors[] = "Gagal memperbarui data: " . $conn->error;
        }
        
        $stmt->close();
    }
}

// Prepare statement to get membership data
$stmt = $conn->prepare("SELECT * FROM membership WHERE id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if we have membership data
if ($result->num_rows > 0) {
    $memberData = $result->fetch_assoc();
} else {
    $_SESSION['admin_message'] = "Anggota tidak ditemukan!";
    $_SESSION['admin_message_type'] = "danger";
    header("Location: admin-members.php");
    exit();
}

$stmt->close();
$conn->close();

// Generate membership number (simple format: MEM + 6 digit padded user id)
$membershipNumber = 'MEM' . str_pad($member_id, 6, '0', STR_PAD_LEFT);

// Format date from database date format to more readable format
function formatDate($date) {
    return date("d F Y", strtotime($date));
}

// Get base URL to use for image paths
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseDir = dirname($_SERVER['SCRIPT_NAME']);
    $baseDir = $baseDir === '/' ? '' : $baseDir;
    return $protocol . $host . $baseDir;
}

// Default photo path - using both relative and absolute URLs for better compatibility
$baseUrl = getBaseUrl();
$relativeDefaultPhoto = "assets/img/default-profile.png";
$defaultPhoto = $baseUrl . '/' . $relativeDefaultPhoto;

// Check if the photo file exists and is accessible
$photoPath = isset($memberData['fotoDiri']) && !empty($memberData['fotoDiri']) ? $memberData['fotoDiri'] : "";

// Function to check if file exists and is accessible
function isPhotoAccessible($path) {
    if (empty($path)) return false;
    
    // For URLs
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $headers = @get_headers($path);
        return $headers && strpos($headers[0], '200') !== false;
    } 
    
    // For local files - check both relative to document root and script directory
    $relativePath = ltrim($path, '/');
    $docRootPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $relativePath;
    $scriptDirPath = dirname(__FILE__) . '/' . $relativePath;
    
    return file_exists($path) || file_exists($docRootPath) || file_exists($scriptDirPath);
}

// Set photo path - if user photo is inaccessible, use default photo
if (!isPhotoAccessible($photoPath)) {
    $photoPath = $defaultPhoto;
}

// Calculate expiry date
$startDate = new DateTime($memberData['tanggalMulai']);
$durationStr = $memberData['durasiMembership'];
$durationMonths = intval(explode(' ', $durationStr)[0]);
$expiryDate = clone $startDate;
$expiryDate->modify("+{$durationMonths} months");
$expiryDateFormatted = $expiryDate->format('d F Y');

// Check if membership is expired
$today = new DateTime();
$isExpired = $expiryDate < $today;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Anggota - FREEDOM GYM</title>
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
        
        /* Membership Card Styles */
        .membership-card-container {
            max-width: 650px;
            margin: 0 auto 30px;
        }
        
        .membership-card {
            background-color: #dc3545; /* Merah utama */
            width: 100%;
            aspect-ratio: 1.85 / 1; /* Rasio diperpanjang */
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            position: relative;
        }
        
        .card-header {
            background-color: #aa0000; /* Merah lebih gelap */
            color: white;
            padding: 12px 20px;
            font-weight: bold;
            font-size: 1.3rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid black;
        }
        
        .card-content {
            padding: 20px;
            display: flex;
            height: calc(100% - 54px); /* Full height minus header */
        }
        
        .photo-section {
            width: 30%;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-right: 1px solid rgba(0, 0, 0, 0.2);
            padding-right: 15px;
            margin-right: 10px; /* Tambahan jarak ke kanan */
        }
        
        .member-photo {
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 8px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 10px;
            background-color: #f1f1f1; /* Light background for default image */
        }
        
        .member-id {
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
            text-align: center;
            background-color: black;
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 5px;
            width: 100%;
        }
        
        .info-section {
            width: 70%;
            padding-left: 10px; /* Sedikit kurangi padding untuk space lebih */
            color: white;
        }
        
        .info-item {
            margin-bottom: 12px; /* Meningkatkan jarak antar item */
            display: flex;
            align-items: flex-start;
        }
        
        .info-label {
            flex: 0 0 35%; /* Kurangi sedikit lebar label */
            font-size: 0.85rem; /* Sedikit perkecil font */
            opacity: 0.9;
            padding-right: 10px; /* Tambah jarak ke kanan */
        }
        
        .info-value {
            flex: 0 0 65%; /* Tambah lebar nilai */
            font-size: 0.95rem; /* Sedikit perkecil font */
            font-weight: 600;
            word-break: break-word; /* Memungkinkan pemisahan kata */
            line-height: 1.3; /* Atur jarak baris */
        }
        
        /* Class khusus untuk teks panjang seperti email */
        .info-value.long-text {
            font-size: 0.85rem; /* Lebih kecil lagi untuk teks panjang */
        }
        
        .card-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            text-align: center;
            padding: 6px;
            font-size: 0.8rem;
        }
        
        /* Expired Badge */
        .expired-badge {
            position: absolute;
            top: 60px;
            right: -35px;
            background-color: #dc3545;
            color: white;
            padding: 5px 40px;
            font-weight: 700;
            font-size: 1rem;
            transform: rotate(45deg);
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
            z-index: 10;
        }
        
        .form-label.required::after {
            content: " *";
            color: #dc3545;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Responsiveness */
        @media (max-width: 767px) {
            .content {
                margin-left: 0;
                padding: 10px;
            }
            
            .membership-card {
                aspect-ratio: auto;
            }
            
            .card-content {
                flex-direction: column;
                padding: 15px;
                height: auto;
            }
            
            .photo-section {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid rgba(0, 0, 0, 0.2);
                padding-right: 0;
                padding-bottom: 15px;
                margin-bottom: 15px;
                margin-right: 0;
            }
            
            .info-section {
                width: 100%;
                padding-left: 0;
            }
            
            .card-footer {
                position: relative;
                margin-top: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
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
            <h3 class="mb-0">Edit Anggota</h3>
            <div>
                <a href="admin-member-view.php?id=<?php echo $member_id; ?>" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-x"></i> Batal
                </a>
                <a href="admin-members.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
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

        <!-- Edit Form -->
        <form method="POST" action="">
            <!-- Member Info Card -->
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Informasi Anggota</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nama" class="form-label required">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($memberData['nama']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($memberData['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nomorHP" class="form-label required">Nomor HP</label>
                            <input type="text" class="form-control" id="nomorHP" name="nomorHP" value="<?php echo htmlspecialchars($memberData['nomorHP']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="jenisKelamin" class="form-label required">Jenis Kelamin</label>
                            <select class="form-select" id="jenisKelamin" name="jenisKelamin" required>
                                <option value="Laki-laki" <?php echo $memberData['jenisKelamin'] === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="Perempuan" <?php echo $memberData['jenisKelamin'] === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tanggalLahir" class="form-label required">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="tanggalLahir" name="tanggalLahir" value="<?php echo htmlspecialchars($memberData['tanggalLahir']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="jenisMembership" class="form-label required">Jenis Membership</label>
                            <select class="form-select" id="jenisMembership" name="jenisMembership" required>
                                <option value="Individual" <?php echo $memberData['jenisMembership'] === 'Individual' ? 'selected' : ''; ?>>Individual</option>
                                <option value="Squad" <?php echo $memberData['jenisMembership'] === 'Squad' ? 'selected' : ''; ?>>Squad</option>
                                <option value="Visit" <?php echo $memberData['jenisMembership'] === 'Visit' ? 'selected' : ''; ?>>Visit</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tanggalMulai" class="form-label required">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="tanggalMulai" name="tanggalMulai" value="<?php echo htmlspecialchars($memberData['tanggalMulai']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="durasiMembership" class="form-label required">Durasi Membership</label>
                            <select class="form-select" id="durasiMembership" name="durasiMembership" required>
                                <option value="1 bulan" <?php echo $memberData['durasiMembership'] === '1 bulan' ? 'selected' : ''; ?>>1 Bulan</option>
                                <option value="3 bulan" <?php echo $memberData['durasiMembership'] === '3 bulan' ? 'selected' : ''; ?>>3 Bulan</option>
                                <option value="6 bulan" <?php echo $memberData['durasiMembership'] === '6 bulan' ? 'selected' : ''; ?>>6 Bulan</option>
                                <option value="12 bulan" <?php echo $memberData['durasiMembership'] === '12 bulan' ? 'selected' : ''; ?>>12 Bulan</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3">Informasi Akun</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label required">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($memberData['username']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password Baru <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small></label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text">Minimal 6 karakter</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Membership Card Preview -->
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Preview Kartu Membership</h5>
                </div>
                <div class="card-body">
                    <div class="membership-card-container">
                        <div class="membership-card">
                            <?php if ($isExpired): ?>
                                <div class="expired-badge">EXPIRED</div>
                            <?php endif; ?>
                            
                            <div class="card-header">
                                FREEDOM GYM CARD
                            </div>
                            
                            <div class="card-content">
                                <div class="photo-section">
                                    <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                                        class="member-photo" 
                                        alt="Foto Profil" 
                                        data-default="<?php echo htmlspecialchars($defaultPhoto); ?>"
                                        onerror="this.src=this.getAttribute('data-default')">
                                    <div class="member-id"><?php echo htmlspecialchars($membershipNumber); ?></div>
                                </div>
                                
                                <div class="info-section">
                                    <div class="info-item">
                                        <div class="info-label">Nama</div>
                                        <div class="info-value" id="preview-nama"><?php echo htmlspecialchars($memberData['nama']); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Jenis Kelamin</div>
                                        <div class="info-value" id="preview-jenisKelamin"><?php echo htmlspecialchars($memberData['jenisKelamin']); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Tanggal Lahir</div>
                                        <div class="info-value" id="preview-tanggalLahir"><?php echo htmlspecialchars(formatDate($memberData['tanggalLahir'])); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Email</div>
                                        <div class="info-value long-text" id="preview-email"><?php echo htmlspecialchars($memberData['email']); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Nomor HP</div>
                                        <div class="info-value" id="preview-nomorHP"><?php echo htmlspecialchars($memberData['nomorHP']); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Jenis Membership</div>
                                        <div class="info-value" id="preview-jenisMembership"><?php echo htmlspecialchars($memberData['jenisMembership']); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Tanggal Mulai</div>
                                        <div class="info-value" id="preview-tanggalMulai"><?php echo htmlspecialchars(formatDate($memberData['tanggalMulai'])); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Durasi</div>
                                        <div class="info-value" id="preview-durasiMembership"><?php echo htmlspecialchars($memberData['durasiMembership']); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                FREEDOM GYM - MEMBERCARD - Valid until <span id="preview-expiryDate"><?php echo $expiryDateFormatted; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="d-flex justify-content-center mb-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live preview update for membership card
        document.addEventListener('DOMContentLoaded', function() {
            // Update name preview
            document.getElementById('nama').addEventListener('input', function() {
                document.getElementById('preview-nama').textContent = this.value;
            });
            
            // Update gender preview
            document.getElementById('jenisKelamin').addEventListener('change', function() {
                document.getElementById('preview-jenisKelamin').textContent = this.options[this.selectedIndex].text;
            });
            
            // Update birth date preview
            document.getElementById('tanggalLahir').addEventListener('change', function() {
                const date = new Date(this.value);
                const options = { day: 'numeric', month: 'long', year: 'numeric' };
                document.getElementById('preview-tanggalLahir').textContent = date.toLocaleDateString('id-ID', options);
            });
            
            // Update email preview
            document.getElementById('email').addEventListener('input', function() {
                document.getElementById('preview-email').textContent = this.value;
            });
            
            // Update phone preview
            document.getElementById('nomorHP').addEventListener('input', function() {
                document.getElementById('preview-nomorHP').textContent = this.value;
            });
            
            // Update membership type preview
            document.getElementById('jenisMembership').addEventListener('change', function() {
                document.getElementById('preview-jenisMembership').textContent = this.options[this.selectedIndex].text;
            });
            
            // Update start date preview
            document.getElementById('tanggalMulai').addEventListener('change', function() {
                const date = new Date(this.value);
                const options = { day: 'numeric', month: 'long', year: 'numeric' };
                document.getElementById('preview-tanggalMulai').textContent = date.toLocaleDateString('id-ID', options);
                
                // Update expiry date
                updateExpiryDate();
            });
            
            // Update duration preview
            document.getElementById('durasiMembership').addEventListener('change', function() {
                document.getElementById('preview-durasiMembership').textContent = this.options[this.selectedIndex].text;
                
                // Update expiry date
                updateExpiryDate();
            });
            
            // Function to update expiry date based on start date and duration
            function updateExpiryDate() {
                const startDate = document.getElementById('tanggalMulai').value;
                const durationSelect = document.getElementById('durasiMembership');
                const durationText = durationSelect.options[durationSelect.selectedIndex].text;
                
                if (startDate) {
                    const durationMonths = parseInt(durationText.split(' ')[0]);
                    const date = new Date(startDate);
                    date.setMonth(date.getMonth() + durationMonths);
                    
                    const options = { day: 'numeric', month: 'long', year: 'numeric' };
                    document.getElementById('preview-expiryDate').textContent = date.toLocaleDateString('id-ID', options);
                }
            }
            
            // Improved backup image error handler with multiple fallbacks
            var memberPhoto = document.querySelector('.member-photo');
            
            // Fallback if image fails to load
            memberPhoto.addEventListener('error', function() {
                var defaultSrc = this.getAttribute('data-default');
                if (this.src !== defaultSrc) {
                    this.src = defaultSrc;
                } else {
                    // Fallback to relative path if default also fails
                    this.src = '/assets/img/default-profile.png';
                }
            });
            
            // Verify image loaded correctly
            memberPhoto.addEventListener('load', function() {
                if (this.naturalWidth === 0 || this.naturalHeight === 0) {
                    this.src = this.getAttribute('data-default');
                }
            });
        });
    </script>
</body>
</html>