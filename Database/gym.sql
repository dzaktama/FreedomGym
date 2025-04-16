-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Apr 2025 pada 20.49
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `level` enum('super_admin','admin') NOT NULL DEFAULT 'admin',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama`, `email`, `no_telp`, `level`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jekouw', 'jekouw@freedomgym.com', '081234567890', 'super_admin', NULL, '2025-03-30 17:14:42', '2025-03-30 17:54:07'),
(2, 'uncle_muthu', 'Aaaaaaaa1', 'Uncle Muthu', 'muthu@gmail.com', '082122048502', 'admin', NULL, '2025-03-30 18:08:40', '2025-03-30 18:08:40'),
(4, 'upin', '$2y$10$6G0k5ZZsz3WJpgZx.j/S.OjIcUZwGGmhOxEYJU5KAKnv7KxHLXjK6', 'Upin', 'upin@freedomgym.com', '08123456789', 'super_admin', '2025-03-31 03:06:16', '2025-03-30 18:22:37', '2025-03-30 20:06:16'),
(5, 'anatolikartov', '$2y$10$mB.e5FGhCS5PUFT0YAbGz.aL0W3KY8l/Qk4Jlgwe8FcdAYfprXmMu', 'Anatoli Kartov', 'anatolikartov@example.com', '081234567890', 'super_admin', NULL, '2025-03-30 20:25:37', '2025-03-30 20:25:37'),
(6, 'kemirijahe', 'admin123', 'Kemiri Jahe', 'kemirijahe@example.com', '082122048502', 'super_admin', '2025-04-03 01:41:19', '2025-03-30 20:29:04', '2025-04-02 18:41:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `gym_classes`
--

CREATE TABLE `gym_classes` (
  `id_class` int(11) NOT NULL,
  `nama_kelas` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `instruktur` varchar(100) NOT NULL,
  `hari` varchar(20) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `kapasitas` int(11) NOT NULL DEFAULT 20,
  `status` enum('active','cancelled','full') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `gym_classes`
--

INSERT INTO `gym_classes` (`id_class`, `nama_kelas`, `deskripsi`, `instruktur`, `hari`, `jam_mulai`, `jam_selesai`, `kapasitas`, `status`, `created_at`) VALUES
(1, 'Weight Training', 'Rhythmic aerobic exercise combined with strength training.', 'Mike Johnson', 'Kamis', '09:00:00', '10:00:00', 29, 'full', '2025-03-30 17:25:24'),
(2, 'Pilates', 'Rhythmic aerobic exercise combined with strength training.', 'Sarah Williams', 'Jumat', '19:30:00', '20:30:00', 21, 'cancelled', '2025-03-30 17:25:24'),
(5, 'Panco', 'hafid digilir satu gym', 'Hafid', 'Selasa', '18:40:00', '22:40:00', 22, 'active', '2025-03-30 17:34:10'),
(6, 'Spinning', 'Perfect for beginners looking to improve flexibility and strength.', 'Alex Brown', 'Rabu', '06:00:00', '07:00:00', 19, '', '2025-03-30 20:59:52'),
(7, 'Spinning', 'Series of exercises performed in succession with minimal rest.', 'Jane Smith', 'Minggu', '07:30:00', '08:30:00', 11, '', '2025-03-30 20:59:52'),
(8, 'Yoga', 'Strength and conditioning program with constantly varied functional movements.', 'John Doe', 'Senin', '10:30:00', '11:30:00', 23, '', '2025-03-30 20:59:52'),
(9, 'Pilates', 'Perfect for beginners looking to improve flexibility and strength.', 'Jane Smith', 'Minggu', '18:00:00', '19:00:00', 23, '', '2025-03-30 20:59:52'),
(10, 'Spinning', 'Focus on core strength, stability, and body alignment.', 'John Doe', 'Jumat', '15:00:00', '16:00:00', 20, '', '2025-03-30 20:59:52'),
(11, 'Functional Training', 'Exercises that simulate daily movements to improve strength for everyday activities.', 'Alex Brown', 'Minggu', '06:00:00', '07:00:00', 28, '', '2025-03-30 20:59:52'),
(12, 'Pilates', 'Focus on core strength, stability, and body alignment.', 'Sarah Williams', 'Selasa', '06:00:00', '07:00:00', 29, '', '2025-03-30 20:59:52'),
(13, 'CrossFit', 'Rhythmic aerobic exercise combined with strength training.', 'Jane Smith', 'Selasa', '15:00:00', '16:00:00', 12, '', '2025-03-30 20:59:52'),
(14, 'Zumba', 'Specific weightlifting for muscle building and aesthetics.', 'Mike Johnson', 'Rabu', '07:30:00', '08:30:00', 24, '', '2025-03-30 20:59:52'),
(15, 'Circuit Training', 'Focus on core strength, stability, and body alignment.', 'Alex Brown', 'Senin', '10:30:00', '11:30:00', 20, '', '2025-03-30 20:59:52'),
(16, 'Yoga', 'Exercises that simulate daily movements to improve strength for everyday activities.', 'John Doe', 'Kamis', '09:00:00', '10:00:00', 16, '', '2025-03-30 20:59:52'),
(17, 'Yoga', 'Dance-based workout that is fun and effective for all fitness levels.', 'Alex Brown', 'Kamis', '15:00:00', '16:00:00', 23, '', '2025-03-30 20:59:52'),
(18, 'Bodybuilding', 'Exercises that simulate daily movements to improve strength for everyday activities.', 'John Doe', 'Sabtu', '06:00:00', '07:00:00', 17, '', '2025-03-30 20:59:52'),
(19, 'Spinning', 'Traditional weight training focusing on specific muscle groups.', 'Mike Johnson', 'Minggu', '06:00:00', '07:00:00', 28, '', '2025-03-30 20:59:52'),
(20, 'Pilates', 'Specific weightlifting for muscle building and aesthetics.', 'Alex Brown', 'Kamis', '06:00:00', '07:00:00', 20, '', '2025-03-30 20:59:52'),
(21, 'Spinning', 'Indoor cycling workout focusing on endurance, strength, intervals, and recovery.', 'John Doe', 'Kamis', '10:30:00', '11:30:00', 21, '', '2025-03-30 20:59:52'),
(22, 'CrossFit', 'Cardio workout that builds strength, speed, and coordination.', 'Mike Johnson', 'Sabtu', '18:00:00', '19:00:00', 23, '', '2025-03-30 20:59:52'),
(23, 'Aerobics', 'Dance-based workout that is fun and effective for all fitness levels.', 'John Doe', 'Kamis', '07:30:00', '08:30:00', 22, '', '2025-03-30 20:59:52'),
(24, 'Bodybuilding', 'Focus on core strength, stability, and body alignment.', 'John Doe', 'Senin', '06:00:00', '07:00:00', 12, '', '2025-03-30 20:59:52'),
(25, 'Zumba', 'Traditional weight training focusing on specific muscle groups.', 'Sarah Williams', 'Kamis', '19:30:00', '20:30:00', 24, '', '2025-03-30 20:59:52'),
(26, 'Powerlift', 'angkat beban', 'Hafid', 'Sabtu', '05:10:00', '06:10:00', 20, 'active', '2025-03-30 21:10:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `membership`
--

CREATE TABLE `membership` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nomorHP` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `tanggalLahir` date NOT NULL,
  `jenisKelamin` enum('Laki-laki','Perempuan','Lainnya') NOT NULL,
  `jenisMembership` enum('Visit','Individual','Squad') NOT NULL,
  `tanggalMulai` date NOT NULL,
  `durasiMembership` enum('1 Bulan','3 Bulan','6 Bulan','12 Bulan') NOT NULL,
  `metodePembayaran` enum('Tunai','Transfer Bank','QRIS') NOT NULL,
  `fotoDiri` varchar(255) NOT NULL DEFAULT 'assets/img/default-profile.png',
  `catatanKesehatan` text DEFAULT NULL,
  `namaKontakDarurat` varchar(255) NOT NULL,
  `nomorKontakDarurat` varchar(20) NOT NULL,
  `persetujuan` tinyint(1) NOT NULL,
  `tanggalDaftar` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `membership`
--

INSERT INTO `membership` (`id`, `nama`, `nomorHP`, `email`, `alamat`, `tanggalLahir`, `jenisKelamin`, `jenisMembership`, `tanggalMulai`, `durasiMembership`, `metodePembayaran`, `fotoDiri`, `catatanKesehatan`, `namaKontakDarurat`, `nomorKontakDarurat`, `persetujuan`, `tanggalDaftar`, `username`, `password`) VALUES
(4, 'Hafid Yunna Manan Baraka', '23232', 'hafidyunna@gmail.com', 'awdad', '2025-03-08', 'Perempuan', 'Visit', '2025-03-04', '3 Bulan', 'Transfer Bank', '', 'adawdawdawdaw', '3232323', 'awdawdad', 1, '2025-03-28 17:25:43', NULL, NULL),
(47, 'Dzaky Wiratama', '082122048502', '124230024@student.upnyk.ac.id', 'kos kapuas inn', '2025-03-12', 'Laki-laki', 'Individual', '2025-03-30', '3 Bulan', 'Tunai', 'assets/img/profile/user_1743321133.jpeg', 'adawdawd', 'awdawd', 'awdad', 1, '2025-03-30 06:28:57', 'dzaktama', '$2y$10$7ypEXjt9Cs1HucMnJod69ebh/jghHWy1jV4NzyWMepz7YoKlvN/fO'),
(48, 'Prasetyo Aditomo', '082122039491', 'prasetyo@gmail.com', 'bcc', '1987-12-03', 'Laki-laki', 'Squad', '2025-03-30', '12 Bulan', 'Transfer Bank', 'assets/img/profile/user_1743324677.jpeg', 'Sehat walafiat', 'Dzaky', '082122048502', 1, '2025-03-30 08:15:13', 'prasdmn00', '$2y$10$vVEYwul2NcdK/JS0SVuA3uXL2A5VUyHMluV6ysudUhbrDJMB/X2KC'),
(49, 'Cecep Gorbacep', '082982382921', 'cecep@gmail.com', 'jl. gorbacep', '2025-03-15', 'Laki-laki', 'Visit', '2025-03-31', '3 Bulan', 'Transfer Bank', 'assets/img/profile/user_1743366173.jpeg', 'gada', 'siapa ya', '23232323', 1, '2025-03-30 20:22:53', 'cecepgorbacep', '$2y$10$tqSw93Mhw3mp2.yuIdc0L.xG/kH1hvUdDCG1il8GWBh/BUmye4bDS'),
(50, 'Jamal', '082122039491', 'jamal@gmail.com', 'rumah', '2025-03-06', 'Lainnya', 'Individual', '2025-03-31', '12 Bulan', 'Transfer Bank', 'assets/img/default-profile.png', 'adaddd', 'ad', 'ada', 1, '2025-03-30 21:02:07', 'jamalgym', '$2y$10$VDLYmom2yGywL5AgUe4yW.bIhWM9e4NoYuHCcBZxBuAKuYselvX.u'),
(51, 'Gus Penceng', '082232032948', 'penceng@gmail.com', 'gym', '2025-03-05', 'Perempuan', 'Individual', '2025-03-31', '12 Bulan', 'QRIS', 'assets/img/profile/user_1743431477.png', 'aw', 'aw', 'aw', 1, '2025-03-31 14:31:17', 'gus_penceng', '$2y$10$1NeZGEX3frlSthe3QvKxCuBYsGGqaQK6DNqPMCS8ycsakhELFfeO.'),
(52, 'Anatoli Kartov', '082394819481', 'ojo@gmail.com', '', '2025-04-22', 'Laki-laki', 'Squad', '2025-03-31', '12 Bulan', 'Tunai', 'assets/img/default-profile.png', NULL, '', '', 0, '2025-03-31 19:42:27', 'anatolikartov', '$2y$10$i5EQP8Qyp2y3p6azn3AlweSVyWg6s4U.7FY0qjqUTY8gIWGafCZTG'),
(53, 'Fufu Fafa', '081212121212', 'fufufafa@gmail.com', 'istana boneka', '2025-05-09', 'Lainnya', 'Squad', '2025-04-02', '6 Bulan', 'Tunai', 'assets/img/profile/user_1743609433.png', 'adawdad', 'awdad', 'awdawda', 1, '2025-04-02 15:57:13', 'fufufafa', '$2y$10$S0ZrMRMfHcZH6oEDZKusvujiv8d/ZGr1WIDbWgXTx1zzwdtlmhbaC');

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `id_payment` int(11) NOT NULL,
  `id_anggota` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `tanggal` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `payments`
--

INSERT INTO `payments` (`id_payment`, `id_anggota`, `jumlah`, `metode_pembayaran`, `status`, `tanggal`, `keterangan`, `created_at`) VALUES
(11, 52, 298000.00, 'QRIS', 'failed', '2025-03-06', '', '2025-04-02 14:40:59'),
(12, 47, 211000.00, 'QRIS', 'pending', '2025-03-27', '', '2025-04-02 14:40:59'),
(13, 51, 296000.00, 'Transfer Bank', 'failed', '2025-03-18', '', '2025-04-02 14:40:59'),
(14, 52, 189000.00, 'QRIS', 'pending', '2025-03-09', '', '2025-04-02 14:40:59'),
(15, 50, 96000.00, 'Credit Card', 'paid', '2025-03-03', '', '2025-04-02 14:40:59'),
(16, 4, 246000.00, 'Transfer Bank', 'paid', '2025-03-31', '', '2025-04-02 14:40:59'),
(17, 52, 215000.00, 'Transfer Bank', 'paid', '2025-03-31', '', '2025-04-02 14:40:59'),
(18, 49, 51000.00, 'Transfer Bank', 'paid', '2025-03-15', '', '2025-04-02 14:40:59'),
(19, 49, 49000.00, 'Cash', 'failed', '2025-03-27', '', '2025-04-02 14:40:59'),
(20, 49, 81000.00, 'Cash', 'failed', '2025-03-10', '', '2025-04-02 14:40:59');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pemesanan_kelas`
--

CREATE TABLE `pemesanan_kelas` (
  `id_pemesanan` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `tanggal_pesan` datetime NOT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pemesanan_kelas`
--

INSERT INTO `pemesanan_kelas` (`id_pemesanan`, `user_id`, `kelas_id`, `tanggal_pesan`, `status`, `created_at`) VALUES
(1, 47, 5, '2025-03-31 15:21:32', 'confirmed', '2025-03-31 13:21:32'),
(2, 47, 26, '2025-03-31 15:22:45', 'confirmed', '2025-03-31 13:22:45'),
(3, 52, 5, '2025-03-31 21:43:38', 'pending', '2025-03-31 19:43:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'gym_name', 'FREEDOM GYM', '2025-03-30 20:31:38'),
(2, 'gym_address', 'Jl. Merdeka No. 123, Yogyakarta', '2025-03-30 20:31:38'),
(3, 'gym_phone', '0812-3456-7890', '2025-03-30 20:31:38'),
(4, 'gym_email', 'info@freedomgym.com', '2025-03-30 20:31:38'),
(5, 'working_hours', 'Senin - Minggu: 06:00 - 22:00', '2025-03-30 20:31:38');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `gym_classes`
--
ALTER TABLE `gym_classes`
  ADD PRIMARY KEY (`id_class`);

--
-- Indeks untuk tabel `membership`
--
ALTER TABLE `membership`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id_payment`),
  ADD KEY `id_anggota` (`id_anggota`);

--
-- Indeks untuk tabel `pemesanan_kelas`
--
ALTER TABLE `pemesanan_kelas`
  ADD PRIMARY KEY (`id_pemesanan`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indeks untuk tabel `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `gym_classes`
--
ALTER TABLE `gym_classes`
  MODIFY `id_class` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `membership`
--
ALTER TABLE `membership`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id_payment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `pemesanan_kelas`
--
ALTER TABLE `pemesanan_kelas`
  MODIFY `id_pemesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `membership` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
