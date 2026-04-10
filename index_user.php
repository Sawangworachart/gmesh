<?php
// index_user.php - ตกแต่งเฉพาะ Header และ Box (Logic เดิม)

session_start();
include_once 'auth.php';
require_once 'db.php';

// ตั้งค่าโซนเวลา
date_default_timezone_set('Asia/Bangkok');

$all_notifications = [];

// ==========================================================================================
// QUERY (ใช้ Logic เดิมที่แก้ Error แล้ว)
// ==========================================================================================
$today = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));

$sql_ma = "SELECT m.ma_id, m.ma_date, m.note, p.project_name, c.customers_name 
           FROM ma_schedule m 
           JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
           LEFT JOIN customers c ON p.customers_id = c.customers_id
           WHERE m.ma_date BETWEEN '$today' AND '$next_week'
           ORDER BY m.ma_date ASC";

$result_ma = mysqli_query($conn, $sql_ma);

if ($result_ma) {
    while ($row = mysqli_fetch_assoc($result_ma)) {

        $ma_time = strtotime($row['ma_date']);
        $today_time = strtotime($today);
        $diff_seconds = $ma_time - $today_time;
        $days_left = floor($diff_seconds / (60 * 60 * 24));

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

        $display_year = date('Y', $ma_time) + 543;
        $display_date = date('d/m/', $ma_time) . $display_year;

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

$js_notifications = json_encode($all_notifications);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_user.css">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7f6;
        }

        /* --- 1. ตกแต่ง Header ให้สวยงาม --- */
        .premium-header {
            background: linear-gradient(135deg, #0061ff 0%, #60efff 100%);
            /* ไล่สีฟ้าสดใส */
            color: white;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 97, 255, 0.3);
            /* เงาฟุ้งๆ สีฟ้า */
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
        }

        /* ใส่ไอคอนระฆังจางๆ ด้านหลัง */
        .premium-header::after {
            content: '\f0f3';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -40px;
            font-size: 180px;
            opacity: 0.15;
            transform: rotate(-20deg);
            pointer-events: none;
        }

        .header-title {
            font-size: 2.2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            font-weight: 300;
        }

        /* ป้ายนับจำนวนรายการ */
        .count-badge {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(5px);
            padding: 5px 15px;
            border-radius: 30px;
            font-weight: bold;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        /* --- 2. ตกแต่งกล่องรายการ (Box Container) --- */
        .premium-box {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            /* เงานุ่มๆ */
            overflow: hidden;
        }

        .premium-box-header {
            background: white;
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
        }

        .header-icon-box {
            width: 45px;
            height: 45px;
            background: #eef2ff;
            /* สีพื้นหลังไอคอนจางๆ */
            color: #0061ff;
            /* สีไอคอน */
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 15px;
            box-shadow: 0 4px 10px rgba(0, 97, 255, 0.1);
        }

        .box-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        /* List Items (คงเดิมแต่ปรับ Padding นิดหน่อย) */
        .list-group-item {
            border: none;
            border-bottom: 1px solid #f9f9f9;
            padding: 20px 25px;
            transition: background 0.2s;
        }

        .list-group-item:hover {
            background-color: #fafbff;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .toast-container {
            z-index: 9999;
        }
    </style>
</head>

<body>

    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">

        <div class="premium-header">
            <h1 class="header-title"><i class="fas fa-bell me-2"></i> ระบบแจ้งเตือน MA (User)</h1>
            <div class="header-subtitle mt-2 d-flex align-items-center flex-wrap gap-2">
                <span>สวัสดีคุณ
                    <strong><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'ผู้ใช้งาน'; ?></strong></span>
                <span class="mx-1">|</span>
                <span><i class="far fa-calendar-alt"></i> วันนี้วันที่
                    <?php echo date('d/m/');
                    echo (date('Y') + 543); ?></span>
                <span class="mx-1">|</span>
                <span class="count-badge">
                    พบรายการแจ้งเตือน <?php echo count($all_notifications); ?> รายการ
                </span>
            </div>
        </div>

        <?php if (!empty($all_notifications)): ?>
            <div class="premium-box">
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
                                <span class="badge <?php echo $notif['badge_color']; ?> me-2 rounded-pill shadow-sm">
                                    <?php echo $notif['time_text']; ?>
                                </span>
                                <strong style="font-size: 1.05rem;" class="text-dark">
                                    <?php echo htmlspecialchars($notif['title']); ?>
                                </strong>
                                <span class="text-muted small ms-1">(<?php echo htmlspecialchars($notif['customer']); ?>)</span>
                                <div class="mt-2 text-muted small">
                                    <i class="far fa-calendar-alt me-1 text-primary"></i> กำหนด:
                                    <?php echo $notif['date_str']; ?>
                                    <?php if ($notif['note']): ?>
                                        | <i class="far fa-comment-alt me-1"></i> Note:
                                        <?php echo htmlspecialchars($notif['note']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="premium-box text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3 opacity-25"></i>
                <h5 class="text-muted">ยอดเยี่ยม! ไม่มีรายการแจ้งเตือนในช่วงนี้</h5>
            </div>
        <?php endif; ?>

    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main_user.js"></script>

    <script>
        const notifications = <?php echo $js_notifications; ?>;
        // JS เดิมสำหรับ Toast
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.querySelector('.toast-container');
            if (notifications.length > 0) {
                notifications.forEach((item, index) => {
                    const html = `
                        <div id="toast-${index}" class="toast show border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="10000">
                            <div class="toast-header ${item.badge_color.includes('danger') ? 'bg-danger text-white' : 'bg-white'}">
                                <i class="fas fa-exclamation-circle me-2 ${item.badge_color.includes('danger') ? '' : 'text-primary'}"></i>
                                <strong class="me-auto">${item.title}</strong>
                                <small>${item.time_text}</small>
                                <button type="button" class="btn-close ${item.badge_color.includes('danger') ? 'btn-close-white' : ''}" data-bs-dismiss="toast"></button>
                            </div>
                            <div class="toast-body bg-white">
                                <strong>ลูกค้า:</strong> ${item.customer}<br>
                                <span class="text-muted small">กำหนด: ${item.date_str}</span>
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', html);
                });
            }
        });
    </script>
</body>

</html>