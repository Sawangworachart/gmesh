<?php
/**
 * ไฟล์: customers_user.php
 * คำอธิบาย: หน้าแสดงรายชื่อลูกค้าสำหรับผู้ใช้งานทั่วไป (User View)
 * รองรับการแสดงผลแบบกลุ่ม (Accordion) และการค้นหาแบบ Real-time
 */

session_start();
include_once 'auth.php'; 
require_once 'db.php';

// ===========================================================================
// ส่วนที่ 1: AJAX BACKEND (API Handler)
// ===========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    
    // 1. ดึงข้อมูลลูกค้าทั้งหมด
    $customers_by_group = [];
    $sql_cus = "SELECT * FROM customers ORDER BY customers_name ASC";
    $res_cus = mysqli_query($conn, $sql_cus);
    
    if ($res_cus) {
        while ($cus = mysqli_fetch_assoc($res_cus)) {
            $gid = $cus['group_id'];
            if (!empty($gid)) {
                $customers_by_group[$gid][] = $cus;
            } else {
                $customers_by_group['uncategorized'][] = $cus;
            }
        }
    }

    // 2. ดึงข้อมูลกลุ่ม
    $sql_groups = "SELECT * FROM customer_groups ORDER BY group_id ASC";
    $res_groups = mysqli_query($conn, $sql_groups);
    
    $has_data = false;

    // --- วนลูปสร้าง HTML สำหรับแต่ละกลุ่ม ---
    if ($res_groups) {
        while ($group = mysqli_fetch_assoc($res_groups)) {
            $gid = $group['group_id'];
            $gname = $group['group_name'];
            $customers_in_group = isset($customers_by_group[$gid]) ? $customers_by_group[$gid] : [];
            $count = count($customers_in_group);
            $has_data = true;
    ?>
        <tr class="group-header" onclick="toggleGroup('group-<?= $gid ?>', this)" data-group-id="group-<?= $gid ?>">
            <td colspan="6">
                <div class="header-content">
                    <div class="company-info">
                        <div class="folder-icon"><i class="fas fa-folder"></i></div>
                        <span><?= htmlspecialchars($gname) ?> <span style="color:#94a3b8; font-weight:400; font-size:0.9rem;">(<?= $count ?>)</span></span>
                    </div>
                    <div class="header-actions">
                        <i class="fas fa-chevron-down arrow-icon"></i>
                    </div>
                </div>
            </td>
        </tr>

        <?php if ($count > 0): ?>
            <?php foreach ($customers_in_group as $row): ?>
            <tr class="group-item group-<?= $gid ?> customer-row" style="display:none;">
                <td class="tree-line-cell">
                    <div class="tree-line-indicator"></div>
                </td>
                <td>
                    <span style="font-weight:600; color:#1e293b; font-size:1.05rem;"><?= htmlspecialchars($row['customers_name']) ?></span><br>
                    <?php if(!empty($row['agency'])): ?>
                        <span class="badge-agency"><i class="fas fa-briefcase"></i> <?= htmlspecialchars($row['agency']) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="contact-info">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($row['contact_name']) ?>
                    </div>
                </td>
                <td>
                    <a href="tel:<?= htmlspecialchars($row['phone']) ?>" class="phone-link">
                        <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($row['phone']) ?>
                    </a>
                </td>
                <td>
                    <span class="address-text"><?= htmlspecialchars($row['address']) ?></span>
                </td>
                <td>
                    <span class="province-tag"><?= htmlspecialchars($row['province']) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr class="group-item group-<?= $gid ?>" style="display:none;">
                <td class="tree-line-cell"><div class="tree-line-indicator"></div></td>
                <td colspan="5" style="color:#94a3b8; font-style:italic; padding:15px; text-align:center;">- ไม่มีข้อมูลลูกค้าในกลุ่มนี้ -</td>
            </tr>
        <?php endif; ?>

    <?php 
        } // จบ while
    } // จบ if res_groups

    // --- ส่วนลูกค้าที่ไม่มีกลุ่ม (Uncategorized) ---
    if (isset($customers_by_group['uncategorized']) && count($customers_by_group['uncategorized']) > 0) {
        $uncat_list = $customers_by_group['uncategorized'];
        $u_count = count($uncat_list);
        $has_data = true;
    ?>
        <tr class="group-header" onclick="toggleGroup('group-uncat', this)" data-group-id="group-uncat">
            <td colspan="6">
                <div class="header-content">
                    <div class="company-info">
                        <div class="folder-icon" style="background:#f1f5f9; color:#64748b;"><i class="fas fa-folder-open"></i></div>
                        <span>ไม่ระบุกลุ่ม <span style="color:#94a3b8; font-weight:400; font-size:0.9rem;">(<?= $u_count ?>)</span></span>
                    </div>
                    <div class="header-actions"><i class="fas fa-chevron-down arrow-icon"></i></div>
                </div>
            </td>
        </tr>
        <?php foreach ($uncat_list as $row): ?>
            <tr class="group-item group-uncat customer-row" style="display:none;">
                <td class="tree-line-cell"><div class="tree-line-indicator"></div></td>
                <td>
                    <span style="font-weight:600; color:#1e293b;"><?= htmlspecialchars($row['customers_name']) ?></span><br>
                    <span class="badge-agency"><?= htmlspecialchars($row['agency']) ?></span>
                </td>
                <td><div class="contact-info"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($row['contact_name']) ?></div></td>
                <td><a href="tel:<?= htmlspecialchars($row['phone']) ?>" class="phone-link"><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($row['phone']) ?></a></td>
                <td><span class="address-text"><?= htmlspecialchars($row['address']) ?></span></td>
                <td><span class="province-tag"><?= htmlspecialchars($row['province']) ?></span></td>
            </tr>
        <?php endforeach; ?>
    <?php } ?>

    <?php if (!$has_data): ?>
        <tr><td colspan="6" class="text-center" style="padding:40px; color:#94a3b8;">ไม่พบข้อมูลลูกค้าในระบบ</td></tr>
    <?php endif; ?>

    <?php
    exit; // จบการทำงาน AJAX
}

// ===========================================================================
// ส่วนที่ 2: หน้าจอแสดงผล (HTML Main Page)
// ===========================================================================

// นับจำนวนกลุ่มทั้งหมด
$total_groups = 0;
$count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM customer_groups");
if($count_row = mysqli_fetch_assoc($count_res)) {
    $total_groups = $count_row['total'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Customers Directory - MaintDash</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- External Libs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/customers_user.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar_user.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="page-header-card animate-zoom">
            <div class="header-title-group">
                <h1>Customers Directory</h1>
                <div class="header-subtitle">รายชื่อลูกค้า แผนก และข้อมูลการติดต่อแยกตามกลุ่มองค์กร</div>
            </div>
        </div>

        <!-- Hero Card (Stats) -->
        <div class="hero-card animate-zoom">
            <div class="hero-info">
                <h3 id="totalCountDisplay"><?= number_format($total_groups); ?></h3>
                <span>กลุ่มลูกค้าทั้งหมด (Organization Groups)</span>
            </div>
            <i class="fas fa-building hero-icon"></i>
        </div>
        
        <!-- Toolbar -->
        <div class="toolbar animate-zoom">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อลูกค้า, เบอร์โทร, หรือจังหวัด..." onkeyup="filterTable()">
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive animate-zoom">
            <table class="grouped-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th> 
                        <th style="width: 30%;">ชื่อองค์กร / แผนก</th> 
                        <th style="width: 15%;">ผู้ติดต่อ</th> 
                        <th style="width: 15%;">เบอร์โทรศัพท์</th>
                        <th style="width: 25%;">ที่อยู่</th>
                        <th style="width: 10%;">จังหวัด</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                     <tr><td colspan="6" class="text-center" style="padding:30px; color:#94a3b8;"><i class="fas fa-circle-notch fa-spin"></i> กำลังโหลดข้อมูล...</td></tr>
                </tbody>
            </table>
        </div>

    </div>
    
    <!-- Custom JS -->
    <script src="assets/js/customers_user.js"></script>

</body>
</html>
