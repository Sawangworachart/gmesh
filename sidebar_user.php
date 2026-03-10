<?php
// หน้า sidebar ของ user (ปรับปรุง UI ให้เหมือน Admin)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'auth.php'; 
require_once 'db.php';

$active_page = basename($_SERVER['PHP_SELF']);

function is_active($page, $active_page)
{
    return (basename($page) === basename($active_page)) ? 'active' : '';
}

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
    
    // แสดงผลแจ้งเตือนแบบ Toast (เฉพาะถ้ามีงาน และไม่อยู่หน้า warn_user.php)
    if ($total_notify > 0 && basename($_SERVER['PHP_SELF']) != 'warn_user.php') {
?>
        <script>
            if (typeof Swal === 'undefined') {
                document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"><\/script>');
            }
        </script>

        <style>
            /* Design Container */
            .notify-toast {
                background: #ffffff !important;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15) !important;
                border-radius: 16px !important;
                padding: 0 !important;
                overflow: hidden;
                font-family: 'Sarabun', sans-serif !important;
                border: 1px solid #f1f5f9;
            }

            /* ส่วนหัว */
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

            /* ส่วนเนื้อหา */
            .notify-body {
                padding: 15px 20px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            /* แถวรายการ */
            .notify-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.95rem;
                padding-bottom: 8px;
                border-bottom: 1px dashed #f1f5f9;
            }

            .notify-row:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }

            /* ดีไซน์ป้าย (Badge) */
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

            /* สีของป้ายแต่ละประเภท */
            .badge-red {
                background-color: #fef2f2;
                color: #dc2626;
                border: 1px solid #fee2e2;
            }

            .badge-orange {
                background-color: #fff7ed;
                color: #ea580c;
                border: 1px solid #ffedd5;
                box-shadow: 0 0 5px rgba(234, 88, 12, 0.2);
            }

            .badge-yellow {
                background-color: #fefce8;
                color: #ca8a04;
                border: 1px solid #fef9c3;
            }

            /* ตัวเลข */
            .notify-count {
                font-weight: 700;
                font-size: 1rem;
                color: #475569;
            }

            .notify-count span {
                font-size: 0.8rem;
                font-weight: 400;
                color: #94a3b8;
                margin-left: 4px;
            }
        </style>

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
                    </div>
                `
                });
            });
        </script>
<?php
    }
}
?>

<style>
    /* CSS STYLES - Design Sidebar (Copy from Admin sidebar.php) */
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap');

    :root {
        --primary-color: #5599ff;
        --primary-dark: #3a75c4;
        --sidebar-width: 250px;
        --sidebar-collapsed-width: 70px;
        --sidebar-color: #555;
        --highlight-color: rgba(255, 255, 255, 0.6);
        --active-color: var(--primary-color);
        --text-white: #ffffff;
        --danger-color: #e74c3c;
    }

    /* หมายเหตุ: body rule ถูกลบออกเพื่อให้ไม่กระทบกับหน้าหลัก */

    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    @keyframes logoFloat {
        0% { transform: translateY(0px); filter: drop-shadow(0 5px 15px rgba(85, 153, 255, 0.2)); }
        50% { transform: translateY(-8px); filter: drop-shadow(0 20px 20px rgba(85, 153, 255, 0.4)); }
        100% { transform: translateY(0px); filter: drop-shadow(0 5px 15px rgba(85, 153, 255, 0.2)); }
    }

    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: linear-gradient(-45deg, #ffffff, #e3f2fd, #f0f8ff, #ffffff);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        color: var(--sidebar-color);
        z-index: 1000;
        transition: transform 0.3s ease, width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-x: hidden;
        overflow-y: auto;
        box-shadow: 2px 0 15px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        padding: 20px 0;
        flex-shrink: 0;
    }

    .sidebar::-webkit-scrollbar { width: 5px; }
    .sidebar::-webkit-scrollbar-thumb { background: rgba(204, 214, 224, 0.5); border-radius: 10px; }
    .sidebar::-webkit-scrollbar-track { background: transparent; }
    .sidebar.collapsed { width: var(--sidebar-collapsed-width); }

    .sidebar-header {
        position: relative;
        padding-top: 25px;
        padding-bottom: 20px;
        text-align: center;
        min-height: 80px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 10px;
    }

    .logo-container {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .sidebar-logo {
        width: 100px;
        height: auto;
        display: block;
        object-fit: contain;
        animation: logoFloat 4s ease-in-out infinite;
        z-index: 2;
    }

    .sidebar-toggle-btn {
        position: absolute;
        top: 10px;
        right: 15px;
        width: 35px;
        height: 35px;
        background: transparent;
        border: none;
        color: #555;
        font-size: 1.5rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        z-index: 10;
    }

    .sidebar-toggle-btn:hover { color: var(--primary-dark); transform: scale(1.1); }
    .sidebar h2 { display: none; }

    .sidebar-nav {
        list-style: none;
        padding: 0 15px;
        margin: 15px 0 10px 0;
        flex-grow: 1;
    }

    .sidebar-nav li a {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        color: var(--sidebar-color);
        text-decoration: none;
        font-size: 1rem;
        transition: all 0.2s;
        border-radius: 8px;
        white-space: nowrap;
        background: rgba(255, 255, 255, 0.0);
    }

    .sidebar-nav li a:hover {
        background-color: var(--highlight-color);
        color: var(--primary-dark);
        transform: translateX(3px);
    }

    .sidebar-nav li.active a {
        background: var(--primary-color);
        color: var(--text-white);
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(85, 153, 255, 0.4);
    }

    .sidebar-nav i {
        min-width: 30px;
        font-size: 1.1rem;
        text-align: center;
        margin-right: 10px;
    }

    .sidebar-nav li.active a i { color: var(--text-white); }
    .sidebar-nav li a:not(.active) i { color: var(--primary-dark); }

    .sidebar-footer {
        padding: 15px 15px 5px 15px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        background: transparent;
    }

    .sidebar-footer a {
        display: flex;
        align-items: center;
        color: var(--danger-color);
        text-decoration: none;
        padding: 10px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .sidebar-footer a:hover { background: rgba(231, 76, 60, 0.1); }

    .sidebar-user-status {
        padding: 10px 15px 20px 15px;
        background: transparent;
        color: #777;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        cursor: default;
    }

    .sidebar-user-status i {
        min-width: 30px;
        text-align: center;
        margin-right: 10px;
        color: var(--primary-dark);
        padding-left: 0;
    }

    /* Collapsed State */
    .sidebar.collapsed { width: var(--sidebar-collapsed-width); }
    .sidebar.collapsed .sidebar-nav { padding: 0; }
    .sidebar.collapsed .sidebar-nav li a { padding: 15px 0; justify-content: center; border-radius: 0; }
    .sidebar.collapsed .sidebar-nav i { margin-right: 0; font-size: 1.3rem; }
    
    .sidebar.collapsed .link-text,
    .sidebar.collapsed .sidebar-footer div,
    .sidebar.collapsed .sidebar-user-status span { display: none; }
    
    .sidebar.collapsed .sidebar-user-status { justify-content: center; padding: 15px 0; }
    .sidebar.collapsed .sidebar-user-status i { margin-right: 0; padding-left: 0; }
    .sidebar.collapsed .sidebar-footer { padding: 15px 0 5px 0; }
    .sidebar.collapsed .sidebar-footer a { justify-content: center; padding: 10px 0; }
    .sidebar.collapsed .sidebar-footer a span { display: none; }
    .sidebar.collapsed .sidebar-footer a i { margin-right: 0; }
    .sidebar.collapsed .sidebar-logo { display: none; }
    .sidebar.collapsed .logo-container { display: none; }
    
    .sidebar.collapsed .sidebar-toggle-btn {
        position: static;
        margin: 0 auto;
        display: flex;
    }
    
    .sidebar.collapsed .sidebar-header {
        padding: 15px 0;
        display: flex;
        justify-content: center;
        min-height: auto;
        border-bottom: none;
    }

    /* Mobile Styles */
    .mobile-menu-btn {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1100;
        background: var(--primary-color);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    #mobile-overlay-backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        backdrop-filter: blur(2px);
    }
    
    #mobile-overlay-backdrop.active { display: block; }

    @media (max-width: 768px) {
        .mobile-menu-btn { display: flex; }
        .sidebar { transform: translateX(-100%); width: 250px !important; box-shadow: none; }
        .sidebar.mobile-active { transform: translateX(0); box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1); }
        .sidebar-toggle-btn { display: none; }
        .sidebar-header { justify-content: center; }
    }
    
    /* Custom Modal */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(3px);
        display: none; justify-content: center; align-items: center;
        z-index: 2000; opacity: 0; transition: opacity 0.3s;
    }
    .modal-overlay.active { display: flex; opacity: 1; }
    .modal-content {
        background: white; padding: 30px; border-radius: 12px;
        width: 90%; max-width: 380px; text-align: center;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        transform: scale(0.9); transition: 0.3s;
        font-family: 'Sarabun', sans-serif;
    }
    .modal-overlay.active .modal-content { transform: scale(1); }
    .logout-modal-icon { font-size: 3rem; color: #f39c12; margin-bottom: 15px; text-align: center; }
    .modal-actions { margin-top: 25px; display: flex; justify-content: center; gap: 10px; }
    .modal-btn { padding: 10px 25px; border: none; border-radius: 6px; cursor: pointer; font-family: inherit; font-size: 0.95rem; }
    .cancel-btn { background: #edf2f7; color: #4a5568; }
    .logout-btn { background: var(--danger-color); color: white; }
</style>

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
            <a href="user_dashboard.php">
                <img src="images/logomaintdash1.png" alt="LogoMaintDash" class="sidebar-logo">
            </a>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li class="<?php echo is_active('user_dashboard.php', $active_page); ?>">
            <a href="user_dashboard.php" title="Dashboard">
                <i class="fas fa-tachometer-alt"></i> <span class="link-text">Dashboard</span>
            </a>
        </li>

        <li class="<?php echo is_active('pmproject_user.php', $active_page); ?>">
            <a href="pmproject_user.php" title="Preventive Maintenance">
                <i class="fas fa-project-diagram"></i> <span class="link-text">Preventive Maintenance</span>
            </a>
        </li>

        <li class="<?php echo is_active('service_user.php', $active_page); ?>">
            <a href="service_user.php" title="Service">
                <i class="fas fa-tools"></i> <span class="link-text">Service</span>
            </a>
        </li>

        <li class="<?php echo is_active('product_user.php', $active_page); ?>">
            <a href="product_user.php" title="Product">
                <i class="fas fa-cogs"></i> <span class="link-text">Product Claim</span>
            </a>
        </li>

        <li class="<?php echo is_active('customers_user.php', $active_page); ?>">
            <a href="customers_user.php" title="Customers">
                <i class="fas fa-users"></i> <span class="link-text">Customers</span>
            </a>
        </li>

        <li class="<?php echo is_active('warn_user.php', $active_page); ?>">
            <a href="warn_user.php" title="Alarms">
                <i class="fas fa-bell"></i> <span class="link-text">Alarms</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="#" id="logout-btn">
            <i class="fas fa-sign-out-alt"></i> <span class="link-text">Logout</span>
        </a>
    </div>

    <div class="sidebar-user-status">
        <i class="fas fa-user-shield"></i>
        <span class="link-text">สถานะ:
            <?php echo isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'User'; ?>
    </div>
</div>

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

<script>
    if (typeof jQuery == 'undefined') {
        console.error("jQuery is required.");
    } else {
        $(document).ready(function() {
            function adjustContent(isCollapsed) {
                if ($(window).width() <= 768) {
                    $('.main-content').css('margin-left', '0px');
                    return;
                }
                const width = isCollapsed ? '70px' : '250px';
                $('.main-content').css({
                    'margin-left': width,
                    'transition': 'margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1)'
                });
            }

            // Init State
            adjustContent($('#sidebar').hasClass('collapsed'));

            $('#mobile-menu-btn').click(function() {
                $('#sidebar').addClass('mobile-active');
                $('#mobile-overlay-backdrop').addClass('active');
            });

            $('#mobile-overlay-backdrop').click(function() {
                $('#sidebar').removeClass('mobile-active');
                $('#mobile-overlay-backdrop').removeClass('active');
            });

            $('#sidebar-toggle').click(function() {
                const sidebar = $('.sidebar');
                sidebar.toggleClass('collapsed');

                const isCollapsed = sidebar.hasClass('collapsed');
                adjustContent(isCollapsed);

                if (isCollapsed) {
                    $(this).find('i').removeClass('fa-bars').addClass('fa-chevron-right');
                } else {
                    $(this).find('i').removeClass('fa-chevron-right').addClass('fa-bars');
                }
            });

            $(window).resize(function() {
                if ($(window).width() > 768) {
                    $('#sidebar').removeClass('mobile-active');
                    $('#mobile-overlay-backdrop').removeClass('active');
                }
                adjustContent($('#sidebar').hasClass('collapsed'));
            });

            // Logout Modal Logic
            $('#logout-btn').click(function(e) {
                e.preventDefault();
                $('#custom-logout-modal').addClass('active');
            });

            $('#modal-cancel-btn').click(function() {
                $('#custom-logout-modal').removeClass('active');
            });

            $('#modal-logout-btn').click(function() {
                window.location.href = 'logout.php';
            });

            $('#custom-logout-modal').click(function(e) {
                if ($(e.target).is(this)) {
                    $(this).removeClass('active');
                }
            });
        });
    }
</script>
