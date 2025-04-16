<?php
// Mulai sesi
session_start();

// Periksa apakah admin sudah login
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in'])) {
    // Jika belum login, arahkan ke halaman login
    header("Location: admin-login.php");
    exit();
}

// Koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gym";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Inisialisasi variabel
$error = '';
$success = '';

// Ambil daftar anggota untuk dropdown
$members_query = "SELECT id, nama, email FROM membership ORDER BY nama";
$members_result = $conn->query($members_query);
$members = [];
if ($members_result && $members_result->num_rows > 0) {
    while ($row = $members_result->fetch_assoc()) {
        $members[] = $row;
    }
}

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $id_anggota = isset($_POST['id_anggota']) ? intval($_POST['id_anggota']) : 0;
    $jumlah = isset($_POST['jumlah']) ? $_POST['jumlah'] : '';
    $jumlah = str_replace(['Rp', '.', ' '], '', $jumlah); // Bersihkan format rupiah
    $jumlah = str_replace(',', '.', $jumlah); // Ganti koma dengan titik
    $metode_pembayaran = isset($_POST['metode_pembayaran']) ? $_POST['metode_pembayaran'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : '';
    $keterangan = isset($_POST['keterangan']) ? $_POST['keterangan'] : '';
    $durasi_bulan = isset($_POST['durasi_bulan']) ? intval($_POST['durasi_bulan']) : 1; // Tambahkan durasi

    // Validasi sederhana
    if (empty($id_anggota)) {
        $error = "Pilih anggota terlebih dahulu.";
    } elseif (empty($jumlah) || !is_numeric($jumlah)) {
        $error = "Jumlah pembayaran tidak valid.";
    } elseif (empty($metode_pembayaran)) {
        $error = "Pilih metode pembayaran.";
    } elseif (empty($status)) {
        $error = "Pilih status pembayaran.";
    } elseif (empty($tanggal)) {
        $error = "Tanggal pembayaran tidak boleh kosong.";
    } elseif ($durasi_bulan <= 0) {
        $error = "Durasi harus lebih dari 0 bulan.";
    } else {
        // Hitung total pembayaran berdasarkan durasi
        $total_jumlah = $jumlah * $durasi_bulan;
        
        // Tambahkan informasi durasi ke keterangan jika durasi > 1 bulan
        if ($durasi_bulan > 1) {
            $keterangan_durasi = "Pembayaran untuk $durasi_bulan bulan (Rp" . number_format($jumlah, 0, ',', '.') . " x $durasi_bulan)";
            if (empty($keterangan)) {
                $keterangan = $keterangan_durasi;
            } else {
                $keterangan = $keterangan_durasi . ". " . $keterangan;
            }
        }
        
        // Siapkan dan jalankan query
        $query = "INSERT INTO payments (id_anggota, jumlah, metode_pembayaran, status, tanggal, keterangan) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("idssss", $id_anggota, $total_jumlah, $metode_pembayaran, $status, $tanggal, $keterangan);
            
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "Pembayaran berhasil ditambahkan.";
                $_SESSION['admin_message_type'] = "success";
                header("Location: admin-payments.php");
                exit();
            } else {
                $error = "Gagal menambahkan pembayaran: " . $stmt->error;
            }
            
            $stmt->close();
        } else {
            $error = "Gagal memproses permintaan: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pembayaran - FREEDOM GYM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- jQuery dan Input Mask untuk format mata uang rupiah -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js"></script>
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
            <li><a href="admin-members.php"><i class="bi bi-people"></i> Kelola Anggota</a></li>
            <li><a href="admin-payments.php" class="active"><i class="bi bi-credit-card"></i> Pembayaran</a></li>
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
            <h3 class="mb-0">Tambah Pembayaran Baru</h3>
            <a href="admin-payments.php" class="btn btn-outline-secondary">
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

                <form method="POST" action="">
                    <div class="row">
                        <!-- Pilih Anggota -->
                        <div class="col-md-6 mb-3">
                            <label for="id_anggota" class="form-label">Anggota</label>
                            <select class="form-select" id="id_anggota" name="id_anggota" required>
                                <option value="">-- Pilih Anggota --</option>
                                <?php foreach($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['nama']); ?> (<?php echo htmlspecialchars($member['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Jumlah Pembayaran -->
                        <div class="col-md-6 mb-3">
                            <label for="jumlah" class="form-label">Jumlah Pembayaran per Bulan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="jumlah" name="jumlah" placeholder="0" required>
                            </div>
                        </div>

                        <!-- Durasi Pembayaran (Baru) -->
                        <div class="col-md-6 mb-3">
                            <label for="durasi_bulan" class="form-label">Durasi (Bulan)</label>
                            <select class="form-select" id="durasi_bulan" name="durasi_bulan" required>
                                <option value="1">1 Bulan</option>
                                <option value="3">3 Bulan</option>
                                <option value="6">6 Bulan</option>
                                <option value="12">12 Bulan</option>
                            </select>
                        </div>

                        <!-- Total Pembayaran (Baru) -->
                        <div class="col-md-6 mb-3">
                            <label for="total_pembayaran" class="form-label">Total Pembayaran</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="total_pembayaran" readonly>
                            </div>
                            <small class="text-muted">Total = Jumlah per bulan × Durasi</small>
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
                            <label for="status" class="form-label">Status Pembayaran</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="paid">Dibayar</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Gagal</option>
                            </select>
                        </div>

                        <!-- Tanggal Pembayaran -->
                        <div class="col-md-6 mb-3">
                            <label for="tanggal" class="form-label">Tanggal Pembayaran</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Keterangan -->
                        <div class="col-md-12 mb-3">
                            <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Simpan Pembayaran
                        </button>
                        <a href="admin-payments.php" class="btn btn-light ms-2">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Format input jumlah ke format rupiah
        $('#jumlah').inputmask({
            alias: 'numeric',
            groupSeparator: '.',
            radixPoint: ',',
            autoGroup: true,
            prefix: '',
            rightAlign: false,
            autoUnmask: true
        });
        
        // Fungsi untuk menghitung total pembayaran
        function updateTotalPembayaran() {
            let jumlah = $('#jumlah').val();
            let durasi = $('#durasi_bulan').val();
            
            // Bersihkan format rupiah
            jumlah = jumlah.replace(/\./g, '').replace(',', '.');
            
            // Konversi ke angka
            jumlah = parseFloat(jumlah) || 0;
            durasi = parseInt(durasi) || 1;
            
            // Hitung total
            let total = jumlah * durasi;
            
            // Format total
            $('#total_pembayaran').val(formatRupiah(total));
        }
        
        // Format angka ke format Rupiah
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }
        
        // Panggil fungsi saat input berubah
        $('#jumlah, #durasi_bulan').on('change keyup', function() {
            updateTotalPembayaran();
        });
        
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