<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root"; // Change with your database username
$password = ""; // Change with your database password
$dbname = "gym";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Store all form data in session to retain input values in case of validation error
    $_SESSION['old_input'] = $_POST;
    
    // Get form data - membership table fields
    $nama = $_POST["nama"];
    $nomorHP = $_POST["nomorHP"];
    $email = $_POST["email"];
    $alamat = $_POST["alamat"];
    $tanggalLahir = $_POST["tanggalLahir"];
    $jenisKelamin = $_POST["jenisKelamin"];
    $jenisMembership = $_POST["jenisMembership"];
    
    // Durasi Membership berdasarkan jenis
    if ($jenisMembership === 'Visit') {
        $durasiMembership = "1 Hari"; // Untuk Visit, durasi otomatis 1 hari
    } else {
        $durasiMembership = $_POST["durasiMembership"];
    }
    
    $metodePembayaran = $_POST["metodePembayaran"];
    $catatanKesehatan = $_POST["catatanKesehatan"] ?? null;
    $namaKontakDarurat = $_POST["namaKontakDarurat"];
    $nomorKontakDarurat = $_POST["nomorKontakDarurat"];
    $persetujuan = isset($_POST["persetujuan"]) ? 1 : 0;
    
    // Get login credentials
    $username = $_POST["username"];
    $password = $_POST["password"];
    $konfirmasi_password = $_POST["konfirmasi_password"];
    
    // Handle file upload
    $fotoDiri = "assets/img/default-profile.png"; // Default image path
    
    if (isset($_FILES['fotoDiri']) && $_FILES['fotoDiri']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['fotoDiri']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if (in_array(strtolower($filetype), $allowed)) {
            // Check filesize (max 2MB)
            if ($_FILES['fotoDiri']['size'] <= 2097152) {
                $newname = 'user_' . time() . '.' . $filetype;
                $target = 'assets/img/profile/' . $newname;
                
                // Create directory if it doesn't exist
                if (!file_exists('assets/img/profile/')) {
                    mkdir('assets/img/profile/', 0777, true);
                }
                
                // Upload file
                if (move_uploaded_file($_FILES['fotoDiri']['tmp_name'], $target)) {
                    $fotoDiri = $target;
                } else {
                    $_SESSION["register_error"] = "Gagal mengupload foto.";
                    header("Location: register.php");
                    exit();
                }
            } else {
                $_SESSION["register_error"] = "Ukuran file terlalu besar. Maksimal 2MB.";
                header("Location: register.php");
                exit();
            }
        } else {
            $_SESSION["register_error"] = "Format file tidak didukung. Gunakan JPG, PNG, atau GIF.";
            header("Location: register.php");
            exit();
        }
    }
    
    // Validate password
    if (strlen($password) < 8) {
        $_SESSION["register_error"] = "Password harus minimal 8 karakter!";
        header("Location: register.php");
        exit();
    }
    
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password)) {
        $_SESSION["register_error"] = "Password harus mengandung huruf besar dan kecil!";
        header("Location: register.php");
        exit();
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $_SESSION["register_error"] = "Password harus mengandung minimal 1 angka!";
        header("Location: register.php");
        exit();
    }
    
    // Check if passwords match
    if ($password !== $konfirmasi_password) {
        $_SESSION["register_error"] = "Password dan konfirmasi password tidak cocok!";
        header("Location: register.php");
        exit();
    }
    
    // Check if username already exists
    $check_username = "SELECT * FROM membership WHERE username = ?";
    $stmt = $conn->prepare($check_username);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION["register_error"] = "Username sudah digunakan, silakan pilih username lain!";
        header("Location: register.php");
        exit();
    }
    
    // Check if email already exists
    $check_email = "SELECT * FROM membership WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION["register_error"] = "Email sudah terdaftar!";
        header("Location: register.php");
        exit();
    }
    
    // Hash password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Mulai transaksi database
        $conn->begin_transaction();

        // Insert data to membership table including username and password
        $sql = "INSERT INTO membership (nama, nomorHP, email, alamat, tanggalLahir, jenisKelamin, jenisMembership, 
                tanggalMulai, durasiMembership, metodePembayaran, fotoDiri, catatanKesehatan, 
                namaKontakDarurat, nomorKontakDarurat, persetujuan, username, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_DATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssssiss", 
                        $nama, 
                        $nomorHP, 
                        $email, 
                        $alamat, 
                        $tanggalLahir, 
                        $jenisKelamin, 
                        $jenisMembership, 
                        $durasiMembership, 
                        $metodePembayaran, 
                        $fotoDiri, 
                        $catatanKesehatan, 
                        $namaKontakDarurat, 
                        $nomorKontakDarurat, 
                        $persetujuan,
                        $username,
                        $hashed_password);
        
        if ($stmt->execute()) {
            // Ambil ID anggota yang baru saja dibuat
            $id_anggota = $conn->insert_id;
            
            // Tentukan harga per bulan berdasarkan jenis membership
            $harga_per_bulan = 0;
            switch($jenisMembership) {
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
            
            // Tentukan durasi dalam bulan atau hari
            if ($jenisMembership === 'Visit') {
                // Untuk Visit, jumlah tetap harga untuk satu hari
                $jumlah = $harga_per_bulan;
                $keterangan_durasi = "1 Hari";
            } else {
                // Untuk jenis membership lain, konversi durasi ke angka bulan
                $durasi_bulan = 1; // Default 1 bulan
                if ($durasiMembership == '3 Bulan') {
                    $durasi_bulan = 3;
                } elseif ($durasiMembership == '6 Bulan') {
                    $durasi_bulan = 6;
                } elseif ($durasiMembership == '12 Bulan') {
                    $durasi_bulan = 12;
                }
                
                // Hitung total biaya membership (harga per bulan * durasi)
                $jumlah = $harga_per_bulan * $durasi_bulan;
                $keterangan_durasi = "$durasi_bulan Bulan";
            }
            
            // Jika jumlah pembayaran valid, masukkan ke tabel pembayaran
            if ($jumlah > 0) {
                // Tentukan status pembayaran
                $status_pembayaran = ($metodePembayaran == 'Tunai') ? 'pending' : 'paid';
                
                // Buat catatan pembayaran
                if ($jenisMembership === 'Visit') {
                    $keterangan_pembayaran = "Pembayaran untuk membership Visit (1 Hari) sebesar Rp" . number_format($jumlah, 0, ',', '.');
                } else {
                    $keterangan_pembayaran = "Pembayaran untuk membership $jenisMembership dengan durasi $durasiMembership (Rp" . number_format($harga_per_bulan, 0, ',', '.') . " x $durasi_bulan bulan)";
                }
                
                // Masukkan data pembayaran ke tabel payments
                $payment_sql = "INSERT INTO payments (id_anggota, jumlah, metode_pembayaran, status, tanggal, keterangan) 
                                VALUES (?, ?, ?, ?, CURRENT_DATE(), ?)";
                
                $payment_stmt = $conn->prepare($payment_sql);
                $payment_stmt->bind_param("idsss", 
                                        $id_anggota, 
                                        $jumlah, 
                                        $metodePembayaran, 
                                        $status_pembayaran, 
                                        $keterangan_pembayaran);
                
                if (!$payment_stmt->execute()) {
                    // Jika gagal menyimpan pembayaran, batalkan transaksi
                    throw new Exception("Gagal menyimpan data pembayaran: " . $conn->error);
                }
                
                $payment_stmt->close();
            }
            
            // Commit transaksi jika semua berhasil
            $conn->commit();
            
            // Clear the old input data since registration was successful
            unset($_SESSION['old_input']);
            
            $_SESSION["register_success"] = "Pendaftaran berhasil! Silakan login.";
            header("Location: login.php");
            exit();
        } else {
            // Rollback transaksi jika ada kesalahan
            $conn->rollback();
            throw new Exception("Gagal menyimpan data: " . $conn->error);
        }
        
    } catch (Exception $e) {
        // Pastikan transaksi di-rollback jika terjadi kesalahan
        $conn->rollback();
        
        $_SESSION["register_error"] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }
    
    $stmt->close();
}

$conn->close();
?>