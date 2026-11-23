<?php
// ========================================================
// FAQ & PUSAT BANTUAN (DEMO SEDERHANA TANPA CSS & JS)
// Berisi 1 pertanyaan & jawaban FAQ, serta form keluhan & submitnya
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$success_message = '';
$error_message = '';

// Proses submit form keluhan / help ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_submit_ticket'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subjek = trim($_POST['subjek'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    if (empty($nama) || empty($email) || empty($subjek) || empty($pesan)) {
        $error_message = 'Lengkapi seluruh data keluhan Anda!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format email tidak valid!';
    } else {
        $stmtTicket = $pdo->prepare("INSERT INTO help_tickets (nama, email, subjek, pesan, status, created_at) VALUES (?, ?, ?, ?, 'baru', NOW())");
        $stmtTicket->execute([$nama, $email, $subjek, $pesan]);
        $success_message = 'Keluhan dan pesan bantuan Anda telah berhasil dikirim ke admin!';
    }
}

// Ambil 1 FAQ dari database
$faq = $pdo->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY urutan ASC, id ASC LIMIT 1")->fetch();
if (!$faq) {
    $faq = [
        'pertanyaan' => 'Bagaimana cara berdonasi di platform NusantaraHijau?',
        'jawaban' => 'Anda cukup membuka menu Kampanye, klik tombol "Donasi untuk kampanye ini", masukkan nominal dan nama, lalu lakukan pembayaran via QRIS atau Virtual Account bank.'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>FAQ &amp; Pusat Bantuan - NusantaraHijau</title>
    <link rel="stylesheet" href="assets/css/public-navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/reference-faq.css">
    <link rel="stylesheet" href="assets/css/location-hero-overrides.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="reference-faq faq-hero-page">

    <?php $navbar_demo = true; require __DIR__ . '/includes/public-navbar.php'; ?>

    <div id="legacy-faq" style="display:none">
    <br>
    <h2>FAQ &amp; Pusat Bantuan</h2>
    <p>Halaman demo tanya-jawab umum dan layanan keluhan / tiket bantuan.</p>

    <!-- BAGIAN 1: TANYA JAWAB FAQ (1 PERTANYAAN & JAWABAN) -->
    <h3>Frequently Asked Questions (FAQ)</h3>
    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <tr bgcolor="#eeeeee">
            <th width="30%">Pertanyaan</th>
            <th>Jawaban</th>
        </tr>
        <tr>
            <td><b><?= htmlspecialchars($faq['pertanyaan']) ?></b></td>
            <td><?= nl2br(htmlspecialchars($faq['jawaban'])) ?></td>
        </tr>
    </table>

    <br><hr><br>

    <!-- BAGIAN 2: FORM KELUHAN & PUSAT BANTUAN -->
    <h3>Form Keluhan &amp; Bantuan Pengguna</h3>
    <p>Sampaikan kendala atau pertanyaan Anda melalui formulir di bawah ini:</p>

    <?php if ($success_message): ?>
        <p><b><font color="green"><?= htmlspecialchars($success_message) ?></font></b></p>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <p><b><font color="red"><?= htmlspecialchars($error_message) ?></font></b></p>
    <?php endif; ?>

    <form method="POST" action="faq-bantuan.php">
        <input type="hidden" name="action_submit_ticket" value="1">
        <table border="1" cellpadding="8" cellspacing="0">
            <tr>
                <td><b>Nama Anda</b></td>
                <td><input type="text" name="nama" size="40" required value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td><b>Email Aktif</b></td>
                <td><input type="email" name="email" size="40" required></td>
            </tr>
            <tr>
                <td><b>Subjek Keluhan</b></td>
                <td><input type="text" name="subjek" size="40" required placeholder="Contoh: Kendala verifikasi donasi"></td>
            </tr>
            <tr>
                <td><b>Isi Pesan Keluhan</b></td>
                <td><textarea name="pesan" rows="5" cols="42" required placeholder="Jelaskan kendala Anda secara detail..."></textarea></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="submit" style="padding: 8px 16px; cursor: pointer;">Submit Keluhan</button>
                </td>
            </tr>
        </table>
    </form>

</div>

<section class="rf-hero">
  <h1>Ada yang bisa kami bantu?</h1>
  <p>Temukan panduan cepat seputar transparansi donasi, metode pembayaran, sertifikat donasi, dan verifikasi lapangan aktif di NusantaraHijau.</p>
  <div class="rf-search"><input id="faq-search" type="text" placeholder="Ketik kata kunci (misal: QRIS, transfer, nama...)" onkeyup="filterFaq()"><button type="button" onclick="filterFaq()">Cari Solusi</button></div>
  <div class="rf-tags"><span>Pencarian Populer:</span><button type="button" onclick="setFaqSearch('QRIS')">QRIS Instan</button><button type="button" onclick="setFaqSearch('Pajak')">Pengurang Pajak</button><button type="button" onclick="setFaqSearch('Hamba Allah')">Hamba Allah</button></div>
</section>
<main>
  <div class="rf-container rf-layout">
    <section id="faq-content" class="rf-content"><h2>Pertanyaan Sering Diajukan</h2><p>Klik pertanyaan untuk membaca panduan selengkapnya.</p><div id="faq-list">
    <article class="rf-faq" data-category="bayar"><button type="button" onclick="toggleFaq(this)"><span>01 Apakah ada batas minimum donasi?</span><span>▼</span></button><div class="rf-answer">Nominal donasi dimulai dari Rp1.000 agar seluruh lapisan masyarakat dapat berpartisipasi.</div></article>
      <article class="rf-faq" data-category="bayar"><button type="button" onclick="toggleFaq(this)"><span>02 Bagaimana cara berdonasi menggunakan QRIS?</span><span>▼</span></button><div class="rf-answer">Pilih kampanye, klik donasi, masukkan nominal, pilih QRIS, lalu selesaikan pembayaran melalui aplikasi perbankan atau dompet digital Anda.</div></article>
      <article class="rf-faq" data-category="akun"><button type="button" onclick="toggleFaq(this)"><span>03 Bisakah berdonasi tanpa menampilkan nama?</span><span>▼</span></button><div class="rf-answer">Ya. Aktifkan pilihan Hamba Allah atau sembunyikan nama pada formulir donasi sebelum melanjutkan pembayaran.</div></article>
      <article class="rf-faq" data-category="laporan"><button type="button" onclick="toggleFaq(this)"><span>04 Bagaimana memantau penggunaan dana donasi?</span><span>▼</span></button><div class="rf-answer">Dampak dan penyaluran dana dapat dipantau melalui halaman kampanye dan laporan transparansi.</div></article>
      <?php if ($faq): ?><article class="rf-faq" data-category="laporan"><button type="button" onclick="toggleFaq(this)"><span>05 <?= htmlspecialchars($faq['pertanyaan']) ?></span><span>▼</span></button><div class="rf-answer"><?= nl2br(htmlspecialchars($faq['jawaban'])) ?></div></article><?php endif; ?>
    </div></section>
  </div>
  <section class="rf-contact"><div class="rf-container rf-help-grid"><div class="rf-ticket"><h3>Kirim Tiket Bantuan</h3><?php if ($success_message): ?><div class="rf-message ok"><?= htmlspecialchars($success_message) ?></div><?php endif; ?><?php if ($error_message): ?><div class="rf-message err"><?= htmlspecialchars($error_message) ?></div><?php endif; ?><form method="POST" action="faq-bantuan.php"><input type="hidden" name="action_submit_ticket" value="1"><div class="rf-row"><div class="rf-field"><label for="nama">Nama Lengkap *</label><input id="nama" type="text" name="nama" required value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>"></div><div class="rf-field"><label for="email">Email Aktif *</label><input id="email" type="email" name="email" required></div></div><div class="rf-field"><label for="subjek">Subjek Keluhan *</label><input id="subjek" type="text" name="subjek" required></div><div class="rf-field"><label for="pesan">Pesan / Kendala *</label><textarea id="pesan" name="pesan" rows="4" required></textarea></div><button type="submit" class="rf-submit">Kirim Tiket Bantuan &rarr;</button></form></div></div></section>
</main>
<script>
function toggleFaq(btn){btn.parentElement.classList.toggle('open')}function setFaqSearch(q){document.getElementById('faq-search').value=q;filterFaq()}function filterFaq(){var q=document.getElementById('faq-search').value.toLowerCase();document.querySelectorAll('#faq-list .rf-faq').forEach(function(item){item.style.display=item.innerText.toLowerCase().includes(q)?'':'none'})}function filterCategory(cat,btn){document.querySelectorAll('.rf-menu button').forEach(function(b){b.classList.remove('active')});btn.classList.add('active');document.querySelectorAll('#faq-list .rf-faq').forEach(function(item){item.style.display=(cat==='all'||item.dataset.category===cat)?'':'none'})}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
