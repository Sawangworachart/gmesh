<?php
// pmschedule_user.php
session_start();
require_once 'db.php';

// ดึงข้อมูล Schedule จาก Database
$schedules = [];
// JOIN: pm_schedule -> customers (เอาชื่อลูกค้า), pm_schedule -> pm_project (เอาเลขสัญญาและชื่ออุปกรณ์ใน project)
$sql = "SELECT s.*, c.customers_name, p.number as contract_no, p.project_name
        FROM pm_schedule s
        LEFT JOIN customers c ON s.customers_id = c.customers_id
        LEFT JOIN pm_project p ON s.pmproject_id = p.pmproject_id
        ORDER BY s.next_scheduled ASC";
$result = mysqli_query($conn, $sql);

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        // ตรวจสอบวันแจ้งเตือน (เช่น ถ้าเหลือน้อยกว่า 30 วันให้เตือน)
        $alert_status = 'normal';
        $next_date = strtotime($row['next_scheduled']);
        $today = time();
        $diff_days = ($next_date - $today) / (60 * 60 * 24);
        
        if ($diff_days >= 0 && $diff_days <= 30) {
            $alert_status = 'near';
        } elseif ($diff_days < 0) {
            $alert_status = 'overdue';
        }

        $schedules[] = [
            'contract_no' => $row['contract_no'], 
            'customer_name' => $row['customers_name'], 
            'device' => $row['project_name'], // สมมติใช้ชื่อ Project แทน Device หรือต้องปรับ DB เพิ่ม column device
            'tor_year' => $row['tor_year'], 
            'visit_done' => $row['in_amount'], 
            'visit_left' => $row['left_amount'], 
            'next_visit' => date('d/m/Y', strtotime($row['next_scheduled'])), 
            'alert_status' => $alert_status
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><title>MaintDash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_user.css">

    <style>
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .header-title { font-size: 1.5rem; font-weight: 700; color: #3b82f6; display: flex; align-items: center; gap: 10px; }
        .table-container { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #fff; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background-color: #f8fafc; color: #64748b; padding: 15px; text-align: center; font-size: 0.9rem; font-weight: 600; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; }
        tbody td { padding: 15px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; color: #334155; vertical-align: middle; text-align: center; font-size: 0.95rem; }
        tbody td:nth-child(1), tbody td:nth-child(2) { text-align: left; }
        .contract-link { color: #3b82f6; font-weight: 600; text-decoration: none; font-size: 0.9rem; }
        .customer-name { font-weight: 700; display: block; font-size: 0.95rem; margin-bottom: 4px; color: #1e293b; }
        .device-name { font-size: 0.85rem; color: #64748b; }
        .visit-left { color: #10b981; font-weight: 700; }
        .badge-alert { background-color: #fee2e2; color: #ef4444; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: inline-block; margin-top: 5px; }
        .badge-overdue { background-color: #dc2626; color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: inline-block; margin-top: 5px; }
    </style>
</head>
<body>
    
    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="page-header"><div class="header-title"><i class="fas fa-clock"></i> PM Schedule & Alert</div></div>
        <div class="table-container">
            <table>
                <thead><tr><th width="15%">สัญญา</th><th width="35%">ลูกค้า/อุปกรณ์</th><th width="10%">TOR (ปี)</th><th width="10%">เข้าแล้ว</th><th width="10%">เหลือ</th><th width="20%">กำหนดครั้งถัดไป</th></tr></thead>
                <tbody>
                    <?php if(empty($schedules)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">ไม่พบข้อมูลตาราง PM</td></tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $row): ?>
                        <tr>
                            <td><a href="#" class="contract-link"><?= htmlspecialchars($row['contract_no']) ?></a></td>
                            <td><span class="customer-name"><?= htmlspecialchars($row['customer_name']) ?></span><span class="device-name"><?= htmlspecialchars($row['device']) ?></span></td>
                            <td><?= $row['tor_year'] ?></td><td><?= $row['visit_done'] ?></td><td class="visit-left"><?= $row['visit_left'] ?></td>
                            <td>
                                <strong><?= $row['next_visit'] ?></strong>
                                <?php if($row['alert_status'] == 'near'): ?>
                                    <br><span class="badge-alert">ใกล้ถึงกำหนด!</span>
                                <?php elseif($row['alert_status'] == 'overdue'): ?>
                                    <br><span class="badge-overdue">เลยกำหนด!</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="js/main_user.js"></script> 
</body>
</html>