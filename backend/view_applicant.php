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
$id = intval($_GET['id'] ?? 0);
$app = $conn->query("SELECT * FROM applications WHERE id = $id")->fetch_assoc();
if (!$app) die('Applicant not found.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applicant - Admin Dashboard</title>
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
        .applicant-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 2rem; max-width: 600px; margin: 0 auto; }
        .applicant-card h3 { margin-top: 0; color: #1e3a8a; }
        .applicant-details { margin: 1.5rem 0; }
        .applicant-details dt { font-weight: 600; color: #1e3a8a; }
        .applicant-details dd { margin: 0 0 1rem 0; color: #222; }
        .doc-link { color: #1e3a8a; text-decoration: underline; }
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
        <div class="applicant-card" style="position:relative;">
            <?php if($app['photo_path']): ?>
                <img src="../<?php echo htmlspecialchars($app['photo_path']); ?>" alt="Passport Photo" style="position:absolute;top:2rem;right:2rem;width:100px;height:100px;object-fit:cover;border-radius:10px;border:2px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,0.08);background:#f8fafc;">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($app['full_name']); ?></h3>
            <dl class="applicant-details">
                <dt>Program Applied</dt><dd><?php echo htmlspecialchars($app['program_applied']); ?></dd>
                <dt>Email</dt><dd><?php echo htmlspecialchars($app['email']); ?></dd>
                <dt>Phone</dt><dd><?php echo htmlspecialchars($app['phone']); ?></dd>
                <dt>Date of Birth</dt><dd><?php echo htmlspecialchars($app['date_of_birth']); ?></dd>
                <dt>Gender</dt><dd><?php echo htmlspecialchars($app['gender']); ?></dd>
                <dt>Address</dt><dd><?php echo htmlspecialchars($app['address']); ?></dd>
                <dt>State</dt><dd><?php echo htmlspecialchars($app['state']); ?></dd>
                <dt>LGA</dt><dd><?php echo htmlspecialchars($app['lga']); ?></dd>
                <dt>Last School</dt><dd><?php echo htmlspecialchars($app['last_school']); ?></dd>
                <dt>Qualification</dt><dd><?php echo htmlspecialchars($app['qualification']); ?></dd>
                <dt>Result Status</dt><dd><?php echo ucfirst(str_replace('_', ' ', $app['result_status'] ?? 'available')); ?></dd>
                <dt>Year Completed</dt><dd><?php echo htmlspecialchars($app['year_completed']); ?></dd>
                <dt>Status</dt><dd><?php echo ucfirst($app['application_status']); ?></dd>
                <dt>Submitted</dt><dd><?php echo $app['submitted_at']; ?></dd>
                <dt>Certificate</dt><dd><?php if($app['certificate_path']) echo '<a class="doc-link" href="../'.htmlspecialchars($app['certificate_path']).'" target="_blank">View Certificate</a>'; ?></dd>
            </dl>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?> 