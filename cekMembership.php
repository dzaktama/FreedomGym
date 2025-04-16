<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gym";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Prepare statement to get membership data
$stmt = $conn->prepare("SELECT * FROM membership WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if we have membership data
if ($result->num_rows > 0) {
    $memberData = $result->fetch_assoc();
} else {
    // Redirect to registration page
    header("Location: register.php");
    exit();
}

$stmt->close();
$conn->close();

// Generate membership number (simple format: MEM + 6 digit padded user id)
$membershipNumber = 'MEM' . str_pad($user_id, 6, '0', STR_PAD_LEFT);

// Format date from database date format to more readable format
function formatDate($date) {
    return date("d F Y", strtotime($date));
}

// Format datetime to include time for Visit memberships
function formatDateTime($date) {
    return date("d F Y, H:i", strtotime($date));
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

// Menangani durasi dan masa berlaku membership
$startDate = new DateTime($memberData['tanggalMulai']);
$durationStr = $memberData['durasiMembership'];
$isVisit = ($memberData['jenisMembership'] === 'Visit');

// Tanggal dan waktu saat ini untuk membandingkan apakah membership masih aktif
$now = new DateTime();

// Hitung expiry date berdasarkan jenis membership
if ($isVisit) {
    // Untuk membership Visit (durasi 1 Hari = 24 jam)
    $expiryDate = clone $startDate;
    $expiryDate->modify("+24 hours");
    $expiryDateFormatted = $expiryDate->format('d F Y, H:i');
    
    // Jika 24 jam sudah lewat, membership sudah tidak berlaku
    $isExpired = ($now > $expiryDate);
} else {
    // Untuk membership regular (berdasarkan bulan)
    $durationMonths = intval(explode(' ', $durationStr)[0]);
    $expiryDate = clone $startDate;
    $expiryDate->modify("+{$durationMonths} months");
    $expiryDateFormatted = $expiryDate->format('d F Y');
    
    // Jika tanggal expired sudah lewat, membership tidak berlaku
    $isExpired = ($now > $expiryDate);
}

// Hitung hari tersisa untuk membership yang masih aktif
if (!$isExpired) {
    $daysDiff = $now->diff($expiryDate)->days;
    $hoursDiff = $now->diff($expiryDate)->h;
    
    if ($isVisit) {
        // Format dalam jam untuk Visit
        if ($daysDiff > 0) {
            $remainingTime = $daysDiff . " hari " . $hoursDiff . " jam";
        } else {
            $remainingTime = $hoursDiff . " jam " . $now->diff($expiryDate)->i . " menit";
        }
    } else {
        // Format dalam hari untuk membership regular
        $remainingTime = $daysDiff . " hari";
    }
} else {
    $remainingTime = "Expired";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Card - FREEDOM GYM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-image: url('assets/img/hero/h1_hero.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-color: rgba(0, 0, 0, 0.7);
            background-blend-mode: overlay;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .page-container {
            max-width: 900px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .membership-card {
            background-color: #dc3545; /* Merah utama */
            width: 100%;
            max-width: 650px; /* Ukuran diperpanjang */
            aspect-ratio: 1.85 / 1; /* Rasio diperpanjang */
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            margin: 0 auto 30px;
            position: relative;
            min-height: 380px; /* Tinggi minimum untuk mencegah tabrakan */
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
        
        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .btn-back {
            background-color: white;
            color: #dc3545;
            border: 2px solid #dc3545;
            font-weight: 600;
            transition: all 0.3s;
            padding: 10px 25px;
            border-radius: 6px;
        }
        
        .btn-back:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-edit {
            background-color: #dc3545;
            color: white;
            border: 2px solid #dc3545;
            font-weight: 600;
            transition: all 0.3s;
            padding: 10px 25px;
            border-radius: 6px;
        }
        
        .btn-edit:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .membership-status {
            background-color: white;
            color: black;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-expired {
            color: #dc3545;
            font-weight: bold;
        }
        
        .remaining-time {
            font-size: 1.1rem;
            margin-top: 5px;
            font-weight: 600;
        }
        
        /* Responsiveness */
        @media (max-width: 500px) {
            .membership-card {
                max-width: 100%;
                aspect-ratio: auto;
                min-height: auto;
            }
            
            .card-header {
                font-size: 1.1rem;
                padding: 10px;
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
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                margin-right: 0;
            }
            
            .member-photo {
                width: 60%;
                margin-bottom: 0;
            }
            
            .member-id {
                width: 35%;
                font-size: 0.7rem;
                margin-top: 0;
            }
            
            .info-section {
                width: 100%;
                padding-left: 0;
            }
            
            .info-label {
                flex: 0 0 40%;
            }
            
            .info-value {
                flex: 0 0 60%;
            }
            
            .card-footer {
                position: relative;
                margin-top: 15px;
            }
            
            .button-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="header">
            <h1>FREEDOM GYM</h1>
            <h3>Kartu Membership</h3>
        </div>
        
        <?php if (isset($_SESSION['profile_updated']) && $_SESSION['profile_updated']): ?>
            <div class="alert alert-success text-center mb-4">
                Profil berhasil diperbarui!
                <?php unset($_SESSION['profile_updated']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Status Membership -->
        <div class="membership-status">
            <h4>Status Membership: 
                <span class="<?php echo $isExpired ? 'status-expired' : 'status-active'; ?>">
                    <?php echo $isExpired ? 'TIDAK AKTIF' : 'AKTIF'; ?>
                </span>
            </h4>
            <?php if (!$isExpired): ?>
                <div class="remaining-time">
                    Sisa Waktu: <?php echo $remainingTime; ?>
                </div>
            <?php else: ?>
                <div class="remaining-time status-expired">
                    Membership Anda telah berakhir
                </div>
            <?php endif; ?>
        </div>
        
        <div class="membership-card">
            <div class="card-header">
                FREEDOM GYM CARD
            </div>
            
            <div class="card-content">
                <div class="photo-section">
                    <!-- Tambahkan data-default untuk menyimpan URL gambar default -->
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
                        <div class="info-value"><?php echo htmlspecialchars($memberData['nama']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Jenis Kelamin</div>
                        <div class="info-value"><?php echo htmlspecialchars($memberData['jenisKelamin']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Tanggal Lahir</div>
                        <div class="info-value"><?php echo htmlspecialchars(formatDate($memberData['tanggalLahir'])); ?></div>
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
                        <div class="info-value">
                            <?php echo $isVisit ? htmlspecialchars(formatDateTime($memberData['tanggalMulai'])) : htmlspecialchars(formatDate($memberData['tanggalMulai'])); ?>
                        </div>
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
        
        <div class="button-container">
            <a href="editProfile.php" class="btn btn-edit">
                <i class="bi bi-pencil-square"></i> Edit Profil
            </a>
            <a href="index.php" class="btn btn-back">
                Kembali ke Beranda
            </a>
        </div>
    </div>
    
    <script>
        // Improved backup image error handler with multiple fallbacks
        document.addEventListener('DOMContentLoaded', function() {
            var memberPhoto = document.querySelector('.member-photo');
            
            // Fallback 1: Jika gambar gagal dimuat, coba gunakan URL dalam atribut data-default
            memberPhoto.addEventListener('error', function() {
                var defaultSrc = this.getAttribute('data-default');
                if (this.src !== defaultSrc) {
                    this.src = defaultSrc;
                } else {
                    // Fallback 2: Jika gambar default juga gagal, coba relatif ke root
                    this.src = '/assets/img/default-profile.png';
                }
            });
            
            // Verifikasi tambahan: Cek jika gambar ada setelah dimuat
            memberPhoto.addEventListener('load', function() {
                if (this.naturalWidth === 0 || this.naturalHeight === 0) {
                    this.src = this.getAttribute('data-default');
                }
            });
        });
    </script>
</body>
</html>