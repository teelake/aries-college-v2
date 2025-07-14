<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require_once 'db_connect.php';
$admin_id = $_SESSION['admin_id'];
// Fetch admin username for profile dropdown
$stmt = $conn->prepare("SELECT username FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_username);
$stmt->fetch();
$stmt->close();
// Fetch current admin info
$stmt = $conn->prepare("SELECT username, email FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    if ($new_username && $new_email) {
        // Check uniqueness (exclude self)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE (username=? OR email=?) AND id != ?");
        $stmt->bind_param("ssi", $new_username, $new_email, $admin_id);
        $stmt->execute();
        $stmt->bind_result($exists);
        $stmt->fetch();
        $stmt->close();
        if ($exists > 0) {
            $msg = "Username or email already exists for another admin.";
        } else {
            $stmt = $conn->prepare("UPDATE admin_users SET username=?, email=? WHERE id=?");
            $stmt->bind_param("ssi", $new_username, $new_email, $admin_id);
            $stmt->execute();
            $stmt->close();
            $username = $new_username;
            $email = $new_email;
            $msg = "Profile updated successfully.";
        }
    } else {
        $msg = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="../img/logo.png">
    <style>
        body { background: #f8fafc; margin: 0; }
        .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 220px; background: #1e3a8a; color: #fff; padding: 2rem 1rem; display: flex; flex-direction: column; min-height: 100vh; z-index: 100; }
        .sidebar a { color: #fff; text-decoration: none; font-weight: 500; margin-bottom: 1.5rem; display: block; }
        .sidebar a.active, .sidebar a:hover { background: #fff; color: #1e3a8a; border-radius: 6px; padding: 0.5rem 1rem; }
        .main-content { margin-left: 240px; padding: 2rem; }
        .profile-bar { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 2rem; }
        .profile-dropdown { position: relative; }
        .profile-btn { background: #fff; color: #1e3a8a; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold; cursor: pointer; border: none; }
        .profile-menu { display: none; position: absolute; right: 0; top: 48px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; min-width: 160px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); z-index: 10; }
        .profile-menu a { display: block; padding: 0.8rem 1.2rem; color: #1e3a8a; text-decoration: none; }
        .profile-menu a:hover { background: #f1f5f9; }
        .form-group{margin-bottom:1rem;}label{display:block;margin-bottom:0.3rem;}input{width:100%;padding:0.5rem;}
        @media (max-width: 900px) {
            .sidebar { position: static; width: 100%; flex-direction: row; padding: 1rem; min-height: unset; }
            .sidebar a { margin: 0 1rem 0 0; display: inline-block; }
            .main-content { margin-left: 0; padding: 1rem; }
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('profileBtn');
        var menu = document.getElementById('profileMenu');
        btn.onclick = function(e) {
            e.stopPropagation();
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        };
        document.body.onclick = function() { menu.style.display = 'none'; };
    });
    </script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="profile-bar">
            <div class="profile-dropdown">
                <button id="profileBtn" class="profile-btn"><?php echo strtoupper(substr($admin_username,0,1)); ?></button>
                <div id="profileMenu" class="profile-menu">
                    <a href="profile.php">Edit Profile</a>
                    <a href="change_password.php">Change Password</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <h2>Edit Profile</h2>
        <?php if(isset($msg)) echo '<p style="color:green;">'.$msg.'</p>'; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?> 