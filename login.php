<?php
// ========================================================
// LOGIN & REGISTER (DEMO SEDERHANA MENGGUNAKAN TABLE TANPA CSS & JS)
// Satu halaman login untuk dua tabel: admins dan users
// ========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$error_login = '';
$error_register = '';
$success_register = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // 1. PROSES LOGIN
    // ==========================================
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error_login = 'Email dan password wajib diisi!';
        } else {
            // Langkah 1: Cek tabel admins dulu
            $stmtAdmin = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
            $stmtAdmin->execute([$email]);
            $admin = $stmtAdmin->fetch();

            if ($admin && (password_verify($password, $admin['password']) || $admin['password'] === $password || hash_equals($admin['password'], $password))) {
                // Set session admin
                $_SESSION['role'] = 'admin';
                $_SESSION['account_type'] = 'admin';
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['nama'];
                $_SESSION['admin_email'] = $admin['email'];

                log_activity($pdo, 'admin', $admin['id'], 'Login Administrator', 'auth', $admin['id'], 'Login berhasil ke panel admin');

                // Redirect ke dashboard admin
                header("Location: admin/dashboard.php");
                exit;
            }

            // Langkah 2: Cek tabel users jika tidak ada / tidak cocok di admins
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmtUser->execute([$email]);
            $user = $stmtUser->fetch();

            if ($user && (password_verify($password, $user['password']) || $user['password'] === $password || hash_equals($user['password'], $password))) {
                if ($user['status'] === 'nonaktif') {
                    $error_login = 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin.';
                } else {
                    // Set session contributor
                    $_SESSION['role'] = 'contributor';
                    $_SESSION['account_type'] = 'user';
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nama'];
                    $_SESSION['user_email'] = $user['email'];

                    log_activity($pdo, 'user', $user['id'], 'Login Kontributor', 'auth', $user['id'], 'Login berhasil ke platform');

                    // Kembali ke website utama / beranda
                    header("Location: index.php");
                    exit;
                }
            }

            // Langkah 3: Jika keduanya tidak cocok
            if (empty($error_login)) {
                $error_login = 'Email atau password salah.';
            }
        }
    }

    // ==========================================
    // 2. PROSES REGISTER (HANYA UNTUK CONTRIBUTOR / USERS)
    // ==========================================
    elseif ($action === 'register') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $organisasi = trim($_POST['organisasi'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

        if (empty($nama) || empty($email) || empty($password)) {
            $error_register = 'Nama, email, dan password wajib diisi!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_register = 'Format email tidak valid!';
        } elseif (strlen($password) < 6) {
            $error_register = 'Password minimal 6 karakter!';
        } elseif ($password !== $konfirmasi_password) {
            $error_register = 'Konfirmasi password tidak cocok!';
        } else {
            // Keamanan: Cek apakah email sudah terdaftar di tabel admins ATAU users
            $stmtCheckAdmin = $pdo->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
            $stmtCheckAdmin->execute([$email]);
            $adminExists = $stmtCheckAdmin->fetch();

            $stmtCheckUser = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmtCheckUser->execute([$email]);
            $userExists = $stmtCheckUser->fetch();

            if ($adminExists || $userExists) {
                $error_register = 'Email tersebut sudah terdaftar! Gunakan email lain.';
            } else {
                // Simpan kontributor baru
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmtInsert = $pdo->prepare("INSERT INTO users (nama, email, password, no_hp, organisasi, status, created_at) VALUES (?, ?, ?, ?, ?, 'aktif', NOW())");
                $stmtInsert->execute([$nama, $email, $hashedPassword, $no_hp, $organisasi]);
                $newId = (int)$pdo->lastInsertId();

                log_activity($pdo, 'user', $newId, 'Registrasi Akun Kontributor', 'users', $newId, "Pendaftaran akun baru {$nama}");

                $success_register = 'Pendaftaran berhasil! Silakan login menggunakan email dan password Anda.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login &amp; Register - Demo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/reference-auth.css">
</head>
<body>
<div id="legacy-auth" style="display:none">

    <!-- Navigasi Percobaan -->
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <tr>
            <td>
                <b>NusantaraHijau (Demo)</b> |
                <a href="index.php">Beranda</a> |
                <a href="daftar-kampanye.php">Kampanye</a> |
                <a href="aksi-relawan.php">Aksi Relawan</a> |
                <a href="artikel-edukasi.php">Artikel Edukasi</a> |
                <a href="faq-bantuan.php">FAQ &amp; Pusat Bantuan</a> |
                <a href="login.php"><b>Login / Register</b></a>
            </td>
        </tr>
    </table>

    <br>
    <h2>Halaman Masuk (Login) &amp; Pendaftaran (Register)</h2>
    <p>Tampilan demo tanpa CSS dan JavaScript menggunakan struktur table agar rapi.</p>

    <!-- TABEL DUA KOLOM: LOGIN DAN REGISTER -->
    <table border="1" cellpadding="12" cellspacing="0" width="100%">
        <tr bgcolor="#eeeeee">
            <th width="50%">FORM MASUK (LOGIN)</th>
            <th width="50%">FORM DAFTAR KONTRIBUTOR BARU (REGISTER)</th>
        </tr>
        <tr valign="top">
            <!-- KOLOM KIRI: LOGIN -->
            <td>
                <h3>Masuk ke Akun Anda</h3>
                <p>Gunakan email dan password Anda (berlaku untuk Admin dan Kontributor).</p>

                <?php if ($error_login): ?>
                    <p><b><font color="red"><?= htmlspecialchars($error_login) ?></font></b></p>
                <?php endif; ?>

                <?php if ($success_register): ?>
                    <p><b><font color="green"><?= htmlspecialchars($success_register) ?></font></b></p>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="login">
                    <table border="1" cellpadding="6" cellspacing="0" width="100%">
                        <tr>
                            <td width="35%"><b>Alamat Email</b></td>
                            <td><input type="email" name="email" style="width: 95%;" required placeholder="admin@nusantarahijau.org atau email user"></td>
                        </tr>
                        <tr>
                            <td><b>Kata Sandi</b></td>
                            <td><input type="password" name="password" style="width: 95%;" required placeholder="Masukkan kata sandi"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <button type="submit" style="padding: 8px 18px; cursor: pointer;">Masuk Sekarang</button>
                            </td>
                        </tr>
                    </table>
                </form>

                <br>
                <table border="1" cellpadding="6" cellspacing="0" width="100%" bgcolor="#f9f9f9">
                    <tr>
                        <td>
                            <b>Informasi Akun Demo Bawaan:</b><br>
                            - <b>Admin:</b> Email: <code>admin@nusantarahijau.org</code> | Password: <code>admin123</code><br>
                            - <b>Kontributor:</b> Email: <code>budi@gmail.com</code> | Password: <code>user123</code>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- KOLOM KANAN: REGISTER KONTRIBUTOR -->
            <td>
                <h3>Daftar Sebagai Penggalang Dana / Kontributor</h3>
                <p>Hanya untuk pendaftaran akun pengguna/kontributor baru. Akun admin dibuat langsung oleh sistem.</p>

                <?php if ($error_register): ?>
                    <p><b><font color="red"><?= htmlspecialchars($error_register) ?></font></b></p>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="register">
                    <table border="1" cellpadding="6" cellspacing="0" width="100%">
                        <tr>
                            <td width="35%"><b>Nama Lengkap *</b></td>
                            <td><input type="text" name="nama" style="width: 95%;" required placeholder="Nama lengkap Anda"></td>
                        </tr>
                        <tr>
                            <td><b>Alamat Email *</b></td>
                            <td><input type="email" name="email" style="width: 95%;" required placeholder="email@contoh.com"></td>
                        </tr>
                        <tr>
                            <td><b>Nomor WhatsApp / HP</b></td>
                            <td><input type="text" name="no_hp" style="width: 95%;" placeholder="081234567890"></td>
                        </tr>
                        <tr>
                            <td><b>Nama Organisasi / Komunitas</b></td>
                            <td><input type="text" name="organisasi" style="width: 95%;" placeholder="Contoh: Komunitas Peduli Laut"></td>
                        </tr>
                        <tr>
                            <td><b>Kata Sandi (Min. 6 Karakter) *</b></td>
                            <td><input type="password" name="password" style="width: 95%;" required placeholder="Minimal 6 karakter"></td>
                        </tr>
                        <tr>
                            <td><b>Konfirmasi Kata Sandi *</b></td>
                            <td><input type="password" name="konfirmasi_password" style="width: 95%;" required placeholder="Ulangi kata sandi"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <button type="submit" style="padding: 8px 18px; cursor: pointer;">Daftar Akun Baru</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
    </table>
</div>

<div class="auth-wrapper">
  <aside class="auth-left">
    <div>
      <div class="brand-row"><div class="brand-icon"><span class="brand-logo-icon" aria-hidden="true">
          <img src="assets/img/nusantarahijau-logo-dark.svg" alt="" width="23" height="23">
        </span></div><div><div class="brand-name">Nusantara<span>Hijau</span></div><div class="brand-tagline">Platform Penggalangan #1 Indonesia</div></div></div>
      <div class="sdg-badge-group"><span class="sdg-pill">Ekosistem Laut</span><span class="sdg-pill">Ekosistem Darat</span></div>
      <div class="visual-card"><img src="https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&amp;fit=crop&amp;w=800&amp;q=80" alt="Konservasi Laut dan Darat Indonesia"><div class="visual-overlay"><div><span class="sat-tag">Aksi Memulihkan Alam</span><span class="sat-loc"></span></div><p class="visual-caption">100% donasi langsung dialokasikan untuk pemulihan alam &amp; reboisasi satwa liar.</p></div></div>
      <h2 class="hero-headline">Bersama Wujudkan Aksi Nyata untuk <span>Kelestarian Laut &amp; Darat</span> Indonesia.</h2>
      <p class="hero-desc">Jadilah bagian dari lebih dari <strong>45.000+ pejuang alam</strong>, relawan lapangan, dan donatur terpercaya dalam merawat bumi Nusantara.</p>
    </div>
    <div><div class="stats-strip"><div><div class="stat-value">Rp 4.8M+</div><div class="stat-label">Donasi Disalurkan</div></div><div><div class="stat-value white">128.450+</div><div class="stat-label">Bibit Pohon &amp; Karang</div></div><div><div class="stat-value sky">100%</div><div class="stat-label">Tersalurkan ke Alam</div></div></div><div class="left-footer"><span>© 2026 NusantaraHijau Yayasan Konservasi</span></div></div>
  </aside>
  <main class="auth-right">
    <div class="top-bar"><a href="index.php" class="back-link">&larr; Kembali ke Beranda</a><span>Butuh bantuan? <a href="faq-bantuan.php" class="help-link">Pusat Resolusi</a></span></div>
    <div class="form-box">
      <h1 id="form-title" class="form-title">Selamat Datang Kembali</h1><p id="form-subtitle" class="form-subtitle">Masuk ke akun Anda untuk memantau jejak donasi dan aksi relawan.</p>
      <?php if ($error_login): ?><div class="alert-box alert-error"><?= htmlspecialchars($error_login) ?></div><?php endif; ?><?php if ($error_register): ?><div class="alert-box alert-error"><?= htmlspecialchars($error_register) ?></div><?php endif; ?><?php if ($success_register): ?><div class="alert-box alert-success"><?= htmlspecialchars($success_register) ?></div><?php endif; ?>
      <div class="tab-switcher"><button type="button" id="tab-login" class="tab-btn active">Masuk (Login)</button><button type="button" id="tab-register" class="tab-btn">Daftar (Register)</button></div>
      <form id="form-login" method="POST" action="login.php"><input type="hidden" name="action" value="login"><div class="form-group"><label class="form-label" for="login-email">Email atau Username</label><input class="form-input no-toggle" id="login-email" type="email" name="email" required placeholder="nama@email.com"></div><div class="form-group"><label class="form-label" for="login-password">Password</label><div class="input-wrap"><input class="form-input" id="login-password" type="password" name="password" required placeholder="Masukkan kata sandi akun"><button type="button" class="toggle-pass-btn" onclick="togglePassword('login-password',this)">Lihat</button></div></div><div class="form-meta"><label class="remember-label"><input type="checkbox"> Ingat Saya</label><a class="forgot-link" href="faq-bantuan.php">Lupa Password?</a></div><button type="submit" class="btn-primary">Masuk Sekarang &rarr;</button><p class="switch-text">Belum punya akun? <button type="button" class="switch-link" onclick="showRegister()">Daftar di sini</button></p></form>
      <form id="form-register" class="hidden" method="POST" action="login.php"><input type="hidden" name="action" value="register"><div class="form-group"><label class="form-label" for="nama">Nama Lengkap</label><input class="form-input no-toggle" id="nama" type="text" name="nama" required placeholder="Contoh: Budi Santoso"></div><div class="form-row-2"><div class="form-group"><label class="form-label" for="no_hp">Nomor WhatsApp</label><input class="form-input no-toggle" id="no_hp" type="text" name="no_hp" placeholder="081234567890"></div><div class="form-group"><label class="form-label" for="email">Email</label><input class="form-input no-toggle" id="email" type="email" name="email" required placeholder="budi@email.com"></div></div><div class="form-group"><label class="form-label" for="organisasi">Organisasi / Komunitas</label><input class="form-input no-toggle" id="organisasi" type="text" name="organisasi" placeholder="Nama organisasi atau komunitas"></div><div class="form-group"><label class="form-label" for="reg-password">Password</label><div class="input-wrap"><input class="form-input" id="reg-password" type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter"><button type="button" class="toggle-pass-btn" onclick="togglePassword('reg-password',this)">Lihat</button></div></div><div class="form-group"><label class="form-label" for="confirm_password">Konfirmasi Password</label><input class="form-input no-toggle" id="confirm_password" type="password" name="konfirmasi_password" required minlength="6" placeholder="Ulangi password"></div><button type="submit" class="btn-primary green">Daftar Akun Baru</button><p class="switch-text">Sudah punya akun? <button type="button" class="switch-link" onclick="showLogin()">Masuk di sini</button></p></form>
    </div>
    <div class="right-footer"><span>© 2026 NusantaraHijau. Hak Cipta Dilindungi.</span><div class="right-footer-links"><a href="#">Privasi</a><a href="#">Syarat &amp; Ketentuan</a><a href="faq-bantuan.php">FAQ</a></div></div>
  </main>
</div>
<script>
function showLogin(){document.getElementById('form-login').classList.remove('hidden');document.getElementById('form-register').classList.add('hidden');document.getElementById('tab-login').classList.add('active');document.getElementById('tab-register').classList.remove('active');document.getElementById('form-title').textContent='Selamat Datang Kembali';document.getElementById('form-subtitle').textContent='Masuk ke akun Anda untuk memantau jejak donasi dan aksi relawan.'}function showRegister(){document.getElementById('form-register').classList.remove('hidden');document.getElementById('form-login').classList.add('hidden');document.getElementById('tab-register').classList.add('active');document.getElementById('tab-login').classList.remove('active');document.getElementById('form-title').textContent='Buat Akun Baru';document.getElementById('form-subtitle').textContent='Daftar sekarang untuk mulai berdonasi dan mengikuti aksi relawan.'}function togglePassword(id,btn){var input=document.getElementById(id);if(input.type==='password'){input.type='text';btn.textContent='Sembunyikan'}else{input.type='password';btn.textContent='Lihat'}}document.getElementById('tab-login').addEventListener('click',showLogin);document.getElementById('tab-register').addEventListener('click',showRegister);
</script>
</body>
</html>
