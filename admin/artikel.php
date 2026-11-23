<?php
// ========================================================
// MODUL 8: KELOLA ARTIKEL EDUKASI (ADMIN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$admin_id = (int)($_SESSION['admin_id'] ?? 1);

// Tambah artikel baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_article'])) {
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $status = $_POST['status'] ?? 'terbit';
    $kategori = ($_POST['kategori'] ?? 'laut') === 'darat' ? 'darat' : 'laut';

    if (!empty($judul) && !empty($isi)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul), '-'));
        $gambar = 'assets/img/campaign-mangrove.png';

        if (!empty($_FILES['gambar']['name']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $fileType = mime_content_type($_FILES['gambar']['tmp_name']);

            if (isset($allowedTypes[$fileType])) {
                $uploadDir = __DIR__ . '/../uploads/articles';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileName = 'artikel-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedTypes[$fileType];
                $targetPath = $uploadDir . '/' . $fileName;

                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetPath)) {
                    $gambar = 'uploads/articles/' . $fileName;
                }
            }
        }

        $stmtIns = $pdo->prepare("INSERT INTO articles (admin_id, judul, slug, isi, gambar, kategori, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmtIns->execute([$admin_id, $judul, $slug, $isi, $gambar, $kategori, $status]);

        log_activity($pdo, 'admin', $admin_id, 'Menulis Artikel Edukasi', 'articles', (int)$pdo->lastInsertId(), "Menerbitkan artikel {$judul}");
        set_flash('success', "Artikel '{$judul}' berhasil disimpan!");
        header('Location: artikel.php');
        exit;
    }
}

// Hapus artikel
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$del_id]);
    log_activity($pdo, 'admin', $admin_id, 'Menghapus Artikel', 'articles', $del_id, "Menghapus artikel ID #{$del_id}");
    set_flash('success', "Artikel berhasil dihapus.");
    header('Location: artikel.php');
    exit;
}

$articles = $pdo->query("SELECT a.*, adm.nama AS author_name 
                         FROM articles a 
                         LEFT JOIN admins adm ON adm.id = a.admin_id 
                         ORDER BY a.id DESC")->fetchAll();

$publishedCount = count(array_filter($articles, static fn($a) => $a['status'] === 'terbit'));
$draftCount = count(array_filter($articles, static fn($a) => $a['status'] === 'draft'));
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Artikel Edukasi - NusantaraHijau</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-shell">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <div class="admin-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>
    <main class="admin-content">
      <?php if ($flash): ?><div class="flash-message flash-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

      <div class="admin-page-heading article-page-heading">
        <div>
          <h1>Kelola Artikel Edukasi &amp; Wawasan SDGs</h1>
          <p>Publikasi wawasan konservasi laut, restorasi mangrove, dan literasi lingkungan kepada publik.</p>
        </div>
      </div>

      <div class="admin-overview-stats article-stats">
        <div class="admin-overview-stat stat-pending"><div class="admin-stat-copy"><span class="stat-title">TOTAL ARTIKEL TERBIT</span><div class="stat-value-line"><strong><?= number_format($publishedCount) ?> Artikel</strong></div><span class="stat-caption">Materi edukasi aktif</span></div><span class="admin-overview-stat-icon">▤</span></div>
      </div>

      <!-- FORMULIR TULIS ARTIKEL BARU -->
      <div class="card" style="padding: 24px; margin-bottom: 24px;">
        <h3 style="font-size: 15px; font-weight: 700; color: #00628F; margin-bottom: 14px;">＋ Tulis Artikel Baru</h3>
        <form method="POST" action="artikel.php" enctype="multipart/form-data">
          <input type="hidden" name="action_add_article" value="1">
          <div class="form-group">
            <label class="form-label required">Judul Artikel</label>
            <input type="text" name="judul" class="form-control" required placeholder="Contoh: Mengenal Pentingnya Menjaga Ekosistem Terumbu Karang">
          </div>
          <div class="form-group">
            <label class="form-label required">Isi Artikel</label>
            <textarea name="isi" class="form-control" rows="5" required placeholder="Tuliskan materi dan wawasan edukasi di sini..."></textarea>
          </div>
          <div class="form-group">
            <label class="form-label required">Foto Artikel</label>
            <input type="file" name="gambar" class="form-control" accept="image/jpeg,image/png,image/webp" required>
            <small style="display:block;margin-top:6px;color:#64748b;font-size:11.5px">Format JPG, PNG, atau WEBP. Gambar ini akan ditampilkan pada halaman artikel publik.</small>
          </div>
          <div class="form-group">
            <label class="form-label">Status Publikasi</label>
            <select name="status" class="form-control" style="max-width: 200px;">
              <option value="terbit">Terbit (Publik)</option>
              <option value="draft">Draft (Konsep)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label required">Kategori Artikel</label>
            <select name="kategori" class="form-control" style="max-width: 200px;" required>
              <option value="laut">Laut</option>
              <option value="darat">Darat</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">✓ Terbitkan Artikel</button>
        </form>
      </div>

      <section class="data-table-container">
        <div class="data-table-header">
          <h3>Daftar Artikel Tersedia</h3>
        </div>
        <div class="admin-table-scroll">
          <table class="data-table admin-reference-table">
            <thead>
              <tr>
                <th>Judul Artikel</th>
                <th>Penulis</th>
                <th>Status</th>
                <th>Tgl Dibuat</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($articles as $art): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($art['judul']) ?></strong><br>
                  <small style="color: var(--text-muted);"><?= htmlspecialchars(mb_strimwidth($art['isi'], 0, 100, '...')) ?></small>
                </td>
                <td><?= htmlspecialchars($art['author_name'] ?? 'Super Admin') ?></td>
                <td>
                  <span class="admin-status <?= $art['status'] === 'terbit' ? 'is-active' : 'is-pending' ?>">
                    <?= ucfirst(htmlspecialchars($art['status'])) ?>
                  </span>
                </td>
                <td><?= date('d M Y', strtotime($art['created_at'])) ?></td>
                <td>
                  <a href="artikel.php?delete=<?= (int)$art['id'] ?>" class="admin-soft-action" style="color: #dc2626;" onclick="return confirm('Hapus artikel ini?')">Hapus</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$articles): ?><tr><td colspan="5" class="admin-empty-state">Belum ada artikel.</td></tr><?php endif; ?>
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
