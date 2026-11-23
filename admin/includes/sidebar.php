<?php
$cur = basename($_SERVER['PHP_SELF']);
try {
    $count_pending_campaign = (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'pending'")->fetchColumn();
} catch (Throwable $e) {
    $count_pending_campaign = 0;
}
?>
<aside class="admin-sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo-icon" aria-hidden="true">
      <img src="../assets/img/nusantarahijau-logo-dark.svg" alt="" width="19" height="19">
    </div>
    <div class="sidebar-brand-copy">
      <h2>Nusantara<span>Hijau</span></h2>
    </div>
  </div>

  <?php require __DIR__ . '/reference-nav.php'; ?>

  <div class="sidebar-footer">
    <a class="admin-logout-link" href="../logout.php">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17l5-5-5-5M15 12H3M21 19V5"/></svg>
      Keluar Akun (Logout)
    </a>
  </div>
</aside>
