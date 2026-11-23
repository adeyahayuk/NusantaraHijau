<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);
$style_version = file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time();
$navbar_style_version = file_exists(__DIR__ . '/../assets/css/public-navbar.css') ? filemtime(__DIR__ . '/../assets/css/public-navbar.css') : time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>NusantaraHijau</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $style_version ?>">
  <link rel="stylesheet" href="assets/css/public-navbar.css?v=<?= $navbar_style_version ?>">
  <link rel="stylesheet" href="assets/css/footer.css">
  <link rel="stylesheet" href="assets/css/print.css" media="print">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="<?= trim((in_array($current_page, ['donasi.php', 'pembayaran.php', 'berhasil.php'], true) ? 'public-payment-flow ' : '') . ($body_class ?? '')) ?>">

<?php require __DIR__ . '/public-navbar.php'; ?>
