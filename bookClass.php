<?php
// Memulai sesi PHP untuk menyimpan informasi login pengguna
session_start();

// Memeriksa apakah pengguna sudah login dengan mendukung dua format sesi yang berbeda
// PENTING: Tidak ada redirect ke halaman login di sini, sehingga semua pengguna bisa melihat daftar kelas
$is_logged_in = (isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in'])) || 
               (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']));

if ($is_logged_in) {
    // Jika pengguna sudah login, ambil informasi pengguna
    $user_id = $_SESSION['user_id'];
    $user_name = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 
                (isset($_SESSION['nama']) ? $_SESSION['nama'] : '');
    $is_admin = isset($_SESSION['is_admin']);
} else {
    // Default values untuk pengguna yang belum login
    $user_id = 0;
    $user_name = '';
    $is_admin = false;
}

// Informasi koneksi database
$servername = "localhost"; // Nama server database
$username = "root";        // Username database
$password = "";            // Password database
$dbname = "gym";           // Nama database

// Membuat koneksi ke database MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi ke database
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); // Jika gagal, hentikan program dan tampilkan pesan error
}

// Membuat tabel pemesanan_kelas jika belum ada
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

// Menjalankan query untuk membuat tabel
$conn->query($create_booking_table);

// Variabel untuk menyimpan pesan sukses dan pesan error
$success_message = '';
$error_message = '';

// Proses pemesanan dan pembatalan hanya dilakukan jika pengguna sudah login
if ($is_logged_in) {
    // Memproses form pemesanan kelas ketika pengguna menekan tombol "Pesan Kelas"
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_class'])) {
        $kelas_id = $_POST['kelas_id']; // Mengambil ID kelas dari form
        
        // Memeriksa apakah kelas ada dan aktif
        $query_check = "SELECT * FROM gym_classes WHERE id_class = ? AND status = 'active'";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("i", $kelas_id); // "i" berarti parameter integer
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if (mysqli_num_rows($result_check) > 0) { // Jika kelas ditemukan
            $class_data = mysqli_fetch_assoc($result_check);
            $kapasitas = $class_data['kapasitas']; // Mendapatkan kapasitas kelas
            
            // Memeriksa apakah kelas sudah penuh
            $query_count = "SELECT COUNT(*) as booked FROM pemesanan_kelas WHERE kelas_id = ? AND status != 'cancelled'";
            $stmt_count = $conn->prepare($query_count);
            $stmt_count->bind_param("i", $kelas_id);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            $booked_count = mysqli_fetch_assoc($result_count)['booked']; // Jumlah pemesanan aktif
            
            if ($booked_count >= $kapasitas) { // Jika kelas sudah penuh
                $error_message = "Maaf, kelas sudah penuh.";
            } else {
                // Memeriksa apakah pengguna sudah memesan kelas ini sebelumnya
                $query_check_booking = "SELECT * FROM pemesanan_kelas WHERE user_id = ? AND kelas_id = ? AND status != 'cancelled'";
                $stmt_check_booking = $conn->prepare($query_check_booking);
                $stmt_check_booking->bind_param("ii", $user_id, $kelas_id); // "ii" berarti dua parameter integer
                $stmt_check_booking->execute();
                $result_check_booking = $stmt_check_booking->get_result();
                
                if (mysqli_num_rows($result_check_booking) > 0) { // Jika sudah pernah memesan
                    $error_message = "Anda sudah memesan kelas ini sebelumnya.";
                } else {
                    // Membuat pemesanan baru
                    $tanggal_pesan = date('Y-m-d H:i:s'); // Tanggal dan waktu saat ini
                    $query_insert = "INSERT INTO pemesanan_kelas (user_id, kelas_id, tanggal_pesan, status) VALUES (?, ?, ?, 'confirmed')";
                    $stmt_insert = $conn->prepare($query_insert);
                    $stmt_insert->bind_param("iis", $user_id, $kelas_id, $tanggal_pesan); // "iis" berarti dua integer dan satu string
                    
                    if ($stmt_insert->execute()) { // Jika berhasil menyimpan pemesanan
                        $success_message = "Kelas berhasil dipesan!";
                    } else {
                        $error_message = "Terjadi kesalahan saat memesan kelas. Silakan coba lagi.";
                    }
                }
            }
        } else {
            $error_message = "Kelas tidak ditemukan atau tidak aktif.";
        }
    }

    // Memproses pembatalan pemesanan kelas
    if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
        $booking_id = $_GET['cancel']; // Mengambil ID pemesanan dari URL
        
        // Memeriksa apakah pemesanan ada dan milik pengguna yang login
        $query_check = "SELECT * FROM pemesanan_kelas WHERE id_pemesanan = ? AND user_id = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("ii", $booking_id, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if (mysqli_num_rows($result_check) > 0) { // Jika pemesanan ditemukan
            // Mengubah status pemesanan menjadi 'cancelled'
            $query_update = "UPDATE pemesanan_kelas SET status = 'cancelled' WHERE id_pemesanan = ?";
            $stmt_update = $conn->prepare($query_update);
            $stmt_update->bind_param("i", $booking_id);
            
            if ($stmt_update->execute()) { // Jika berhasil mengubah status
                $success_message = "Pemesanan kelas berhasil dibatalkan.";
            } else {
                $error_message = "Terjadi kesalahan saat membatalkan pemesanan. Silakan coba lagi.";
            }
        } else {
            $error_message = "Pemesanan tidak ditemukan atau bukan milik Anda.";
        }
    }

    // Mengambil daftar kelas yang telah dipesan oleh pengguna
    $query_booked = "SELECT pk.*, gc.nama_kelas, gc.deskripsi, gc.hari, gc.jam_mulai, gc.jam_selesai, gc.instruktur 
                    FROM pemesanan_kelas pk 
                    JOIN gym_classes gc ON pk.kelas_id = gc.id_class 
                    WHERE pk.user_id = ? AND pk.status != 'cancelled'
                    ORDER BY gc.hari ASC, gc.jam_mulai ASC";
    $stmt_booked = $conn->prepare($query_booked);
    $stmt_booked->bind_param("i", $user_id);
    $stmt_booked->execute();
    $result_booked = $stmt_booked->get_result();
}
?>

<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Pesan Kelas - Freedom Fitness Gym</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="site.webmanifest">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">

    <!-- CSS here - Mengimpor semua file CSS yang diperlukan untuk tampilan website -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/css/slicknav.css">
    <link rel="stylesheet" href="assets/css/flaticon.css">
    <link rel="stylesheet" href="assets/css/gijgo.css">
    <link rel="stylesheet" href="assets/css/animate.min.css">
    <link rel="stylesheet" href="assets/css/animated-headline.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/themify-icons.css">
    <link rel="stylesheet" href="assets/css/slick.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS styling untuk tampilan halaman pemesanan kelas */
        .welcome-user {
            color: #fff;
            margin-right: 15px;
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
        }
        
        .logout-btn {
            background-color: transparent;
            border: 1px solid #dc3545;
            color: #dc3545;
            border-radius: 30px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-left: 10px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .admin-btn {
            background-color: #ffc107;
            border: 1px solid #ffc107;
            color: #212529;
            border-radius: 30px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-left: 10px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        .admin-btn:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            color: #212529;
        }

        .book-class-container {
            padding: 50px 0;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Styling untuk Gallery Kelas */
        .class-gallery-section {
            padding: 80px 0;
            background-color: #121212;
        }
        
        .gallery-card {
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid #2c2c2c;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .gallery-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
        }
        
        .gallery-image {
            position: relative;
            height: 250px;
            overflow: hidden;
        }
        
        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .gallery-card:hover .gallery-image img {
            transform: scale(1.1);
        }
        
        .class-day-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: rgba(220, 53, 69, 0.8);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .class-time-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .class-status-badge {
            position: absolute;
            bottom: 15px;
            right: 15px;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .class-status-badge.available {
            background-color: rgba(40, 167, 69, 0.8);
            color: white;
        }
        
        .class-status-badge.almost-full {
            background-color: rgba(255, 193, 7, 0.8);
            color: black;
        }
        
        .class-status-badge.full {
            background-color: rgba(220, 53, 69, 0.8);
            color: white;
        }
        
        .gallery-content {
            padding: 20px;
        }
        
        .gallery-content h4 {
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .gallery-content .instructor {
            color: #dc3545;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .gallery-content .instructor i {
            margin-right: 5px;
        }
        
        .gallery-content .description {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 65px;
        }
        
        .capacity-bar {
            margin-bottom: 15px;
        }
        
        .capacity-text {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .progress {
            height: 8px;
            background-color: #2c2c2c;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(to right, #28a745, #dc3545);
            border-radius: 10px;
        }
        
        .book-now-btn {
            background-color: #dc3545;
            color: white;
            text-transform: uppercase;
            font-weight: 600;
            border-radius: 30px;
            padding: 10px 20px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .book-now-btn:hover {
            background-color: #c82333;
            color: white;
        }
        
        .book-now-btn.disabled {
            background-color: #6c757d;
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .filter-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .filter-btn {
            background-color: transparent;
            color: #fff;
            border: 1px solid #dc3545;
            border-radius: 30px;
            padding: 8px 20px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn.active, .filter-btn:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .section-title {
            color: #fff;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }
        
        /* Styling untuk Reserved Classes */
        .reserved-classes-section {
            padding: 50px 0;
            background-color: #121212;
        }
        
        .reserved-class-card {
            background-color: #1a1a1a;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #2c2c2c;
        }
        
        .reserved-class-title {
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .reserved-class-info {
            color: #aaa;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .reserved-class-info i {
            color: #dc3545;
            margin-right: 8px;
            width: 16px;
        }
        
        .cancel-reservation-btn {
            background-color: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
            border-radius: 30px;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        
        .cancel-reservation-btn:hover {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body class="black-bg">
    <!-- ? Preloader Start - Animasi loading sebelum halaman muncul -->
    <div id="preloader-active">
        <div class="preloader d-flex align-items-center justify-content-center">
            <div class="preloader-inner position-relative">
                <div class="preloader-circle"></div>
                <div class="preloader-img pere-text">
                    <img src="img/TT.png" alt="">
                </div>
            </div>
        </div>
    </div>
    <!-- Preloader Start -->
    <header>
        <!-- Header Start - Bagian atas website yang berisi logo dan menu navigasi -->
        <div class="header-area header-transparent">
            <div class="main-header header-sticky">
                <div class="container-fluid">
                    <div class="menu-wrapper d-flex align-items-center justify-content-between">
                        <!-- Logo -->
                        <div class="logo">
                            <a href="index.php"><img src="img/TT.png" alt=""></a>
                        </div>
                        <!-- Main-menu - Menu navigasi utama website -->
                        <div class="main-menu f-right d-none d-lg-block">
                            <nav>
                                <ul id="navigation">
                                    <li><a href="index.php">Home</a></li>
                                    <li><a href="pricing.php">List Harga</a></li>
                                    <li><a href="cekMembership.php">Membership</a></li>
                                    <li><a href="bookClass.php" class="active">Pesan Kelas</a></li>
                                </ul>
                            </nav>
                        </div>          
                        <!-- Header-btn - Tombol login/logout dan admin panel -->
                        <div class="header-btns d-none d-lg-block f-right">
                            <?php if($is_logged_in): ?>
                                <!-- Menampilkan pesan selamat datang jika pengguna sudah login -->
                                <span class="welcome-user">Halo, <?php echo htmlspecialchars($user_name); ?></span>
                                <?php if($is_admin): ?>
                                    <!-- Menampilkan tombol Admin Panel jika pengguna adalah admin -->
                                    <a href="admin-dashboard.php" class="admin-btn">Admin Panel</a>
                                <?php endif; ?>
                                <a href="logout.php" class="logout-btn">Logout</a>
                            <?php else: ?>
                                <!-- Menampilkan tombol login jika pengguna belum login -->
                                <a href="login.php" class="btn">LOGIN</a>
                                <a href="admin-login.php" class="btn">Admin</a>
                            <?php endif; ?>
                        </div>
                       <!-- Mobile Menu - Menu untuk tampilan mobile -->
                       <div class="col-12">
                        <div class="mobile_menu d-block d-lg-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Header End -->
</header>
<main>
    <!--? Hero Start - Banner utama halaman -->
    <div class="slider-area position-relative">
        <div class="slider-active">
            <!-- Single Slider -->
            <div class="single-slider slider-height d-flex align-items-center">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-9 col-lg-9 col-md-10">
                            <div class="hero__caption">
                                <span data-animation="fadeInLeft" data-delay="0.1s">FREEDOM FITNESS GYM</span>
                                <h1 data-animation="fadeInLeft" data-delay="0.4s">PEMESANAN KELAS</h1>
                            </div>
                        </div>
                    </div>
                </div>          
            </div>
        </div>
    </div>
    <!-- Hero End -->

    <!-- Notification Section - Bagian untuk menampilkan notifikasi sukses atau error -->
    <section class="book-class-container">
        <div class="container">
            <?php if(!empty($success_message)): ?>
                <!-- Menampilkan pesan sukses jika ada -->
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Berhasil!</strong> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(!empty($error_message)): ?>
                <!-- Menampilkan pesan error jika ada -->
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Reserved Classes Section - Menampilkan kelas yang telah dipesan oleh pengguna -->
            <?php if ($is_logged_in && isset($result_booked) && $result_booked->num_rows > 0): ?>
            <div class="reserved-classes-section">
                <div class="container">
                    <h2 class="section-title">KELAS YANG TELAH ANDA PESAN</h2>
                    <div class="row">
                        <?php while ($row = $result_booked->fetch_assoc()): ?>
                        <!-- Kartu untuk setiap kelas yang sudah dipesan -->
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="reserved-class-card">
                                <h4 class="reserved-class-title"><?php echo htmlspecialchars($row['nama_kelas']); ?></h4>
                                <p class="reserved-class-info"><i class="fas fa-calendar-day"></i> <?php echo htmlspecialchars($row['hari']); ?></p>
                                <p class="reserved-class-info"><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])); ?></p>
                                <p class="reserved-class-info"><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['instruktur']); ?></p>
                                <p class="reserved-class-info"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars(substr($row['deskripsi'], 0, 100)) . (strlen($row['deskripsi']) > 100 ? '...' : ''); ?></p>
                                <div class="text-end mt-3">
                                    <!-- Tombol untuk membatalkan pemesanan kelas -->
                                    <a href="?cancel=<?php echo $row['id_pemesanan']; ?>" class="cancel-reservation-btn" onclick="return confirm('Apakah Anda yakin ingin membatalkan pemesanan kelas ini?')">
                                        <i class="fas fa-times me-1"></i> Batalkan
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Gallery Kelas Section - Bagian untuk menampilkan semua kelas yang tersedia -->
    <?php
    // Fungsi untuk mendapatkan URL gambar berdasarkan nama kelas
    function getClassImage($className) {
        // Daftar URL gambar untuk setiap jenis kelas
        $classImages = [
            'Yoga' => 'https://images.unsplash.com/photo-1575052814086-f385e2e2ad1b',
            'HIIT' => 'https://images.unsplash.com/photo-1434682772747-f16d3ea162c3',
            'Zumba' => 'https://images.unsplash.com/photo-1517964603305-4024e26475cc',
            'Pilates' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a',
            'CrossFit' => 'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5',
            'Bodybuilding' => 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61',
            'Functional Training' => 'https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5',
            'Spinning' => 'https://images.unsplash.com/photo-1594737625785-a6cbdabd333c',
            'Boxing' => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed',
            'Weight Training' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48',
            'Aerobics' => 'https://images.unsplash.com/photo-1518310383802-640c2de311b2',
            'Circuit Training' => 'https://images.unsplash.com/photo-1518644730709-0835105d9daa'
        ];
        
        // Jika nama kelas ada dalam daftar, kembalikan URL gambarnya
        if (isset($classImages[$className])) {
            return $classImages[$className];
        }
        
        // Jika tidak ada, kembalikan gambar default
        return 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438';
    }

    // Query SQL untuk mengambil daftar SEMUA kelas (termasuk yang tidak aktif)
    $query_all_classes = "SELECT * FROM gym_classes ORDER BY hari ASC, jam_mulai ASC";
    $result_all_classes = $conn->query($query_all_classes);

    // Cek apakah ada data kelas
    if ($result_all_classes && $result_all_classes->num_rows > 0) {
    ?>

    <section class="class-gallery-section">
        <div class="container">
            <h2 class="section-title text-center mb-5">PILIHAN KELAS</h2>
            
            <!-- Filter untuk gallery - Tombol untuk menyaring kelas berdasarkan hari -->
            <div class="filter-buttons mb-4">
                <button class="filter-btn active" data-filter="all">Semua Kelas</button>
                <button class="filter-btn" data-filter="Senin">Senin</button>
                <button class="filter-btn" data-filter="Selasa">Selasa</button>
                <button class="filter-btn" data-filter="Rabu">Rabu</button>
                <button class="filter-btn" data-filter="Kamis">Kamis</button>
                <button class="filter-btn" data-filter="Jumat">Jumat</button>
                <button class="filter-btn" data-filter="Sabtu">Sabtu</button>
                <button class="filter-btn" data-filter="Minggu">Minggu</button>
            </div>
            
            <div class="row gallery-container">
                <?php
                // Loop melalui semua kelas dari database
                while ($class = $result_all_classes->fetch_assoc()) {
                    // Dapatkan URL gambar berdasarkan nama kelas
                    $imageUrl = getClassImage($class['nama_kelas']);
                    
                    // Hitung persentase ketersediaan dan jumlah tempat yang terisi
                    $query_booked = "SELECT COUNT(*) as booked FROM pemesanan_kelas WHERE kelas_id = ? AND status != 'cancelled'";
                    $stmt_booked = $conn->prepare($query_booked);
                    $stmt_booked->bind_param("i", $class['id_class']);
                    $stmt_booked->execute();
                    $booked_result = $stmt_booked->get_result();
                    $booked_count = $booked_result->fetch_assoc()['booked']; // Jumlah peserta yang sudah mendaftar
                    $availability = $class['kapasitas'] - $booked_count; // Sisa tempat yang tersedia
                    $percent_full = ($booked_count / $class['kapasitas']) * 100; // Persentase kepenuhan kelas
                    
                    // Tentukan status ketersediaan kelas dan warna label
                    if ($class['status'] != 'active') {
                        // Kelas tidak aktif
                        $status_label = "TIDAK TERSEDIA";
                        $status_class = "full";
                    } elseif ($availability <= 0) {
                        // Kelas aktif tapi sudah penuh
                        $status_label = "PENUH";
                        $status_class = "full";
                    } elseif ($availability <= 5) {
                        // Kelas aktif tapi hampir penuh (tersisa 5 slot atau kurang)
                        $status_label = "HAMPIR PENUH";
                        $status_class = "almost-full";
                    } else {
                        // Kelas aktif dan masih banyak slot tersedia
                        $status_label = "TERSEDIA";
                        $status_class = "available";
                    }
                ?>
                <!-- Kartu untuk setiap kelas yang tersedia -->
                <div class="col-lg-4 col-md-6 mb-4 gallery-item" data-day="<?php echo $class['hari']; ?>">
                    <div class="gallery-card">
                        <div class="gallery-image">
                            <!-- Gambar kelas -->
                            <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($class['nama_kelas']); ?>" class="img-fluid">
                            <!-- Badge untuk menampilkan hari kelas -->
                            <div class="class-day-badge"><?php echo $class['hari']; ?></div>
                            <!-- Badge untuk menampilkan waktu kelas -->
                            <div class="class-time-badge">
                                <?php echo date('H:i', strtotime($class['jam_mulai'])) . ' - ' . date('H:i', strtotime($class['jam_selesai'])); ?>
                            </div>
                            <!-- Badge untuk menampilkan status ketersediaan kelas -->
                            <div class="class-status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></div>
                        </div>
                        <div class="gallery-content">
                            <!-- Judul kelas -->
                            <h4><?php echo htmlspecialchars($class['nama_kelas']); ?></h4>
                            <!-- Nama instruktur kelas -->
                            <p class="instructor"><i class="fas fa-user"></i> <?php echo htmlspecialchars($class['instruktur']); ?></p>
                            <!-- Deskripsi kelas -->
                            <p class="description"><?php echo htmlspecialchars($class['deskripsi']); ?></p>
                            <!-- Bar kapasitas kelas -->
                            <div class="capacity-bar">
                                <div class="capacity-text"><?php echo "$booked_count / {$class['kapasitas']} peserta"; ?></div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percent_full; ?>%" 
                                        aria-valuenow="<?php echo $percent_full; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            
                            <?php if ($is_logged_in): ?>
                                <!-- Jika pengguna sudah login, tampilkan tombol pemesanan langsung -->
                                <?php if ($class['status'] == 'active' && $availability > 0): ?>
                                    <!-- Kelas aktif dan masih tersedia, tampilkan tombol "Pesan Sekarang" -->
                                    <form action="" method="POST" class="mt-3">
                                        <input type="hidden" name="kelas_id" value="<?php echo $class['id_class']; ?>">
                                        <button type="submit" name="book_class" class="btn book-now-btn">Pesan Sekarang</button>
                                    </form>
                                <?php elseif ($class['status'] == 'active' && $availability <= 0): ?>
                                    <!-- Kelas aktif tapi sudah penuh, tampilkan tombol "Kelas Penuh" yang tidak bisa diklik -->
                                    <button class="btn book-now-btn disabled" disabled>Kelas Penuh</button>
                                <?php else: ?>
                                    <!-- Kelas tidak aktif, tampilkan tombol "Kelas Tidak Tersedia" yang tidak bisa diklik -->
                                    <button class="btn book-now-btn disabled" disabled>Kelas Tidak Tersedia</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Jika pengguna belum login, tampilkan tombol "Login untuk Booking" -->
                                <?php if ($class['status'] == 'active' && $availability > 0): ?>
                                    <!-- Kelas aktif dan masih tersedia, arahkan ke halaman login dengan redirect -->
                                    <a href="login.php?redirect=bookClass.php" class="btn book-now-btn">Login untuk Booking</a>
                                <?php elseif ($class['status'] == 'active' && $availability <= 0): ?>
                                    <!-- Kelas aktif tapi sudah penuh, tampilkan tombol "Kelas Penuh" yang tidak bisa diklik -->
                                    <button class="btn book-now-btn disabled" disabled>Kelas Penuh</button>
                                <?php else: ?>
                                    <!-- Kelas tidak aktif, tampilkan tombol "Kelas Tidak Tersedia" yang tidak bisa diklik -->
                                    <button class="btn book-now-btn disabled" disabled>Kelas Tidak Tersedia</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                }
                ?>
            </div>
        </div>
    </section>

    <?php
    } else {
        // Tampilkan pesan jika tidak ada data kelas di database
        echo '<div class="container py-5 text-center">';
        echo '<h2 class="text-light mb-4">Kelas Belum Tersedia</h2>';
        echo '<p class="text-muted">Saat ini belum ada kelas yang tersedia. Silakan cek kembali nanti.</p>';
        echo '</div>';
    }
    ?>

</main>

<footer>
    <!--? Footer Start - Bagian bawah website yang berisi informasi kontak dan navigasi tambahan -->
    <div class="footer-area black-bg">
        <div class="container">
            <div class="footer-top footer-padding">
                <!-- Footer Menu -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="single-footer-caption mb-50 text-center">
                            <!-- logo -->
                            <div class="footer-logo wow fadeInUp" data-wow-duration="1s" data-wow-delay=".2s">
                                <a href="index.html"><img src="img/TT.png" alt=""></a>
                            </div>
                            <!-- Menu -->
                            <!-- Header Start -->
                            <div class="header-area main-header2 wow fadeInUp" data-wow-duration="2s" data-wow-delay=".4s">
                                <div class="main-header main-header2">
                                    <div class="menu-wrapper menu-wrapper2">
                                        <!-- Main-menu -->
                                        <div class="main-menu main-menu2 text-center">
                                            <nav>
                                                <ul>
                                                    <li><a href="index.php">Home</a></li>
                                                    <li><a href="about.html">About</a></li>
                                                    <li><a href="pricing.php">Pricing</a></li>
                                                    <li><a href="gallery.html">Gallery</a></li>
                                                    <li><a href="contact.html">Contact</a></li>
                                                </ul>
                                            </nav>
                                        </div>   
                                    </div>
                                </div>
                            </div>
                            <!-- Header End -->
                            <!-- social -->
                            <div class="footer-social mt-30 wow fadeInUp" data-wow-duration="3s" data-wow-delay=".8s">
                                <a href="#"><i class="fab fa-youtube"></i></a>
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="row d-flex align-items-center">
                    <div class="col-lg-12">
                        <div class="footer-copy-right text-center">
                            <p>
                              Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | Freedom Fitness Gym
                            </p>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <!-- Footer End-->
  </footer>
  <!-- Scroll Up - Tombol untuk kembali ke atas halaman -->
  <div id="back-top" >
    <a title="Go to Top" href="#"> <i class="fas fa-level-up-alt"></i></a>
</div>

<!-- JS here - Mengimpor semua file JavaScript yang diperlukan -->
<script src="./assets/js/vendor/modernizr-3.5.0.min.js"></script>
<!-- Jquery, Popper, Bootstrap -->
<script src="./assets/js/vendor/jquery-1.12.4.min.js"></script>
<script src="./assets/js/popper.min.js"></script>
<script src="./assets/js/bootstrap.min.js"></script>
<!-- Jquery Mobile Menu -->
<script src="./assets/js/jquery.slicknav.min.js"></script>

<!-- Jquery Slick , Owl-Carousel Plugins -->
<script src="./assets/js/owl.carousel.min.js"></script>
<script src="./assets/js/slick.min.js"></script>
<!-- One Page, Animated-HeadLin -->
<script src="./assets/js/wow.min.js"></script>
<script src="./assets/js/animated.headline.js"></script>
<script src="./assets/js/jquery.magnific-popup.js"></script>

<!-- Date Picker -->
<script src="./assets/js/gijgo.min.js"></script>
<!-- Nice-select, sticky -->
<script src="./assets/js/jquery.nice-select.min.js"></script>
<script src="./assets/js/jquery.sticky.js"></script>

<!-- counter , waypoint,Hover Direction -->
<script src="./assets/js/jquery.counterup.min.js"></script>
<script src="./assets/js/waypoints.min.js"></script>
<script src="./assets/js/jquery.countdown.min.js"></script>
<script src="./assets/js/hover-direction-snake.min.js"></script>

<!-- contact js -->
<script src="./assets/js/contact.js"></script>
<script src="./assets/js/jquery.form.js"></script>
<script src="./assets/js/jquery.validate.min.js"></script>
<script src="./assets/js/mail-script.js"></script>
<script src="./assets/js/jquery.ajaxchimp.min.js"></script>

<!-- Jquery Plugins, main Jquery -->	
<script src="./assets/js/plugins.js"></script>
<script src="./assets/js/main.js"></script>

<!-- Gallery Filter Script - Script untuk menyaring kelas berdasarkan hari -->
<script>
    $(document).ready(function() {
        // Menyembunyikan pesan alert setelah 5 detik
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
        
        // Fungsi filter gallery berdasarkan hari
        const galleryFilterButtons = document.querySelectorAll('.filter-btn');
        const galleryItems = document.querySelectorAll('.gallery-item');
        
        // Menampilkan semua item secara default
        galleryItems.forEach(item => {
            item.style.display = 'block';
        });
        
        // Event listener untuk tombol filter gallery
        galleryFilterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Hapus kelas active dari semua tombol
                galleryFilterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Tambahkan kelas active ke tombol yang diklik
                this.classList.add('active');
                
                // Ambil nilai filter (nama hari)
                const filterValue = this.getAttribute('data-filter');
                
                // Tampilkan atau sembunyikan item gallery berdasarkan filter
                galleryItems.forEach(item => {
                    if (filterValue === 'all' || item.getAttribute('data-day') === filterValue) {
                        item.style.display = 'block'; // Tampilkan item jika sesuai filter atau filter = "all"
                    } else {
                        item.style.display = 'none'; // Sembunyikan item jika tidak sesuai filter
                    }
                });
            });
        });
    });
</script>

</body>
</html>