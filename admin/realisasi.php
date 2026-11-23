<?php
// ========================================================
// MODUL 8: REALISASI PENYALURAN DANA TERMIN ESCROW (ADMIN)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$admin_id = (int)($_SESSION['admin_id'] ?? 1);

// Cek apakah aksi cepat konfirmasi penyaluran terjadwal
if (isset($_GET['id'])) {
    $disb_id = (int)$_GET['id'];
    $stmtCek = $pdo->prepare("SELECT d.*, c.judul, (c.dana_terkumpul - c.dana_tersalurkan) AS sisa_saldo 
                              FROM disbursements d 
                              JOIN campaigns c ON d.campaign_id = c.id 
                              WHERE d.id = ? AND d.status = 'dijadwalkan'");
    $stmtCek->execute([$disb_id]);
    $targetDisb = $stmtCek->fetch();

    if ($targetDisb) {
        $pdo->prepare("UPDATE disbursements SET status = 'tersalur', tgl_realisasi = CURRENT_DATE(), admin_id = ? WHERE id = ?")
            ->execute([$admin_id, $disb_id]);
        $pdo->prepare("UPDATE campaigns SET dana_tersalurkan = dana_tersalurkan + ? WHERE id = ?")
            ->execute([$targetDisb['jumlah'], $targetDisb['campaign_id']]);

        log_activity($pdo, 'admin', $admin_id, 'Realisasi Penyaluran Selesai', 'disbursements', $disb_id, "Penyaluran dana {$targetDisb['judul']} sebesar " . format_rupiah($targetDisb['jumlah']));
        set_flash('success', "Penyaluran sebesar " . format_rupiah($targetDisb['jumlah']) . " berhasil direalisasikan!");
        header("Location: penyaluran.php");
        exit;
    }
}

// Ambil daftar kampanye aktif
$campaigns = $pdo->query("SELECT id, judul, target_dana, dana_terkumpul, dana_tersalurkan, 
                                 (dana_terkumpul - dana_tersalurkan) AS sisa_saldo 
                          FROM campaigns WHERE status = 'aktif' ORDER BY id ASC")->fetchAll();

// PROSES SUBMIT REALISASI BARU
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = (int)$_POST['campaign_id'];
    $amount_raw = preg_replace('/[^0-9]/', '', $_POST['amount'] ?? '');
    $amount = (float)$amount_raw;
    $tgl_rencana = $_POST['tgl_rencana'] ?? date('Y-m-d');
    $tgl_realisasi = $_POST['tgl_realisasi'] ?: date('Y-m-d');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Cek saldo kampanye
    $stmtCamp = $pdo->prepare("SELECT judul, dana_terkumpul, dana_tersalurkan, (dana_terkumpul - dana_tersalurkan) AS sisa_saldo FROM campaigns WHERE id = ?");
    $stmtCamp->execute([$campaign_id]);
    $campData = $stmtCamp->fetch();

    if (!$campData) {
        $errors[] = "Kampanye tidak valid.";
    } elseif ($amount <= 0) {
        $errors[] = "Nominal pencairan harus lebih dari Rp 0.";
    } elseif ($amount > $campData['sisa_saldo']) {
        $errors[] = "Validasi Gagal: Nominal pencairan (" . format_rupiah($amount) . ") melebihi sisa saldo escrow yang tersedia (" . format_rupiah($campData['sisa_saldo']) . ")!";
    }

    if (empty($errors)) {
        // Simpan realisasi penyaluran ke disbursements
        $stmtIns = $pdo->prepare("INSERT INTO disbursements 
            (campaign_id, admin_id, jumlah, tgl_rencana, tgl_realisasi, keterangan, bukti_foto, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'assets/img/bukti-transfer-termin.jpg', 'tersalur', NOW())");
        $stmtIns->execute([$campaign_id, $admin_id, $amount, $tgl_rencana, $tgl_realisasi, $keterangan]);
        $newDisbId = (int)$pdo->lastInsertId();

        // Tambah akumulasi dana_tersalurkan di campaigns
        $pdo->prepare("UPDATE campaigns SET dana_tersalurkan = dana_tersalurkan + ? WHERE id = ?")->execute([$amount, $campaign_id]);

        log_activity($pdo, 'admin', $admin_id, 'Realisasi Penyaluran Baru', 'disbursements', $newDisbId, "Pencairan {$campData['judul']} sebesar " . format_rupiah($amount));

        set_flash('success', "Penyaluran dana sebesar " . format_rupiah($amount) . " untuk {$campData['judul']} berhasil dicairkan!");
        header("Location: penyaluran.php");
        exit;
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Realisasi Penyaluran - Admin</title>
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

      <?php if (!empty($errors)): ?>
        <div class="flash-message flash-danger">
          <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="admin-page-heading">
        <div>
          <h1>Formulir Realisasi Pencairan Dana Escrow</h1>
          <p>Catat dan validasi pencairan dana termin ke penggalang/vendor lapangan secara transparan.</p>
        </div>
        <a class="admin-soft-action" href="penyaluran.php">← Kembali ke Daftar</a>
      </div>

      <div class="card" style="max-width: 760px; padding: 28px;">
        <form method="POST" action="realisasi.php">
          <div class="form-group">
            <label class="form-label required">Pilih Kampanye Program</label>
            <select name="campaign_id" class="form-control" required id="campSelect">
              <option value="">-- Pilih Kampanye --</option>
              <?php foreach ($campaigns as $c): ?>
                <option value="<?= $c['id'] ?>" data-saldo="<?= $c['sisa_saldo'] ?>">
                  <?= htmlspecialchars($c['judul']) ?> (Saldo Escrow: <?= format_rupiah($c['sisa_saldo']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label required">Nominal Pencairan</label>
            <div class="input-with-prefix">
              <span class="prefix">Rp</span>
              <input type="text" name="amount" id="disbAmount" class="form-control" required placeholder="Contoh: 10.000.000">
            </div>
            <small style="color: var(--text-muted); font-size: 11px;">Nominal tidak boleh melebihi sisa saldo escrow kampanye terkait.</small>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group">
              <label class="form-label required">Tanggal Rencana</label>
              <input type="date" name="tgl_rencana" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Tanggal Realisasi Transfer</label>
              <input type="date" name="tgl_realisasi" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label required">Keterangan / Keperluan Termin</label>
            <textarea name="keterangan" class="form-control" rows="4" required placeholder="Contoh: Pembayaran pengadaan bibit karang dan rangka besi termin 1..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
            ✓ Realisasikan Penyaluran Dana
          </button>
        </form>
      </div>
    </main>
  </div>
</div>

<script>
const amountInput = document.getElementById('disbAmount');
if (amountInput) {
  amountInput.addEventListener('input', function() {
    let val = this.value.replace(/[^0-9]/g, '');
    this.value = val ? parseInt(val).toLocaleString('id-ID') : '';
  });
}
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
