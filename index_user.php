<?php
/**
 * ไฟล์: index_user.php
 * คำอธิบาย: หน้า Dashboard สำหรับผู้ใช้งานทั่วไป (User)
 * แสดงรายการแจ้งเตือน MA ที่ใกล้ถึงกำหนด (ภายใน 7 วัน)
 */

session_start();
include_once 'includes/auth.php'; 
require_once 'includes/db.php';   

// ตั้งค่าโซนเวลา
date_default_timezone_set('Asia/Bangkok');

// ตัวแปรสำหรับเก็บข้อมูลแจ้งเตือน
$all_notifications = [];

// ==========================================================================================
// 1. DATABASE QUERY (ดึงข้อมูลงาน MA ที่จะถึงกำหนดใน 7 วัน)
// ==========================================================================================
$today = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));

// Query ข้อมูลจากตาราง ma_schedule เชื่อมกับ pm_project และ customers
$sql_ma = "SELECT m.ma_id, m.ma_date, m.note, p.project_name, c.customers_name 
           FROM ma_schedule m 
           JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
           LEFT JOIN customers c ON p.customers_id = c.customers_id
           WHERE m.ma_date BETWEEN '$today' AND '$next_week'
           ORDER BY m.ma_date ASC";

$result_ma = mysqli_query($conn, $sql_ma);

if ($result_ma) {
    while ($row = mysqli_fetch_assoc($result_ma)) {
        
        // คำนวณจำนวนวันคงเหลือ
        $ma_time = strtotime($row['ma_date']);
        $today_time = strtotime($today);
        $diff_seconds = $ma_time - $today_time;
        $days_left = floor($diff_seconds / (60 * 60 * 24));

        // กำหนดสีและข้อความตามความเร่งด่วน
        $badge_color = 'bg-info text-dark';
        $time_text = "อีก " . $days_left . " วัน";

        if ($days_left <= 0) {
            $days_left = 0;
            $time_text = "วันนี้!";
            $badge_color = 'bg-danger text-white';
        } elseif ($days_left == 1) {
            $time_text = "พรุ่งนี้";
            $badge_color = 'bg-warning text-dark';
        }

        // แปลงวันที่เป็นรูปแบบไทย
        $display_year = date('Y', $ma_time) + 543;
        $display_date = date('d/m/', $ma_time) . $display_year;

        // เก็บข้อมูลลง Array
        $all_notifications[] = [
            'id' => $row['ma_id'],
            'title' => "MA: " . $row['project_name'],
            'customer' => $row['customers_name'],
            'date_str' => $display_date,
            'time_text' => $time_text,
            'badge_color' => $badge_color,
            'note' => $row['note']
        ];
    }
}

// แปลงข้อมูลเป็น JSON เพื่อส่งให้ JavaScript (สำหรับ Toast Notification)
$js_notifications = json_encode($all_notifications);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - MaintDash</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logomaintdash1.png">
    
    <!-- External Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/index_user.css"> 
</head>
<body>
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar_user.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header Section -->
        <div class="premium-header animate-zoom">
            <h1 class="header-title"><i class="fas fa-bell me-2"></i> ระบบแจ้งเตือน MA (User)</h1>
            <div class="header-subtitle mt-2 d-flex align-items-center flex-wrap gap-2">
                <span>สวัสดีคุณ <strong><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'ผู้ใช้งาน'; ?></strong></span>
                <span class="mx-1">|</span>
                <span><i class="far fa-calendar-alt"></i> วันนี้วันที่ <?php echo date('d/m/'); echo (date('Y')+543); ?></span>
                <span class="mx-1">|</span>
                <span class="count-badge">
                    พบรายการแจ้งเตือน <?php echo count($all_notifications); ?> รายการ
                </span>
            </div>
        </div>

        <!-- Notification List -->
        <?php if (!empty($all_notifications)): ?>
        <div class="premium-box animate-zoom">
            <div class="premium-box-header">
                <div class="header-icon-box">
                    <i class="fas fa-list-ul"></i>
                </div>
                <div>
                    <h5 class="box-title">รายการที่ต้องดูแลเร็วๆ นี้</h5>
                    <small class="text-muted">ภายใน 7 วัน</small>
                </div>
            </div>

            <ul class="list-group list-group-flush">
                <?php foreach ($all_notifications as $notif): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge <?php echo $notif['badge_color']; ?> me-2 shadow-sm">
                            <?php echo $notif['time_text']; ?>
                        </span>
                        <strong style="font-size: 1.05rem;" class="text-dark">
                            <?php echo htmlspecialchars($notif['title']); ?>
                        </strong> 
                        <span class="text-muted small ms-1">(<?php echo htmlspecialchars($notif['customer']); ?>)</span>
                        <div class="mt-2 text-muted small">
                            <i class="far fa-calendar-alt me-1 text-primary"></i> กำหนด: <?php echo $notif['date_str']; ?> 
                            <?php if($notif['note']): ?>
                                | <i class="far fa-comment-alt me-1"></i> Note: <?php echo htmlspecialchars($notif['note']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php else: ?>
            <div class="premium-box text-center py-5 animate-zoom">
                <i class="fas fa-check-circle fa-4x text-success mb-3 opacity-25"></i>
                <h5 class="text-muted">ยอดเยี่ยม! ไม่มีรายการแจ้งเตือนในช่วงนี้</h5>
            </div>
        <?php endif; ?>

    </div>
    
    <!-- Toast Container for Notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ส่งข้อมูล PHP ไปยัง JS
        const notifications = <?php echo $js_notifications; ?>;
    </script>
    <script src="assets/js/index_user.js"></script>
</body>
</html>
