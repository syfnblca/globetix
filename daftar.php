<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar - GlobeTix</title>
  <link rel="stylesheet" href="daftar.css">
</head>
<body>
  <header>
    <div class="logo">
      <img src="logo.png" alt="GlobeTix Logo" style="height:50px;">
    </div>
  </header>

  <section class="form-container">
  <h2>Daftar</h2>
  <form action="daftar.php" method="POST">
    <div class="form-group"><input type="text" name="username" placeholder="Username" required></div>
    <div class="form-group"><input type="text" name="nama_lengkap" placeholder="Nama Lengkap" required></div>
    <div class="form-group"><input type="email" name="email" placeholder="Email" required></div>
    <div class="form-group"><input type="password" name="password" placeholder="Kata Sandi" required></div>
    <div class="form-group"><input type="password" name="konfirmasi" placeholder="Konfirmasi Kata Sandi" required></div>
    <button type="submit" class="btn-primary">Daftar</button>
  </form>
  <p style="text-align:center; margin-top:10px;">
    Sudah punya akun? <a href="masuk.php" style="color:#fff; text-decoration:underline;">Masuk</a>
  </p>
</section>

  <footer>
    <p>© 2025 GlobeTix Travel Booking</p>
  </footer>
</body>
</html>
<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username     = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email        = trim($_POST['email']);
    $password     = $_POST['password'];
    $konfirmasi   = $_POST['konfirmasi'];

    if ($password !== $konfirmasi) {
    echo "<script>
            alert('Password dan konfirmasi tidak sama!');
            window.history.back();
          </script>";
    exit;
}

    // cek apakah username atau email sudah ada
    $cek = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $cek->bind_param("ss", $username, $email);
    $cek->execute();
    $hasil = $cek->get_result();

    if ($hasil->num_rows > 0) {
        echo "<script>alert('Username atau Email sudah terdaftar!'); window.location='daftar.php';</script>";
        exit;
    }
    $cek->close();
    // hash password
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    // insert user baru
    $stmt = $conn->prepare("INSERT INTO users (username, nama_lengkap, email, password, role) VALUES (?, ?, ?, ?, 'user')");
    $stmt->bind_param("ssss", $username, $nama_lengkap, $email, $hashed);

    if ($stmt->execute()) {
        // ✅ berhasil → langsung ke masuk.php
        header("Location: masuk.php");
        exit;
    } else {
        // ❌ gagal → popup gagal
        echo "<script>alert('Pendaftaran gagal: ".$stmt->error."'); window.location.href='daftar.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
