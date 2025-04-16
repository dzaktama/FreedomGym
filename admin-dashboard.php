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

// Fetch statistics for dashboard
// Total Members
$sql_total_members = "SELECT COUNT(*) as total FROM membership";
$result_total_members = $conn->query($sql_total_members);
$total_members = ($result_total_members->num_rows > 0) ? $result_total_members->fetch_assoc()['total'] : 0;

// Active Members
$sql_active_members = "SELECT COUNT(*) as active FROM membership WHERE CURRENT_DATE() < DATE_ADD(tanggalMulai, INTERVAL SUBSTRING_INDEX(durasiMembership, ' ', 1) MONTH)";
$result_active_members = $conn->query($sql_active_members);
$active_members = ($result_active_members->num_rows > 0) ? $result_active_members->fetch_assoc()['active'] : 0;

// Recent Members (last 5)
$sql_recent_members = "SELECT id, nama, jenisMembership, tanggalMulai, durasiMembership FROM membership ORDER BY tanggalMulai DESC LIMIT 5";
$result_recent_members = $conn->query($sql_recent_members);

// Check if payments table exists
$check_payments_table = $conn->query("SHOW TABLES LIKE 'payments'");
$payments_exist = $check_payments_table->num_rows > 0;

// Recent Payments (last 5)
if ($payments_exist) {
    $sql_recent_payments = "SELECT p.id_payment as id, m.nama as member_name, p.jumlah as amount, p.tanggal as date, p.status 
                           FROM payments p 
                           JOIN membership m ON p.id_anggota = m.id 
                           ORDER BY p.tanggal DESC, p.id_payment DESC LIMIT 5";
    $result_recent_payments = $conn->query($sql_recent_payments);
    
    $recent_payments = [];
    if ($result_recent_payments->num_rows > 0) {
        while($row = $result_recent_payments->fetch_assoc()) {
            $status_text = '';
            switch ($row['status']) {
                case 'paid':
                    $status_text = 'Dibayar';
                    break;
                case 'pending':
                    $status_text = 'Pending';
                    break;
                case 'failed':
                    $status_text = 'Gagal';
                    break;
                default:
                    $status_text = $row['status'];
            }
            
            $recent_payments[] = [
                "id" => $row['id'],
                "member_name" => $row['member_name'],
                "amount" => "Rp" . number_format($row['amount'], 0, ',', '.'),
                "date" => date('d M Y', strtotime($row['date'])),
                "status" => $status_text,
                "status_class" => $row['status'] == 'paid' ? 'badge-paid' : ($row['status'] == 'pending' ? 'badge-pending' : 'badge-danger')
            ];
        }
    }
} else {
    // Dummy data if payments table doesn't exist
    $recent_payments = [
        ["id" => 1, "member_name" => "Prasetyo Aditomo", "amount" => "Rp300.000", "date" => "30 March 2025", "status" => "Dibayar", "status_class" => "badge-paid"],
        ["id" => 2, "member_name" => "Budi Santoso", "amount" => "Rp95.000", "date" => "29 March 2025", "status" => "Dibayar", "status_class" => "badge-paid"],
        ["id" => 3, "member_name" => "Dewi Lestari", "amount" => "Rp25.000", "date" => "28 March 2025", "status" => "Dibayar", "status_class" => "badge-paid"],
        ["id" => 4, "member_name" => "Ahmad Fauzi", "amount" => "Rp300.000", "date" => "27 March 2025", "status" => "Dibayar", "status_class" => "badge-paid"],
        ["id" => 5, "member_name" => "Siti Nurhaliza", "amount" => "Rp95.000", "date" => "26 March 2025", "status" => "Dibayar", "status_class" => "badge-paid"]
    ];
}

// Check if gym_classes table exists
$check_classes_table = $conn->query("SHOW TABLES LIKE 'gym_classes'");
$classes_exist = $check_classes_table->num_rows > 0;

// Get total number of classes
$total_classes = 0;
if ($classes_exist) {
    $sql_total_classes = "SELECT COUNT(*) as total FROM gym_classes WHERE status = 'active'";
    $result_total_classes = $conn->query($sql_total_classes);
    $total_classes = ($result_total_classes->num_rows > 0) ? $result_total_classes->fetch_assoc()['total'] : 0;
} else {
    $total_classes = 25; // Default value if gym_classes table doesn't exist
}

// Membership Types Stats
$sql_membership_types = "SELECT jenisMembership, COUNT(*) as count FROM membership GROUP BY jenisMembership";
$result_membership_types = $conn->query($sql_membership_types);
$membership_types = [];
if ($result_membership_types->num_rows > 0) {
    while($row = $result_membership_types->fetch_assoc()) {
        $membership_types[] = $row;
    }
}

// Get total revenue
$total_revenue = 0;
if ($payments_exist) {
    $sql_total_revenue = "SELECT SUM(jumlah) as total FROM payments WHERE status = 'paid'";
    $result_total_revenue = $conn->query($sql_total_revenue);
    $total_revenue = ($result_total_revenue->num_rows > 0) ? $result_total_revenue->fetch_assoc()['total'] : 0;
} else {
    $total_revenue = 4500000; 
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FREEDOM GYM</title>
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
        .dashboard-header {
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
        .card-stats {
            padding: 20px;
            text-align: center;
            background-color: white;
            border-left: 5px solid;
        }
        .card-stats.card-members {
            border-color: #dc3545;
        }
        .card-stats.card-active {
            border-color: #28a745;
        }
        .card-stats.card-revenue {
            border-color: #ffc107;
        }
        .card-stats.card-classes {
            border-color: #17a2b8;
        }
        .card-stats .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .card-stats .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .card-stats .stats-text {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .recent-table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .badge-status {
            padding: 8px 12px;
            border-radius: 50px;
            font-weight: 600;
        }
        .badge-paid {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
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
        <div class="dashboard-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Dashboard</h3>
            <div class="user-info">
                <span>Selamat Datang, <strong><?php echo $_SESSION['admin_nama']; ?></strong></span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card-stats card-members">
                    <div class="stats-icon text-danger">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-number"><?php echo $total_members; ?></div>
                    <div class="stats-text">Total Anggota</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats card-active">
                    <div class="stats-icon text-success">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="stats-number"><?php echo $active_members; ?></div>
                    <div class="stats-text">Anggota Aktif</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats card-revenue">
                    <div class="stats-icon text-warning">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stats-number">Rp<?php echo number_format($total_revenue, 0, ',', '.'); ?></div>
                    <div class="stats-text">Total Pendapatan</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats card-classes">
                    <div class="stats-icon text-info">
                        <i class="bi bi-calendar2-week"></i>
                    </div>
                    <div class="stats-number"><?php echo $total_classes; ?></div>
                    <div class="stats-text">Kelas Tersedia</div>
                </div>
            </div>
        </div>

        <!-- Recent Members and Payments -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Anggota Terbaru</h5>
                        <a href="admin-members.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover recent-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama</th>
                                        <th>Jenis</th>
                                        <th>Tgl Mulai</th>
                                        <th>Durasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_recent_members && $result_recent_members->num_rows > 0): ?>
                                        <?php while($row = $result_recent_members->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                                <td><?php echo htmlspecialchars($row['jenisMembership']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($row['tanggalMulai'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['durasiMembership']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Tidak ada data anggota terbaru</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pembayaran Terbaru</h5>
                        <a href="admin-payments.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover recent-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama</th>
                                        <th>Jumlah</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_payments)): ?>
                                        <?php foreach($recent_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo $payment['id']; ?></td>
                                                <td><?php echo htmlspecialchars($payment['member_name']); ?></td>
                                                <td><?php echo $payment['amount']; ?></td>
                                                <td><?php echo $payment['date']; ?></td>
                                                <td>
                                                    <span class="badge badge-status <?php echo $payment['status_class']; ?>">
                                                        <?php echo $payment['status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Tidak ada data pembayaran terbaru</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Membership Stats and Quick Access -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Statistik Membership</h5>
                        <a href="admin-reports.php" class="btn btn-sm btn-outline-primary">Lihat Laporan</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover recent-table">
                                <thead>
                                    <tr>
                                        <th>Jenis Membership</th>
                                        <th class="text-end">Jumlah Anggota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($membership_types)): ?>
                                        <?php foreach($membership_types as $type): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($type['jenisMembership']); ?></td>
                                                <td class="text-end"><?php echo $type['count']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center">Tidak ada data statistik membership</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Akses Cepat</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="admin-member-add.php" class="btn btn-primary mb-2">
                                <i class="bi bi-person-plus"></i> Tambah Anggota Baru
                            </a>
                            <a href="admin-class-add.php" class="btn btn-success mb-2">
                                <i class="bi bi-calendar-plus"></i> Tambah Kelas Baru
                            </a>
                            <a href="admin-reports.php" class="btn btn-info mb-2">
                                <i class="bi bi-file-earmark-text"></i> Buat Laporan
                            </a>
                            <a href="admin-payment-add.php" class="btn btn-warning">
                                <i class="bi bi-cash"></i> Catat Pembayaran
                            </a>
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