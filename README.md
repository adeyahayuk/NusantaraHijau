# NUSANTARAHIJAU (SDG 14 & SDG 15 CONSERVATION ESCROW PLATFORM)

> **Responsi Praktikum Pemrograman Web - Kelompok 3**
> Dibuat murni dengan **HTML, CSS, JavaScript, PHP Native & MySQL XAMPP (Zero Framework & Tanpa Bahasa Tambahan)**.

---

## 📋 DAFTAR MODUL LENGKAP YANG DIIMPLEMENTASIKAN

Sesuai referensi visual PDF (Halaman 1 sampai 8) dan spesifikasi responsi:

### 1. MODUL 6: Pembayaran Donasi (Publik, Tanpa Login)
- **Halaman Form Donasi (`index.php`)** [PDF Halaman 1]:
  - Menjadi halaman utama web (*default landing page*).
  - Ringkasan kampanye: foto, progres dana, persentase terkumpul, jumlah donatur, sisa hari.
  - Pilihan nominal donasi cepat mulai Rp 1.000 dan nominal bebas.
  - Opsi *"Donasi sebagai Anonim"* (menyamarkan nama menjadi "Hamba Allah / Anonim" di publik).
  - Form Nama Lengkap Donatur, Email aktif, dan Pesan Doa Kebaikan dengan penghitung karakter (max 200).
  - Pilihan metode pembayaran: QRIS Instan, Virtual Account Bank (BCA, Mandiri, BRI, BNI), dan E-Wallet Deeplink.
  - Tombol *"Lanjut ke Pembayaran"*.
- **Halaman Pembayaran & Simulasi (`pembayaran.php`)** [PDF Halaman 2]:
  - Kode donasi unik (contoh: `#DON-2024-88419`), nominal, dan batas waktu bayar dengan hitung mundur (*countdown timer* 24 jam realtime).
  - QRIS dengan kode QR dinamis & NMID, Nomor Virtual Account simulasi dengan tombol salin (copy), dan E-Wallet apps.
  - Kotak simulasi cepat: tombol *"✓ Simulasi Bayar Sekarang (Otomatis Berhasil)"*.
  - Status otomatis berubah dari menunggu menjadi lunas secara instan.
- **Halaman Pembayaran Berhasil (`berhasil.php`)** [PDF Halaman 3]:
  - Pesan sukses verifikasi escrow, ringkasan transaksi, stempel *LUNAS / VERIFIED*, kode hash SHA-256 audit, ID verifikasi escrow.
  - Tombol *"Unduh Invoice PDF (Struk Resmi)"* dan *"Bagikan Bukti Donasi (Share)"*.
- **Struk / Invoice Cetak (`invoice.php`)**:
  - Halaman invoice tanda terima resmi siap cetak / PDF dengan logo, stempel resmi, dan lisensi Kemensos RI.

---

### 2. MODUL 8: Dashboard Manajemen & Pengawasan Admin
- **Ringkasan / Dashboard Utama (`admin/index.php`)** [PDF Halaman 4]:
  - 4 Kartu statistik: Total Dana Masuk, Dana Tersalurkan, Saldo Tertahan (Escrow), dan Kampanye Aktif.
  - Grafik batang komparasi dana masuk vs penyaluran realisasi (Mei - Okt 2024) menggunakan SVG murni.
  - Grafik donat rasio kategori SDGs (SDG 14: 64% vs SDG 15: 36%) dan distribusi siklus kampanye.
  - Ringkasan satu pengajuan kampanye terbaru yang masih menunggu verifikasi.
- **Verifikasi Kampanye (`admin/verifikasi.php`)** [PDF Halaman 5]:
  - Antrean proposal kampanye baru, detail dokumen wajib (Proposal Teknis 4.2 MB, RAB 3 Termin, Rekom BKKPN).
  - Aksi: Setujui Kampanye, Tolak Pengajuan, dan Minta Revisi dengan catatan modal.
- **Daftar Donasi (`admin/donasi.php`)**:
  - Tabel transaksi donasi publik lengkap dengan filter status dan metode, pencarian instan, dan tautan ke struk donatur.
- **Buku Kas per Kampanye (`admin/buku-kas.php`)**:
  - Dropdown pilih kampanye, kalkulasi kas masuk, penyaluran, dan sisa saldo escrow. Peringatan otomatis jika pengajuan termin melebihi saldo.
- **Review Rencana Penyaluran (`admin/review-rencana.php`)**:
  - Tinjau RAB dari kontributor (item, estimasi biaya, prioritas, vendor, tanggal). Aksi: Setujui atau Minta Revisi.
- **Realisasi Penyaluran (`admin/realisasi.php`)**:
  - Form pencairan per termin dengan validasi otomatis tidak boleh melebihi saldo escrow.
- **Perpanjangan Kampanye (`admin/perpanjangan.php`)**:
  - Persetujuan atau penolakan penambahan waktu penggalangan.
- **Kalender Terpadu Penyaluran (`admin/kalender.php`)**:
  - Tampilan kalender bulanan jadwal penyaluran termin dan kegiatan relawan.
- **Kelola Data Master & CMS (`admin/kelola-data.php`)**:
  - 7 Tab: Contributor, Artikel Edukasi, Pusat FAQ, Pesan Bantuan, Kategori SDGs, Laporan Publik, dan Pantau Relawan.
- **Pengaturan Pembayaran (`admin/pengaturan.php`)**:
  - Konfigurasi NMID QRIS dan nomor rekening escrow kustodi bank.
- **Log Aktivitas (`admin/log.php`)**:
  - Audit trail seluruh aksi admin, kontributor, dan donatur.

---

### 3. Contributor View
- Dashboard contributor lama sudah dihapus dan akan diganti pada tahap pengembangan berikutnya.
- Data organisasi, mitra, dan pengaju kampanye tetap disimpan untuk kebutuhan pengelolaan admin.

---

## 🚀 CARA MENJALANKAN DI XAMPP

1. **Letakkan Folder Project di XAMPP**:
   Pindahkan folder `Responsi_PemWeb` ini ke direktori `htdocs` XAMPP Anda:
   ```
   C:\xampp\htdocs\Responsi_PemWeb\
   ```
2. **Nyalakan XAMPP**:
   - Buka **XAMPP Control Panel**.
   - Klik **Start** pada modul **Apache** dan **MySQL**.
3. **Impor Database**:
   - Buka browser dan buka `http://localhost/phpmyadmin/`.
   - Buat database baru bernama `nusantara_hijau` atau cukup impor file `database.sql` yang ada di root project ini.
   - *(Fitur Otomatis)*: Kode kami dilengkapi *auto-installer* di `config/database.php` yang akan otomatis membuat database dan mengimpor tabel jika MySQL berjalan!
4. **Buka Aplikasi di Browser**:
   - **Halaman Utama / Modul 6 (Donasi Publik)**: `http://localhost/Responsi_PemWeb/`
   - **Dashboard Admin (Modul 8)**: `http://localhost/Responsi_PemWeb/admin/`

---

## 🎨 PALET WARNA & SPESIFIKASI VISUAL (SESUAI PDF)
- **Primary Teal/Deep Ocean**: `#026a86` / `#085464`
- **Emerald Green Konservasi**: `#10b981` / `#059669` / `#16a34a`
- **Sky Blue SDGs**: `#0284c7` / `#e0f2fe`
- **Light Slate Background**: `#f8fafc`
- **Zero Framework**: Tidak ada dependensi bootstrap, tailwind, react, vue, atau jquery. Semua UI dibangun dengan Pure CSS dan interaktivitas Pure Vanilla JS!
