<?php
// ========================================================
// DETAIL KAMPANYE SAYA (KONTRIBUTOR)
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'contributor') {
    header('Location: login.php');
    exit;
}

$accountId = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT c.*, u.nama AS pic_name, u.organisasi, u.no_hp 
                       FROM campaigns c 
                       LEFT JOIN users u ON u.id = c.user_id 
                       WHERE c.id = ? AND c.user_id = ?');
$stmt->execute([$id, $accountId]);
$campaign = $stmt->fetch();

if (!$campaign) {
    header('Location: kampanye-saya.php');
    exit;
}

$percent = $campaign['target_dana'] > 0 ? min(100, round($campaign['dana_terkumpul'] / $campaign['target_dana'] * 100)) : 0;
$deadline = date('d F Y', strtotime($campaign['tgl_selesai']));
$sisaHari = max(0, (int) ceil((strtotime($campaign['tgl_selesai']) - strtotime('today')) / 86400));

$image = $campaign['gambar'];
  if (!is_file(__DIR__ . '/' . $image)) {
    $png = preg_replace('/\.jpe?g$/i', '.png', $image);
    if ($png && is_file(__DIR__ . '/' . $png)) $image = $png;
}
  $hasImage = is_file(__DIR__ . '/' . $image);

$page_title = 'Detail Kampanye';
$body_class = 'campaign-detail-page';
require_once __DIR__ . '/includes/header.php';
?>

<main class="campaign-detail-main">
  <a class="back-campaigns" href="kampanye-saya.php">←&nbsp; Kembali ke Kampanye Saya</a>

  <?php if (isset($_GET['submitted'])): ?>
    <div class="detail-success">Pengajuan kampanye berhasil dikirim dan sedang menunggu verifikasi oleh tim admin!</div>
  <?php endif; ?>

  <div class="detail-badges">
    <span class="detail-status <?= htmlspecialchars($campaign['status']) ?>">
      ● <?= $campaign['status'] === 'aktif' ? 'Berjalan' : ($campaign['status'] === 'ditolak' ? 'Ditolak' : 'Menunggu Verifikasi') ?>
    </span>
    <span>Kampanye Milik Anda</span>
  </div>

  <h1><?= htmlspecialchars($campaign['judul']) ?></h1>

  <section class="detail-top">
    <div>
      <div class="detail-photo<?= $hasImage ? ' has-image' : '' ?>"<?php if ($hasImage): ?> style="background-image:url('<?= htmlspecialchars($image) ?>')"<?php endif; ?>>
        <span>Program Konservasi</span>
        <b>Dokumentasi Tim Lapangan</b>
      </div>
      <div class="detail-mini-stats">
        <div>
          <small>Status Program</small>
          <strong><?= strtoupper(htmlspecialchars($campaign['status'])) ?></strong>
          <em>Verifikasi Admin</em>
        </div>
        <div>
          <small>Donatur Berpartisipasi</small>
          <strong><?= number_format((int)$campaign['donor_count']) ?> Orang</strong>
          <em>Donatur Terdaftar</em>
        </div>
        <div>
          <small>Sisa Waktu</small>
          <strong><?= $sisaHari ?> Hari</strong>
          <em>Hingga Tenggat</em>
        </div>
      </div>
    </div>

    <div class="detail-information">
      <section>
        <small>LOKASI KONSERVASI</small>
        <h2><?= htmlspecialchars($campaign['lokasi']) ?></h2>
      </section>

      <section>
        <small>TENGGAT WAKTU PENDANAAN</small>
        <h2>Batas Waktu: <?= htmlspecialchars($deadline) ?></h2>
        <p><b>Sisa <?= $sisaHari ?> hari lagi</b></p>
      </section>

      <section>
        <h3>Penanggung Jawab Lapangan &amp; Organisasi</h3>
        <div class="detail-contact">
          <span><?= htmlspecialchars(strtoupper(substr($campaign['pic_name'] ?: 'NH', 0, 2))) ?></span>
          <div>
            <h2><?= htmlspecialchars($campaign['pic_name'] ?: 'Kontributor') ?></h2>
            <p><?= htmlspecialchars($campaign['organisasi'] ?: 'Inisiator Konservasi') ?></p>
          </div>
          <b><?= htmlspecialchars($campaign['no_hp'] ?: '-') ?></b>
        </div>
        <small>DESKRIPSI LENGKAP PROGRAM</small>
        <p class="detail-description"><?= nl2br(htmlspecialchars($campaign['deskripsi'])) ?></p>
      </section>
    </div>
  </section>

  <section class="detail-funding">
    <small>AKUMULASI PENDANAAN PUBLIK</small>
    <div>
      <strong><?= format_rupiah($campaign['dana_terkumpul']) ?></strong>
      <b><?= $percent ?>% Terpenuhi</b>
      <span>Target Keseluruhan <em><?= format_rupiah($campaign['target_dana']) ?></em></span>
      <span>Dukungan Publik <em><?= number_format((int)$campaign['donor_count'], 0, ',', '.') ?> Donatur Terdaftar</em></span>
    </div>
    <div class="detail-progress"><i style="width:<?= $percent ?>%"></i></div>
    <p>
      Terkumpul: <?= format_rupiah($campaign['dana_terkumpul']) ?>
      <span>Kekurangan: <?= format_rupiah(max(0, $campaign['target_dana'] - $campaign['dana_terkumpul'])) ?></span>
    </p>
  </section>
</main>

<script src="assets/js/main.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
