<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require_once 'db_connect.php';
$msg = '';
// Add new admin
if (isset($_POST['add_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $email && $password) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username=? OR email=?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->bind_result($exists);
        $stmt->fetch();
        $stmt->close();
        if ($exists > 0) {
            $msg = "Username or email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hash);
            $stmt->execute();
            $stmt->close();
            $msg = "New admin added successfully.";
        }
    } else {
        $msg = "All fields are required.";
    }
}
// Delete admin
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id === 1) {
        $msg = "Default admin cannot be deleted.";
    } else {
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id=?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Admin deleted.";
    }
}
// List admins
$admins = $conn->query("SELECT id, username, email FROM admin_users ORDER BY id ASC");
$admin_id = $_SESSION['admin_id'];
// Fetch admin username for profile dropdown
$stmt = $conn->prepare("SELECT username FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_username);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Aries College</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="../img/logo.png">
    <style>
        .admin-table { width:100%; border-collapse:collapse; margin-top:2rem; }
        .admin-table th, .admin-table td { border:1px solid #e5e7eb; padding:0.7rem; text-align:left; }
        .admin-table th { background:#f1f5f9; }
        .admin-table td:last-child { text-align:center; }
        .admin-table .not-allowed { color:#aaa; cursor:not-allowed; }
        .add-admin-form { margin-top:2rem; max-width:400px; background:#fff; padding:1.5rem; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
        .add-admin-form input { width:100%; margin-bottom:1rem; padding:0.6rem; border-radius:5px; border:1px solid #e5e7eb; }
        .add-admin-form button { width:100%; }
        .profile-bar { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 2rem; }
        .profile-dropdown { position: relative; }
        .profile-btn { background: #fff; color: #1e3a8a; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold; cursor: pointer; border: none; }
        .profile-menu { display: none; position: absolute; right: 0; top: 48px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; min-width: 160px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); z-index: 10; }
        .profile-menu a { display: block; padding: 0.8rem 1.2rem; color: #1e3a8a; text-decoration: none; }
        .profile-menu a:hover { background: #f1f5f9; }
    </style>
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
    <h2>Admin Management</h2>
    <?php if($msg) echo '<p style="color:green;">'.htmlspecialchars($msg).'</p>'; ?>
    <table class="admin-table">
        <thead>
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while($row = $admins->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <?php if($row['id'] == 1): ?>
                        <span class="not-allowed">Default admin</span>
                    <?php else: ?>
                        <a href="admins.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this admin?');">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <form class="add-admin-form" method="POST">
        <h3>Add New Admin</h3>
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
    </form>
</div>
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
</body>
</html>
<?php $conn->close(); ?> 