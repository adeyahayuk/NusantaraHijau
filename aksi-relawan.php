<?php
// ========================================================
// AKSI RELAWAN (DEMO SEDERHANA TANPA CSS & JS)
// Menampilkan daftar kegiatan relawan + tombol "Daftar sebagai relawan"
// yang diarahkan ke form identitas dan submit
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$success_message = '';
$error_message = '';

// Proses submit pendaftaran relawan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_register_volunteer'])) {
    $activity_id = (int)($_POST['activity_id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alasan = trim($_POST['alasan'] ?? '');

    if ($activity_id <= 0 || empty($nama) || empty($email) || empty($no_hp) || empty($alasan)) {
        $error_message = 'Semua kolom formulir pendaftaran relawan wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format alamat email tidak valid!';
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO volunteer_registrations (activity_id, nama, email, no_hp, alasan, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmtInsert->execute([$activity_id, $nama, $email, $no_hp, $alasan]);

        $activityLink = $pdo->prepare("SELECT va.link_grup_wa, c.judul AS campaign_title
                                       FROM volunteer_activities va
                                       LEFT JOIN campaigns c ON c.id = va.campaign_id
                                       WHERE va.id = ? LIMIT 1");
        $activityLink->execute([$activity_id]);
        $activityData = $activityLink->fetch();
        $waLink = trim($activityData['link_grup_wa'] ?? '') ?: '';

        if ($waLink === '' || str_contains($waLink, 'demo-relawan')) {
            $campaignName = $activityData['campaign_title'] ?? 'kampanye NusantaraHijau';
            $waMessage = 'Halo, saya telah mendaftar sebagai relawan untuk aksi kampanye ' . $campaignName . '.';
            $waLink = 'https://wa.me/?text=' . rawurlencode($waMessage);
        }

        header('Location: ' . $waLink);
        exit;
    }
}

// Sinkronkan status kegiatan relawan dengan masa kampanye.
// Kampanye baru otomatis memunculkan kegiatan; kampanye habis masa berlaku tidak ditampilkan.
$pdo->exec("UPDATE volunteer_activities va
            JOIN campaigns c ON c.id = va.campaign_id
            SET va.status = 'selesai'
            WHERE c.tgl_selesai < CURRENT_DATE()
               OR c.status <> 'aktif'");

// Pastikan setiap kampanye memiliki minimal satu kegiatan relawan.
// Ini menjaga halaman tetap terhubung meskipun database belum memiliki seed activity tambahan.
$campaignsForVolunteer = $pdo->query("SELECT id, judul, deskripsi, lokasi, status
                                       FROM campaigns
                                       WHERE status = 'aktif'
                                         AND tgl_selesai >= CURRENT_DATE()
                                       ORDER BY id ASC")->fetchAll();
foreach ($campaignsForVolunteer as $campaignForVolunteer) {
    $activityCheck = $pdo->prepare("SELECT id FROM volunteer_activities WHERE campaign_id = ? LIMIT 1");
    $activityCheck->execute([(int)$campaignForVolunteer['id']]);

    $existingActivity = $activityCheck->fetch();

    if (!$existingActivity) {
        $activityInsert = $pdo->prepare("INSERT INTO volunteer_activities
            (campaign_id, admin_id, judul, deskripsi, lokasi, tanggal, kuota, link_grup_wa, status, created_at)
            VALUES (?, 1, ?, ?, ?, DATE_ADD(CURRENT_DATE(), INTERVAL 14 DAY), 50,
                    'https://chat.whatsapp.com/demo-relawan', 'buka', NOW())");
        $activityInsert->execute([
            (int)$campaignForVolunteer['id'],
            $campaignForVolunteer['judul'],
            'Bergabung mendukung pelaksanaan kampanye melalui aksi lapangan, edukasi, pendataan, dan kegiatan sukarela sesuai kebutuhan mitra lokal.',
            $campaignForVolunteer['lokasi']
        ]);
    } else {
        $pdo->prepare("UPDATE volunteer_activities
                       SET status = 'buka'
                       WHERE id = ?")->execute([(int)$existingActivity['id']]);
    }
}

// Muat ulang daftar agar kegiatan yang baru dibuat otomatis tampil.
$activities = $pdo->query("SELECT va.*, c.judul AS campaign_title, c.gambar AS campaign_image
                           FROM volunteer_activities va
                           LEFT JOIN campaigns c ON va.campaign_id = c.id
                           WHERE va.status = 'buka'
                             AND c.status = 'aktif'
                             AND c.tgl_selesai >= CURRENT_DATE()
                           ORDER BY va.id DESC")->fetchAll();

$selected_act_id = (int)($_GET['act_id'] ?? ($activities[0]['id'] ?? 0));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aksi Relawan - NusantaraHijau</title>
    <link rel="stylesheet" href="assets/css/public-navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/demo-pages.css">
    <link rel="stylesheet" href="assets/css/location-hero-overrides.css">
</head>
  <body class="demo-page volunteer-hero-page">

    <?php $navbar_demo = true; require __DIR__ . '/includes/public-navbar.php'; ?>

<section class="demo-hero">
  <div class="demo-container demo-hero-inner demo-relawan-hero">
    <div>
      <h1>Bergabung Jadi Relawan <span>Aksi Nyata Nusantara</span></h1>
      <p class="demo-lead">Turun langsung ke pesisir, hutan tropis, dan gugusan terumbu karang dengan pendampingan lapangan serta perlindungan keselamatan.</p>
    </div>
  </div>
</section>

<main class="demo-container demo-section">
  <div class="demo-section-head"><div><div class="demo-kicker">Daftar Kampanye</div><h2>Pilihan Aksi Relawan</h2></div></div>
  <?php if ($success_message): ?><div class="demo-alert success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
  <?php if ($error_message): ?><div class="demo-alert error"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

  <div class="demo-relawan-grid">
  <?php if (!empty($activities)): foreach ($activities as $act): ?>
    <article class="demo-card">
      <?php if (!empty($act['campaign_image'])): ?>
        <img class="demo-card-image" style="height:170px" src="<?= htmlspecialchars($act['campaign_image']) ?>" alt="<?= htmlspecialchars($act['judul']) ?>">
      <?php else: ?>
        <div class="demo-card-image" style="height:170px;background:linear-gradient(135deg,#dff1fa,#eaf5df)"></div>
      <?php endif; ?>
      <div class="demo-card-body">
        <div class="demo-muted"><?= date('d M Y', strtotime($act['tanggal'])) ?> • <?= htmlspecialchars($act['lokasi']) ?></div>
        <h3><?= htmlspecialchars($act['judul']) ?></h3>
        <p class="demo-card-text"><?= htmlspecialchars(mb_strimwidth($act['deskripsi'], 0, 150, '...')) ?></p>
        <div class="demo-card-bottom">
          <button type="button" class="demo-btn volunteer-register-btn" style="width:100%" data-activity-id="<?= (int)$act['id'] ?>" data-activity-name="<?= htmlspecialchars($act['judul'], ENT_QUOTES) ?>">Daftar sebagai Relawan</button>
        </div>
      </div>
    </article>
  <?php endforeach; else: ?>
    <div class="demo-empty">Belum ada kegiatan relawan yang dibuka saat ini.</div>
  <?php endif; ?>
  </div>
</main>

<div id="volunteer-modal" class="demo-modal" aria-hidden="true">
  <div class="demo-modal-backdrop"></div>
  <div class="demo-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="volunteer-modal-title">
    <button type="button" class="demo-modal-close" aria-label="Tutup">×</button>
    <div class="demo-kicker">Registrasi</div>
    <h2 id="volunteer-modal-title" style="margin:5px 0 6px;font-size:22px">Formulir Pendaftaran Relawan Aksi</h2>
    <p class="demo-muted" style="margin-bottom:16px">Lengkapi identitas Anda. Setelah dikirim, Anda akan diarahkan ke grup WhatsApp aksi kampanye yang dipilih.</p>
    <form method="POST" action="aksi-relawan.php">
      <input type="hidden" name="action_register_volunteer" value="1">
      <div class="demo-form-grid">
        <div class="demo-field"><label>Kegiatan *</label><input id="activity-name" type="text" readonly value=""><input id="activity-select" type="hidden" name="activity_id" value="" required></div>
        <div class="demo-field"><label>Nama Lengkap *</label><input type="text" name="nama" required value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>"></div>
        <div class="demo-field"><label>Alamat Email *</label><input type="email" name="email" required></div>
        <div class="demo-field"><label>Nomor WhatsApp / HP *</label><input type="text" name="no_hp" required placeholder="0812XXXXXXXX"></div>
      </div>
      <div class="demo-field" style="margin-top:13px"><label>Alasan Ingin Bergabung *</label><textarea name="alasan" required placeholder="Tuliskan motivasi atau pengalaman singkat Anda..."></textarea></div>
      <button class="demo-btn green" type="submit" style="width:100%;margin-top:14px">Kirim Pendaftaran Relawan</button>
    </form>
  </div>
</div>

<script>
(function(){
  var modal=document.getElementById('volunteer-modal');
  var select=document.getElementById('activity-select');
  var activityNameInput=document.getElementById('activity-name');
  function openModal(activityId,activityName){if(activityId&&select){select.value=String(activityId)}activityNameInput.value=activityName||'';modal.classList.add('open');modal.setAttribute('aria-hidden','false');document.body.style.overflow='hidden'}
  function closeModal(){modal.classList.remove('open');modal.setAttribute('aria-hidden','true');document.body.style.overflow=''}
  document.querySelectorAll('.volunteer-register-btn').forEach(function(btn){btn.addEventListener('click',function(){openModal(btn.dataset.activityId,btn.dataset.activityName)})});
  modal.querySelector('.demo-modal-close').addEventListener('click',closeModal);
  modal.querySelector('.demo-modal-backdrop').addEventListener('click',closeModal);
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
