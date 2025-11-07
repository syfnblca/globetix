# 🌍 GlobeTix  
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-orange?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-Frontend-purple?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-Academic-lightgrey)
![Status](https://img.shields.io/badge/Status-Completed-success)

---

## 🚌 Sistem Pemesanan Tiket Bus Online – GlobeTix

**GlobeTix** adalah aplikasi web berbasis PHP yang dikembangkan untuk memudahkan pengguna dalam melakukan pemesanan tiket bus secara online.  
Melalui sistem ini, pengguna dapat mencari jadwal bus, memilih kursi, mengunggah bukti pembayaran, serta melacak status pemesanan secara real-time.  
Admin memiliki kontrol penuh untuk mengelola data bus, jadwal, transaksi, refund, serta laporan keuangan secara efisien.
---

## 👥 Tim Pengembang
| Nama | NIM |
|------|-----|
| Refi Yuni Mariska | 23050530001 |
| Syafa Nabila | 23050530010 |
| Jihan Khasna Ul Afifah | 23050530017 |
| Ivan Noor Muchammad Nafis | 23050530048 |
| Agatha Aprilia Kundiop | 23050530062 |

📍 *Program Studi Pendidikan Teknik Informatika*  
Fakultas Teknik – Universitas Negeri Yogyakarta (2025)

---

## ⚙️ Modul Utama

### 🔐 Autentikasi Pengguna
- Fitur registrasi, login, auto-login, dan ubah password  
- Menggunakan **PHPMailer** untuk verifikasi email dan notifikasi pengguna

### 🎫 Pemesanan Tiket
- Alur: *Cari bus → Pilih kursi → Unggah bukti pembayaran → Lihat status pemesanan*

### 💳 Pembayaran
- Pengguna dapat mengunggah bukti transfer dan menunggu konfirmasi admin

### 🧑‍💼 Panel Admin
- Mengelola data bus, jadwal, transaksi, refund, serta laporan penjualan

---

## 🖥️ Teknologi yang Digunakan
- **Frontend:** HTML, CSS, JavaScript, Bootstrap  
- **Backend:** PHP Native  
- **Database:** MySQL  
- **Server:** XAMPP / Apache  
- **Email Service:** PHPMailer  

---
## 🚀 Cara Menjalankan Proyek

### 1️⃣ Clone Repository
```bash
git clone https://github.com/syfnblca/globetix.git
2️⃣ Import Database

Buka phpMyAdmin

Buat database baru, misalnya globetix

Import file globetix.sql yang ada di folder utama proyek

3️⃣ Konfigurasi Database

Buka file db.php

Sesuaikan dengan kredensial MySQL kamu:

<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "globetix";
?>

4️⃣ Jalankan di Server Lokal

Pindahkan folder proyek ke dalam folder htdocs (jika menggunakan XAMPP)

Jalankan Apache dan MySQL melalui XAMPP Control Panel

5️⃣ Akses di Browser

User: http://localhost/globetix/landing.php

Admin: http://localhost/globetix/admin/login.php

6️⃣ Login Admin (Default)
username: admin
password: admin123

✨ Fitur Utama

Registrasi dan login pengguna

Autologin otomatis

Pencarian jadwal bus berdasarkan rute dan tanggal

Pemilihan kursi interaktif

Upload bukti pembayaran

Riwayat pemesanan pengguna

Pengelolaan bus, jadwal, transaksi, dan refund oleh admin

Dashboard admin dengan laporan transaksi

🧩 Pengembangan Selanjutnya

Integrasi dengan API pembayaran online

Fitur notifikasi real-time (email & SMS)

Peningkatan desain UI/UX

Penambahan filter pencarian lanjutan

Dukungan multi-kota dan multi-operator

📄 Lisensi

Proyek ini dibuat untuk keperluan akademik di Universitas Negeri Yogyakarta.
Dilarang digunakan untuk kepentingan komersial tanpa izin dari pengembang.

📬 Kontak

Jika ada pertanyaan, bug, atau ingin berkontribusi, silakan hubungi:
📧 globetix.msg@gmail.com

🌐 https://github.com/syfnblca/globetix

⭐ Dukung proyek ini dengan memberi bintang (⭐) di repositori jika kamu merasa GlobeTix bermanfaat!
