<?php
// admin/login.php
session_start();
include 'includes/db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // Query admin user
        $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ? AND role = 'admin'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if ($password === $row['password']) {
                // Login success
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $row['user_id'];
                $_SESSION['admin_username'] = $username;
                // Debug
                error_log("Login success for user: $username");
                header("Location: index_admin.php");
                exit();
            } else {
                $message = "Password salah.";
                error_log("Password mismatch for user: $username");
            }
        } else {
            $message = "Username admin tidak ditemukan.";
        }
        $stmt->close();
    } else {
        $message = "Harap isi username dan password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - GlobeTix</title>
    <link rel="stylesheet" href="assets/login.css">
</head>
<body>
<div class="login-container">
    <h2>Admin Login</h2>
    <?php if ($message): ?>
        <p class="error"><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
