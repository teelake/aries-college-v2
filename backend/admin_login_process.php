<?php
session_start();
require_once 'db_connect.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    $_SESSION['login_error'] = 'Please enter both username and password.';
    header('Location: admin_login.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 1) {
    $stmt->bind_result($admin_id, $password_hash);
    $stmt->fetch();
    if (password_verify($password, $password_hash)) {
        $_SESSION['admin_id'] = $admin_id;
        header('Location: dashboard.php');
        exit;
    }
}
$_SESSION['login_error'] = 'Invalid username or password.';
header('Location: admin_login.php');
exit;
?> 