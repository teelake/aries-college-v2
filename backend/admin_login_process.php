<?php
// Start output buffering to prevent headers already sent error
ob_start();
session_start();
require_once 'db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    $_SESSION['login_error'] = 'Please enter both username and password.';
    header('Location: admin_login.php');
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
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
    
} catch (Exception $e) {
    $_SESSION['login_error'] = 'Database error: ' . $e->getMessage();
    header('Location: admin_login.php');
    exit;
}

// Clean up output buffer
ob_end_flush();
?> 