<?php
// หน้า Alarms เเจ้งเตือนของ user
session_start();
include_once 'auth.php';
require_once 'db.php';

date_default_timezone_set('Asia/Bangkok');

function dateThai($strDate)
{
    if (!$strDate || $strDate == '0000-00-00')
        return '-';
    $strYear = date("Y", strtotime($strDate)) + 543;
    $strMonth = date("n", strtotime($strDate));
    $strDay = date("j", strtotime($strDate));
    $strMonthCut = array("", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค.");
    return "$strDay " . $strMonthCut[$strMonth] . " $strYear";
}

function getProjectStatusText($status)
{
    switch ((int) $status) {
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

function getProjectStatusBadgeClass($status)
{
    switch ((int) $status) {
        case 1:
            return 'background-color: #FEF3C7; color: #92400E;';
        case 2:
            return 'background-color: #E0E7FF; color: #3730A3;';
        case 3:
            return 'background-color: #DCFCE7; color: #166534;';
        default:
            return 'background-color: #F3F4F6; color: #6B7280;';
    }
}

// =========================================================
// PART 1: API HANDLER (AJAX)
// =========================================================
if (isset($_POST['action']) && $_POST['action'] == 'get_ma_detail') {
    header('Content-Type: application/json');
    if (!isset($_POST['ma_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        exit;
    }
    $ma_id = mysqli_real_escape_string($conn, $_POST['ma_id']);

    $sql_detail = "SELECT m.*, p.project_name, p.deliver_work_date, p.end_date, p.status as project_status,
                          c.customers_name, c.address, c.phone, c.contact_name
                   FROM ma_schedule m 
                   JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
                   LEFT JOIN customers c ON p.customers_id = c.customers_id
                   WHERE m.ma_id = '$ma_id'";
    $result_detail = mysqli_query($conn, $sql_detail);

    if ($result_detail && $row = mysqli_fetch_assoc($result_detail)) {
        $project_name = htmlspecialchars($row['project_name']);
        $customer_name = htmlspecialchars($row['customers_name']);
        $note_text = $row['remark'] ?: $row['note'];
        $note = !empty($note_text) ? nl2br(htmlspecialchars($note_text)) : 'ไม่มีข้อมูลเพิ่มเติม';
        $contact = !empty($row['contact_name']) ? htmlspecialchars($row['contact_name']) : '-';
        $phone = !empty($row['phone']) ? htmlspecialchars($row['phone']) : '-';
        $ma_date_thai = dateThai($row['ma_date']);
        $start_contract = dateThai($row['deliver_work_date']);
        $end_contract = dateThai($row['end_date']);
        $status_label = getProjectStatusText($row['project_status']);
        $status_style = getProjectStatusBadgeClass($row['project_status']);

        $html = "
        <div class='fade-in-up' style='font-family: \"Sarabun\", sans-serif;'>
            <div class='d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 text-start'>
                <h5 class='fw-bold text-primary mb-0'><i class='fas fa-info-circle me-2'></i>รายละเอียดงาน MA</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='card border-0 mb-3 shadow-sm rounded-4' style='background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%); border-left: 5px solid #0d6efd !important;'>
                <div class='card-body p-3 text-start'>
                    <div class='d-flex align-items-center'>
                        <div class='bg-primary text-white rounded-3 d-flex align-items-center justify-content-center me-3 flex-shrink-0 shadow-sm' style='width: 50px; height: 50px;'>
                            <i class='fas fa-clipboard-list fs-4'></i>
                        </div>
                        <div>
                            <div class='text-muted small fw-bold mb-1'>ชื่อโครงการ</div>
                            <h6 class='fw-bold mb-0 text-dark' style='line-height: 1.4;'>{$project_name}</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class='row g-3'>
                <div class='col-md-12 text-start'>
                    <div class='p-2 px-3 bg-light rounded-3 border-start border-3 border-primary shadow-sm'>
                        <label class='text-muted' style='font-size: 0.75rem; font-weight: 600;'> ข้อมูลลูกค้า</label>
                        <div class='fw-bold text-dark'>{$customer_name}</div>
                    </div>
                </div>
                <div class='col-6 text-start'>
                    <div class='p-2 px-3 bg-white rounded-3 border shadow-sm h-100'>
                        <label class='text-muted d-block' style='font-size: 0.75rem;'> วันเริ่มสัญญา</label>
                        <span class='fw-bold text-dark' style='font-size: 0.9rem;'>{$start_contract}</span>
                    </div>
                </div>
                <div class='col-6 text-start'>
                    <div class='p-2 px-3 bg-white rounded-3 border shadow-sm h-100'>
                        <label class='text-muted d-block' style='font-size: 0.75rem;'> วันสิ้นสุดสัญญา</label>
                        <span class='fw-bold text-dark' style='font-size: 0.9rem;'>{$end_contract}</span>
                    </div>
                </div>
                <div class='col-6 text-start'>
                    <div class='p-2 px-3 bg-white rounded-3 border shadow-sm h-100'>
                        <label class='text-muted d-block' style='font-size: 0.75rem;'> ผู้ติดต่อ</label>
                        <span class='fw-bold text-dark'>{$contact}</span>
                    </div>
                </div>
                <div class='col-6 text-start'>
                    <div class='p-2 px-3 bg-white rounded-3 border shadow-sm h-100'>
                        <label class='text-muted d-block' style='font-size: 0.75rem;'> เบอร์โทร</label>
                        <span class='fw-bold text-primary'>{$phone}</span>
                    </div>
                </div>
                <div class='col-6 text-start'>
                    <div class='p-2 px-3 rounded-3 border shadow-sm' style='background-color: #fff5f5;'>
                        <label class='text-danger d-block' style='font-size: 0.75rem; font-weight: 600;'> กำหนด MA</label>
                        <span class='fw-bold text-danger fs-6'>{$ma_date_thai}</span>
                    </div>
                </div>
                <div class='col-6 text-start'>
                    <div class='p-2 px-3 rounded-3 border shadow-sm bg-white h-100'>
                        <label class='text-muted d-block' style='font-size: 0.75rem;'> สถานะ</label>
                        <span class='badge rounded-pill px-3 mt-1' style='{$status_style} font-weight: 600;'>{$status_label}</span>
                    </div>
                </div>
                <div class='col-12 text-start'>
                    <div class='p-3 bg-light rounded-3 border-top border-3 border-secondary'>
                        <label class='text-secondary d-block mb-1' style='font-size: 0.75rem; font-weight: 600;'> รายละเอียด / Note</label>
                        <div class='text-dark small' style='line-height: 1.5;'>{$note}</div>
                    </div>
                </div>
            </div>
        </div>";
        echo json_encode(['success' => true, 'html' => $html]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล']);
    }
    exit;
}

// =========================================================
// PART 2: DATA FETCHING
// =========================================================
$today = date('Y-m-d');
$today_obj = new DateTime($today);
$sql_ma = "SELECT m.ma_id, m.ma_date, m.note, p.project_name, p.deliver_work_date, p.end_date, c.customers_name, c.contact_name, c.phone
           FROM ma_schedule m
           JOIN pm_project p ON m.pmproject_id = p.pmproject_id
           LEFT JOIN customers c ON p.customers_id = c.customers_id
           WHERE m.is_done = 0 AND m.ma_date IS NOT NULL AND m.ma_date >= '2000-01-01'
           ORDER BY m.ma_date ASC";

$result_ma = mysqli_query($conn, $sql_ma);
$all_notifications = [];
$stats = ['all' => 0, 'within7' => 0, 'within90' => 0, 'overdue' => 0];

if ($result_ma) {
    $stats['all'] = mysqli_num_rows($result_ma);
    while ($row = mysqli_fetch_assoc($result_ma)) {
        $target_obj = new DateTime($row['ma_date']);
        $interval = $today_obj->diff($target_obj);
        $days_left = (int) $interval->format('%r%a');

        if ($days_left < 0) {
            $time_text = "เกินกำหนด " . abs($days_left) . " วัน";
            $badge_class = "st-critical";
            $stats['overdue']++;
        } elseif ($days_left <= 7) {
            $time_text = ($days_left == 0) ? "วันนี้!" : "อีก $days_left วัน";
            $badge_class = "st-warning";
            $stats['within7']++;
        } elseif ($days_left <= 90) {
            $time_text = "อีก $days_left วัน";
            $badge_class = "st-info";
            $stats['within90']++;
        } else {
            $time_text = "อีก $days_left วัน";
            $badge_class = "st-normal";
        }

        $all_notifications[] = [
            'id' => $row['ma_id'],
            'title' => $row['project_name'],
            'customer' => $row['customers_name'],
            'contact' => $row['contact_name'],
            'phone' => $row['phone'],
            'date_str' => date('d/m/', strtotime($row['ma_date'])) . (date('Y', strtotime($row['ma_date'])) + 543),
            'time_text' => $time_text,
            'badge_class' => $badge_class,
            'note' => $row['note']
        ];
    }
}

$sql_calendar = "SELECT m.ma_id, m.ma_date, p.project_name, c.customers_name FROM ma_schedule m JOIN pm_project p ON m.pmproject_id = p.pmproject_id LEFT JOIN customers c ON p.customers_id = c.customers_id WHERE m.is_done = 0 AND m.ma_date IS NOT NULL AND m.ma_date >= '2000-01-01' ORDER BY m.ma_date ASC";
$result_calendar = mysqli_query($conn, $sql_calendar);
$calendar_events = [];
while ($cal_row = mysqli_fetch_assoc($result_calendar)) {
    $date = $cal_row['ma_date'];
    if (!isset($calendar_events[$date]))
        $calendar_events[$date] = [];
    $calendar_events[$date][] = ['id' => $cal_row['ma_id'], 'title' => $cal_row['project_name'], 'customer' => $cal_row['customers_name']];
}
$json_calendar_events = json_encode($calendar_events, JSON_UNESCAPED_UNICODE) ?: '{}';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - Alarms</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --bg-body: #f3f5f9;
            --font-main: 'Sarabun', sans-serif;
            --card-radius: 12px;
        }

        body {
            background-color: var(--bg-body);
            font-family: var(--font-main);
            color: #444;
            margin: 0;
        }

        .main-content {
            padding: 25px;
            transition: all 0.3s;
        }

        .inventory-header {
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            margin-bottom: 25px;
            border-left: 6px solid #0d6efd;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            border-radius: var(--card-radius);
            padding: 25px;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .grad-gray {
            background: linear-gradient(135deg, #64748b, #94a3b8);
        }

        .grad-orange {
            background: linear-gradient(135deg, #e67e22, #f39c12);
        }

        .grad-blue {
            background: linear-gradient(135deg, #2980b9, #3498db);
        }

        .grad-red {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
        }

        .stat-title {
            font-size: 0.9rem;
            font-weight: 600;
            opacity: 0.95;
            z-index: 2;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1;
            z-index: 2;
        }

        .stat-icon-bg {
            position: absolute;
            right: 10px;
            bottom: -15px;
            font-size: 6rem;
            opacity: 0.2;
            transform: rotate(-10deg);
            z-index: 1;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-start;
        }

        .search-box-wrapper {
            position: relative;
            width: 100%;
            max-width: 500px;
        }

        .search-box-wrapper input {
            padding: 10px 15px 10px 40px;
            border-radius: 25px;
            border: 1px solid #ced4da;
            width: 100%;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .search-box-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .table-responsive {
            background: #fff;
            padding: 20px;
            border-radius: var(--card-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .bordered-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #dee2e6;
        }

        .bordered-table th {
            background-color: #f8f9fa;
            color: #333 !important;
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            font-weight: bold;
            text-align: center;
        }

        .bordered-table td {
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            vertical-align: middle;
            color: #333;
        }

        .badge-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.8rem;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }

        .st-critical {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .st-warning {
            background: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ffe0b2;
        }

        .st-info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }

        .st-normal {
            background: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }

        .stat-card.active {
            outline: 3px solid #2962ff;
            outline-offset: 3px;
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(41, 98, 255, 0.4) !important;
            filter: brightness(1.1);
        }

        /* Calendar Modal */
        .calendar-modal-content {
            background: #ffffff;
            border-radius: 16px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: row;
            height: 700px;
            overflow: hidden;
        }

        .cal-left-panel {
            flex: 1.5;
            padding: 30px;
            border-right: 1px solid #f0f0f0;
        }

        .cal-right-panel {
            flex: 1;
            padding: 25px;
            background: #ffffff;
            overflow-y: auto;
        }

        .cal-cell {
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid #f1f5f9;
            color: #475569;
            aspect-ratio: 1/1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cal-cell:hover:not(:empty) {
            background: #f1f5f9;
            transform: scale(1.05);
        }

        .has-event-day {
            border: 2px solid #ef4444 !important;
            color: #ef4444 !important;
            background: #fef2f2 !important;
        }

        .today-day {
            background: #3b82f6 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .event-item-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #3b82f6;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            text-align: left;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        /* =============================================
           RESPONSIVE CONFIGURATIONS
           ============================================= */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-value {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .calendar-modal-content {
                flex-direction: column;
                height: auto;
                max-height: 95vh;
            }

            .cal-left-panel {
                border-right: none;
                border-bottom: 1px solid #eee;
                padding: 15px;
            }

            .cal-right-panel {
                padding: 15px;
                height: 350px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }

            .inventory-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }

            .inventory-header button {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 15px;
                min-height: 100px;
            }

            .stat-value {
                font-size: 2rem;
            }

            /* Table to Card Transformation */
            .bordered-table thead {
                display: none;
            }

            .bordered-table,
            .bordered-table tbody,
            .bordered-table tr,
            .bordered-table td {
                display: block;
                width: 100%;
            }

            .bordered-table tr {
                margin-bottom: 20px;
                border: 1px solid #dee2e6;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
                overflow: hidden;
            }

            .bordered-table td {
                border: none;
                border-bottom: 1px solid #f0f0f0;
                padding: 10px 15px;
                text-align: left !important;
                padding-left: 45% !important;
                position: relative;
            }

            .bordered-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 40%;
                font-weight: bold;
                color: #888;
                font-size: 0.75rem;
                text-transform: uppercase;
            }

            .bordered-table td:last-child {
                border-bottom: none;
                background: #f8f9fa;
                text-align: center !important;
                padding-left: 15px !important;
            }

            .badge-pill {
                width: 100%;
                font-size: 0.85rem;
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="inventory-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0 text-dark">Alarms</h2>
                <p class="text-muted mb-0">จัดการข้อมูลแจ้งเตือนและตรวจสอบกำหนดการบำรุงรักษา</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" onclick="openCalendarModal()">
                <i class="fas fa-calendar-alt me-2"></i> เปิดปฎิทินงาน
            </button>
        </div>

        <div class="stats-grid">
            <div class="stat-card grad-gray active" onclick="filterTable('all', this)">
                <div class="stat-title">งานทั้งหมด</div>
                <div class="stat-value"><?= $stats['all']; ?></div>
                <i class="fas fa-tasks stat-icon-bg"></i>
            </div>
            <div class="stat-card grad-orange" onclick="filterTable('st-warning', this)">
                <div class="stat-title">ภายใน 7 วัน</div>
                <div class="stat-value"><?= $stats['within7']; ?></div>
                <i class="fas fa-clock stat-icon-bg"></i>
            </div>
            <div class="stat-card grad-blue" onclick="filterTable('st-info', this)">
                <div class="stat-title">ภายใน 90 วัน</div>
                <div class="stat-value"><?= $stats['within90']; ?></div>
                <i class="fas fa-calendar-day stat-icon-bg"></i>
            </div>
            <div class="stat-card grad-red" onclick="filterTable('st-critical', this)">
                <div class="stat-title">เกินกำหนด</div>
                <div class="stat-value"><?= $stats['overdue']; ?></div>
                <i class="fas fa-exclamation-circle stat-icon-bg"></i>
            </div>
        </div>

        <div class="search-container">
            <div class="search-box-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="tableSearchInput" placeholder="ค้นหาชื่อโครงการ, ชื่อลูกค้า, หรือรายละเอียด...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="bordered-table" id="notifTable">
                <thead>
                    <tr>
                        <th width="15%">กำหนด MA</th>
                        <th width="35%">โครงการ / รายละเอียด</th>
                        <th width="25%">ลูกค้า / ผู้ติดต่อ</th>
                        <th width="15%">ระยะเวลาคงเหลือ</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody id="notifTableBody">
                    <?php if (!empty($all_notifications)):
                        foreach ($all_notifications as $notif): ?>
                            <tr class="ma-row <?php echo $notif['badge_class']; ?>">
                                <td data-label="กำหนด MA" class="text-center fw-bold"><?php echo $notif['date_str']; ?></td>
                                <td data-label="โครงการ">
                                    <div class="fw-bold"><?php echo htmlspecialchars($notif['title']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($notif['note']); ?></small>
                                </td>
                                <td data-label="ลูกค้า">
                                    <div><?php echo htmlspecialchars($notif['customer']); ?></div>
                                    <small class="fw-bold text-primary"><?php echo htmlspecialchars($notif['phone']); ?></small>
                                </td>
                                <td data-label="คงเหลือ" class="text-center">
                                    <span
                                        class="badge-pill <?php echo $notif['badge_class']; ?>"><?php echo $notif['time_text']; ?></span>
                                </td>
                                <td data-label="จัดการ" class="text-center">
                                    <button class="btn btn-sm btn-outline-primary d-sm-none w-100 mb-1"
                                        onclick="viewProject(<?php echo $notif['id']; ?>)">ดูรายละเอียด</button>
                                    <button class="btn btn-sm btn-outline-dark d-none d-sm-inline-block"
                                        onclick="viewProject(<?php echo $notif['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">ไม่พบข้อมูลงาน MA</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-4" id="modalNote"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="calendarModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 1000px;">
            <div class="modal-content calendar-modal-content">
                <div class="cal-left-panel">
                    <div class="cal-header-top d-flex justify-content-between align-items-center mb-3">
                        <div class="cal-month-label fw-bold fs-5" id="monthYearLabel"></div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-light border" id="prevMonth"><i
                                    class="fas fa-chevron-left"></i></button>
                            <button class="btn btn-sm btn-light border" id="nextMonth"><i
                                    class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="cal-week-row d-grid mb-2"
                        style="grid-template-columns: repeat(7, 1fr); text-align:center; font-weight:bold; font-size:0.8rem; color:#888;">
                        <div>อา</div>
                        <div>จ</div>
                        <div>อ</div>
                        <div>พ</div>
                        <div>พฤ</div>
                        <div>ศ</div>
                        <div>ส</div>
                    </div>
                    <div class="cal-days-grid d-grid" id="calendarDays"
                        style="grid-template-columns: repeat(7, 1fr); gap: 5px;"></div>
                </div>
                <div class="cal-right-panel">
                    <div class="side-header border-bottom pb-2 mb-3 d-flex justify-content-between align-items-center">
                        <span id="sideTitle" class="fw-bold">รายละเอียดงาน</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div id="monthEventList"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const calendarEvents = <?php echo $json_calendar_events; ?>;
        const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
        let currDate = new Date(); let currMonth = currDate.getMonth(); let currYear = currDate.getFullYear();

        $(document).ready(function () {
            $("#tableSearchInput").on("keyup", function () {
                var value = $(this).val().toLowerCase();
                $("#notifTableBody tr").filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });

        function openCalendarModal() {
            renderCalendar(currMonth, currYear);
            new bootstrap.Modal(document.getElementById('calendarModal')).show();
        }

        function renderCalendar(month, year) {
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const grid = document.getElementById('calendarDays');
            document.getElementById('monthYearLabel').innerText = `${monthNames[month]} ${year + 543}`;
            let html = "";
            for (let i = 0; i < firstDay; i++) html += `<div></div>`;
            for (let day = 1; day <= daysInMonth; day++) {
                let d = String(day).padStart(2, '0'), m = String(month + 1).padStart(2, '0'), key = `${year}-${m}-${d}`;
                let eventClass = calendarEvents[key] ? 'has-event-day' : '';
                let todayClass = (day === new Date().getDate() && month === new Date().getMonth() && year === new Date().getFullYear()) ? 'today-day' : '';
                html += `<div class="cal-cell ${eventClass} ${todayClass}" onclick="showDayEvents('${key}')">${day}</div>`;
            }
            grid.innerHTML = html;
            showDayEvents(null);
        }

        function showDayEvents(key) {
            const list = document.getElementById('monthEventList');
            let events = [];
            if (key && calendarEvents[key]) {
                events = calendarEvents[key];
                document.getElementById('sideTitle').innerText = "งานวันที่ " + key.split('-').reverse().join('/');
            } else {
                document.getElementById('sideTitle').innerText = "งานประจำเดือนนี้";
                Object.keys(calendarEvents).forEach(k => {
                    if (k.startsWith(`${currYear}-${String(currMonth + 1).padStart(2, '0')}`)) {
                        calendarEvents[k].forEach(e => events.push({ ...e, date: k }));
                    }
                });
            }
            if (events.length === 0) {
                list.innerHTML = `<div class='text-center mt-5'><i class='fas fa-folder-open fa-3x mb-3 opacity-20'></i><p>ไม่มีรายการงาน</p></div>`;
            } else {
                list.innerHTML = events.map(e => `
                    <div class="event-item-card shadow-sm" onclick="viewProject(${e.id})">
                        <div class="fw-bold mb-1">${e.title}</div>
                        <small class="d-block mb-1 text-muted">${e.customer}</small>
                        ${e.date ? `<small class='text-primary fw-bold'><i class='far fa-calendar-alt me-1'></i>${e.date.split('-').reverse().join('/')}</small>` : ''}
                    </div>`).join('');
            }
        }

        function viewProject(id) {
            // ปิด Modal ปฏิทินถ้าเปิดอยู่
            const calModal = bootstrap.Modal.getInstance(document.getElementById('calendarModal'));
            if (calModal) calModal.hide();

            $.post('warn_user.php', { action: 'get_ma_detail', ma_id: id }, function (res) {
                if (res.success) {
                    $('#modalNote').html(res.html);
                    new bootstrap.Modal(document.getElementById('detailModal')).show();
                }
            }, 'json');
        }

        document.getElementById('prevMonth').onclick = () => { currMonth--; if (currMonth < 0) { currMonth = 11; currYear--; } renderCalendar(currMonth, currYear); };
        document.getElementById('nextMonth').onclick = () => { currMonth++; if (currMonth > 11) { currMonth = 0; currYear++; } renderCalendar(currMonth, currYear); };

        function filterTable(statusClass, element) {
            $('.stat-card').removeClass('active');
            if (element) $(element).addClass('active');
            if (statusClass === 'all') { $('.ma-row').fadeIn(200); }
            else { $('.ma-row').hide(); $('.' + statusClass).fadeIn(200); }
        }
    </script>
</body>

</html>