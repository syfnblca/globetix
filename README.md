<div align="center">

# 🌍 **GlobeTix**

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-orange?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-Frontend-purple?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-Academic-lightgrey)
![Status](https://img.shields.io/badge/Status-Completed-success)

---

### 🚌 Sistem Pemesanan Tiket Bus Online – GlobeTix

**GlobeTix** adalah aplikasi web berbasis PHP yang dikembangkan untuk memudahkan pengguna dalam melakukan pemesanan tiket bus secara online.  
Melalui sistem ini, pengguna dapat mencari jadwal bus, memilih kursi, mengunggah bukti pembayaran, serta melacak status pemesanan secara real-time.  
Admin memiliki kontrol penuh untuk mengelola data bus, jadwal, transaksi, refund, serta laporan keuangan secara efisien.

</div>

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

## 🖥️ Teknologi yang Digunakan
- **Frontend:** HTML, CSS, JavaScript, Bootstrap  
- **Backend:** PHP Native  
- **Database:** MySQL  
- **Server:** XAMPP / Apache  
- **Email Service:** PHPMailer  

---

## 👨‍💻 Login Admin
- **URL:** [`http://localhost/globetix/admin/login.php`](http://localhost/globetix/admin/login.php)  
- **Username:** `admin`  
- **Password:** `admin123`

---

## ⚙️ Modul Sistem

### 1️⃣ Modul Autentikasi
Mengatur login, registrasi, ubah password, dan autologin pengguna.  
**File:** `masuk.php`, `daftar.php`, `autologin.php`, `ubah_password.php`  
📧 Terintegrasi dengan **PHPMailer** untuk pengiriman notifikasi email.

### 2️⃣ Modul Pemesanan
Menangani seluruh proses pemesanan tiket dari pencarian hingga pembatalan.  
**File:** `dashboard.php`, `hasil_pencarian.php`, `pilih_kursi.php`, `detail_pemesanan.php`, `batalkan_pemesanan.php`

### 3️⃣ Modul Pembayaran
Mengelola unggah bukti pembayaran dan konfirmasi oleh admin.  
**File:** `pembayaran.php`, `uploads/`, `admin/pemesanan.php`

### 4️⃣ Modul Admin
Menjadi pusat pengelolaan sistem: data bus, jadwal, pembayaran, refund, dan laporan.  
**File:** `admin/index_admin.php`, `admin/bus.php`, `admin/pemesanan.php`, `admin/refund.php`

---

## 📖 Panduan Penggunaan Singkat

### 👥 Pengguna
1. Registrasi dan login melalui `masuk.php`  
2. Cari tiket bus berdasarkan asal, tujuan, dan tanggal  
3. Pilih kursi dan unggah bukti pembayaran  
4. Lihat status transaksi di `riwayat.php`  
5. Dapatkan bantuan di `bantuan.php`

### 🛠️ Admin
1. Login ke panel admin (`admin/login.php`)  
2. Kelola jadwal bus dan data transaksi  
3. Konfirmasi pembayaran dan refund  
4. Pantau laporan keuangan serta statistik di `index_admin.php`

---

## 📷 Tampilan Utama
- Dashboard User: pencarian tiket dan jadwal  
- Riwayat Pemesanan: daftar tiket & status transaksi  
- Profil Pengguna: ubah data & password  
- Dashboard Admin: statistik booking dan laporan refund  

---

## 📄 Lisensi
Proyek ini dikembangkan untuk keperluan akademik di **Universitas Negeri Yogyakarta**  
dan **tidak untuk tujuan komersial**.

---

## 🔗 Link Repository
- 🌐 **Web App:** [https://github.com/syfnblca/globetix](https://github.com/syfnblca/globetix)  

---

<div align="center">

## 📬 Kontak
📧 **globetix.msg@gmail.com**  

⭐ Dukung proyek ini dengan memberi **bintang (⭐)** di repositori jika kamu merasa *GlobeTix* bermanfaat!

</div>
