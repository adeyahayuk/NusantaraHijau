<?php
// ========================================================
// MODUL PROFIL KONTRIBUTOR / PENGGUNA
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Pastikan sudah login sebagai kontributor
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'contributor') {
    header('Location: login.php');
    exit;
}

$accountId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

// Ambil profil dari tabel users (11 Tabel Final)
$stmt = $pdo->prepare('SELECT id, nama, email, no_hp, organisasi, foto, status FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$accountId]);
$profile = $stmt->fetch();

if (!$profile) {
    die('Akun pengguna tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['phone'] ?? '');
    $organisasi = trim($_POST['organization'] ?? '');

    if ($nama === '' || $email === '') {
        $error = 'Nama lengkap dan email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan alamat email yang valid.';
    }

    // Hapus foto jika diminta
    if ($error === '' && isset($_POST['remove_photo'])) {
        if (!empty($profile['foto']) && file_exists(__DIR__ . '/' . $profile['foto'])) {
          @unlink(__DIR__ . '/' . $profile['foto']);
        }
        $profile['foto'] = null;
        $pdo->prepare('UPDATE users SET foto = NULL WHERE id = ?')->execute([$accountId]);
        $message = 'Foto profil berhasil dihapus.';
    }

    // Upload foto profil baru jika ada
    if ($error === '' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['profile_photo'];
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $mime = function_exists('mime_content_type') && is_uploaded_file($upload['tmp_name']) ? mime_content_type($upload['tmp_name']) : '';
        if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 2 * 1024 * 1024 || !isset($allowedTypes[$mime])) {
            $error = 'Foto harus berformat JPG atau PNG dengan ukuran maksimal 2MB.';
        } else {
            $uploadDir = __DIR__ . '/assets/uploads';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                $error = 'Folder foto profil tidak dapat dibuat.';
            } else {
                $filename = 'profile-' . $accountId . '-' . time() . '.' . $allowedTypes[$mime];
                if (move_uploaded_file($upload['tmp_name'], $uploadDir . '/' . $filename)) {
                    $profile['foto'] = 'assets/uploads/' . $filename;
                } else {
                    $error = 'Foto tidak dapat diunggah. Silakan coba lagi.';
                }
            }
        }
    }

    if ($error === '') {
        try {
            // Periksa email tidak bentrok dengan akun lain
            $checkEmailAdmin = $pdo->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
            $checkEmailAdmin->execute([$email]);
            $adminExists = $checkEmailAdmin->fetch();

            $checkEmailUser = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $checkEmailUser->execute([$email, $accountId]);
            $userExists = $checkEmailUser->fetch();

            if ($adminExists || $userExists) {
                $error = 'Alamat email tersebut telah digunakan oleh akun lain.';
            } else {
                $stmtUpdate = $pdo->prepare('UPDATE users SET nama = ?, email = ?, no_hp = ?, organisasi = ?, foto = ? WHERE id = ?');
                $stmtUpdate->execute([$nama, $email, $no_hp, $organisasi, $profile['foto'], $accountId]);

                $_SESSION['user_name'] = $nama;
                $_SESSION['user_email'] = $email;
                $profile['nama'] = $nama;
                $profile['email'] = $email;
                $profile['no_hp'] = $no_hp;
                $profile['organisasi'] = $organisasi;

                log_activity($pdo, 'user', $accountId, 'Update Profil', 'users', $accountId, 'Memperbarui data profil');
                $message = 'Perubahan profil berhasil disimpan.';
            }
        } catch (Throwable $exception) {
            $error = 'Gagal menyimpan perubahan: ' . $exception->getMessage();
        }
    }
}

$page_title = 'Lihat & Edit Profil';
$body_class = 'profile-page';
require_once __DIR__ . '/includes/header.php';

$nama_awal = $profile['nama'] ?: 'Pengguna';
$words = preg_split('/\s+/', trim($nama_awal));
$initials = strtoupper(substr($words[0] ?? 'K', 0, 1) . substr(end($words) ?: '', 0, 1));
?>

<style>
.profile-page{--bg-page:#f5f8ff;background:#f5f8ff;color:#102b45}
.profile-main{width:min(100% - 48px,1120px);margin:0 auto;padding:30px 0 48px}
.profile-page .breadcrumb-bar{width:100%;max-width:none;padding:0;background:transparent;border:0}
.profile-crumb{display:flex;align-items:center;gap:8px;color:#68778a;font-size:13px}
.profile-crumb svg{width:18px;height:18px;fill:none;stroke:#0875b1;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.profile-crumb b{color:#9aa8ba;font-size:18px}
.profile-crumb strong{color:#223d58}
.profile-heading{margin:20px 0 22px}
.profile-heading h1{color:#0f2b46;font-size:32px;line-height:1.12;letter-spacing:-1.35px}
.profile-heading p{margin-top:7px;color:#526171;font-size:14px}
.profile-alert{margin:0 0 16px;padding:12px 15px;border-radius:9px;font-size:13px;font-weight:600}
.profile-alert.success{background:#ecfbe9;color:#237b17;border:1px solid #ccebc6}
.profile-alert.error{background:#fff0f0;color:#bf2020;border:1px solid #f4cccc}
.profile-card{padding:30px;border:1px solid #edf1f7;border-radius:12px;background:#fff;box-shadow:0 10px 28px rgba(47,83,126,.035)}
.profile-card-title{display:flex;align-items:center;gap:12px;padding-bottom:22px;border-bottom:1px solid #edf1f7}
.profile-title-icon{width:42px;height:42px;display:grid;flex:0 0 42px;place-items:center;border-radius:9px;background:#dcecff;color:#0875b1}
.profile-title-icon svg{width:23px;height:23px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.profile-card-title h2{font-size:18px;line-height:1.2;color:#112f4b}
.profile-card-title p{margin-top:4px;color:#596575;font-size:12px}
.profile-status{display:inline-flex;align-items:center;gap:6px;margin-left:auto;padding:7px 10px;border-radius:999px;background:#ecfbe9;color:#237b17;font-size:12px;font-weight:700}
.profile-status svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2.2}
.profile-form{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:20px;margin-top:24px}
.profile-photo-box{grid-column:1/-1;display:flex;align-items:center;gap:18px;padding:18px;border:1px solid #edf1f7;border-radius:10px;background:#fafdff}
.profile-photo{width:92px;height:92px;display:grid;flex:0 0 92px;place-items:center;overflow:hidden;border:3px solid #fff;border-radius:50%;background:#dcecff;color:#0875b1;font-size:28px;font-weight:800;box-shadow:0 4px 14px rgba(47,83,126,.12)}
.profile-photo img{width:100%;height:100%;object-fit:cover}
.profile-photo-actions{min-width:0}
.profile-upload-btn{display:inline-flex;align-items:center;gap:7px;min-height:38px;padding:0 13px;border:1px solid #cbdff1;border-radius:8px;background:#fff;color:#0875b1;font-size:12px;font-weight:700;cursor:pointer}
.profile-upload-btn svg,.profile-delete-btn svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.profile-delete-btn{display:inline-flex;align-items:center;gap:7px;min-height:38px;margin-left:8px;padding:0 13px;border:1px solid #f1cccc;border-radius:8px;background:#fff;color:#c33;font-size:12px;font-weight:700;cursor:pointer}
.profile-photo-actions p{margin-top:8px;color:#7b8796;font-size:11px}
.profile-field{min-width:0}
.profile-label-line{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:7px}
.profile-label-line label{color:#142f4b;font-size:12px;font-weight:700}
.profile-label-line .verified,.profile-label-line .whatsapp{display:inline-flex;align-items:center;gap:5px;color:#2b7a4b;font-size:11px;font-weight:700}
.profile-label-line .whatsapp{color:#168b67}
.profile-label-line svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2.2}
.profile-input-wrap{min-height:47px;display:flex;align-items:center;gap:10px;padding:0 13px;border:1px solid transparent;border-radius:9px;background:#eaf2ff;color:#465564}
.profile-input-wrap>svg{width:18px;height:18px;flex:0 0 18px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.profile-input-wrap input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:#183552;font-size:14px}
.profile-input-wrap input::placeholder{color:#9aa7b8}
.profile-input-wrap:focus-within{border-color:#87bfe1;box-shadow:0 0 0 3px rgba(135,191,225,.18)}
.profile-field small{display:block;margin-top:7px;color:#69788a;font-size:11px}
.profile-submit-row{grid-column:1/-1;display:flex;justify-content:flex-end;padding-top:4px}
.profile-save-btn{display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:0 22px;border:0;border-radius:8px;background:#0875b1;color:#fff;font-weight:700;cursor:pointer}
.profile-save-btn:hover{background:#075f91}
.profile-save-btn svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
@media(max-width:760px){.profile-main{width:min(100% - 32px,1120px);padding-top:24px}.profile-heading h1{font-size:28px}.profile-card{padding:20px 16px}.profile-card-title{align-items:flex-start}.profile-card-title p{max-width:280px}.profile-status{display:none}.profile-form{grid-template-columns:1fr}.profile-photo-box{align-items:flex-start}.profile-field,.profile-submit-row{grid-column:1}.profile-submit-row{justify-content:stretch}.profile-save-btn{width:100%;justify-content:center}.profile-delete-btn{margin:8px 0 0}.profile-photo-actions p{max-width:220px}}
@media(max-width:460px){.profile-photo-box{flex-direction:column}.profile-card-title p{display:none}}
</style>

<main class="profile-main">
  <!-- BREADCRUMB -->
  <div class="breadcrumb-bar">
    <div class="profile-crumb">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <a href="index.php" style="color: inherit; text-decoration: none;">Beranda</a>
      <b>›</b>
      <strong>Profil Kontributor</strong>
    </div>
  </div>

  <!-- HEADING -->
  <header class="profile-heading">
    <h1>Lihat &amp; Edit Profil</h1>
    <p>Kelola informasi profil pribadi dan kontak akun NusantaraHijau Anda.</p>
  </header>

  <?php if ($message): ?>
    <div class="profile-alert success" role="status"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="profile-alert error" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- KARTU PROFIL UTAMA -->
  <section class="profile-card">
    <div class="profile-card-title">
      <div class="profile-title-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div>
        <h2>Profil Akun Kontributor</h2>
        <p>Data identitas Anda digunakan untuk verifikasi kampanye konservasi alam.</p>
      </div>
      <span class="profile-status">
        <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        Akun Aktif
      </span>
    </div>

    <form method="post" enctype="multipart/form-data" class="profile-form">
      <!-- KOTAK FOTO PROFIL -->
      <div class="profile-photo-box">
        <div class="profile-photo" id="profilePhotoDisplay">
          <?php if (!empty($profile['foto']) && file_exists(__DIR__ . '/' . $profile['foto'])): ?>
            <img src="<?= htmlspecialchars($profile['foto']) ?>" alt="Foto profil" id="avatarImgPreview">
          <?php else: ?>
            <span id="avatarInitialsText"><?= htmlspecialchars($initials) ?></span>
          <?php endif; ?>
        </div>

        <div class="profile-photo-actions">
          <label class="profile-upload-btn" for="profilePhotoInput">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Unggah Foto Baru
          </label>
          <input id="profilePhotoInput" name="profile_photo" accept="image/jpeg,image/png" type="file" hidden>

          <?php if (!empty($profile['foto'])): ?>
            <button type="submit" name="remove_photo" value="1" class="profile-delete-btn" onclick="return confirm('Apakah Anda yakin ingin menghapus foto profil ini?')">
              <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              Hapus Foto
            </button>
          <?php endif; ?>

          <p>Format foto JPG atau PNG, ukuran maksimal 2MB. Disarankan foto beresolusi jelas dengan rasio 1:1.</p>
        </div>
      </div>

      <!-- KOLOM 1: NAMA LENGKAP -->
      <div class="profile-field">
        <div class="profile-label-line">
          <label for="profileName">Nama Lengkap Penanggung Jawab</label>
          <span class="verified"><svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg> Terdaftar di Sistem</span>
        </div>
        <div class="profile-input-wrap">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <input id="profileName" name="name" type="text" required value="<?= htmlspecialchars($profile['nama']) ?>">
        </div>
      </div>

      <!-- KOLOM 2: ALAMAT EMAIL -->
      <div class="profile-field">
        <div class="profile-label-line">
          <label for="profileEmail">Alamat Email Resmi</label>
          <span class="verified"><svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg> Terverifikasi</span>
        </div>
        <div class="profile-input-wrap">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <input id="profileEmail" name="email" type="email" required value="<?= htmlspecialchars($profile['email']) ?>">
        </div>
      </div>

      <!-- KOLOM 3: NOMOR WHATSAPP -->
      <div class="profile-field">
        <div class="profile-label-line">
          <label for="profilePhone">Nomor WhatsApp / Kontak Aktif</label>
          <span class="whatsapp">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            Terhubung WhatsApp
          </span>
        </div>
        <div class="profile-input-wrap">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <input id="profilePhone" name="phone" type="text" placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($profile['no_hp'] ?? '') ?>">
        </div>
        <small>Nomor ini digunakan admin untuk koordinasi pencairan dana escrow dan verifikasi program konservasi.</small>
      </div>

      <!-- KOLOM 4: NAMA ORGANISASI / LEMBAGA -->
      <div class="profile-field">
        <div class="profile-label-line">
          <label for="profileOrg">Nama Organisasi / Lembaga / Komunitas</label>
          <span style="color: #536174; font-size: 12px;">Inisiator Konservasi</span>
        </div>
        <div class="profile-input-wrap">
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          <input id="profileOrg" name="organization" type="text" placeholder="Contoh: Yayasan Konservasi Hayati Indonesia" value="<?= htmlspecialchars($profile['organisasi'] ?? '') ?>">
        </div>
      </div>

      <!-- TOMBOL SIMPAN -->
      <div class="profile-submit-row">
        <button type="submit" class="profile-save-btn">
          <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
          Simpan Perubahan Profil
        </button>
      </div>
    </form>
  </section>
</main>

<script>
// Preview foto profil langsung saat memilih file
document.getElementById('profilePhotoInput').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    const box = document.getElementById('profilePhotoDisplay');
    box.innerHTML = '<img src="' + e.target.result + '" alt="Foto Profil" style="width:100%; height:100%; object-fit:cover;">';
  };
  reader.readAsDataURL(file);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
