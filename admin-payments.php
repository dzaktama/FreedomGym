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

// Buat tabel pembayaran jika belum ada
$create_payments_table = "CREATE TABLE IF NOT EXISTS `payments` (
    `id_payment` int(11) NOT NULL AUTO_INCREMENT,
    `id_anggota` int(11) NOT NULL,
    `jumlah` decimal(10,2) NOT NULL,
    `metode_pembayaran` varchar(50) NOT NULL,
    `status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
    `tanggal` date NOT NULL,
    `keterangan` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id_payment`),
    KEY `id_anggota` (`id_anggota`),
    CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `membership` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

$conn->query($create_payments_table);

// Pengaturan pagination
$items_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Fungsionalitas pencarian
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " WHERE m.nama LIKE '%$search%' OR p.metode_pembayaran LIKE '%$search%' OR p.status LIKE '%$search%'";
}

// Filter berdasarkan status
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
if (!empty($status_filter)) {
    $search_condition = empty($search_condition) ? " WHERE p.status = '$status_filter'" : $search_condition . " AND p.status = '$status_filter'";
}

// Hitung total data untuk pagination
$count_sql = "SELECT COUNT(*) as total FROM payments p JOIN membership m ON p.id_anggota = m.id" . $search_condition;
$count_result = $conn->query($count_sql);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Ambil data pembayaran
$sql = "SELECT p.id_payment, p.id_anggota, m.nama, p.jumlah, p.metode_pembayaran, p.status, p.tanggal, p.keterangan 
        FROM payments p
        JOIN membership m ON p.id_anggota = m.id" . 
        $search_condition . 
        " ORDER BY p.tanggal DESC, p.id_payment DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

// Jika tabel kosong, masukkan data sampel
if ($total_items == 0) {
    // Periksa apakah ada anggota terlebih dahulu
    $check_members = "SELECT id FROM membership";
    $members_result = $conn->query($check_members);
    
    if ($members_result->num_rows > 0) {
        $member_ids = [];
        while($row = $members_result->fetch_assoc()) {
            $member_ids[] = $row['id'];
        }
        
        // Contoh metode pembayaran
        $payment_methods = ['Cash', 'Transfer Bank', 'Credit Card', 'QRIS'];
        
        // Contoh status
        $statuses = ['paid', 'pending', 'failed'];
        
        // Masukkan 10 pembayaran sampel
        for ($i = 0; $i < 10; $i++) {
            $member_id = $member_ids[array_rand($member_ids)];
            $amount = rand(25, 300) * 1000;
            $method = $payment_methods[array_rand($payment_methods)];
            $status = $statuses[array_rand($statuses)];
            $date = date('Y-m-d', strtotime("-" . rand(0, 30) . " days"));
            
            $insert_sample = "INSERT INTO payments (id_anggota, jumlah, metode_pembayaran, status, tanggal, keterangan)
                             VALUES ($member_id, $amount, '$method', '$status', '$date', '')";
            $conn->query($insert_sample);
        }
        
        // Perbarui data
        $count_result = $conn->query($count_sql);
        $total_items = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_items / $items_per_page);
        $result = $conn->query($sql);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - FREEDOM GYM</title>
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
        .table td, .table th {
            vertical-align: middle;
        }
        .search-bar {
            max-width: 300px;
        }
        .status-badge {
            padding: 6px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .status-paid {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .filter-btn {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #dee2e6;
            background-color: white;
            color: #6c757d;
            transition: all 0.2s;
        }
        .filter-btn:hover, .filter-btn.active {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
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
            <h3 class="mb-0">Pembayaran</h3>
            <a href="admin-payment-add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Pembayaran
            </a>
        </div>

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

        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">Daftar Pembayaran</h5>
                    </div>
                    <div class="col-md-6">
                        <form action="" method="GET" class="d-flex justify-content-end">
                            <div class="input-group search-bar">
                                <input type="text" class="form-control" placeholder="Cari pembayaran..." name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Status -->
                <div class="filters">
                    <a href="admin-payments.php" class="filter-btn <?php echo empty($status_filter) ? 'active' : ''; ?>">
                        Semua
                    </a>
                    <a href="admin-payments.php?status=paid<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $status_filter === 'paid' ? 'active' : ''; ?>">
                        Dibayar
                    </a>
                    <a href="admin-payments.php?status=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                        Pending
                    </a>
                    <a href="admin-payments.php?status=failed<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $status_filter === 'failed' ? 'active' : ''; ?>">
                        Gagal
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Anggota</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $status_class = '';
                                    switch ($row['status']) {
                                        case 'paid':
                                            $status_class = 'status-paid';
                                            $status_text = 'Dibayar';
                                            break;
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            $status_text = 'Pending';
                                            break;
                                        case 'failed':
                                            $status_class = 'status-failed';
                                            $status_text = 'Gagal';
                                            break;
                                        default:
                                            $status_class = '';
                                            $status_text = $row['status'];
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $row['id_payment']; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td>Rp<?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($row['metode_pembayaran']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                                        <td><?php echo !empty($row['keterangan']) ? htmlspecialchars(substr($row['keterangan'], 0, 30)) . (strlen($row['keterangan']) > 30 ? '...' : '') : '-'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">Tidak ada data pembayaran</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Tutup koneksi
$conn->close();
?>