<div class="sidebar">
    <div style="text-align:center;margin-bottom:2rem;">
        <div style="width:70px;height:70px;background:#fff;border-radius:50%;margin:0 auto 0.5rem auto;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
            <img src="../img/logo.png" alt="Aries College Logo" style="width:60px;height:60px;object-fit:contain;display:block;">
        </div>
        <div style="font-size:1.1rem;font-weight:bold;letter-spacing:0.5px;">Aries College</div>
    </div>
    <a href="dashboard.php" class="<?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo 'active'; ?>">Dashboard</a>
    <a href="applicants.php" class="<?php if(basename($_SERVER['PHP_SELF'])=='applicants.php') echo 'active'; ?>">Applicants</a>
    <a href="messages.php" class="<?php if(basename($_SERVER['PHP_SELF'])=='messages.php') echo 'active'; ?>">Messages</a>
    <a href="transactions.php" class="<?php if(basename($_SERVER['PHP_SELF'])=='transactions.php') echo 'active'; ?>">Transactions</a>
    <a href="admins.php" class="<?php if(basename($_SERVER['PHP_SELF'])=='admins.php') echo 'active'; ?>">Admins</a>
    <a href="logout.php">Logout</a>
</div> 