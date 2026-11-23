<?php
// ========================================================
// MODUL 8: DATABASE RELAWAN (ADMIN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$totalVolunteers = (int)$pdo->query("SELECT COUNT(*) FROM volunteer_registrations")->fetchColumn();
$activityCount = (int)$pdo->query("SELECT COUNT(*) FROM volunteer_activities")->fetchColumn();

$volunteers = $pdo->query("SELECT vr.*, va.judul AS activity_title, va.link_grup_wa, va.lokasi AS activity_location,
                                  c.judul AS campaign_title
                           FROM volunteer_registrations vr
                           JOIN volunteer_activities va ON va.id = vr.activity_id
                           LEFT JOIN campaigns c ON c.id = va.campaign_id
                           ORDER BY vr.id DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Relawan - NusantaraHijau</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-shell">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <div class="admin-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>
    <main class="admin-content">
      <div class="admin-page-heading volunteer-page-heading">
        <div>
          <h1>Database Relawan NusantaraHijau</h1>
          <p>Daftar relawan terdaftar beserta program konservasi yang diikuti.</p>
        </div>
      </div>

      <div class="admin-overview-stats volunteer-stats">
        <div class="admin-overview-stat stat-total">
          <div class="admin-stat-copy"><span class="stat-title">TOTAL RELAWAN TERDAFTAR</span><div class="stat-value-line"><strong><?= number_format($totalVolunteers) ?> Orang</strong></div><span class="stat-caption"><span class="success-dot"></span> Pendaftar aksi konservasi</span></div>
          <span class="admin-overview-stat-icon">♣</span>
        </div>
        <div class="admin-overview-stat stat-pending">
          <div class="admin-stat-copy"><span class="stat-title">PROGRAM RELAWAN</span><div class="stat-value-line"><strong><?= number_format($activityCount) ?> Inisiatif</strong></div><span class="stat-caption"><span class="success-dot"></span> Aksi lapangan dibuka</span></div>
          <span class="admin-overview-stat-icon">▤</span>
        </div>
        <div class="admin-overview-stat stat-volunteers">
          <?php
          $recentCount = (int)$pdo->query("SELECT COUNT(*) FROM volunteer_registrations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
          ?>
          <div class="admin-stat-copy"><span class="stat-title">PENDAFTAR BARU</span><div class="stat-value-line"><strong><?= number_format($recentCount) ?> Minggu Ini</strong></div><span class="stat-caption"><span class="success-dot"></span> <?= number_format($recentCount) ?> relawan bergabung</span></div>
          <span class="admin-overview-stat-icon">♟</span>
        </div>
      </div>

      <section class="data-table-container volunteer-table-card">
        <div class="data-table-header volunteer-table-tools">
          <div class="volunteer-tabs">
            <button type="button" class="active" data-volunteer-filter="all">Semua Relawan</button>
          </div>
          <label class="admin-search-inline volunteer-search"><span>⌕</span><input id="volunteerSearch" type="search" placeholder="Cari relawan, kota, atau kegiatan..."></label>
        </div>

        <div class="admin-table-scroll">
          <table class="data-table volunteer-reference-table">
            <thead>
              <tr>
                <th>Nama Relawan</th>
                <th>Kampanye yang Diikuti</th>
                <th>Grup WA Komunitas</th>
                <th>Kota / Wilayah</th>
                <th>Tanggal Bergabung</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($volunteers as $v):
              $initial = strtoupper(mb_substr(trim($v['nama']), 0, 1));
            ?>
              <tr class="volunteer-row" data-search="<?= htmlspecialchars(strtolower($v['activity_title'] . ' ' . $v['activity_location'] . ' ' . $v['nama'])) ?>">
                <td>
                  <div class="volunteer-person"><span class="volunteer-avatar"><?= htmlspecialchars($initial) ?></span><div><strong><?= htmlspecialchars($v['nama']) ?></strong><small>ID: RELAWAN-<?= str_pad((string)$v['id'], 4, '0', STR_PAD_LEFT) ?></small></div></div>
                </td>
                <td>
                  <strong class="volunteer-campaign"><span></span><?= htmlspecialchars($v['campaign_title'] ?: $v['activity_title']) ?></strong><small class="volunteer-location-mini"><?= htmlspecialchars(mb_strimwidth($v['activity_location'], 0, 42, '...')) ?></small>
                </td>
                <td>
                  <?php if (!empty($v['link_grup_wa'])): ?><a class="volunteer-wa" href="<?= htmlspecialchars($v['link_grup_wa']) ?>" target="_blank" rel="noopener">⌁ Grup WA <?= htmlspecialchars(mb_strimwidth($v['campaign_title'] ?: 'Komunitas', 0, 24, '')) ?></a><?php else: ?><span>-</span><?php endif; ?>
                </td>
                <td><strong class="volunteer-city"><?= htmlspecialchars(mb_strimwidth($v['activity_location'], 0, 38, '')) ?></strong></td>
                <td><strong class="volunteer-date"><?= date('d Sep Y', strtotime($v['created_at'])) ?></strong></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$volunteers): ?><tr><td colspan="5" class="admin-empty-state">Belum ada pendaftaran relawan.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</div>
<script>
document.getElementById('volunteerSearch').addEventListener('input', function () {
  const query = this.value.toLowerCase();
  document.querySelectorAll('.volunteer-row').forEach(row => row.hidden = !row.dataset.search.includes(query));
});
document.querySelectorAll('[data-volunteer-filter]').forEach(button => {
  button.addEventListener('click', function () {
    document.querySelectorAll('[data-volunteer-filter]').forEach(item => item.classList.remove('active'));
    this.classList.add('active');
    const filter = this.dataset.volunteerFilter.toLowerCase();
    document.querySelectorAll('.volunteer-row').forEach(row => {
      row.hidden = filter !== 'all' && !row.dataset.search.includes(filter);
    });
  });
});
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
