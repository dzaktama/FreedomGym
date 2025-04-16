<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Akun Baru - FREEDOM GYM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-image: url('assets/img/hero/h1_hero.jpg');
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
        .register-container {
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
        .alert {
            margin-top: 1rem;
        }
        
        /* QRIS Modal Styling */
        .qris-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        .qris-image {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: white;
            border-radius: 8px;
        }
        .modal-header {
            background-color: #dc3545;
            color: white;
        }
        .qris-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        /* Highlight price info */
        .price-info {
            padding: 5px 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-weight: 500;
            color: #dc3545;
            margin-top: 5px;
            display: inline-block;
        }
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="index.php" class="btn btn-back">
            <i class="bi bi-arrow-left fs-4"></i>
        </a>
        <div class="text-center mb-4">
            <h2 class="fw-bold">Register Akun Membership</h2>
            <p class="text-muted">FREEDOM GYM</p>
        </div>

        <?php
        // Start session
        session_start();
        
        // Display error message if any
        if (isset($_SESSION['register_error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['register_error'] . '</div>';
            unset($_SESSION['register_error']);
        }
        
        // Get old form data if available
        $old = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : [];
        ?>

        <form action="proses_register.php" method="POST" enctype="multipart/form-data">
            <!-- Data Pribadi -->
            <h5 class="mt-4 mb-3"><b>Data Pribadi</b></h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?php echo isset($old['nama']) ? htmlspecialchars($old['nama']) : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nomorHP" class="form-label">Nomor HP</label>
                    <input type="text" class="form-control" id="nomorHP" name="nomorHP" value="<?php echo isset($old['nomorHP']) ? htmlspecialchars($old['nomorHP']) : ''; ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($old['email']) ? htmlspecialchars($old['email']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="2" required><?php echo isset($old['alamat']) ? htmlspecialchars($old['alamat']) : ''; ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tanggalLahir" class="form-label">Tanggal Lahir</label>
                    <input type="date" class="form-control" id="tanggalLahir" name="tanggalLahir" value="<?php echo isset($old['tanggalLahir']) ? htmlspecialchars($old['tanggalLahir']) : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="jenisKelamin" class="form-label">Jenis Kelamin</label>
                    <select class="form-select" id="jenisKelamin" name="jenisKelamin" required>
                        <option value="">Pilih</option>
                        <option value="Laki-laki" <?php echo (isset($old['jenisKelamin']) && $old['jenisKelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo (isset($old['jenisKelamin']) && $old['jenisKelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                        <option value="Lainnya" <?php echo (isset($old['jenisKelamin']) && $old['jenisKelamin'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                </div>
            </div>

            <!-- Data Membership -->
            <h5 class="mt-4 mb-3"><b>Data Membership</b></h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="jenisMembership" class="form-label">Jenis Membership</label>
                    <select class="form-select" id="jenisMembership" name="jenisMembership" required>
                        <option value="">Pilih</option>
                        <option value="Visit" data-price="25000" <?php echo (isset($old['jenisMembership']) && $old['jenisMembership'] == 'Visit') ? 'selected' : ''; ?>>Visit (Rp25.000)</option>
                        <option value="Individual" data-price="95000" <?php echo (isset($old['jenisMembership']) && $old['jenisMembership'] == 'Individual') ? 'selected' : ''; ?>>Individual (Rp95.000)</option>
                        <option value="Squad" data-price="300000" <?php echo (isset($old['jenisMembership']) && $old['jenisMembership'] == 'Squad') ? 'selected' : ''; ?>>Squad (Rp300.000)</option>
                    </select>
                    <div id="membershipPrice" class="price-info mt-2" style="display: none;"></div>
                    <div id="visitInfo" class="form-text mt-2" style="display: none;">
                        <i class="bi bi-info-circle"></i> Membership Visit hanya berlaku untuk satu hari.
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="durasiMembership" class="form-label">Durasi Membership</label>
                    <select class="form-select" id="durasiMembership" name="durasiMembership" required>
                        <option value="">Pilih</option>
                        <option value="1 Bulan" <?php echo (isset($old['durasiMembership']) && $old['durasiMembership'] == '1 Bulan') ? 'selected' : ''; ?>>1 Bulan</option>
                        <option value="3 Bulan" <?php echo (isset($old['durasiMembership']) && $old['durasiMembership'] == '3 Bulan') ? 'selected' : ''; ?>>3 Bulan</option>
                        <option value="6 Bulan" <?php echo (isset($old['durasiMembership']) && $old['durasiMembership'] == '6 Bulan') ? 'selected' : ''; ?>>6 Bulan</option>
                        <option value="12 Bulan" <?php echo (isset($old['durasiMembership']) && $old['durasiMembership'] == '12 Bulan') ? 'selected' : ''; ?>>12 Bulan</option>
                    </select>
                    <input type="hidden" name="durasiMembership" id="hiddenDurasiMembership" value="1 Hari" disabled>
                </div>
            </div>

            <div class="mb-3">
                <label for="metodePembayaran" class="form-label">Metode Pembayaran</label>
                <select class="form-select" id="metodePembayaran" name="metodePembayaran" required>
                    <option value="">Pilih</option>
                    <option value="Tunai" <?php echo (isset($old['metodePembayaran']) && $old['metodePembayaran'] == 'Tunai') ? 'selected' : ''; ?>>Tunai</option>
                    <option value="Transfer Bank" <?php echo (isset($old['metodePembayaran']) && $old['metodePembayaran'] == 'Transfer Bank') ? 'selected' : ''; ?>>Transfer Bank</option>
                    <option value="QRIS" <?php echo (isset($old['metodePembayaran']) && $old['metodePembayaran'] == 'QRIS') ? 'selected' : ''; ?>>QRIS</option>
                </select>
                <div class="mt-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnShowQRIS">
                        <i class="bi bi-qr-code"></i> Lihat Kode QRIS
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label for="fotoDiri" class="form-label">Foto Diri</label>
                <input type="file" class="form-control" id="fotoDiri" name="fotoDiri" accept="image/*">
                <small class="text-muted">Opsional. Format: JPG, PNG. Maks: 2MB</small>
            </div>

            <div class="mb-3">
                <label for="catatanKesehatan" class="form-label">Catatan Kesehatan</label>
                <textarea class="form-control" id="catatanKesehatan" name="catatanKesehatan" rows="2"><?php echo isset($old['catatanKesehatan']) ? htmlspecialchars($old['catatanKesehatan']) : ''; ?></textarea>
                <small class="text-muted">Opsional. Tuliskan jika ada riwayat penyakit atau kondisi khusus.</small>
            </div>

            <!-- Kontak Darurat -->
            <h5 class="mt-4 mb-3"><b>Kontak Darurat</b></h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="namaKontakDarurat" class="form-label">Nama Kontak Darurat</label>
                    <input type="text" class="form-control" id="namaKontakDarurat" name="namaKontakDarurat" value="<?php echo isset($old['namaKontakDarurat']) ? htmlspecialchars($old['namaKontakDarurat']) : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nomorKontakDarurat" class="form-label">Nomor Kontak Darurat</label>
                    <input type="text" class="form-control" id="nomorKontakDarurat" name="nomorKontakDarurat" value="<?php echo isset($old['nomorKontakDarurat']) ? htmlspecialchars($old['nomorKontakDarurat']) : ''; ?>" required>
                </div>
            </div>

            <!-- Credentials untuk Login -->
            <h5 class="mt-4 mb-3"><b>Akun Login</b></h5>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($old['username']) ? htmlspecialchars($old['username']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="small text-muted mt-1">
                    Password harus memiliki:
                    <ul class="mb-0">
                        <li>Minimal 8 karakter</li>
                        <li>Huruf besar dan kecil</li>
                        <li>Minimal 1 angka</li>
                    </ul>
                </div>
            </div>

            <div class="mb-3">
                <label for="konfirmasi_password" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="showPassword">
                <label class="form-check-label" for="showPassword">Tampilkan Password</label>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="persetujuan" name="persetujuan" value="1" <?php echo (isset($old['persetujuan']) && $old['persetujuan'] == '1') ? 'checked' : ''; ?> required>
                <label class="form-check-label" for="persetujuan">Saya menyetujui syarat dan ketentuan</label>
            </div>

            <!-- Informasi Total Pembayaran -->
            <div class="alert alert-info" id="paymentSummary" style="display: none;">
                <h6 class="mb-2"><b>Ringkasan Pembayaran:</b></h6>
                <div id="summaryContent"></div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Buat Akun</button>
            </div>
        </form>
        
        <div class="text-center mt-3">
            <p class="small">Sudah punya akun? <a href="login.php" class="text-danger">Login</a></p>
        </div>
    </div>

    <!-- Modal QRIS -->
    <div class="modal fade" id="qrisModal" tabindex="-1" aria-labelledby="qrisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrisModalLabel">Pembayaran QRIS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="qris-container">
                        <p>Silakan scan kode QR berikut untuk melakukan pembayaran:</p>
                        <p class="fw-bold" id="qrisAmount">Total: <span>Rp0</span></p>
                        <img src="assets/img/qris-dana.jpg" alt="QRIS Payment Code" class="qris-image">
                        <div class="qris-info">
                            <p class="mb-1"><strong>Nomor Virtual Account:</strong></p>
                            <p class="mb-3">0821 2204 8502</p>
                            <p class="mb-0 text-muted small">Silakan tunjukkan dan pindai QR code di atas untuk mulai bertransaksi.</p>
                            <p class="mb-0 text-muted small">Powered by DANA - www.dana.id</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmPayment">Konfirmasi Pembayaran</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('showPassword').addEventListener('change', function() {
            const passwordInput = document.getElementById('password');
            const konfirmasiInput = document.getElementById('konfirmasi_password');
            
            passwordInput.type = this.checked ? 'text' : 'password';
            konfirmasiInput.type = this.checked ? 'text' : 'password';
        });

        // Tampilkan informasi harga membership
        const jenisMembership = document.getElementById('jenisMembership');
        const durasiMembership = document.getElementById('durasiMembership');
        const hiddenDurasiMembership = document.getElementById('hiddenDurasiMembership');
        const membershipPrice = document.getElementById('membershipPrice');
        const paymentSummary = document.getElementById('paymentSummary');
        const summaryContent = document.getElementById('summaryContent');
        const visitInfo = document.getElementById('visitInfo');
        
        // Fungsi untuk menangani perubahan jenis membership
        function handleMembershipChange() {
            if (jenisMembership.value === 'Visit') {
                // Jika Visit dipilih, nonaktifkan dropdown durasi dan tampilkan pesan info
                durasiMembership.disabled = true;
                durasiMembership.value = "";
                durasiMembership.style.display = 'none';
                hiddenDurasiMembership.disabled = false;
                visitInfo.style.display = 'block';
            } else {
                // Jika jenis lain dipilih, aktifkan dropdown durasi dan sembunyikan pesan info
                durasiMembership.disabled = false;
                durasiMembership.style.display = 'block';
                hiddenDurasiMembership.disabled = true;
                visitInfo.style.display = 'none';
            }
            
            updateMembershipPrice();
        }
        
        // Fungsi untuk menampilkan harga saat jenis membership atau durasi berubah
        function updateMembershipPrice() {
            if (jenisMembership.value) {
                const option = jenisMembership.options[jenisMembership.selectedIndex];
                const basePrice = parseInt(option.getAttribute('data-price'));
                
                if (jenisMembership.value === 'Visit') {
                    // Untuk Visit, tampilkan harga untuk 1 hari saja
                    membershipPrice.textContent = `Biaya: Rp${formatNumber(basePrice)} (1 hari)`;
                    membershipPrice.style.display = 'inline-block';
                } else {
                    // Untuk jenis lain, hitung berdasarkan durasi
                    if (durasiMembership.value) {
                        const durasi = durasiMembership.value;
                        let durasiAngka = 1;
                        if (durasi === '3 Bulan') durasiAngka = 3;
                        else if (durasi === '6 Bulan') durasiAngka = 6;
                        else if (durasi === '12 Bulan') durasiAngka = 12;
                        
                        const totalPrice = basePrice * durasiAngka;
                        membershipPrice.textContent = `Biaya: Rp${formatNumber(basePrice)}/bulan × ${durasiAngka} bulan = Rp${formatNumber(totalPrice)}`;
                    } else {
                        // Jika durasi belum dipilih, tampilkan harga per bulan saja
                        membershipPrice.textContent = `Biaya: Rp${formatNumber(basePrice)}/bulan`;
                    }
                    membershipPrice.style.display = 'inline-block';
                }
                
                updatePaymentSummary();
            } else {
                membershipPrice.style.display = 'none';
                paymentSummary.style.display = 'none';
            }
        }
        
        // Fungsi untuk memperbarui ringkasan pembayaran
        function updatePaymentSummary() {
            if (jenisMembership.value) {
                const option = jenisMembership.options[jenisMembership.selectedIndex];
                const basePrice = parseInt(option.getAttribute('data-price'));
                const jenis = option.text.split(' (')[0]; // Ambil teks tanpa harga
                
                if (jenisMembership.value === 'Visit') {
                    // Untuk Visit, tampilkan ringkasan dengan durasi 1 hari
                    summaryContent.innerHTML = `
                        <p class="mb-1">Jenis Membership: <b>${jenis}</b></p>
                        <p class="mb-1">Durasi: <b>1 Hari</b></p>
                        <p class="mb-1">Total Pembayaran: <b>Rp${formatNumber(basePrice)}</b></p>
                    `;
                    paymentSummary.style.display = 'block';
                } else if (durasiMembership.value) {
                    // Untuk jenis lain, perlu pilih durasi dulu
                    const durasi = durasiMembership.value;
                    let durasiAngka = 1;
                    if (durasi === '3 Bulan') durasiAngka = 3;
                    else if (durasi === '6 Bulan') durasiAngka = 6;
                    else if (durasi === '12 Bulan') durasiAngka = 12;
                    
                    // Hitung total pembayaran (harga dasar * durasi)
                    const totalPayment = basePrice * durasiAngka;
                    
                    // Update summary content
                    summaryContent.innerHTML = `
                        <p class="mb-1">Jenis Membership: <b>${jenis}</b></p>
                        <p class="mb-1">Harga per Bulan: <b>Rp${formatNumber(basePrice)}</b></p>
                        <p class="mb-1">Durasi: <b>${durasi}</b></p>
                        <p class="mb-1">Total Pembayaran: <b>Rp${formatNumber(totalPayment)}</b></p>
                    `;
                    paymentSummary.style.display = 'block';
                } else {
                    paymentSummary.style.display = 'none';
                }
            } else {
                paymentSummary.style.display = 'none';
            }
        }
        
        // Format angka dengan pemisah ribuan
        function formatNumber(num) {
            return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        }
        
        // Inisialisasi event listeners
        jenisMembership.addEventListener('change', handleMembershipChange);
        durasiMembership.addEventListener('change', updateMembershipPrice);
        
        // Inisialisasi nilai awal jika ada data lama
        window.addEventListener('DOMContentLoaded', function() {
            handleMembershipChange();
        });

        // QRIS Modal Functionality
        document.getElementById('btnShowQRIS').addEventListener('click', function() {
            // Check if QRIS is selected
            const metodePembayaran = document.getElementById('metodePembayaran');
            if (metodePembayaran.value === 'QRIS') {
                // Update QRIS amount based on selected membership
                if (jenisMembership.value) {
                    const option = jenisMembership.options[jenisMembership.selectedIndex];
                    const basePrice = parseInt(option.getAttribute('data-price'));
                    
                    let totalPayment = basePrice;
                    
                    if (jenisMembership.value !== 'Visit' && durasiMembership.value) {
                        // Mendapatkan durasi dalam angka bulan
                        const durasi = durasiMembership.value;
                        let durasiAngka = 1;
                        if (durasi === '3 Bulan') durasiAngka = 3;
                        else if (durasi === '6 Bulan') durasiAngka = 6;
                        else if (durasi === '12 Bulan') durasiAngka = 12;
                        
                        // Hitung total pembayaran
                        totalPayment = basePrice * durasiAngka;
                    }
                    
                    document.querySelector('#qrisAmount span').textContent = `Rp${formatNumber(totalPayment)}`;
                }
                
                // Initialize and show the modal
                const qrisModal = new bootstrap.Modal(document.getElementById('qrisModal'));
                qrisModal.show();
            } else {
                alert('Silakan pilih metode pembayaran QRIS terlebih dahulu');
            }
        });

        // Automatically show/hide QRIS button when payment method changes
        document.getElementById('metodePembayaran').addEventListener('change', function() {
            const showQRISBtn = document.getElementById('btnShowQRIS');
            showQRISBtn.style.display = this.value === 'QRIS' ? 'inline-block' : 'none';
        });

        // Initialize btnShowQRIS visibility on page load
        window.addEventListener('DOMContentLoaded', function() {
            const metodePembayaran = document.getElementById('metodePembayaran');
            const showQRISBtn = document.getElementById('btnShowQRIS');
            showQRISBtn.style.display = metodePembayaran.value === 'QRIS' ? 'inline-block' : 'none';
        });

        // Handle confirm payment button
        document.getElementById('btnConfirmPayment').addEventListener('click', function() {
            alert('Pembayaran Anda sedang diproses. Silakan lanjutkan registrasi.');
            const qrisModal = bootstrap.Modal.getInstance(document.getElementById('qrisModal'));
            qrisModal.hide();
        });
    </script>
</body>
</html>
<?php
// Clear the old input data after displaying the form
unset($_SESSION['old_input']);
?>