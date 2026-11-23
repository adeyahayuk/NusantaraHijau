<?php
// ========================================================
// AJUKAN GALANG DANA (KONTRIBUTOR / PENGGALANG DANA)
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Pastikan sudah login sebagai kontributor
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'contributor') {
    header('Location: login.php');
    exit;
}

$accountId = (int)$_SESSION['user_id'];

// Ambil data kontak penggalang dari tabel users
$userStmt = $pdo->prepare('SELECT nama, no_hp, organisasi FROM users WHERE id = ?');
$userStmt->execute([$accountId]);
$userData = $userStmt->fetch() ?: ['nama' => '', 'no_hp' => '', 'organisasi' => ''];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['title'] ?? '');
    $deskripsi = trim($_POST['description'] ?? '');
    $lokasi = trim($_POST['location'] ?? '');
    $kategori = ($_POST['category'] ?? 'laut') === 'darat' ? 'darat' : 'laut';
    $target_dana = (float) preg_replace('/[^0-9]/', '', $_POST['target_amount'] ?? '');
    $deadline = $_POST['deadline'] ?? '';
    $picName = trim($_POST['pic_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $imagePath = 'assets/img/campaign-cenderawasih.png';

    if ($judul === '' || $deskripsi === '' || $lokasi === '' || $target_dana < 1000000 || $deadline === '' || $picName === '' || !isset($_POST['agreement'])) {
        $error = 'Lengkapi seluruh data wajib (Target minimal Rp 1.000.000) dan setujui pernyataan pengajuan.';
    } elseif (strtotime($deadline) <= strtotime('today')) {
        $error = 'Batas waktu penggalangan harus setelah hari ini.';
    }

    // Upload satu-satunya berkas: Gambar Kampanye
    if ($error === '' && isset($_FILES['campaign_image']) && $_FILES['campaign_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['campaign_image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $mime = function_exists('mime_content_type') && is_uploaded_file($file['tmp_name']) ? mime_content_type($file['tmp_name']) : '';
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024 || !isset($allowed[$mime])) {
            $error = 'Foto utama harus JPG/PNG dan maksimal 5MB.';
        } else {
            $uploadDir = __DIR__ . '/assets/uploads/campaigns';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                $error = 'Folder unggahan foto tidak dapat dibuat.';
            } else {
                $filename = 'campaign-' . $accountId . '-' . time() . '.' . $allowed[$mime];
                if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                    $imagePath = 'assets/uploads/campaigns/' . $filename;
                } else {
                    $error = 'Foto utama tidak dapat diunggah.';
                }
            }
        }
    }

    if ($error === '') {
        // Buat slug sederhana dari judul
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul), '-'));

        // Update nomor telepon penggalang di users jika ada perubahan
        if (!empty($phone) && $phone !== $userData['no_hp']) {
            $pdo->prepare("UPDATE users SET no_hp = ? WHERE id = ?")->execute([$phone, $accountId]);
        }

        // Simpan ke tabel campaigns (11 Tabel Final)
        $stmtInsert = $pdo->prepare('INSERT INTO campaigns 
            (user_id, judul, slug, deskripsi, lokasi, gambar, target_dana, dana_terkumpul, dana_tersalurkan, donor_count, tgl_mulai, tgl_selesai, status, catatan_admin, created_at, kategori)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, 0.00, 0, CURRENT_DATE(), ?, "pending", "Pengajuan baru menunggu verifikasi admin.", NOW(), ?)');
        $stmtInsert->execute([
            $accountId,
            $judul,
            $slug,
            $deskripsi,
            $lokasi,
            $imagePath,
            $target_dana,
            $deadline,
            $kategori
        ]);

        $newCampaignId = (int)$pdo->lastInsertId();

        // Catat activity log
        log_activity($pdo, 'user', $accountId, 'Mengajukan Kampanye Baru', 'campaigns', $newCampaignId, "Pengajuan kampanye {$judul}");

        header('Location: detail-kampanye.php?id=' . $newCampaignId . '&submitted=1');
        exit;
    }
}

$page_title = 'Ajukan Galang Dana';
$body_class = 'campaign-submit-page';
require_once __DIR__ . '/includes/header.php';
?>

<main class="campaign-submit-main">
  <header class="submit-heading">
    <div>
      <h1>Ajukan Galang Dana Konservasi</h1>
      <p>Setiap pengajuan kampanye akan diverifikasi secara teliti oleh admin dalam 1×24 jam sebelum dipublikasikan ke publik.</p>
    </div>
    <span class="submit-review">Estimasi Verifikasi: &lt; 24 Jam</span>
  </header>

  <?php if ($error): ?>
    <div class="submit-alert" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form class="campaign-submit-card" method="post" enctype="multipart/form-data">
    <!-- SEKSI 1: INFORMASI INTI AKSI -->
    <section class="submit-section">
      <div class="section-title">
        <span>1</span>
        <h2>Informasi Inti Program</h2>
        <b>* Wajib diisi</b>
      </div>
      <label>
        Judul Kampanye <small>Maksimal 120 karakter</small>
        <input name="title" maxlength="120" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" placeholder="Contoh: Restorasi Terumbu Karang Teluk Cenderawasih">
      </label>
      <label>
        Deskripsi Permasalahan dan Rencana Aksi
        <textarea name="description" rows="5" required placeholder="Jelaskan kondisi ekosistem saat ini, tujuan konservasi, dan bagaimana dana donasi akan digunakan..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </label>
      <label class="location-label">
        Lokasi Pelaksanaan Konservasi
        <input name="location" required value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" placeholder="Contoh: Pantai Pangumbahan, Kabupaten Sukabumi, Jawa Barat">
      </label>

      <!-- Pilihan Kategori Konservasi -->
      <div class="category-options">
        <span>Pilih Kategori Konservasi</span>
        <label class="category-choice laut">
          <input type="radio" name="category" value="laut" <?= (($_POST['category'] ?? 'laut') === 'laut') ? 'checked' : '' ?>>
          Laut
        </label>
        <label class="category-choice darat">
          <input type="radio" name="category" value="darat" <?= (($_POST['category'] ?? '') === 'darat') ? 'checked' : '' ?>>
          Darat
        </label>
      </div>
    </section>

    <!-- SEKSI 2: TARGET & LINIMASA -->
    <section class="submit-section">
      <div class="section-title">
        <span class="green">2</span>
        <h2>Target Dana &amp; Linimasa</h2>
      </div>
      <div class="submit-grid">
        <label>
          Target Dana Donasi
          <div class="money-input">
            <b>Rp</b>
            <input name="target_amount" inputmode="numeric" required value="<?= htmlspecialchars($_POST['target_amount'] ?? '') ?>" placeholder="Contoh: 25.000.000">
          </div>
          <small>Target minimal pengajuan Rp 1.000.000.</small>
        </label>
        <label>
          Rencana Batas Waktu Penggalangan
          <input name="deadline" type="date" required value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
          <small>Pilih tanggal penggalangan dana berakhir.</small>
        </label>
      </div>
    </section>

    <!-- SEKSI 3: FOTO UTAMA KAMPANYE -->
    <section class="submit-section">
      <div class="section-title">
        <span class="orange">3</span>
        <h2>Dokumentasi Foto Utama</h2>
      </div>
      <label>
        Foto Utama Kampanye <small>Format JPG/PNG maks. 5MB</small>
        <div class="campaign-upload">
          <div class="upload-preview" id="campaignPreview">Foto Utama</div>
          <div>
            <strong id="campaignFileName">Belum ada foto dipilih</strong>
            <p>Admin memverifikasi pengajuan dari kejelasan foto lapangan asli. Pastikan foto beresolusi tajam tanpa watermark.</p>
            <label class="upload-btn" for="campaignImage">⇄ Pilih Berkas Foto</label>
            <input id="campaignImage" name="campaign_image" accept="image/jpeg,image/png" type="file" hidden>
          </div>
        </div>
      </label>
    </section>

    <!-- SEKSI 4: DATA KONTAK PENANGGUNG JAWAB -->
    <section class="submit-section">
      <div class="section-title">
        <span>4</span>
        <h2>Data Kontak Penanggung Jawab</h2>
      </div>
      <div class="submit-grid">
        <label>
          Nama Lengkap Penanggung Jawab
          <input name="pic_name" required value="<?= htmlspecialchars($_POST['pic_name'] ?? $userData['nama']) ?>">
        </label>
        <label>
          Nomor WhatsApp Aktif
          <input name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? $userData['no_hp']) ?>" placeholder="081234567890">
          <small>Admin akan menghubungi nomor ini jika diperlukan koordinasi verifikasi.</small>
        </label>
      </div>
    </section>

    <!-- PERNYATAAN INTEGRITAS -->
    <label class="agreement">
      <input type="checkbox" name="agreement" required>
      <span>Saya menyatakan seluruh data yang diisi adalah benar, bertanggung jawab penuh atas pelaksanaan program konservasi, dan bersedia dihubungi via WhatsApp oleh tim admin verifikator.</span>
    </label>

    <div class="submit-actions">
      <a href="kampanye-saya.php">Batal</a>
      <button type="submit">Kirim Pengajuan Galang Dana ▷</button>
    </div>
  </form>
</main>

<script src="assets/js/main.js"></script>
<script>
document.getElementById('campaignImage').addEventListener('change', function() {
  const f = this.files[0];
  if (!f) return;
  document.getElementById('campaignFileName').textContent = f.name;
  const r = new FileReader();
  r.onload = e => {
    document.getElementById('campaignPreview').style.backgroundImage = `url(${e.target.result})`;
  };
  r.readAsDataURL(f);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
