<?php
// ========================================================
// PENGATURAN KEAMANAN AKUN KONTRIBUTOR
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'contributor') {
    header('Location: login.php');
    exit;
}

$accountId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $accountStmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $accountStmt->execute([$accountId]);
    $account = $accountStmt->fetch();

    if (!$account) {
        $error = 'Akun tidak ditemukan.';
    } elseif ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Lengkapi semua kolom kata sandi.';
    } elseif (!password_verify($oldPassword, $account['password']) && !hash_equals($account['password'], $oldPassword) && $account['password'] !== $oldPassword) {
        $error = 'Password lama tidak sesuai.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password baru belum sama.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $accountId]);
        log_activity($pdo, 'user', $accountId, 'Ganti Password', 'users', $accountId, 'Memperbarui kata sandi akun');
        $message = 'Password berhasil diperbarui.';
    }
}

$page_title = 'Pengaturan Akun';
$body_class = 'account-settings-page';
require_once __DIR__ . '/includes/header.php';
?>

<main class="account-settings-main">
  <header class="account-settings-heading">
    <h1>Pengaturan Akun</h1>
    <p>Kelola keamanan kata sandi akun NusantaraHijau Anda.</p>
  </header>

  <?php if ($message): ?><div class="settings-alert success" role="status"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="settings-alert error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <section class="password-card" aria-labelledby="password-card-title">
    <div class="password-card-title">
      <div class="password-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.42 1.42-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V19.6h-2v-.08a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-1.42-1.42.06-.06A1.7 1.7 0 0 0 10.76 15a1.7 1.7 0 0 0-1.56-1.03H9.12v-2h.08A1.7 1.7 0 0 0 10.76 10a1.7 1.7 0 0 0-.34-1.88l-.06-.06 1.42-1.42.06.06a1.7 1.7 0 0 0 1.88.34A1.7 1.7 0 0 0 14.75 5.48V5.4h2v.08a1.7 1.7 0 0 0 1.03 1.56 1.7 1.7 0 0 0 1.88-.34l.06-.06 1.42 1.42-.06.06A1.7 1.7 0 0 0 20.74 10a1.7 1.7 0 0 0 1.56 1.03h.08v2h-.08A1.7 1.7 0 0 0 19.4 15Z"/></svg></div>
      <div><h2 id="password-card-title">Ubah Password</h2><p>Pastikan akun Anda terlindungi dengan kombinasi kata sandi yang kuat.</p></div>
    </div>

    <form method="post" class="password-form">
      <div class="password-field">
        <label for="oldPassword">Password Lama</label>
        <div class="password-input-wrap">
          <input id="oldPassword" name="old_password" type="password" placeholder="Masukkan kata sandi saat ini" required>
        </div>
      </div>

      <div class="password-field">
        <label for="newPassword">Password Baru</label>
        <div class="password-input-wrap">
          <input id="newPassword" name="new_password" type="password" placeholder="Minimal 6 karakter" required>
        </div>
      </div>

      <div class="password-field">
        <label for="confirmPassword">Konfirmasi Password Baru</label>
        <div class="password-input-wrap">
          <input id="confirmPassword" name="confirm_password" type="password" placeholder="Ulangi kata sandi baru" required>
        </div>
      </div>

      <div class="password-submit-row">
        <a href="index.php" class="password-cancel-btn">Batal</a>
        <button type="submit" class="password-save-btn">Simpan Password Baru</button>
      </div>
    </form>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
