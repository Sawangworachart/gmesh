<?php
// หน้า Preventive Maintenance ของ user
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once 'auth.php'; 
require_once 'db.php';

// --- API SECTION (สำหรับดึงข้อมูลตาราง MA ผ่าน AJAX) ---
if (isset($_GET['action']) && $_GET['action'] == 'get_ma_detail') {
    while (ob_get_level()) ob_end_clean(); 
    header('Content-Type: application/json');
    
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        exit;
    }

    $id = intval($_GET['id']);
    
    $maSql = "SELECT * FROM ma_schedule WHERE pmproject_id = $id ORDER BY ma_date ASC";
    $maResult = mysqli_query($conn, $maSql);
    
    $schedule = [];
    if ($maResult) {
        while($row = mysqli_fetch_assoc($maResult)) {
            $dateObj = date_create($row['ma_date']);
            $y = date_format($dateObj, 'Y') + 543;
            $row['formatted_date'] = date_format($dateObj, 'd/m/') . $y;
            $row['has_file'] = (!empty($row['file_path']) && file_exists($row['file_path'])) ? true : false;
            $schedule[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'schedule' => $schedule]);
    exit;
}

// --- ดึงข้อมูล Main Project ---
$projects = [];
$sql = "SELECT p.*, c.customers_name 
        FROM pm_project p 
        LEFT JOIN customers c ON p.customers_id = c.customers_id 
        ORDER BY p.pmproject_id DESC";
$result = mysqli_query($conn, $sql);

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $rawStatus = trim($row['status']);
        $displayStatus = $rawStatus;
        
        if ($rawStatus == '2' || strcasecmp($rawStatus, 'กำลังดำเนินการ') == 0 || strcasecmp($rawStatus, 'In Progress') == 0) {
            $displayStatus = 'กำลังดำเนินการ';
        } elseif ($rawStatus == '3' || strcasecmp($rawStatus, 'ดำเนินการเสร็จสิ้น') == 0 || strcasecmp($rawStatus, 'Completed') == 0) {
            $displayStatus = 'ดำเนินการเสร็จสิ้น';
        } elseif ($rawStatus == '1' || strcasecmp($rawStatus, 'รอการตรวจสอบ') == 0 || strcasecmp($rawStatus, 'Pending') == 0) {
            $displayStatus = 'รอการตรวจสอบ';
        } else {
            $displayStatus = 'รอการตรวจสอบ'; 
        }

        $projects[] = [
            'id' => $row['pmproject_id'],
            'display_id' => $row['number'] ? $row['number'] : 'ID:'.$row['pmproject_id'],
            'project_no' => $row['number'],
            'name' => $row['project_name'],
            'customer' => $row['customers_name'],
            'responsible' => $row['responsible_person'],
            'status' => $displayStatus,
            'contract_period' => $row['contract_period'],
            'ma_detail' => $row['going_ma'],
            'start_date' => $row['deliver_work_date'],
            'end_date' => $row['end_date'],
            'file_path' => $row['file_path']
        ];
    }
}

$stats = ['total' => count($projects), 'pending' => 0, 'doing' => 0, 'done' => 0];
foreach ($projects as $p) {
    $st = $p['status'];
    if ($st == 'รอการตรวจสอบ') {
        $stats['pending']++;
    } elseif ($st == 'กำลังดำเนินการ') {
        $stats['doing']++;
    } elseif ($st == 'ดำเนินการเสร็จสิ้น') {
        $stats['done']++;
    }
}

function formatDate($date) {
    if (!$date || $date == '0000-00-00') return '-';
    $y = date('Y', strtotime($date)) + 543;
    return date('d/m/', strtotime($date)) . $y;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - PM Projects</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/pm_project.css">
</head>
<body>
    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Preventive Maintenance</h2>
                <p class="page-subtitle">ภาพรวมโครงการและแผนการบำรุงรักษาของคุณ</p>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card total"><div class="stat-icon"><i class="fas fa-layer-group"></i></div><div class="stat-info"><label>ทั้งหมด</label><span><?= $stats['total'] ?></span></div></div>
            <div class="stat-card pending"><div class="stat-icon"><i class="fas fa-clipboard-check"></i></div><div class="stat-info"><label>รอตรวจสอบ</label><span><?= $stats['pending'] ?></span></div></div>
            <div class="stat-card processing"><div class="stat-icon"><i class="fas fa-spinner"></i></div><div class="stat-info"><label>กำลังดำเนินการ</label><span><?= $stats['doing'] ?></span></div></div>
            <div class="stat-card completed"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-info"><label>เสร็จสิ้น</label><span><?= $stats['done'] ?></span></div></div>
        </div>

        <div class="table-toolbar">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อโครงการ, ลูกค้า..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table" id="projectTable">
                    <thead>
                        <tr>
                            <th>เลขที่โครงการ</th>
                            <th>ชื่อโครงการ</th>
                            <th>ลูกค้า</th>
                            <th class="text-center">สถานะ</th>
                            <th>สัญญา</th>
                            <th>เริ่ม / สิ้นสุดประกัน</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr><td colspan="7" class="text-center p-5 text-muted">ไม่พบข้อมูลโครงการ</td></tr>
                        <?php else: foreach ($projects as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['project_no']) ?></td>
                                <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['customer']) ?></td>
                                <?php
                                    $statusText = $row['status'];
                                    $statusClass = 'pending'; // default
                                    if ($statusText == 'กำลังดำเนินการ') {
                                        $statusClass = 'processing';
                                    } elseif ($statusText == 'ดำเนินการเสร็จสิ้น') {
                                        $statusClass = 'completed';
                                    }
                                ?>
                                <td class="text-center"><span class="status-pill status-<?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                                <td><?= htmlspecialchars($row['contract_period']) ?></td>
                                <td>
                                    <div><i class="fas fa-play-circle text-primary"></i> <?= formatDate($row['start_date']) ?></div>
                                    <div><i class="fas fa-flag-checkered text-danger"></i> <?= formatDate($row['end_date']) ?></div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info" onclick='openViewModal(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
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

    <!-- View Project Modal -->
    <div id="viewProjectModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 900px;">
            <div class="modal-header">
                <h3 id="view_project_name" class="modal-title"></h3>
                <button class="btn-close" onclick="closeViewModal()"></button>
            </div>
            <div class="modal-body">
                <div id="view_modal_content"></div>
            </div>
        </div>
    </div>

    <script src="js/pmproject_user.js"></script>
</body>
</html>