-- ========================================================
-- DATABASE: nusantara_hijau (11 TABEL FINAL)
-- PLATFORM KONSERVASI NUSANTARAHIJAU
-- ========================================================

CREATE DATABASE IF NOT EXISTS `nusantara_hijau` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nusantara_hijau`;

-- Nonaktifkan pengecekan foreign key selama pembuatan ulang tabel
SET FOREIGN_KEY_CHECKS = 0;

-- 1. TABEL ADMINS
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL USERS (KONTRIBUTOR / PENGGALANG DANA)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `no_hp` VARCHAR(30) DEFAULT NULL,
  `organisasi` VARCHAR(150) DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABEL CAMPAIGNS
DROP TABLE IF EXISTS `campaigns`;
CREATE TABLE `campaigns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) DEFAULT NULL,
  `deskripsi` TEXT NOT NULL,
  `lokasi` VARCHAR(150) NOT NULL,
  `kategori` ENUM('laut','darat') DEFAULT 'laut',
  `gambar` VARCHAR(255) DEFAULT 'assets/img/campaign-cenderawasih.png',
  `target_dana` DECIMAL(15,2) NOT NULL,
  `dana_terkumpul` DECIMAL(15,2) DEFAULT 0.00,
  `dana_tersalurkan` DECIMAL(15,2) DEFAULT 0.00,
  `donor_count` INT DEFAULT 0,
  `tgl_mulai` DATE DEFAULT (CURRENT_DATE),
  `tgl_selesai` DATE NOT NULL,
  `status` ENUM('pending', 'aktif', 'ditolak', 'selesai') DEFAULT 'aktif',
  `catatan_admin` TEXT DEFAULT NULL,
  `verified_by` INT DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`verified_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABEL DONATIONS
DROP TABLE IF EXISTS `donations`;
CREATE TABLE `donations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT NOT NULL,
  `kode_invoice` VARCHAR(50) NOT NULL UNIQUE,
  `nama_donatur` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `is_anonim` TINYINT(1) DEFAULT 0,
  `jumlah` DECIMAL(15,2) NOT NULL,
  `metode` VARCHAR(50) NOT NULL, -- 'qris', 'va_bank', 'ewallet'
  `payment_channel` VARCHAR(50) DEFAULT 'QRIS Instant (GPN/GoPay)',
  `va_number` VARCHAR(50) DEFAULT NULL,
  `sha256_hash` VARCHAR(64) DEFAULT NULL,
  `pesan` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'berhasil', 'gagal') DEFAULT 'pending',
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. TABEL DISBURSEMENTS
DROP TABLE IF EXISTS `disbursements`;
CREATE TABLE `disbursements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT NOT NULL,
  `admin_id` INT DEFAULT NULL,
  `jumlah` DECIMAL(15,2) NOT NULL,
  `tgl_rencana` DATE NOT NULL,
  `tgl_realisasi` DATE DEFAULT NULL,
  `keterangan` TEXT NOT NULL,
  `bukti_foto` VARCHAR(255) DEFAULT 'assets/img/bukti-transfer-termin.jpg',
  `status` ENUM('dijadwalkan', 'tersalur') DEFAULT 'dijadwalkan',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. TABEL ARTICLES
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT DEFAULT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) DEFAULT NULL,
  `isi` TEXT NOT NULL,
  `gambar` VARCHAR(255) DEFAULT 'assets/img/campaign-mangrove.png',
  `kategori` ENUM('laut','darat') DEFAULT 'laut',
  `status` ENUM('draft', 'terbit') DEFAULT 'terbit',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. TABEL VOLUNTEER_ACTIVITIES
DROP TABLE IF EXISTS `volunteer_activities`;
CREATE TABLE `volunteer_activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT DEFAULT NULL,
  `admin_id` INT DEFAULT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `lokasi` VARCHAR(150) NOT NULL,
  `tanggal` DATE NOT NULL,
  `kuota` INT NOT NULL DEFAULT 50,
  `link_grup_wa` VARCHAR(255) DEFAULT 'https://chat.whatsapp.com/demo-relawan',
  `status` ENUM('buka', 'penuh', 'selesai') DEFAULT 'buka',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. TABEL VOLUNTEER_REGISTRATIONS
DROP TABLE IF EXISTS `volunteer_registrations`;
CREATE TABLE `volunteer_registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `activity_id` INT NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(30) NOT NULL,
  `alasan` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`activity_id`) REFERENCES `volunteer_activities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. TABEL FAQS
DROP TABLE IF EXISTS `faqs`;
CREATE TABLE `faqs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT DEFAULT NULL,
  `pertanyaan` VARCHAR(255) NOT NULL,
  `jawaban` TEXT NOT NULL,
  `urutan` INT DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. TABEL HELP_TICKETS
DROP TABLE IF EXISTS `help_tickets`;
CREATE TABLE `help_tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `subjek` VARCHAR(150) NOT NULL,
  `pesan` TEXT NOT NULL,
  `status` ENUM('baru', 'diproses', 'selesai') DEFAULT 'baru',
  `balasan` TEXT DEFAULT NULL,
  `dibalas_oleh` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`dibalas_oleh`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. TABEL ACTIVITY_LOGS
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `actor_type` ENUM('admin', 'user') NOT NULL,
  `actor_id` INT DEFAULT NULL,
  `aksi` VARCHAR(100) NOT NULL,
  `entitas` VARCHAR(50) DEFAULT NULL,
  `entitas_id` INT DEFAULT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT '127.0.0.1',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aktifkan kembali pengecekan foreign key
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- DATA AWAL (SEED DATA)
-- ========================================================

-- Akun Administrator (password: admin123)
INSERT INTO `admins` (`id`, `nama`, `email`, `password`, `foto`, `created_at`) VALUES
(1, 'Super Admin', 'admin@nusantarahijau.org', '$2y$10$wTqgU9tH6bJ2qGgW1z6nXeZ0yJ4S0yR4L7P4X3J0S0Y4L7P4X3J0S', 'assets/uploads/profile-admin.jpg', NOW());

-- Akun Kontributor / Penggalang Dana (password: user123)
INSERT INTO `users` (`id`, `nama`, `email`, `password`, `no_hp`, `organisasi`, `foto`, `status`, `created_at`) VALUES
(1, 'Fahmi Ramadhan', 'fahmi.ramadhan@example.com', 'user123', '081234567890', 'Yayasan Terumbu Cenderawasih (Nabire, Papua)', 'assets/uploads/profile-2-1795235829.jpg', 'aktif', NOW()),
(2, 'Maya Kartika', 'maya.kartika@example.com', 'user123', '081255501112', 'Komunitas Mangrove Biru Jawa Barat', NULL, 'aktif', NOW()),
(3, 'Rizky Ananda', 'rizky.ananda@example.com', 'user123', '081355502223', 'Forum HarimauKita Jambi', NULL, 'aktif', NOW()),
(4, 'Dewi Lestari', 'dewi.lestari@example.com', 'user123', '081455503334', 'Konservasi Penyu Sukamade', NULL, 'aktif', NOW()),
(5, 'Arif Hidayat', 'arif.hidayat@example.com', 'user123', '081655504445', 'Gerakan Pesisir Bersih Garut', NULL, 'aktif', NOW()),
(6, 'Nadia Putri', 'nadia.putri@example.com', 'user123', '081955505556', 'Pusat Rehabilitasi Nyaru Menteng', NULL, 'aktif', NOW()),
(7, 'Bima Saputra', 'bima.saputra@example.com', 'user123', '082155506667', 'GreenKoridor Kalimantan Barat', NULL, 'aktif', NOW());

-- Kampanye Donasi
INSERT INTO `campaigns` (`id`, `user_id`, `judul`, `slug`, `deskripsi`, `lokasi`, `kategori`, `gambar`, `target_dana`, `dana_terkumpul`, `dana_tersalurkan`, `donor_count`, `tgl_mulai`, `tgl_selesai`, `status`, `catatan_admin`, `verified_by`, `verified_at`, `created_at`) VALUES
(1, 1, 'Adopsi 500 Spider Web Reef Teluk Cenderawasih', 'adopsi-500-spider-web-reef-teluk-cenderawasih', 'Penanaman 500 unit modul reef spider web di zona terumbu karang rusak dan pendampingan 40 nelayan lokal untuk restorasi laut berkelanjutan.', 'Yayasan Terumbu Cenderawasih (Nabire, Papua)', 'laut', 'assets/img/campaign-cenderawasih.png', 200000000.00, 156400000.00, 50000000.00, 1248, '2024-05-01', DATE_ADD(CURRENT_DATE, INTERVAL 26 DAY), 'aktif', 'Proposal disetujui, dokumen lengkap.', 1, NOW(), '2024-05-01 10:00:00'),
(2, 1, 'Adopsi Sarang Penyu & Edukasi Pesisir Sukabumi', 'adopsi-sarang-penyu-sukabumi', 'Pelestarian populasi penyu hijau melalui relokasi sarang alami yang terancam predator dan perburuan liar ke bak penetasan semi-alami di Suaka Margasatwa Pangumbahan.', 'Pantai Pangumbahan, Jawa Barat', 'laut', 'assets/img/campaign-penyu.png', 25000000.00, 14200000.00, 5000000.00, 64, '2024-06-01', '2026-12-31', 'aktif', 'Program konservasi penyu terverifikasi BKSDA.', 1, NOW(), '2024-06-01 09:30:00'),
(3, 1, 'Rehabilitasi Hutan Mangrove Teluk Youtefa', 'rehabilitasi-hutan-mangrove-youtefa', 'Inisiatif penanaman 5.000 bibit Rhizophora apiculata untuk membentengi pesisir Teluk Youtefa Jayapura dari abrasi dan memulihkan habitat kepiting bakau lokal.', 'Teluk Youtefa, Kota Jayapura', 'laut', 'assets/img/campaign-mangrove.png', 35000000.00, 0.00, 0.00, 0, '2024-10-01', '2026-12-31', 'pending', 'Menunggu verifikasi proposal oleh admin.', NULL, NULL, NOW()),
(4, 2, 'Restorasi Mangrove Muara Gembong', 'restorasi-mangrove-muara-gembong', 'Penanaman mangrove untuk memperkuat sabuk hijau pesisir Muara Gembong dan melibatkan kelompok tani pesisir setempat.', 'Muara Gembong, Jawa Barat', 'laut', 'assets/img/campaign-mangrove.png', 75000000.00, 56250000.00, 42000000.00, 486, '2025-01-10', '2026-12-31', 'aktif', 'Kampanye aktif dan terverifikasi.', 1, NOW(), '2025-01-10 09:00:00'),
(5, 3, 'Patroli Perlindungan Harimau Sumatra', 'patroli-harimau-sumatra-kerinci', 'Pendanaan patroli habitat, pemasangan kamera jebak, serta dukungan logistik tim ranger konservasi di zona penyangga Kerinci.', 'Kerinci, Jambi', 'darat', 'assets/img/campaign-cenderawasih.png', 200000000.00, 180500000.00, 135000000.00, 912, '2025-02-01', '2026-12-31', 'aktif', 'Program konservasi satwa terverifikasi.', 1, NOW(), '2025-02-01 09:00:00'),
(6, 4, 'Konservasi dan Pelepasan Tukik Penyu Hijau', 'konservasi-tukik-penyu-sukamade', 'Relokasi sarang telur dari predator dan pendampingan penetasan semi-alami hingga tukik dilepas ke laut lepas.', 'Sukamade, Banyuwangi', 'laut', 'assets/img/campaign-penyu.png', 120000000.00, 75200000.00, 58000000.00, 638, '2025-03-01', '2026-12-31', 'aktif', 'Program konservasi penyu terverifikasi.', 1, NOW(), '2025-03-01 09:00:00'),
(7, 5, 'Pembersihan 10 Ton Sampah Pesisir Santolo', 'pembersihan-sampah-pesisir-santolo', 'Pengangkatan sampah pesisir, pemasangan jaring penghalang sungai, dan pengolahan plastik menjadi paving block.', 'Pantai Santolo, Garut', 'laut', 'assets/img/campaign-cenderawasih.png', 100000000.00, 68400000.00, 51000000.00, 421, '2025-03-15', '2026-12-31', 'aktif', 'Program aksi pesisir terverifikasi.', 1, NOW(), '2025-03-15 09:00:00'),
(8, 6, 'Rehabilitasi Orangutan Liar Nyaru Menteng', 'rehabilitasi-orangutan-naru-menteng', 'Dukungan pakan, perawatan medis, dan sekolah hutan pra-pelepasliaran bagi bayi orangutan yatim piatu.', 'Nyaru Menteng, Kalimantan Tengah', 'darat', 'assets/img/campaign-mangrove.png', 180000000.00, 156000000.00, 128000000.00, 780, '2025-01-20', '2026-12-31', 'aktif', 'Program rehabilitasi satwa terverifikasi.', 1, NOW(), '2025-01-20 09:00:00'),
(9, 7, 'Reboisasi Mangrove Kalimantan Barat', 'reboisasi-mangrove-kalimantan-barat', 'Penanaman 15.000 bibit mangrove pada lahan gambut terdegradasi untuk menahan abrasi dan menyerap emisi karbon.', 'Kubu Raya, Kalimantan Barat', 'laut', 'assets/img/campaign-mangrove.png', 250000000.00, 250000000.00, 210000000.00, 1450, '2024-01-01', '2025-12-31', 'selesai', 'Kampanye telah mencapai target dan dinyatakan selesai.', 1, NOW(), '2024-01-01 09:00:00'),
(10, 1, 'Restorasi Terumbu Karang Raja Ampat', 'restorasi-terumbu-karang-raja-empat', 'Program transplantasi 2.000 fragmen karang menggunakan media restorasi dan pemantauan pertumbuhan berkala.', 'Misool, Raja Ampat', 'laut', 'assets/img/campaign-cenderawasih.png', 200000000.00, 200000000.00, 188000000.00, 1680, '2024-02-01', '2025-11-30', 'selesai', 'Kampanye telah mencapai target dan dinyatakan selesai.', 1, NOW(), '2024-02-01 09:00:00');
-- Transaksi Donasi
INSERT INTO `donations` (`id`, `campaign_id`, `kode_invoice`, `nama_donatur`, `email`, `is_anonim`, `jumlah`, `metode`, `payment_channel`, `va_number`, `sha256_hash`, `pesan`, `status`, `paid_at`, `created_at`) VALUES
(1, 1, 'DON-2024-88419', 'Rian Pratama', 'rian.pratama@gmail.com', 0, 100000.00, 'qris', 'QRIS Instant (GPN/GoPay)', NULL, 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', 'Semoga terumbu karang Papua lestari selalu!', 'berhasil', NOW(), NOW()),
(2, 1, 'DON-2024-77123', 'Hamba Allah', 'anonim@gmail.com', 1, 50000.00, 'qris', 'QRIS Nasional', NULL, 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'Semoga berkah untuk alam Indonesia.', 'berhasil', NOW(), NOW()),
(3, 2, 'DON-2024-99312', 'Siti Rahma', 'siti.rahma@yahoo.com', 0, 250000.00, 'qris', 'QRIS Instant (GPN/GoPay)', NULL, NULL, 'Lindungi penyu kita.', 'pending', NULL, NOW()),
(4, 4, 'DON-2024-12345', 'Donatur Dummy', 'donatur.dummy@gmail.com', 0, 10000000.00, 'qris', 'QRIS Instant (GPN/GoPay)', NULL, NULL, 'Kampanye dummy selesai.', 'berhasil', NOW(), NOW()),
(5, 5, 'DON-2024-67890', 'Donatur Aktif', 'donatur.aktif@gmail.com', 0, 2500000.00, 'qris', 'QRIS Nasional', NULL, NULL, 'Kampanye dummy aktif.', 'pending', NULL, NOW()),
(6, 1, 'DON-2024-64021', 'Nadia Prameswari', 'nadia.prameswari@gmail.com', 0, 750000.00, 'qris', 'QRIS Nasional', NULL, NULL, 'Sebagai penyalur program CSR perusahaan, NusantaraHijau sangat membahagiakan dan membantu audit kami karena laporan terperinci dan tingkat deskripsi sangat baik.', 'berhasil', NOW(), NOW()),
(7, 1, 'DON-2024-64022', 'Bagas Wicaksono', 'bagas.wicaksono@gmail.com', 0, 350000.00, 'qris', 'QRIS Nasional', NULL, NULL, 'Ikut aksi relawan mangrove lewat platform ini benar-benar terorganisir. Perlengkapan lengkap, edukasi ilmiahnya jelas, dan dampaknya terasa nyata.', 'berhasil', NOW(), NOW()),
(8, 2, 'DON-2024-64023', 'Andi Rahmadani', 'andi.rahmadani@gmail.com', 0, 125000.00, 'qris', 'QRIS Nasional', NULL, NULL, 'Transparansi luar biasa. Sebulan setelah berdonasi untuk karang Misool, saya menerima koordinat GPS dan foto perkembangan bibit karang lengkap.', 'berhasil', NOW(), NOW()),
(9, 3, 'DON-2024-64024', 'Hamba Allah', 'hamba.allah@gmail.com', 1, 500000.00, 'qris', 'QRIS Nasional', NULL, NULL, 'Semoga reboisasi hutan mangrove ini membantu menjaga garis pantai dan memberikan manfaat bagi masyarakat pesisir.', 'berhasil', NOW(), NOW()),
(10, 4, 'DON-2024-64025', 'Maya Kusuma', 'maya.kusuma@gmail.com', 0, 200000.00, 'qris', 'QRIS Nasional', NULL, NULL, 'Kecil atau besar, setiap kontribusi untuk satwa Indonesia layak disambut. Terima kasih atas transparansinya.', 'berhasil', NOW(), NOW()),
(11, 5, 'DON-2024-64026', 'Hamba Allah', 'anonim.dua@gmail.com', 1, 1000000.00, 'qris', 'QRIS Nasional', NULL, NULL, 'Untuk bumi yang lebih hijau, untuk generasi yang lebih sehat. Semoga berkah.', 'berhasil', NOW(), NOW()),
(12, 6, 'DON-2024-64027', 'Arif Prasetyo', 'arif.prasetyo@gmail.com', 0, 600000.00, 'qris', 'QRIS Nasional', NULL, NULL, 'Akses laporan dan bukti lapangan sangat memudahkan saya memilih kampanye konservasi yang terpercaya.', 'berhasil', NOW(), NOW());

-- Penyaluran Dana
INSERT INTO `disbursements` (`id`, `campaign_id`, `admin_id`, `jumlah`, `tgl_rencana`, `tgl_realisasi`, `keterangan`, `bukti_foto`, `status`, `created_at`) VALUES
(1, 1, 1, 15000000.00, '2024-06-15', '2024-06-16', 'Penyaluran Termin 1: Pengadaan 50 unit rangka besi Spider Web Reef dan bibit karang', 'assets/img/bukti-transfer-termin.jpg', 'tersalur', '2024-06-15 14:00:00'),
(2, 1, 1, 17500000.00, '2024-11-20', NULL, 'Penyaluran Termin 2: Biaya operasional penyelam dan monitoring substrat karang', 'assets/img/bukti-transfer-termin.jpg', 'dijadwalkan', NOW()),
(3, 2, 1, 5000000.00, '2024-07-10', '2024-07-12', 'Penyaluran Termin 1: Pembangunan pagar pelindung bak penetasan penyu', 'assets/img/bukti-transfer-termin.jpg', 'tersalur', '2024-07-10 11:00:00'),
(4, 4, 1, 10000000.00, '2023-12-01', '2023-12-05', 'Penyaluran Dana Kampanye Dummy Selesai', 'assets/img/bukti-transfer-termin.jpg', 'tersalur', NOW()),
(5, 5, 1, 2500000.00, '2024-06-01', NULL, 'Penyaluran Dana Kampanye Dummy Aktif', 'assets/img/bukti-transfer-termin.jpg', 'dijadwalkan', NOW());

-- Artikel Edukasi
INSERT INTO `articles` (`id`, `admin_id`, `judul`, `slug`, `isi`, `gambar`, `kategori`, `status`, `created_at`) VALUES
(1, 1, 'Mengenal Metode Spider Web Reef dalam Restorasi Karang', 'mengenal-metode-spider-web-reef', 'Metode Spider Web Reef merupakan teknik restorasi terumbu karang menggunakan struktur rangka besi heksagonal yang saling mengunci. Struktur ini berfungsi menstabilkan pecahan karang dan memberikan substrat kokoh bagi larva karang untuk tumbuh.', 'assets/img/campaign-cenderawasih.png', 'laut', 'terbit', NOW()),
(2, 1, 'Mengapa Terumbu Karang Krusial bagi Ketahanan Pangan Pesisir Indonesia', 'mengapa-terumbu-karang-ketahanan-pangan-pesisir-indonesia', 'Lebih dari 60% sumber protein hewani masyarakat pesisir kepulauan bersandar pada ekosistem karang yang sehat. Penurunan tutupan karang berdampak langsung pada tangkapan nelayan dan stabilitas ekosistem pesisir Indonesia.', 'https://images.unsplash.com/photo-1546026423-cc4642628d2b?auto=format&fit=crop&w=800&q=80', 'laut', 'terbit', '2025-02-14 08:00:00'),
(3, 1, 'Restorasi Hutan Mangrove: Solusi Alami Blue Carbon Terbesar Dunia', 'restorasi-hutan-mangrove-blue-carbon-terbesar-dunia', 'Mangrove Indonesia menyimpan karbon hingga empat kali lipat lebih padat dibanding hutan hujan dataran tinggi. Karbon biru dalam sedimen pesisir menjadi solusi alami untuk mitigasi iklim dan perlindungan garis pantai.', 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80', 'laut', 'terbit', '2025-02-10 08:00:00'),
(4, 1, 'Mengenal Satwa Kunci Ekosistem Hutan Hujan Tropis Nusantara', 'satwa-kunci-ekosistem-hutan-hujan-tropis-nusantara', 'Dari gajah Sumatra hingga rangkong, satwa penyebar biji menentukan regenerasi vegetasi hutan primer. Kehadiran satwa kunci menjaga siklus ekologi, keragaman hayati, dan ketahanan kawasan hutan tropis.', 'https://images.unsplash.com/photo-1516426122078-c23e76319801?auto=format&fit=crop&w=800&q=80', 'darat', 'terbit', '2025-02-05 08:00:00'),
(5, 1, '5 Langkah Konkret Gaya Hidup Rendah Emisi Tanpa Biaya Tambahan', 'lima-langkah-gaya-hidup-rendah-emisi', 'Mengurangi jejak ekologis tidak selalu menuntut pembelian produk baru. Mengelola sampah dapur, mengurangi limbah sekali pakai, berhemat energi, dan mengoptimalkan mobilitas adalah langkah nyata yang dapat dilakukan setiap hari.', 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=800&q=80', 'darat', 'terbit', '2025-02-02 08:00:00'),
(6, 1, 'Masa Depan Wilayah Konservasi Perairan: Efektivitas Zonasi Tangkap', 'masa-depan-wilayah-konservasi-perairan-zonasi-tangkap', 'Evaluasi kawasan suaka bahari menunjukkan bahwa pembatasan armada serta penerapan zonasi tangkap dapat meningkatkan biomassa ikan karang, memperbaiki pemulihan stok, dan mendukung mata pencarian nelayan tradisional.', 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?auto=format&fit=crop&w=800&q=80', 'laut', 'terbit', '2025-01-28 08:00:00'),
(7, 1, 'Peran Jamur Mikoriza Asli Hutan Tropis Mempercepat Reboisasi Lahan Kritis', 'jamur-mikoriza-reboisasi-lahan-kritis', 'Inokulasi jamur mikoriza simbiotik lokal membantu akar pohon menyerap air dan unsur hara. Penggunaan mikoriza asli dapat meningkatkan kelangsungan hidup bibit hutan tropis pada lahan terdegradasi atau bekas tambang.', 'https://images.unsplash.com/photo-1466692476868-aef1dfb1e735?auto=format&fit=crop&w=800&q=80', 'darat', 'terbit', '2025-01-24 08:00:00');

-- Kegiatan Relawan
INSERT INTO `volunteer_activities` (`id`, `campaign_id`, `admin_id`, `judul`, `deskripsi`, `lokasi`, `tanggal`, `kuota`, `link_grup_wa`, `status`, `created_at`) VALUES
(1, 1, 1, 'Aksi Bersih Pantai & Penanaman Fragmen Karang Nabire', 'Kegiatan pembersihan sampah pesisir dan perakitan bibit karang pada struktur rangka laba-laba bersama nelayan lokal Teluk Cenderawasih.', 'Pantai Gedo, Nabire, Papua Tengah', '2025-01-18', 40, 'https://wa.me/?text=Aksi%20Relawan%20Teluk%20Cenderawasih', 'buka', NOW());

-- Pendaftaran Relawan
INSERT INTO `volunteer_registrations` (`id`, `activity_id`, `nama`, `email`, `no_hp`, `alasan`, `created_at`) VALUES
(1, 1, 'Farhan Ramadhan', 'farhan.ramadhan@gmail.com', '081399887766', 'Ingin berkontribusi nyata menjaga kelestarian terumbu karang di Papua.', NOW());

-- FAQ
INSERT INTO `faqs` (`id`, `admin_id`, `pertanyaan`, `jawaban`, `urutan`, `is_active`, `created_at`) VALUES
(1, 1, 'Bagaimana mekanisme transparansi donasi di platform NusantaraHijau?', 'Setiap donasi yang masuk ditampung pada rekening kustodi escrow dan disalurkan secara bertahap (termin) hanya setelah penggalang dana menyerahkan bukti pertanggungjawaban lapangan yang diverifikasi oleh admin.', 1, 1, NOW());

-- Help Tickets (Pusat Bantuan / Keluhan)
INSERT INTO `help_tickets` (`id`, `nama`, `email`, `subjek`, `pesan`, `status`, `balasan`, `dibalas_oleh`, `created_at`) VALUES
(1, 'Ahmad Fauzi', 'ahmad.fauzi@gmail.com', 'Konfirmasi Pembayaran QRIS', 'Halo admin, saya sudah transfer via QRIS sebesar Rp 100.000 tapi ingin memastikan apakah statusnya sudah tercatat otomatis?', 'selesai', 'Halo Kak Ahmad, donasi via QRIS terverifikasi otomatis secara instan. Terima kasih atas kepedulian Anda.', 1, NOW());

-- Activity Logs
INSERT INTO `activity_logs` (`id`, `actor_type`, `actor_id`, `aksi`, `entitas`, `entitas_id`, `deskripsi`, `ip_address`, `created_at`) VALUES
(1, 'admin', 1, 'Inisialisasi Sistem', 'system', 1, 'Konfigurasi 11 tabel database terintegrasi', '127.0.0.1', NOW());
