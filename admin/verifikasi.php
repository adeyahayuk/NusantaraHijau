<?php
// ========================================================
// MODUL 8: VERIFIKASI PROPOSAL KAMPANYE OLEH ADMIN
// Admin memverifikasi dari isi pengajuan (deskripsi, lokasi, gambar)
// dan dapat menghubungi penggalang via WhatsApp (no_hp di users)
// ========================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$admin_id = (int)($_SESSION['admin_id'] ?? 1);

// PROSES AKSI VERIFIKASI (SETUJUI / TOLAK)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $notes = trim($_POST['admin_notes'] ?? '');
    $target_id = (int)$_POST['campaign_id'];

    if ($action === 'approve') {
        $stmtApprove = $pdo->prepare("UPDATE campaigns 
                                      SET status = 'aktif', catatan_admin = ?, verified_by = ?, verified_at = NOW() 
                                      WHERE id = ?");
        $stmtApprove->execute(['Disetujui oleh Super Admin. Siap dipublikasikan ke publik.', $admin_id, $target_id]);
        log_activity($pdo, 'admin', $admin_id, 'Menyetujui Kampanye', 'campaigns', $target_id, "Menyetujui kampanye ID #{$target_id}");
        set_flash('success', "Kampanye ID #{$target_id} berhasil disetujui dan kini aktif di feed publik!");
    } elseif ($action === 'reject') {
        $stmtReject = $pdo->prepare("UPDATE campaigns 
                                     SET status = 'ditolak', catatan_admin = ?, verified_by = ?, verified_at = NOW() 
                                     WHERE id = ?");
        $stmtReject->execute([$notes ?: 'Ditolak oleh admin.', $admin_id, $target_id]);
        log_activity($pdo, 'admin', $admin_id, 'Menolak Kampanye', 'campaigns', $target_id, "Menolak kampanye ID #{$target_id}. Alasan: {$notes}");
        set_flash('danger', "Pengajuan kampanye ID #{$target_id} telah ditolak.");
    }

    header("Location: verifikasi.php");
    exit;
}

// Ambil kampanye yang terpilih atau kampanye pending pertama
$camp_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($pdo->query("SELECT id FROM campaigns WHERE status = 'pending' ORDER BY id DESC LIMIT 1")->fetchColumn() ?: 0);

$stmt = $pdo->prepare("SELECT c.*, u.nama AS pic_name, u.organisasi, u.no_hp, u.email 
                       FROM campaigns c 
                       LEFT JOIN users u ON c.user_id = u.id 
                       WHERE c.id = ?");
$stmt->execute([$camp_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    // Fallback ke kampanye pending pertama
    $campaign = $pdo->query("SELECT c.*, u.nama AS pic_name, u.organisasi, u.no_hp, u.email 
                             FROM campaigns c 
                             LEFT JOIN users u ON c.user_id = u.id 
                             WHERE c.status = 'pending' ORDER BY c.id DESC LIMIT 1")->fetch();
}

// Daftar seluruh kampanye yang menunggu verifikasi (pending)
$pendingCampaigns = $pdo->query("SELECT c.*, u.nama AS pic_name, u.organisasi, u.no_hp, u.email 
                                FROM campaigns c 
                                LEFT JOIN users u ON c.user_id = u.id 
                                WHERE c.status = 'pending' 
                                ORDER BY c.id DESC")->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Verifikasi Proposal Kampanye - Admin</title>
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
          <h1>Verifikasi Proposal Kampanye Konservasi</h1>
          <p>Tinjau deskripsi, lokasi, dan dokumentasi foto pengajuan penggalangan dana. Hubungi kontak WhatsApp penggalang untuk konfirmasi jika diperlukan.</p>
        </div>
      </div>

      <div class="verification-grid">
        <aside class="card verification-queue">
          <h2>Daftar Pengajuan Kampanye Baru</h2>
          <div class="verification-queue-list">
            <?php foreach ($pendingCampaigns as $p):
              $pPhone = preg_replace('/[^0-9]/', '', $p['no_hp'] ?? '');
              if (str_starts_with($pPhone, '0')) $pPhone = '62' . substr($pPhone, 1);
              $selected = ($p['id'] == ($campaign['id'] ?? 0));
            ?>
              <a href="verifikasi.php?id=<?= (int)$p['id'] ?>" class="verification-queue-card <?= $selected ? 'selected' : '' ?>">
                <div class="verification-thumb" style="background-image:url('../<?= htmlspecialchars($p['gambar']) ?>')"></div>
                <div class="verification-queue-info">
                  <div class="verification-card-line">
                    <strong>#PROP-2024-<?= str_pad((string)$p['id'], 3, '0', STR_PAD_LEFT) ?></strong>
                    <span class="verification-status">Menunggu Verifikasi</span>
                  </div>
                  <h3><?= htmlspecialchars($p['judul']) ?></h3>
                  <p><?= htmlspecialchars($p['organisasi'] ?: 'Inisiator Kampanye') ?></p>
                  <?php if (!empty($pPhone)): ?><span class="verification-target">Target: <?= format_rupiah($p['target_dana']) ?></span><?php endif; ?>
                  <div class="verification-meta">
                    <span><?= htmlspecialchars($p['no_hp'] ?: '-') ?></span>
                    <span class="verification-verified">Terverifikasi</span>
                  </div>
                  <?php if (!empty($pPhone)): ?>
                    <span class="verification-wa" onclick="event.preventDefault(); event.stopPropagation(); window.open('https://wa.me/<?= $pPhone ?>','_blank')">Hubungi via WhatsApp</span>
                  <?php endif; ?>
                </div>
              </a>
            <?php endforeach; ?>
            <?php if (empty($pendingCampaigns)): ?><p class="admin-empty-state">Antrean pengajuan kosong.</p><?php endif; ?>
          </div>
        </aside>

        <aside class="card verification-detail">
          <?php if ($campaign):
            $cleanPhone = preg_replace('/[^0-9]/', '', $campaign['no_hp'] ?? '');
            if (str_starts_with($cleanPhone, '0')) $cleanPhone = '62' . substr($cleanPhone, 1);
          ?>
            <div class="verification-detail-image" style="background-image:url('../<?= htmlspecialchars($campaign['gambar']) ?>')">
              <span><span>Foto Dokumentasi / Inisiatif</span></span>
            </div>

            <div class="verification-detail-code">#PROP-2024-<?= str_pad((string)$campaign['id'], 3, '0', STR_PAD_LEFT) ?></div>
            <h2><?= htmlspecialchars($campaign['judul']) ?></h2>

            <div class="verification-detail-meta">
              <div><small>Nama / Organisasi</small><strong><?= htmlspecialchars($campaign['organisasi'] ?: $campaign['pic_name']) ?></strong></div>
              <div><small>Target Dana</small><strong><?= format_rupiah($campaign['target_dana']) ?></strong></div>
              <div><small>Rencana Durasi</small><strong>60 Hari</strong></div>
            </div>

            <div class="verification-description">
              <small>Ringkasan Kegiatan</small>
              <p><?= nl2br(htmlspecialchars($campaign['deskripsi'])) ?></p>
            </div>

            <div class="verification-contact">
              <div><small>KONTAK PENGGALANG</small><strong>☎ <?= htmlspecialchars($campaign['no_hp'] ?: '-') ?></strong></div>
              <?php if (!empty($cleanPhone)): ?><a href="https://wa.me/<?= $cleanPhone ?>?text=Halo%20<?= urlencode($campaign['pic_name']) ?>" target="_blank">Hubungi via WhatsApp</a><?php endif; ?>
              <p>Gunakan WhatsApp untuk koordinasi cepat mengenai proposal, jadwal wawancara, atau klarifikasi program.</p>
            </div>

            <form method="POST" action="verifikasi.php" class="verification-actions">
              <input type="hidden" name="campaign_id" value="<?= (int)$campaign['id'] ?>">
              <textarea name="admin_notes" class="form-control verification-note" rows="2" placeholder="Catatan verifikasi (opsional untuk persetujuan, wajib untuk penolakan)"></textarea>
              <button type="submit" name="action" value="approve" class="verification-approve" onclick="return confirm('Setujui kampanye ini dan publikasikan ke publik?')">✓ Setujui Pengajuan</button>
              <button type="submit" name="action" value="reject" class="verification-reject" onclick="return confirm('Tolak proposal kampanye ini?')">✕ Tolak dengan Alasan</button>
            </form>
          <?php else: ?>
            <div class="verification-empty"><h3>Tidak ada kampanye yang menunggu verifikasi</h3><p>Seluruh proposal telah diproses.</p></div>
          <?php endif; ?>
        </aside>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
