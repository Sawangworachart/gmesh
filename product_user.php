<?php
// หน้า product ของ user
session_start();
include_once 'auth.php'; 
require_once 'db.php';

// --- 1. ดึงข้อมูลสถิติ (Stats) จากตารางเดียวกันกับ Admin (ดึงจาก TinyInt 1,2,3,4) ---
$stats = ['total' => 0, 's1' => 0, 's2' => 0, 's3' => 0, 's4' => 0];
$sql_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as s1,
                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as s2,
                SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as s3,
                SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as s4
              FROM product";
$res_stats = mysqli_query($conn, $sql_stats);
if($res_stats) {
    $row_s = mysqli_fetch_assoc($res_stats);
    $stats = [
        'total' => (int)$row_s['total'],
        's1' => (int)$row_s['s1'],
        's2' => (int)$row_s['s2'],
        's3' => (int)$row_s['s3'],
        's4' => (int)$row_s['s4']
    ];
}

// --- 2. ดึงข้อมูลสินค้า/อุปกรณ์ (Table) JOIN เพื่อดึงชื่อลูกค้าเหมือน Admin ---
$products = [];
$sql = "SELECT p.*, c.customers_name, c.agency 
        FROM product p 
        LEFT JOIN customers c ON p.customers_id = c.customers_id 
        ORDER BY p.product_id DESC";
$result = mysqli_query($conn, $sql);

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $status_val = (int)$row['status'];
        $status_th = 'ไม่ระบุ';
        $badgeClass = 'st-default';

        if ($status_val == 1) {
            $status_th = 'รอสินค้าจากลูกค้า';
            $badgeClass = 'st-product-s1'; 
        } elseif ($status_val == 2) {
            $status_th = 'ตรวจสอบ';
            $badgeClass = 'st-product-s2'; 
        } elseif ($status_val == 3) {
            $status_th = 'รอสินค้าจาก supplier';
            $badgeClass = 'st-product-s3'; 
        } elseif ($status_val == 4) {
            $status_th = 'ส่งคืนลูกค้า';
            $badgeClass = 'st-product-s4'; 
        }

        $start_date_fmt = (!empty($row['start_date']) && $row['start_date'] != '0000-00-00') ? date('d/m/Y', strtotime($row['start_date'])) : '-';
        $end_date_fmt = (!empty($row['end_date']) && $row['end_date'] != '0000-00-00') ? date('d/m/Y', strtotime($row['end_date'])) : '-';

        $products[] = [
            'id' => $row['product_id'],
            'customer' => $row['customers_name'] ?? 'ไม่ระบุลูกค้า',
            'department' => $row['agency'] ?? '-',
            'device_name' => $row['device_name'],
            'sn' => $row['serial_number'],
            'symptom' => $row['repair_details'],
            'file_path' => $row['file_path'], 
            'status_th' => $status_th,
            'badge_class' => $badgeClass,
            'start_date' => $start_date_fmt,
            'end_date' => $end_date_fmt
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - Product Claim</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/product.css">
</head>
<body>
    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Product Claim</h2>
                <p class="page-subtitle">ติดตามสถานะงานซ่อมของคุณ</p>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon-box bg-all"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info"><p>ทั้งหมด</p><h3><?= number_format($stats['total']) ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-s1"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><p>รอสินค้า</p><h3><?= number_format($stats['s1']) ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-s2"><i class="fas fa-search"></i></div>
                <div class="stat-info"><p>ตรวจสอบ</p><h3><?= number_format($stats['s2']) ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-s3"><i class="fas fa-truck-loading"></i></div>
                <div class="stat-info"><p>รออะไหล่</p><h3><?= number_format($stats['s3']) ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-s4"><i class="fas fa-check-double"></i></div>
                <div class="stat-info"><p>ส่งคืน</p><h3><?= number_format($stats['s4']) ?></h3></div>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาอุปกรณ์, ลูกค้า, S/N..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table" id="productTable">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>ลูกค้า / แผนก</th>
                            <th>อุปกรณ์ / S/N</th>
                            <th class="text-center">สถานะ</th>
                            <th>รายละเอียด</th>
                            <th>วันที่เริ่ม / สิ้นสุด</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($products)): ?>
                            <tr><td colspan="7" class="text-center p-5 text-muted">ไม่พบข้อมูล</td></tr>
                        <?php else: foreach ($products as $idx => $row): ?>
                        <tr>
                            <td class="text-center"><?= $idx + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['customer']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($row['department']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['device_name']) ?></strong><br>
                                <small class="text-muted">S/N: <?= htmlspecialchars($row['sn'] ?: '-') ?></small>
                            </td>
                            <td class="text-center"><span class="status-badge <?= $row['badge_class'] ?>"><?= $row['status_th'] ?></span></td>
                            <td><?= mb_strimwidth($row['symptom'], 0, 80, "...") ?></td>
                            <td>
                                <div><i class="fas fa-play-circle text-primary"></i> <?= $row['start_date'] ?></div>
                                <div><i class="fas fa-flag-checkered text-danger"></i> <?= $row['end_date'] ?></div>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick='viewDetail(<?= htmlspecialchars(json_encode($row)) ?>)'>
                                    <i class="far fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">รายละเอียดอุปกรณ์</h3>
                <button class="btn-close" onclick="closeModal()"></button>
            </div>
            <div class="modal-body" id="v_content"></div>
        </div>
    </div>

    <script src="js/product_user.js"></script>
</body>
</html>