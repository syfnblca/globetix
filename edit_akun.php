<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: masuk.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_baru = trim($_POST['nama_lengkap']);
    $username_baru = trim($_POST['username']);

    // Cek apakah username sudah digunakan oleh user lain
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $check_stmt->bind_param("si", $username_baru, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<script>alert('Username sudah digunakan oleh pengguna lain!'); window.location='edit_akun.php';</script>";
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();

    $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, username = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $nama_baru, $username_baru, $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('Perubahan berhasil disimpan!'); window.location='profil.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data!'); window.location='edit_akun.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Akun</title>
  <link rel="stylesheet" href="profil.css">
</head>
<body>
  <header>
    <div class="logo"><img src="logo.png" alt="Logo"></div>
    <nav class="nav-menu">
      <a href="dashboard.php">Beranda</a>
      <a href="riwayat.php">Riwayat</a>
      <a href="profil.php" class="active">Profil</a>
      <a href="keluar.php">Keluar</a>
    </nav>
  </header>

  <section class="profile-container">
    <div class="profile-icon">👤</div>
    <form method="POST">
      <input type="text" name="nama_lengkap" placeholder="Nama Baru" required>
      <input type="text" name="username" placeholder="Username" required>
      <button type="submit" class="btn">Simpan Perubahan</button>
    </form>
  </section>

  <footer>
    <div class="links">
      <a href="https://wa.me/6289519515332" target="_blank">Contact Person</a>
      <a href="bantuan.php">Bantuan/Laporan</a>
    </div>
    <p>© 2025 GlobeTix Travel Booking</p>
  </footer>
</body>
</html>
