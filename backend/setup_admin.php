<?php
session_start();
require_once 'db_connect.php';

// Check if admin already exists
$check = $conn->query("SELECT COUNT(*) as count FROM admin_users");
$result = $check->fetch_assoc();

if ($result['count'] == 0) {
    // Create default admin
    $username = 'admin';
    $email = 'admin@achtech.org.ng';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hash);
    
    if ($stmt->execute()) {
        echo "<h2>Default Admin Created Successfully!</h2>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><strong>Email:</strong> admin@achtech.org.ng</p>";
        echo "<p><a href='admin_login.php'>Go to Login</a></p>";
    } else {
        echo "<h2>Error creating admin user</h2>";
        echo "<p>Error: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
    echo "<h2>Admin users already exist</h2>";
    echo "<p>There are already admin users in the system.</p>";
    echo "<p><a href='admin_login.php'>Go to Login</a></p>";
}
?> 