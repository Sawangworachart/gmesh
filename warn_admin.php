<?php
// หน้า Alarms เเจ้งเตือนของadmin
ob_start();
session_start();
date_default_timezone_set('Asia/Bangkok');

require_once 'auth.php';
require_once 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// กำหนดวันที่ปัจจุบัน
$today_str = date('Y-m-d');
$today_obj = new DateTime($today_str);

function dateThai($strDate)
{
    if (!$strDate || $strDate == '0000-00-00') return '-';
    $strYear = date("Y", strtotime($strDate)) + 543;
    $strMonth = date("n", strtotime($strDate));
    $strDay = date("j", strtotime($strDate));
    $strMonthCut = array("", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค.");
    return "$strDay " . $strMonthCut[$strMonth] . " $strYear";
}

function projectStatusText($status)
{
    switch ((int)$status) {
        case 1:
            return 'รอการตรวจสอบ';
        case 2:
            return 'กำลังดำเนินการ';
        case 3:
            return 'ดำเนินการเสร็จสิ้น';
        default:
            return '-';
    }
}

function projectStatusBadge($status)
{
    switch ((int)$status) {
        case 1:
            return '<span class="status-badge status-wait">รอการตรวจสอบ</span>';
        case 2:
            return '<span class="status-badge status-progress">กำลังดำเนินการ</span>';
        case 3:
            return '<span class="status-badge status-done">ดำเนินการเสร็จสิ้น</span>';
        default:
            return '<span class="status-badge">-</span>';
    }
}

function projectStatusStyle($status)
{
    switch ((int)$status) {
        case 1: // รอการตรวจสอบ
            return [
                'color' => '#92400E',
                'bg'    => '#FEF3C7'
            ];
        case 2: // กำลังดำเนินการ
            return [
                'color' => '#3730A3',
                'bg'    => '#E0E7FF'
            ];
        case 3: // ดำเนินการเสร็จสิ้น
            return [
                'color' => '#166534',
                'bg'    => '#DCFCE7'
            ];
        default:
            return [
                'color' => '#6B7280',
                'bg'    => '#F3F4F6'
            ];
    }
}

/* ================= AJAX HANDLER ================= */
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- ส่วนที่ 1: บันทึกงานสำเร็จ (MARK COMPLETE) ---
    if ($action == 'mark_complete') {
        // 1. รับค่าและ Clean ข้อมูล
        $ma_id = mysqli_real_escape_string($conn, $_POST['ma_id']);
        $ma_date = mysqli_real_escape_string($conn, $_POST['ma_date']);
        $remark = mysqli_real_escape_string($conn, $_POST['remark']);

        $file_sql_part = ""; // เตรียมไว้ใส่ใน SQL กรณีมีไฟล์
        $finalFilePath = "";

        // 2. จัดการไฟล์ (ถ้ามีส่งมา)
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $uploadDir = 'uploads/ma/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileExt = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $fileName = "ma_" . $ma_id . "_" . time() . "." . $fileExt;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $finalFilePath = $targetPath;
                // ✅ ต่อสตริงเพื่อนำไปใช้ใน Query (ฟิลด์ file_path ต้องตรงกับใน Database)
                $file_sql_part = ", file_path = '$finalFilePath'";
            }
        }

        // 3. รันคำสั่ง SQL ครั้งเดียวเพื่อ Update ข้อมูลทั้งหมด
        // $file_sql_part จะมีค่าก็ต่อเมื่อมีการอัปโหลดไฟล์สำเร็จเท่านั้น
        $sql = "UPDATE ma_schedule SET 
                ma_date = '$ma_date', 
                remark = '$remark', 
                is_done = 1 
                $file_sql_part 
                WHERE ma_id = '$ma_id'";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        }
        exit; // จบการทำงานของเงื่อนไขนี้ทันที
    }

    // --- ส่วนที่ 2: ดึงรายละเอียด (GET MA DETAIL) ---
    if ($action === 'get_ma_detail') {
        $ma_id = mysqli_real_escape_string($conn, $_POST['ma_id']);

        $sql = "SELECT m.*, p.project_name, p.responsible_person, p.status_remark, 
                       p.deliver_work_date, p.end_date, p.status as project_status,
                       c.customers_name, c.phone, c.contact_name
                FROM ma_schedule m
                JOIN pm_project p ON m.pmproject_id = p.pmproject_id
                LEFT JOIN customers c ON p.customers_id = c.customers_id
                WHERE m.ma_id = '$ma_id'
                AND m.is_done = 0";
        $result = mysqli_query($conn, $sql);

        if ($row = mysqli_fetch_assoc($result)) {
            // คำนวณวันคงเหลือ
            $target_obj = new DateTime($row['ma_date']);
            $today_obj  = new DateTime(date('Y-m-d'));
            $interval   = $today_obj->diff($target_obj);
            $diff_days  = (int)$interval->format('%r%a');

            // --- เริ่มสร้าง HTML สำหรับส่งกลับไปแสดงใน Modal ---
            $html = '<div style="font-family:\'Sarabun\', sans-serif;">';
            $html .= '<div style="text-align:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">';
            $html .= '    <h3 style="margin:0; color:#1e293b; font-size:1.2rem;">รายละเอียดงาน MA</h3>';
            $html .= '</div>';

            $html .= '<div style="display:flex; flex-direction:column; gap:8px; font-size: 0.95rem;">';
            $style = projectStatusStyle($row['project_status']);

            $rows = [
                ['label' => 'ชื่อโครงการ', 'val' => $row['project_name'], 'color' => '#1e293b', 'bold' => true, 'icon' => 'fas fa-clipboard-list'],
                ['label' => 'ข้อมูลลูกค้า',   'val' => $row['customers_name'], 'color' => '#1e293b', 'icon' => 'fas fa-building'],
                ['label' => 'วันเริ่มสัญญา', 'val' => dateThai($row['deliver_work_date']), 'color' => '#1e293b', 'icon' => 'far fa-calendar-plus'],
                ['label' => 'วันสิ้นสุดสัญญา', 'val' => dateThai($row['end_date']), 'color' => '#1e293b', 'icon' => 'far fa-calendar-check'],
                ['label' => 'ผู้ติดต่อ', 'val' => $row['contact_name'], 'color' => '#1e293b', 'icon' => 'far fa-user'],
                ['label' => 'เบอร์โทร', 'val' => $row['phone'], 'color' => '#6366f1', 'icon' => 'fas fa-phone'],
                ['label' => 'กำหนด MA', 'val' => dateThai($row['ma_date']), 'color' => '#ef4444', 'bold' => true, 'icon' => 'fas fa-tools'],
                ['label' => 'สถานะ', 'val' => projectStatusText($row['project_status']), 'color' => $style['color'], 'bg' => $style['bg'], 'icon' => 'fas fa-info-circle']
            ];

            foreach ($rows as $r) {
                $bgStyle = isset($r['bg']) ? "background:{$r['bg']}; padding:5px 8px; border-radius:6px;" : "padding-bottom:6px; border-bottom:1px dashed #f1f5f9;";
                $valStyle = "color:{$r['color']};" . (isset($r['bold']) ? "font-weight:700;" : "");
                $icon = isset($r['icon']) ? "<i class='{$r['icon']}' style='color:#94a3b8; width:20px; text-align:center; margin-right:5px;'></i>" : "";

                $html .= "<div style=\"display:flex; align-items:center; {$bgStyle}\">";
                $html .= "  <div style=\"min-width:140px; color:#64748b; font-size:0.9rem; display:flex; align-items:center;\">{$icon} {$r['label']}</div>";
                $html .= "  <div style=\"{$valStyle} flex:1;\">" . ($r['val'] ?: '-') . "</div>";
                $html .= "</div>";
            }
            $html .= '</div>';

            $html .= '<div style="background:#f8fafc; padding:12px; border-radius:10px; margin-top:15px; border:1px solid #e2e8f0;">';
            $html .= '  <b style="display:block; margin-bottom:5px; color:#334155; font-size:0.9rem;"><i class="far fa-sticky-note"></i> รายละเอียด / Note:</b>';
            $html .= '  <div style="color:#475569; font-size:0.9rem; line-height:1.4;">' . (nl2br(htmlspecialchars($row['note'])) ?: '-') . '</div>';
            $html .= '</div>';
            $html .= '</div>';

            echo json_encode(['success' => true, 'html' => $html]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูล']);
        }
        exit;
    }
}

$sql_urgent = "
    SELECT m.*, p.project_name, c.customers_name 
    FROM ma_schedule m
    JOIN pm_project p ON m.pmproject_id = p.pmproject_id
    LEFT JOIN customers c ON p.customers_id = c.customers_id
    WHERE m.is_done = 0
    ORDER BY m.ma_date ASC
";

$res_urgent = mysqli_query($conn, $sql_urgent);

$groups = ['all' => [], 'within7' => [], 'within90' => [], 'overdue' => []];
$calendar_events = [];

while ($row = mysqli_fetch_assoc($res_urgent)) {
    $target_obj = new DateTime($row['ma_date']);
    $interval = $today_obj->diff($target_obj);
    $diff_days = (int)$interval->format('%r%a');
    $row['diff'] = $diff_days;

    $groups['all'][] = $row;
    if ($diff_days < 0) {
        $groups['overdue'][] = $row;
    } elseif ($diff_days >= 0 && $diff_days <= 7) {
        $groups['within7'][] = $row;
    } elseif ($diff_days > 7 && $diff_days <= 90) {
        $groups['within90'][] = $row;
    }

    $calendar_events[] = [
        'id' => $row['ma_id'],
        'title' => $row['project_name'],
        'start' => $row['ma_date'],
        'backgroundColor' => ($diff_days < 0) ? '#fee2e2' : (($diff_days <= 7) ? '#fff7ed' : '#e0f2fe'),
        'textColor' => ($diff_days < 0) ? '#ef4444' : (($diff_days <= 7) ? '#ea580c' : '#0284c7'),
        'borderColor' => ($diff_days < 0) ? '#ef4444' : (($diff_days <= 7) ? '#ea580c' : '#0284c7')
    ];
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"

        crossorigin="anonymous"
        referrerpolicy="no-referrer" />

    <meta charset="UTF-8">
    <title>MaintDash</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/th.js'></script>

    <style>
        :root {
            --primary: #5599ff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: var(--bg);
            margin: 0;
            overflow-x: hidden;
        }

        .main-content {

            margin-left: 250px;
            /* ปรับให้เท่ากับความกว้างของ Sidebar */
            padding: 30px;
            width: auto;
            box-sizing: border-box;
            transition: margin-left 0.3s ease;
            /* เพื่อให้ขยับลื่นไหลตอนหุบเมนู */
        }

        .header-actions {
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        /* ยกเครื่อง Stat Box ใหม่ */
        .stat-box {
            background: #ffffff;
            border: none !important;
            /* เอาเส้นขอบเดิมออก */
            border-radius: 24px;
            /* มนมากขึ้น */
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.04);
            /* เงาฟุ้งแบบนุ่มๆ */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        }

        .stat-val {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .urgent-alert-container {
            margin-bottom: 30px;
            padding: 15px;
            background: #fff8f1;
            border-radius: 15px;
            border: 1px dashed var(--warning);
            max-width: 100%;
            overflow: hidden;
        }

        .urgent-card-scroll {
            gap: 15px;
            overflow-x: auto;
            padding-bottom: 10px;
            scrollbar-width: thin;
        }

        .urgent-mini-card {
            min-width: 280px;
            background: white;
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid var(--warning);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            position: relative;
            flex-shrink: 0;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            width: 100%;
            overflow-x: auto;
            box-sizing: border-box;
        }

        .tab-nav {
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            overflow-x: auto;
        }

        .tab-link {
            padding: 12px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1rem;
            color: #64748b;
            font-weight: 500;
            transition: 0.3s;
            white-space: nowrap;
        }

        .tab-link.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
            background: #f1f7ff;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th {
            background: #f8fafc;
            color: #475569;
            text-align: left;
            padding: 15px;
            font-size: 0.9rem;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #edf2f7;
            font-size: 0.9rem;
        }

        .badge-day {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .overdue-badge {
            background: #fee2e2;
            color: #ef4444;
        }

        .warning-badge {
            background: #fff7ed;
            color: #ea580c;
        }

        .info-badge {
            background: #e0f2fe;
            color: #0284c7;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;

            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);

            display: none;
            /* ✅ สำคัญ */
            align-items: center;
            justify-content: center;

            z-index: 2001;
        }



        .modal-body .row,
        .modal-body .detail-row {
            margin-bottom: 10px;
            /* เดิมมัก 16–20 */
        }

        .detail-row {
            display: flex;
            padding: 10px 4px;
            border-bottom: 1px dashed #e5e7eb;
        }

        .detail-label {
            width: 140px;
            color: #64748b;
            font-weight: 500;
            flex-shrink: 0;
        }

        .detail-value {
            color: #0f172a;
            font-weight: 500;
        }


        .detail-row:last-child {
            border-bottom: none;
        }

        .note-box {
            margin-top: 14px;
            padding: 14px 18px;

            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;

            color: #334155;
        }

        .modal-footer button {
            padding: 6px 16px;
            border-radius: 8px;
            border: 1px solid #cbd5f5;
            background: #fff;
        }


        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1;
            white-space: nowrap;
        }

        .status-progress {
            background: #E0E7FF;
            /* ฟ้าอ่อน */
            color: #3730A3;
        }

        .status-done {
            background: #DCFCE7;
            /* เขียวอ่อน */
            color: #166534;
        }

        .status-wait {
            background: #FEF3C7;
            /* เหลืองอ่อน */
            color: #92400E;
            /* เหลืองเข้ม */
        }

        .modal-dialog {
            max-width: 800px;
        }

        .modal-body {
            padding: 18px 22px;
            font-size: 15px;
            color: #1f2937;
        }


        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
        }

        .main-content {
            position: relative;
            z-index: 20;
        }

        .header-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* ⬅️ ดันปุ่มไปขวาสุด */
            gap: 15px;
            margin-bottom: 20px;
            position: relative;
            z-index: 30;
        }

        /* ===== MA TABS (CLEAN STYLE) ===== */
        .ma-tabs {

            gap: 25px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
            padding-bottom: 5px;
        }

        .ma-tab {
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            padding: 8px 2px;
            position: relative;
            transition: 0.25s;
        }

        .ma-tab:hover {
            color: #2563eb;
        }

        .ma-tab.active {
            color: #2563eb;
        }

        .ma-tab.active::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -6px;
            width: 100%;
            height: 3px;
            background: #2563eb;
            border-radius: 3px;
        }

        /* ===== BADGE ===== */
        .ma-badge {
            background: #e5e7eb;
            color: #374151;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 999px;
            margin-left: 6px;
        }

        /* badge colors */
        .ma-badge.warning {
            background: #fff7ed;
            color: #ea580c;
        }

        .ma-badge.info {
            background: #e0f2fe;
            color: #0284c7;
        }

        .ma-badge.danger {
            background: #fee2e2;
            color: #ef4444;
        }

        /* MOBILE */
        @media (max-width: 768px) {
            .ma-tabs {
                gap: 12px;
                overflow-x: auto;
            }
        }

        /* ===== STAT ACTIVE STATE ===== */
        .stat-box {
            background: white;
            padding: 20px 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 6px solid #ddd;

            display: flex;
            align-items: center;
            justify-content: flex-start;
            /* เปลี่ยนจาก space-between เป็น flex-start (ชิดซ้าย) */
            gap: 20px;
            /* ระยะห่างระหว่าง ไอคอน กับ ตัวหนังสือ (ปรับเลขนี้ได้ถ้าอยากให้ห่าง/ชิดกว่านี้) */

            cursor: pointer;
            transition: all 0.2s ease;
        }

        .stat-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-info {
            font-size: 1.25rem;
            text-align: left;
            /* เปลี่ยนจาก right เป็น left */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-val {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.1;
            margin-top: 5px;
        }

        .stat-box.active {
            background: #f8fafc;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .stat-box.active::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 12px;
            border: 2px solid rgba(99, 102, 241, 0.35);
            pointer-events: none;
        }

        /* ส่วนครอบกล่องค้นหา */
        .search-box {
            position: relative;
            margin-bottom: 25px;
            max-width: 500px;
            /* จำกัดความกว้างไม่ให้ยาวเกินไป */
        }

        /* ไอคอนแว่นขยาย */
        .search-box i {
            position: absolute;
            top: 50%;
            left: 18px;
            transform: translateY(-50%);
            color: #94a3b8;
            /* สีเทาฟ้าอ่อน */
            font-size: 18px;
            pointer-events: none;
            transition: color 0.3s ease;
        }

        /* ช่อง Input */
        .search-box input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            /* เว้นที่ด้านซ้ายให้ไอคอน */
            border-radius: 16px;
            /* มนแบบพอดีๆ */
            border: 1.5px solid #e2e8f0;
            background-color: #ffffff;
            font-family: 'Sarabun', sans-serif;
            font-size: 0.95rem;
            outline: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            /* เงาจางๆ */
            transition: all 0.3s ease;
        }

        /* เอฟเฟกต์ตอนคลิก (Focus) */
        .search-box input:focus {
            border-color: #3b82f6;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1);
            background-color: #fff;
        }

        /* เปลี่ยนสีไอคอนตอนคลิก */
        .search-box input:focus+i {
            color: #3b82f6;
        }

        /* เขียนเพิ่ม: ตกแต่งวงสีล้อมรอบจำนวนวันในการ์ด */
        .days-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #fff7ed;
            /* สีส้มอ่อน */
            color: #ea580c;
            /* สีส้มเข้ม */
            padding: 4px 12px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.85rem;
            border: 1px solid #ffedd5;
            box-shadow: 0 2px 4px rgba(234, 88, 12, 0.1);
        }

        /* จัดการระยะห่างกลุ่มปุ่มควบคุมปฏิทินฝั่งขวา */
        .fc-toolbar-chunk:last-child {

            gap: 5px;
            align-items: center;
        }

        /* ตกแต่งปุ่มเลือกวันที่ให้เด่นกว่าปุ่มมาตรฐาน */
        .fc-selectDateBtn-button {
            background-color: #1e293b !important;
            color: white !important;
            border-radius: 8px !important;
            padding: 5px 15px !important;
            font-weight: 600 !important;
            border: none !important;
            margin-left: 10px !important;
            /* เว้นระยะจากปุ่ม 'วันนี้' */
        }

        /* กำหนดให้หน้าต่างรายละเอียด (Detail) อยู่หน้าสุดเสมอ */
        #modalDetail {
            z-index: 1060 !important;
            /* ค่าต้องสูงกว่า Calendar */
        }

        #modalCalendar.show {
            background: rgba(0, 0, 0, 0.5);
            /* ฉากหลังปฏิทิน */
        }

        #modalDetail.show {
            background: rgba(0, 0, 0, 0.3);
            /* ฉากหลังรายละเอียด (จางลงหน่อยจะได้ไม่มืดตึ๊ดตื๋อเมื่อซ้อนกัน) */
        }

        /* กำหนดให้หน้าต่างปฏิทิน (Calendar) อยู่เลเยอร์รองลงมา */
        #modalCalendar {
            z-index: 1050 !important;
        }

        /* ปรับให้เงาพื้นหลังของหน้าต่างที่ซ้อนกันดูแยกจากกันชัดเจน */
        .modal-overlay.show {
            display: flex !important;
            /* บังคับให้แสดงผลเมื่อมี class .show */
        }

        /* เขียนเพิ่ม: ไฮไลท์สีเต็มกรอบสำหรับวันที่มีงาน */
        .fc-daygrid-day.has-ma-event {
            background-color: #f0f7ff !important;
            /* สีฟ้าอ่อน หรือเปลี่ยนเป็นสีที่ต้องการ */
        }

        /* ตกแต่งเพิ่มเติมเพื่อให้ตัวเลขวันที่ดูเด่นขึ้นเมื่อมีไฮไลท์ */
        .has-ma-event .fc-daygrid-day-number {
            color: #2563eb !important;
            font-weight: 800 !important;
        }

        /* บังคับให้สีไฮไลท์แสดงเต็มกรอบวันที่ */
        .has-ma-event {
            background-color: #f0f7ff !important;
            /* สีฟ้าอ่อน */
            transition: background 0.2s;
        }

        .has-ma-event:hover {
            background-color: #e0efff !important;
            /* สีเข้มขึ้นเมื่อเอาเมาส์ชี้ */
        }

        .has-ma-event .fc-daygrid-day-number {
            color: #2563eb !important;
            font-weight: 800 !important;
        }

        /* ===== LAYOUT หลัก ===== */
        .layout {

            min-height: 100vh;
        }

        /* ช่องว่างกัน sidebar */
        .sidebar-space {
            width: 240px;
            /* ⬅️ ต้องเท่ากับความกว้าง sidebar */
            flex-shrink: 0;
        }

        /* เนื้อหาหลัก */
        .main-content {

            padding: 20px;
            position: relative;
            z-index: 1;
            background: #f8fafc;
        }

        /* กัน scroll แนวนอน */
        body {
            overflow-x: hidden;
        }


        /* ===== FIX CLICK BLOCKED BY MODAL OVERLAY ===== */
        /* ================= MODAL (FIX FINAL) ================= */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);

            display: none;
            /* ซ่อนจริง */
            align-items: center;
            justify-content: center;

            z-index: 2001;
        }

        /* ⭐ จุดสำคัญที่คุณขาดอยู่ */


        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            /* เอฟเฟกต์กระจกฝ้า */
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        /* ===== FIX HEADER CALENDAR BUTTON POSITION ===== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .page-title {
            margin: 0;
        }

        .btn-calendar {
            white-space: nowrap;
        }

        .upcoming-wrapper {
            background: #fffbeb;
            /* สีเหลืองพาสเทลจางๆ */
            border: 1px solid #fcd34d;
            /* เส้นขอบสีเหลืองเข้มขึ้นนิดนึง */
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(251, 191, 36, 0.1);
            /* เงาสีเหลืองจางๆ */
        }

        .upcoming-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .upcoming-header h3 {
            margin: 0;
            font-size: 1.05rem;
            color: #92400e;
            /* สีน้ำตาลเข้ม (ให้อ่านง่ายบนพื้นเหลือง) */
        }

        .upcoming-count {
            background: #fff7ed;
            color: #ea580c;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .upcoming-scroll {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            padding-bottom: 6px;
        }

        .upcoming-card {
            min-width: 280px;
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            /* ไว้เล่นสีที่มุม */
        }

        /* เพิ่มแถบสีเล็กๆ ด้านซ้ายให้ดูรู้สถานะทันที */
        .upcoming-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 6px;
            background: var(--warning);
            /* หรือสีตามสถานะ */
        }

        .upcoming-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
            border-color: #f59e0b;
            /* ชี้แล้วขอบเข้มขึ้น */
        }

        .upcoming-days {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 999px;
            margin-bottom: 10px;
        }

        .upcoming-days.warning {
            background: #fff7ed;
            color: #ea580c;
        }

        .upcoming-days.danger {
            background: #fee2e2;
            color: #ef4444;
        }

        .project-name {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .project-meta,
        .project-date {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 4px;
        }

        /* ===== ACTION BUTTONS ===== */
        .action-btn {
            border: none;
            border-radius: 8px;
            padding: 6px 10px;
            margin: 0 3px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* ดูรายละเอียด */
        .btn-view {
            background: #e0f2fe;
            /* ฟ้าอ่อน */
            color: #0284c7;
            /* ฟ้าเข้ม */
        }

        .btn-view:hover {
            background: #bae6fd;
            transform: translateY(-1px);
        }

        /* คลิกสำเร็จ (เฉพาะ overdue) */
        .btn-complete {
            background: #dcfce7;
            /* เขียวอ่อน */
            color: #16a34a;
            /* เขียวเข้ม */
        }

        .btn-complete:hover {
            background: #bbf7d0;
            transform: translateY(-1px);
        }

        /* ปรับไอคอนให้คม */
        .action-btn i {
            font-size: 0.95rem;
        }

        /* ปรับปรุงโครงสร้าง Modal ปฏิทิน */
        #modalCalendar .modal-content {
            max-width: 1100px;
            width: 95%;
            height: 85vh;
            display: flex;
            flex-direction: column;
        }

        /* ส่วนแสดงผลแบบ Flex */
        .calendar-container {
            display: flex;
            flex: 1;
            overflow: hidden;
            gap: 20px;
            margin-top: 15px;
        }

        /* ฝั่งปฏิทิน */
        #calendar-area {
            flex: 2;
            min-width: 0;
            /* ป้องกันการดัน Layout */
        }

        /* ฝั่งรายการงานประจำเดือน (ด้านขวา) */
        .monthly-summary-sidebar {
            flex: 1;
            background: #ffffff;
            border-left: 1px solid #e2e8f0;
            padding-left: 20px;
            display: flex;
            flex-direction: column;
        }

        .summary-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-list {
            overflow-y: auto;
            flex: 1;
        }

        /* สไตล์การ์ดงานใน Sidebar */
        .summary-item {
            padding: 12px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            margin-bottom: 10px;
            cursor: pointer;
            transition: 0.2s;
            border-left: 5px solid #3b82f6;
        }

        .summary-item:hover {
            background: #f8fafc;
            transform: translateX(5px);
        }

        .summary-item-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #2563eb;
            margin-bottom: 4px;
        }

        .summary-item-sub {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* บังคับไม่ให้ Body และ Layout หลักเกิด Scroll แนวนอน */
        body,
        .layout {
            overflow-x: hidden !important;
        }

        /* ปรับ Container ของตารางให้พอดีหน้าจอ */
        .table-container {
            width: 100%;
            overflow-x: hidden;
            /* เปลี่ยนจาก auto เป็น hidden */
            box-sizing: border-box;
        }

        /* ตั้งค่าตารางให้ยืดตามความกว้างหน้าจอ ไม่ให้กว้างเกินไป */
        table.maTable {
            width: 100% !important;
            table-layout: fixed;
            /* บังคับให้จัดการความกว้างคอลัมน์ให้อยู่ภายใน 100% */
            word-wrap: break-word;
            /* ตัดคำที่ยาวเกินไปให้ขึ้นบรรทัดใหม่ */
        }

        /* กำหนดความกว้างคอลัมน์ที่แน่นอน (ตัวอย่าง) */
        table.maTable th:nth-child(1) {
            width: 20%;
        }

        /* วันที่ */
        table.maTable th:nth-child(2) {
            width: 15%;
        }

        /* ระยะเวลา */
        table.maTable th:nth-child(3) {
            width: 50%;
        }

        /* ชื่อโครงการ */
        table.maTable th:nth-child(4) {
            width: 15%;
        }

        /* จัดการ */

        #modalDetail .modal-content {
            max-width: 750px;
            /* ความกว้างกำลังดี */
            width: 95%;

            height: auto !important;
            /* 🟢 สำคัญ: ให้สูงตามเนื้อหาจริง */
            max-height: 98vh !important;
            /* กันล้นจอแค่กรณีจอเล็กมากๆ */
            overflow-y: visible !important;
            /* ปิด Scrollbar */

            padding: 25px !important;
            /* ลดขอบรอบๆ ลงหน่อย */
            display: block !important;
        }

        #detailBody {
            padding: 0 10px;
            /* จัดระยะเนื้อหาด้านใน */
        }


        /* ===== FINAL HOTFIX : DO NOT REMOVE ANYTHING ABOVE ===== */

        /* บังคับให้ modal แสดงและรับคลิก */
        .modal-overlay.show {
            display: flex !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        /* ป้องกัน overlay ที่ซ่อนอยู่บังคลิก */
        .modal-overlay {
            pointer-events: none;
        }

        /* modal ที่เปิดอยู่ต้องรับคลิก */
        .modal-overlay.show {
            pointer-events: auto !important;
        }

        /* ดัน modal ให้อยู่บนสุดจริง */
        /* ดัน modal ให้อยู่บนสุดจริง */

        /* 1. ปฏิทิน (ให้ต่ำกว่ารายละเอียดนิดนึง) */
        #modalCalendar {
            z-index: 99990 !important;
        }

        /* 2. รายละเอียด (ให้สูงที่สุด) */
        #modalDetail {
            z-index: 99999 !important;
        }

        /* ===== FIX LOGOUT CLICK BLOCK ===== */

        /* overlay ที่ไม่ show ต้องไม่รับคลิก */
        .modal-overlay:not(.show) {
            pointer-events: none !important;
        }

        /* overlay ที่ show เท่านั้นถึงรับคลิก */
        .modal-overlay.show {
            pointer-events: auto !important;
        }

        /* สไตล์ส่วนหัวใหม่ (Banner) */
        .header-banner-custom {
            background: #ffffff;
            border-radius: 12px;
            /* ความมนของกรอบ */
            padding: 20px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            /* เงาฟุ้งๆ แบบในรูป */
            border-left: 5px solid #4f8cf6;
            /* เส้นแถบสีฟ้าด้านหน้าตามรูป */
        }

        .header-left-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-icon-circle {
            font-size: 1.8rem;
            color: #334155;
        }

        .header-text-group {
            display: flex;
            flex-direction: column;
        }

        .header-main-title {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }

        .header-sub-desc {
            margin: 4px 0 0 0;
            font-size: 1rem;
            color: #64748b;
        }

        /* สไตล์ปุ่มแบบมนยาว (Pill Button) ตามรูป */
        .btn-pill-primary {
            background: linear-gradient(135deg, #5c93ff 0%, #3b82f6 100%);
            /* ไล่เฉดสีฟ้า */
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 50px;
            /* ทำให้ปุ่มมนยาว */
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }

        .btn-pill-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4);
            filter: brightness(1.05);
        }

        /* ปรับแต่งสำหรับมือถือ */
        @media (max-width: 768px) {
            .header-banner-custom {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .header-right-action {
                width: 100%;
            }

            .btn-pill-primary {
                width: 100%;
                justify-content: center;
            }
        }

        /* ===== ปุ่มปิดหน้าต่าง (สีแดง + ขอบมนหน่อยๆ) ===== */
        .btn-close-modal {
            background-color: #dc2626;
            /* สีแดงเข้ม */
            color: #ffffff;
            /* ตัวหนังสือสีขาว */
            border: none;
            /* ไม่เอาเส้นขอบ */

            padding: 10px 30px;
            /* ความกว้างปุ่ม */
            border-radius: 12px;
            /* 🟢 จุดสำคัญ: ความโค้งมน (แก้เลขนี้ได้) */

            font-family: 'Sarabun', sans-serif;
            font-size: 1rem;
            font-weight: 600;

            cursor: pointer;
            box-shadow: 0 4px 6px rgba(220, 38, 38, 0.2);
            /* เงาแดงจางๆ */
            transition: all 0.2s ease;
        }

        .btn-close-modal:hover {
            background-color: #b91c1c;
            /* สีเข้มขึ้นตอนเอาเมาส์ชี้ */
            transform: translateY(-2px);
            /* ลอยขึ้นนิดนึง */
            box-shadow: 0 6px 12px rgba(220, 38, 38, 0.3);
        }
    </style>
</head>

<body>
    <div class="layout">

        <div class="sidebar-space"></div>

        <?php include 'sidebar.php'; ?>

        <div class="main-content">

            <div class="header-banner-custom">
                <div class="header-left-content">
                    <div class="header-icon-circle">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="header-text-group">
                        <h2 class="header-main-title"> Alarms</h2>
                        <p class="header-sub-desc">จัดการข้อมูลการแจ้งเตือนและตรวจสอบกำหนดการบำรุงรักษา</p>
                    </div>
                </div>
                <div class="header-right-action">
                    <button id="btn-show-calendar" class="btn-pill-primary" onclick="triggerCalendarModal()">
                        <i class="fas fa-calendar-check"></i>เปิดปฏิทินงาน
                    </button>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-box active" id="card-all" data-tab="all" style="border-left-color:#10b981;" onclick="switchTab('all')">
                    <div class="stat-icon-box" style="background:#d1fae5; color:#059669;">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-info">
                        <div style="color:#64748b; font-weight:600;">งานทั้งหมด</div>
                        <div class="stat-val" data-count="<?= count($groups['all']) ?>">0</div>
                    </div>
                </div>

                <div class="stat-box" id="card-urgent" data-tab="within7" style="border-left-color:#f59e0b;" onclick="switchTab('within7')">
                    <div class="stat-icon-box" style="background:#ffedd5; color:#ea580c;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div style="color:#9a3412; font-weight:600;">ภายใน 7 วัน</div>
                        <div class="stat-val" data-count="<?= count($groups['within7']) ?>">0</div>
                    </div>
                </div>

                <div class="stat-box" id="card-normal" data-tab="within90" style="border-left-color:#6366f1;" onclick="switchTab('within90')">
                    <div class="stat-icon-box" style="background:#e0e7ff; color:#4338ca;">
                        <i class="far fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <div style="color:#3730a3; font-weight:600;">ภายใน 90 วัน</div>
                        <div class="stat-val" data-count="<?= count($groups['within90']) ?>">0</div>
                    </div>
                </div>

                <div class="stat-box" id="card-overdue" data-tab="overdue" style="border-left-color:#ef4444;" onclick="switchTab('overdue')">
                    <div class="stat-icon-box" style="background:#fee2e2; color:#b91c1c;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <div style="color:#991b1b; font-weight:600;">งานที่เกินกำหนด</div>
                        <div class="stat-val" data-count="<?= count($groups['overdue']) ?>">0</div>
                    </div>
                </div>
            </div>


            <?php if (!empty($groups['within7'])): ?>
                <div class="upcoming-wrapper">
                    <div class="upcoming-header">
                        <h3>
                            <i class="fas fa-clock"></i>
                            งานที่กำลังจะถึงภายใน 7 วัน
                        </h3>
                        <span class="upcoming-count"><?= count($groups['within7']) ?> งาน</span>
                    </div>

                    <div class="upcoming-scroll">
                        <?php foreach ($groups['within7'] as $item): ?>
                            <div id="card-<?= $item['ma_id'] ?>" class="upcoming-card" onclick="loadDetail(<?= $item['ma_id'] ?>)">
                                <div class="upcoming-days warning">
                                    <?= $item['diff'] == 0 ? 'วันนี้' : 'อีก ' . $item['diff'] . ' วัน' ?>
                                </div>

                                <div class="upcoming-body">
                                    <div class="project-name">
                                        <?= htmlspecialchars($item['project_name']) ?>
                                    </div>

                                    <div class="project-meta">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($item['customers_name'] ?: '-') ?>
                                    </div>

                                    <div class="project-date">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= dateThai($item['ma_date']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>




            <div class="table-container">

                <div class="ma-tabs">
                    <button class="ma-tab active" data-tab="all" onclick="switchTab('all')">
                        ทั้งหมด <span class="ma-badge"><?= count($groups['all']) ?></span>
                    </button>

                    <button class="ma-tab" data-tab="within7" onclick="switchTab('within7')">
                        งานที่ครบกำหนดภายใน 7 วัน
                        <span class="ma-badge warning"><?= count($groups['within7']) ?></span>
                    </button>

                    <button class="ma-tab" data-tab="within90" onclick="switchTab('within90')">
                        งานที่ครบกำหนดภายใน 90 วัน
                        <span class="ma-badge info"><?= count($groups['within90']) ?></span>
                    </button>

                    <button class="ma-tab" data-tab="overdue" onclick="switchTab('overdue')">
                        งานที่เกินกำหนด
                        <span class="ma-badge danger"><?= count($groups['overdue']) ?></span>
                    </button>
                </div>

                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="ค้นหาชื่อโครงการ, ลูกค้า หรือครั้งที่ MA...">
                    <i class="fas fa-search"></i>
                </div>

                <?php foreach ($groups as $id => $items): ?>
                    <div id="content-<?= $id ?>" class="tab-content <?= $id == 'all' ? 'active' : '' ?>" style="<?= $id != 'all' ? 'display:none;' : '' ?>">
                        <table class="maTable">
                            <thead>
                                <tr>
                                    <th>วันที่กำหนด</th>
                                    <th>ระยะเวลา</th>
                                    <th>ชื่อโครงการ</th>
                                    <th style="text-align:center;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; color:#94a3b8; padding:30px;">
                                            ไม่มีรายการในส่วนนี้
                                        </td>
                                    </tr>
                                    <?php else: foreach ($items as $i): ?>
                                        <?php
                                        $d = $i['diff'];
                                        $badge = ($d < 0) ? 'overdue-badge' : (($d <= 7) ? 'warning-badge' : 'info-badge');
                                        $label = ($d < 0) ? "เกินกำหนด " . abs($d) . " วัน" : (($d == 0) ? "วันนี้" : "อีก $d วัน");
                                        ?>
                                        <tr id="row-<?= $i['ma_id'] ?>">
                                            <td><?= dateThai($i['ma_date']) ?></td>
                                            <td><span class="badge-day <?= $badge ?>"><?= $label ?></span></td>
                                            <td>
                                                <div style="font-weight:600; color:#1e293b;">
                                                    <?= htmlspecialchars($i['project_name']) ?>
                                                </div>

                                                <div style="font-size:0.85rem; color:#64748b; margin-top:2px;">
                                                    <i class="far fa-user"></i> <?= htmlspecialchars($i['customers_name']) ?>
                                                </div>
                                                <span style="display:none;"><?= $i['ma_date'] ?></span>
                                            </td>
                                            <td style="text-align:center;">
                                                <!-- ดูรายละเอียด : ทุกงาน -->
                                                <button class="action-btn btn-view"
                                                    onclick="loadDetail(<?= $i['ma_id'] ?>)"
                                                    title="ดูรายละเอียด">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <!-- คลิกสำเร็จ : เฉพาะงานที่เกินกำหนด -->
                                                <?php if ($d <= 7): ?>
                                                    <button class="action-btn btn-complete"
                                                        onclick="markAsComplete(<?= $i['ma_id'] ?>, '<?= $i['ma_date'] ?>', '<?= htmlspecialchars($i['project_name']) ?>', '<?= htmlspecialchars($i['note']) ?>', this)"
                                                        title="บันทึกว่างานเสร็จแล้ว">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>

            </div>

        </div><!-- END main-content -->

    </div><!-- END layout -->

    <!-- ================= MODAL DETAIL ================= -->
    <div id="modalDetail" class="modal-overlay" onclick="closeModals(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div style="display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                <img src="images/logomaintdash1.png" alt="Logo" style="height: 40px; width: auto;" onerror="this.src='https://via.placeholder.com/40x40?text=LOGO'">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.2rem;">รายละเอียดงาน</h3>
            </div>

            <div id="detailBody"></div>
            <div style="text-align:right; margin-top:25px;">
                <button class="btn-close-modal" onclick="$('#modalDetail').removeClass('show')">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>

    <!-- ================= MODAL CALENDAR ================= -->
    <div id="modalCalendar" class="modal-overlay" onclick="closeModals(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0;"><i class="fas fa-calendar-check"></i> ปฏิทินกำหนดการ MA</h3>
                <button onclick="$('#modalCalendar').removeClass('show')" style="border:none; background:none; cursor:pointer; font-size:1.5rem;">&times;</button>
            </div>

            <div class="calendar-container">
                <div id="calendar-area"></div>

                <div class="monthly-summary-sidebar">
                    <div class="summary-title">งานประจำเดือน</div>
                    <div id="monthly-list" class="summary-list">
                    </div>
                </div>
            </div>
        </div>
    </div>




    <script>
        let fullCalendar;

        function switchTab(tabId) {
            // 1. เปลี่ยน Tab ตามปกติ
            $('.tab-content').hide();
            $('#content-' + tabId).show();

            $('.ma-tab').removeClass('active');
            $('.ma-tab[data-tab="' + tabId + '"]').addClass('active');

            $('.stat-box').removeClass('active');
            $('.stat-box[data-tab="' + tabId + '"]').addClass('active');

            // 2. [เพิ่มใหม่] สั่งให้ค้นหาซ้ำทันทีที่เปลี่ยน Tab (เพื่อให้การกรองยังอยู่)
            $("#searchInput").trigger("keyup");
        }



        $(document).ready(function() {
            $("#searchInput").on("keyup", function() {
                var value = $(this).val().toLowerCase().trim();

                // กรองเฉพาะแถวที่อยู่ใน Content ที่กำลังแสดงอยู่ (Visible)
                $(".tab-content:visible tbody tr").each(function() {
                    var rowText = $(this).text().toLowerCase();

                    // ถ้าข้อความในแถวมีคำที่ค้นหา ให้แสดง ถ้าไม่มีให้ซ่อน
                    if (rowText.indexOf(value) > -1) {
                        $(this).fadeIn(200); // แสดงแบบนุ่มนวล
                    } else {
                        $(this).hide();
                    }
                });

                // ถ้าค้นหาแล้วไม่เจออะไรเลย ให้แสดงข้อความ No Data (ออปชันเสริม)
                var visibleRows = $(".tab-content:visible tbody tr:visible").length;
                if (visibleRows === 0) {
                    if ($("#no-results").length === 0) {
                        $(".tab-content:visible tbody").append('<tr id="no-results"><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">ไม่พบข้อมูลที่ค้นหา...</td></tr>');
                    }
                } else {
                    $("#no-results").remove();
                }
            });
        });

        function loadDetail(id) {
            if (!id) return;
            $.ajax({
                url: 'warn_admin.php',
                type: 'POST',
                data: {
                    action: 'get_ma_detail',
                    ma_id: id
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#detailBody').html(res.html);

                        // ❌ ลบบรรทัดนี้ออก หรือ Comment ไว้ (บรรทัดที่สั่งปิด Modal อื่น)
                        // $('.modal-overlay').removeClass('show'); 

                        // ✅ เปิดหน้า Detail ทับลงไปเลย
                        $('#modalDetail').addClass('show');

                    } else {
                        Swal.fire('Error', res.error, 'error');
                    }
                }
            });
        }
        // ฟังก์ชันสำหรับอัปเดตรายการงานด้านข้าง
        function updateMonthlySummary() {
            if (!fullCalendar) return;

            const currentViewDate = fullCalendar.getDate();
            const currentMonth = currentViewDate.getMonth();
            const currentYear = currentViewDate.getFullYear();

            // ข้อมูล events ทั้งหมดจาก PHP
            const allEvents = <?= json_encode($calendar_events) ?>;

            // กรองเอาเฉพาะเดือนที่แสดง
            const monthlyEvents = allEvents.filter(ev => {
                const evDate = new Date(ev.start);
                return evDate.getMonth() === currentMonth && evDate.getFullYear() === currentYear;
            });

            const listContainer = $('#monthly-list');
            listContainer.empty();

            if (monthlyEvents.length === 0) {
                listContainer.append('<div style="text-align:center; color:#94a3b8; padding:20px;">ไม่มีงานในเดือนนี้</div>');
                return;
            }

            monthlyEvents.forEach(ev => {
                const html = `
            <div class="summary-item" onclick="loadDetail(${ev.id})" style="border-left-color: ${ev.borderColor}">
                <div class="summary-item-title">${ev.title}</div>
                <div class="summary-item-sub"><i class="far fa-building"></i> อื่นๆ</div>
            </div>
        `;
                listContainer.append(html);
            });
        }

        // แก้ไขฟังก์ชัน triggerCalendarModal เพิ่ม callback 'datesSet'
        function triggerCalendarModal() {
            $('.modal-overlay').removeClass('show');
            $('#modalCalendar').addClass('show');

            setTimeout(() => {
                if (!fullCalendar) {
                    const calendarEl = document.getElementById('calendar-area');

                    // 1. ดึงวันที่ของงาน "ภายใน 7 วัน" จาก PHP มาเป็น Array
                    const urgentDates = <?php
                                        $u_dates = [];
                                        // วนลูปเก็บเฉพาะวันที่จากกลุ่ม within7
                                        if (isset($groups['within7'])) {
                                            foreach ($groups['within7'] as $g) {
                                                $u_dates[] = $g['ma_date'];
                                            }
                                        }
                                        // แปลงเป็น JSON และตัดวันที่ซ้ำออก
                                        echo json_encode(array_values(array_unique($u_dates)));
                                        ?>;

                    fullCalendar = new FullCalendar.Calendar(calendarEl, {
                        locale: 'th',
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: ''
                        },
                        events: <?php echo json_encode($calendar_events); ?>,

                        // 2. ฟังก์ชันระบายสีพื้นหลังช่องวันที่
                        dayCellDidMount: function(info) {
                            // ถ้าวันที่ของช่องนั้น (info.dateStr) ตรงกับรายการเร่งด่วน
                            if (urgentDates.includes(info.dateStr)) {
                                // ใส่สีพื้นหลังเหลืองจางๆ (เปลี่ยนรหัสสีตรงนี้ได้)
                                info.el.style.backgroundColor = '#fffbeb';
                                info.el.style.transition = 'background-color 0.3s';
                            }
                        },

                        eventClick: function(info) {
                            loadDetail(info.event.id);
                        },
                        datesSet: function() {
                            updateMonthlySummary();
                        }
                    });
                    fullCalendar.render();
                } else {
                    fullCalendar.updateSize();
                    updateMonthlySummary();
                }
            }, 200);
        }


        function closeModals(e) {
            if (e.target === e.currentTarget) {
                $(e.currentTarget).removeClass('show');
            }
        }

        // เพิ่มพารามิเตอร์ currentDate เข้าไปในฟังก์ชัน
        // เพิ่มพารามิเตอร์ projectName และ maNote
        function markAsComplete(id, currentDate, projectName, maNote, btn) {
            Swal.fire({
                title: '<span style="color: #1e293b; font-size: 1.4rem; font-weight: 700;">บันทึกผลดำเนินงาน</span>',
                html: `
            <div style="text-align:left; padding: 10px 5px;">
                <div style="background: #eff6ff; padding: 12px; border-radius: 12px; border: 1px solid #dbeafe; margin-bottom: 20px;">
                    <div style="font-size: 0.8rem; color: #3b82f6; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">ข้อมูลงาน</div>
                    <div style="font-size: 1rem; color: #1e3a8a; font-weight: 700; line-height: 1.4;">${projectName}</div>
                    <div style="font-size: 0.9rem; color: #60a5fa; margin-top: 2px;">
                        <i class="fas fa-tag"></i> ${maNote || 'งาน MA'}
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px;">
                        <i class="far fa-calendar-alt"></i> วันที่ดำเนินการจริง
                    </label>
                    <input type="date" id="swal-ma-date" class="swal2-input" 
                           style="margin: 0; width: 100%; border-radius: 10px; border: 1px solid #e2e8f0; font-family: 'Sarabun', sans-serif;" 
                           value="${currentDate}">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px;">
                        <i class="far fa-edit"></i> หมายเหตุ / รายละเอียดงาน
                    </label>
                    <textarea id="swal-remark" class="swal2-textarea" 
                              style="margin: 0; width: 100%; height: 100px; border-radius: 10px; border: 1px solid #e2e8f0; font-family: 'Sarabun', sans-serif; font-size: 0.9rem;" 
                              placeholder="ระบุรายละเอียดการเข้าบริการ..."></textarea>
                </div>

                <div style="background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px;">
                        <i class="fas fa-paperclip"></i> แนบไฟล์หลักฐาน (รูปภาพหรือ PDF)
                    </label>
                    <input type="file" id="swal-file" 
                           style="font-size: 0.8rem; color: #64748b; width: 100%;">
                </div>
            </div>
        `,
                icon: 'info',
                // ... (ส่วนที่เหลือของ Swal.fire เหมือนเดิมของคุณ) ...
                iconColor: '#3b82f6',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check-circle"></i> บันทึก',
                cancelButtonText: 'ไว้ทีหลัง',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#94a3b8',
                reverseButtons: true,
                preConfirm: () => {
                    const maDate = document.getElementById('swal-ma-date').value;
                    const remark = document.getElementById('swal-remark').value;
                    const file = document.getElementById('swal-file').files[0];

                    if (!maDate) {
                        Swal.showValidationMessage('กรุณาระบุวันที่ดำเนินการ');
                        return false;
                    }

                    const formData = new FormData();
                    formData.append('action', 'mark_complete');
                    formData.append('ma_id', id);
                    formData.append('ma_date', maDate);
                    formData.append('remark', remark);
                    if (file) {
                        formData.append('file', file);
                    }
                    return formData;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'warn_admin.php',
                        type: 'POST',
                        data: result.value,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'บันทึกสำเร็จ',
                                    timer: 1000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('ผิดพลาด', res.error, 'error');
                            }
                        }
                    });
                }
            });
        }

        function animateCounters() {
            $('.stat-val').each(function() {
                const $el = $(this);
                const target = parseInt($el.data('count'), 10) || 0;

                // ✅ ถ้าเป็น 0 แสดงทันที ไม่ต้อง animate
                if (target === 0) {
                    $el.text('0');
                    return;
                }

                let current = 0;
                const duration = 800;
                const stepTime = Math.max(Math.floor(duration / target), 20);

                const counter = setInterval(() => {
                    current++;
                    $el.text(current);

                    if (current >= target) {
                        $el.text(target);
                        clearInterval(counter);
                    }
                }, stepTime);
            });
        }

        $(document).ready(function() {
            animateCounters();
        });


        // เรียกตอนโหลดหน้า
        $(document).ready(function() {
            animateCounters();
        });

        function updateMonthlySummary() {
            if (!fullCalendar) return;

            const currentViewDate = fullCalendar.getDate();
            const currentMonth = currentViewDate.getMonth();
            const currentYear = currentViewDate.getFullYear();

            // ข้อมูล events ทั้งหมดจาก PHP
            const allEvents = <?= json_encode($calendar_events) ?>;

            // กรองเอาเฉพาะเดือนที่แสดง
            const monthlyEvents = allEvents.filter(ev => {
                const evDate = new Date(ev.start);
                return evDate.getMonth() === currentMonth && evDate.getFullYear() === currentYear;
            });

            const listContainer = $('#monthly-list');
            listContainer.empty();

            if (monthlyEvents.length === 0) {
                listContainer.append('<div style="text-align:center; color:#94a3b8; padding:20px;">ไม่มีงานในเดือนนี้</div>');
                return;
            }

            monthlyEvents.forEach(ev => {
                const html = `
            <div class="summary-item" onclick="loadDetail(${ev.id})" style="border-left-color: ${ev.borderColor}">
                <div class="summary-item-title">${ev.title}</div>
                <div class="summary-item-sub"><i class="far fa-building"></i> อื่นๆ</div>
            </div>
        `;
                listContainer.append(html);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.getElementById('logout-btn');
            const modal = document.getElementById('custom-logout-modal');
            const cancelBtn = document.getElementById('modal-cancel-btn');
            const confirmBtn = document.getElementById('modal-logout-btn');

            if (!logoutBtn || !modal) return;

            // คลิกปุ่มออกจากระบบ → เปิด modal
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.classList.add('show');
            });

            // คลิกยกเลิก → ปิด modal
            cancelBtn.addEventListener('click', function() {
                modal.classList.remove('show');
            });

            // คลิกยืนยัน → logout
            confirmBtn.addEventListener('click', function() {
                window.location.href = 'logout.php';
            });

            // คลิกพื้นหลัง → ปิด modal
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>