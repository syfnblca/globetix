<?php
session_start();
include 'db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: masuk.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Validasi booking_id dan kepemilikan
if ($booking_id <= 0) {
    echo "<script>alert('Booking ID tidak valid!'); window.location.href = 'riwayat.php';</script>";
    exit();
}

$check = $conn->prepare("SELECT kode_booking, status_booking FROM booking WHERE booking_id=? AND user_id=?");
$check->bind_param("ii", $booking_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Booking tidak ditemukan atau bukan milik Anda!'); window.location.href = 'riwayat.php';</script>";
    exit();
}

$row = $result->fetch_assoc();
$kode_booking = $row['kode_booking'];
$status_booking = $row['status_booking'];

if ($status_booking !== 'confirmed') {
    echo "<script>alert('Booking tidak dapat dibatalkan!'); window.location.href = 'riwayat.php';</script>";
    exit();
}

// Cek apakah sudah ada request refund
$refund_check = $conn->prepare("SELECT refund_id FROM refund WHERE booking_id=?");
$refund_check->bind_param("i", $booking_id);
$refund_check->execute();
if ($refund_check->get_result()->num_rows > 0) {
    echo "<script>alert('Request pembatalan sudah diajukan!'); window.location.href = 'riwayat.php';</script>";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alasan = trim($_POST['alasan']);
    $rekening_refund = trim($_POST['rekening_refund']);

    if (empty($alasan) || empty($rekening_refund)) {
        $error = "Semua field harus diisi!";
    } else {
        // Get payment_id and jumlah_bayar
        $payment_query = $conn->prepare("SELECT payment_id, jumlah_bayar FROM pembayaran WHERE booking_id=? LIMIT 1");
        $payment_query->bind_param("i", $booking_id);
        $payment_query->execute();
        $payment_result = $payment_query->get_result();

        if ($payment_result->num_rows > 0) {
            $payment_row = $payment_result->fetch_assoc();
            $payment_id = $payment_row['payment_id'];
            $jumlah_refund = $payment_row['jumlah_bayar'];

            // Insert into refund table
            $insert = $conn->prepare("INSERT INTO refund (booking_id, payment_id, alasan, rekening_refund, jumlah_refund, status_refund) VALUES (?, ?, ?, ?, ?, 'diproses')");
            $insert->bind_param("iissd", $booking_id, $payment_id, $alasan, $rekening_refund, $jumlah_refund);

            if ($insert->execute()) {
                // Send email to admin
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

                    $mail->setFrom('storyfromabiee@gmail.com@gmail.com', 'Globetix MSG');
                    $mail->addAddress('storyfromabiee@gmail.com@gmail.com');

                    $mail->isHTML(true);
                    $mail->Subject = 'Request Pembatalan Booking - ' . $kode_booking;
                    $mail->Body    = "
                        <h3>Request Pembatalan Booking</h3>
                        <p><strong>Kode Booking:</strong> {$kode_booking}</p>
                        <p><strong>Alasan Pembatalan:</strong> {$alasan}</p>
                        <p><strong>Rekening Pengembalian:</strong> {$rekening_refund}</p>
                        <p><strong>Jumlah Refund:</strong> Rp " . number_format($jumlah_refund, 0, ',', '.') . "</p>
                        <p>Silakan periksa dan proses refund di admin panel.</p>
                    ";

                    $mail->send();
                    echo "<script>alert('Request pembatalan berhasil diajukan! Email notifikasi telah dikirim ke admin.'); window.location.href = 'riwayat.php';</script>";
                } catch (Exception $e) {
                    echo "<script>alert('Request pembatalan berhasil diajukan, namun gagal mengirim email ke admin. Error: {$mail->ErrorInfo}'); window.location.href = 'riwayat.php';</script>";
                }
            } else {
                $error = "Gagal mengajukan request pembatalan!";
            }
        } else {
            $error = "Data pembayaran tidak ditemukan!";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Batalkan Pemesanan - GlobeTix</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .cancel-form {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        .cancel-form h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
        }
        .booking-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        .booking-code {
            font-size: 18px;
            font-weight: 600;
            color: #007bff;
            margin: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: #fff;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        .form-group textarea {
            height: 120px;
            resize: vertical;
            font-family: inherit;
        }
        .readonly-field {
            background: #f8f9fa;
            cursor: not-allowed;
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 25px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220,53,69,0.3);
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            color: #6c757d;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .btn-back:hover {
            color: #007bff;
        }
        .error {
            color: #721c24;
            background: linear-gradient(135deg, #f8d7da, #fce4e6);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-weight: 500;
        }
        .warning {
            color: #856404;
            background: linear-gradient(135deg, #fff3cd, #ffecb5);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .cancel-form {
                margin: 20px;
                padding: 20px;
            }
            .cancel-form h2 {
                font-size: 24px;
            }
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
            <a href="riwayat.php" class="active">Riwayat</a>
            <a href="profil.php">Profil</a>
            <a href="keluar.php">Keluar</a>
        </nav>
    </header>

    <div class="cancel-form">
        <h2>Batalkan Pemesanan</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="booking-info">
            <p class="booking-code">Kode Booking: <?php echo htmlspecialchars($kode_booking); ?></p>
        </div>

        <form method="post">
            <div class="form-group">
                <label for="alasan">Alasan Pembatalan:</label>
                <textarea id="alasan" name="alasan" placeholder="Jelaskan alasan pembatalan..." required></textarea>
            </div>
            <div class="form-group">
                <label for="rekening_refund">Rekening Pengembalian Dana:</label>
                <input type="text" id="rekening_refund" name="rekening_refund" placeholder="Contoh: BCA 1234567890 a.n. Nama Lengkap" required>
            </div>
            <button type="submit" class="btn-submit">Ajukan Pembatalan</button>
        </form>
        <a href="riwayat.php" class="btn-back">← Kembali ke Riwayat</a>
    </div>
</body>
</html>
