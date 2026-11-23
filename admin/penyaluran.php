<?php
// ========================================================
// MODUL 8: PENYALURAN DANA KAMPANYE (ADMIN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$totalDisbursed = (float)$pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM disbursements WHERE status = 'tersalur'")->fetchColumn();
$scheduled = (float)$pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM disbursements WHERE status = 'dijadwalkan'")->fetchColumn();
$totalDonation = (float)$pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM donations WHERE status = 'berhasil'")->fetchColumn();
$escrow = max(0, $totalDonation - $totalDisbursed);

$disbursements = $pdo->query("SELECT d.*, c.judul AS campaign_title, u.nama AS penggalang_nama, u.organisasi
                              FROM disbursements d
                              JOIN campaigns c ON c.id = d.campaign_id
                              LEFT JOIN users u ON u.id = c.user_id
                              ORDER BY d.id DESC LIMIT 50")->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Penyaluran Dana - NusantaraHijau</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-shell">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <div class="admin-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>
    <main class="admin-content">
      <?php if ($flash): ?><div class="flash-message flash-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
      
      <div class="admin-page-heading disbursement-page-heading">
        <div>
          <h1>Penyaluran Dana &amp; Realisasi Program</h1>
          <p>Kelola jadwal pencairan dana termin kampanye dan verifikasi bukti penyaluran lapangan.</p>
        </div>
      </div>

      <div class="admin-overview-stats disbursement-stats">
        <div class="admin-overview-stat stat-total">
          <div class="admin-stat-copy"><span class="stat-title">TOTAL TERSALURKAN</span><div class="stat-value-line"><strong><?= format_rupiah($totalDisbursed) ?></strong></div><span class="stat-caption">Dana tercatat sebagai terealisasi</span></div>
          <span class="admin-overview-stat-icon">✓</span>
        </div>
        <div class="admin-overview-stat stat-pending">
          <div class="admin-stat-copy"><span class="stat-title">PENYALURAN DIJADWALKAN</span><div class="stat-value-line"><strong><?= format_rupiah($scheduled) ?></strong></div><span class="stat-caption">Menunggu proses pencairan</span></div>
          <span class="admin-overview-stat-icon">◷</span>
        </div>
        <div class="admin-overview-stat stat-volunteers">
          <div class="admin-stat-copy"><span class="stat-title">REKENING ESCROW BERSIH</span><div class="stat-value-line"><strong><?= format_rupiah($escrow) ?></strong></div><span class="stat-caption">Saldo tersedia untuk disalurkan</span></div>
          <span class="admin-overview-stat-icon">▣</span>
        </div>
      </div>

      <section class="data-table-container">
        <div class="data-table-header">
          <div class="admin-table-tabs">
            <a class="active" href="penyaluran.php">Semua Penyaluran</a>
            <a href="realisasi.php">Form Realisasi</a>
          </div>
          <label class="admin-search-inline"><span>⌕</span><input id="disbursementSearch" type="search" placeholder="Cari kampanye atau keterangan..."></label>
        </div>

        <div class="admin-table-scroll">
          <table class="data-table admin-reference-table" id="disbursementTable">
            <thead>
              <tr>
                <th>Kampanye &amp; Mitra Inisiator</th>
                <th>Nominal Penyaluran</th>
                <th>Terjadwal / Terrealisasi</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($disbursements as $disb): ?>
              <tr class="disbursement-row">
                <td>
                  <strong><?= htmlspecialchars($disb['campaign_title']) ?></strong><br>
                  <small><?= htmlspecialchars($disb['organisasi'] ?? $disb['penggalang_nama']) ?></small>
                </td>
                <td><strong class="amount-blue"><?= format_rupiah($disb['jumlah']) ?></strong></td>
                <td><?= date('d M Y', strtotime($disb['tgl_rencana'])) ?><br><small><?= $disb['tgl_realisasi'] ? date('d M Y', strtotime($disb['tgl_realisasi'])) : 'Belum direalisasi' ?></small></td>
                <td>
                  <span class="admin-status <?= $disb['status'] === 'tersalur' ? 'is-active' : 'is-pending' ?>">
                    <?= $disb['status'] === 'tersalur' ? 'Terealisasi' : 'Dijadwalkan' ?>
                  </span>
                </td>
                <td>
                  <?php if ($disb['status'] === 'dijadwalkan'): ?>
                    <a class="admin-primary-action" href="realisasi.php?id=<?= (int)$disb['id'] ?>">Proses Salurkan</a>
                  <?php else: ?>
                    <span style="color: #236D00; font-size: 11px; font-weight: 700;">✓ Selesai</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$disbursements): ?>
              <tr><td colspan="5" class="admin-empty-state">Belum ada data penyaluran dana.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="admin-table-footer">
          <span>Menampilkan <?= count($disbursements) ?> penyaluran</span>
        </div>
      </section>
    </main>
  </div>
</div>
<script>
document.getElementById('disbursementSearch').addEventListener('input', function () {
  const query = this.value.toLowerCase();
  document.querySelectorAll('.disbursement-row').forEach(row => row.hidden = !row.innerText.toLowerCase().includes(query));
});
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
