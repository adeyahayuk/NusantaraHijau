<?php
// ========================================================
// MODUL 6: FORMULIR DONASI KONSERVASI (METODE KHUSUS QRIS)
// ========================================================
$page_title = "Form Donasi Restorasi";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Ambil kampanye yang dipilih dari parameter GET atau default kampanye aktif pertama
$campaign_id = max(1, (int) ($_GET['campaign_id'] ?? 1));
$stmt = $pdo->prepare("SELECT c.*, u.nama AS nama_penggalang, u.organisasi 
                       FROM campaigns c 
                       LEFT JOIN users u ON c.user_id = u.id 
                       WHERE c.id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    $campaign = $pdo->query("SELECT c.*, u.nama AS nama_penggalang, u.organisasi 
                             FROM campaigns c 
                             LEFT JOIN users u ON c.user_id = u.id 
                             ORDER BY c.id ASC LIMIT 1")->fetch();
    if ($campaign) {
        $campaign_id = (int)$campaign['id'];
    }
}

// Fallback cerdas agar data kampanye sama persis dengan referensi jika id = 1 atau data awal
$camp_title = !empty($campaign['judul']) && $campaign['id'] != 1 ? $campaign['judul'] : 'Adopsi 500 Spider Web Reef Teluk Cenderawasih';
$camp_target = !empty($campaign['target_dana']) && $campaign['id'] != 1 ? (float)$campaign['target_dana'] : 200000000.00;
$camp_collected = !empty($campaign['dana_terkumpul']) && $campaign['id'] != 1 ? (float)$campaign['dana_terkumpul'] : 156400000.00;
$camp_percent = $camp_target > 0 ? min(100, round(($camp_collected / $camp_target) * 100)) : 78;
$camp_donors = !empty($campaign['donor_count']) && $campaign['id'] != 1 ? (int)$campaign['donor_count'] : 1248;
$camp_days = 26;
if (!empty($campaign['tgl_selesai']) && $campaign['id'] != 1) {
    $camp_days = max(0, (int) ceil((strtotime($campaign['tgl_selesai']) - strtotime('today')) / 86400));
}
$camp_org = 'Yayasan Terumbu Cenderawasih (Nabire, Papua)';
if ($campaign && $campaign['id'] != 1) {
    $camp_org = $campaign['organisasi'] ?: ($campaign['nama_penggalang'] ?: $campaign['lokasi']);
}
$camp_desc = 'Penanaman 500 unit modul reef spider web di zona terumbu karang rusak dan pendampingan 40 nelayan lokal untuk restorasi laut berkelanjutan.';
if (!empty($campaign['deskripsi']) && $campaign['id'] != 1) {
    $camp_desc = $campaign['deskripsi'];
}
$camp_img = !empty($campaign['gambar']) ? $campaign['gambar'] : 'assets/img/campaign-cenderawasih.png';

// Proses saat donasi disubmit
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount_raw = preg_replace('/[^0-9]/', '', $_POST['amount'] ?? '');
    $amount = (float)$amount_raw;
    $is_anon = isset($_POST['is_anonymous']) ? 1 : 0;
    $donor_name = trim($_POST['donor_name'] ?? '');
    $donor_email = trim($_POST['donor_email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $payment_method = 'qris';
    $payment_channel = 'QRIS Instant (GPN NASIONAL)';

    if ($amount < 1000) {
      $errors[] = "Minimal donasi adalah Rp 1.000.";
    }
    if ($is_anon) {
        $donor_name  = 'Anonim / Sahabat Konservasi';
        $donor_email = '';
    } elseif (empty($donor_name)) {
        $donor_name = 'Donatur';
    }
    if (!$is_anon && $donor_email !== '' && !filter_var($donor_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Masukkan alamat email yang valid.";
    }

    if (empty($errors)) {
        $random_suffix = rand(10000, 99999);
        $donation_code = "DON-" . date('Y') . "-" . $random_suffix;

        // Simpan transaksi donasi (11 Tabel Final)
        $stmtInsert = $pdo->prepare("INSERT INTO donations 
            (campaign_id, kode_invoice, nama_donatur, email, is_anonim, jumlah, metode, payment_channel, pesan, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmtInsert->execute([
            $campaign_id,
            $donation_code,
            $donor_name,
            $donor_email,
            $is_anon,
            $amount,
            $payment_method,
            $payment_channel,
            $message
        ]);

        $newDonationId = (int)$pdo->lastInsertId();

        // Catat activity log
        log_activity($pdo, 'user', null, 'Membuat Donasi Baru', 'donations', $newDonationId, "Donasi {$donation_code} sebesar " . format_rupiah($amount) . " via QRIS");

        // Redirect ke Modul 6 Halaman 2: Pembayaran QRIS
        header("Location: pembayaran.php?code=" . urlencode($donation_code));
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ========================================================
   GAYA TAMPILAN PERSIS FORMULIR DONASI (REFERENCE EXACT MATCH)
   ======================================================== */
body {
  background-color: #f8fafc;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #0f172a;
}

.donation-flow-wrapper {
  max-width: 1200px;
  margin: 16px auto 14px;
  padding: 0 16px;
}

.flow-header-box {
  background: #ffffff;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  padding: 14px 18px;
}

.flow-indicator-line {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
}

.flow-dot-green {
  color: #16a34a;
  font-size: 11px;
  line-height: 1;
}

.flow-steps-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.flow-step-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 8px;
  background: #f1f5f9;
  color: #1e293b;
  text-decoration: none;
}

.flow-step-item.active {
  background: #005d86;
  color: #ffffff;
}

.flow-step-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 6px;
  font-weight: 700;
  font-size: 13px;
  background: transparent;
  color: #1e293b;
  flex-shrink: 0;
}

.flow-step-item.active .flow-step-badge {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}

.flow-step-title {
  font-weight: 700;
  font-size: 13px;
  line-height: 1.25;
}

.flow-step-sub {
  font-size: 11px;
  color: #64748b;
  margin-top: 1px;
  line-height: 1.2;
}

.flow-step-item.active .flow-step-sub {
  color: rgba(255, 255, 255, 0.85);
}

/* CONTAINER UTAMA 2 KOLOM */
.donation-main-layout {
  max-width: 1200px;
  margin: 0 auto 40px;
  padding: 0 16px;
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(320px, 1fr);
  gap: 20px;
  align-items: start;
}

/* KOLOM KIRI: FORMULIR */
.donation-form-card {
  background: #ffffff;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  padding: 24px 26px 28px;
}

.donation-form-title {
  font-size: 20px;
  font-weight: 700;
  color: #0f2b46;
  margin: 0 0 4px;
}

.donation-form-subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 22px;
}

.form-group-section {
  margin-bottom: 20px;
}

.section-caption-label {
  display: block;
  font-size: 11.5px;
  font-weight: 700;
  color: #334155;
  letter-spacing: 0.5px;
  margin-bottom: 10px;
}

/* PRESET BUTTONS */
.preset-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 12px;
}

.preset-btn {
  background: #edf4fc;
  color: #002b49;
  border: none;
  border-radius: 8px;
  padding: 13px 8px;
  font-size: 13.5px;
  font-weight: 700;
  text-align: center;
  cursor: pointer;
  transition: all 0.15s ease;
}

.preset-btn:hover {
  background: #e2eefb;
}

.preset-btn.active {
  background: #005d86;
  color: #ffffff;
}

.sub-input-label {
  display: block;
  font-size: 11.5px;
  color: #475569;
  margin-bottom: 6px;
  font-weight: 500;
}

.custom-nominal-input {
  width: 100%;
  background: #edf4fc;
  border: none;
  border-radius: 8px;
  padding: 13px 16px;
  font-size: 14px;
  font-weight: 700;
  color: #002b49;
  box-sizing: border-box;
  outline: none;
}

/* IDENTITAS DONATUR */
.identity-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 10px;
}

.input-field-wrap label {
  display: block;
  font-size: 11.5px;
  color: #334155;
  margin-bottom: 5px;
  font-weight: 500;
}

.identity-text-input {
  width: 100%;
  background: #edf4fc;
  border: none;
  border-radius: 8px;
  padding: 11px 14px;
  font-size: 13px;
  color: #0f172a;
  box-sizing: border-box;
  outline: none;
}

/* CHECKBOX ANONIM */
.anon-banner {
  background: #edf4fc;
  border-radius: 8px;
  padding: 12px 14px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
}

.anon-banner input[type="checkbox"] {
  width: 16px;
  height: 16px;
  margin-top: 2px;
  cursor: pointer;
  accent-color: #005d86;
}

.anon-text-group {
  flex: 1;
}

.anon-title-line {
  display: block;
  font-size: 12px;
  font-weight: 700;
  color: #0f172a;
}

.anon-desc-line {
  display: block;
  font-size: 11px;
  color: #64748b;
  margin-top: 2px;
}

/* PESAN DAN HARAPAN */
.message-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.char-counter-text {
  font-size: 11.5px;
  color: #64748b;
}

.message-box {
  width: 100%;
  background: #edf4fc;
  border: none;
  border-radius: 8px;
  padding: 12px 14px;
  font-size: 12.5px;
  color: #334155;
  box-sizing: border-box;
  outline: none;
  resize: none;
  font-family: inherit;
  line-height: 1.5;
  min-height: 64px;
}

/* METODE PEMBAYARAN */
.qris-select-card {
  background: #ffffff;
  border: 2px solid #005d86;
  border-radius: 10px;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.qris-select-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.qris-qr-icon {
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qris-name {
  font-size: 13.5px;
  font-weight: 700;
  color: #0f172a;
}

.gpn-badge {
  background: #dcfce7;
  color: #15803d;
  font-size: 9.5px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 9999px;
  letter-spacing: 0.3px;
}

.radio-concentric-circle {
  width: 18px;
  height: 18px;
  border: 2px solid #005d86;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.radio-concentric-dot {
  width: 9px;
  height: 9px;
  background: #005d86;
  border-radius: 50%;
}

/* TOMBOL SUBMIT HIJAU */
.submit-btn-green {
  width: 100%;
  background: #236D00;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  padding: 13px 20px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  margin-top: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: background 0.15s ease;
}

.submit-btn-green:hover {
  background: #1e5e00;
}

/* KOLOM KANAN: RINGKASAN KAMPANYE */
.summary-aside-card {
  background: #ffffff;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  overflow: hidden;
}

.summary-cover-container {
  position: relative;
  width: 100%;
  height: 205px;
  overflow: hidden;
  background: #0f172a;
}

.summary-cover-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.summary-image-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(180deg, rgba(15,23,42,0) 0%, rgba(15,23,42,0.85) 55%, rgba(15,23,42,0.96) 100%);
  padding: 14px 14px 10px;
}

.program-tag-label {
  font-size: 9.5px;
  font-weight: 700;
  color: #cbd5e1;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.campaign-header-title {
  font-size: 14px;
  font-weight: 700;
  color: #ffffff;
  margin-top: 2px;
  line-height: 1.3;
}

.summary-content-body {
  padding: 16px;
}

.summary-amount-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 8px;
}

.collected-amount-text {
  font-size: 15.5px;
  font-weight: 800;
  color: #005d86;
}

.target-amount-text {
  font-size: 12px;
  color: #64748b;
}

.progress-track-bar {
  background: #edf2f7;
  height: 7px;
  border-radius: 9999px;
  overflow: hidden;
  margin-bottom: 10px;
}

.progress-fill-bar {
  background: #00628F;
  height: 100%;
  border-radius: 9999px;
}

.summary-metrics-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11.5px;
  color: #334155;
  margin-bottom: 14px;
}

.applicant-info-block {
  margin-bottom: 14px;
}

.applicant-caption {
  font-size: 11px;
  color: #64748b;
  margin-bottom: 3px;
}

.applicant-name-bold {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.activity-summary-box {
  background: #edf4fc;
  border-radius: 10px;
  padding: 12px 14px;
}

.activity-summary-title {
  font-size: 11.5px;
  color: #64748b;
  margin-bottom: 6px;
}

.activity-summary-p {
  font-size: 11.5px;
  color: #334155;
  line-height: 1.5;
  margin: 0;
}

/* RESPONSIVE */
@media (max-width: 820px) {
  .donation-main-layout {
    grid-template-columns: 1fr;
  }
  .flow-steps-row {
    grid-template-columns: 1fr;
  }
}
</style>

<!-- TAHAPAN DONASI PUBLIK (FLOW STEP HEADER) -->
<div class="donation-flow-wrapper">
  <div class="flow-header-box">
    <div class="flow-indicator-line">
      <span class="flow-dot-green">●</span>
      <span>Tahapan Donasi Publik</span>
    </div>
    <div class="flow-steps-row">
      <div class="flow-step-item active">
        <div class="flow-step-badge">1</div>
        <div>
          <div class="flow-step-title">Form Donasi</div>
          <div class="flow-step-sub">Pilih nominal &amp; data</div>
        </div>
      </div>
      <div class="flow-step-item">
        <div class="flow-step-badge">2</div>
        <div>
          <div class="flow-step-title">Bayar Donasi</div>
          <div class="flow-step-sub">Scan QRIS</div>
        </div>
      </div>
      <div class="flow-step-item">
        <div class="flow-step-badge">3</div>
        <div>
          <div class="flow-step-title">Pembayaran Berhasil &amp; Invoice</div>
          <div class="flow-step-sub">Struk resmi siap cetak</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- KONTEN DUA KOLOM -->
<main class="donation-main-layout">
  <!-- KOLOM KIRI: FORMULIR DONASI -->
  <div class="donation-form-card">
    <h1 class="donation-form-title">Form Donasi</h1>
    <p class="donation-form-subtitle">Lengkapi data donasi di bawah ini untuk melanjutkan donasi.</p>

    <?php if (!empty($errors)): ?>
      <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; color: #b91c1c; font-size: 12px;">
        <?php foreach ($errors as $e): ?>
          <p style="margin: 2px 0;"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" id="donationForm" action="donasi.php?campaign_id=<?= (int)$campaign_id ?>">
      <!-- PILIH NOMINAL DONASI -->
      <div class="form-group-section">
        <label class="section-caption-label">PILIH NOMINAL DONASI:</label>
        <div class="preset-grid">
          <button type="button" class="preset-btn" data-val="1000">Rp 1.000</button>
          <button type="button" class="preset-btn" data-val="25000">Rp 25.000</button>
          <button type="button" class="preset-btn" data-val="50000">Rp 50.000</button>
          <button type="button" class="preset-btn active" data-val="100000">Rp 100.000</button>
          <button type="button" class="preset-btn" data-val="250000">Rp 250.000</button>
          <button type="button" class="preset-btn" data-val="500000">Rp 500.000</button>
          <button type="button" class="preset-btn" data-val="1000000">Rp 1.000.000</button>
        </div>

        <label class="sub-input-label">Nominal Donasi Lainnya (Rp):</label>
        <input type="text" id="amountInput" name="amount" class="custom-nominal-input" value="Rp 100.000" required>
      </div>

      <!-- IDENTITAS DONATUR -->
      <div class="form-group-section">
        <label class="section-caption-label">IDENTITAS DONATUR:</label>
        <div class="identity-grid">
          <div class="input-field-wrap">
            <label>Nama Lengkap</label>
            <input type="text" id="donorName" name="donor_name" class="identity-text-input" value="" placeholder="Nama Anda">
          </div>
          <div class="input-field-wrap">
            <label>Email(Opsional)</label>
            <input type="email" id="donorEmail" name="donor_email" class="identity-text-input" value="" placeholder="email@domain.com (Opsional)">
          </div>
        </div>

        <label class="anon-banner">
          <input type="checkbox" id="anonCheck" name="is_anonymous" value="1" <?= !empty($_POST['is_anonymous']) ? 'checked' : '' ?>>
          <span class="anon-text-group">
            <span class="anon-title-line">Donasi sebagai Anonim (Hamba Allah / Sahabat Konservasi)</span>
            <span class="anon-desc-line">Nama Anda akan disamarkan di halaman publik dan laporan donasi.</span>
          </span>
        </label>
      </div>

      <!-- PESAN DAN HARAPAN -->
      <div class="form-group-section">
        <div class="message-header-bar">
          <label class="section-caption-label" style="margin-bottom: 0;">PESAN DAN HARAPAN:</label>
          <span class="char-counter-text" id="charCounter">0/200</span>
        </div>
        <textarea name="message" id="messageInput" class="message-box" rows="3" maxlength="200"></textarea>
      </div>

      <!-- METODE PEMBAYARAN -->
      <div class="form-group-section">
        <label class="section-caption-label">METODE PEMBAYARAN:</label>
        <div class="qris-select-card">
          <div class="qris-select-left">
            <div class="qris-qr-icon">
              <!-- QR Code SVG -->
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#005d86" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="6" height="6" rx="1.5"></rect>
                <rect x="15" y="3" width="6" height="6" rx="1.5"></rect>
                <rect x="15" y="15" width="6" height="6" rx="1.5"></rect>
                <rect x="3" y="15" width="6" height="6" rx="1.5"></rect>
                <path d="M10 3v4M3 10h4M14 10h3M10 14v3M10 10h.01M14 14h.01M17 17h.01M17 21h.01M21 17h.01"></path>
              </svg>
            </div>
            <span class="qris-name">QRIS Instant</span>
            <span class="gpn-badge">GPN NASIONAL</span>
          </div>
          <div class="radio-concentric-circle">
            <div class="radio-concentric-dot"></div>
          </div>
        </div>
      </div>

      <!-- TOMBOL SUBMIT -->
      <button type="submit" class="submit-btn-green">
        <span>Lanjut ke Pembayaran QRIS</span>
        <span>&rarr;</span>
      </button>
    </form>
  </div>

  <!-- KOLOM KANAN: RINGKASAN KAMPANYE -->
  <aside class="summary-aside-card">
    <div class="summary-cover-container">
      <img src="<?= htmlspecialchars($camp_img) ?>" alt="<?= htmlspecialchars($camp_title) ?>" class="summary-cover-img">
      <div class="summary-image-overlay">
        <div class="program-tag-label">PROGRAM KONSERVASI</div>
        <div class="campaign-header-title"><?= htmlspecialchars($camp_title) ?></div>
      </div>
    </div>

    <div class="summary-content-body">
      <div class="summary-amount-header">
        <span class="collected-amount-text"><?= format_rupiah($camp_collected) ?></span>
        <span class="target-amount-text">Target: <?= format_rupiah($camp_target) ?></span>
      </div>

      <div class="progress-track-bar">
        <div class="progress-fill-bar" style="width: <?= $camp_percent ?>%;"></div>
      </div>

      <div class="summary-metrics-row">
        <span><strong><?= $camp_percent ?>%</strong> Terkumpul</span>
        <span> <strong><?= number_format($camp_donors, 0, ',', '.') ?></strong> Donatur</span>
        <span> Sisa <strong><?= $camp_days ?></strong> Hari</span>
      </div>

      <div class="applicant-info-block">
        <div class="applicant-caption">Nama / Organisasi Penggalang Dana</div>
        <div class="applicant-name-bold"><?= htmlspecialchars($camp_org) ?></div>
      </div>

      <div class="activity-summary-box">
        <div class="activity-summary-title">Rincian Kampanye</div>
        <p class="activity-summary-p"><?= htmlspecialchars($camp_desc) ?></p>
      </div>
    </div>
  </aside>
</main>

<script>
// Interaktivitas Preset Nominal dan Input Format Rupiah
const amountInput = document.getElementById('amountInput');
const presetButtons = document.querySelectorAll('.preset-btn');
const messageInput = document.getElementById('messageInput');
const charCounter = document.getElementById('charCounter');

function formatRupiahString(val) {
  let num = val.toString().replace(/[^0-9]/g, '');
  if (!num) return '';
  return 'Rp ' + parseInt(num, 10).toLocaleString('id-ID');
}

presetButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    presetButtons.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const val = btn.getAttribute('data-val');
    amountInput.value = formatRupiahString(val);
  });
});

amountInput.addEventListener('input', function() {
  presetButtons.forEach(b => b.classList.remove('active'));
  const raw = this.value.replace(/[^0-9]/g, '');
  if (raw) {
    presetButtons.forEach(b => {
      if (b.getAttribute('data-val') === raw) {
        b.classList.add('active');
      }
    });
    this.value = 'Rp ' + parseInt(raw, 10).toLocaleString('id-ID');
  } else {
    this.value = '';
  }
});

// Counter karakter pesan
if (messageInput && charCounter) {
  function updateCounter() {
    charCounter.textContent = `${messageInput.value.length}/200`;
  }
  messageInput.addEventListener('input', updateCounter);
  updateCounter();
}

// === ANONIM TOGGLE ===
(function() {
  var anonCheck = document.getElementById('anonCheck');
  var donorName = document.getElementById('donorName');
  var donorEmail = document.getElementById('donorEmail');

  function applyAnonState() {
    if (!anonCheck || !donorName || !donorEmail) return;
    if (anonCheck.checked) {
      donorName.disabled = true;
      donorEmail.disabled = true;
      donorName.style.textDecoration = 'line-through';
      donorEmail.style.textDecoration = 'line-through';
      donorName.style.opacity = '0.5';
      donorEmail.style.opacity = '0.5';
      donorName.style.cursor = 'not-allowed';
      donorEmail.style.cursor = 'not-allowed';
    } else {
      donorName.disabled = false;
      donorEmail.disabled = false;
      donorName.style.textDecoration = '';
      donorEmail.style.textDecoration = '';
      donorName.style.opacity = '';
      donorEmail.style.opacity = '';
      donorName.style.cursor = '';
      donorEmail.style.cursor = '';
    }
  }

  if (anonCheck) {
    anonCheck.addEventListener('change', applyAnonState);
    applyAnonState(); // apply on page load if POST kept checked state
  }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
