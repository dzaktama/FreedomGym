<?php
// Mulai sesi
session_start();

// Sertakan file auth_check.php
require_once 'auth_check.php';

// Verifikasi admin sudah login
isAdminLoggedIn();

// Koneksi database
$servername = "localhost";
$username = "root"; // Ganti dengan username database Anda
$password = ""; // Ganti dengan password database Anda
$dbname = "gym";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Periksa apakah ID anggota disediakan
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['admin_message'] = "ID anggota tidak valid!";
    $_SESSION['admin_message_type'] = "danger";
    header("Location: admin-members.php");
    exit();
}

// Ambil ID anggota dari URL
$member_id = intval($_GET['id']);

// Fungsi untuk menghitung tanggal kedaluwarsa dengan mempertimbangkan jenis membership
function calculateExpiryDate($membershipType, $startDate, $durationStr) {
    $startDateTime = new DateTime($startDate);
    
    if ($membershipType === 'Visit') {
        // Untuk Visit, berlaku tepat 24 jam dari waktu pendaftaran
        $expiryDate = clone $startDateTime;
        $expiryDate->modify('+24 hours');
    } else {
        // Untuk membership regular, berlaku berdasarkan bulan
        $durationMonths = intval(explode(' ', $durationStr)[0]);
        $expiryDate = clone $startDateTime;
        $expiryDate->modify("+{$durationMonths} months");
    }
    
    return $expiryDate;
}

// Format tanggal dan waktu lengkap dengan jam, menit, detik
function formatDateTime($date) {
    return date("d F Y, H:i:s", strtotime($date));
}

// Format tanggal standar tanpa jam
function formatDate($date) {
    return date("d F Y", strtotime($date));
}

// Query dengan perhitungan sisa waktu dalam detik
$query = "SELECT m.*, 
          TIMESTAMPDIFF(SECOND, NOW(), 
          CASE 
            WHEN m.jenisMembership = 'Visit' THEN DATE_ADD(m.tanggalMulai, INTERVAL 24 HOUR)
            WHEN m.durasiMembership = '1 Bulan' THEN DATE_ADD(m.tanggalMulai, INTERVAL 1 MONTH)
            WHEN m.durasiMembership = '3 Bulan' THEN DATE_ADD(m.tanggalMulai, INTERVAL 3 MONTH)
            WHEN m.durasiMembership = '6 Bulan' THEN DATE_ADD(m.tanggalMulai, INTERVAL 6 MONTH)
            WHEN m.durasiMembership = '12 Bulan' THEN DATE_ADD(m.tanggalMulai, INTERVAL 12 MONTH)
            ELSE m.tanggalMulai
          END) as secondsRemaining
          FROM membership m 
          WHERE m.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

// Periksa apakah data keanggotaan ditemukan
if ($result->num_rows > 0) {
    $memberData = $result->fetch_assoc();
} else {
    $_SESSION['admin_message'] = "Anggota tidak ditemukan!";
    $_SESSION['admin_message_type'] = "danger";
    header("Location: admin-members.php");
    exit();
}

// Tutup statement
$stmt->close();

// Hasilkan nomor keanggotaan (format sederhana: MEM + ID pengguna 6 digit)
$membershipNumber = 'MEM' . str_pad($member_id, 6, '0', STR_PAD_LEFT);

// Dapatkan URL dasar untuk digunakan untuk jalur gambar
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseDir = dirname($_SERVER['SCRIPT_NAME']);
    $baseDir = $baseDir === '/' ? '' : $baseDir;
    return $protocol . $host . $baseDir;
}

// Jalur foto default - menggunakan URL relatif dan absolut untuk kompatibilitas yang lebih baik
$baseUrl = getBaseUrl();
$relativeDefaultPhoto = "assets/img/default-profile.png";
$defaultPhoto = $baseUrl . '/' . $relativeDefaultPhoto;

// Periksa apakah file foto ada dan dapat diakses
$photoPath = isset($memberData['fotoDiri']) && !empty($memberData['fotoDiri']) ? $memberData['fotoDiri'] : "";

// Fungsi untuk memeriksa apakah file ada dan dapat diakses
function isPhotoAccessible($path) {
    if (empty($path)) return false;
    
    // Untuk URL
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $headers = @get_headers($path);
        return $headers && strpos($headers[0], '200') !== false;
    } 
    
    // Untuk file lokal - periksa relatif terhadap root dokumen dan direktori skrip
    $relativePath = ltrim($path, '/');
    $docRootPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $relativePath;
    $scriptDirPath = dirname(__FILE__) . '/' . $relativePath;
    
    return file_exists($path) || file_exists($docRootPath) || file_exists($scriptDirPath);
}

// Atur jalur foto - jika foto pengguna tidak dapat diakses, gunakan foto default
if (!isPhotoAccessible($photoPath)) {
    $photoPath = $defaultPhoto;
}

// Cek jenis membership
$isVisit = ($memberData['jenisMembership'] === 'Visit');

// Hitung tanggal kedaluwarsa
$expiryDate = calculateExpiryDate(
    $memberData['jenisMembership'],
    $memberData['tanggalMulai'],
    $memberData['durasiMembership']
);

// Format tanggal kedaluwarsa sesuai jenis membership
$expiryDateFormatted = $isVisit ? 
    $expiryDate->format('d F Y, H:i:s') : 
    $expiryDate->format('d F Y');

// Periksa apakah keanggotaan sudah kedaluwarsa
$now = new DateTime();
$isExpired = $expiryDate < $now;

// Format sisa waktu untuk ditampilkan
$remainingTimeText = "";
if ($memberData['secondsRemaining'] > 0) {
    // Hitung hari, jam, menit
    $days = floor($memberData['secondsRemaining'] / (60 * 60 * 24));
    $hours = floor(($memberData['secondsRemaining'] % (60 * 60 * 24)) / (60 * 60));
    $minutes = floor(($memberData['secondsRemaining'] % (60 * 60)) / 60);
    
    if ($days > 0) {
        $remainingTimeText = "$days hari ";
    }
    if ($hours > 0 || $days > 0) {
        $remainingTimeText .= "$hours jam ";
    }
    $remainingTimeText .= "$minutes menit";
} else {
    $remainingTimeText = "Kadaluarsa";
}

// Tutup koneksi
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Anggota - FREEDOM GYM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Gaya untuk body */
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f6f9;
            min-height: 100vh;
        }
        
        /* Gaya untuk sidebar */
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
        
        /* Gaya header sidebar */
        .sidebar-header {
            padding: 10px 20px;
            border-bottom: 1px solid #2c3034;
            margin-bottom: 20px;
        }
        
        /* Gaya menu sidebar */
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        
        /* Gaya item menu sidebar */
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        /* Gaya link menu sidebar */
        .sidebar-menu a {
            color: #adb5bd;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: all 0.3s;
        }
        
        /* Efek hover dan aktif untuk item menu sidebar */
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Gaya icon pada menu sidebar */
        .sidebar-menu a i {
            margin-right: 10px;
        }
        
        /* Gaya konten utama */
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        
        /* Gaya header halaman */
        .page-header {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        /* Gaya kartu */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        /* Gaya untuk kontainer kartu membership */
        .membership-card-container {
            max-width: 650px;
            margin: 0 auto 30px;
        }

        /* Gaya kartu membership */
        .membership-card {
            background-color: #dc3545; /* Merah utama */
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            position: relative;
        }

        /* Gaya header kartu */
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

        /* Gaya konten kartu */
        .card-content {
            padding: 20px;
            display: flex;
            min-height: 300px;
        }

        /* Gaya untuk bagian foto */
        .photo-section {
            width: 30%;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-right: 1px solid rgba(0, 0, 0, 0.2);
            padding-right: 15px;
            margin-right: 10px;
        }

        /* Gaya untuk foto anggota */
        .member-photo {
            width: 100%;
            max-width: 150px;
            aspect-ratio: 1 / 1;
            border-radius: 8px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 10px;
            background-color: #f1f1f1;
        }

        /* Gaya untuk ID anggota */
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

        /* Gaya untuk bagian informasi */
        .info-section {
            width: 70%;
            padding-left: 10px;
            color: white;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-gap: 10px;
        }

        /* Gaya untuk item informasi */
        .info-item {
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
        }

        /* Gaya untuk label informasi */
        .info-label {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 3px;
        }

        /* Gaya untuk nilai informasi */
        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            word-break: break-word;
            line-height: 1.3;
        }

        /* Kelas khusus untuk teks panjang seperti email */
        .info-value.long-text {
            font-size: 0.85rem;
        }

        /* Gaya untuk footer kartu */
        .card-footer {
            width: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            text-align: center;
            padding: 8px;
            font-size: 0.8rem;
            margin-top: auto;
        }

        /* Gaya untuk badge kedaluwarsa */
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
        
        /* Gaya untuk tabel info anggota */
        .member-info-table td, .member-info-table th {
            padding: 12px 15px;
        }
        
        /* Gaya untuk header tabel info anggota */
        .member-info-table th {
            font-weight: 600;
            color: #495057;
        }
        
        /* Gaya untuk tombol aksi */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        /* Gaya untuk membership status */
        .membership-status {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .status-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        
        .remaining-time {
            font-size: 1rem;
            font-weight: 500;
        }
        
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-expired {
            color: #dc3545;
            font-weight: 600;
        }
        
        /* Responsivitas untuk perangkat mobile */
        @media (max-width: 767px) {
            .content {
                margin-left: 0;
                padding: 10px;
            }
            
            .card-content {
                flex-direction: column;
                padding: 15px;
            }
            
            .photo-section {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid rgba(0, 0, 0, 0.2);
                padding-right: 0;
                padding-bottom: 15px;
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .info-section {
                width: 100%;
                padding-left: 0;
                display: block;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        
        /* CSS untuk Cetak Kartu Membership */
        @media print {
            /* Reset ukuran halaman dan margin */
            @page {
                size: landscape;  /* Gunakan landscape untuk kartu yang lebih lebar */
                margin: 0;        /* Hilangkan margin halaman */
            }
            
            /* Sembunyikan semua elemen kecuali yang akan dicetak */
            body * {
                visibility: hidden !important;
            }
            
            /* Tampilkan hanya kartu membership */
            .membership-card, .membership-card * {
                visibility: visible !important;
            }
            
            /* Posisikan kartu membership di tengah halaman */
            .membership-card {
                position: fixed !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 90% !important;  /* Gunakan persentase untuk adaptasi lebih baik */
                max-width: 500px !important; /* Batasi lebar maksimum */
                height: auto !important;
                aspect-ratio: 1.6 / 1 !important; /* Rasio kartu ID standar */
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0!important;
                page-break-inside: avoid !important; /* Cegah kartu terpotong */
                overflow: visible !important;
            }
            
            /* Ubah tata letak kartu dari yang sebelumnya */
            .card-content {
                display: flex !important;
                flex-direction: row !important;
                height: auto !important;
                padding: 10px !important;
            }
            
            /* Header kartu */
            .card-header {
                background-color: #aa0000 !important;
                color: white !important;
                font-weight: bold !important;
                text-align: center !important;
                padding: 10px !important;
                font-size: 1.2rem !important;
            }
            
            /* Bagian foto */
            .photo-section {
                width: 25% !important;
                padding: 10px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: flex-start !important;
            }
            
            /* Foto anggota */
            .member-photo {
                width: 100% !important;
                aspect-ratio: 1 / 1 !important;
                border-radius: 8px !important;
                border: 3px solid white !important;
                margin-bottom: 10px !important;
                max-width: 150px !important;
            }
            
            /* ID anggota */
            .member-id {
                text-align: center !important;
                background-color: black !important;
                color: white !important;
                padding: 3px 8px !important;
                font-size: 0.8rem !important;
                width: 100% !important;
                border-radius: 4px !important;
            }
            
            /* Bagian informasi */
            .info-section {
                width: 75% !important;
                padding: 10px 15px !important;
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                grid-gap: 8px !important;
            }
            
            /* Setiap item informasi */
            .info-item {
                margin-bottom: 5px !important;
                display: flex !important;
                flex-direction: column !important;
            }
            
            /* Label informasi */
            .info-label {
                font-size: 0.7rem !important;
                color: rgba(255, 255, 255, 0.8) !important;
                margin-bottom: 2px !important;
            }
            
            /* Nilai informasi */
            .info-value {
                font-size: 0.9rem !important;
                font-weight: bold !important;
                color: white !important;
            }
            
            /* Footer kartu */
            .card-footer {
                position: absolute !important;
                bottom: 0 !important;
                width: 100% !important;
                background-color: rgba(0, 0, 0, 0.7) !important;
                color: white !important;
                text-align: center !important;
                padding: 5px !important;
                font-size: 0.8rem !important;
            }
            
            /* Badge kedaluwarsa */
            .expired-badge {
                position: absolute !important;
                top: 50px !important;
                right: -35px !important;
                background-color: #dc3545 !important;
                color: white !important;
                padding: 5px 30px !important;
                transform: rotate(45deg) !important;
                font-weight: bold !important;
                font-size: 0.9rem !important;
                z-index: 10 !important;
            }
            
            /* Pastikan warna dan gambar tercetak dengan benar */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
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
            <li><a href="admin-reports.php"><i class="bi bi-clipboard-data"></i> Laporan</a></li>
            <li><a href="admin-settings.php"><i class="bi bi-gear"></i> Pengaturan</a></li>
            <li><a href="logout-admin.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Konten Utama -->
    <div class="content">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Detail Anggota</h3>
            <a href="admin-members.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Membership Status Card -->
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Status Membership</h5>
            </div>
            <div class="card-body">
                <div class="membership-status">
                    <div class="status-title">
                        Status: 
                        <span class="<?php echo $isExpired ? 'status-expired' : 'status-active'; ?>">
                            <?php echo $isExpired ? 'KADALUARSA' : 'AKTIF'; ?>
                        </span>
                    </div>
                    <?php if (!$isExpired): ?>
                        <div class="remaining-time">
                            Sisa waktu: <?php echo $remainingTimeText; ?>
                        </div>
                    <?php else: ?>
                        <div class="remaining-time status-expired">
                            Membership telah berakhir pada: <?php echo $expiryDateFormatted; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kartu Informasi Anggota -->
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Informasi Anggota</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless member-info-table">
                            <tr>
                                <th width="40%">ID Anggota</th>
                                <td><?php echo $member_id; ?></td>
                            </tr>
                            <tr>
                                <th>Nama</th>
                                <td><?php echo htmlspecialchars($memberData['nama']); ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo htmlspecialchars($memberData['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Nomor HP</th>
                                <td><?php echo htmlspecialchars($memberData['nomorHP']); ?></td>
                            </tr>
                            <tr>
                                <th>Jenis Kelamin</th>
                                <td><?php echo htmlspecialchars($memberData['jenisKelamin']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless member-info-table">
                            <tr>
                                <th width="40%">Tanggal Lahir</th>
                                <td><?php echo formatDate($memberData['tanggalLahir']); ?></td>
                            </tr>
                            <tr>
                                <th>Jenis Membership</th>
                                <td><?php echo htmlspecialchars($memberData['jenisMembership']); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Mulai</th>
                                <td><?php echo $isVisit ? formatDateTime($memberData['tanggalMulai']) : formatDate($memberData['tanggalMulai']); ?></td>
                            </tr>
                            <tr>
                                <th>Durasi</th>
                                <td><?php echo htmlspecialchars($memberData['durasiMembership']); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Berakhir</th>
                                <td>
                                    <?php echo $expiryDateFormatted; ?>
                                    <?php if ($isExpired): ?>
                                        <span class="badge bg-danger ms-2">Kadaluarsa</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="admin-member-edit.php?id=<?php echo $member_id; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit Profil
                    </a>
                    <a href="admin-membership-renew.php?id=<?php echo $member_id; ?>" class="btn btn-success">
                        <i class="bi bi-arrow-repeat"></i> Perpanjang Membership
                    </a>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $member_id; ?>, '<?php echo htmlspecialchars($memberData['nama']); ?>')">
                        <i class="bi bi-trash"></i> Hapus Anggota
                    </button>
                </div>
            </div>
        </div>

        <!-- Kartu Membership -->
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Kartu Membership</h5>
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
                                    alt="class="member-photo" 
                                    alt="Foto Profil" 
                                    data-default="<?php echo htmlspecialchars($defaultPhoto); ?>"
                                    onerror="this.src=this.getAttribute('data-default')">
                                <div class="member-id"><?php echo htmlspecialchars($membershipNumber); ?></div>
                            </div>
                            
                            <div class="info-section">
                                <div class="info-item">
                                    <div class="info-label">Nama</div>
                                    <div class="info-value"><?php echo htmlspecialchars($memberData['nama']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Jenis Kelamin</div>
                                    <div class="info-value"><?php echo htmlspecialchars($memberData['jenisKelamin']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Tanggal Lahir</div>
                                    <div class="info-value"><?php echo formatDate($memberData['tanggalLahir']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value long-text"><?php echo htmlspecialchars($memberData['email']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Nomor HP</div>
                                    <div class="info-value"><?php echo htmlspecialchars($memberData['nomorHP']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Jenis Membership</div>
                                    <div class="info-value"><?php echo htmlspecialchars($memberData['jenisMembership']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Tanggal Mulai</div>
                                    <div class="info-value"><?php echo $isVisit ? formatDateTime($memberData['tanggalMulai']) : formatDate($memberData['tanggalMulai']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Durasi</div>
                                    <div class="info-value"><?php echo htmlspecialchars($memberData['durasiMembership']); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            FREEDOM GYM - MEMBERCARD - Valid until <?php echo $expiryDateFormatted; ?>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    <button class="btn btn-primary" onclick="printMembershipCard()">
                        <i class="bi bi-printer"></i> Cetak Kartu Membership
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus anggota <strong id="deleteMemberName"></strong>?</p>
                    <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="deleteLink" class="btn btn-danger">Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk konfirmasi penghapusan anggota
        function confirmDelete(id, name) {
            // Atur nama anggota yang akan dihapus pada modal
            document.getElementById('deleteMemberName').textContent = name;
            // Atur link untuk menghapus anggota
            document.getElementById('deleteLink').href = 'admin-members.php?delete=' + id;
            
            // Tampilkan modal konfirmasi
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
        
        // Fungsi untuk mencetak kartu membership (menggunakan window.print)
        function printMembershipCard() {
            // Gunakan metode cetak bawaan browser
            window.print();
        }
        
        // Tangani error loading gambar dengan mengganti ke gambar default
        document.addEventListener('DOMContentLoaded', function() {
            // Ambil elemen foto anggota
            var memberPhoto = document.querySelector('.member-photo');
            
            // Pastikan elemen foto ada
            if(memberPhoto) {
                // Fallback jika gambar gagal dimuat
                memberPhoto.addEventListener('error', function() {
                    var defaultSrc = this.getAttribute('data-default');
                    if (this.src !== defaultSrc) {
                        // Coba gunakan gambar default jika berbeda dari current src
                        this.src = defaultSrc;
                    } else {
                        // Fallback ke path relatif jika default juga gagal
                        this.src = 'assets/img/default-profile.png';
                    }
                });
                
                // Verifikasi gambar dimuat dengan benar
                memberPhoto.addEventListener('load', function() {
                    if (this.naturalWidth === 0 || this.naturalHeight === 0) {
                        this.src = this.getAttribute('data-default');
                    }
                });
            }
        });
    </script>
</body>
</html>