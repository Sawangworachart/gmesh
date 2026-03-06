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
    <title>MaintDash - Products</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/product_user.css">
</head>
<body>
    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="page-header-card">
            <h1 style="margin:0; font-size:1.8rem; color:#1e293b;">Product Claim</h1>
            <p style="margin:5px 0 0; color:#64748b; font-size:1rem;">ระบบจัดการและติดตามสถานะงานซ่อม ตั้งแต่รับอุปกรณ์จนถึงส่งคืนลูกค้า</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card grad-all">
                <div class="stat-label">ทั้งหมด</div><div class="stat-val"><?= number_format($stats['total']) ?></div>
                <i class="fas fa-layer-group fa-bg"></i>
            </div>
            <div class="stat-card grad-s1">
                <div class="stat-label">รอสินค้าจากลูกค้า</div><div class="stat-val"><?= number_format($stats['s1']) ?></div>
                <i class="fas fa-clock fa-bg"></i>
            </div>
            <div class="stat-card grad-s2">
                <div class="stat-label">ตรวจสอบ</div><div class="stat-val"><?= number_format($stats['s2']) ?></div>
                <i class="fas fa-search fa-bg"></i>
            </div>
            <div class="stat-card grad-s3">
                <div class="stat-label">รอสินค้าจาก supplier</div><div class="stat-val"><?= number_format($stats['s3']) ?></div>
                <i class="fas fa-truck-loading fa-bg"></i>
            </div>
            <div class="stat-card grad-s4">
                <div class="stat-label">ส่งคืนลูกค้า</div><div class="stat-val"><?= number_format($stats['s4']) ?></div>
                <i class="fas fa-check-double fa-bg"></i>
            </div>
        </div>

        <div class="toolbar-container">
            <div class="search-pill">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาอุปกรณ์, ลูกค้า, S/N..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="table-container">
            <div class="scroll-area">
                <table class="bordered-table" id="productTable">
                    <thead>
                        <tr>
                            <th width="5%">ลำดับ</th>
                            <th width="22%">ลูกค้า / เเผนก</th>
                            <th width="8%">อุปกรณ์ / S/N</th> <th width="15%" style="text-align: center;">สถานะ</th>
                            <th width="30%">รายละเอียดการซ่อม</th> <th width="15%">วันที่เริ่ม / วันที่สิ้นสุด</th>
                            <th width="5%" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($products)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:40px; color:#999;">ไม่พบข้อมูลอุปกรณ์ในระบบ</td></tr>
                        <?php else: foreach ($products as $idx => $row): ?>
                        <tr>
                            <td style="text-align: center; font-weight: bold;"><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($row['customer']) ?></strong><br><small style="color:#94a3b8;"><?= htmlspecialchars($row['department']) ?></small></td>
                            <td><strong style="color:var(--primary);"><?= htmlspecialchars($row['device_name']) ?></strong><br><small>S/N: <?= htmlspecialchars($row['sn'] ?: '-') ?></small></td>
                            <td style="text-align: center;"><span class="status-pill <?= $row['badge_class'] ?>"><?= $row['status_th'] ?></span></td>
                            <td style="color:#64748b;"><?= mb_strimwidth($row['symptom'], 0, 80, "...") ?></td>
                            <td>
                                <div class="date-info">
                                    <span style="color:#2563eb;"><i class="fas fa-play-circle"></i> <?= $row['start_date'] ?></span><br>
                                    <span style="color:#dc2626;"><i class="fas fa-flag-checkered"></i> <?= $row['end_date'] ?></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-view" onclick="viewDetail(<?= htmlspecialchars(json_encode($row)) ?>)">
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

    <div class="modal-overlay" id="viewModal" onclick="if(event.target == this) closeModal()">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="margin:0;"><i class="fas fa-file-invoice"></i> รายละเอียดอุปกรณ์</h3>
                <span style="cursor:pointer; font-size:1.8rem;" onclick="closeModal()">&times;</span>
            </div>
            <div style="padding:25px;" id="v_content"></div>
        </div>
    </div>
<script src="js/product_user.js"></script>
</body>
</html>