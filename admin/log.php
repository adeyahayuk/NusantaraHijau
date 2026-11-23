<?php
// ========================================================
// MODUL 8: LOG AKTIVITAS & AUDIT TRAIL SISTEM (ADMIN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$role_filter = $_GET['role'] ?? '';
$sql = "SELECT al.*, 
               CASE 
                 WHEN al.actor_type = 'admin' THEN COALESCE(adm.nama, 'Admin')
                 ELSE COALESCE(u.nama, 'Pengguna')
               END AS actor_name
        FROM activity_logs al
        LEFT JOIN admins adm ON al.actor_type = 'admin' AND adm.id = al.actor_id
        LEFT JOIN users u ON al.actor_type = 'user' AND u.id = al.actor_id
        WHERE 1=1 ";
$params = [];

if (!empty($role_filter)) {
    $sql .= "AND al.actor_type = ? ";
    $params[] = $role_filter;
}

$sql .= "ORDER BY al.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Log Aktivitas Sistem - Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <div class="admin-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main class="admin-content">
      <div class="admin-page-heading log-page-heading">
        <div>
          <h1>Log Aktivitas Sistem</h1>
          <p>Catatan riwayat audit kampanye, transaksi, verifikasi, dan perubahan data administrasi secara real-time.</p>
        </div>
      </div>

      <div class="data-table-container log-table-card">
        <div class="data-table-header log-table-tools">
          <div class="log-filter-tabs">
            <a href="log.php" class="active">Semua</a>
            <a href="log.php?role=admin" class="<?= $role_filter === 'admin' ? 'active' : '' ?>">Verifikasi Kampanye</a>
            <a href="log.php?role=user" class="<?= $role_filter === 'user' ? 'active' : '' ?>">Aktivitas Pengguna</a>
          </div>
          <label class="admin-search-inline log-search"><span>⌕</span><input id="logSearch" type="search" placeholder="Cari penguna, IP, aksi, atau tanggal..."></label>
        </div>

        <div class="admin-table-scroll">
          <table class="data-table log-reference-table">
            <thead>
              <tr>
                <th>Waktu &amp; Tanggal</th>
                <th>Pengguna / Aktor</th>
                <th>Jenis Aksi</th>
                <th>Deskripsi Detail</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($logs) > 0): ?>
                <?php foreach ($logs as $log):
                  $actionClass = 'log-action-blue';
                  $aksiLower = strtolower($log['aksi']);
                  if (str_contains($aksiLower, 'donasi') || str_contains($aksiLower, 'masuk')) $actionClass = 'log-action-green';
                  if (str_contains($aksiLower, 'penyaluran') || str_contains($aksiLower, 'update')) $actionClass = 'log-action-green';
                  if (str_contains($aksiLower, 'verifikasi') || str_contains($aksiLower, 'menyetujui')) $actionClass = 'log-action-blue';
                  if (str_contains($aksiLower, 'login')) $actionClass = 'log-action-gray';
                  if (str_contains($aksiLower, 'artikel')) $actionClass = 'log-action-orange';
                ?>
                  <tr class="log-row">
                    <td><div class="log-time"><strong><?= date('Hari, H:i WIB', strtotime($log['created_at'])) ?></strong><small><?= date('d Okt Y', strtotime($log['created_at'])) ?></small></div></td>
                    <td>
                      <div class="log-actor"><span class="log-avatar <?= $log['actor_type'] === 'admin' ? 'admin' : 'user' ?>"><?= strtoupper(mb_substr($log['actor_name'], 0, 2)) ?></span><div><strong><?= htmlspecialchars($log['actor_name']) ?></strong><small><?= htmlspecialchars($log['actor_type'] === 'admin' ? 'admin@nusantarahijau.org' : 'pengguna@nusantarahijau.org') ?></small></div></div>
                    </td>
                    <td><span class="log-action <?= $actionClass ?>"><?= htmlspecialchars($log['aksi']) ?></span></td>
                    <td><div class="log-detail"><strong><?= htmlspecialchars($log['aksi']) ?></strong><small><?= htmlspecialchars($log['deskripsi'] ?? '-') ?></small><code><?= htmlspecialchars($log['entitas'] ?? '-') ?> #<?= (int)($log['entitas_id'] ?? 0) ?> &nbsp; | &nbsp; IP: <?= htmlspecialchars($log['ip_address']) ?></code></div></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="4" class="admin-empty-state">Belum ada catatan log aktivitas.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
document.getElementById('logSearch')?.addEventListener('input', function () {
  const query = this.value.toLowerCase();
  document.querySelectorAll('.log-row').forEach(row => {
    row.hidden = !row.innerText.toLowerCase().includes(query);
  });
});
</script>
</body>
</html>
