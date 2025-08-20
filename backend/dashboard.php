<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require_once 'db_connect.php';
// Fetch analytics
$totalApplicants = $conn->query("SELECT COUNT(*) FROM applications")->fetch_row()[0];
$admitted = $conn->query("SELECT COUNT(*) FROM applications WHERE application_status='admitted'")->fetch_row()[0];
$notAdmitted = $conn->query("SELECT COUNT(*) FROM applications WHERE application_status='not_admitted'")->fetch_row()[0];
$totalPayments = $conn->query("SELECT IFNULL(SUM(amount),0) FROM transactions WHERE status='success'")->fetch_row()[0];
$messages = $conn->query("SELECT COUNT(*) FROM contact_messages")->fetch_row()[0];
$recentApplicants = $conn->query("SELECT full_name, email, program_applied, submitted_at FROM applications ORDER BY submitted_at DESC LIMIT 5");
// Fetch admin username for profile dropdown
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT username FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_username);
$stmt->fetch();
$stmt->close();
// Fetch applicants per program for bar chart
$programData = $conn->query("SELECT program_applied, COUNT(*) as total FROM applications GROUP BY program_applied");
$programLabels = [];
$programCounts = [];
while($row = $programData->fetch_assoc()) {
    $programLabels[] = $row['program_applied'];
    $programCounts[] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aries College</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .dashboard-cards { display: flex; flex-wrap: wrap; gap: 2rem; margin: 2rem 0; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 2rem; flex: 1 1 200px; min-width: 180px; text-align: center; font-size: 1.1rem; }
        .card span { display: block; font-size: 2rem; font-weight: bold; margin-top: 0.5rem; color: #1e3a8a; }
        .analytics-section { margin-top: 2rem; }
        .responsive-table { width: 100%; border-collapse: collapse; }
        .responsive-table th, .responsive-table td { border: 1px solid #e5e7eb; padding: 0.7rem; text-align: left; }
        .responsive-table th { background: #f1f5f9; }
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
        <div class="dashboard-cards">
            <div class="card">Total Applicants<span><?php echo $totalApplicants; ?></span></div>
            <div class="card">Admitted<span><?php echo $admitted; ?></span></div>
            <div class="card">Not Admitted<span><?php echo $notAdmitted; ?></span></div>
            <div class="card">Payments<span>₦<?php echo number_format($totalPayments,2); ?></span></div>
            <div class="card">Messages<span><?php echo $messages; ?></span></div>
        </div>
        <div class="analytics-section">
            <h3>Recent Applicants</h3>
            <table class="responsive-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Program</th><th>Submitted</th></tr>
                </thead>
                <tbody>
                <?php while($row = $recentApplicants->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['program_applied']); ?></td>
                        <td><?php echo $row['submitted_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <h3 style="margin-top:2rem;">Applicants by Program</h3>
            <canvas id="programBarChart" height="100"></canvas>
        </div>
    </div>
    <script>
    // Bar chart for applicants per program
    const ctx = document.getElementById('programBarChart').getContext('2d');
    const programBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($programLabels); ?>,
            datasets: [{
                label: 'Number of Applicants',
                data: <?php echo json_encode($programCounts); ?>,
                backgroundColor: 'rgba(30, 58, 138, 0.7)',
                borderColor: 'rgba(30, 58, 138, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    </script>
</body>
</html>
<?php $conn->close(); ?> 