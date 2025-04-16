<?php
// Mulai sesi untuk manajemen otentikasi dan penyimpanan data
session_start();

// Periksa apakah admin telah login
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in'])) {
    // Jika admin belum login, arahkan ke halaman login admin
    header("Location: admin-login.php");
    exit();
}

// Pengaturan koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gym";

// Buat koneksi ke database
$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi database
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Inisialisasi variabel
$id_anggota = 0;
$error = '';
$success = '';
$memberData = null;

// Fungsi untuk memformat tanggal dan waktu dalam format yang lebih mudah dibaca
function formatDateTime($dateTimeStr) {
    $date = new DateTime($dateTimeStr);
    return $date->format('d F Y, H:i:s');
}

// Fungsi untuk mendapatkan informasi durasi membership dalam bentuk teks
function getDurationText($durationStr) {
    if ($durationStr === '1 Hari') {
        return '24 jam';
    } else {
        return $durationStr;
    }
}

// Periksa apakah ada parameter ID anggota
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_anggota = intval($_GET['id']);
    
    // Ambil data anggota berdasarkan ID
    $query = "SELECT m.*, TIMESTAMPDIFF(SECOND, NOW(), 
              CASE 
                WHEN m.jenisMembership = 'Visit' THEN DATE_ADD(m.tanggalMulai, INTERVAL 24 HOUR)
                WHEN m.durasiMembership = '1 Bulan' THEN DATE_ADD(m.tanggalMulai, INTERVAL 1 MONTH)
                WHEN m.durasiMembership = '3 Bulan' THEN DATE_ADD(m.tanggalMulai, INTERVAL 3 MONTH)
                WHEN m.durasiMembership = '6 Bulan' THEN DATE_ADD(m.tanggalMulai, INTERVAL 6 MONTH)
                WHEN m.durasiMembership = '12 Bulan' THEN DATE_ADD(m.tanggalMulai, INTERVAL 12 MONTH)
                ELSE m.tanggalMulai
              END) as secondsRemaining
              FROM membership m WHERE m.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_anggota);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $memberData = $result->fetch_assoc();
    } else {
        $error = "Data anggota tidak ditemukan.";
    }
    
    $stmt->close();
} else {
    $error = "ID anggota tidak valid.";
}

// Hitung tanggal berakhir berdasarkan jenis dan durasi membership
function calculateExpiryDate($memberData) {
    $startDate = new DateTime($memberData['tanggalMulai']);
    
    if ($memberData['jenisMembership'] === 'Visit') {
        // Untuk membership Visit, berlaku 24 jam
        $expiryDate = clone $startDate;
        $expiryDate->modify('+24 hours');
    } else {
        // Untuk membership reguler, berlaku berdasarkan bulan
        $durationStr = $memberData['durasiMembership'];
        $durationMonths = intval(explode(' ', $durationStr)[0]);
        $expiryDate = clone $startDate;
        $expiryDate->modify("+{$durationMonths} months");
    }
    
    return $expiryDate;
}

// Inisialisasi variabel untuk form
$durations = ['1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan'];
if ($memberData && $memberData['jenisMembership'] === 'Visit') {
    // Jika Visit, durasi hanya 1 hari
    $durations = ['1 Hari'];
}

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew'])) {
    // Ambil data dari form
    $id_anggota = isset($_POST['id_anggota']) ? intval($_POST['id_anggota']) : 0;
    $durasi = isset($_POST['durasi']) ? $_POST['durasi'] : '';
    $metode_pembayaran = isset($_POST['metode_pembayaran']) ? $_POST['metode_pembayaran'] : '';
    $status_pembayaran = isset($_POST['status_pembayaran']) ? $_POST['status_pembayaran'] : 'pending';
    
    // Validasi input
    if (empty($id_anggota)) {
        $error = "ID anggota tidak valid.";
    } elseif (empty($durasi)) {
        $error = "Pilih durasi perpanjangan.";
    } elseif (empty($metode_pembayaran)) {
        $error = "Pilih metode pembayaran.";
    } else {
        // Ambil data anggota terbaru
        $stmt = $conn->prepare("SELECT * FROM membership WHERE id = ?");
        $stmt->bind_param("i", $id_anggota);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $memberData = $result->fetch_assoc();
            
            // Hitung tanggal mulai baru dan tanggal berakhir
            $currentDate = new DateTime();
            $expiryDate = calculateExpiryDate($memberData);
            
            // Jika membership belum berakhir, perpanjang dari tanggal berakhir
            // Jika sudah berakhir, mulai dari sekarang
            $newStartDate = ($currentDate < $expiryDate) ? $expiryDate : $currentDate;
            
            // Format tanggal mulai baru
            $newStartDateStr = $newStartDate->format('Y-m-d H:i:s');
            
            // Hitung jumlah pembayaran berdasarkan jenis membership dan durasi
            $harga_per_bulan = 0;
            switch($memberData['jenisMembership']) {
                case 'Visit':
                    $harga_per_bulan = 25000;
                    break;
                case 'Individual':
                    $harga_per_bulan = 95000;
                    break;
                case 'Squad':
                    $harga_per_bulan = 300000;
                    break;
                default:
                    $harga_per_bulan = 0;
            }
            
            // Tentukan jumlah pembayaran
            $jumlah = 0;
            if ($memberData['jenisMembership'] === 'Visit') {
                // Untuk Visit, jumlah tetap harga untuk satu hari
                $jumlah = $harga_per_bulan;
                $keterangan_durasi = "1 Hari";
            } else {
                // Untuk jenis membership lain, konversi durasi ke angka bulan
                $durasi_bulan = 1; // Default 1 bulan
                if ($durasi == '3 Bulan') {
                    $durasi_bulan = 3;
                } elseif ($durasi == '6 Bulan') {
                    $durasi_bulan = 6;
                } elseif ($durasi == '12 Bulan') {
                    $durasi_bulan = 12;
                }
                
                // Hitung total biaya membership (harga per bulan * durasi)
                $jumlah = $harga_per_bulan * $durasi_bulan;
                $keterangan_durasi = "$durasi_bulan Bulan";
            }
            
            // Mulai transaksi database
            $conn->begin_transaction();
            
            try {
                // Update tanggal mulai dan durasi membership
                $updateQuery = "UPDATE membership SET tanggalMulai = ?, durasiMembership = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("ssi", $newStartDateStr, $durasi, $id_anggota);
                $updateStmt->execute();
                
                // Buat catatan pembayaran
                if ($memberData['jenisMembership'] === 'Visit') {
                    $keterangan_pembayaran = "Perpanjangan membership Visit (1 Hari) sebesar Rp" . number_format($jumlah, 0, ',', '.');
                } else {
                    $durasi_bulan = intval(explode(' ', $durasi)[0]);
                    $keterangan_pembayaran = "Perpanjangan membership " . $memberData['jenisMembership'] . " dengan durasi $durasi (Rp" . number_format($harga_per_bulan, 0, ',', '.') . " x $durasi_bulan bulan)";
                }
                
                // Tambahkan pembayaran ke tabel payments
                $paymentQuery = "INSERT INTO payments (id_anggota, jumlah, metode_pembayaran, status, tanggal, keterangan) 
                                VALUES (?, ?, ?, ?, NOW(), ?)";
                $paymentStmt = $conn->prepare($paymentQuery);
                $paymentStmt->bind_param("idsss", $id_anggota, $jumlah, $metode_pembayaran, $status_pembayaran, $keterangan_pembayaran);
                $paymentStmt->execute();
                
                // Commit transaksi
                $conn->commit();
                
                $success = "Membership berhasil diperpanjang!";
                
                // Arahkan kembali ke halaman detail anggota
                header("Location: admin-member-view.php?id=" . $id_anggota . "&success=renewal");
                exit();
                
            } catch (Exception $e) {
                // Rollback transaksi jika terjadi kesalahan
                $conn->rollback();
                $error = "Gagal memperpanjang membership: " . $e->getMessage();
            }
        } else {
            $error = "Data anggota tidak ditemukan.";
        }
    }
}

// Hitung tanggal berakhir untuk tampilan
$expiryDate = null;
if ($memberData) {
    $expiryDate = calculateExpiryDate($memberData);
    
    // Cek apakah membership sudah berakhir
    $now = new DateTime();
    $isExpired = ($now > $expiryDate);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpanjang Membership - FREEDOM GYM</title>
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
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .btn-primary {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #c82333;
            border-color: #c82333;
        }
        .membership-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .membership-info .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .expired-badge {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .active-badge {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0">FREEDOM GYM</h4>
            <p class="text-muted mb-0">Panel Admin</p>
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

    <!-- Konten Utama -->
    <div class="content">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Perpanjang Membership</h3>
            <a href="admin-member-view.php?id=<?php echo $id_anggota; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if(!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if($memberData): ?>
                    <!-- Informasi Membership Saat Ini -->
                    <h5 class="mb-3">Informasi Membership Saat Ini</h5>
                    <div class="membership-info">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Nama</div>
                                    <div><?php echo htmlspecialchars($memberData['nama']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Jenis Membership</div>
                                    <div><?php echo htmlspecialchars($memberData['jenisMembership']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Tanggal Mulai</div>
                                    <div><?php echo formatDateTime($memberData['tanggalMulai']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Durasi</div>
                                    <div><?php echo htmlspecialchars(getDurationText($memberData['durasiMembership'])); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Tanggal Berakhir</div>
                                    <div>
                                        <?php echo $expiryDate ? $expiryDate->format('d F Y, H:i:s') : 'Tidak tersedia'; ?>
                                        <?php if (isset($isExpired)): ?>
                                            <span class="ms-2 <?php echo $isExpired ? 'expired-badge' : 'active-badge'; ?>">
                                                <?php echo $isExpired ? 'KADALUARSA' : 'AKTIF'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Status</div>
                                    <div>
                                        <?php 
                                        if ($memberData['secondsRemaining'] > 0) {
                                            echo '<span class="active-badge">AKTIF</span> - Sisa waktu: ';
                                            $days = floor($memberData['secondsRemaining'] / (60 * 60 * 24));
                                            $hours = floor(($memberData['secondsRemaining'] % (60 * 60 * 24)) / (60 * 60));
                                            $minutes = floor(($memberData['secondsRemaining'] % (60 * 60)) / 60);
                                            
                                            if ($days > 0) {
                                                echo "$days hari ";
                                            }
                                            if ($hours > 0 || $days > 0) {
                                                echo "$hours jam ";
                                            }
                                            echo "$minutes menit";
                                        } else {
                                            echo '<span class="expired-badge">KADALUARSA</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Perpanjangan -->
                    <h5 class="mb-3">Form Perpanjangan Membership</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="id_anggota" value="<?php echo $id_anggota; ?>">
                        
                        <div class="row">
                            <!-- Durasi Perpanjangan -->
                            <div class="col-md-6 mb-3">
                                <label for="durasi" class="form-label">Durasi Perpanjangan</label>
                                <select class="form-select" id="durasi" name="durasi" required>
                                    <option value="">-- Pilih Durasi --</option>
                                    <?php foreach($durations as $duration): ?>
                                        <option value="<?php echo $duration; ?>"><?php echo $duration; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Metode Pembayaran -->
                            <div class="col-md-6 mb-3">
                                <label for="metode_pembayaran" class="form-label">Metode Pembayaran</label>
                                <select class="form-select" id="metode_pembayaran" name="metode_pembayaran" required>
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="Credit Card">Kartu Kredit</option>
                                    <option value="Debit Card">Kartu Debit</option>
                                    <option value="QRIS">QRIS</option>
                                    <option value="E-Wallet">E-Wallet</option>
                                </select>
                            </div>

                            <!-- Status Pembayaran -->
                            <div class="col-md-6 mb-3">
                                <label for="status_pembayaran" class="form-label">Status Pembayaran</label>
                                <select class="form-select" id="status_pembayaran" name="status_pembayaran" required>
                                    <option value="paid">Dibayar</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            
                            <!-- Total Pembayaran (display only) -->
                            <div class="col-md-6 mb-3">
                                <label for="total_pembayaran" class="form-label">Total Pembayaran</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="total_pembayaran" readonly>
                                </div>
                                <small class="text-muted">Total akan dihitung berdasarkan jenis membership dan durasi</small>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="renew" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Perpanjang Membership
                            </button>
                            <a href="admin-member-view.php?id=<?php echo $id_anggota; ?>" class="btn btn-light ms-2">Batal</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Data anggota tidak ditemukan atau terjadi kesalahan.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS dan jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Fungsi untuk menghitung total pembayaran
        function updateTotalPembayaran() {
            let durasi = $('#durasi').val();
            let jenisMembership = "<?php echo isset($memberData['jenisMembership']) ? $memberData['jenisMembership'] : ''; ?>";
            let hargaPerBulan = 0;
            
            // Tentukan harga per bulan berdasarkan jenis membership
            switch(jenisMembership) {
                case 'Visit':
                    hargaPerBulan = 25000;
                    break;
                case 'Individual':
                    hargaPerBulan = 95000;
                    break;
                case 'Squad':
                    hargaPerBulan = 300000;
                    break;
                default:
                    hargaPerBulan = 0;
            }
            
            let total = 0;
            
            // Hitung total berdasarkan durasi
            if (jenisMembership === 'Visit') {
                // Untuk Visit, total = harga per hari
                total = hargaPerBulan;
            } else if (durasi) {
                // Untuk membership reguler, total = harga per bulan * durasi bulan
                let durasiAngka = 1;
                if (durasi === '3 Bulan') durasiAngka = 3;
                else if (durasi === '6 Bulan') durasiAngka = 6;
                else if (durasi === '12 Bulan') durasiAngka = 12;
                
                total = hargaPerBulan * durasiAngka;
            }
            
            // Format total ke format Rupiah
            $('#total_pembayaran').val(formatRupiah(total));
        }
        
        // Fungsi untuk format angka ke Rupiah
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }
        
        // Update total saat durasi berubah
        $('#durasi').on('change', updateTotalPembayaran);
        
        // Inisialisasi
        updateTotalPembayaran();
    });
    </script>
</body>
</html>
<?php
// Tutup koneksi
$conn->close();
?>