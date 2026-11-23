<?php
// ========================================================
// MODUL 8: PUSAT BANTUAN & KELUHAN (HELP TICKETS)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$admin_id = (int)($_SESSION['admin_id'] ?? 1);

// Proses balas tiket keluhan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reply_ticket'])) {
    $tid = (int)$_POST['ticket_id'];
    $balasan = trim($_POST['balasan'] ?? '');
    $status = $_POST['status'] ?? 'selesai';

    if ($tid > 0 && !empty($balasan)) {
        $stmtRep = $pdo->prepare("UPDATE help_tickets SET balasan = ?, status = ?, dibalas_oleh = ? WHERE id = ?");
        $stmtRep->execute([$balasan, $status, $admin_id, $tid]);
        log_activity($pdo, 'admin', $admin_id, 'Membalas Tiket Keluhan', 'help_tickets', $tid, "Membalas tiket #{$tid}");
        set_flash('success', "Balasan tiket keluhan #{$tid} berhasil disimpan!");
        header('Location: bantuan.php');
        exit;
    }
}

$tickets = $pdo->query("SELECT ht.*, adm.nama AS admin_nama 
                        FROM help_tickets ht 
                        LEFT JOIN admins adm ON adm.id = ht.dibalas_oleh 
                        ORDER BY ht.id DESC")->fetchAll();

$openTickets = count(array_filter($tickets, static fn($t) => $t['status'] === 'baru'));
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pusat Bantuan &amp; Keluhan - Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-shell">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <div class="admin-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>
    <main class="admin-content">
      <div class="admin-page-heading help-page-heading">
        <div>
          <h1>Pusat Bantuan &amp; Dukungan</h1>
          <p>Kelola tiket keluhan, balasan admin, dan status bantuan pengguna.</p>
        </div>
      </div>

      <?php if ($flash): ?><div class="flash-message flash-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

      <div class="admin-overview-stats help-stats">
        <div class="admin-overview-stat stat-pending">
          <div class="admin-stat-copy"><span class="stat-title">TIKET BARU / BELUM DIBALAS</span><div class="stat-value-line"><strong><?= number_format($openTickets) ?> <small>Tiket</small></strong></div><span class="stat-caption">Memerlukan respon admin</span></div>
          <span class="admin-overview-stat-icon">◷</span>
        </div>
        <div class="admin-overview-stat stat-total">
          <div class="admin-stat-copy"><span class="stat-title">TOTAL SELURUH TIKET</span><div class="stat-value-line"><strong><?= number_format(count($tickets)) ?> <small>Tiket</small></strong></div><span class="stat-caption">Layanan bantuan</span></div>
          <span class="admin-overview-stat-icon">▤</span>
        </div>
      </div>

      <section class="data-table-container">
        <div class="data-table-header">
          <h3>Daftar Tiket Keluhan &amp; Bantuan</h3>
        </div>
        <div class="admin-table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Pengirim</th>
                <th>Subjek &amp; Pesan Keluhan</th>
                <th>Status</th>
                <th>Balasan Admin</th>
                <th>Waktu</th>
                <th>Aksi Tanggapi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($t['nama']) ?></strong><br>
                    <small style="color: var(--text-muted);"><?= htmlspecialchars($t['email']) ?></small>
                  </td>
                  <td>
                    <strong style="color: #00628F;"><?= htmlspecialchars($t['subjek']) ?></strong><br>
                    <p style="margin: 4px 0; font-size: 12.5px;"><?= nl2br(htmlspecialchars($t['pesan'])) ?></p>
                  </td>
                  <td>
                    <span class="admin-status <?= $t['status'] === 'selesai' ? 'is-complete' : ($t['status'] === 'diproses' ? 'is-active' : 'is-pending') ?>">
                      <?= ucfirst(htmlspecialchars($t['status'])) ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($t['balasan'])): ?>
                      <p style="margin: 0; font-size: 12px; color: #236D00;">✓ <?= nl2br(htmlspecialchars($t['balasan'])) ?></p>
                      <small style="color: var(--text-muted);">Dibalas oleh: <?= htmlspecialchars($t['admin_nama'] ?? 'Admin') ?></small>
                    <?php else: ?>
                      <em style="color: #dc2626; font-size: 12px;">Belum dibalas</em>
                    <?php endif; ?>
                  </td>
                  <td><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
                  <td>
                    <details>
                      <summary class="admin-soft-action" style="cursor: pointer; display: inline-block;">Balas / Ubah</summary>
                      <form method="POST" action="bantuan.php" style="margin-top: 10px; min-width: 250px;">
                        <input type="hidden" name="action_reply_ticket" value="1">
                        <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                        <textarea name="balasan" class="form-control" rows="3" required placeholder="Tulis balasan untuk pengguna..."><?= htmlspecialchars($t['balasan'] ?? '') ?></textarea>
                        <select name="status" class="form-control" style="margin-top: 6px;">
                          <option value="diproses" <?= $t['status'] === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                          <option value="selesai" <?= $t['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="margin-top: 6px; padding: 6px 12px; font-size: 12px;">Kirim Balasan</button>
                      </form>
                    </details>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$tickets): ?><tr><td colspan="6" class="admin-empty-state">Belum ada tiket bantuan.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
