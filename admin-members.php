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

// Process delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    $delete_sql = "DELETE FROM membership WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $_SESSION['admin_message'] = "Anggota berhasil dihapus!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal menghapus anggota: " . $conn->error;
        $_SESSION['admin_message_type'] = "danger";
    }
    $stmt->close();
    // Redirect to remove the get parameter
    header("Location: admin-members.php");
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
    $search_condition = " WHERE nama LIKE '%$search%' OR email LIKE '%$search%' OR nomorHP LIKE '%$search%' OR username LIKE '%$search%'";
}

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM membership" . $search_condition;
$count_result = $conn->query($count_sql);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get members data
$sql = "SELECT id, nama, email, nomorHP, jenisKelamin, tanggalLahir, jenisMembership, tanggalMulai, durasiMembership, username FROM membership" . 
       $search_condition . 
       " ORDER BY id DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Anggota - FREEDOM GYM</title>
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
        .badge-membership {
            padding: 6px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .badge-squad {
            background-color: #e6f7ff;
            color: #0099ff;
        }
        .badge-individual {
            background-color: #fff2e6;
            color: #ff8c00;
        }
        .badge-visit {
            background-color: #e6ffe6;
            color: #00cc00;
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
            <h3 class="mb-0">Kelola Anggota</h3>
            <a href="admin-member-add.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Tambah Anggota
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
                        <h5 class="mb-0">Daftar Anggota</h5>
                    </div>
                    <div class="col-md-6">
                        <form action="" method="GET" class="d-flex justify-content-end">
                            <div class="input-group search-bar">
                                <input type="text" class="form-control" placeholder="Cari anggota..." name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>No. HP</th>
                                <th>Jenis Kelamin</th>
                                <th>Membership</th>
                                <th>Tanggal Mulai</th>
                                <th>Durasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $badge_class = '';
                                    switch (strtolower($row['jenisMembership'])) {
                                        case 'squad':
                                            $badge_class = 'badge-squad';
                                            break;
                                        case 'individual':
                                            $badge_class = 'badge-individual';
                                            break;
                                        case 'visit':
                                            $badge_class = 'badge-visit';
                                            break;
                                        default:
                                            $badge_class = '';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nomorHP']); ?></td>
                                        <td><?php echo htmlspecialchars($row['jenisKelamin']); ?></td>
                                        <td>
                                            <span class="badge-membership <?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($row['jenisMembership']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($row['tanggalMulai'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['durasiMembership']); ?></td>
                                        <td>
                                            <a href="admin-member-view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info btn-action" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="admin-member-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning btn-action" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-danger btn-action" title="Hapus" 
                                               onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama']); ?>')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">Tidak ada data anggota</td>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
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
        // Function to show delete confirmation modal
        function confirmDelete(id, name) {
            document.getElementById('deleteMemberName').textContent = name;
            document.getElementById('deleteLink').href = 'admin-members.php?delete=' + id;
            
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