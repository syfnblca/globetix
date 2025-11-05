<?php
// admin/includes/auth.php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location:login.php");
    exit();
}
?>
