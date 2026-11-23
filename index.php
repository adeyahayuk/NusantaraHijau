<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$stmt = $pdo->prepare("SELECT c.*, u.nama AS nama_penggalang, u.organisasi
                       FROM campaigns c
                       LEFT JOIN users u ON c.user_id = u.id
                       WHERE c.status = 'aktif'
                         AND c.tgl_selesai >= CURRENT_DATE()
                         AND c.dana_terkumpul < c.target_dana
                       ORDER BY c.id DESC
                       LIMIT 3");
$stmt->execute();
$campaigns = $stmt->fetchAll();
$campaignCount = count($campaigns);
$totalCollected = $pdo->query("SELECT COALESCE(SUM(dana_terkumpul),0) FROM campaigns")->fetchColumn();
$totalNeeded = $pdo->query("SELECT COALESCE(SUM(target_dana),0) FROM campaigns")->fetchColumn();
$totalDisbursed = $pdo->query("SELECT COALESCE(SUM(dana_tersalurkan),0) FROM campaigns")->fetchColumn();
$activeCampaignCount = $pdo->query("SELECT COUNT(*) FROM campaigns
                        WHERE status = 'aktif' AND dana_terkumpul < target_dana")->fetchColumn();
$completedCampaignCount = $pdo->query("SELECT COUNT(*) FROM campaigns
                          WHERE status = 'selesai'
                            OR (status = 'aktif' AND dana_terkumpul >= target_dana)")->fetchColumn();
$recentDonationsStmt = $pdo->query("SELECT nama_donatur, is_anonim, jumlah, pesan, paid_at, created_at
                                     FROM donations
                                     WHERE status = 'berhasil'
                                     ORDER BY COALESCE(paid_at, created_at) DESC, id DESC
                                     LIMIT 9");
$recentDonations = $recentDonationsStmt->fetchAll();
$collectedPercent = $totalNeeded > 0 ? round(($totalCollected / $totalNeeded) * 100, 1) : 0;
$collectedPercent = min(100, $collectedPercent);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Beranda - NusantaraHijau</title>
    <link rel="stylesheet" href="assets/css/public-navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/demo-pages.css">
    <link rel="stylesheet" href="assets/css/home-overrides.css">
</head>
<body class="demo-page">
<?php $navbar_demo = true; require __DIR__ . '/includes/public-navbar.php'; ?>

<section class="demo-hero">
  <div class="demo-container demo-hero-inner">
    <div>
      <h1>Bersama Pulihkan Laut &amp; <span>Hijaukan Daratan</span> Nusantara</h1>
      <p class="demo-lead">Galang dana transparan untuk mendukung restorasi terumbu karang, hutan mangrove, perlindungan satwa, dan aksi komunitas lokal.</p>
      <div class="demo-actions">
        <a class="demo-btn" href="daftar-kampanye.php">Mulai Donasi Sekarang</a>
        <a class="demo-btn secondary" href="aksi-relawan.php">Jelajahi Aksi Relawan</a>
      </div>
    </div>
    <div class="demo-monitor">
      <small class="demo-muted">PERBANDINGAN DANA</small>
      <h3 style="margin:8px 0 12px">Dana Terkumpul &amp; Dana Dibutuhkan</h3>
      <div class="demo-fund-row"><span><b>Dana Terkumpul</b><strong><?= format_rupiah($totalCollected) ?></strong></span></div>
      <div class="demo-progress"><span style="width:<?= $collectedPercent ?>%;background:#236d00"></span></div>
      <div class="demo-fund-row"><span><b>Dana Dibutuhkan</b><strong><?= format_rupiah($totalNeeded) ?></strong></span><em><?= $collectedPercent ?>%</em></div>
    </div>
  </div>
</section>

<section class="demo-container demo-stats">
  <div class="demo-stat">
    <div class="demo-stat-icon blue" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg></div>
    <div><strong><?= format_rupiah($totalCollected) ?></strong><small>Dana Terkumpul</small></div>
  </div>
  <div class="demo-stat">
    <div class="demo-stat-icon green" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/></svg></div>
    <div><strong><?= format_rupiah($totalDisbursed) ?></strong><small>Dana Tersalurkan</small></div>
  </div>
  <div class="demo-stat">
    <div class="demo-stat-icon cyan" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/><path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"/><path d="M8 6v8"/></svg></div>
    <div><strong><?= $activeCampaignCount ?> Kampanye</strong><small>Kampanye Aktif</small></div>
  </div>
  <div class="demo-stat">
    <div class="demo-stat-icon amber" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>
    <div><strong><?= $completedCampaignCount ?> Kampanye</strong><small>Kampanye Selesai</small></div>
  </div>
</section>

<main class="demo-container demo-section">
  <div class="demo-section-head">
    <div><div class="demo-kicker">Aksi Nyata Terverifikasi</div><h2>Kampanye Konservasi Pilihan</h2></div>
    <div class="demo-category-filters" role="group" aria-label="Filter kategori kampanye">
    <button class="demo-filter active" type="button" data-category-filter="all">Semua</button>
    <button class="demo-filter laut" type="button" data-category-filter="laut">Laut</button>
    <button class="demo-filter darat" type="button" data-category-filter="darat">Darat</button>
  </div>
  </div>

  

  <div class="demo-grid" id="homeCampaignGrid">
  <?php if (!empty($campaigns)): foreach ($campaigns as $camp):
      $percent = $camp['target_dana'] > 0 ? round(($camp['dana_terkumpul'] / $camp['target_dana']) * 100, 1) : 0;
      $percent = min(100, $percent);
      $daysLeft = max(0, (strtotime($camp['tgl_selesai']) - strtotime(date('Y-m-d'))) / 86400);
      $category = get_campaign_category($camp);
  ?>
    <article class="demo-card" data-campaign-category="<?= $category ?>">
      <?php if (!empty($camp['gambar'])): ?>
        <img class="demo-card-image" src="<?= htmlspecialchars($camp['gambar']) ?>" alt="<?= htmlspecialchars($camp['judul']) ?>">
      <?php else: ?>
        <div class="demo-card-image"></div>
      <?php endif; ?>
      <div class="demo-card-body">
        <div class="demo-muted"><span class="demo-category <?= $category === 'darat' ? 'darat' : 'laut' ?>"><?= ucfirst($category) ?></span> <?= htmlspecialchars($camp['organisasi'] ?: $camp['nama_penggalang'] ?: 'Mitra Konservasi') ?></div>
        <h3><?= htmlspecialchars($camp['judul']) ?></h3>
        <p class="demo-card-text"><?= htmlspecialchars(mb_strimwidth($camp['deskripsi'], 0, 150, '...')) ?></p>
        <div class="demo-card-bottom">
          <div class="demo-card-meta"><strong><?= format_rupiah($camp['dana_terkumpul']) ?></strong><strong><?= $percent ?>%</strong></div>
          <div class="demo-progress"><span style="width:<?= $percent ?>%"></span></div>
          <div class="demo-card-meta demo-muted"><span>Target <?= format_rupiah($camp['target_dana']) ?></span><span><?= ceil($daysLeft) ?> hari lagi</span></div>
          <a class="demo-btn <?= $category === 'darat' ? 'green' : '' ?>" style="width:100%" href="donasi.php?campaign_id=<?= (int)$camp['id'] ?>">Donasi Sekarang</a>
        </div>
      </div>
    </article>
  <?php endforeach; else: ?>
    <div class="demo-empty">Belum ada kampanye aktif yang dapat ditampilkan.</div>
  <?php endif; ?>
  </div>

  <div class="demo-all-campaigns-wrap">
    <a class="demo-all-campaigns demo-btn" href="daftar-kampanye.php">Lihat Semua Kampanye <span aria-hidden="true">→</span></a>
  </div>
</main>

<section class="demo-container donor-section">
  <div class="demo-section-head">
    <div><div class="demo-kicker">Donasi Terbaru</div><h2>Rangkaian Kepedulian untuk Bumi</h2></div>
  </div>

  <div class="donor-carousel" id="donor-carousel" aria-live="polite">
  <?php foreach (array_slice($recentDonations, 0, 4) as $index => $donation):
      $donorName = $donation['is_anonim'] ? 'Hamba Allah' : ($donation['nama_donatur'] ?: 'Donatur');
      $donorInitial = mb_strtoupper(mb_substr($donorName, 0, 1));
      $donorMessage = trim((string)($donation['pesan'] ?? '')) ?: 'Semoga kontribusi ini membawa dampak baik untuk alam Indonesia.';
  ?>
    <article class="donor-card" data-donor-card>
      <div class="donor-stars" aria-label="Pesan dan harapan">Pesan dan Harapan</div>
      <p class="donor-message">"<?= htmlspecialchars($donorMessage) ?>"</p>
      <div class="donor-profile">
        <div class="donor-avatar"><?= htmlspecialchars($donorInitial) ?></div>
        <div>
          <strong><?= htmlspecialchars($donorName) ?></strong>
          <span>Donasi <?= format_rupiah((float)$donation['jumlah']) ?></span>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
</section>

<section class="demo-register home-donation-banner">
  <div class="demo-container demo-bottom-cta">
    <div>
      <div class="demo-kicker" style="color:#b9e5ff">MULAI LANGKAH NYATA HARI INI</div>
      <h2>Satu Donasi Kecil, Dampak Nyata untuk Generasi Masa Depan Indonesia.</h2>
      <p>Mulai dari Rp1.000 dan ikut mendukung pemulihan ekosistem laut serta hutan Nusantara.</p>
    </div>
    <div class="demo-actions">
      <a class="demo-btn green" href="daftar-kampanye.php">Berdonasi Sekarang</a>
      <a class="demo-btn secondary" href="artikel-edukasi.php">Pelajari Tentang Alam</a>
    </div>
  </div>
</section>

<script>
    document.querySelectorAll('[data-category-filter]').forEach(function(button) {
      button.addEventListener('click', function() {
        document.querySelectorAll('[data-category-filter]').forEach(function(item) { item.classList.remove('active'); });
        button.classList.add('active');
        const category = button.dataset.categoryFilter;
        document.querySelectorAll('#homeCampaignGrid [data-campaign-category]').forEach(function(card) {
          card.style.display = category === 'all' || card.dataset.campaignCategory === category ? '' : 'none';
        });
      });
    });
  </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
