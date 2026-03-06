<?php
// =========================================
// Sidebar (Admin)
// =========================================

include_once 'auth.php';

// 1. ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!isset($conn)) {
    if (file_exists('db.php')) {
        require_once 'db.php';
    } else if (file_exists('../db.php')) {
        require_once '../db.php';
    }
}

// 2. เริ่มคำนวณการแจ้งเตือน (Global Notification)
if (isset($conn)) {
    // ตั้งค่า Timezone
    date_default_timezone_set('Asia/Bangkok');
    $today_notify = new DateTime(date('Y-m-d'));

    // SQL: นับเฉพาะงานที่ยังไม่เสร็จ (is_done = 0)
    $sql_notify = "SELECT m.ma_date 
                   FROM ma_schedule m 
                   JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
                   WHERE p.status != 'Completed' 
                   AND m.is_done = 0";

    $res_notify = mysqli_query($conn, $sql_notify);

    // ตัวแปรนับจำนวนแยกตามประเภท
    $cnt_overdue = 0; // เกินกำหนด
    $cnt_today = 0; // ต้องทำวันนี้
    $cnt_future = 0; // เร็วๆนี้ (1-7 วัน)

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

    // 3. แสดงผล (ถ้ามีงาน และไม่อยู่หน้า warn_admin.php)
    if ($total_notify > 0 && basename($_SERVER['PHP_SELF']) != 'warn_admin.php') {
?>
        <!-- Load SweetAlert2 if not exists -->
        <script>
            if (typeof Swal === 'undefined') {
                document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"><\/script>');
            }
        </script>

        <!-- Notification Logic Script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let overdue = <?php echo $cnt_overdue; ?>;
                let today = <?php echo $cnt_today; ?>;
                let future = <?php echo $cnt_future; ?>;

                // สร้างเนื้อหา HTML ภายใน
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

                // เลือก Icon และข้อความหัวข้อหลัก
                let headerIcon = '';
                let headerTitle = 'สรุปงานซ่อมบำรุง';

                if (overdue > 0) {
                    headerIcon = '<i class="fas fa-bell" style="color: #dc2626;"></i>';
                    headerTitle = 'แจ้งเตือนงาน ';
                } else if (today > 0) {
                    headerIcon = '<i class="fas fa-clock" style="color: #ea580c;"></i>';
                    headerTitle = 'มีงานต้องทำวันนี้';
                } else {
                    headerIcon = '<i class="fas fa-info-circle" style="color: #ca8a04;"></i>';
                    headerTitle = 'แจ้งเตือนงานใกล้ถึง';
                }

                const Toast = Swal.mixin({
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 8000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'notify-toast'
                    },
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                        toast.addEventListener('click', () => {
                            window.location.href = 'warn_admin.php';
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
                    </div>
                `
                });
            });
        </script>
<?php
    }
}

// **************************************************************************
// [PART 2] SIDEBAR MENU LOGIC
// **************************************************************************

$active_page = basename($_SERVER['PHP_SELF']);

function is_active($page, $active_page)
{
    return (basename($page) === basename($active_page)) ? 'active' : '';
}

function is_active_parent($pages, $active_page)
{
    foreach ($pages as $page) {
        if (basename($page) === basename($active_page)) {
            return 'active-parent';
        }
    }
    return '';
}

$project_pages = ['pm_project.php', 'service_project.php'];
$is_project_active = is_active_parent($project_pages, $active_page);
?>

<!-- Custom CSS for Sidebar -->
<link rel="stylesheet" href="assets/css/sidebar.css?v=<?php echo time(); ?>">

<button id="mobile-menu-btn" class="mobile-menu-btn">
    <i class="fas fa-bars"></i>
</button>

<div id="mobile-overlay-backdrop"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="sidebar-toggle-btn" id="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>

        <div class="logo-container">
            <a href="dashboard.php">
                <img src="images/logomaintdash1.png" alt="LogoMaintDash" class="sidebar-logo">
            </a>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li class="<?php echo is_active('dashboard.php', $active_page); ?>">
            <a href="dashboard.php" title="Dashboard">
                <i class="fas fa-tachometer-alt"></i> <span class="link-text" data-i18n="dashboard">Dashboard</span>
            </a>
        </li>

        <li class="<?php echo is_active('pm_project.php', $active_page); ?>">
            <a href="pm_project.php" title="Preventive Maintenance">
                <i class="fas fa-project-diagram"></i> <span class="link-text" data-i18n="preventive_maintenance">Preventive Maintenance</span>
            </a>
        </li>
        
        <li class="<?php echo is_active('pm_schedule.php', $active_page); ?>">
            <a href="pm_schedule.php" title="PM Schedule">
                <i class="fas fa-calendar-alt"></i> <span class="link-text" data-i18n="pm_schedule">PM Schedule</span>
            </a>
        </li>

        <li class="<?php echo is_active('service_project.php', $active_page); ?>">
            <a href="service_project.php" title="Service">
                <i class="fas fa-tools"></i> <span class="link-text" data-i18n="service">Service</span>
            </a>
        </li>

        <li class="<?php echo is_active('product.php', $active_page); ?>">
            <a href="product.php" title="Product">
                <i class="fas fa-cogs"></i> <span class="link-text" data-i18n="products_service">Product Claim</span>
            </a>
        </li>

        <li class="<?php echo is_active('customers.php', $active_page); ?>">
            <a href="customers.php" title="Customers">
                <i class="fas fa-users"></i> <span class="link-text" data-i18n="customers">Customers</span>
            </a>
        </li>

        <li class="<?php echo is_active('warn_admin.php', $active_page); ?>">
            <a href="warn_admin.php" title="Alarms">
                <i class="fas fa-bell"></i> <span class="link-text" data-i18n="alarms">Alarms</span>
            </a>
        </li>
        
        <li class="<?php echo is_active('manage_users.php', $active_page); ?>">
            <a href="manage_users.php" title="Users">
                <i class="fas fa-user-friends"></i> <span class="link-text" data-i18n="users">Manage Users</span>
            </a>
        </li>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin') : ?>
            <li class="<?php echo is_active('manage_admin.php', $active_page); ?>">
                <a href="manage_admin.php" title="Edit Admin">
                    <i class="fas fa-user-cog"></i>
                    <span class="link-text" data-i18n="edit_user">Manage Admins</span>
                </a>
            </li>
        <?php endif; ?>

    </ul>

    <div class="sidebar-footer">
        <a href="#" id="logout-btn">
            <i class="fas fa-sign-out-alt"></i> <span class="link-text" data-i18n="logout">Logout</span>
        </a>
    </div>

    <div class="sidebar-user-status">
        <i class="fas fa-user-shield"></i>
        <span class="link-text" data-i18n="status">สถานะ:
            <?php echo isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'unknown'; ?>
    </div>
</div>

<!-- Logout Modal -->
<div id="custom-logout-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="logout-modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>

        <h2>ยืนยันการออกจากระบบ</h2>
        <p>คุณต้องการออกจากระบบใช่หรือไม่?</p>
        <div class="modal-actions">
            <button id="modal-cancel-btn" class="modal-btn cancel-btn">ยกเลิก</button>
            <button id="modal-logout-btn" class="modal-btn logout-btn">ยืนยัน</button>
        </div>
    </div>
</div>

<!-- Custom JS for Sidebar -->
<script src="assets/js/sidebar.js?v=<?php echo time(); ?>"></script>
