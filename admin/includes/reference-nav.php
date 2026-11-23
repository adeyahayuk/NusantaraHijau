<?php
$activeTab = $_GET['tab'] ?? '';
$adminLinks = [
    ['index.php', 'index.php', 'Ringkasan', '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
    ['verifikasi.php', 'verifikasi.php', 'Verifikasi Pengajuan', '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14 9 11"/>', $count_pending_campaign ?? 0],
    ['kampanye.php', 'kampanye.php', 'Kelola Kampanye', '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>'],
    ['penyaluran.php', 'penyaluran.php', 'Penyaluran Dana', '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>'],
    ['donasi.php', 'donasi.php', 'Data Donasi', '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
    ['relawan.php', 'relawan.php', 'Data Relawan', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/>'],
    ['artikel.php', 'artikel.php', 'Kelola Artikel Edukasi', '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>'],
    ['log.php', 'log.php', 'Log Aktivitas', '<path d="M3 12h4l3-9 4 18 3-9h4"/>'],
    ['faq.php', 'faq.php', 'FAQ', '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/>'],
    ['bantuan.php', 'bantuan.php', 'Pusat Bantuan', '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><path d="m4.9 4.9 4.3 4.3m5.6 5.6 4.3 4.3m0-14.2-4.3 4.3m-5.6 5.6-4.3 4.3"/>'],
];
?>
<nav class="reference-admin-nav" aria-label="Navigasi admin">
  <?php foreach ($adminLinks as $link):
      [$href, $file, $label, $icon, $badge, $tab] = array_pad($link, 6, null);
      $isActive = $cur === $file && (!$tab || $activeTab === $tab);
  ?>
    <a href="<?= htmlspecialchars($href) ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
      <span class="sidebar-link-left">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $icon ?></svg>
        <span><?= htmlspecialchars($label) ?></span>
      </span>
      <?php if (!empty($badge)): ?><span class="badge-count"><?= (int)$badge ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>
