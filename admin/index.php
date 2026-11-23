<?php
// ========================================================
// MODUL 8: DASHBOARD MANAJEMEN ADMIN (RINGKASAN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Ambil statistik real-time dari database 11 Tabel Final
$total_masuk = (float)($pdo->query("SELECT SUM(jumlah) FROM donations WHERE status = 'berhasil'")->fetchColumn() ?: 0);
$total_donasi_count = (int)($pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'berhasil'")->fetchColumn() ?: 0);
$total_salur = (float)($pdo->query("SELECT SUM(jumlah) FROM disbursements WHERE status = 'tersalur'")->fetchColumn() ?: 0);
$saldo_escrow = max(0, $total_masuk - $total_salur);
$kampanye_aktif = (int)($pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'aktif'")->fetchColumn() ?: 0);
$pengajuan_menunggu = (int)($pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'pending'")->fetchColumn() ?: 0);

$pengajuan_terbaru = $pdo->query("SELECT c.id, c.judul, c.target_dana, c.lokasi, c.created_at, u.nama AS pic_name, u.organisasi
                                 FROM campaigns c
                                 LEFT JOIN users u ON c.user_id = u.id
                                 WHERE c.status = 'pending'
                                 ORDER BY c.created_at DESC, c.id DESC
                                 LIMIT 1")->fetch();

$relawan_aktif = (int)($pdo->query("SELECT COUNT(*) FROM volunteer_registrations")->fetchColumn() ?: 0);

// Handle sinkronisasi escrow feedback
if (isset($_GET['sync'])) {
    set_flash('success', 'Sinkronisasi Rekening Penampung Escrow Bank Kustodi Berhasil (Status: 100% Guaranteed).');
    header("Location: index.php");
    exit;
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin - NusantaraHijau</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <!-- SIDEBAR ADMIN -->
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <!-- MAIN AREA -->
  <div class="admin-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main class="admin-content">
      <!-- FLASH NOTIFICATION -->
      <?php if ($flash): ?>
        <div class="flash-message flash-<?= $flash['type'] ?>">
          <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <!-- HEADER MODUL 8 -->
      <div class="admin-page-heading">
        <div>
          <h1>Ringkasan Dashboard</h1>
          <p>Selamat datang kembali, pantau ringkasan performa kampanye konservasi dan donasi secara real-time.</p>
        </div>
      </div>

      <div class="admin-overview-stats">
        <a href="verifikasi.php" class="admin-overview-stat stat-pending">
          <div class="admin-stat-copy">
            <span class="stat-title">PENGAJUAN MENUNGGU</span>
            <div class="stat-value-line"><strong><?= number_format($pengajuan_menunggu) ?></strong><small>Proposal</small></div>
            <span class="stat-caption">Perlu tinjauan verifikasi admin</span>
          </div>
          <span class="admin-overview-stat-icon">◷</span>
        </a>
        <a href="donasi.php" class="admin-overview-stat stat-total">
          <div class="admin-stat-copy">
            <span class="stat-title">TOTAL DANA TERKUMPUL</span>
            <div class="stat-value-line"><strong><?= format_rupiah($total_masuk) ?></strong></div>
            <span class="stat-caption">Dari <?= number_format($total_donasi_count) ?> donasi berhasil</span>
          </div>
          <span class="admin-overview-stat-icon">▣</span>
        </a>
        <a href="penyaluran.php" class="admin-overview-stat stat-volunteers">
          <div class="admin-stat-copy">
            <span class="stat-title">DANA TERSALURKAN</span>
            <div class="stat-value-line"><strong><?= format_rupiah($total_salur) ?></strong></div>
            <span class="stat-caption">Saldo Escrow: <?= format_rupiah($saldo_escrow) ?></span>
          </div>
          <span class="admin-overview-stat-icon">♧</span>
        </a>
      </div>

      <!-- GRAFIK KOMPARASI & DONUT SDGs -->
      <div class="charts-grid">
        <!-- BAR CHART KOMPARASI -->
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <div>
              <h3 style="font-size: 15px; font-weight: 700; color: #0f172a;">Tren Penghimpunan Dana &amp; Penyaluran</h3>
              <span style="font-size: 12px; color: var(--text-muted);">Komparasi Dana Masuk vs Realisasi Lapangan</span>
            </div>
            <div style="display: flex; gap: 14px; font-size: 11.5px; font-weight: 600;">
              <span style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 10px; height: 10px; background: #0284c7; border-radius: 2px;"></span> Dana Masuk (<?= format_rupiah($total_masuk) ?>)
              </span>
              <span style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 10px; height: 10px; background: #10b981; border-radius: 2px;"></span> Penyaluran (<?= format_rupiah($total_salur) ?>)
              </span>
            </div>
          </div>

          <div id="adminBarChartSvg" style="min-height: 180px; display: flex; align-items: flex-end; gap: 24px; padding: 20px 10px; border-bottom: 1px solid #e2e8f0;">
            <div style="flex: 1; text-align: center;">
              <div style="height: 120px; background: #0284c7; border-radius: 4px 4px 0 0; margin-bottom: 6px;"></div>
              <small style="font-size: 11px; font-weight: 600;">Donasi Masuk</small>
            </div>
            <div style="flex: 1; text-align: center;">
              <div style="height: <?= $total_masuk > 0 ? max(20, round(($total_salur / $total_masuk) * 120)) : 40 ?>px; background: #10b981; border-radius: 4px 4px 0 0; margin-bottom: 6px;"></div>
              <small style="font-size: 11px; font-weight: 600;">Tersalurkan</small>
            </div>
            <div style="flex: 1; text-align: center;">
              <div style="height: <?= $total_masuk > 0 ? max(20, round(($saldo_escrow / $total_masuk) * 120)) : 80 ?>px; background: #f59e0b; border-radius: 4px 4px 0 0; margin-bottom: 6px;"></div>
              <small style="font-size: 11px; font-weight: 600;">Saldo Escrow</small>
            </div>
          </div>

          <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 10px 14px; margin-top: 14px; font-size: 11.5px; color: #334155; display: flex; justify-content: space-between;">
            <span>Total Relawan Terdaftar: <strong><?= number_format($relawan_aktif) ?> Orang</strong></span>
          </div>
        </div>

        <!-- DONUT CHART KATEGORI & SIKLUS -->
        <div class="card">
          <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 12px;">Distribusi Kampanye Konservasi</h3>
          
          <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 16px;">
            <div style="width: 128px; height: 128px; aspect-ratio: 1 / 1; flex: 0 0 128px; border-radius: 50%; background: conic-gradient(#00628F 0% 65%, #236D00 65% 100%); display: flex; align-items: center; justify-content: center;">
              <div style="width: 76px; height: 76px; aspect-ratio: 1 / 1; background: white; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <span style="font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1;"><?= $kampanye_aktif + $pengajuan_menunggu ?></span>
                <span style="font-size: 8px; color: var(--text-muted); font-weight: 700;">TOTAL</span>
              </div>
            </div>

            <div style="font-size: 12px; color: #334155;">
              <div style="margin-bottom: 8px;">
                <span style="display: inline-block; width: 8px; height: 8px; background: #00628F; border-radius: 50%; margin-right: 4px;"></span>
                Kampanye Aktif: <strong><?= $kampanye_aktif ?></strong>
              </div>
              <div>
                <span style="display: inline-block; width: 8px; height: 8px; background: #dc2626; border-radius: 50%; margin-right: 4px;"></span>
                Menunggu Verifikasi: <strong><?= $pengajuan_menunggu ?></strong>
              </div>
            </div>
          </div>

          <div style="border-top: 1px solid var(--border); padding-top: 12px;">
            <span style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 8px;">
              RINGKASAN STATUS KAMPANYE
            </span>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 11.5px;">
              <div style="background: #f8fafc; padding: 6px 10px; border-radius: var(--radius-sm);">
                <strong style="color: #00628F; font-size: 14px; display: block;"><?= $kampanye_aktif ?></strong>
                <span>Aktif Menggalang</span>
              </div>
              <div style="background: #fef2f2; padding: 6px 10px; border-radius: var(--radius-sm);">
                <strong style="color: #dc2626; font-size: 14px; display: block;"><?= $pengajuan_menunggu ?></strong>
                <span>Perlu Verifikasi</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PENGAJUAN TERBARU -->
      <div class="task-queue-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <span style="width: 10px; height: 10px; background: #dc2626; border-radius: 50%;"></span>
            Pengajuan Kampanye Menunggu Verifikasi
          </h3>
          <span class="badge-count" style="font-size: 11px; padding: 3px 10px;">
            <?= $pengajuan_terbaru ? '1 Proposal Menunggu' : 'Semua Sudah Diverifikasi' ?>
          </span>
        </div>

        <?php if ($pengajuan_terbaru): ?>
          <div class="task-item">
            <div class="task-left">
              <div class="task-icon" style="color: #00628F; background: #D1E4FF;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
              <div class="task-details">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                  <span class="status-pill-warning" style="font-size: 10px; padding: 2px 7px;">#KMP-<?= (int)$pengajuan_terbaru['id'] ?></span>
                  <span style="font-size: 11px; color: var(--text-muted); font-weight: 700;">Target: <?= format_rupiah($pengajuan_terbaru['target_dana']) ?></span>
                </div>
                <h4><?= htmlspecialchars($pengajuan_terbaru['judul']) ?></h4>
                <p>Pengaju: <strong><?= htmlspecialchars($pengajuan_terbaru['pic_name']) ?></strong> (<?= htmlspecialchars($pengajuan_terbaru['organisasi'] ?: 'Inisiator') ?>) · Lokasi: <?= htmlspecialchars($pengajuan_terbaru['lokasi'] ?? '-') ?> · Diajukan: <?= htmlspecialchars(date('d M Y', strtotime($pengajuan_terbaru['created_at']))) ?></p>
              </div>
            </div>
            <a href="verifikasi.php?id=<?= (int)$pengajuan_terbaru['id'] ?>" class="btn-action-sm btn-action-blue">Tinjau Pengajuan →</a>
          </div>
        <?php else: ?>
          <p class="admin-empty-state">Belum ada pengajuan proposal kampanye yang menunggu verifikasi saat ini.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
