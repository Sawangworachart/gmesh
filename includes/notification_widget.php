<?php
/**
 * ไฟล์: notification_widget.php
 * คำอธิบาย: Widget แจ้งเตือนมุมขวาล่าง (Toast Notification) สำหรับงาน MA ในวันนี้
 * Features: Multiple Notifications, Slide Up Stack, LocalStorage Memory
 */

if (!isset($conn)) { return; }

// 1. ดึงข้อมูลงาน "วันนี้" (CURDATE) ทั้งหมด (ตัด LIMIT 1 ออก)
$sql_notify = "SELECT m.ma_id, m.ma_date, m.note, 
               p.project_name, p.responsible_person, 
               c.customers_name
               FROM ma_schedule m 
               JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
               LEFT JOIN customers c ON p.customers_id = c.customers_id
               WHERE m.ma_date = CURDATE() 
               ORDER BY m.ma_id DESC"; // เรียงจากล่าสุด

$query_notify = mysqli_query($conn, $sql_notify);

// เก็บข้อมูลลง Array ก่อน
$notify_list = [];
if ($query_notify) {
    while ($row = mysqli_fetch_assoc($query_notify)) {
        $notify_list[] = $row;
    }
}

// ถ้าไม่มีงานวันนี้ จบการทำงาน
if (empty($notify_list)) return;
?>

<!-- Custom CSS -->
<link rel="stylesheet" href="assets/css/notification_widget.css">

<!-- Toast Container -->
<div id="ma-toast-container">
    <?php foreach($notify_list as $index => $item): 
        $ma_id = $item['ma_id'];
        $project_name = htmlspecialchars($item['project_name']);
        $customer_name = htmlspecialchars($item['customers_name']);
    ?>
    
    <div class="ma-toast" id="toast-<?php echo $ma_id; ?>" data-id="<?php echo $ma_id; ?>" onclick="handleToastClick(event, <?php echo $ma_id; ?>)">
        <div class="ma-toast-icon">
            <i class="fas fa-bell"></i>
        </div>
        <div class="ma-toast-content">
            <div class="ma-toast-title">
                ถึงกำหนด MA
                <span class="ma-toast-badge">Today</span>
            </div>
            <div class="ma-toast-desc" title="<?php echo $project_name; ?>">
                <?php echo $project_name; ?>
            </div>
            <div class="ma-toast-sub">
                <i class="fas fa-building me-1"></i> <?php echo $customer_name; ?>
            </div>
        </div>
        <button class="ma-toast-close" onclick="closeToast(event, <?php echo $ma_id; ?>)">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <?php endforeach; ?>
</div>

<!-- Custom JS -->
<script src="assets/js/notification_widget.js"></script>
