<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
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

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize variables for error and success messages
$errorMsg = "";
$successMsg = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $nama = $_POST["nama"];
    $nomorHP = $_POST["nomorHP"];
    $email = $_POST["email"];
    $tanggalLahir = $_POST["tanggalLahir"];
    $jenisKelamin = $_POST["jenisKelamin"];
    $username = $_POST["username"];
    
    // First check if username is already taken by another user
    $check_username = "SELECT id FROM membership WHERE username = ? AND id != ?";
    $stmt = $conn->prepare($check_username);
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $username_result = $stmt->get_result();
    
    if ($username_result->num_rows > 0) {
        $errorMsg = "Username sudah digunakan oleh pengguna lain. Silakan pilih username yang berbeda.";
    } else {
        // Check if email is already taken by another user
        $check_email = "SELECT id FROM membership WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $email_result = $stmt->get_result();
        
        if ($email_result->num_rows > 0) {
            $errorMsg = "Email sudah digunakan oleh pengguna lain. Silakan gunakan email yang berbeda.";
        } else {
            // Process photo if uploaded
            $fotoDiri = null; // Will be set only if a new photo is uploaded
            
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
                            $errorMsg = "Gagal mengupload foto.";
                        }
                    } else {
                        $errorMsg = "Ukuran file terlalu besar. Maksimal 2MB.";
                    }
                } else {
                    $errorMsg = "Format file tidak didukung. Gunakan JPG, PNG, atau GIF.";
                }
            }
            
            // If no error occurred, update the database
            if (empty($errorMsg)) {
                // Construct the SQL query based on whether a new photo was uploaded
                if ($fotoDiri) {
                    $sql = "UPDATE membership SET 
                            nama = ?, 
                            nomorHP = ?, 
                            email = ?, 
                            tanggalLahir = ?, 
                            jenisKelamin = ?, 
                            username = ?,
                            fotoDiri = ?
                            WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssssi", $nama, $nomorHP, $email, $tanggalLahir, $jenisKelamin, $username, $fotoDiri, $user_id);
                } else {
                    $sql = "UPDATE membership SET 
                            nama = ?, 
                            nomorHP = ?, 
                            email = ?, 
                            tanggalLahir = ?, 
                            jenisKelamin = ?, 
                            username = ?
                            WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssi", $nama, $nomorHP, $email, $tanggalLahir, $jenisKelamin, $username, $user_id);
                }
                
                if ($stmt->execute()) {
                    // Update session data
                    $_SESSION['nama'] = $nama;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    
                    // Set flag untuk menampilkan pesan sukses di cekMembership.php
                    $_SESSION['profile_updated'] = true;
                    
                    // Redirect ke halaman cekMembership.php
                    header("Location: cekMembership.php");
                    exit();
                } else {
                    $errorMsg = "Gagal memperbarui profil: " . $conn->error;
                }
            }
        }
    }
}

// Get current user data
$sql = "SELECT * FROM membership WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // User not found in database (shouldn't happen if logged in)
    header("Location: login.php");
    exit();
}

$userData = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Get base URL to use for image paths
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseDir = dirname($_SERVER['SCRIPT_NAME']);
    $baseDir = $baseDir === '/' ? '' : $baseDir;
    return $protocol . $host . $baseDir;
}

// Default photo path - using both relative and absolute URLs for better compatibility
$baseUrl = getBaseUrl();
$relativeDefaultPhoto = "assets/img/default-profile.png";
$defaultPhoto = $baseUrl . '/' . $relativeDefaultPhoto;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - FREEDOM GYM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-image: url('assets/img/hero/h1_hero.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-color: rgba(0, 0, 0, 0.9);
            background-blend-mode: lighten;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .edit-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            padding: 30px;
            margin: 20px auto;
        }
        .btn-primary {
            background-color: #dc3545;
            border-color: #dc3545;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }
        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        .btn-back {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: rgba(255, 255, 255, 0.7);
            border: 1px solid #dc3545;
            color: #dc3545;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background-color: #dc3545;
            color: white;
        }
        .current-photo {
            width: 120px;
            height: 120px;
            border-radius: 60px;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #dc3545;
        }
        .photo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <a href="cekMembership.php" class="btn btn-back">
            <i class="bi bi-arrow-left fs-4"></i>
        </a>
        <div class="text-center mb-4">
            <h2 class="fw-bold">Edit Profil</h2>
            <p class="text-muted">FREEDOM GYM</p>
        </div>
        
        <?php if(!empty($errorMsg)): ?>
            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($successMsg)): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
            
            <div class="photo-section">
                <?php 
                $photoPath = isset($userData['fotoDiri']) && !empty($userData['fotoDiri']) ? $userData['fotoDiri'] : "";
                
                // Function to check if file exists and is accessible
                function isPhotoAccessible($path) {
                    if (empty($path)) return false;
                    
                    // For URLs
                    if (filter_var($path, FILTER_VALIDATE_URL)) {
                        $headers = @get_headers($path);
                        return $headers && strpos($headers[0], '200') !== false;
                    } 
                    
                    // For local files - check both relative to document root and script directory
                    $relativePath = ltrim($path, '/');
                    $docRootPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $relativePath;
                    $scriptDirPath = dirname(__FILE__) . '/' . $relativePath;
                    
                    return file_exists($path) || file_exists($docRootPath) || file_exists($scriptDirPath);
                }
                
                // Set photo path - if user photo is inaccessible, use default photo
                if (!isPhotoAccessible($photoPath)) {
                    $photoPath = $defaultPhoto;
                }
                ?>
                <!-- Tambahkan data-default untuk menyimpan URL gambar default -->
                <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                     alt="Current Photo" 
                     class="current-photo" 
                     data-default="<?php echo htmlspecialchars($defaultPhoto); ?>"
                     onerror="this.src=this.getAttribute('data-default')">
                <div class="mb-3">
                    <label for="fotoDiri" class="form-label">Update Foto Profil</label>
                    <input type="file" class="form-control" id="fotoDiri" name="fotoDiri" accept="image/*">
                    <small class="text-muted">Format: JPG, PNG. Maks: 2MB</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($userData['nama']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nomorHP" class="form-label">Nomor HP</label>
                    <input type="text" class="form-control" id="nomorHP" name="nomorHP" value="<?php echo htmlspecialchars($userData['nomorHP']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tanggalLahir" class="form-label">Tanggal Lahir</label>
                    <input type="date" class="form-control" id="tanggalLahir" name="tanggalLahir" value="<?php echo htmlspecialchars($userData['tanggalLahir']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="jenisKelamin" class="form-label">Jenis Kelamin</label>
                    <select class="form-select" id="jenisKelamin" name="jenisKelamin" required>
                        <option value="Laki-laki" <?php echo ($userData['jenisKelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo ($userData['jenisKelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                        <option value="Lainnya" <?php echo ($userData['jenisKelamin'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="cekMembership.php" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
    
    <script>
        // Improved backup image error handler with multiple fallbacks
        document.addEventListener('DOMContentLoaded', function() {
            var profilePhoto = document.querySelector('.current-photo');
            
            // Fallback 1: Jika gambar gagal dimuat, coba gunakan URL dalam atribut data-default
            profilePhoto.addEventListener('error', function() {
                var defaultSrc = this.getAttribute('data-default');
                if (this.src !== defaultSrc) {
                    this.src = defaultSrc;
                } else {
                    // Fallback 2: Jika gambar default juga gagal, coba relatif ke root
                    this.src = '/assets/img/default-profile.png';
                }
            });
            
            // Verifikasi tambahan: Cek jika gambar ada setelah dimuat
            profilePhoto.addEventListener('load', function() {
                if (this.naturalWidth === 0 || this.naturalHeight === 0) {
                    this.src = this.getAttribute('data-default');
                }
            });
        });
    </script>
</body>
</html>