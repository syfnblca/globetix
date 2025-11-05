<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Masuk - GlobeTix</title>
  <link rel="stylesheet" href="masuk.css">
</head>
<body>
  <!-- Navbar -->
  <header>
    <div class="logo">
      <img src="logo.png" alt="GlobeTix Logo" style="height:50px;">
    </div>
  </header>

  <!-- Form Login -->
  <section class="form-container">
    <h2>Masuk</h2>
    <form action="masuk.php" method="POST">
      <div class="form-group">
        <input type="text" name="username" placeholder="Username atau Email" required>
      </div>
      <div class="form-group">
        <input type="password" name="password" placeholder="Kata Sandi" required>
      </div>
      <button type="submit" class="btn-primary">Masuk</button>
    </form>
    <p style="text-align:center; margin-top:10px;">
      <a href="lupa.php" style="color:#fff; text-decoration:underline;">Lupa Kata Sandi?</a>
    </p>
    <p style="text-align:center; margin-top:10px;">
      Belum punya akun? <a href="daftar.php" style="color:#fff; text-decoration:underline;">Daftar</a>
    </p>
  </section>

  <!-- Footer -->
  <footer>
    <p>© 2025 GlobeTix Travel Booking</p>
  </footer>
</body>
</html>
<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // cek user berdasarkan username/email
    $stmt = $conn->prepare("SELECT user_id, username, email, password FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // login sukses → simpan session
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];

            // redirect ke dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('Password salah!'); window.location.href='masuk.php';</script>";
        }
    } else {
        echo "<script>alert('User tidak ditemukan!'); window.location.href='masuk.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
