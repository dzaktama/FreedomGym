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

// Get membership statistics
$sql_membership_stats = "SELECT jenisMembership, COUNT(*) as count FROM membership GROUP BY jenisMembership";
$result_membership_stats = $conn->query($sql_membership_stats);

$membership_stats = [];
$membership_total = 0;
if ($result_membership_stats->num_rows > 0) {
    while($row = $result_membership_stats->fetch_assoc()) {
        $membership_stats[$row['jenisMembership']] = $row['count'];
        $membership_total += $row['count'];
    }
}

// Get gender distribution
$sql_gender_stats = "SELECT jenisKelamin, COUNT(*) as count FROM membership GROUP BY jenisKelamin";
$result_gender_stats = $conn->query($sql_gender_stats);

$gender_stats = [];
if ($result_gender_stats->num_rows > 0) {
    while($row = $result_gender_stats->fetch_assoc()) {
        $gender_stats[$row['jenisKelamin']] = $row['count'];
    }
}

// Get monthly registrations (last 6 months)
$months = [];
$registrations = [];

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $sql_monthly = "SELECT COUNT(*) as count FROM membership WHERE tanggalMulai BETWEEN '$start_date' AND '$end_date'";
    $result_monthly = $conn->query($sql_monthly);
    $row_monthly = $result_monthly->fetch_assoc();
    
    $registrations[] = $row_monthly['count'];
}

// Get payment statistics if table exists
$payment_stats = [];
$payment_total = 0;

$check_payments_table = $conn->query("SHOW TABLES LIKE 'payments'");
if ($check_payments_table->num_rows > 0) {
    $sql_payment_stats = "SELECT status, COUNT(*) as count, SUM(jumlah) as total FROM payments GROUP BY status";
    $result_payment_stats = $conn->query($sql_payment_stats);
    
    if ($result_payment_stats->num_rows > 0) {
        while($row = $result_payment_stats->fetch_assoc()) {
            $payment_stats[$row['status']] = [
                'count' => $row['count'],
                'total' => $row['total']
            ];
            if ($row['status'] == 'paid') {
                $payment_total = $row['total'];
            }
        }
    }
}

// Get class distribution if table exists
$class_stats = [];

$check_classes_table = $conn->query("SHOW TABLES LIKE 'gym_classes'");
if ($check_classes_table->num_rows > 0) {
    $sql_class_stats = "SELECT nama_kelas, COUNT(*) as count FROM gym_classes GROUP BY nama_kelas ORDER BY count DESC LIMIT 5";
    $result_class_stats = $conn->query($sql_class_stats);
    
    if ($result_class_stats->num_rows > 0) {
        while($row = $result_class_stats->fetch_assoc()) {
            $class_stats[$row['nama_kelas']] = $row['count'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - FREEDOM GYM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .summary-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .summary-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .summary-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .statistics-table th {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .text-primary-red {
            color: #dc3545;
        }
        .report-actions {
            display: flex;
            gap: 10px;
        }
        .filter-date {
            max-width: 200px;
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
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Laporan dan Statistik</h3>
            <div class="report-actions">
                <button class="btn btn-outline-secondary" onclick="printReport()">
                    <i class="bi bi-printer"></i> Cetak
                </button>
                <button class="btn btn-outline-primary" onclick="exportPDF()">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </button>
                <button class="btn btn-outline-success" onclick="exportExcel()">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </button>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="summary-card">
                    <div class="summary-icon text-primary-red">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="summary-title">Total Anggota</div>
                    <div class="summary-value"><?php echo $membership_total; ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="summary-card">
                    <div class="summary-icon text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="summary-title">Total Pendapatan</div>
                    <div class="summary-value">Rp<?php echo number_format($payment_total ?? 0, 0, ',', '.'); ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="summary-card">
                    <div class="summary-icon text-info">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="summary-title">Total Kelas</div>
                    <div class="summary-value"><?php echo array_sum($class_stats); ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="summary-card">
                    <div class="summary-icon text-warning">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="summary-title">Pertumbuhan Bulanan</div>
                    <div class="summary-value">
                        <?php 
                            $growth = 0;
                            if (count($registrations) > 1 && $registrations[count($registrations) - 2] > 0) {
                                $growth = (($registrations[count($registrations) - 1] - $registrations[count($registrations) - 2]) / $registrations[count($registrations) - 2]) * 100;
                            }
                            echo number_format($growth, 1) . '%';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Tables -->
        <div class="row">
            <!-- Membership Distribution Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Distribusi Jenis Membership</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="membershipChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gender Distribution Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Distribusi Jenis Kelamin</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="genderChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Registrations Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Pendaftaran Bulanan (6 Bulan Terakhir)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="registrationsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Distribution -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Kelas Terpopuler</h5>
                    </div>
                    <div class="card-body">
                        <table class="table statistics-table">
                            <thead>
                                <tr>
                                    <th>Nama Kelas</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_stats as $class => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($class); ?></td>
                                        <td class="text-end"><?php echo $count; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($class_stats)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center">Tidak ada data kelas</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Detailed Membership Table -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Detail Membership</h5>
                    </div>
                    <div class="card-body">
                        <table class="table statistics-table">
                            <thead>
                                <tr>
                                    <th>Jenis Membership</th>
                                    <th class="text-end">Jumlah Anggota</th>
                                    <th class="text-end">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($membership_stats as $type => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($type); ?></td>
                                        <td class="text-end"><?php echo $count; ?></td>
                                        <td class="text-end"><?php echo number_format(($count / $membership_total) * 100, 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($membership_stats)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Tidak ada data membership</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Statistics -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Statistik Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <table class="table statistics-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th class="text-end">Jumlah Transaksi</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $status_labels = [
                                    'paid' => 'Dibayar',
                                    'pending' => 'Pending',
                                    'failed' => 'Gagal'
                                ];
                                foreach ($payment_stats as $status => $data): ?>
                                    <tr>
                                        <td><?php echo $status_labels[$status] ?? htmlspecialchars($status); ?></td>
                                        <td class="text-end"><?php echo $data['count']; ?></td>
                                        <td class="text-end">Rp<?php echo number_format($data['total'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($payment_stats)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Tidak ada data pembayaran</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart Initialization Scripts -->
    <script>
        // Membership Distribution Chart
        const membershipCtx = document.getElementById('membershipChart').getContext('2d');
        const membershipChart = new Chart(membershipCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($membership_stats)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($membership_stats)); ?>,
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#ffc107',
                        '#20c997',
                        '#0dcaf0'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Gender Distribution Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        const genderChart = new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($gender_stats)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($gender_stats)); ?>,
                    backgroundColor: [
                        '#0d6efd',
                        '#d63384',
                        '#6c757d'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Monthly Registrations Chart
        const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
        const registrationsChart = new Chart(registrationsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Jumlah Pendaftaran',
                    data: <?php echo json_encode($registrations); ?>,
                    backgroundColor: '#dc3545',
                    borderColor: '#dc3545',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Print Report Function
        function printReport() {
            window.print();
        }

        // Export to PDF Function (placeholder)
        function exportPDF() {
            alert('Fungsi export PDF belom bisa, gangerti cara bikinnya hehe.');
        }

        // Export to Excel Function (placeholder)
        function exportExcel() {
            alert('Fungsi export PDF belom bisa, gangerti cara bikinnya hehe.');
        }
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>