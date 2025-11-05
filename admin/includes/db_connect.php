<?php
// admin/includes/db_connect.php

// Koneksi menggunakan MySQLi Procedural sesuai permintaan kamu
$conn = mysqli_connect("localhost", "root", "root", "globetix");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
