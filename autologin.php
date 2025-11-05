<?php
session_start();
include 'db.php';

if (!isset($_GET['token'])) {
    header("Location: masuk.php");
    exit;
}

$token = $_GET['token'];
$now = date("Y-m-d H:i:s");

$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token=? AND expires_at > ?");
$stmt->bind_param("ss", $token, $now);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $email = $row['email'];

    // ambil user dari email
    $u = $conn->prepare("SELECT user_id, username FROM users WHERE email=? LIMIT 1");
    $u->bind_param("s", $email);
    $u->execute();
    $user = $u->get_result()->fetch_assoc();

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];

        // hapus token supaya sekali pakai
        $del = $conn->prepare("DELETE FROM password_resets WHERE token=?");
        $del->bind_param("s", $token);
        $del->execute();

        header("Location: dashboard.php");
        exit;
    }
}

echo "Link login tidak valid atau sudah kadaluarsa.";
?>
