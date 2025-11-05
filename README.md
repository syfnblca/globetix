# 🚌 GLOBETIX TRAVEL BOOKING
### Rancang Bangun Sistem Pemesanan Tiket Online Berbasis Web

---

## 👨‍💻 Login Admin
- **URL:** `http://localhost/globetix/admin/login.php`  
- **Username:** `admin`  
- **Password:** `admin123`

---

## 📘 Tentang Proyek
**GlobeTix** merupakan sistem pemesanan tiket bus online berbasis web yang dikembangkan sebagai proyek akhir oleh mahasiswa **Universitas Negeri Yogyakarta**.  
Aplikasi ini menyediakan fitur pemesanan tiket, manajemen jadwal, pembayaran, refund, serta dashboard admin yang menampilkan statistik dan laporan transaksi secara real-time.

**Oleh:**
- Refi Yuni Mariska (23050530001)  
- Syafa Nabila (23050530010)  
- Jihan Khasna Ul Afifah (23050530017)  
- Ivan Noor Muchammad Nafis (23050530048)  
- Agatha Aprilia Kundiop (23050530062)  

**Departemen:** Pendidikan Teknik Elektronika dan Informatika  
**Fakultas:** Teknik  
**Universitas Negeri Yogyakarta, 2025**

---

## 🔗 Link Repository
- **Front-End / Web App:** [https://github.com/syfnblca/globetix](https://github.com/syfnblca/globetix)
- **Database API / Backend:** *(jika ada, tambahkan link di sini)*

---

## 🗂 Struktur Direktori

globetix/
├── admin/
│ ├── assets/
│ ├── includes/
│ ├── bus.php
│ ├── edit_bus.php
│ ├── tambah_bus.php
│ ├── refund.php
│ ├── pemesanan.php
│ ├── index_admin.php
│ └── login.php
│
├── PHPMailer/
├── uploads/
├── autologin.php
├── bantuan.php
├── batalkan_pemesanan.php
├── dashboard.php
├── db.php
├── detail_pemesanan.php
├── hasil_pencarian.php
├── pilih_kursi.php
├── pembayaran.php
├── profil.php
├── riwayat.php
├── ubah_password.php
└── globetix.sql
## ⚙️ Modul Sistem

### 1️⃣ Modul Autentikasi
Mengatur login, registrasi, ubah password, dan autologin pengguna.  
- File: `masuk.php`, `daftar.php`, `autologin.php`, `ubah_password.php`
- Terintegrasi dengan **PHPMailer** untuk pengiriman notifikasi email.

### 2️⃣ Modul Pemesanan
Menangani seluruh proses pemesanan tiket dari pencarian hingga pembatalan.  
- File: `dashboard.php`, `hasil_pencarian.php`, `pilih_kursi.php`, `detail_pemesanan.php`, `batalkan_pemesanan.php`

### 3️⃣ Modul Pembayaran
Mengelola unggah bukti pembayaran dan konfirmasi oleh admin.  
- File: `pembayaran.php`, `uploads/`, `admin/pemesanan.php`

### 4️⃣ Modul Admin
Menjadi pusat pengelolaan sistem: data bus, jadwal, pembayaran, refund, dan laporan.  
- File: `admin/index_admin.php`, `admin/bus.php`, `admin/pemesanan.php`, `admin/refund.php`

---

## 📄 Panduan Penggunaan Singkat

### 👥 Pengguna
1. Registrasi dan login melalui `masuk.php`.
2. Cari tiket bus berdasarkan asal, tujuan, dan tanggal.
3. Pilih kursi dan unggah bukti pembayaran.
4. Lihat status transaksi di **riwayat.php**.
5. Dapatkan bantuan di **bantuan.php**.

### 🛠 Admin
1. Login ke panel admin (`admin/login.php`).
2. Kelola jadwal bus dan data transaksi.
3. Konfirmasi pembayaran dan refund.
4. Pantau laporan keuangan dan statistik di **index_admin.php**.

---

## 🧰 Teknologi yang Digunakan
- **Frontend:** HTML, CSS, JavaScript (Bootstrap)
- **Backend:** PHP Native
- **Database:** MySQL
- **Server:** XAMPP / Apache
- **Email:** PHPMailer

---

## 📷 Tampilan Utama
- Dashboard User: pencarian tiket dan jadwal.
- Riwayat Pemesanan: daftar tiket & status transaksi.
- Profil Pengguna: ubah data & password.
- Dashboard Admin: statistik booking dan laporan refund.

---

## 🧾 Lisensi
Proyek ini dikembangkan untuk keperluan akademik di **Universitas Negeri Yogyakarta**  
dan tidak untuk tujuan komersial.

---

**📧 Kontak:**  
Jika mengalami kendala, hubungi admin melalui globetix.msg@gmail.com**.
