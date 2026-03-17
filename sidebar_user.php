<?php
// sidebar_user.php (User)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'auth.php';
require_once 'db.php';

$active_page = basename($_SERVER['PHP_SELF']);

// --- Notification Logic ---
$cnt_overdue = 0; $cnt_today = 0; $cnt_future = 0; $total_notify = 0;

if (isset($conn)) {
    date_default_timezone_set('Asia/Bangkok');
    $today_notify = new DateTime(date('Y-m-d'));
    
    $sql_notify = "SELECT m.ma_date FROM ma_schedule m 
                   JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
                   WHERE m.is_done = 0";
    $res_notify = mysqli_query($conn, $sql_notify);

    if ($res_notify) {
        while ($row = mysqli_fetch_assoc($res_notify)) {
            $ma_date_obj = new DateTime(date('Y-m-d', strtotime($row['ma_date'])));
            $days = (int) $today_notify->diff($ma_date_obj)->format("%r%a");

            if ($days < 0) $cnt_overdue++;
            elseif ($days == 0) $cnt_today++;
            elseif ($days >= 1 && $days <= 7) $cnt_future++;
        }
    }
    $total_notify = $cnt_overdue + $cnt_today + $cnt_future;
}
?>

<link rel="stylesheet" href="css/sidebar.css">

<div class="sidebar-overlay"></div>

<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="images/logomaintdash1.png" alt="Logo" class="sidebar-logo">
        </div>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-item <?= $active_page == 'user_dashboard.php' ? 'active' : '' ?>">
            <a href="user_dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'pmproject_user.php' ? 'active' : '' ?>">
            <a href="pmproject_user.php" class="nav-link">
                <i class="fas fa-project-diagram"></i>
                <span>Preventive Maintenance</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'service_user.php' ? 'active' : '' ?>">
            <a href="service_user.php" class="nav-link">
                <i class="fas fa-tools"></i>
                <span>Service</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'product_user.php' ? 'active' : '' ?>">
            <a href="product_user.php" class="nav-link">
                <i class="fas fa-microchip"></i>
                <span>Product Claim</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'customers_user.php' ? 'active' : '' ?>">
            <a href="customers_user.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'warn_user.php' ? 'active' : '' ?>">
            <a href="warn_user.php" class="nav-link">
                <i class="fas fa-bell"></i>
                <span>Alarms</span>
                <?php if ($total_notify > 0): ?>
                    <span class="badge-count"><?= $total_notify ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <footer class="sidebar-footer">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>ออกจากระบบ</span>
        </a>
        <div class="user-status">
            <i class="fas fa-circle text-success" style="font-size: 0.5rem;"></i>
            <span>สิทธิ์: <?= $_SESSION['user_role'] ?? 'User' ?></span>
        </div>
    </footer>
</aside>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/sidebar.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notifyData = {
        overdue: <?= $cnt_overdue ?>,
        today: <?= $cnt_today ?>,
        future: <?= $cnt_future ?>,
        redirectUrl: 'warn_user.php'
    };
    
    if ((notifyData.overdue > 0 || notifyData.today > 0 || notifyData.future > 0) && '<?= $active_page ?>' !== 'warn_user.php') {
        showNotificationToast(notifyData);
    }
});
</script>