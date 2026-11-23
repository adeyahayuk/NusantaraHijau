<?php
// ========================================================
// DAFTAR KAMPANYE (DEMO SEDERHANA MENGGUNAKAN TABLE TANPA CSS & JS)
// Syarat: Kampanye sudah di-ACC admin (status = 'aktif')
// dan masa/target belum tercapai (tgl_selesai >= CURDATE() AND dana_terkumpul < target_dana)
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Ambil kampanye yang status aktif, belum berakhir, dan target belum tercapai
$stmt = $pdo->prepare("SELECT c.*, u.nama AS nama_penggalang, u.organisasi 
                       FROM campaigns c 
                       LEFT JOIN users u ON c.user_id = u.id 
                       WHERE c.status = 'aktif' 
                         AND c.tgl_selesai >= CURRENT_DATE() 
                         AND c.dana_terkumpul < c.target_dana 
                       ORDER BY c.id DESC");
$stmt->execute();
$campaigns = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Kampanye Donasi - NusantaraHijau</title>
    <link rel="stylesheet" href="assets/css/public-navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/reference-campaign.css">
    <link rel="stylesheet" href="assets/css/location-hero-overrides.css">
</head>
<body class="reference-campaign campaign-hero-page">

    <?php $navbar_demo = true; require __DIR__ . '/includes/public-navbar.php'; ?>

    <div id="legacy-campaign-list" style="display:none">
        <br>
        <h2>Daftar Kampanye Donasi Terbuka</h2>
        <p>Menampilkan kampanye yang telah disetujui admin dan target dananya belum tercapai.</p>

        <!-- Tabel Daftar Kampanye -->
        <table border="1" cellpadding="10" cellspacing="0" width="100%">
            <thead>
                <tr bgcolor="#eeeeee">
                    <th>No</th>
                    <th>Foto</th>
                    <th>Judul Kampanye</th>
                    <th>Penggalang / Lokasi</th>
                    <th>Target Dana</th>
                    <th>Dana Terkumpul</th>
                    <th>Batas Waktu</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($campaigns) > 0): ?>
                    <?php foreach ($campaigns as $index => $camp): 
                        $persen = $camp['target_dana'] > 0 ? round(($camp['dana_terkumpul'] / $camp['target_dana']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td align="center"><?= $index + 1 ?></td>
                        <td align="center">
                            <?php if (!empty($camp['gambar'])): ?>
                                <img src="<?= htmlspecialchars($camp['gambar']) ?>" alt="Foto Kampanye" width="120">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($camp['judul']) ?></strong>
                            <p><?= htmlspecialchars(mb_strimwidth($camp['deskripsi'], 0, 150, '...')) ?></p>
                        </td>
                        <td>
                            <b><?= htmlspecialchars($camp['nama_penggalang'] ?? 'Penggalang') ?></b><br>
                            <small><?= htmlspecialchars($camp['organisasi'] ?? '') ?></small><br>
                            <small>Lokasi: <?= htmlspecialchars($camp['lokasi']) ?></small>
                        </td>
                        <td><?= format_rupiah($camp['target_dana']) ?></td>
                        <td>
                            <b><?= format_rupiah($camp['dana_terkumpul']) ?></b><br>
                            <small>(<?= $persen ?>% tercapai)</small>
                        </td>
                        <td><?= date('d M Y', strtotime($camp['tgl_selesai'])) ?></td>
                        <td align="center">
                            <!-- Tombol donasi untuk kampanye ini diarahkan ke modul pembayaran -->
                            <a href="donasi.php?campaign_id=<?= (int)$camp['id'] ?>">
                                <button type="button" style="padding: 8px 14px; cursor: pointer;">
                                    Donasi untuk kampanye ini
                            </button>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" align="center">Tidak ada kampanye aktif yang memenuhi syarat saat ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <section class="rc-hero">
        <div class="rc-container">
            <h1>Kampanye Konservasi <span>Terverifikasi</span></h1>
            <p>Pilih program pemulihan laut dan daratan Nusantara yang ingin Anda dukung secara transparan.</p>
            
            <!-- Form Pencarian Kampanye -->
            <form class="demo-search rc-hero-search" id="campaignSearchForm" role="search">
                <input id="campaignSearchInput" type="search" placeholder="Cari yang ingin kamu bantu" aria-label="Cari kampanye">
                <button class="demo-btn" type="submit">Cari Kampanye</button>
            </form>
        </div>
    </section>

    <section class="rc-campaign-section">
        <div class="rc-container rc-section-heading">
            <h2>Daftar Kampanye</h2>
            <div class="rc-category-filters" role="group" aria-label="Filter kategori kampanye">
                <button class="rc-filter active" type="button" data-category-filter="all">Semua</button>
                <button class="rc-filter laut" type="button" data-category-filter="laut">Laut</button>
                <button class="rc-filter darat" type="button" data-category-filter="darat">Darat</button>
            </div>
        </div>
    </section>
    <main class="rc-container rc-grid" id="campaignListGrid">
        <?php if (count($campaigns) > 0): foreach ($campaigns as $camp):
            $persen = $camp['target_dana'] > 0 ? round(($camp['dana_terkumpul'] / $camp['target_dana']) * 100, 1) : 0;
            $persen = min(100, $persen);
            $category = get_campaign_category($camp);
        ?>
        <article class="rc-card" data-campaign-category="<?= $category ?>">
            <?php if (!empty($camp['gambar'])): ?>
                <img src="<?= htmlspecialchars($camp['gambar']) ?>" alt="<?= htmlspecialchars($camp['judul']) ?>">
            <?php else: ?>
                <div style="height:210px;background:linear-gradient(135deg,#e0f2fe,#dcfce7)"></div>
            <?php endif; ?>
            <div class="rc-body">
                <span class="rc-badge <?= $category === 'darat' ? 'darat' : 'laut' ?>"><?= ucfirst($category) ?> • Kampanye Terverifikasi</span>
                <h2><?= htmlspecialchars($camp['judul']) ?></h2>
                <p class="rc-meta">Lokasi: <?= htmlspecialchars($camp['lokasi']) ?></p>
                <p class="rc-meta">oleh <?= htmlspecialchars($camp['nama_penggalang'] ?: 'Penggalang') ?></p>
                <div class="rc-progress">
                    <span style="width:<?= $persen ?>%"></span>
                </div>
                <div class="rc-card-meta">
                    <span><strong><?= format_rupiah($camp['dana_terkumpul']) ?></strong> terkumpul</span>
                    <span><?= $persen ?>%</span>
                </div>
                <div class="rc-card-meta">
                    <span>Target <?= format_rupiah($camp['target_dana']) ?></span>
                    <span><?= date('d M Y', strtotime($camp['tgl_selesai'])) ?></span>
                </div>
                <div class="rc-actions">
                    <a class="rc-btn secondary" href="kampanye-detail.php?id=<?= (int)$camp['id'] ?>">Lihat Detail</a>
                    <a class="rc-btn <?= $category === 'darat' ? 'green' : '' ?>" href="donasi.php?campaign_id=<?= (int)$camp['id'] ?>">Donasi</a>
                </div>
            </div>
        </article>
        <?php endforeach; else: ?>
            <div class="rc-empty">Belum ada kampanye aktif yang memenuhi syarat.</div>
        <?php endif; ?>
    </main>

    <script>
    document.querySelectorAll('.rc-category-filters [data-category-filter]').forEach(function(button) {
        button.addEventListener('click', function() {
            document.querySelectorAll('.rc-category-filters [data-category-filter]').forEach(function(item) { item.classList.remove('active'); });
            button.classList.add('active');
            const category = button.dataset.categoryFilter;
            activeCampaignCategory = category;
            applyCampaignSearchAndFilter();
        });
    });

    const campaignCards = document.querySelectorAll('#campaignListGrid [data-campaign-category]');
    const campaignSearchInput = document.getElementById('campaignSearchInput');
    let activeCampaignCategory = 'all';

    function applyCampaignSearchAndFilter() {
        const query = campaignSearchInput.value.trim().toLowerCase();
        let visible = 0;

        campaignCards.forEach(function(card) {
            const categoryMatch = activeCampaignCategory === 'all' || card.dataset.campaignCategory === activeCampaignCategory;
            const searchMatch = !query || card.innerText.toLowerCase().includes(query);
            const show = categoryMatch && searchMatch;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const emptyElement = document.querySelector('#campaignListGrid .rc-empty');
        if (visible === 0 && campaignCards.length > 0) {
            if (!emptyElement) {
                const message = document.createElement('div');
                message.className = 'rc-empty';
                message.id = 'campaignSearchEmpty';
                message.textContent = 'Tidak ada kampanye yang cocok dengan pencarian.';
                document.getElementById('campaignListGrid').appendChild(message);
            } else {
                emptyElement.style.display = '';
            }
        } else if (emptyElement) {
            emptyElement.style.display = 'none';
        }
    }

    campaignSearchInput.addEventListener('input', applyCampaignSearchAndFilter);

    document.getElementById('campaignSearchForm').addEventListener('submit', function(event) {
        event.preventDefault();
        applyCampaignSearchAndFilter();
    });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
