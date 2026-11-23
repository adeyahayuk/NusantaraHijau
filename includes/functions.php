<?php
// ========================================================
// FUNGSI BANTUAN GLOBAL NUSANTARAHIJAU (PHP NATIVE)
// ========================================================

/**
 * Format angka ke format Rupiah standar Indonesia
 */
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia (contoh: 24 Oktober 2024, 14:32 WIB)
 */
function format_tanggal_indo($datetime, $with_time = true) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $tgl = date('d', $time);
    $bln = $bulan[(int)date('m', $time)] ?? '';
    $thn = date('Y', $time);
    
    if ($with_time) {
        $jam = date('H:i', $time);
        return "{$tgl} {$bln} {$thn}, {$jam} WIB";
    }
    return "{$tgl} {$bln} {$thn}";
}

/**
 * Catat Log Aktivitas ke tabel activity_logs (11 Tabel Final)
 * Kolom: actor_type (admin/user), actor_id, aksi, entitas, entitas_id, deskripsi, ip_address
 */
function log_activity($pdo, $actor_type = 'user', $actor_id = null, $aksi = '', $entitas = null, $entitas_id = null, $deskripsi = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Kompatibilitas jika dipanggil dengan gaya lama: log_activity($pdo, $user_name, $user_role, $action, $target)
        if (!in_array($actor_type, ['admin', 'user'], true)) {
            $user_name = (string)$actor_type;
            $user_role = (string)$actor_id;
            $action = (string)$aksi;
            $target = (string)$entitas;
            
            $actType = ($user_role === 'admin') ? 'admin' : 'user';
            $actId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
            $desc = "{$user_name} ({$user_role}) melakukan {$action} pada {$target}";
            
            $stmt = $pdo->prepare("INSERT INTO activity_logs (actor_type, actor_id, aksi, entitas, entitas_id, deskripsi, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$actType, $actId, $action, 'general', null, $desc, $ip]);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO activity_logs (actor_type, actor_id, aksi, entitas, entitas_id, deskripsi, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$actor_type, $actor_id, $aksi, $entitas, $entitas_id, $deskripsi, $ip]);
    } catch (Exception $e) {
        // Abaikan agar tidak memutus alur utama
    }
}

/**
 * Generate Hash SHA256 simulasi escrow Kemensos
 */
function generate_escrow_hash($kode_invoice, $jumlah, $date) {
    return hash('sha256', $kode_invoice . '|' . $jumlah . '|' . $date . '|' . 'NUSANTARAHIJAU_ESCROW_KEMENSOS_RI');
}

/**
 * Set flash message untuk feedback ke user
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Ambil flash message lalu hapus dari session
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Helper untuk redirect aman
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * Tentukan kategori konservasi kampanye secara otomatis.
 * Mendukung tampilan Laut/Darat meskipun database lama belum memiliki kolom kategori.
 */
function get_campaign_category($campaign) {
    $saved = strtolower(trim((string)($campaign['kategori'] ?? '')));
    if (in_array($saved, ['laut', 'darat'], true)) {
        return $saved;
    }

    $text = strtolower(($campaign['judul'] ?? '') . ' ' . ($campaign['deskripsi'] ?? '') . ' ' . ($campaign['lokasi'] ?? ''));

    $daratKeywords = [
        'hutan', 'mangrove', 'orangutan', 'harimau', 'satwa', 'ranger',
        'badak', 'gajah', 'reboisasi', 'darat', 'gambut', 'nyaru menteng',
        'kerinci', 'kalimantan', 'kawasan hutan'
    ];

    foreach ($daratKeywords as $keyword) {
        if (str_contains($text, $keyword)) {
            return 'darat';
        }
    }

    return 'laut';
}
