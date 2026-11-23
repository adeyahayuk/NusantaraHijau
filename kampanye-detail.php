<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = max(1, (int) ($_GET['id'] ?? 1));
$stmt = $pdo->prepare("SELECT c.*, u.nama AS nama_penggalang, u.organisasi
                       FROM campaigns c
                       LEFT JOIN users u ON c.user_id = u.id
                       WHERE c.id = ? LIMIT 1");
$stmt->execute([$id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    $campaign = $pdo->query("SELECT c.*, u.nama AS nama_penggalang, u.organisasi
                             FROM campaigns c
                             LEFT JOIN users u ON c.user_id = u.id
                             ORDER BY c.id ASC LIMIT 1")->fetch();
}

if (!$campaign) {
    die('Kampanye tidak ditemukan.');
}

$target = (float) $campaign['target_dana'];
$collected = (float) $campaign['dana_terkumpul'];
$percent = $target > 0 ? min(100, round(($collected / $target) * 100)) : 0;
$daysLeft = max(0, (int) ceil((strtotime($campaign['tgl_selesai']) - strtotime('today')) / 86400));
$image = !empty($campaign['gambar']) ? $campaign['gambar'] : 'assets/img/campaign-cenderawasih.png';
$page_title = $campaign['judul'];
$body_class = 'reference-campaign-detail';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/css/reference-campaign-detail.css">
<main class="rcd-page">
  <div class="rcd-container">
    <nav class="rcd-breadcrumb"><a href="index.php">Beranda</a><span>›</span><a href="daftar-kampanye.php">Kampanye</a><span>›</span><strong><?= htmlspecialchars($campaign['judul']) ?></strong></nav>

    <div class="rcd-grid">
      <section class="rcd-main">
        <div class="rcd-badges"><span class="rcd-verified">✓ Terverifikasi</span><span class="rcd-location">Lokasi: <?= htmlspecialchars($campaign['lokasi']) ?></span></div>
        <h1><?= htmlspecialchars($campaign['judul']) ?></h1>

        <div class="rcd-organizer"><div class="rcd-org-avatar">NH</div><div><strong><?= htmlspecialchars($campaign['nama_penggalang'] ?: 'NusantaraHijau') ?></strong><small><?= htmlspecialchars($campaign['organisasi'] ?: 'Mitra Konservasi Terverifikasi') ?></small></div></div>

        <div class="rcd-gallery"><img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($campaign['judul']) ?>"></div>

        <div class="rcd-tabs"><button class="active" type="button" data-tab="story">Cerita Penggalangan</button><button type="button" data-tab="report">Laporan Transparansi</button><button type="button" data-tab="news">Kabar Terbaru</button></div>
        <div id="story" class="rcd-panel active"><div class="rcd-alert">! Kondisi yang membutuhkan dukungan Anda sekarang</div><p><?= nl2br(htmlspecialchars($campaign['deskripsi'])) ?></p><div class="rcd-impact"><article><strong>Ekosistem</strong><p>Pemulihan kawasan laut dan darat melalui pendampingan mitra lokal.</p></article><article><strong>Monitoring</strong><p>Pemantauan berkala dan pelaporan perkembangan lapangan.</p></article><article><strong>Kolaborasi</strong><p>Keterlibatan relawan, komunitas, dan organisasi setempat.</p></article></div></div>
        <div id="report" class="rcd-panel"><div class="rcd-story-card"><h3>Rincian Alokasi Dana</h3><p>Pengadaan dan operasional lapangan, pendampingan relawan, monitoring, serta pelaporan audit transparan.</p></div></div>
        <div id="news" class="rcd-panel"><div class="rcd-story-card"><h3>Update Lapangan Terbaru</h3><p>Program terus dipantau dan diperbarui sesuai perkembangan pelaksanaan kampanye.</p></div></div>
      </section>

      <aside class="rcd-donation"><div class="rcd-donation-label"><span>DANA TERKUMPUL</span><strong><?= $percent ?>%</strong></div><div class="rcd-total"><?= format_rupiah($collected) ?></div><div class="rcd-progress"><span style="width:<?= $percent ?>%"></span></div><div class="rcd-donation-meta"><span><b><?= number_format((int) ($campaign['donor_count'] ?? 0)) ?></b> Donatur</span><span><b>Sisa <?= $daysLeft ?> hari</b></span></div><div class="rcd-target"><span>Target</span><strong><?= format_rupiah($target) ?></strong></div><a class="rcd-pay" href="donasi.php?campaign_id=<?= (int) $campaign['id'] ?>">Donasi untuk Kampanye Ini</a><p class="rcd-safe"></p></aside>
    </div>
  </div>
</main>
<script>
document.querySelectorAll('.rcd-tabs button').forEach(function(btn){btn.addEventListener('click',function(){document.querySelectorAll('.rcd-tabs button').forEach(function(b){b.classList.remove('active')});document.querySelectorAll('.rcd-panel').forEach(function(p){p.classList.remove('active')});btn.classList.add('active');document.getElementById(btn.dataset.tab).classList.add('active')})});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
