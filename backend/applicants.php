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
// Adjustable rows per page
$perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10,20,50,100]) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;
// Search and filter
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$where = [];
if ($search) {
    $search_sql = $conn->real_escape_string($search);
    $where[] = "(full_name LIKE '%$search_sql%' OR email LIKE '%$search_sql%' OR program_applied LIKE '%$search_sql%')";
}
if ($status && in_array($status, ['submitted','approved','rejected'])) {
    $where[] = "application_status = '".$conn->real_escape_string($status)."'";
}
$where_sql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
$totalRows = $conn->query("SELECT COUNT(*) FROM applications $where_sql")->fetch_row()[0];
$totalPages = ceil($totalRows / $perPage);
$applicants = $conn->query("SELECT * FROM applications $where_sql ORDER BY submitted_at DESC LIMIT $perPage OFFSET $offset");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    // Check uniqueness
    $stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE email=? OR phone=?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $stmt->bind_result($exists);
    $stmt->fetch();
    $stmt->close();
    if ($exists > 0) {
        $msg = "An application with this email or phone already exists.";
    } else {
        // ... existing application insert logic ...
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicants - Admin Dashboard</title>
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
        .table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
        .table th, .table td { border: 1px solid #e5e7eb; padding: 0.7rem; text-align: left; }
        .table th { background: #f1f5f9; }
        .actions a { margin-right: 0.5rem; }
        .status-submitted { color: #f59e42; font-weight: bold; }
        .status-approved { color: #16a34a; font-weight: bold; }
        .status-rejected { color: #dc2626; font-weight: bold; }
        .pagination { margin: 2rem 0; text-align: center; }
        .pagination a, .pagination span { display: inline-block; margin: 0 4px; padding: 6px 12px; border-radius: 4px; background: #f1f5f9; color: #1e3a8a; text-decoration: none; }
        .pagination .active { background: #1e3a8a; color: #fff; font-weight: bold; }
        .pagination .disabled { color: #aaa; pointer-events: none; }
        .table-controls { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; margin-bottom: 1rem; }
        .table-controls input, .table-controls select { padding: 0.5rem; border-radius: 4px; border: 1px solid #e5e7eb; }
        @media (max-width: 900px) {
            .sidebar { position: static; width: 100%; flex-direction: row; padding: 1rem; min-height: unset; }
            .sidebar a { margin: 0 1rem 0 0; display: inline-block; }
            .main-content { margin-left: 0; padding: 1rem; }
        }
        @media (max-width: 700px) { .table, .table th, .table td { font-size: 0.95rem; } }
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
        <h2>Applicants</h2>
        
        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['admin_message']['type']; ?>" style="
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 6px;
                background: <?php echo $_SESSION['admin_message']['type'] === 'success' ? '#d1fae5' : '#fee2e2'; ?>;
                color: <?php echo $_SESSION['admin_message']['type'] === 'success' ? '#065f46' : '#991b1b'; ?>;
                border: 1px solid <?php echo $_SESSION['admin_message']['type'] === 'success' ? '#a7f3d0' : '#fecaca'; ?>;
            ">
                <?php echo htmlspecialchars($_SESSION['admin_message']['text']); ?>
            </div>
            <?php unset($_SESSION['admin_message']); ?>
        <?php endif; ?>
        
        <form class="table-controls" method="get" action="">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, program...">
            <select name="status">
                <option value="">All Status</option>
                <option value="submitted" <?php if($status==='submitted') echo 'selected'; ?>>Submitted</option>
                <option value="approved" <?php if($status==='approved') echo 'selected'; ?>>Approved</option>
                <option value="rejected" <?php if($status==='rejected') echo 'selected'; ?>>Rejected</option>
            </select>
            <select name="per_page">
                <option value="10" <?php if($perPage==10) echo 'selected'; ?>>10</option>
                <option value="20" <?php if($perPage==20) echo 'selected'; ?>>20</option>
                <option value="50" <?php if($perPage==50) echo 'selected'; ?>>50</option>
                <option value="100" <?php if($perPage==100) echo 'selected'; ?>>100</option>
            </select>
            <button type="submit" class="btn btn-primary">Apply</button>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $applicants->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['program_applied']); ?></td>
                    <td class="status-<?php echo $row['application_status']; ?>"><?php echo ucfirst($row['application_status']); ?></td>
                    <td><?php echo $row['submitted_at']; ?></td>
                    <td class="actions">
                        <a href="view_applicant.php?id=<?php echo $row['id']; ?>">View</a>
                        <a href="admit_applicant.php?id=<?php echo $row['id']; ?>">Admit</a>
                        <a href="reject_applicant.php?id=<?php echo $row['id']; ?>">Reject</a>
                        <a href="send_email.php?id=<?php echo $row['id']; ?>">Send Email</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="pagination">
            <?php
            $queryStr = http_build_query(array_merge($_GET, ['page' => null]));
            if($page > 1): ?>
                <a href="?<?php echo $queryStr . '&page=' . ($page-1); ?>">&laquo; Prev</a>
            <?php else: ?>
                <span class="disabled">&laquo; Prev</span>
            <?php endif; ?>
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <?php if($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?php echo $queryStr . '&page=' . $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?>
                <a href="?<?php echo $queryStr . '&page=' . ($page+1); ?>">Next &raquo;</a>
            <?php else: ?>
                <span class="disabled">Next &raquo;</span>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?> 