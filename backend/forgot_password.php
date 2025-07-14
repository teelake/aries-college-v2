<?php
require_once 'db_connect.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $stmt = $conn->prepare("SELECT id, username FROM admin_users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($admin_id, $username);
            $stmt->fetch();
            // Generate new password
            $new_pass = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 10);
            $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt->close();
            $stmt2 = $conn->prepare("UPDATE admin_users SET password_hash=? WHERE id=?");
            $stmt2->bind_param("si", $new_hash, $admin_id);
            $stmt2->execute();
            $stmt2->close();
            // Send email
            $subject = "Password Reset - Aries College Admin";
            $message = "Hello $username,\n\nYour new password is: $new_pass\n\nPlease log in and change your password immediately.";
            $headers = "From: Aries College <no-reply@achtech.org.ng>\r\nContent-type: text/plain; charset=UTF-8";
            mail($email, $subject, $message, $headers);
            $msg = '<span style="color:green;">A new password has been sent to your email.</span>';
        } else {
            $msg = '<span style="color:red;">Email not found.</span>';
        }
    } else {
        $msg = '<span style="color:red;">Please enter your email.</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Admin Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>.form-group{margin-bottom:1rem;}label{display:block;margin-bottom:0.3rem;}input{width:100%;padding:0.5rem;}body{background:#f8fafc;}</style>
</head>
<body>
    <div class="login-container" style="max-width:400px;margin:80px auto;background:#fff;padding:2rem;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
        <h2>Forgot Password</h2>
        <?php if($msg) echo '<p>'.$msg.'</p>'; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Enter your email address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
        <p style="margin-top:1rem;"><a href="admin_login.php">Back to Login</a></p>
    </div>
</body>
</html>
<?php $conn->close(); ?> 