<?php
// =========================================
// หน้าตาราง PM (User) - PM Schedule User View
// =========================================

session_start();
require_once 'includes/db.php'; // เชื่อมต่อฐานข้อมูล

// ดึงข้อมูล Schedule จาก Database
$schedules = [];

// JOIN Tables:
// 1. pm_schedules (ตารางหลัก)
// 2. Projects (เอาเลขสัญญาและชื่อ Project)
// 3. customers (เอาชื่อลูกค้า)
// Note: ปรับชื่อตารางและฟิลด์ให้ตรงกับ Admin (pm_schedules, Projects, customers)
$sql = "
    SELECT 
        ps.*, 
        p.contract_number AS contract_no, 
        p.project_name,
        c.customer_name 
    FROM 
        pm_schedules ps
    LEFT JOIN 
        Projects p ON ps.contract_id = p.contract_id
    LEFT JOIN 
        customers c ON p.customer_id = c.customer_id
    ORDER BY 
        ps.next_visit_date ASC";

$result = mysqli_query($conn, $sql);

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        // ตรวจสอบวันแจ้งเตือน
        $alert_status = 'normal';
        $next_date = strtotime($row['next_visit_date']);
        $today = time();
        $diff_days = ($next_date - $today) / (60 * 60 * 24);
        
        // เงื่อนไขแจ้งเตือน
        if ($diff_days >= 0 && $diff_days <= 30) {
            $alert_status = 'near'; // ใกล้ถึงกำหนด (ภายใน 30 วัน)
        } elseif ($diff_days < 0) {
            $alert_status = 'overdue'; // เลยกำหนด
        }

        // คำนวณรอบที่เหลือ
        $visit_left = $row['tor_visits_per_year'] - $row['visits_done'];

        $schedules[] = [
            'contract_no' => $row['contract_no'], 
            'customer_name' => $row['customer_name'], 
            'device' => $row['device_equipment'], // ใช้ชื่ออุปกรณ์จากตาราง pm_schedules
            'tor_year' => $row['tor_visits_per_year'], 
            'visit_done' => $row['visits_done'], 
            'visit_left' => $visit_left, 
            'next_visit' => date('d/m/Y', strtotime($row['next_visit_date'])), 
            'alert_status' => $alert_status
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - PM Schedule</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logomaintdash1.png">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/pmschedule_user.css?v=<?php echo time(); ?>">
    
    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>
    
    <?php include 'includes/sidebar_user.php'; ?> <!-- เมนูด้านข้าง -->

    <div class="main-content">
        
        <!-- Header -->
        <div class="page-header">
            <div class="header-title">
                <div class="header-icon"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <h1 style="margin:0; font-size:1.8rem;">PM Schedule</h1>
                    <p style="margin:4px 0 0; font-size:0.95rem; color:#64748b; font-weight:400;">ตารางการเข้าบำรุงรักษาตามสัญญา</p>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="15%">สัญญา</th>
                        <th width="35%">ลูกค้า / อุปกรณ์</th>
                        <th width="10%" class="text-center">TOR (ปี)</th>
                        <th width="10%" class="text-center">เข้าแล้ว</th>
                        <th width="10%" class="text-center">เหลือ</th>
                        <th width="20%">กำหนดครั้งถัดไป</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($schedules)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">
                                <i class="far fa-calendar-times fa-3x" style="margin-bottom:10px;"></i><br>ไม่พบข้อมูลตาราง PM
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $row): ?>
                        <tr>
                            <td>
                                <a href="#" class="contract-link"><?= htmlspecialchars($row['contract_no']) ?></a>
                            </td>
                            <td>
                                <span class="customer-name"><?= htmlspecialchars($row['customer_name']) ?></span>
                                <span class="device-name"><i class="fas fa-microchip"></i> <?= htmlspecialchars($row['device']) ?></span>
                            </td>
                            <td class="text-center"><?= $row['tor_year'] ?></td>
                            <td class="text-center"><?= $row['visit_done'] ?></td>
                            <td class="text-center"><span class="visit-left"><?= $row['visit_left'] ?></span></td>
                            <td>
                                <div style="font-weight:700; color:#1e293b;"><?= $row['next_visit'] ?></div>
                                <?php if($row['alert_status'] == 'near'): ?>
                                    <span class="badge-alert"><i class="fas fa-exclamation-circle"></i> ใกล้ถึงกำหนด!</span>
                                <?php elseif($row['alert_status'] == 'overdue'): ?>
                                    <span class="badge-overdue"><i class="fas fa-exclamation-triangle"></i> เลยกำหนด!</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="assets/js/pmschedule_user.js?v=<?php echo time(); ?>"></script> 
</body>
</html>
