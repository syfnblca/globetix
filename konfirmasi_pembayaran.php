<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// Get booking IDs from POST (comma-separated for round-trip)
$booking_ids_str = $_POST['booking_ids'] ?? '';
$booking_ids = array_filter(array_map('intval', explode(',', $booking_ids_str)));

if (empty($booking_ids)) {
    echo "<script>alert('Data booking tidak valid.'); window.location='dashboard.php';</script>";
    exit;
}

// Validate file upload
if (!isset($_FILES['bukti_tf']) || $_FILES['bukti_tf']['error'] !== UPLOAD_ERR_OK) {
    echo "<script>alert('Bukti transfer tidak diunggah.'); window.history.back();</script>";
    exit;
}

$file = $_FILES['bukti_tf'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($extension, $allowed_extensions)) {
    echo "<script>alert('Format file tidak didukung. Hanya JPG, PNG, atau PDF.'); window.history.back();</script>";
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
    echo "<script>alert('Ukuran file terlalu besar. Maksimal 5MB.'); window.history.back();</script>";
    exit;
}

// Create upload directory if not exists
$upload_dir = 'uploads/bukti/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$filename = 'bukti_' . implode('_', $booking_ids) . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo "<script>alert('Gagal mengunggah file bukti.'); window.history.back();</script>";
    exit;
}

// Check if all bookings are still in pending status
$check_stmt = $conn->prepare("SELECT status_booking FROM booking WHERE booking_id = ?");
foreach ($booking_ids as $bid) {
    $check_stmt->bind_param("i", $bid);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    if (!$result || $result['status_booking'] !== 'pending') {
        echo "<script>alert('Salah satu booking sudah tidak valid atau sudah diproses.'); window.location='riwayat.php';</script>";
        exit;
    }
}

// Begin transaction to update bookings and insert payments
$conn->begin_transaction();
try {
    // Prepare statements
    $update_booking = $conn->prepare("UPDATE booking SET status_booking = 'pending' WHERE booking_id = ?");
    $insert_payment = $conn->prepare("INSERT INTO pembayaran (booking_id, metode, no_identitas, jumlah_bayar, status_pembayaran, bukti_bayar) VALUES (?, 'transfer_bank', '-', ?, 'pending', ?) ON DUPLICATE KEY UPDATE bukti_bayar = VALUES(bukti_bayar), status_pembayaran = 'pending'");

    foreach ($booking_ids as $bid) {
        // Update booking status
        $update_booking->bind_param("i", $bid);
        $update_booking->execute();

        // Get total_harga for this booking
        $get_harga_stmt = $conn->prepare("SELECT total_harga FROM booking WHERE booking_id = ?");
        $get_harga_stmt->bind_param("i", $bid);
        $get_harga_stmt->execute();
        $harga_result = $get_harga_stmt->get_result()->fetch_assoc();
        $total_harga = $harga_result['total_harga'];

        // Insert payment record
        $insert_payment->bind_param("ids", $bid, $total_harga, $filename);
        $insert_payment->execute();
    }

    $conn->commit();
} catch (Exception $ex) {
    $conn->rollback();
    echo "<script>alert('Gagal menyimpan data pembayaran: " . addslashes($ex->getMessage()) . "'); window.history.back();</script>";
    exit;
}

// Send email notification to admin
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
$email_sent = false;

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'storyfromabiee@gmail.com';
    $mail->Password = 'biathkgvtcdldgxz'; // Update with new Gmail app password
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('globetix.msg@gmail.com', 'GlobeTix System');
    $mail->addAddress('globetix.msg@gmail.com', 'GlobeTix Admin');

    $mail->isHTML(false);
    $mail->Subject = 'Bukti Pembayaran Baru - GlobeTix';

    $body = "Ada bukti pembayaran baru yang diunggah untuk booking berikut:\n\n";
    foreach ($booking_ids as $bid) {
        $get_details_stmt = $conn->prepare("SELECT b.kode_booking, b.total_harga, p.nama_penumpang FROM booking b JOIN penumpang p ON b.booking_id = p.booking_id WHERE b.booking_id = ? LIMIT 1");
        $get_details_stmt->bind_param("i", $bid);
        $get_details_stmt->execute();
        $details = $get_details_stmt->get_result()->fetch_assoc();
        $body .= "Booking ID: {$bid}\n";
        $body .= "Kode Booking: {$details['kode_booking']}\n";
        $body .= "Nama Penumpang: {$details['nama_penumpang']}\n";
        $body .= "Total: Rp" . number_format($details['total_harga'], 0, ',', '.') . "\n\n";
    }
    $body .= "Bukti pembayaran telah dilampirkan. Silakan periksa dan konfirmasi di admin panel.";

    $mail->Body = $body;
    $mail->addAttachment($filepath, 'Bukti_Pembayaran_' . implode('_', $booking_ids) . '.' . $extension);

    $mail->send();
    $email_sent = true;
} catch (Exception $e) {
    error_log("Email gagal dikirim. Error: {$mail->ErrorInfo}");
    // For debugging, you can uncomment the line below to see the error in browser
    // echo "<script>alert('Email error: " . addslashes($mail->ErrorInfo) . "');</script>";
}

// Prepare success message
$message = 'Bukti pembayaran berhasil diunggah.';
if ($email_sent) {
    $message .= ' Email notifikasi telah dikirim ke admin.';
} else {
    $message .= ' Email notifikasi gagal dikirim, namun admin akan memeriksa secara manual.';
}
$message .= ' Menunggu konfirmasi admin.';

echo "<script>alert('" . addslashes($message) . "'); window.location='riwayat.php';</script>";
?>
