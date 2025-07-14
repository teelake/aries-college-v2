<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Aries College Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="../img/logo.png">
    <style>
        body { background: #f8fafc; }
        .login-container { max-width: 400px; margin: 80px auto; background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .login-container h2 { text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.7rem; border-radius: 6px; border: 1px solid #e5e7eb; }
        .btn { width: 100%; }
        .error { color: #dc2626; text-align: center; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <img src="../img/logo.png" alt="Aries College Logo" style="width:90px;height:auto;">
        </div>
        <h2>Admin Login</h2>
        <?php if(isset($_SESSION['login_error'])): ?>
            <div class="error"><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></div>
        <?php endif; ?>
        <form action="admin_login_process.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div style="text-align:center;margin-top:1rem;">
            <a href="forgot_password.php" style="color:#1e3a8a;text-decoration:underline;font-size:0.98rem;">Forgot password?</a>
        </div>
    </div>
</body>
</html> 