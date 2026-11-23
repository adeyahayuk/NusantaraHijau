<?php
// ========================================================
// MODUL 6: HALAMAN 2 - PEMBAYARAN DONASI QRIS & SIMULASI CEPAT
// ========================================================
$page_title = "Selesaikan Pembayaran Donasi QRIS";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$code = $_GET['code'] ?? '';
if (empty($code)) {
    header("Location: daftar-kampanye.php");
    exit;
}

// Ambil data donasi dan kampanye (11 Tabel Final)
$stmt = $pdo->prepare("SELECT d.*, c.judul AS campaign_title, c.target_dana, c.dana_terkumpul, 
                              c.donor_count, c.tgl_selesai, c.gambar, u.nama AS nama_penggalang, u.organisasi 
                       FROM donations d
                       JOIN campaigns c ON d.campaign_id = c.id
                       LEFT JOIN users u ON c.user_id = u.id
                       WHERE d.kode_invoice = ?");
$stmt->execute([$code]);
$donation = $stmt->fetch();

if (!$donation) {
    header("Location: daftar-kampanye.php");
    exit;
}

// Jika sudah lunas / berhasil, langsung arahkan ke halaman berhasil
if ($donation['status'] === 'berhasil') {
    header("Location: berhasil.php?code=" . urlencode($code));
    exit;
}

// Hitung sisa hari dan progres kampanye
$sisa_hari = max(0, (int) ceil((strtotime($donation['tgl_selesai']) - strtotime('today')) / 86400));
$target = (float)$donation['target_dana'];
$collected = (float)$donation['dana_terkumpul'];
$percent = $target > 0 ? min(100, round(($collected / $target) * 100, 1)) : 0;

// PROSES SIMULASI PEMBAYARAN (OTOMATIS BERHASIL)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simulate_payment') {
    $now = date('Y-m-d H:i:s');
    $hash = generate_escrow_hash($donation['kode_invoice'], $donation['jumlah'], $now);

    // Update donasi jadi 'berhasil'
    $stmtUpdate = $pdo->prepare("UPDATE donations 
                                 SET status = 'berhasil', paid_at = ?, sha256_hash = ? 
                                 WHERE kode_invoice = ?");
    $stmtUpdate->execute([$now, $hash, $code]);

    // Update akumulasi dana kampanye & jumlah donatur
    $stmtCamp = $pdo->prepare("UPDATE campaigns
                   SET status = CASE
                       WHEN status = 'aktif' AND dana_terkumpul + ? >= target_dana THEN 'selesai'
                       ELSE status
                     END,
                     dana_terkumpul = dana_terkumpul + ?,
                     donor_count = donor_count + 1
                   WHERE id = ?");
    $stmtCamp->execute([$donation['jumlah'], $donation['jumlah'], $donation['campaign_id']]);

    // Catat activity log
    log_activity($pdo, 'user', null, 'Simulasi Pelunasan Sukses', 'donations', (int)$donation['id'], "Pelunasan donasi {$donation['kode_invoice']} ({$donation['campaign_title']}) sebesar " . format_rupiah($donation['jumlah']));

    // Redirect ke Halaman Berhasil (Modul 6 Halaman 3)
    header("Location: berhasil.php?code=" . urlencode($code));
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- TAHAPAN DONASI PUBLIK (FLOW STEP HEADER) -->
<div class="donation-flow-wrapper" style="max-width: 1200px; margin: 16px auto 14px; padding: 0 16px;">
  <div class="flow-header-box" style="background: #ffffff; border: 1px solid #edf2f7; border-radius: 12px; padding: 14px 18px;">
    <div class="flow-indicator-line" style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 13px; font-weight: 700; color: #1e293b;">
      <span class="flow-dot-green" style="color: #16a34a; font-size: 11px;">●</span>
      <span>Tahapan Donasi Publik</span>
    </div>
    <div class="flow-steps-row" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
      <div class="flow-step-item" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; background: #f1f5f9; color: #1e293b; text-decoration: none;">
        <div class="flow-step-badge" style="width: 26px; height: 26px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center;">1</div>
        <div>
          <div class="flow-step-title" style="font-weight: 700; font-size: 13px;">Form Donasi</div>
          <div class="flow-step-sub" style="font-size: 11px; color: #64748b;">Pilih nominal &amp; data</div>
        </div>
      </div>
      <div class="flow-step-item active" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; background: #005d86; color: #ffffff; text-decoration: none;">
        <div class="flow-step-badge" style="width: 26px; height: 26px; border-radius: 6px; background: rgba(255,255,255,0.2); color: #ffffff; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center;">2</div>
        <div>
          <div class="flow-step-title" style="font-weight: 700; font-size: 13px;">Bayar Donasi</div>
          <div class="flow-step-sub" style="font-size: 11px; color: rgba(255,255,255,0.85);">Scan QRIS Nasional</div>
        </div>
      </div>
      <div class="flow-step-item" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; background: #f1f5f9; color: #1e293b; text-decoration: none;">
        <div class="flow-step-badge" style="width: 26px; height: 26px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center;">3</div>
        <div>
          <div class="flow-step-title" style="font-weight: 700; font-size: 13px;">Pembayaran Berhasil &amp; Invoice</div>
          <div class="flow-step-sub" style="font-size: 11px; color: #64748b;">Struk resmi siap cetak</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- KONTEN DUA KOLOM PEMBAYARAN -->
<main class="donation-layout">
  <!-- KOLOM KIRI: INSTRUKSI BAYAR QRIS & SIMULASI -->
  <div class="card payment-card">
    <div class="card-header-styled">
      <div class="card-title-group">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00628F" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        <h2>Pembayaran QRIS Nasional</h2>
      </div>
      <span class="status-pill-warning">MENUNGGU SCAN PEMBAYARAN</span>
    </div>

    <!-- METADATA TAGIHAN & COUNTDOWN -->
    <div class="payment-meta-bar">
      <span>Kode Donasi: <strong>#<?= htmlspecialchars($donation['kode_invoice']) ?></strong></span>
      <span>•</span>
      <span>Batas Waktu: <strong class="countdown-box" id="paymentCountdown">23 : 59 : 59</strong></span>
    </div>

    <!-- PANE QRIS (SATU-SATUNYA METODE PEMBAYARAN) -->
    <div class="payment-instruction-box">
      <div class="qris-display-area">
        <div class="qris-code-box">
          <span style="font-size: 11px; font-weight: 800; color: #dc2626; display: block; margin-bottom: 6px;">QRIS • GPN NASIONAL</span>
          <img src="assets/img/qris-code.svg" alt="Kode QRIS NusantaraHijau">
          <span style="font-size: 10px; color: var(--text-muted); font-family: monospace;">NMID: ID102024098273</span>
        </div>

        <div class="qris-details">
          <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); display: block;">NOMINAL DONASI POKOK:</span>
          <h3><?= format_rupiah($donation['jumlah']) ?></h3>
          <div class="receiver">Penerima: <strong>Yayasan Nusantara Hijau Mandiri</strong></div>

          <div style="font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #0f172a;">Petunjuk Pembayaran QRIS:</div>
          <ol class="instruction-steps">
            <li>Buka aplikasi m-Banking atau e-Wallet (BCA Mobile, GoPay, OVO, ShopeePay, DANA, Livin', dll).</li>
            <li>Pilih menu <strong>Scan QR / Bayar</strong> lalu arahkan kamera ke kode QRIS di samping.</li>
            <li>Nominal <strong><?= format_rupiah($donation['jumlah']) ?></strong> akan otomatis terisi. Konfirmasi pembayaran dengan PIN Anda.</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- KOTAK SIMULASI CEPAT -->
    <div class="simulation-alert-box">
      <strong>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Simulasi Pembayaran Cepat
      </strong>
      <p>Tekan tombol hijau di bawah ini untuk mensimulasikan scan QRIS sukses dan melihat tanda terima resmi.</p>
    </div>

    <!-- FORM SUBMIT SIMULASI PELUNASAN -->
    <form method="POST" action="pembayaran.php?code=<?= urlencode($code) ?>">
      <input type="hidden" name="action" value="simulate_payment">
      <button type="submit" class="btn-simulate-pay">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>Simulasi Bayar Sekarang (Otomatis Berhasil)</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </form>

    <a href="donasi.php?campaign_id=<?= $donation['campaign_id'] ?>" class="btn-back-link">← Kembali ke Form Donasi</a>
  </div>

  <!-- KOLOM KANAN: RINGKASAN KAMPANYE & DATA DONATUR -->
  <aside>
    <!-- RINGKASAN KAMPANYE - tampilan baru dengan image overlay -->
    <div style="background:#ffffff; border:1px solid #edf2f7; border-radius:12px; overflow:hidden;">
      <!-- Gambar dengan overlay teks -->
      <div style="position:relative; width:100%; height:200px; overflow:hidden; background:#0f172a;">
        <img src="<?= htmlspecialchars($donation['gambar'] ?? 'assets/img/campaign-cenderawasih.png') ?>"
             alt="Cover Kampanye"
             style="width:100%; height:100%; object-fit:cover; display:block;">
        <div style="position:absolute; bottom:0; left:0; right:0; background:linear-gradient(180deg,rgba(15,23,42,0) 0%,rgba(15,23,42,0.85) 55%,rgba(15,23,42,0.96) 100%); padding:14px 14px 10px;">
          <div style="font-size:9.5px; font-weight:700; color:#cbd5e1; letter-spacing:0.5px; text-transform:uppercase;">PROGRAM KONSERVASI</div>
          <div style="font-size:14px; font-weight:700; color:#ffffff; margin-top:2px; line-height:1.3;"><?= htmlspecialchars($donation['campaign_title']) ?></div>
        </div>
      </div>
      <!-- Body konten -->
      <div style="padding:16px;">
        <!-- Jumlah & Target -->
        <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:8px;">
          <span style="font-size:15.5px; font-weight:800; color:#005d86;"><?= format_rupiah($collected) ?></span>
          <span style="font-size:12px; color:#64748b;">Target: <?= format_rupiah($target) ?></span>
        </div>
        <!-- Progress bar -->
        <div style="background:#edf2f7; height:7px; border-radius:9999px; overflow:hidden; margin-bottom:10px;">
          <div style="background:#00628F; height:100%; border-radius:9999px; width:<?= $percent ?>%;"></div>
        </div>
        <!-- Statistik -->
        <div style="display:flex; justify-content:space-between; align-items:center; font-size:11.5px; color:#334155; margin-bottom:14px;">
          <span><strong><?= $percent ?>%</strong> Terkumpul</span>
          <span style="display:inline-flex; align-items:center; gap:4px;">
            <strong><?= number_format((int)$donation['donor_count']) ?></strong> Donatur
          </span>
          <span style="display:inline-flex; align-items:center; gap:4px;">
            Sisa <strong><?= $sisa_hari ?></strong> Hari
          </span>
        </div>
        <!-- Nama/Organisasi Pemohon -->
        <div style="margin-bottom:14px;">
          <div style="font-size:11px; color:#64748b; margin-bottom:3px;">Nama / Organisasi Peenggalang Dana</div>
          <div style="font-size:13px; font-weight:700; color:#0f172a;"><?= htmlspecialchars($donation['organisasi'] ?: $donation['nama_penggalang']) ?></div>
        </div>
        <!-- Donatur -->
        <div style="display:flex; align-items:center; gap:8px; background:#edf4fc; border-radius:8px; padding:10px 12px; margin-bottom:<?= !empty($donation['pesan']) ? '12px' : '0' ?>;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#005d86" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <div>
            <div style="font-size:13px; font-weight:700; color:#005d86;"><?= $donation['is_anonim'] ? 'Anonim / Sahabat Konservasi' : htmlspecialchars($donation['nama_donatur']) ?></div>
            <?php if (!$donation['is_anonim'] && !empty($donation['email'])): ?>
            <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($donation['email']) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <!-- Pesan Donatur -->
        <?php if (!empty($donation['pesan'])): ?>
        <div>
          <div style="font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase;">PESAN &amp; HARAPAN:</div>
          <div style="font-size:12px; color:#334155; font-style:italic; line-height:1.5;">&ldquo;<?= htmlspecialchars($donation['pesan']) ?>&rdquo;</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </aside>
</main>

<script>
// Countdown timer realtime sederhana 24 jam
let totalSeconds = 23 * 3600 + 59 * 60 + 59;
const countdownEl = document.getElementById('paymentCountdown');
setInterval(() => {
  if (totalSeconds <= 0) return;
  totalSeconds--;
  const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
  const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
  const s = String(totalSeconds % 60).padStart(2, '0');
  if (countdownEl) countdownEl.textContent = `${h} : ${m} : ${s}`;
}, 1000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
