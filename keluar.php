<?php
// mulai session
session_start();

// hapus semua session
session_unset();
session_destroy();

// redirect ke landing page
header("Location: landing.php");
exit;
?>