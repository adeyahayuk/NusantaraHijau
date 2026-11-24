# 🌿 NusantaraHijau

**Platform donasi dan aksi peduli ekosistem laut dan darat Indonesia.**

NusantaraHijau adalah aplikasi web donasi yang mempertemukan masyarakat yang ingin membantu dengan penggalang dana yang menangani masalah lingkungan, seperti kerusakan terumbu karang, pencemaran laut, deforestasi, hingga bencana alam. Proyek ini dibuat sebagai **Responsi 2 (Proyek Akhir) Praktikum Pemrograman Web I**, Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman.

> ⚠️ Website ini adalah **simulasi** untuk keperluan pembelajaran. Proses pembayaran donasi hanya demo dan tidak menggunakan payment gateway sungguhan.

---

## 📌 Latar Belakang dan Tujuan

Indonesia adalah negara kepulauan dengan kekayaan ekosistem laut dan darat yang sangat besar, namun terus menghadapi ancaman seperti pencemaran, kerusakan habitat, dan bencana alam. Banyak orang ingin membantu tetapi kesulitan menemukan saluran donasi yang terpercaya dan transparan.

**Tujuan proyek:**
- Menyediakan wadah donasi yang terverifikasi untuk isu ekosistem laut dan darat.
- Meningkatkan kesadaran masyarakat melalui konten edukasi dan membuka peluang keterlibatan sebagai relawan.

## 🌍 Keterkaitan dengan SDGs

| SDGs | Tujuan | Kaitan dengan proyek |
|------|--------|----------------------|
| **SDG 14** | Ekosistem Lautan | Kampanye dan edukasi untuk pelestarian laut, terumbu karang, dan pesisir |
| **SDG 15** | Ekosistem Daratan | Kampanye dan edukasi untuk pelestarian hutan, satwa, dan lahan |

## 👥 Peran Pengguna

| Peran | Hak akses |
|-------|-----------|
| **Pengunjung / Donatur** | Melihat kampanye dan edukasi, berdonasi tanpa login, mendaftar sebagai relawan |
| **Contributor (Penggalang Dana)** | Register dan login, mengajukan penggalangan dana, melihat kampanye miliknya, mengelola akun |
| **Admin** | Memverifikasi pengajuan kampanye, mengelola operasional platform, memantau log aktivitas |

## 🧩 Daftar Modul

1. **Landing Page (Beranda)**: halaman utama berisi ringkasan platform, kampanye unggulan, dan ajakan berdonasi.
2. **Kampanye**: daftar dan detail kampanye penggalangan dana beserta progres dana terkumpul.
3. **Artikel Edukasi**: artikel edukasi seputar ekosistem laut dan darat (SDGs 14 dan 15) yang dikelola pihak NusantaraHijau.
4. **Aksi Relawan**: formulir pendaftaran relawan, lalu diarahkan ke grup koordinasi.
5. **Pusat Bantuan dan FAQ**: pertanyaan umum dan bantuan penggunaan platform.
6. **Pembayaran Donasi**: alur donasi simulasi dengan instruksi pembayaran/QR, status berhasil, dan unduh invoice.
7. **Autentikasi**: register dan login untuk contributor dan admin dalam satu halaman login.
8. **Dashboard Admin**: verifikasi kampanye, pengelolaan operasional (kampanye, donasi, relawan, artikel), FAQ, pusat bantuan, dan log aktivitas.
9. **Profil dan Galang Dana**: menu profil berisi form Galang Dana, Kampanye Saya, dan Pengaturan Akun.

## 🛠️ Teknologi yang Digunakan

- **HTML5**, **CSS3**, **JavaScript**
- **PHP Native** (tanpa framework)
- **MySQL** sebagai basis data
- **XAMPP** sebagai server lokal
- **Git dan GitHub** untuk kolaborasi

## ⚙️ Panduan Instalasi dan Konfigurasi

### Prasyarat
- [XAMPP](https://www.apachefriends.org/) (Apache, PHP 8.2.12, dan MySQL)
- Git
- Browser modern

### Langkah Instalasi

1. **Clone repository** ke folder `htdocs` XAMPP:
   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/adeyahayuk/NusantaraHijau.git
   ```
2. **Jalankan XAMPP** lalu aktifkan modul **Apache** dan **MySQL**.
3. **Buat database**: buka `http://localhost/phpmyadmin`, buat database baru bernama `nusantara_hijau`.
4. **Impor database**: pilih database tersebut, menu **Import**, lalu pilih berkas `database.sql`.
5. **Atur koneksi database** pada berkas konfigurasi (sesuaikan nama berkas dengan proyek Anda, misalnya `config/koneksi.php`):
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db   = "nusantara_hijau";
   ```

## ▶️ Panduan Menjalankan Aplikasi

1. Pastikan Apache dan MySQL pada XAMPP berstatus **Running**.
2. Buka browser dan akses:
   ```
   http://localhost/nusantarahijau
   ```

## 🗂️ Struktur Folder

> Sesuaikan dengan struktur proyek Anda.

```
nusantarahijau/
├── assets/          # CSS, JavaScript, gambar
├── config/          # Konfigurasi koneksi database
├── admin/           # Halaman dashboard admin
├── includes/        # Komponen bersama (navbar, footer)
└── index.php        # Halaman beranda
└── ....
```

## 👨‍💻 Tim Pengembang

| Nama | NIM | Modul yang Dikerjakan |
|------|-----|-----------------------|
| `Muhammad Azhar Qaidusshidqi` | `H1D025086` | `2, 5, 7` |
| `Ade Yahya Hendriawan` | `H1D025090` | `6, 8, 9` |
| `Panji Dwi Putra Herdyanto` | `H1D025164` | `1, 3, 4` |

## 🔗 Tautan Proyek

- 🎥 Video Presentasi: `https://youtu.be/zducYwF7Rso`
- 🖼️ Project Showcase: `https://www.instagram.com/p/DgGwQgikfNCVFGkeHRNY1zwNx3AJQU51lB58z00/?evlk=c3d4anRtNHhtZG4y`

---

Dibuat untuk Praktikum Pemrograman Web I, Jurusan Informatika, Universitas Jenderal Soedirman, 2026.
