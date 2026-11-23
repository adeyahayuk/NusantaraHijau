<?php
// ========================================================
// MODUL 8: KELOLA KAMPANYE (ADMIN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$campaigns = $pdo->query("SELECT c.*, u.nama AS pic_name, u.organisasi
                          FROM campaigns c
                          LEFT JOIN users u ON c.user_id = u.id
                          ORDER BY c.id DESC")->fetchAll();

$activeCount = count(array_filter($campaigns, static fn($campaign) => $campaign['status'] === 'aktif'));
$pendingCount = count(array_filter($campaigns, static fn($campaign) => $campaign['status'] === 'pending'));
$completedCount = count(array_filter($campaigns, static fn($campaign) => $campaign['status'] === 'selesai'));
$totalCollected = array_sum(array_column($campaigns, 'dana_terkumpul'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Kampanye - NusantaraHijau</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-shell">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <div class="admin-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>
    <main class="admin-content">
      <div class="admin-page-heading campaign-page-heading">
        <div>
          <h1>Daftar Kampanye Berjalan &amp; Selesai</h1>
          <p>Kelola status aktif, jeda, target, atau perpanjangan masa penggalangan dana.</p>
        </div>
        <label class="admin-search-inline campaign-search"><span>⌕</span><input id="campaignSearch" type="search" placeholder="Cari nama kampanye..."></label>
      </div>

      <section class="data-table-container campaign-list-card">
        <div class="data-table-header campaign-table-header">
          <span>Daftar kampanye aktif, selesai, dan menunggu verifikasi</span>
        </div>
        <div class="admin-table-scroll">
          <table class="data-table admin-reference-table" id="campaignTable">
            <thead>
              <tr>
                <th>Nama Kampanye</th>
                <th>Terkumpul / Target</th>
                <th>Batas Waktu</th>
                <th>Status</th>
                <th>Aksi Pengelolaan</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($campaigns as $campaign):
              $progress = (float)$campaign['target_dana'] > 0 ? min(100, round(((float)$campaign['dana_terkumpul'] / (float)$campaign['target_dana']) * 100)) : 0;
            ?>
              <tr class="campaign-row">
                <td>
                  <div class="admin-campaign-name">
                    <span class="admin-campaign-thumb" style="background-image: url('../<?= htmlspecialchars($campaign['gambar']) ?>'); background-size: cover;"></span>
                    <span>
                      <strong><?= htmlspecialchars($campaign['judul']) ?></strong><br>
                      <small><?= htmlspecialchars($campaign['organisasi'] ?: $campaign['pic_name']) ?> · <?= htmlspecialchars($campaign['lokasi']) ?></small>
                    </span>
                  </div>
                </td>
                <td>
                  <strong class="amount-blue"><?= format_rupiah($campaign['dana_terkumpul']) ?></strong><br>
                  <small class="table-subline">dari <?= format_rupiah($campaign['target_dana']) ?> (<?= $progress ?>%)</small>
                </td>
                <td><?= date('d M Y', strtotime($campaign['tgl_selesai'])) ?></td>
                <td>
                  <span class="admin-status <?= $campaign['status'] === 'aktif' ? 'is-active' : ($campaign['status'] === 'selesai' ? 'is-complete' : ($campaign['status'] === 'ditolak' ? 'is-rejected' : 'is-pending')) ?>">
                    <?= htmlspecialchars(ucfirst($campaign['status'])) ?>
                  </span>
                </td>
                <td>
                  <a class="admin-soft-action" href="perpanjangan.php?id=<?= (int)$campaign['id'] ?>">◷ &nbsp;Perpanjang Masa</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$campaigns): ?><tr><td colspan="5" class="admin-empty-state">Belum ada kampanye.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</div>
<script>
document.getElementById('campaignSearch').addEventListener('input', function () {
  const query = this.value.toLowerCase();
  document.querySelectorAll('.campaign-row').forEach(row => row.hidden = !row.innerText.toLowerCase().includes(query));
});
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
