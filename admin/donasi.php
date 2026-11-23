<?php
// ========================================================
// MODUL 8: DAFTAR SEMUA DONASI MASUK (ADMIN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Filter & Pencarian
$status_filter = $_GET['status'] ?? '';
$method_filter = $_GET['method'] ?? '';

$sql = "SELECT d.*, c.judul AS campaign_title 
        FROM donations d 
        JOIN campaigns c ON d.campaign_id = c.id 
        WHERE 1=1 ";
$params = [];

if (!empty($status_filter)) {
    $sql .= "AND d.status = ? ";
    $params[] = $status_filter;
}
if (!empty($method_filter) && $method_filter === 'qris') {
    $sql .= "AND d.metode = 'qris' ";
}

$sql .= "ORDER BY d.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donations = $stmt->fetchAll();

// Total kalkulasi
$total_success = (float)($pdo->query("SELECT SUM(jumlah) FROM donations WHERE status = 'berhasil'")->fetchColumn() ?: 0);
$count_success = (int)($pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'berhasil'")->fetchColumn() ?: 0);
$today_success = (float)($pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM donations WHERE status = 'berhasil' AND DATE(paid_at) = CURRENT_DATE()")->fetchColumn() ?: 0);
$average_donation = $count_success > 0 ? $total_success / $count_success : 0;

// Normalisasi QRIS
try {
    $pdo->exec("UPDATE donations SET metode = 'qris', payment_channel = 'QRIS Nasional', va_number = NULL");
} catch (Throwable $e) {
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Donasi - Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <div class="admin-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main class="admin-content">
      <div class="admin-page-heading donation-page-heading">
        <div>
          <h1>Data Donasi Masuk</h1>
          <p>Daftar seluruh transaksi donasi publik dan riwayat dana masuk terverifikasi.</p>
        </div>
      </div>

      <div class="admin-overview-stats donation-stats">
        <div class="admin-overview-stat stat-total">
          <div class="admin-stat-copy"><span class="stat-title">TOTAL DANA TERKUMPUL</span><div class="stat-value-line"><strong><?= format_rupiah($total_success) ?></strong></div><span class="stat-caption">± <?= number_format($count_success) ?> transaksi berhasil</span></div>
          <span class="admin-overview-stat-icon">▣</span>
        </div>
        <div class="admin-overview-stat stat-pending">
          <div class="admin-stat-copy"><span class="stat-title">TOTAL DONATUR</span><div class="stat-value-line"><strong><?= number_format($count_success) ?> Donasi</strong></div><span class="stat-caption">Dari <?= number_format($count_success) ?> kampanye aktif</span></div>
          <span class="admin-overview-stat-icon">♡</span>
        </div>
        <div class="admin-overview-stat stat-volunteers">
          <div class="admin-stat-copy"><span class="stat-title">DONASI HARI INI / RATA-RATA</span><div class="stat-value-line"><strong><?= format_rupiah($today_success) ?></strong></div><span class="stat-caption">Rata-rata: <?= format_rupiah($average_donation) ?>/donasi</span></div>
          <span class="admin-overview-stat-icon">▣</span>
        </div>
      </div>

      <!-- FILTER & TABEL -->
      <div class="data-table-container">
        <div class="data-table-header donation-filter-header">
          <form method="GET" class="table-filter-group donation-status-tabs">
            <select name="status" onchange="this.form.submit()">
              <option value="">-- Semua Status --</option>
              <option value="berhasil" <?= ($status_filter == 'berhasil') ? 'selected' : '' ?>>Berhasil / Lunas</option>
              <option value="pending" <?= ($status_filter == 'pending') ? 'selected' : '' ?>>Menunggu Pembayaran</option>
              <option value="gagal" <?= ($status_filter == 'gagal') ? 'selected' : '' ?>>Gagal</option>
            </select>

            <select name="method" onchange="this.form.submit()">
              <option value="">-- Semua Metode --</option>
              <option value="qris" <?= ($method_filter == 'qris') ? 'selected' : '' ?>>QRIS Nasional</option>
            </select>
          </form>
        </div>

        <div class="admin-table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Donatur</th>
                <th>Kampanye Program</th>
                <th>Nominal</th>
                <th>Metode</th>
                <th>Status</th>
                <th>Waktu</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($donations) > 0): ?>
                <?php foreach ($donations as $d): ?>
                  <tr>
                    <td><strong>#<?= htmlspecialchars($d['kode_invoice']) ?></strong></td>
                    <td>
                      <strong><?= $d['is_anonim'] ? 'Hamba Allah (Anonim)' : htmlspecialchars($d['nama_donatur']) ?></strong><br>
                      <small style="color: var(--text-muted);"><?= htmlspecialchars($d['email']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($d['campaign_title']) ?></td>
                    <td><strong class="amount-blue"><?= format_rupiah($d['jumlah']) ?></strong></td>
                    <td><span style="display:inline-flex;align-items:center;gap:5px;color:#0284c7;font-weight:800;font-size:11.5px;"><span>↗</span> QRIS</span></td>
                    <td>
                      <span class="admin-status <?= ($d['status'] === 'berhasil') ? 'is-active' : (($d['status'] === 'pending') ? 'is-pending' : 'is-rejected') ?>">
                        <?= ucfirst(htmlspecialchars($d['status'])) ?>
                      </span>
                    </td>
                    <td><?= date('d M Y, H:i', strtotime($d['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="admin-empty-state">Tidak ada data transaksi donasi.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
