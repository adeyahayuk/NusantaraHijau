<?php
// ========================================================
// MODUL 8: PUSAT FAQ (ADMIN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$admin_id = (int)($_SESSION['admin_id'] ?? 1);

// Tambah / Hapus FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_faq'])) {
    if ($_POST['action_faq'] === 'add') {
        $q = trim($_POST['pertanyaan'] ?? '');
        $a = trim($_POST['jawaban'] ?? '');
        if ($q !== '' && $a !== '') {
            $pdo->prepare("INSERT INTO faqs (admin_id, pertanyaan, jawaban, urutan, is_active, created_at) VALUES (?, ?, ?, 1, 1, NOW())")
                ->execute([$admin_id, $q, $a]);
            log_activity($pdo, 'admin', $admin_id, 'Menambah FAQ', 'faqs', (int)$pdo->lastInsertId(), "Menambahkan pertanyaan FAQ baru");
            set_flash('success', 'Pertanyaan FAQ berhasil ditambahkan.');
        }
    } elseif ($_POST['action_faq'] === 'delete') {
        $fid = (int)($_POST['faq_id'] ?? 0);
        $pdo->prepare("DELETE FROM faqs WHERE id = ?")->execute([$fid]);
        log_activity($pdo, 'admin', $admin_id, 'Menghapus FAQ', 'faqs', $fid, "Menghapus FAQ ID #{$fid}");
        set_flash('success', 'FAQ berhasil dihapus.');
    }
    header('Location: faq.php');
    exit;
}

$faqs = $pdo->query("SELECT * FROM faqs ORDER BY urutan ASC, id ASC")->fetchAll();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pusat FAQ - NusantaraHijau</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-shell">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <div class="admin-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>
    <main class="admin-content">
      <div class="admin-page-heading faq-page-heading">
        <div>
          <h1>Pusat FAQ &amp; Bantuan Admin</h1>
          <p>Kelola pertanyaan umum dan jawaban untuk pusat bantuan platform NusantaraHijau.</p>
        </div>
      </div>

      <?php if ($flash): ?><div class="flash-message flash-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

      <!-- FORM TAMBAH FAQ -->
      <div class="card" style="padding: 24px; margin-bottom: 24px;">
        <h3 style="font-size: 15px; font-weight: 700; color: #00628F; margin-bottom: 14px;">＋ Tambah Pertanyaan FAQ</h3>
        <form method="POST" action="faq.php">
          <input type="hidden" name="action_faq" value="add">
          <div class="form-group">
            <label class="form-label required">Pertanyaan</label>
            <input type="text" name="pertanyaan" class="form-control" required placeholder="Contoh: Bagaimana cara memverifikasi bukti donasi?">
          </div>
          <div class="form-group">
            <label class="form-label required">Jawaban Lengkap</label>
            <textarea name="jawaban" class="form-control" rows="3" required placeholder="Tuliskan jawaban yang informatif dan jelas..."></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">✓ Simpan FAQ</button>
        </form>
      </div>

      <section class="data-table-container">
        <div class="data-table-header">
          <h3>Daftar FAQ Aktif (<?= count($faqs) ?>)</h3>
        </div>
        <div class="admin-table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th width="35%">Pertanyaan</th>
                <th>Jawaban</th>
                <th width="12%">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($faqs as $f): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($f['pertanyaan']) ?></strong></td>
                  <td><?= nl2br(htmlspecialchars($f['jawaban'])) ?></td>
                  <td>
                    <form method="POST" action="faq.php" onsubmit="return confirm('Hapus FAQ ini?');">
                      <input type="hidden" name="action_faq" value="delete">
                      <input type="hidden" name="faq_id" value="<?= (int)$f['id'] ?>">
                      <button type="submit" class="admin-soft-action" style="color: #dc2626; background: none; border: none; cursor: pointer;">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$faqs): ?><tr><td colspan="3" class="admin-empty-state">Belum ada FAQ.</td></tr><?php endif; ?>
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
