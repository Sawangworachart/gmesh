<?php
// หน้า sidebar ของ user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'auth.php'; 
require_once 'db.php';

$current_page = basename($_SERVER['PHP_SELF']);

// ================= USER NOTIFICATION (คงเดิมตามไฟล์ต้นฉบับ) =================
$notify_today   = 0; $notify_soon    = 0; $notify_overdue = 0;
$today = date('Y-m-d'); $next_week = date('Y-m-d', strtotime('+7 days'));

if (isset($conn)) {
    $sql_notify = "SELECT 
            SUM(CASE WHEN m.ma_date = '$today' THEN 1 ELSE 0 END) AS today_count,
            SUM(CASE WHEN m.ma_date > '$today' AND m.ma_date <= '$next_week' THEN 1 ELSE 0 END) AS soon_count,
            SUM(CASE WHEN m.ma_date < '$today' THEN 1 ELSE 0 END) AS overdue_count
        FROM ma_schedule m
        INNER JOIN pm_project p ON m.pmproject_id = p.pmproject_id
        WHERE m.is_done = 0";
    $res = mysqli_query($conn, $sql_notify);
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $notify_today = (int)$row['today_count'];
        $notify_soon = (int)$row['soon_count'];
        $notify_overdue = (int)$row['overdue_count'];
    }
}
$alert_count = $notify_today + $notify_soon + $notify_overdue;
?>

<?php
// sidebar_user.php - อัปเกรดระบบแจ้งเตือนให้เหมือนหน้า Admin (คงเดิมส่วน Sidebar)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'auth.php'; 

// 1. ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!isset($conn)) {
    require_once 'db.php';
}

$current_page = basename($_SERVER['PHP_SELF']);

// ================= [PART 1] NOTIFICATION LOGIC (เหมือน Admin) =================
$cnt_overdue = 0; 
$cnt_today = 0; 
$cnt_future = 0;
$total_notify = 0;

if (isset($conn)) {
    date_default_timezone_set('Asia/Bangkok');
    $today_notify = new DateTime(date('Y-m-d'));

    // SQL: นับเฉพาะงานที่ยังไม่เสร็จ (is_done = 0)
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

<style>
    /* --- CSS SIDEBAR เดิม (ห้ามเปลี่ยน) --- */
    :root { --sidebar-width: 250px; --toggle-btn-color: #2962ff; --sidebar-bg: #fff; }
    .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background: var(--sidebar-bg); box-shadow: 2px 0 10px rgba(0,0,0,0.05); z-index: 1100; overflow-y: auto; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .main-content { 
        margin-left: var(--sidebar-width) !important; 
        width: calc(100% - var(--sidebar-width)) !important; 
    }
    @media (max-width: 991px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0 !important; width: 100% !important; } body.sidebar-mobile-open .sidebar { transform: translateX(0); } }
    body.sidebar-collapsed .sidebar { transform: translateX(-100%); }
    body.sidebar-collapsed .main-content { margin-left: 0 !important; 
        width: 100% !important; }
    .menu-toggle-btn { position: fixed; top: 15px; left: 15px; width: 40px; height: 40px; z-index: 1200; background: var(--toggle-btn-color); color: #fff; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    @media (min-width: 992px) { .menu-toggle-btn { left: calc(var(--sidebar-width) - 50px); top: 10px; background: transparent; color: #555; } body.sidebar-collapsed .menu-toggle-btn { left: 20px; background: var(--toggle-btn-color); color: #fff; } }
    .sidebar-header { padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #eee; margin-top: 15px; }
    .nav-menu { list-style: none; padding: 0; margin: 10px 0; }
    .nav-menu li a { display: flex; align-items: center; gap: 10px; padding: 12px 25px; color: #555; text-decoration: none; font-weight: 500; border-left: 4px solid transparent; }
    .nav-menu li a:hover, .nav-menu li.active > a { background-color: #f8f9fa; color: var(--toggle-btn-color); border-left-color: var(--toggle-btn-color); }
    .sidebar-footer { position: absolute; bottom: 0; width: 100%; background: #f8f9fa; padding: 15px; border-top: 1px solid #eee; }
    .badge-alert { background: #dc3545; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; }
    .animated-logo { width: 100px; animation: logo-float 4s ease-in-out infinite; }
    @keyframes logo-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

    /* --- CSS NOTIFICATION ดีไซน์ใหม่ (เหมือน Admin) --- */
    .notify-toast {
        background: #ffffff !important;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15) !important;
        border-radius: 16px !important;
        padding: 0 !important;
        overflow: hidden;
        font-family: 'Sarabun', sans-serif !important;
        border: 1px solid #f1f5f9;
    }
    .notify-header {
        padding: 12px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-weight: 700;
        color: #334155;
        font-size: 1rem;
    }
    .notify-body {
        padding: 15px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .notify-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.95rem;
        padding-bottom: 8px;
        border-bottom: 1px dashed #f1f5f9;
    }
    .notify-row:last-child { border-bottom: none; padding-bottom: 0; }
    .notify-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
    }
    .badge-red { background-color: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
    .badge-orange { background-color: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
    .badge-yellow { background-color: #fefce8; color: #ca8a04; border: 1px solid #fef9c3; }
    .notify-count { font-weight: 700; font-size: 1rem; color: #475569; }
    .notify-count span { font-size: 0.8rem; font-weight: 400; color: #94a3b8; margin-left: 4px; }
</style>

<button id="mainSidebarToggleBtn" class="menu-toggle-btn" onclick="toggleMainSidebar()">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <img src="images/logomaintdash1.png" alt="Logo" class="animated-logo"> 
    </div>
    <ul class="nav-menu">
        <li class="<?= $current_page == 'user_dashboard.php' ? 'active' : '' ?>"><a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="<?= $current_page == 'pmproject_user.php' ? 'active' : '' ?>"><a href="pmproject_user.php"><i class="fas fa-project-diagram"></i> Preventive Maintenance</a></li>
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
        <a href="logout.php" class="logout-link" style="color: #dc3545; text-decoration: none; font-weight: 600;"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        <div class="status-bar" style="font-size: 0.8rem; color: #888; padding-top: 5px;"><i class="fas fa-user"></i> สถานะ: <?php echo isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'ผู้ใช้งาน'; ?></div>
    </div>
</div>

<div class="sidebar-overlay" onclick="toggleMainSidebar()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1050;"></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // LocalStorage Script (คงเดิม)
    (function() {
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed && window.innerWidth > 992) { document.body.classList.add('sidebar-collapsed'); }
    })();

    function toggleMainSidebar() {
        if (window.innerWidth > 992) {
            const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        } else {
            document.body.classList.toggle('sidebar-mobile-open');
            const overlay = document.querySelector('.sidebar-overlay');
            overlay.style.display = document.body.classList.contains('sidebar-mobile-open') ? 'block' : 'none';
        }
    }

    // --- ส่วนแสดงผล SweetAlert2 ดีไซน์เดียวกับ Admin ---
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