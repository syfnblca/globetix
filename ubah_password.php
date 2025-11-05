<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: masuk.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lama = $_POST['password_lama'];
    $baru = $_POST['password_baru'];

    // Ambil password lama
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Cek apakah password lama cocok
    if (password_verify($lama, $hashed_password)) {
        $hashed_baru = password_hash($baru, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update->bind_param("si", $hashed_baru, $user_id);

        if ($update->execute()) {
            echo "<script>alert('Password berhasil diubah!'); window.location='profil.php';</script>";
        } else {
            echo "<script>alert('Gagal mengubah password!');</script>";
        }
        $update->close();
    } else {
        echo "<script>alert('Password lama salah!'); window.location='ubah_password.php';</script>";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ubah Password</title>
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
    <h2>Ubah Password</h2>
    <form method="POST">
      <input type="password" name="password_lama" placeholder="Kata sandi sebelumnya" required>
      <input type="password" name="password_baru" placeholder="Kata sandi baru" required>
      <button type="submit" class="btn">Ubah Password</button>
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
