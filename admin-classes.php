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

// Create gym_classes table if it doesn't exist
$create_classes_table = "CREATE TABLE IF NOT EXISTS `gym_classes` (
    `id_class` int(11) NOT NULL AUTO_INCREMENT,
    `nama_kelas` varchar(100) NOT NULL,
    `deskripsi` text DEFAULT NULL,
    `instruktur` varchar(100) NOT NULL,
    `hari` varchar(20) NOT NULL,
    `jam_mulai` time NOT NULL,
    `jam_selesai` time NOT NULL,
    `kapasitas` int(11) NOT NULL DEFAULT 20,
    `status` enum('active','cancelled','full') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

$conn->query($create_classes_table);

// Process delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    $delete_sql = "DELETE FROM gym_classes WHERE id_class = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $_SESSION['admin_message'] = "Kelas berhasil dihapus!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal menghapus kelas: " . $conn->error;
        $_SESSION['admin_message_type'] = "danger";
    }
    $stmt->close();
    // Redirect to remove the get parameter
    header("Location: admin-classes.php");
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
    $search_condition = " WHERE nama_kelas LIKE '%$search%' OR instruktur LIKE '%$search%' OR hari LIKE '%$search%'";
}

// Filter by day
$day_filter = isset($_GET['day']) ? $conn->real_escape_string($_GET['day']) : '';
if (!empty($day_filter)) {
    $search_condition = empty($search_condition) ? " WHERE hari = '$day_filter'" : $search_condition . " AND hari = '$day_filter'";
}

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM gym_classes" . $search_condition;
$count_result = $conn->query($count_sql);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get classes data
$sql = "SELECT * FROM gym_classes" . 
       $search_condition . 
       " ORDER BY hari ASC, jam_mulai ASC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

// If table is empty, insert sample data
if ($total_items == 0) {
    // Sample class names
    $class_names = [
        'Yoga', 'HIIT', 'Zumba', 'Pilates', 'CrossFit', 
        'Bodybuilding', 'Functional Training', 'Spinning',
        'Boxing', 'Weight Training', 'Aerobics', 'Circuit Training'
    ];
    
    // Sample descriptions
    $descriptions = [
        'Perfect for beginners looking to improve flexibility and strength.',
        'High-intensity interval training to burn maximum calories.',
        'Dance-based workout that is fun and effective for all fitness levels.',
        'Focus on core strength, stability, and body alignment.',
        'Strength and conditioning program with constantly varied functional movements.',
        'Specific weightlifting for muscle building and aesthetics.',
        'Exercises that simulate daily movements to improve strength for everyday activities.',
        'Indoor cycling workout focusing on endurance, strength, intervals, and recovery.',
        'Cardio workout that builds strength, speed, and coordination.',
        'Traditional weight training focusing on specific muscle groups.',
        'Rhythmic aerobic exercise combined with strength training.',
        'Series of exercises performed in succession with minimal rest.'
    ];
    
    // Sample instructors
    $instructors = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Williams', 'Alex Brown'];
    
    // Sample days
    $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    
    // Sample times
    $times = [
        ['06:00:00', '07:00:00'],
        ['07:30:00', '08:30:00'],
        ['09:00:00', '10:00:00'],
        ['10:30:00', '11:30:00'],
        ['15:00:00', '16:00:00'],
        ['16:30:00', '17:30:00'],
        ['18:00:00', '19:00:00'],
        ['19:30:00', '20:30:00']
    ];
    
    // Sample statuses
    $statuses = ['active', 'cancelled', 'full'];
    
    // Insert sample classes - using prepared statements to handle quotes properly
    $insert_sample = "INSERT INTO gym_classes (nama_kelas, deskripsi, instruktur, hari, jam_mulai, jam_selesai, kapasitas, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_sample);
    $stmt->bind_param("sssssssi", $class_name, $description, $instructor, $day, $start_time, $end_time, $capacity, $status);
    
    for ($i = 0; $i < 20; $i++) {
        $class_name = $class_names[array_rand($class_names)];
        $description = $descriptions[array_rand($descriptions)];
        $instructor = $instructors[array_rand($instructors)];
        $day = $days[array_rand($days)];
        $time = $times[array_rand($times)];
        $start_time = $time[0];
        $end_time = $time[1];
        $capacity = rand(10, 30);
        $status = $statuses[array_rand($statuses)];
        
        $stmt->execute();
    }
    
    $stmt->close();
    
    // Refresh the data
    $count_result = $conn->query($count_sql);
    $total_items = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_items / $items_per_page);
    $result = $conn->query($sql);
}

// Days of week in Indonesian
$days_of_week = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kelas - FREEDOM GYM</title>
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
        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-full {
            background-color: #fff3cd;
            color: #856404;
        }
        .day-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .day-btn {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #dee2e6;
            background-color: white;
            color: #6c757d;
            transition: all 0.2s;
        }
        .day-btn:hover, .day-btn.active {
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
            <h3 class="mb-0">Jadwal Kelas</h3>
            <a href="admin-class-add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Kelas
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
                        <h5 class="mb-0">Daftar Kelas</h5>
                    </div>
                    <div class="col-md-6">
                        <form action="" method="GET" class="d-flex justify-content-end">
                            <div class="input-group search-bar">
                                <input type="text" class="form-control" placeholder="Cari kelas..." name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Day Filters -->
                <div class="day-filter">
                    <a href="admin-classes.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" class="day-btn <?php echo empty($day_filter) ? 'active' : ''; ?>">
                        Semua
                    </a>
                    <?php foreach ($days_of_week as $day): ?>
                        <a href="admin-classes.php?day=<?php echo urlencode($day); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="day-btn <?php echo $day_filter === $day ? 'active' : ''; ?>">
                            <?php echo $day; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Kelas</th>
                                <th>Instruktur</th>
                                <th>Hari</th>
                                <th>Jam</th>
                                <th>Kapasitas</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $status_class = '';
                                    switch ($row['status']) {
                                        case 'active':
                                            $status_class = 'status-active';
                                            $status_text = 'Aktif';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'status-cancelled';
                                            $status_text = 'Dibatalkan';
                                            break;
                                        case 'full':
                                            $status_class = 'status-full';
                                            $status_text = 'Penuh';
                                            break;
                                        default:
                                            $status_class = '';
                                            $status_text = $row['status'];
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $row['id_class']; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_kelas']); ?></td>
                                        <td><?php echo htmlspecialchars($row['instruktur']); ?></td>
                                        <td><?php echo htmlspecialchars($row['hari']); ?></td>
                                        <td><?php echo date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])); ?></td>
                                        <td><?php echo $row['kapasitas']; ?> orang</td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin-class-edit.php?id=<?php echo $row['id_class']; ?>" class="btn btn-sm btn-warning btn-action" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-danger btn-action" title="Hapus" 
                                               onclick="confirmDelete(<?php echo $row['id_class']; ?>, '<?php echo htmlspecialchars($row['nama_kelas']); ?>')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">Tidak ada data kelas</td>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($day_filter) ? '&day=' . urlencode($day_filter) : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($day_filter) ? '&day=' . urlencode($day_filter) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($day_filter) ? '&day=' . urlencode($day_filter) : ''; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
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
                    <p>Apakah Anda yakin ingin menghapus kelas <strong id="deleteClassName"></strong>?</p>
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
        function confirmDelete(id, name) {
            document.getElementById('deleteClassName').textContent = name;
            document.getElementById('deleteLink').href = 'admin-classes.php?delete=' + id;
            
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