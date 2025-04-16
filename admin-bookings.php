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

// Check if pemesanan_kelas table exists
$create_booking_table = "CREATE TABLE IF NOT EXISTS `pemesanan_kelas` (
    `id_pemesanan` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `kelas_id` int(11) NOT NULL,
    `tanggal_pesan` datetime NOT NULL,
    `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id_pemesanan`),
    KEY `user_id` (`user_id`),
    KEY `kelas_id` (`kelas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

$conn->query($create_booking_table);

// Process status update request
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    
    $update_sql = "UPDATE pemesanan_kelas SET status = ? WHERE id_pemesanan = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        $_SESSION['admin_message'] = "Status pemesanan berhasil diperbarui!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal memperbarui status: " . $conn->error;
        $_SESSION['admin_message_type'] = "danger";
    }
    
    $stmt->close();
    // Redirect to remove the post parameter
    header("Location: admin-bookings.php");
    exit();
}

// Process delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    $delete_sql = "DELETE FROM pemesanan_kelas WHERE id_pemesanan = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id_to_delete);
    
    if ($stmt->execute()) {
        $_SESSION['admin_message'] = "Pemesanan berhasil dihapus!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal menghapus pemesanan: " . $conn->error;
        $_SESSION['admin_message_type'] = "danger";
    }
    
    $stmt->close();
    // Redirect to remove the get parameter
    header("Location: admin-bookings.php");
    exit();
}

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " WHERE m.nama LIKE '%$search%' OR gc.nama_kelas LIKE '%$search%'";
}

// Filter by status
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
if (!empty($status_filter)) {
    $search_condition = empty($search_condition) 
                      ? " WHERE pk.status = '$status_filter'" 
                      : $search_condition . " AND pk.status = '$status_filter'";
}

// Filter by class
$class_filter = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
if ($class_filter > 0) {
    $search_condition = empty($search_condition) 
                      ? " WHERE pk.kelas_id = $class_filter" 
                      : $search_condition . " AND pk.kelas_id = $class_filter";
}

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM pemesanan_kelas pk 
             JOIN membership m ON pk.user_id = m.id
             JOIN gym_classes gc ON pk.kelas_id = gc.id_class" . $search_condition;
$count_result = $conn->query($count_sql);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get bookings data
$sql = "SELECT pk.id_pemesanan, pk.user_id, m.nama as member_name, 
        pk.kelas_id, gc.nama_kelas, gc.hari, gc.jam_mulai, gc.jam_selesai, 
        pk.tanggal_pesan, pk.status 
        FROM pemesanan_kelas pk 
        JOIN membership m ON pk.user_id = m.id
        JOIN gym_classes gc ON pk.kelas_id = gc.id_class" . 
        $search_condition . 
        " ORDER BY pk.tanggal_pesan DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

// Get class list for filter
$class_sql = "SELECT id_class, nama_kelas FROM gym_classes ORDER BY nama_kelas";
$class_result = $conn->query($class_sql);
$class_list = [];
if ($class_result->num_rows > 0) {
    while ($row = $class_result->fetch_assoc()) {
        $class_list[$row['id_class']] = $row['nama_kelas'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemesanan Kelas - FREEDOM GYM</title>
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
        .btn-action {
            padding: 5px 10px;
            margin-right: 5px;
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
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
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
        .class-filter {
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
            <li><a href="admin-bookings.php" class="active"><i class="bi bi-bookmark-check"></i> Pemesanan Kelas</a></li>
            <li><a href="admin-reports.php"><i class="bi bi-clipboard-data"></i> Laporan</a></li>
            <li><a href="admin-settings.php"><i class="bi bi-gear"></i> Pengaturan</a></li>
            <li><a href="logout-admin.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Kelola Pemesanan Kelas</h3>
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
                        <h5 class="mb-0">Daftar Pemesanan Kelas</h5>
                    </div>
                    <div class="col-md-6">
                        <form action="" method="GET" class="d-flex justify-content-end">
                            <div class="input-group search-bar">
                                <input type="text" class="form-control" placeholder="Cari pemesanan..." name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Status Filters -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="filters">
                            <a href="admin-bookings.php" class="filter-btn <?php echo empty($status_filter) ? 'active' : ''; ?>">
                                Semua
                            </a>
                            <a href="admin-bookings.php?status=confirmed<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $class_filter > 0 ? '&class_id=' . $class_filter : ''; ?>" class="filter-btn <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">
                                Terkonfirmasi
                            </a>
                            <a href="admin-bookings.php?status=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $class_filter > 0 ? '&class_id=' . $class_filter : ''; ?>" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                                Pending
                            </a>
                            <a href="admin-bookings.php?status=cancelled<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $class_filter > 0 ? '&class_id=' . $class_filter : ''; ?>" class="filter-btn <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                                Dibatalkan
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <!-- Class Filter -->
                        <form action="" method="GET" class="d-flex justify-content-end">
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <?php if (!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                            <div class="input-group class-filter">
                                <select class="form-select" name="class_id" onchange="this.form.submit()">
                                    <option value="0">Semua Kelas</option>
                                    <?php foreach ($class_list as $id => $name): ?>
                                        <option value="<?php echo $id; ?>" <?php echo $class_filter == $id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Member</th>
                                <th>Nama Kelas</th>
                                <th>Jadwal</th>
                                <th>Tanggal Pesan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $status_class = '';
                                    switch ($row['status']) {
                                        case 'confirmed':
                                            $status_class = 'status-confirmed';
                                            $status_text = 'Terkonfirmasi';
                                            break;
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            $status_text = 'Pending';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'status-cancelled';
                                            $status_text = 'Dibatalkan';
                                            break;
                                        default:
                                            $status_class = '';
                                            $status_text = $row['status'];
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $row['id_pemesanan']; ?></td>
                                        <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_kelas']); ?></td>
                                        <td><?php echo htmlspecialchars($row['hari']) . ', ' . date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])); ?></td>
                                        <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_pesan'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $row['id_pemesanan']; ?>" title="Ubah Status">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="#" class="btn btn-sm btn-danger btn-action" title="Hapus" 
                                               onclick="confirmDelete(<?php echo $row['id_pemesanan']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            
                                            <!-- Update Status Modal -->
                                            <div class="modal fade" id="updateStatusModal<?php echo $row['id_pemesanan']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Ubah Status Pemesanan</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="" method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="booking_id" value="<?php echo $row['id_pemesanan']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="status" class="form-label">Status</label>
                                                                    <select class="form-select" id="status" name="status">
                                                                        <option value="pending" <?php echo $row['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="confirmed" <?php echo $row['status'] === 'confirmed' ? 'selected' : ''; ?>>Terkonfirmasi</option>
                                                                        <option value="cancelled" <?php echo $row['status'] === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <p><strong>Detail Pemesanan:</strong></p>
                                                                    <p>Nama Member: <?php echo htmlspecialchars($row['member_name']); ?></p>
                                                                    <p>Kelas: <?php echo htmlspecialchars($row['nama_kelas']); ?></p>
                                                                    <p>Jadwal: <?php echo htmlspecialchars($row['hari']) . ', ' . date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])); ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" name="update_status" class="btn btn-primary">Simpan Perubahan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">Tidak ada data pemesanan kelas</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $class_filter > 0 ? '&class_id=' . $class_filter : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $class_filter > 0 ? '&class_id=' . $class_filter : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $class_filter > 0 ? '&class_id=' . $class_filter : ''; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Pemesanan</h5>
                        <?php
                        $stats_sql = "SELECT 
                                      COUNT(*) as total,
                                      SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                                      SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                                      SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                                      FROM pemesanan_kelas";
                        $stats_result = $conn->query($stats_sql);
                        $stats = $stats_result->fetch_assoc();
                        ?>
                        <h3 class="mt-3 mb-4"><?php echo $stats['total']; ?></h3>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="p-2 rounded status-confirmed">
                                    <h6 class="mb-0"><?php echo $stats['confirmed']; ?></h6>
                                    <small>Terkonfirmasi</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded status-pending">
                                    <h6 class="mb-0"><?php echo $stats['pending']; ?></h6>
                                    <small>Pending</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded status-cancelled">
                                    <h6 class="mb-0"><?php echo $stats['cancelled']; ?></h6>
                                    <small>Dibatalkan</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Kelas Terpopuler</h5>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nama Kelas</th>
                                        <th>Hari</th>
                                        <th>Jam</th>
                                        <th>Total Pemesanan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $popular_sql = "SELECT gc.nama_kelas, gc.hari, gc.jam_mulai, gc.jam_selesai, COUNT(pk.id_pemesanan) as booking_count
                                                   FROM gym_classes gc
                                                   LEFT JOIN pemesanan_kelas pk ON gc.id_class = pk.kelas_id AND pk.status != 'cancelled'
                                                   GROUP BY gc.id_class
                                                   ORDER BY booking_count DESC
                                                   LIMIT 5";
                                    $popular_result = $conn->query($popular_sql);
                                    
                                    if ($popular_result && $popular_result->num_rows > 0) {
                                        while ($row = $popular_result->fetch_assoc()) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($row['nama_kelas']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['hari']) . '</td>';
                                            echo '<td>' . date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])) . '</td>';
                                            echo '<td>' . $row['booking_count'] . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus pemesanan ini?</p>
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
        // Function to show delete confirmation modal
        function confirmDelete(id) {
            document.getElementById('deleteLink').href = 'admin-bookings.php?delete=' + id;
            
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>