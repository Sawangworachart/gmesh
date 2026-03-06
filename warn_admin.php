<?php
// =========================================
// หน้า Alarms (Admin) - warn_admin.php
// =========================================

ob_start();
session_start();
date_default_timezone_set('Asia/Bangkok');

require_once 'auth.php';
require_once 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 0); // ปิดการแสดง error หน้าเว็บ (ควรดู log แทน)

// กำหนดวันที่ปัจจุบัน
$today_str = date('Y-m-d');
$today_obj = new DateTime($today_str);

// --- Helper Functions ---

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
        case 1: return 'รอการตรวจสอบ';
        case 2: return 'กำลังดำเนินการ';
        case 3: return 'ดำเนินการเสร็จสิ้น';
        default: return '-';
    }
}

function projectStatusStyle($status)
{
    switch ((int)$status) {
        case 1: return ['color' => '#92400E', 'bg' => '#FEF3C7']; // รอตรวจสอบ
        case 2: return ['color' => '#3730A3', 'bg' => '#E0E7FF']; // กำลังดำเนินการ
        case 3: return ['color' => '#166534', 'bg' => '#DCFCE7']; // เสร็จสิ้น
        default: return ['color' => '#6B7280', 'bg' => '#F3F4F6'];
    }
}

// --------------------------------------------------------------------------
//  AJAX HANDLER
// --------------------------------------------------------------------------
if (isset($_POST['action'])) {
    // ล้าง Output Buffer เพื่อให้มั่นใจว่าส่ง JSON กลับไปอย่างเดียว
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'];

    // 1. บันทึกงานสำเร็จ (MARK COMPLETE)
    if ($action == 'mark_complete') {
        $ma_id = mysqli_real_escape_string($conn, $_POST['ma_id']);
        $ma_date = mysqli_real_escape_string($conn, $_POST['ma_date']);
        $remark = mysqli_real_escape_string($conn, $_POST['remark']);

        $file_sql_part = "";
        $finalFilePath = "";

        // จัดการไฟล์
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $uploadDir = 'uploads/ma/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileExt = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $fileName = "ma_" . $ma_id . "_" . time() . "." . $fileExt;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $finalFilePath = $targetPath;
                $file_sql_part = ", file_path = '$finalFilePath'";
            }
        }

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
        exit;
    }

    // 2. ดึงรายละเอียด (GET MA DETAIL)
    if ($action === 'get_ma_detail') {
        $ma_id = mysqli_real_escape_string($conn, $_POST['ma_id']);

        $sql = "SELECT m.*, p.project_name, p.responsible_person, p.status_remark, 
                       p.deliver_work_date, p.end_date, p.status as project_status,
                       c.customers_name, c.phone, c.contact_name
                FROM ma_schedule m
                JOIN pm_project p ON m.pmproject_id = p.pmproject_id
                LEFT JOIN customers c ON p.customers_id = c.customers_id
                WHERE m.ma_id = '$ma_id'";
        
        $result = mysqli_query($conn, $sql);

        if ($row = mysqli_fetch_assoc($result)) {
            // สร้าง HTML สำหรับ Modal
            $style = projectStatusStyle($row['project_status']);
            $statusText = projectStatusText($row['project_status']);
            
            $html = '<div style="font-family:\'Sarabun\', sans-serif;">';
            $html .= '<div style="text-align:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">';
            $html .= '    <h3 style="margin:0; color:#1e293b; font-size:1.2rem;">รายละเอียดงาน MA</h3>';
            $html .= '</div>';

            $html .= '<div style="display:flex; flex-direction:column; gap:8px; font-size: 0.95rem;">';
            
            $rows = [
                ['label' => 'ชื่อโครงการ', 'val' => $row['project_name'], 'color' => '#1e293b', 'bold' => true, 'icon' => 'fas fa-clipboard-list'],
                ['label' => 'ข้อมูลลูกค้า',   'val' => $row['customers_name'], 'color' => '#1e293b', 'icon' => 'fas fa-building'],
                ['label' => 'วันเริ่มสัญญา', 'val' => dateThai($row['deliver_work_date']), 'color' => '#1e293b', 'icon' => 'far fa-calendar-plus'],
                ['label' => 'วันสิ้นสุดสัญญา', 'val' => dateThai($row['end_date']), 'color' => '#1e293b', 'icon' => 'far fa-calendar-check'],
                ['label' => 'ผู้ติดต่อ', 'val' => $row['contact_name'], 'color' => '#1e293b', 'icon' => 'far fa-user'],
                ['label' => 'เบอร์โทร', 'val' => $row['phone'], 'color' => '#6366f1', 'icon' => 'fas fa-phone'],
                ['label' => 'กำหนด MA', 'val' => dateThai($row['ma_date']), 'color' => '#ef4444', 'bold' => true, 'icon' => 'fas fa-tools'],
                ['label' => 'สถานะโครงการ', 'val' => $statusText, 'color' => $style['color'], 'bg' => $style['bg'], 'icon' => 'fas fa-info-circle']
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
            $html .= '  <div style="color:#475569; font-size:0.9rem; line-height:1.4;">' . (nl2br(htmlspecialchars($row['note'] ?? '')) ?: '-') . '</div>';
            $html .= '</div>';
            $html .= '</div>';

            echo json_encode(['success' => true, 'html' => $html]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูล']);
        }
        exit;
    }
}

// --------------------------------------------------------------------------
//  MAIN PAGE LOGIC
// --------------------------------------------------------------------------

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

// เตรียมข้อมูลวันที่เร่งด่วนสำหรับ JS
$urgent_dates = [];
foreach ($groups['within7'] as $g) {
    $urgent_dates[] = $g['ma_date'];
}
$urgent_dates_json = json_encode(array_values(array_unique($urgent_dates)));
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - Alarms</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- CSS Libraries -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/warn_admin.css?v=<?php echo time(); ?>">
    
    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/th.js'></script>
</head>

<body>
    
    <div class="layout">
        
        <div class="sidebar-space"></div> <!-- Placeholder for sidebar -->
        <?php include 'sidebar.php'; ?>

        <div class="main-content">

            <!-- Banner -->
            <div class="header-banner-custom">
                <div class="header-left-content">
                    <div class="header-icon-circle"><i class="fas fa-bell"></i></div>
                    <div class="header-text-group">
                        <h2 class="header-main-title">Alarms</h2>
                        <p class="header-sub-desc">จัดการข้อมูลการแจ้งเตือนและตรวจสอบกำหนดการบำรุงรักษา</p>
                    </div>
                </div>
                <div class="header-right-action">
                    <button id="btn-show-calendar" class="btn-pill-primary" onclick="triggerCalendarModal()">
                        <i class="fas fa-calendar-check"></i> เปิดปฏิทินงาน
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stat-grid">
                <div class="stat-box active" id="card-all" data-tab="all" style="border-left-color:#10b981;" onclick="switchTab('all')">
                    <div class="stat-icon-box" style="background:#d1fae5; color:#059669;"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-info">
                        <div style="color:#64748b; font-weight:600;">งานทั้งหมด</div>
                        <div class="stat-val" data-count="<?= count($groups['all']) ?>">0</div>
                    </div>
                </div>

                <div class="stat-box" id="card-urgent" data-tab="within7" style="border-left-color:#f59e0b;" onclick="switchTab('within7')">
                    <div class="stat-icon-box" style="background:#ffedd5; color:#ea580c;"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <div style="color:#9a3412; font-weight:600;">ภายใน 7 วัน</div>
                        <div class="stat-val" data-count="<?= count($groups['within7']) ?>">0</div>
                    </div>
                </div>

                <div class="stat-box" id="card-normal" data-tab="within90" style="border-left-color:#6366f1;" onclick="switchTab('within90')">
                    <div class="stat-icon-box" style="background:#e0e7ff; color:#4338ca;"><i class="far fa-calendar-alt"></i></div>
                    <div class="stat-info">
                        <div style="color:#3730a3; font-weight:600;">ภายใน 90 วัน</div>
                        <div class="stat-val" data-count="<?= count($groups['within90']) ?>">0</div>
                    </div>
                </div>

                <div class="stat-box" id="card-overdue" data-tab="overdue" style="border-left-color:#ef4444;" onclick="switchTab('overdue')">
                    <div class="stat-icon-box" style="background:#fee2e2; color:#b91c1c;"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-info">
                        <div style="color:#991b1b; font-weight:600;">งานที่เกินกำหนด</div>
                        <div class="stat-val" data-count="<?= count($groups['overdue']) ?>">0</div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Alerts -->
            <?php if (!empty($groups['within7'])): ?>
                <div class="upcoming-wrapper">
                    <div class="upcoming-header">
                        <h3><i class="fas fa-clock"></i> งานที่กำลังจะถึงภายใน 7 วัน</h3>
                        <span class="upcoming-count"><?= count($groups['within7']) ?> งาน</span>
                    </div>

                    <div class="upcoming-scroll">
                        <?php foreach ($groups['within7'] as $item): ?>
                            <div id="card-<?= $item['ma_id'] ?>" class="upcoming-card" onclick="loadDetail(<?= $item['ma_id'] ?>)">
                                <div class="upcoming-days warning">
                                    <?= $item['diff'] == 0 ? 'วันนี้' : 'อีก ' . $item['diff'] . ' วัน' ?>
                                </div>
                                <div class="upcoming-body">
                                    <div class="project-name"><?= htmlspecialchars($item['project_name']) ?></div>
                                    <div class="project-meta"><i class="fas fa-user"></i> <?= htmlspecialchars($item['customers_name'] ?: '-') ?></div>
                                    <div class="project-date"><i class="fas fa-calendar-day"></i> <?= dateThai($item['ma_date']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Table -->
            <div class="table-container">
                <div class="ma-tabs">
                    <button class="ma-tab active" data-tab="all" onclick="switchTab('all')">
                        ทั้งหมด <span class="ma-badge"><?= count($groups['all']) ?></span>
                    </button>
                    <button class="ma-tab" data-tab="within7" onclick="switchTab('within7')">
                        งานที่ครบกำหนดภายใน 7 วัน <span class="ma-badge warning"><?= count($groups['within7']) ?></span>
                    </button>
                    <button class="ma-tab" data-tab="within90" onclick="switchTab('within90')">
                        งานที่ครบกำหนดภายใน 90 วัน <span class="ma-badge info"><?= count($groups['within90']) ?></span>
                    </button>
                    <button class="ma-tab" data-tab="overdue" onclick="switchTab('overdue')">
                        งานที่เกินกำหนด <span class="ma-badge danger"><?= count($groups['overdue']) ?></span>
                    </button>
                </div>

                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="ค้นหาชื่อโครงการ, ลูกค้า...">
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
                                        <td colspan="4" style="text-align:center; color:#94a3b8; padding:30px;">ไม่มีรายการในส่วนนี้</td>
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
                                            <div style="font-weight:600; color:#1e293b;"><?= htmlspecialchars($i['project_name']) ?></div>
                                            <div style="font-size:0.85rem; color:#64748b; margin-top:2px;">
                                                <i class="far fa-user"></i> <?= htmlspecialchars($i['customers_name']) ?>
                                            </div>
                                        </td>
                                        <td style="text-align:center;">
                                            <button class="action-btn btn-view" onclick="loadDetail(<?= $i['ma_id'] ?>)" title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($d <= 7): ?>
                                                <button class="action-btn btn-complete" 
                                                    onclick="markAsComplete(<?= $i['ma_id'] ?>, '<?= $i['ma_date'] ?>', '<?= htmlspecialchars($i['project_name']) ?>', '<?= htmlspecialchars($i['note']) ?>', this)"
                                                    title="บันทึกว่างานเสร็จแล้ว">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>

        </div><!-- END main-content -->
    </div><!-- END layout -->

    <!-- Modal Detail -->
    <div id="modalDetail" class="modal-overlay" onclick="closeModals(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div style="display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                <img src="images/logomaintdash1.png" alt="Logo" style="height: 40px; width: auto;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.2rem;">รายละเอียดงาน</h3>
            </div>
            <div id="detailBody"></div>
            <div style="text-align:right; margin-top:25px;">
                <button class="btn-close-modal" onclick="$('#modalDetail').removeClass('show')">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>

    <!-- Modal Calendar -->
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
                    <div id="monthly-list" class="summary-list"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass PHP Variables to JS -->
    <script>
        const calendarEvents = <?= json_encode($calendar_events); ?>;
        const urgentDates = <?= $urgent_dates_json; ?>;
    </script>

    <!-- Custom JS -->
    <script src="assets/js/warn_admin.js?v=<?php echo time(); ?>"></script>

</body>
</html>
