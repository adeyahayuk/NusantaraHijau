<?php
// ========================================================
// KONFIGURASI DATABASE XAMPP (NATIVE PDO)
// ========================================================

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'nusantara_hijau';

try {
    // Coba koneksi langsung ke database nusantara_hijau
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Deteksi otomatis jika skema 11 tabel final belum diterapkan (misal tabel lama masih ada)
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'email'")->fetch();
        if (!$checkCol) {
            $sqlFile = __DIR__ . '/../database.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                $pdo->exec($sqlContent);
            }
        }
    } catch (Throwable $t) {
        // Jika tabel belum ada, impor database.sql
        $sqlFile = __DIR__ . '/../database.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $pdo->exec($sqlContent);
        }
    }

    // Pastikan kolom kategori kampanye tersedia pada database lama.
    try {
        $categoryColumn = $pdo->query("SHOW COLUMNS FROM `campaigns` LIKE 'kategori'")->fetch();
        if (!$categoryColumn) {
            $pdo->exec("ALTER TABLE `campaigns` ADD COLUMN `kategori` ENUM('laut','darat') DEFAULT 'laut' AFTER `lokasi`");
        }
    } catch (Throwable $e) {
        // Kolom akan ditambahkan saat skema tersedia/diinisialisasi ulang.
    }

    // Pastikan tabel artikel memiliki kategori Laut/Darat.
    try {
        $articleCategoryColumn = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'kategori'")->fetch();
        if (!$articleCategoryColumn) {
            $pdo->exec("ALTER TABLE `articles` ADD COLUMN `kategori` ENUM('laut','darat') DEFAULT 'laut' AFTER `gambar`");
        }
    } catch (Throwable $e) {
        // Abaikan jika tabel belum tersedia saat koneksi awal.
    }

} catch (PDOException $e) {
    // Jika database belum dibuat di XAMPP, buat otomatis dan impor database.sql
    try {
        $pdoInit = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass);
        $pdoInit->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sqlFile = __DIR__ . '/../database.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $pdoInit->exec($sqlContent);
            
            // Rekoneksi ke DB yang baru dibuat
            $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } else {
            die("Koneksi Database Gagal: " . $e->getMessage());
        }
    } catch (Exception $ex) {
        die("Koneksi Database XAMPP Gagal. Pastikan modul MySQL di XAMPP Control Panel sudah aktif (Running). Error: " . $ex->getMessage());
    }
}

// Opsi manual reset skema database jika dipicu via URL ?reinit_db=1
if (isset($_GET['reinit_db']) && $_GET['reinit_db'] === '1') {
    $sqlFile = __DIR__ . '/../database.sql';
    if (file_exists($sqlFile)) {
        $pdo->exec(file_get_contents($sqlFile));
        echo "<script>alert('Database berhasil diinisialisasi ulang ke 11 tabel final!'); window.location.href='index.php';</script>";
        exit;
    }
}

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
