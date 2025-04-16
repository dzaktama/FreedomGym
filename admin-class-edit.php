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

// Days of week in Indonesian
$days_of_week = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$statuses = ['active' => 'Aktif', 'cancelled' => 'Dibatalkan', 'full' => 'Penuh'];

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['admin_message'] = "ID kelas tidak valid!";
    $_SESSION['admin_message_type'] = "danger";
    header("Location: admin-classes.php");
    exit();
}

$class_id = intval($_GET['id']);

// Fetch class data
$fetch_sql = "SELECT * FROM gym_classes WHERE id_class = ?";
$stmt = $conn->prepare($fetch_sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    $_SESSION['admin_message'] = "Kelas tidak ditemukan!";
    $_SESSION['admin_message_type'] = "danger";
    header("Location: admin-classes.php");
    exit();
}

$class_data = $result->fetch_assoc();
$stmt->close();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $nama_kelas = trim($_POST['nama_kelas']);
    $deskripsi = trim($_POST['deskripsi']);
    $instruktur = trim($_POST['instruktur']);
    $hari = trim($_POST['hari']);
    $jam_mulai = trim($_POST['jam_mulai']);
    $jam_selesai = trim($_POST['jam_selesai']);
    $kapasitas = intval($_POST['kapasitas']);
    $status = trim($_POST['status']);

    // Basic validation
    $errors = [];

    if (empty($nama_kelas)) {
        $errors[] = "Nama kelas wajib diisi";
    }
    
    if (empty($instruktur)) {
        $errors[] = "Nama instruktur wajib diisi";
    }
    
    if (empty($hari) || !in_array($hari, $days_of_week)) {
        $errors[] = "Hari tidak valid";
    }
    
    if (empty($jam_mulai)) {
        $errors[] = "Jam mulai wajib diisi";
    }
    
    if (empty($jam_selesai)) {
        $errors[] = "Jam selesai wajib diisi";
    }
    
    if ($kapasitas < 1) {
        $errors[] = "Kapasitas minimal 1 orang";
    }
    
    if (empty($status) || !array_key_exists($status, $statuses)) {
        $errors[] = "Status tidak valid";
    }

    // If no errors, update data
    if (empty($errors)) {
        $update_sql = "UPDATE gym_classes SET 
                       nama_kelas = ?, 
                       deskripsi = ?, 
                       instruktur = ?, 
                       hari = ?, 
                       jam_mulai = ?, 
                       jam_selesai = ?, 
                       kapasitas = ?, 
                       status = ? 
                       WHERE id_class = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssssssi", $nama_kelas, $deskripsi, $instruktur, $hari, $jam_mulai, $jam_selesai, $kapasitas, $status, $class_id);
        
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Kelas berhasil diperbarui!";
            $_SESSION['admin_message_type'] = "success";
            header("Location: admin-classes.php");
            exit();
        } else {
            $errors[] = "Gagal memperbarui kelas: " . $conn->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kelas - FREEDOM GYM</title>
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
        .required-field::after {
            content: " *";
            color: red;
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
            <li><a href="admin-classes.php" class="active"><i class="bi bi-calendar-check"></i> Jadwal Kelas</a></li>
            <li><a href="admin-reports.php"><i class="bi bi-clipboard-data"></i> Laporan</a></li>
            <li><a href="admin-settings.php"><i class="bi bi-gear"></i> Pengaturan</a></li>
            <li><a href="logout-admin.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Edit Kelas</h3>
            <a href="admin-classes.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5 class="alert-heading">Terjadi kesalahan!</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Form Edit Kelas</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nama_kelas" class="form-label required-field">Nama Kelas</label>
                            <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" required 
                                value="<?php echo isset($_POST['nama_kelas']) ? htmlspecialchars($_POST['nama_kelas']) : htmlspecialchars($class_data['nama_kelas']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="instruktur" class="form-label required-field">Instruktur</label>
                            <input type="text" class="form-control" id="instruktur" name="instruktur" required 
                                value="<?php echo isset($_POST['instruktur']) ? htmlspecialchars($_POST['instruktur']) : htmlspecialchars($class_data['instruktur']); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : htmlspecialchars($class_data['deskripsi']); ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="hari" class="form-label required-field">Hari</label>
                            <select class="form-select" id="hari" name="hari" required>
                                <option value="">Pilih Hari</option>
                                <?php foreach ($days_of_week as $day): ?>
                                    <option value="<?php echo $day; ?>" 
                                        <?php 
                                        if (isset($_POST['hari']) && $_POST['hari'] === $day) {
                                            echo 'selected';
                                        } elseif (!isset($_POST['hari']) && $class_data['hari'] === $day) {
                                            echo 'selected';
                                        }
                                        ?>
                                    >
                                        <?php echo $day; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="jam_mulai" class="form-label required-field">Jam Mulai</label>
                            <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" required 
                                value="<?php echo isset($_POST['jam_mulai']) ? htmlspecialchars($_POST['jam_mulai']) : htmlspecialchars($class_data['jam_mulai']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="jam_selesai" class="form-label required-field">Jam Selesai</label>
                            <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" required 
                                value="<?php echo isset($_POST['jam_selesai']) ? htmlspecialchars($_POST['jam_selesai']) : htmlspecialchars($class_data['jam_selesai']); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="kapasitas" class="form-label required-field">Kapasitas</label>
                            <input type="number" class="form-control" id="kapasitas" name="kapasitas" min="1" required 
                                value="<?php echo isset($_POST['kapasitas']) ? htmlspecialchars($_POST['kapasitas']) : htmlspecialchars($class_data['kapasitas']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label required-field">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <?php foreach ($statuses as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" 
                                        <?php 
                                        if (isset($_POST['status']) && $_POST['status'] === $key) {
                                            echo 'selected';
                                        } elseif (!isset($_POST['status']) && $class_data['status'] === $key) {
                                            echo 'selected';
                                        }
                                        ?>
                                    >
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                        <a href="admin-classes.php" class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Check start time and end time
                const startTime = document.getElementById('jam_mulai').value;
                const endTime = document.getElementById('jam_selesai').value;
                
                if (startTime && endTime && startTime >= endTime) {
                    alert('Jam mulai harus lebih awal dari jam selesai!');
                    isValid = false;
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>