<?php
// sidebar.php (Admin)
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
                   WHERE p.status != 'Completed' AND m.is_done = 0";
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
        <li class="nav-item <?= $active_page == 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'pm_project.php' ? 'active' : '' ?>">
            <a href="pm_project.php" class="nav-link">
                <i class="fas fa-project-diagram"></i>
                <span>Preventive Maintenance</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'service_project.php' ? 'active' : '' ?>">
            <a href="service_project.php" class="nav-link">
                <i class="fas fa-tools"></i>
                <span>Service</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'product.php' ? 'active' : '' ?>">
            <a href="product.php" class="nav-link">
                <i class="fas fa-microchip"></i>
                <span>Product Claim</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'customers.php' ? 'active' : '' ?>">
            <a href="customers.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'manage_admin.php' ? 'active' : '' ?>">
            <a href="manage_admin.php" class="nav-link">
                <i class="fas fa-user-shield"></i>
                <span>User Management</span>
            </a>
        </li>
        <li class="nav-item <?= $active_page == 'warn_admin.php' ? 'active' : '' ?>">
            <a href="warn_admin.php" class="nav-link">
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
            <span>สิทธิ์: <?= $_SESSION['user_role'] ?? 'Admin' ?></span>
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
        redirectUrl: 'warn_admin.php'
    };
    
    if ((notifyData.overdue > 0 || notifyData.today > 0 || notifyData.future > 0) && '<?= $active_page ?>' !== 'warn_admin.php') {
        showNotificationToast(notifyData);
    }
});
</script>

    .submenu li a i {
        margin-top: 7px;
        margin-right: 10px;
    }

    .submenu li a:hover {
        background-color: rgba(255, 255, 255, 0.5);
        color: var(--primary-dark);
    }

    .submenu li.active a {
        background: rgba(255, 255, 255, 0.5);
        color: var(--primary-dark);
        font-weight: 600;
    }

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

    .sidebar-footer a:hover {
        background: rgba(231, 76, 60, 0.1);
    }

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

    .sidebar.collapsed .sidebar-nav {
        padding: 0;
    }

    .sidebar.collapsed .sidebar-nav li a {
        padding: 15px 0;
        justify-content: center;
        border-radius: 0;
    }

    .sidebar.collapsed .sidebar-nav i {
        margin-right: 0;
        font-size: 1.3rem;
    }

    .sidebar.collapsed .link-text,
    .sidebar.collapsed .fa-caret-down,
    .sidebar.collapsed .sidebar-footer div,
    .sidebar.collapsed .sidebar-user-status span {
        display: none;
    }

    .sidebar.collapsed .submenu {
        display: none !important;
    }

    .sidebar.collapsed .sidebar-user-status {
        justify-content: center;
        padding: 15px 0;
    }

    .sidebar.collapsed .sidebar-user-status i {
        margin-right: 0;
        padding-left: 0;
    }

    .sidebar.collapsed .sidebar-footer {
        padding: 15px 0 5px 0;
    }

    .sidebar.collapsed .sidebar-footer a {
        justify-content: center;
        padding: 10px 0;
    }

    .sidebar.collapsed .sidebar-footer a span {
        display: none;
    }

    .sidebar.collapsed .sidebar-footer a i {
        margin-right: 0;
    }

    .sidebar.collapsed .sidebar-logo {
        display: none;
    }

    .sidebar.collapsed .logo-container {
        display: none;
    }

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

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(3px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 2000;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 12px;
        width: 90%;
        max-width: 380px;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        transform: scale(0.9);
        transition: 0.3s;
        font-family: 'Sarabun', sans-serif;
    }

    .modal-overlay.active .modal-content {
        transform: scale(1);
    }

    .logout-modal-icon {
        font-size: 3rem;
        color: #f39c12;
        margin-bottom: 15px;
        text-align: center;
    }

    .modal-actions {
        margin-top: 25px;
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .modal-btn {
        padding: 10px 25px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-family: inherit;
        font-size: 0.95rem;
    }

    .cancel-btn {
        background: #edf2f7;
        color: #4a5568;
    }

    .logout-btn {
        background: var(--danger-color);
        color: white;
    }

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

    /* ===== Language Switch ===== */
    .lang-switch {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px;
        font-size: 12px;
        color: #64748b;
    }

    .switch {
        position: relative;
        width: 36px;
        height: 18px;
    }

    .switch input {
        display: none;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: #cbd5f5;
        border-radius: 999px;
        transition: .3s;
    }

    .slider::before {
        content: "";
        position: absolute;
        width: 14px;
        height: 14px;
        left: 2px;
        top: 2px;
        background: white;
        border-radius: 50%;
        transition: .3s;
    }

    .switch input:checked+.slider {
        background: #6366f1;
    }

    .switch input:checked+.slider::before {
        transform: translateX(18px);
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

    #mobile-overlay-backdrop.active {
        display: block;
    }

    @media (max-width: 768px) {
        .mobile-menu-btn {
            display: flex;
        }

        .sidebar {
            transform: translateX(-100%);
            width: 250px !important;
            box-shadow: none;
        }

        .sidebar.mobile-active {
            transform: translateX(0);
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-toggle-btn {
            display: none;
        }

        .sidebar-header {
            justify-content: center;
        }
    }
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

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin') : ?>
            <li class="<?php echo is_active('manage_admin.php', $active_page); ?>">
                <a href="manage_admin.php" title="Edit user">
                    <i class="fas fa-user-cog"></i>
                    <span class="link-text" data-i18n="edit_user">Edit user</span>
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

            $('.has-submenu > .toggle-btn').click(function(e) {
                e.preventDefault();
                if ($('.sidebar').hasClass('collapsed') && $(window).width() > 768) return;

                const parent = $(this).parent();
                const submenu = $(this).next('.submenu');

                $('.has-submenu').not(parent).find('.submenu').slideUp(250);
                $('.has-submenu').not(parent).removeClass('active-parent').find('.fa-caret-down').css('transform', 'rotate(0deg)');

                submenu.slideToggle(250);
                parent.toggleClass('active-parent');

                const icon = $(this).find('.fa-caret-down');
                if (parent.hasClass('active-parent')) {
                    icon.css('transform', 'rotate(180deg)');
                } else {
                    icon.css('transform', 'rotate(0deg)');
                }
            });

            if ($('.has-submenu').hasClass('active-parent')) {
                $('.has-submenu .submenu').show();
                $('.has-submenu .fa-caret-down').css('transform', 'rotate(180deg)');
            }

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