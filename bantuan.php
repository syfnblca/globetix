<?php
session_start();
include 'db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $pesan = trim($_POST['pesan']);

    if (empty($nama) || empty($email) || empty($pesan)) {
        $error = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Send email using PHPMailer
        require 'PHPMailer/src/Exception.php';
        require 'PHPMailer/src/PHPMailer.php';
        require 'PHPMailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'storyfromabiee@gmail.com';
            $mail->Password   = 'biathkgvtcdldgxz';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('storyfromabiee@gmail.com', 'Globetix MSG');
            $mail->addAddress('storyfromabiee@gmail.com');

            $mail->isHTML(true);
            $mail->Subject = 'Bantuan/Laporan dari User - ' . $nama;
            $mail->Body    = "
                <h3>Bantuan/Laporan</h3>
                <p><strong>Nama:</strong> {$nama}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Pesan:</strong></p>
                <p>" . nl2br(htmlspecialchars($pesan)) . "</p>
            ";

            $mail->send();
            $success = "Pesan berhasil dikirim! Kami akan segera merespons.";
        } catch (Exception $e) {
            $error = "Gagal mengirim pesan. Error: {$mail->ErrorInfo}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bantuan/Laporan - GlobeTix</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .help-form {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .help-form h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        .btn-submit:hover {
            background: #0056b3;
        }
        .btn-back {
            display: inline-block;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
        }
        .btn-back:hover {
            text-decoration: underline;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            color: #155724;
            background: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
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
            <a href="profil.php">Profil</a>
            <a href="keluar.php">Keluar</a>
        </nav>
    </header>

    <div class="help-form">
        <h2>Bantuan/Laporan</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="nama">Nama Lengkap:</label>
                <input type="text" id="nama" name="nama" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="pesan">Pesan Bantuan/Laporan:</label>
                <textarea id="pesan" name="pesan" placeholder="Jelaskan masalah atau pertanyaan Anda..." required></textarea>
            </div>
            <button type="submit" class="btn-submit">Kirim Pesan</button>
        </form>
        <a href="dashboard.php" class="btn-back">← Kembali ke Beranda</a>
    </div>
</body>
</html>
