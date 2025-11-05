<?php
$host = "localhost";
$user = "root";     // default user XAMPP/Laragon
$pass = "root";         // default kosong
$db   = "globetix"; // sesuai nama database

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
