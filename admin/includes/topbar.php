<?php
// admin/includes/topbar.php
$adminDisplayName = $_SESSION['admin_name'] ?? 'Super Admin';
$adminInitials = strtoupper(substr($adminDisplayName, 0, 2));
?>
<header class="admin-topbar">
  <div class="topbar-search">
    <span class="search-icon-pos" style="display: flex; align-items: center; justify-content: center;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </span>
    <input type="text" id="tableSearchInput" placeholder="Cari kampanye, donasi, atau relawan...">
  </div>

  <div class="topbar-actions">
    <a href="index.php?sync=1" class="admin-sync-button">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.6 9a7 7 0 0 1 11.6-2L20 12M4 12l2.8 5a7 7 0 0 0 11.6-2"/></svg>
      Sinkron Data
    </a>

    <div class="user-pill">
      <div class="user-avatar" style="background: #00628F; color: white;"><?= htmlspecialchars($adminInitials) ?></div>
      <div>
        <strong style="display: block; font-size: 12.5px; color: #00628F;"><?= htmlspecialchars($adminDisplayName) ?></strong>
        <span style="font-size: 10px; color: #236D00; font-weight: 700;">● Admin Aktif</span>
      </div>
    </div>
  </div>
</header>
