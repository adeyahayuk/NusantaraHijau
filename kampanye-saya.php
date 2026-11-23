<?php
// ========================================================
// MODUL KAMPANYE SAYA (KONTRIBUTOR / PENGGALANG DANA)
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Pastikan user adalah contributor
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'contributor') {
    header('Location: login.php');
    exit;
}

$accountId = (int)$_SESSION['user_id'];

// Ambil kampanye milik user ini (11 Tabel Final)
$campaignStmt = $pdo->prepare('SELECT id, judul, lokasi, kategori, target_dana, dana_terkumpul, donor_count, deskripsi, gambar, status, tgl_mulai, tgl_selesai, catatan_admin, created_at
    FROM campaigns WHERE user_id = ? ORDER BY id DESC');
$campaignStmt->execute([$accountId]);
$campaigns = $campaignStmt->fetchAll();

$statusCount = ['all' => count($campaigns), 'active' => 0, 'pending' => 0, 'rejected' => 0, 'completed' => 0];
foreach ($campaigns as $campaign) {
    if ($campaign['status'] === 'aktif') $statusCount['active']++;
    elseif ($campaign['status'] === 'pending') $statusCount['pending']++;
    elseif ($campaign['status'] === 'ditolak') $statusCount['rejected']++;
    elseif ($campaign['status'] === 'selesai') $statusCount['completed']++;
}

function campaign_status_meta(string $status): array {
    return match ($status) {
        'aktif' => ['Berjalan', 'active'],
        'pending' => ['Menunggu Verifikasi', 'pending'],
        'ditolak' => ['Ditolak', 'rejected'],
        'selesai' => ['Selesai', 'completed'],
        default => ['Menunggu', 'pending'],
    };
}

$page_title = 'Kampanye Saya';
$body_class = 'my-campaigns-page';
require_once __DIR__ . '/includes/header.php';
?>

<main class="my-campaigns-main">
  <header class="campaigns-heading">
    <div>
      <div class="campaigns-title-line">
        <h1>Kampanye Saya</h1>
        <span><?= $statusCount['all'] ?> Terdaftar</span>
      </div>
      <p>Kelola pengajuan galang dana, pantau capaian donasi, dan laporkan verifikasi dampak konservasi alam Anda.</p>
    </div>
    <a class="campaign-create-btn" href="ajukan-galang-dana.php">
      <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Ajukan Galang Dana
    </a>
  </header>

  <div class="campaigns-toolbar">
    <div class="campaign-filter-tabs" role="tablist" aria-label="Filter kampanye">
      <button class="campaign-filter active" type="button" data-filter="all">Semua <span><?= $statusCount['all'] ?></span></button>
      <button class="campaign-filter" type="button" data-filter="active">Berjalan <span><?= $statusCount['active'] ?></span></button>
      <button class="campaign-filter" type="button" data-filter="pending">Menunggu Verifikasi <span><?= $statusCount['pending'] ?></span></button>
      <button class="campaign-filter" type="button" data-filter="rejected">Ditolak <span><?= $statusCount['rejected'] ?></span></button>
      <button class="campaign-filter" type="button" data-filter="completed">Selesai <span><?= $statusCount['completed'] ?></span></button>
    </div>

    <div class="campaign-category-filters" role="group" aria-label="Filter kategori konservasi">
      <button class="campaign-category-filter active" type="button" data-category="all">Semua Kategori</button>
      <button class="campaign-category-filter laut" type="button" data-category="laut">Laut</button>
      <button class="campaign-category-filter darat" type="button" data-category="darat">Darat</button>
    </div>
  </div>

  <section class="campaigns-grid" id="campaignsGrid" aria-label="Daftar kampanye Anda">
    <?php foreach ($campaigns as $campaign): ?>
      <?php 
        [$statusLabel, $statusClass] = campaign_status_meta($campaign['status']);
        $percent = $campaign['target_dana'] > 0 ? min(100, round(($campaign['dana_terkumpul'] / $campaign['target_dana']) * 100)) : 0;
        $imagePath = $campaign['gambar'] ?? 'assets/img/campaign-cenderawasih.png';
        if (!empty($imagePath) && !file_exists(__DIR__ . '/' . $imagePath)) {
            $pngFallback = preg_replace('/\.jpe?g$/i', '.png', $imagePath);
            if ($pngFallback && file_exists(__DIR__ . '/' . $pngFallback)) $imagePath = $pngFallback;
        }
        $photoAvailable = !empty($imagePath) && file_exists(__DIR__ . '/' . $imagePath);
        $category = get_campaign_category($campaign);
      ?>
      <article class="campaign-card" data-status="<?= $statusClass ?>" data-category="<?= $category ?>">
        <div class="campaign-card-visual<?= $photoAvailable ? ' has-photo' : '' ?>"<?php if ($photoAvailable): ?> style="background-image: url('<?= htmlspecialchars($imagePath) ?>')"<?php endif; ?>>
          <span class="campaign-status <?= $statusClass ?>">
            <span class="status-symbol"><?= $statusClass === 'active' ? '●' : ($statusClass === 'rejected' ? '⊗' : '◷') ?></span>
            <?= $statusLabel ?>
          </span>
          <div class="campaign-visual-meta">
            <span><?= htmlspecialchars($campaign['lokasi']) ?></span>
            <?php if ($statusClass === 'active'): ?>
              <span><?= number_format((int) $campaign['donor_count'], 0, ',', '.') ?> Donatur</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="campaign-card-content">
          <span class="campaign-category-badge <?= $category ?>"><?= ucfirst($category) ?></span>
          <h2><?= htmlspecialchars($campaign['judul']) ?></h2>
          <p class="campaign-date">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="1"/><path d="M8 3v4m8-4v4M4 10h16"/></svg>
            Diajukan: <?= date('d M Y', strtotime($campaign['created_at'])) ?>
          </p>

          <?php if ($statusClass === 'active'): ?>
            <div class="campaign-funds">
              <span>Dana Terkumpul <strong><?= format_rupiah($campaign['dana_terkumpul']) ?></strong></span>
              <b><?= $percent ?>%</b>
            </div>
            <div class="campaign-progress"><span style="width: <?= $percent ?>%"></span></div>
            <div class="campaign-funds-foot">
              <span>Target: <?= format_rupiah($campaign['target_dana']) ?></span>
              <strong>Tenggat: <?= date('d M Y', strtotime($campaign['tgl_selesai'])) ?></strong>
            </div>
          <?php elseif ($statusClass === 'pending'): ?>
            <div class="campaign-note pending">
              <strong><span>♜</span> Dalam Peninjauan Admin</strong>
              <p>Proposal kampanye sedang ditinjau oleh admin verifikator NusantaraHijau.</p>
            </div>
            <div class="campaign-estimate">
              <span>Pengajuan Target <strong><?= format_rupiah($campaign['target_dana']) ?></strong></span>
              <b>Est. 1×24 Jam Kerja</b>
            </div>
          <?php elseif ($statusClass === 'rejected'): ?>
            <div class="campaign-note rejected">
              <strong><span>△</span> Ditolak</strong>
              <p><?= htmlspecialchars($campaign['catatan_admin'] ?: 'Detail pengajuan belum memenuhi syarat verifikasi program.') ?></p>
            </div>
            <div class="campaign-target">Target Pengajuan: <?= format_rupiah($campaign['target_dana']) ?></div>
          <?php else: ?>
            <div class="campaign-note completed">
              <strong>✓ Kampanye Selesai</strong>
              <p>Target penggalangan dana program telah tercapai sepenuhnya.</p>
            </div>
          <?php endif; ?>

          <a href="detail-kampanye.php?id=<?= (int) $campaign['id'] ?>" class="campaign-detail-btn <?= $statusClass === 'rejected' ? 'rejected' : ($category === 'darat' ? 'darat' : '') ?>">
            Lihat Detail <span>→</span>
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <div class="campaigns-empty" id="campaignsEmpty" <?= count($campaigns) > 0 ? 'hidden' : '' ?>>
    Belum ada kampanye dalam kategori ini.
  </div>
</main>

<script>
let activeStatusFilter = 'all';
let activeCategoryFilter = 'all';

function applyCampaignFilters() {
  let visible = 0;
  document.querySelectorAll('.campaign-card').forEach(card => {
    const statusMatch = activeStatusFilter === 'all' || card.dataset.status === activeStatusFilter;
    const categoryMatch = activeCategoryFilter === 'all' || card.dataset.category === activeCategoryFilter;
    const show = statusMatch && categoryMatch;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('campaignsEmpty').hidden = visible > 0;
}

document.querySelectorAll('.campaign-filter').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.campaign-filter').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    activeStatusFilter = this.dataset.filter;
    applyCampaignFilters();
  });
});

document.querySelectorAll('.campaign-category-filter').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.campaign-category-filter').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    activeCategoryFilter = this.dataset.category;
    applyCampaignFilters();
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
