<?php
session_start();
include 'db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: masuk.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $conn->prepare("SELECT nama_lengkap, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Profil - GlobeTix</title>
  <link rel="stylesheet" href="profil.css">
</head>
<body>
  <!-- Navbar -->
  <header>
    <div class="logo">
      <img src="logo.png" alt="GlobeTix Logo">
    </div>
    <nav class="nav-menu">
      <a href="dashboard.php">Beranda</a>
      <a href="riwayat.php">Riwayat</a>
      <a href="profil.php" class="active">Profil</a>
      <a href="keluar.php">Keluar</a>
    </nav>
  </header>

  <!-- Profil -->
  <section class="profile-container">
    <div class="profile-icon">👤</div>
    <h2><?php echo htmlspecialchars($user['nama_lengkap']); ?></h2>
    <p><?php echo htmlspecialchars($user['email']); ?></p>

    <div class="profile-buttons">
      <a href="edit_akun.php" class="btn">Edit Akun</a>
      <a href="ubah_password.php" class="btn">Ubah Kata Sandi</a>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="links">
      <a href="https://wa.me/6289519515332" target="_blank">Contact Person</a>
      <a href="bantuan.php">Bantuan/Laporan</a>
    </div>
    <p>© 2025 GlobeTix Travel Booking</p>
  </footer>
</body>
</html>
