<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lupa Kata Sandi - GlobeTix</title>
  <link rel="stylesheet" href="lupa.css">
</head>
<body>
    <header>
    <div class="logo">
      <img src="logo.png" alt="GlobeTix Logo" style="height:50px;">
    </div>
  </header>

  <div class="forgot-container">

    <h1>Lupa Kata Sandi</h1>
    <p>Silakan masukkan alamat email yang ingin Anda kirimi informasi pengaturan ulang kata sandi Anda.</p>

    <form action="lupa.php" method="POST">
      <input type="email" name="email" placeholder="Alamat Email" required>
      <button type="submit" class="btn-submit">Minta tautan masuk</button>
    </form>

    <a href="dashboard.php" class="back-btn">Kembali ke Masuk</a>
  </div>

  <footer>
    <p>© 2025 GlobeTix Travel Booking</p>
  </footer>
</body>
</html>
<?php
session_start();
include 'db.php';

// --- PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Cek apakah email ada di database
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $user_id = $row['user_id'];

        // --- BUAT TOKEN LOGIN SEKALI PAKAI ---
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + 600); // berlaku 10 menit

        // Hapus token lama untuk email ini
        $del = $conn->prepare("DELETE FROM password_resets WHERE email=?");
        $del->bind_param("s", $email);
        $del->execute();
        $del->close();

        // Simpan token baru
        $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $ins->bind_param("sss", $email, $token, $expires_at);
        $ins->execute();
        $ins->close();

        // Link auto login
        $login_link = "http://localhost/globetix/autologin.php?token=" . $token;

        // --- KIRIM EMAIL VIA GMAIL ---
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'storyfromabiee@gmail.com'; // alamat Gmail kamu
            $mail->Password   = 'biathkgvtcdldgxz'; // ← GANTI dengan App Password 16 karakter TANPA SPASI
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // bisa juga gunakan 'tls'
            $mail->Port       = 587;

            $mail->setFrom('globetix.msg@gmail.com', 'GlobeTix Support');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Tautan Masuk Cepat GlobeTix';
            $mail->Body    = "
                Halo,<br><br>
                Klik tombol di bawah ini untuk langsung masuk ke Dashboard GlobeTix:<br><br>
                <a href='{$login_link}' style='background:#ffa726;color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:bold;'>MASUK KE DASHBOARD</a>
                <br><br>
                Tautan ini berlaku selama 10 menit dan hanya bisa digunakan sekali.<br><br>
                Jika Anda tidak meminta tautan ini, abaikan email ini.<br><br>
                Hormat kami,<br>Tim GlobeTix
            ";
            $mail->AltBody = "Klik link berikut untuk login cepat: {$login_link}";

            $mail->send();

            echo "<script>alert('Tautan masuk cepat telah dikirim ke email Anda. Cek inbox Gmail.'); window.location='masuk.php';</script>";
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            echo "<script>alert('Gagal mengirim email.'); window.location='lupa.php';</script>";
        }
    } else {
        echo "<script>alert('Email tidak terdaftar!'); window.location='lupa.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
