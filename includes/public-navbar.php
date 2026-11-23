<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'contributor');
$account_name = 'Pengguna';
$account_email = '';
$account_id = (int) ($_SESSION['user_id'] ?? 0);
$account_photo = '';

if ($is_logged_in) {
    try {
        $account_stmt = $pdo->prepare('SELECT nama, email, foto FROM users WHERE id = ? LIMIT 1');
        $account_stmt->execute([$account_id]);
        $account_data = $account_stmt->fetch();
        if ($account_data) {
            $account_name = $account_data['nama'];
            $account_email = $account_data['email'];
            $account_photo = $account_data['foto'] ?? '';
        }
    } catch (Throwable $exception) {
        // Keep the navbar usable if profile data cannot be loaded.
    }
}

$account_words = preg_split('/\s+/', trim($account_name));
$account_initials = strtoupper(substr($account_words[0] ?? 'K', 0, 1) . substr(end($account_words) ?: '', 0, 1));
$navbar_class = !empty($navbar_demo) ? ' navbar-demo' : (!empty($navbar_print) ? ' navbar-print' : '');
?>
<header class="navbar<?= $navbar_class ?>">
  <div class="nav-container">
    <div class="brand-group">
      <a href="index.php" class="brand-link" aria-label="NusantaraHijau, beranda">
        <span class="brand-logo-icon" aria-hidden="true">
          <img src="assets/img/nusantarahijau-logo-dark.svg" alt="" width="23" height="23">
        </span>
        <span class="brand-text"><span class="brand-name">Nusantara</span><span class="brand-name-accent">Hijau</span></span>
      </a>
    </div>

    <ul class="nav-menu">
      <li><a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Beranda</a></li>
      <li><a href="daftar-kampanye.php" class="<?= in_array($current_page, ['daftar-kampanye.php', 'detail-kampanye.php', 'donasi.php', 'pembayaran.php', 'berhasil.php', 'invoice.php', 'kampanye-saya.php', 'ajukan-galang-dana.php'], true) ? 'active' : '' ?>">Kampanye</a></li>
      <li><a href="aksi-relawan.php" class="<?= $current_page === 'aksi-relawan.php' ? 'active' : '' ?>">Aksi Relawan</a></li>
      <li><a href="artikel-edukasi.php" class="<?= $current_page === 'artikel-edukasi.php' ? 'active' : '' ?>">Artikel Edukasi</a></li>
      <li><a href="faq-bantuan.php" class="<?= $current_page === 'faq-bantuan.php' ? 'active' : '' ?>">FAQ &amp; Pusat Bantuan</a></li>
    </ul>

    <div class="nav-actions">
      <?php if ($is_logged_in): ?>
        <div class="account-menu account-authenticated" id="accountMenu">
          <button type="button" class="account-trigger" id="accountTrigger" aria-expanded="false" aria-controls="accountPopover">
            <span class="account-trigger-user">
              <?php if (!empty($account_photo) && file_exists(__DIR__ . '/../' . $account_photo)): ?>
                <img src="<?= htmlspecialchars($account_photo) ?>" alt="" class="account-avatar account-avatar-small">
              <?php else: ?>
                <span class="account-avatar account-avatar-small"><?= htmlspecialchars($account_initials) ?></span>
              <?php endif; ?>
              <span class="account-name"><?= htmlspecialchars($account_name) ?></span>
              <svg class="account-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </button>
          <div class="account-popover" id="accountPopover" hidden>
            <div class="account-popover-profile">
              <?php if (!empty($account_photo) && file_exists(__DIR__ . '/../' . $account_photo)): ?>
                <img src="<?= htmlspecialchars($account_photo) ?>" alt="" class="account-avatar account-avatar-large">
              <?php else: ?>
                <span class="account-avatar account-avatar-large"><?= htmlspecialchars($account_initials) ?></span>
              <?php endif; ?>
              <span class="account-online-dot"></span>
              <div class="account-profile-copy">
                <strong><?= htmlspecialchars($account_name) ?></strong>
                <span><?= htmlspecialchars($account_email) ?></span>
              </div>
            </div>
            <a class="account-profile-action" href="profil.php">Lihat dan edit profil</a>
            <div class="account-menu-links">
              <a href="kampanye-saya.php">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11v2a2 2 0 0 0 2 2h2l9 4V5l-9 4H5a2 2 0 0 0-2 2Z"/><path d="M7 15v4a2 2 0 0 0 2 2h1l2-4"/><path d="M19 8.5a4 4 0 0 1 0 7"/><path d="M21.5 6a8 8 0 0 1 0 12"/></svg>
                Kampanye Saya <span aria-hidden="true">›</span>
              </a>
              <a href="pengaturan-akun.php">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.42 1.42-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V19.6h-2v-.08a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-1.42-1.42.06-.06A1.7 1.7 0 0 0 9.32 15a1.7 1.7 0 0 0-1.56-1.03H7.6v-2h.16A1.7 1.7 0 0 0 9.32 10a1.7 1.7 0 0 0-.34-1.88l-.06-.06 1.42-1.42.06.06a1.7 1.7 0 0 0 1.88.34A1.7 1.7 0 0 0 13.31 5.48V5.4h2v.08a1.7 1.7 0 0 0 1.03 1.56 1.7 1.7 0 0 0 1.88-.34l.06-.06 1.42 1.42-.06.06A1.7 1.7 0 0 0 19.4 10a1.7 1.7 0 0 0 1.56 1.03h.04v2h-.04A1.7 1.7 0 0 0 19.4 15Z"/></svg>
                Pengaturan Akun <span aria-hidden="true">›</span>
              </a>
            </div>
            <div class="account-popover-footer">
              <a href="logout.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                Keluar Akun
              </a>
            </div>
          </div>
        </div>
      <?php elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a class="nav-login-btn" href="admin/dashboard.php">Dashboard Admin</a>
      <?php else: ?>
        <a class="nav-login-btn" href="login.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
          <span>Masuk / Daftar</span>
        </a>
      <?php endif; ?>
      <?php if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
        <a class="nav-fundraising-btn" href="ajukan-galang-dana.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
          <span>Galang Dana</span>
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>
<script src="assets/js/public-navbar.js" defer></script>
