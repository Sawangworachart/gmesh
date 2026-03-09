<?php
// =========================================
// หน้า PM Project (User) - Project Management User View
// =========================================

session_start();
require_once 'includes/db.php'; // เชื่อมต่อฐานข้อมูล

// --------------------------------------------------------------------------
//  API Handler (จัดการ AJAX Request)
// --------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'get_ma_detail') {
    header('Content-Type: application/json');
    $project_id = intval($_GET['id']);
    
    // ดึงข้อมูลแผน PM (Schedule) ของโปรเจ็คนี้
    // หมายเหตุ: ปรับ Query ตามโครงสร้างจริงของตาราง pm_schedules หรือที่เกี่ยวข้อง
    // สมมติว่าใช้ contract_id เชื่อมโยง หรือ project_id โดยตรง
    // ในที่นี้สมมติว่าเชื่อมผ่าน contract_id ที่อยู่ใน Projects
    
    // 1. หา contract_id จาก project_id
    $contract_sql = "SELECT contract_id FROM Projects WHERE project_id = $project_id";
    $contract_res = mysqli_query($conn, $contract_sql);
    $contract_row = mysqli_fetch_assoc($contract_res);
    
    $schedule_data = [];
    
    if ($contract_row) {
        $contract_id = $contract_row['contract_id'];
        
        // 2. ดึงตาราง PM
        // เนื่องจากโครงสร้าง pm_schedules อาจจะเป็น Master Plan
        // หากต้องการ History การเข้า PM อาจต้องดูตารางอื่น เช่น service_reports หรือ pm_logs
        // แต่ถ้าเอาแค่แผน:
        $sql = "SELECT * FROM pm_schedules WHERE contract_id = $contract_id ORDER BY next_visit_date ASC";
        $result = mysqli_query($conn, $sql);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $schedule_data[] = [
                'formatted_date' => date('d/m/Y', strtotime($row['next_visit_date'])),
                'note' => $row['device_equipment'], // สมมติให้แสดงอุปกรณ์ในช่อง Note
                'remark' => $row['department'], // สมมติให้แสดงแผนก
                'has_file' => false, // ยังไม่มีระบบไฟล์แนบในตารางนี้
                'file_path' => '#'
            ];
        }
    }
    
    echo json_encode(['success' => true, 'schedule' => $schedule_data]);
    exit;
}

// --------------------------------------------------------------------------
//  Main Page Logic (แสดงผลหน้าเว็บ)
// --------------------------------------------------------------------------

// ดึงข้อมูลโปรเจ็คทั้งหมด (เฉพาะ User อาจจะกรองตามสิทธิ์ แต่ที่นี้ดึงหมดตามเดิม)
$sql = "SELECT p.*, c.customer_name 
        FROM Projects p 
        LEFT JOIN customers c ON p.customer_id = c.customer_id 
        ORDER BY p.project_id DESC";
$result = mysqli_query($conn, $sql);

// ตัวแปรสำหรับนับสถิติ
$total_projects = 0;
$status_counts = [
    'ongoing' => 0,   // กำลังดำเนินการ
    'completed' => 0, // ดำเนินการเสร็จสิ้น
    'pending' => 0    // รอการตรวจสอบ
];

$projects = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $projects[] = $row;
        $total_projects++;
        
        $st = trim($row['status']);
        if ($st == 'กำลังดำเนินการ') $status_counts['ongoing']++;
        elseif ($st == 'ดำเนินการเสร็จสิ้น') $status_counts['completed']++;
        elseif ($st == 'รอการตรวจสอบ') $status_counts['pending']++;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - PM Projects</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logomaintdash1.png">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/pmproject_user.css?v=<?php echo time(); ?>">
    
    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>
    
    <?php include 'includes/sidebar_user.php'; ?> <!-- เมนูด้านข้าง -->

    <div class="main-content">
        
        <!-- Header -->
        <div class="page-header-card animate-zoom">
            <div class="header-title-group">
                <h1>PM Projects</h1>
                <div class="header-subtitle">ติดตามสถานะโครงการและการบำรุงรักษา</div>
            </div>
            <div style="font-size:3rem; color:#e3e6f0; opacity:0.5;"><i class="fas fa-project-diagram"></i></div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid animate-zoom" style="animation-delay: 0.1s;">
            <div class="stat-card grad-blue">
                <div class="stat-label">โครงการทั้งหมด</div>
                <div class="stat-val"><?= $total_projects ?></div>
                <i class="fas fa-folder stat-icon-bg"></i>
            </div>
            <div class="stat-card grad-orange">
                <div class="stat-label">กำลังดำเนินการ</div>
                <div class="stat-val"><?= $status_counts['ongoing'] ?></div>
                <i class="fas fa-spinner stat-icon-bg"></i>
            </div>
            <div class="stat-card grad-green">
                <div class="stat-label">เสร็จสิ้น</div>
                <div class="stat-val"><?= $status_counts['completed'] ?></div>
                <i class="fas fa-check-circle stat-icon-bg"></i>
            </div>
            <div class="stat-card grad-purple">
                <div class="stat-label">รอตรวจสอบ</div>
                <div class="stat-val"><?= $status_counts['pending'] ?></div>
                <i class="fas fa-clock stat-icon-bg"></i>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar-container animate-zoom" style="animation-delay: 0.2s;">
            <div class="toolbar-title">
                <i class="fas fa-list-ul"></i> รายการโครงการ
            </div>
            <div class="search-pill">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อโครงการ, ลูกค้า..." onkeyup="searchTable('projectTable', 'searchInput')">
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive animate-zoom" style="animation-delay: 0.3s;">
            <table class="bordered-table" id="projectTable">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="20%">ชื่อโครงการ</th>
                        <th width="20%">ลูกค้า</th>
                        <th width="15%">ผู้รับผิดชอบ</th>
                        <th width="12%" class="text-center">ระยะเวลา</th>
                        <th width="12%" class="text-center">สถานะ</th>
                        <th width="8%" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="7" class="text-center" style="padding:40px; color:#94a3b8;">
                                <i class="far fa-folder-open fa-3x" style="margin-bottom:10px;"></i><br>ไม่พบข้อมูลโครงการ
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $idx => $row): 
                            // กำหนดคลาสสีของ Badge สถานะ
                            $status_class = 'st-default';
                            if ($row['status'] == 'กำลังดำเนินการ') $status_class = 'st-progress';
                            elseif ($row['status'] == 'ดำเนินการเสร็จสิ้น') $status_class = 'st-completed';
                            elseif ($row['status'] == 'รอการตรวจสอบ') $status_class = 'st-pending';
                            
                            $start_date = $row['start_date'] ? date('d/m/Y', strtotime($row['start_date'])) : '-';
                            $end_date = $row['end_date'] ? date('d/m/Y', strtotime($row['end_date'])) : '-';
                        ?>
                        <tr>
                            <td class="text-center"><?= $idx + 1 ?></td>
                            <td>
                                <span class="proj-name" title="<?= htmlspecialchars($row['project_name']) ?>"><?= htmlspecialchars($row['project_name']) ?></span>
                                <div style="font-size:0.8rem; color:#858796; margin-top:2px;">
                                    <?= htmlspecialchars($row['contract_number']) ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight:600; color:#5a5c69;"><?= htmlspecialchars($row['customer_name']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($row['responsible_person']) ?></td>
                            <td class="text-center" style="font-size:0.85rem;">
                                <div style="color:#1cc88a;"><?= $start_date ?></div>
                                <div style="color:#e74a3b;"><?= $end_date ?></div>
                            </td>
                            <td class="text-center">
                                <span class="status-pill <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                            </td>
                            <td class="text-center">
                                <button class="btn-action" 
                                    data-id="<?= $row['project_id'] ?>"
                                    data-no="<?= $idx + 1 ?>"
                                    data-name="<?= htmlspecialchars($row['project_name']) ?>"
                                    data-customer="<?= htmlspecialchars($row['customer_name']) ?>"
                                    data-responsible="<?= htmlspecialchars($row['responsible_person']) ?>"
                                    data-start="<?= $start_date ?>"
                                    data-end="<?= $end_date ?>"
                                    data-contract="<?= htmlspecialchars($row['contract_number']) ?>"
                                    data-ma="<?= htmlspecialchars($row['warranty_period']) ?>"
                                    data-status="<?= htmlspecialchars($row['status']) ?>"
                                    onclick="viewDetail(this)" title="ดูรายละเอียด">
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

    <!-- Modal: View Details -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header-blue">
                <h2><i class="fas fa-tasks"></i> รายละเอียดโครงการ</h2>
                <button class="close-modal-white" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-body custom-scroll">
                
                <!-- Project Info -->
                <div class="section-head"><i class="fas fa-info-circle"></i> ข้อมูลทั่วไป</div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <label>ชื่อโครงการ</label>
                        <span id="view_name">-</span>
                    </div>
                    <div class="info-item">
                        <label>ลูกค้า</label>
                        <span id="view_customer">-</span>
                    </div>
                    <div class="info-item">
                        <label>เลขที่สัญญา</label>
                        <span id="view_contract">-</span>
                    </div>
                    <div class="info-item">
                        <label>ผู้รับผิดชอบ</label>
                        <span id="view_responsible">-</span>
                    </div>
                    <div class="info-item">
                        <label>ระยะเวลาประกัน (MA)</label>
                        <span id="view_ma">-</span>
                    </div>
                    <div class="info-item">
                        <label>สถานะปัจจุบัน</label>
                        <span id="view_status_badge">-</span>
                    </div>
                    <div class="info-item">
                        <label>วันที่เริ่มสัญญา</label>
                        <span id="view_start" style="color:#1cc88a;">-</span>
                    </div>
                    <div class="info-item">
                        <label>วันที่สิ้นสุดสัญญา</label>
                        <span id="view_end" style="color:#e74a3b;">-</span>
                    </div>
                </div>

                <!-- MA Plan Table -->
                <div class="section-head"><i class="fas fa-calendar-alt"></i> แผนการบำรุงรักษา (PM Plan)</div>
                
                <div class="content-box">
                    <table class="ma-table">
                        <thead>
                            <tr>
                                <th width="10%" class="text-center">ครั้งที่</th>
                                <th width="20%">วันที่เข้าบำรุงรักษา</th>
                                <th width="30%">อุปกรณ์</th>
                                <th width="25%">แผนก/หน่วยงาน</th>
                                <th width="15%" class="text-center">ไฟล์แนบ</th>
                            </tr>
                        </thead>
                        <tbody id="ma_table_body">
                            <!-- Data loaded via AJAX -->
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="assets/js/pmproject_user.js?v=<?php echo time(); ?>"></script>
</body>
</html>
