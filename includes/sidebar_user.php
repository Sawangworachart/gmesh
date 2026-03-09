<?php
// =========================================
// Sidebar (User)
// =========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/auth.php'; 
require_once __DIR__ . '/db.php';

$current_page = basename($_SERVER['PHP_SELF']);

// 1. Notification Logic
$cnt_overdue = 0; 
$cnt_today = 0; 
$cnt_future = 0;
$total_notify = 0;

if (isset($conn)) {
    date_default_timezone_set('Asia/Bangkok');
    $today_notify = new DateTime(date('Y-m-d'));

    // SQL: นับเฉพาะงานที่ยังไม่เสร็จ
    $sql_notify = "SELECT m.ma_date 
                   FROM ma_schedule m 
                   JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
                   WHERE m.is_done = 0";

    $res_notify = mysqli_query($conn, $sql_notify);

    if ($res_notify) {
        while ($row = mysqli_fetch_assoc($res_notify)) {
            $ma_date_str = date('Y-m-d', strtotime($row['ma_date']));
            $ma_date_obj = new DateTime($ma_date_str);

            $diff = $today_notify->diff($ma_date_obj);
            $days = (int) $diff->format("%r%a");

            if ($days < 0) {
                $cnt_overdue++;
            } elseif ($days == 0) {
                $cnt_today++;
            } elseif ($days >= 1 && $days <= 7) {
                $cnt_future++;
            }
        }
    }
    $total_notify = $cnt_overdue + $cnt_today + $cnt_future;
}
?>

<!-- Custom CSS for Sidebar User -->
<link rel="stylesheet" href="assets/css/sidebar_user.css?v=<?php echo time(); ?>">

<button id="mainSidebarToggleBtn" class="menu-toggle-btn" onclick="toggleMainSidebar()">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <img src="assets/images/logomaintdash1.png" alt="Logo" class="animated-logo"> 
    </div>
    <ul class="nav-menu">
        <li class="<?= $current_page == 'user_dashboard.php' ? 'active' : '' ?>"><a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="<?= $current_page == 'pmproject_user.php' ? 'active' : '' ?>"><a href="pmproject_user.php"><i class="fas fa-project-diagram"></i> PM Projects</a></li>
        <li class="<?= $current_page == 'pmschedule_user.php' ? 'active' : '' ?>"><a href="pmschedule_user.php"><i class="fas fa-calendar-alt"></i> PM Schedule</a></li>
        <li class="<?= $current_page == 'service_user.php' ? 'active' : '' ?>"><a href="service_user.php"><i class="fas fa-tools"></i> Service</a></li>
        <li class="<?= $current_page == 'product_user.php' ? 'active' : '' ?>"><a href="product_user.php"><i class="fas fa-microchip"></i> Product Claim</a></li>
        <li class="<?= $current_page == 'customers_user.php' ? 'active' : '' ?>"><a href="customers_user.php"><i class="fas fa-users"></i> Customers</a></li>
        <li class="<?= ($current_page == 'warn_user.php') ? 'active' : '' ?>">
            <a href="warn_user.php" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-bell"></i> Alarms</span>
                <?php if($total_notify > 0): ?> <span class="badge-alert"><?= $total_notify ?></span> <?php endif; ?>
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="includes/logout.php" class="logout-link" style="color: #dc3545; text-decoration: none; font-weight: 600;"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        <div class="status-bar" style="font-size: 0.8rem; color: #888; padding-top: 5px;"><i class="fas fa-user"></i> สถานะ: <?php echo isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'ผู้ใช้งาน'; ?></div>
    </div>
</div>

<div class="sidebar-overlay" onclick="toggleMainSidebar()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1050;"></div>

<!-- Custom JS for Sidebar User -->
<script src="assets/js/sidebar_user.js?v=<?php echo time(); ?>"></script>

<!-- SweetAlert2 Notification Logic -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let overdue = <?php echo $cnt_overdue; ?>;
        let today = <?php echo $cnt_today; ?>;
        let future = <?php echo $cnt_future; ?>;
        let currentPage = '<?php echo $current_page; ?>';

        if ((overdue > 0 || today > 0 || future > 0) && currentPage !== 'warn_user.php') {
            let htmlContent = '';

            if (today > 0) {
                htmlContent += `
                <div class="notify-row">
                    <div class="notify-badge badge-orange">
                        <i class="fas fa-calendar-check"></i> วันนี้
                    </div>
                    <div class="notify-count" style="color: #ea580c;">${today} <span>งาน</span></div>
                </div>`;
            }

            if (future > 0) {
                htmlContent += `
                <div class="notify-row">
                    <div class="notify-badge badge-yellow">
                        <i class="fas fa-hourglass-half"></i> เร็วๆ นี้ ภายใน 7 วัน
                    </div>
                    <div class="notify-count">${future} <span>งาน</span></div>
                </div>`;
            }

            if (overdue > 0) {
                htmlContent += `
                <div class="notify-row">
                    <div class="notify-badge badge-red">
                        <i class="fas fa-exclamation-circle"></i> เกินกำหนด
                    </div>
                    <div class="notify-count" style="color: #dc2626;">${overdue} <span>งาน</span></div>
                </div>`;
            }

            let headerIcon = '<i class="fas fa-bell" style="color: #dc2626;"></i>';
            let headerTitle = 'แจ้งเตือนงาน';

            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 8000,
                timerProgressBar: true,
                customClass: { popup: 'notify-toast' },
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                    toast.addEventListener('click', () => {
                        window.location.href = 'warn_user.php';
                    })
                }
            });

            Toast.fire({
                html: `
                <div class="notify-header">
                    ${headerIcon} ${headerTitle}
                </div>
                <div class="notify-body">
                    ${htmlContent}
                </div>`
            });
        }
    });
</script>
