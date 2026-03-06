<?php
/**
 * ไฟล์: warn_user.php
 * คำอธิบาย: หน้าแจ้งเตือน (Alarms) สำหรับ User
 * แสดงรายการ MA ที่ใกล้ถึงกำหนด, เกินกำหนด และปฏิทินงาน
 */

session_start();
include_once 'auth.php'; 
require_once 'db.php';   

date_default_timezone_set('Asia/Bangkok');

// ---------------------------------------------------------------------------
// HELPER FUNCTIONS
// ---------------------------------------------------------------------------

function dateThai($strDate) {
    if(!$strDate || $strDate == '0000-00-00') return '-';
    $strYear = date("Y", strtotime($strDate)) + 543;
    $strMonth = date("n", strtotime($strDate));
    $strDay = date("j", strtotime($strDate));
    $strMonthCut = Array("", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค.");
    return "$strDay " . $strMonthCut[$strMonth] . " $strYear";
}

function getProjectStatusText($status) {
    switch ((int)$status) {
        case 1: return 'รอการตรวจสอบ';
        case 2: return 'กำลังดำเนินการ';
        case 3: return 'ดำเนินการเสร็จสิ้น';
        default: return '-';
    }
}

function getProjectStatusBadgeClass($status) {
    switch ((int)$status) {
        case 1: return 'background-color: #FEF3C7; color: #92400E;'; 
        case 2: return 'background-color: #E0E7FF; color: #3730A3;'; 
        case 3: return 'background-color: #DCFCE7; color: #166534;'; 
        default: return 'background-color: #F3F4F6; color: #6B7280;';
    }
}

// ===========================================================================
// PART 1: API HANDLER (AJAX Request)
// ===========================================================================
if (isset($_POST['action']) && $_POST['action'] == 'get_ma_detail') {
    header('Content-Type: application/json');
    
    if (!isset($_POST['ma_id'])) { 
        echo json_encode(['success' => false, 'message' => 'Missing ID']); 
        exit; 
    }
    
    $ma_id = mysqli_real_escape_string($conn, $_POST['ma_id']);
    
    // Query ข้อมูลรายละเอียด
    $sql_detail = "SELECT m.*, p.project_name, p.deliver_work_date, p.end_date, p.status as project_status,
                          c.customers_name, c.address, c.phone, c.contact_name
                   FROM ma_schedule m 
                   JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
                   LEFT JOIN customers c ON p.customers_id = c.customers_id
                   WHERE m.ma_id = '$ma_id'";     
    $result_detail = mysqli_query($conn, $sql_detail);
    
    if ($result_detail && $row = mysqli_fetch_assoc($result_detail)) {
        // เตรียมตัวแปรสำหรับแสดงผล
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

        // สร้าง HTML สำหรับ Modal Detail
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

// ===========================================================================
// PART 2: PAGE RENDERING (Fetch Data)
// ===========================================================================
$today = date('Y-m-d');
$today_obj = new DateTime($today);

// ดึงข้อมูล MA ที่ยังไม่เสร็จ (is_done=0)
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
        $interval   = $today_obj->diff($target_obj);
        $days_left  = (int)$interval->format('%r%a');

        // จัดกลุ่มสถานะตามวันที่เหลือ
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

// ดึงข้อมูลสำหรับปฏิทิน
$sql_calendar = "SELECT m.ma_id, m.ma_date, p.project_name, c.customers_name 
                 FROM ma_schedule m 
                 JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
                 LEFT JOIN customers c ON p.customers_id = c.customers_id 
                 WHERE m.is_done = 0 AND m.ma_date IS NOT NULL AND m.ma_date >= '2000-01-01' 
                 ORDER BY m.ma_date ASC";
$result_calendar = mysqli_query($conn, $sql_calendar);
$calendar_events = [];

while ($cal_row = mysqli_fetch_assoc($result_calendar)) {
    $date = $cal_row['ma_date'];
    if (!isset($calendar_events[$date])) $calendar_events[$date] = [];
    $calendar_events[$date][] = [
        'id' => $cal_row['ma_id'], 
        'title' => $cal_row['project_name'], 
        'customer' => $cal_row['customers_name']
    ];
}

// แปลงข้อมูลปฏิทินเป็น JSON
$json_calendar_events = json_encode($calendar_events, JSON_UNESCAPED_UNICODE) ?: '{}';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alarms & Calendar - MaintDash</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- External Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/warn_user.css">
</head>
<body>
    
    <!-- Sidebar -->
    <?php include 'sidebar_user.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="inventory-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0 text-dark">Alarms</h2>
                <p class="text-muted mb-0">จัดการข้อมูลแจ้งเตือนและตรวจสอบกำหนดการบำรุงรักษา</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" onclick="openCalendarModal()">
                <i class="fas fa-calendar-alt me-2"></i> เปิดปฎิทินงาน
            </button>
        </div>

        <!-- Stats Grid -->
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

        <!-- Search Toolbar -->
        <div class="search-container">
            <div class="search-box-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="tableSearchInput" placeholder="ค้นหาชื่อโครงการ, ชื่อลูกค้า, หรือรายละเอียด...">
            </div>
        </div>

        <!-- Data Table -->
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
                    <?php if (!empty($all_notifications)): foreach ($all_notifications as $notif): ?>
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
                            <span class="badge-pill <?php echo $notif['badge_class']; ?>"><?php echo $notif['time_text']; ?></span>
                        </td>
                        <td data-label="จัดการ" class="text-center">
                            <button class="btn btn-sm btn-outline-primary d-sm-none w-100 mb-1" onclick="viewProject(<?php echo $notif['id']; ?>)">ดูรายละเอียด</button>
                            <button class="btn btn-sm btn-outline-dark d-none d-sm-inline-block" onclick="viewProject(<?php echo $notif['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">ไม่พบข้อมูลงาน MA</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: รายละเอียดงาน (Detail) -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-4" id="modalNote"></div>
            </div>
        </div>
    </div>
    
    <!-- Modal: ปฏิทิน (Calendar) -->
    <div class="modal fade" id="calendarModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 1000px;">
            <div class="modal-content calendar-modal-content">
                <!-- Calendar Left Panel -->
                <div class="cal-left-panel">
                    <div class="cal-header-top d-flex justify-content-between align-items-center mb-3">
                        <div class="cal-month-label fw-bold fs-5" id="monthYearLabel"></div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-light border" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn btn-sm btn-light border" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="cal-week-row d-grid mb-2" style="grid-template-columns: repeat(7, 1fr); text-align:center; font-weight:bold; font-size:0.8rem; color:#888;">
                        <div>อา</div><div>จ</div><div>อ</div><div>พ</div><div>พฤ</div><div>ศ</div><div>ส</div>
                    </div>
                    <div class="cal-days-grid d-grid" id="calendarDays" style="grid-template-columns: repeat(7, 1fr); gap: 5px;"></div>
                </div>
                <!-- Calendar Right Panel (Event List) -->
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const calendarEvents = <?php echo $json_calendar_events; ?>;
    </script>
    <script src="assets/js/warn_user.js"></script>
</body>
</html>
