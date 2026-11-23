<?php
// ========================================================
// MODUL 8: PERPANJANGAN MASA PENGGALANGAN KAMPANYE (ADMIN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$admin_id = (int)($_SESSION['admin_id'] ?? 1);

// PROSES PERPANJANGAN WAKTU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_extend'])) {
    $camp_id = (int)$_POST['campaign_id'];
    $extra_days = (int)$_POST['extra_days'];

    if ($camp_id > 0 && $extra_days > 0) {
        $stmtC = $pdo->prepare("SELECT judul, tgl_selesai FROM campaigns WHERE id = ?");
        $stmtC->execute([$camp_id]);
        $camp = $stmtC->fetch();

        if ($camp) {
            $current_end = strtotime($camp['tgl_selesai']) > time() ? strtotime($camp['tgl_selesai']) : time();
            $new_end_date = date('Y-m-d', strtotime("+{$extra_days} days", $current_end));

            $pdo->prepare("UPDATE campaigns SET tgl_selesai = ? WHERE id = ?")->execute([$new_end_date, $camp_id]);
            log_activity($pdo, 'admin', $admin_id, 'Perpanjangan Waktu Kampanye', 'campaigns', $camp_id, "Memperpanjang {$camp['judul']} (+{$extra_days} hari) hingga {$new_end_date}");

            set_flash('success', "Masa kampanye {$camp['judul']} berhasil diperpanjang (+{$extra_days} hari) hingga " . date('d M Y', strtotime($new_end_date)) . "!");
            header("Location: kampanye.php");
            exit;
        }
    }
}

$selected_id = (int)($_GET['id'] ?? 0);
$campaigns = $pdo->query("SELECT id, judul, tgl_selesai, status FROM campaigns WHERE status = 'aktif' ORDER BY id ASC")->fetchAll();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Perpanjangan Masa Kampanye - Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <div class="admin-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main class="admin-content">
      <?php if ($flash): ?>
        <div class="flash-message flash-<?= $flash['type'] ?>">
          <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <div class="admin-page-heading">
        <div>
          <h1>Perpanjangan Masa Kampanye</h1>
          <p>Tambahkan durasi batas waktu penggalangan dana bagi kampanye konservasi yang memerlukan waktu tambahan.</p>
        </div>
        <a class="admin-soft-action" href="kampanye.php">← Kembali ke Kelola Kampanye</a>
      </div>

      <div class="card" style="max-width: 650px; padding: 28px;">
        <form method="POST" action="perpanjangan.php">
          <input type="hidden" name="action_extend" value="1">

          <div class="form-group">
            <label class="form-label required">Pilih Kampanye</label>
            <select name="campaign_id" class="form-control" required>
              <option value="">-- Pilih Kampanye Aktif --</option>
              <?php foreach ($campaigns as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($c['id'] == $selected_id) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['judul']) ?> (Batas Saat Ini: <?= date('d M Y', strtotime($c['tgl_selesai'])) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label required">Tambahan Waktu (Hari)</label>
            <select name="extra_days" class="form-control" required>
              <option value="15">+15 Hari</option>
              <option value="30" selected>+30 Hari (1 Bulan)</option>
              <option value="60">+60 Hari (2 Bulan)</option>
              <option value="90">+90 Hari (3 Bulan)</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary" style="padding: 11px 24px;">
            ✓ Terapkan Perpanjangan Masa
          </button>
        </form>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
