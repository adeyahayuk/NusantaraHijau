<?php
// ========================================================
// ARTIKEL EDUKASI (DEMO SEDERHANA TANPA CSS & JS)
// Menampilkan daftar artikel edukasi menggunakan table
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$articles = $pdo->query("SELECT a.*, adm.nama AS author_name
                         FROM articles a
                         LEFT JOIN admins adm ON a.admin_id = adm.id
                         WHERE a.status = 'terbit'
                         ORDER BY a.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Artikel Edukasi - NusantaraHijau</title>
    <link rel="stylesheet" href="assets/css/public-navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/demo-pages.css">
    <link rel="stylesheet" href="assets/css/location-hero-overrides.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="demo-page education-hero-page">

    <?php $navbar_demo = true; require __DIR__ . '/includes/public-navbar.php'; ?>

<section class="demo-hero">
  <div class="demo-container" style="padding-top:48px;padding-bottom:48px">
    <h1>Artikle Edukasi &amp; Literasi <span>Kelestarian Nusantara</span></h1>
    <p class="demo-lead">Pahami pemulihan ekosistem laut dan daratan tropis sebagai fondasi ketahanan pangan, mitigasi iklim, serta masa depan Indonesia.</p>
    <form class="demo-search" onsubmit="event.preventDefault();filterEduArticles()">
      <input id="article-search" type="search" placeholder="Cari topik, terumbu karang, mangrove, atau satwa endemik...">
      <button class="demo-btn" type="submit">Cari Artikel</button>
    </form>
  </div>
</section>

<main class="demo-container demo-section">
  <div class="demo-filters" style="margin-bottom:22px">
    <button class="demo-tab active" type="button" onclick="setEduFilter('all',this)">Semua Artikel</button>
    <button class="demo-tab" type="button" onclick="setEduFilter('laut',this)">Laut</button>
    <button class="demo-tab" type="button" onclick="setEduFilter('darat',this)">Darat</button>
  </div>

  <div class="demo-layout">
    <div>
      <div class="demo-article-grid" id="articles-container">
      <?php if (!empty($articles)): foreach ($articles as $index => $art):
          $savedTopic = strtolower(trim((string)($art['kategori'] ?? '')));
          $text = strtolower(($art['judul'] ?? '') . ' ' . ($art['isi'] ?? ''));
          $topic = in_array($savedTopic, ['laut', 'darat'], true)
              ? $savedTopic
              : ((str_contains($text, 'laut') || str_contains($text, 'karang') || str_contains($text, 'penyu')) ? 'laut' : 'darat');
      ?>
        <article class="demo-card edu-card" data-topic="<?= $topic ?>">
          <?php if (!empty($art['gambar'])): ?><img class="demo-card-image" src="<?= htmlspecialchars($art['gambar']) ?>" alt="<?= htmlspecialchars($art['judul']) ?>"><?php else: ?><div class="demo-card-image"></div><?php endif; ?>
          <div class="demo-card-body">
            <div class="demo-muted"><?= date('d M Y', strtotime($art['created_at'])) ?> • Edukasi Konservasi</div>
            <h3><?= htmlspecialchars($art['judul']) ?></h3>
            <p class="demo-card-text"><?= htmlspecialchars(mb_strimwidth($art['isi'], 0, 220, '...')) ?></p>
            <div class="demo-card-bottom"><div class="demo-card-meta"><span>Penulis: <?= htmlspecialchars($art['author_name'] ?: 'Admin NusantaraHijau') ?></span><strong><?= strtoupper($topic) ?></strong></div></div>
          </div>
        </article>
      <?php endforeach; else: ?>
        <div class="demo-empty">Belum ada artikel edukasi yang diterbitkan.</div>
      <?php endif; ?>
      </div>
      <nav class="demo-pagination" id="article-pagination" aria-label="Navigasi halaman artikel"></nav>
    </div>
  </div>
</main>

<script>
var currentArticlePage = 1;
var articlesPerPage = 9;
var activeArticleTopic = 'all';

function getVisibleArticleCards(){
  var query = (document.getElementById('article-search').value || '').toLowerCase().trim();
  var cards = Array.from(document.querySelectorAll('.edu-card'));
  return cards.filter(function(card){
    var topicMatch = activeArticleTopic === 'all' || card.dataset.topic === activeArticleTopic;
    var searchMatch = !query || card.innerText.toLowerCase().includes(query);
    return topicMatch && searchMatch;
  });
}

function renderArticlePagination(){
  var cards = Array.from(document.querySelectorAll('.edu-card'));
  var visibleCards = getVisibleArticleCards();
  var totalPages = Math.max(1, Math.ceil(visibleCards.length / articlesPerPage));
  if (currentArticlePage > totalPages) currentArticlePage = totalPages;

  cards.forEach(function(card){ card.style.display = 'none'; });
  var firstIndex = (currentArticlePage - 1) * articlesPerPage;
  var lastIndex = firstIndex + articlesPerPage;
  visibleCards.slice(firstIndex, lastIndex).forEach(function(card){ card.style.display = 'flex'; });

  var pagination = document.getElementById('article-pagination');
  pagination.innerHTML = '';
  if (cards.length === 0) return;

  function addPageButton(label, page, type){
    var button = document.createElement('button');
    button.type = 'button';
    button.className = type === 'arrow' ? 'demo-page-arrow' : 'demo-page-number';
    button.innerHTML = label;
    button.disabled = type === 'arrow' && (page < 1 || page > totalPages);
    button.addEventListener('click', function(){
      currentArticlePage = page;
      renderArticlePagination();
      document.getElementById('articles-container').scrollIntoView({behavior:'smooth', block:'start'});
    });
    pagination.appendChild(button);
  }

  addPageButton('‹', currentArticlePage - 1, 'arrow');

  var pageNumbers = [];
  if (totalPages <= 5) {
    for (var i = 1; i <= totalPages; i++) pageNumbers.push(i);
  } else if (currentArticlePage <= 3) {
    pageNumbers = [1, 2, 3, 'ellipsis', totalPages];
  } else if (currentArticlePage >= totalPages - 2) {
    pageNumbers = [1, 'ellipsis', totalPages - 2, totalPages - 1, totalPages];
  } else {
    pageNumbers = [1, 'ellipsis', currentArticlePage, 'ellipsis', totalPages];
  }

  pageNumbers.forEach(function(item){
    if (item === 'ellipsis') {
      var dots = document.createElement('span');
      dots.className = 'demo-page-dots';
      dots.textContent = '...';
      pagination.appendChild(dots);
    } else {
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'demo-page-number' + (item === currentArticlePage ? ' active' : '');
      button.textContent = item;
      button.addEventListener('click', function(){
        currentArticlePage = item;
        renderArticlePagination();
        document.getElementById('articles-container').scrollIntoView({behavior:'smooth', block:'start'});
      });
      pagination.appendChild(button);
    }
  });

  addPageButton('›', currentArticlePage + 1, 'arrow');
}

function setEduFilter(topic,btn){
  document.querySelectorAll('.demo-tab').forEach(function(b){b.classList.remove('active')});
  btn.classList.add('active');
  activeArticleTopic = topic;
  currentArticlePage = 1;
  renderArticlePagination();
}

function filterEduArticles(){
  currentArticlePage = 1;
  renderArticlePagination();
}

document.getElementById('article-search').addEventListener('input', function(){
  currentArticlePage = 1;
  renderArticlePagination();
});

renderArticlePagination();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
