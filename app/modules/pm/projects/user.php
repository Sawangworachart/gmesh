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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management - Mesh Intelligence</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/pmproject_user.css">
</head>
<body>

    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="page-header-card animate-zoom">
            <div class="header-title-group">
                <h1>Preventive Maintenance</h1>
                <div class="header-subtitle">บริหารจัดการโครงการและแผนการบำรุงรักษา (PM/MA)</div>
            </div>
        </div>

        <div class="stats-grid animate-zoom">
            <div class="stat-card grad-blue">
                <div><div class="stat-label">โครงการทั้งหมด</div><div class="stat-val"><?= $stats['total'] ?></div></div>
                <i class="fas fa-layer-group stat-icon-bg"></i>
            </div>
            
            <div class="stat-card grad-green">
                <div><div class="stat-label">รอการตรวจสอบ</div><div class="stat-val"><?= $stats['pending'] ?></div></div>
                <i class="fas fa-hourglass-half stat-icon-bg"></i>
            </div>
            
            <div class="stat-card grad-purple">
                <div><div class="stat-label">กำลังดำเนินการ</div><div class="stat-val"><?= $stats['doing'] ?></div></div>
                <i class="fas fa-spinner stat-icon-bg"></i>
            </div>
            
            <div class="stat-card grad-orange">
                <div><div class="stat-label">ดำเนินการเสร็จสิ้น</div><div class="stat-val"><?= $stats['done'] ?></div></div>
                <i class="fas fa-check-circle stat-icon-bg"></i>
            </div>
        </div>

        <div class="toolbar-container animate-zoom">
            <div style="font-size: 1.2rem; font-weight: 700;"><i class="fas fa-list-ul"></i> รายการโครงการ</div>
            <div class="search-pill">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อโครงการ, รหัส, ลูกค้า..." onkeyup="searchTable('projectTable', 'searchInput')">
            </div>
        </div>

        <div class="table-responsive animate-zoom">
            <table class="bordered-table" id="projectTable">
                <thead>
                    <tr>
                        <th width="8%">เลขที่โครงการ</th>
                        <th width="12%">ชื่อโครงการ</th> 
                        <th width="15%">รายละเอียด (MA)</th> 
                        <th width="14%">ลูกค้า</th>
                        <th width="10%">ผู้รับผิดชอบ</th>
                        <th width="8%">สถานะ</th>
                        <th width="9%">สัญญา</th>
                        <th width="10%">วันที่เริ่ม / วันที่สิ้นสุด</th>
                        <th width="5%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($projects)): ?>
                        <tr><td colspan="9" class="text-center" style="padding:50px; color:#bdc3c7;">
                            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i><br>ไม่พบข้อมูลโครงการ
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($projects as $index => $row): 
                            $statusText = $row['status'];
                            $badgeClass = 'st-default';

                            if($statusText == 'กำลังดำเนินการ') { 
                                $badgeClass = 'st-progress'; 
                            } elseif($statusText == 'ดำเนินการเสร็จสิ้น') { 
                                $badgeClass = 'st-completed'; 
                            } elseif($statusText == 'รอการตรวจสอบ') { 
                                $badgeClass = 'st-pending'; 
                            }
                            
                            $resName = $row['responsible'] ? $row['responsible'] : '-';
                        ?>
                        <tr>
                            <td><span style="font-weight:700; color:#4e73df; font-size:0.85rem;"><?= htmlspecialchars($row['project_no']) ?></span></td>
                            <td title="<?= htmlspecialchars($row['name']) ?>"><span class="proj-name"><?= htmlspecialchars($row['name']) ?></span></td>
                            
                            <td title="<?= htmlspecialchars($row['ma_detail']) ?>">
                                <div class="ma-detail-cell"><?= $row['ma_detail'] ? htmlspecialchars($row['ma_detail']) : '-' ?></div>
                            </td>
                            
                            <td><span style="font-size:0.9rem;"><?= htmlspecialchars($row['customer']) ?></span></td>
                            <td><span style="font-size:0.9rem;"><?= htmlspecialchars($resName) ?></span></td>
                            
                            <td><span class="status-pill <?= $badgeClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                            
                            <td style="font-size:0.9rem;"><?= htmlspecialchars($row['contract_period']) ?></td>
                            <td>
                                <div style="font-size:0.8rem;">
                                    <div style="color:#2980b9;"><i class="fas fa-play"></i> <?= formatDate($row['start_date']) ?></div>
                                    <div style="color:#c0392b;"><i class="fas fa-flag"></i> <?= formatDate($row['end_date']) ?></div>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-action" 
                                    onclick="viewDetail(this)"
                                    data-id="<?= $row['id'] ?>"
                                    data-no="<?= htmlspecialchars($row['project_no']) ?>"
                                    data-name="<?= htmlspecialchars($row['name']) ?>"
                                    data-customer="<?= htmlspecialchars($row['customer']) ?>"
                                    data-responsible="<?= htmlspecialchars($resName) ?>"
                                    data-status="<?= htmlspecialchars($statusText) ?>"
                                    data-contract="<?= htmlspecialchars($row['contract_period']) ?>"
                                    data-ma="<?= htmlspecialchars($row['ma_detail']) ?>"
                                    data-start="<?= formatDate($row['start_date']) ?>"
                                    data-end="<?= formatDate($row['end_date']) ?>"
                                    title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <div class="modal-header-blue">
                <div style="display:flex; flex-direction:column;">
                    <span id="view_no" style="font-size:0.85rem; opacity:0.8; margin-bottom:2px;">-</span>
                    <h2 id="view_name">รายละเอียดโครงการ</h2>
                </div>
                <button class="close-modal-white" onclick="closeViewModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="info-grid">
                    <div class="info-item" style="border-left-color: #4e73df;">
                        <label>ลูกค้า / Customer</label><span id="view_customer">-</span>
                    </div>
                    <div class="info-item" style="border-left-color: #1cc88a;">
                        <label>ผู้รับผิดชอบ</label><span id="view_responsible">-</span>
                    </div>
                    <div class="info-item" style="border-left-color: #9b59b6;">
                        <label>สถานะโครงการ</label><span id="view_status_badge">-</span>
                    </div>
                    <div class="info-item" style="border-left-color: #36b9cc;">
                        <label>วันส่งมอบงาน</label><span id="view_start">-</span>
                    </div>
                    <div class="info-item" style="border-left-color: #f6c23e;">
                        <label>วันสิ้นสุดสัญญา</label><span id="view_end">-</span>
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <div class="section-head"><i class="fas fa-info-circle"></i> รายละเอียดโครงการ</div>
                    <div class="content-box ma-text-box" id="view_ma">-</div>
                </div>

                <div>
                    <div class="section-head">
                        <i class="fas fa-history"></i> ประวัติ/แผนการบำรุงรักษา(MA): <span id="view_contract" style="color:#555; font-weight:500;">-</span>
                    </div>
                    <div class="content-box" style="padding:0; border:none;">
                        <div class="ma-table-wrapper">
                            <table class="ma-table">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="15%">วันที่</th>
                                        <th width="35%">รายละเอียด (Note)</th> <th width="35%">หมายเหตุ (Remark)</th> <th width="10%" class="text-center">ไฟล์</th> </tr>
                                </thead>
                                <tbody id="ma_table_body">
                                    <tr><td colspan="5" class="text-center">กำลังโหลดข้อมูล...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="js/pmproject_user.js"></script>
</body>
</html>