<?php
// =========================================
// หน้า Service Project (Admin)
// =========================================

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once 'auth.php'; // ตรวจสอบสิทธิ์การเข้าถึง
include_once 'db.php';   // เชื่อมต่อฐานข้อมูล

// สร้างโฟลเดอร์สำหรับเก็บไฟล์อัปโหลดหากยังไม่มี
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// --------------------------------------------------------------------------
//  HELPER FUNCTIONS (ฟังก์ชันช่วยทำงาน)
// --------------------------------------------------------------------------

// แปลงข้อความเป็น ID สถานะ (1=On-site, 2=Remote, 3=Subcontractor)
function getStatusId($text)
{
    $map = ['On-site' => 1, 'Remote' => 2, 'Subcontractor' => 3];
    return $map[$text] ?? 1;
}

// แปลง ID สถานะเป็นข้อความ
function getStatusText($id)
{
    $map = [1 => 'On-site', 2 => 'Remote', 3 => 'แจ้ง Subcontractor'];
    return $map[$id] ?? 'On-site';
}

// --------------------------------------------------------------------------
//  API HANDLER (จัดการคำขอ AJAX)
// --------------------------------------------------------------------------
if (isset($_GET['api']) && $_GET['api'] == 'true') {
    ob_clean(); // ล้าง Output Buffer เพื่อให้ JSON สะอาด
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    try {
        // 1. ดึงข้อมูลลูกค้าทั้งหมด (Fetch Customers)
        if ($action == 'fetch_customers') {
            $sql = "SELECT customers_id, customers_name, agency, contact_name FROM customers ORDER BY customers_name ASC";
            $result = $conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        // 2. ดึงข้อมูลงานบริการทั้งหมด (Fetch All Services)
        if ($action == 'fetch_all') {
            $sql = "SELECT 
                s.service_id, d.detail_id, s.project_name, d.start_date, d.end_date, d.service_type,
                d.equipment, d.`s/n` as sn, d.number, d.symptom, d.action_taken, d.file_path,
                c.customers_name, c.agency, c.phone
            FROM service_project_detail d
            LEFT JOIN service_project_new s ON s.service_id = d.service_id
            LEFT JOIN customers c ON d.customers_id = c.customers_id
            ORDER BY d.start_date DESC";

            $result = $conn->query($sql);
            if (!$result) {
                echo json_encode(['success' => false, 'message' => $conn->error]);
                exit;
            }

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $row['status'] = getStatusText($row['service_type']);
                $data[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        // 3. สรุปสถานะงาน (Status Summary)
        if ($action == 'fetch_status_summary') {
            $summary = ['On-site' => 0, 'Remote' => 0, 'แจ้ง Subcontractor' => 0, 'Total' => 0];
            $res = $conn->query("SELECT service_type, COUNT(*) as count FROM service_project_detail GROUP BY service_type");

            while ($row = $res->fetch_assoc()) {
                $status_key = getStatusText($row['service_type']);
                $summary[$status_key] = (int)$row['count'];
                $summary['Total'] += (int)$row['count'];
            }
            echo json_encode(['success' => true, 'data' => $summary]);
            exit;
        }

        // 4. ดึงข้อมูลงานเดี่ยว (Fetch Single)
        if ($action == 'fetch_single') {
            $id = intval($_GET['id']);
            $sql = "SELECT 
                        s.service_id, s.project_name, d.start_date, d.end_date, d.detail_id, d.customers_id,
                        d.service_type, d.equipment, d.`s/n` as sn, d.number, d.symptom, d.action_taken, d.file_path,
                        c.customers_name, c.agency, c.phone, c.address, c.contact_name
                    FROM service_project_new s
                    LEFT JOIN service_project_detail d ON s.service_id = d.service_id
                    LEFT JOIN customers c ON d.customers_id = c.customers_id
                    WHERE d.detail_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if ($row) {
                $row['status_val'] = getStatusText($row['service_type']);
            }
            echo json_encode(['success' => true, 'data' => $row]);
            exit;
        }

        // 5. บันทึกข้อมูล (Save Data - Insert/Update)
        if ($action == 'save_data') {
            $service_id  = intval($_POST['service_id'] ?? 0);
            $detail_id   = intval($_POST['detail_id'] ?? 0);
            $customers_id = intval($_POST['customers_id']);
            $project_name = trim($_POST['project_name']);
            $statusString   = $_POST['status'];
            $serviceTypeInt = getStatusId($statusString);
            $start_date = $_POST['start_date'];
            $end_date   = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
            $equipment     = $_POST['equipment'] ?? '';
            $sn            = $_POST['sn'] ?? '';
            $number        = $_POST['number'] ?? '';
            $symptom       = $_POST['symptom'] ?? '';
            $action_taken  = $_POST['action_taken'] ?? '';

            $conn->begin_transaction(); // เริ่ม Transaction

            try {
                // --- จัดการไฟล์อัปโหลด ---
                $filenameToSave = null;
                if (isset($_FILES['service_file']) && $_FILES['service_file']['error'] == 0) {
                    $ext = pathinfo($_FILES['service_file']['name'], PATHINFO_EXTENSION);
                    $newFilename = 'service_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $targetPath = 'uploads/' . $newFilename;
                    if (move_uploaded_file($_FILES['service_file']['tmp_name'], $targetPath)) {
                        $filenameToSave = $newFilename;
                    }
                }

                // --- A. ค้นหาหรือสร้าง Project ใหม่ ---
                $stmtFind = $conn->prepare("SELECT service_id FROM service_project_new WHERE project_name = ? LIMIT 1");
                $stmtFind->bind_param("s", $project_name);
                $stmtFind->execute();
                $resFind = $stmtFind->get_result();

                if ($resFind->num_rows > 0) {
                    $row = $resFind->fetch_assoc();
                    $service_id = $row['service_id'];
                } else {
                    $stmtInsert = $conn->prepare("INSERT INTO service_project_new (project_name) VALUES (?)");
                    $stmtInsert->bind_param("s", $project_name);
                    $stmtInsert->execute();
                    $service_id = $conn->insert_id;
                }

                // --- B. บันทึกรายละเอียด (Insert / Update) ---
                if ($detail_id > 0) {
                    // กรณีแก้ไข (Update)
                    $sql = "UPDATE service_project_detail SET customers_id=?, service_type=?, equipment=?, `s/n`=?, number=?, symptom=?, action_taken=?, start_date=?, end_date=?";
                    if ($filenameToSave) $sql .= ", file_path=?";
                    $sql .= " WHERE detail_id=?";
                    
                    $stmtD = $conn->prepare($sql);
                    if ($filenameToSave) {
                        $stmtD->bind_param("iissssssssi", $customers_id, $serviceTypeInt, $equipment, $sn, $number, $symptom, $action_taken, $start_date, $end_date, $filenameToSave, $detail_id);
                    } else {
                        $stmtD->bind_param("iisssssssi", $customers_id, $serviceTypeInt, $equipment, $sn, $number, $symptom, $action_taken, $start_date, $end_date, $detail_id);
                    }
                    $stmtD->execute();
                } else {
                    // กรณีเพิ่มใหม่ (Insert)
                    $stmtD = $conn->prepare("INSERT INTO service_project_detail (service_id, customers_id, service_type, equipment, `s/n`, number, symptom, action_taken, start_date, end_date, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtD->bind_param("iiissssssss", $service_id, $customers_id, $serviceTypeInt, $equipment, $sn, $number, $symptom, $action_taken, $start_date, $end_date, $filenameToSave);
                    $stmtD->execute();
                }

                $conn->commit(); // ยืนยันการบันทึก
                echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
            } catch (Exception $e) {
                $conn->rollback(); // ยกเลิกหากเกิดข้อผิดพลาด
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// --------------------------------------------------------------------------
//  PHP HTML RENDER PART (เตรียมข้อมูลเบื้องต้นสำหรับหน้าเว็บ)
// --------------------------------------------------------------------------

// ดึงรายชื่อโครงการสำหรับ Filter
$project_filter_opt = [];
$p_res = $conn->query("SELECT DISTINCT project_name FROM service_project_new ORDER BY project_name ASC");
if ($p_res) {
    while ($r = $p_res->fetch_assoc()) {
        if (!empty($r['project_name'])) $project_filter_opt[] = $r['project_name'];
    }
}

// ดึงรายชื่อลูกค้าสำหรับ Dropdown
$customers_opt = [];
$c_res = $conn->query("SELECT customers_id, customers_name, contact_name FROM customers ORDER BY customers_name ASC");
if ($c_res) {
    while ($r = $c_res->fetch_assoc()) {
        $customers_opt[] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - Service Project</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- CSS Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS (แยกไฟล์) -->
    <link rel="stylesheet" href="assets/css/service_project.css?v=<?php echo time(); ?>">

    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">

        <!-- Header Banner -->
        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-box"><i class="fas fa-tools"></i></div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Service Project</h2>
                    <p class="header-sub-desc">ระบบบันทึกข้อมูลการแจ้งซ่อมและประวัติการเข้าบริการลูกค้า</p>
                </div>
            </div>

            <div class="header-right-action" style="display: flex; gap: 10px; align-items: center;">
                <a href="service_project_export.php" class="btn-add-custom" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <button class="btn-add-custom" onclick="openModal()">
                    <i class="fas fa-plus"></i> เพิ่มข้อมูล
                </button>
            </div>
        </div>

        <!-- Status Cards -->
        <div class="status-cards">
            <div class="status-card card-total active-filter" id="cardTotal" onclick="filterByStatus('')">
                <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                <div class="card-info"><h4>ทั้งหมด</h4><div class="count">0</div></div>
            </div>
            <div class="status-card card-onsite" id="cardOnsite" onclick="filterByStatus('On-site')">
                <div class="card-icon"><i class="fas fa-building"></i></div>
                <div class="card-info"><h4>On-site</h4><div class="count">0</div></div>
            </div>
            <div class="status-card card-remote" id="cardRemote" onclick="filterByStatus('Remote')">
                <div class="card-icon"><i class="fas fa-laptop-house"></i></div>
                <div class="card-info"><h4>Remote</h4><div class="count">0</div></div>
            </div>
            <div class="status-card card-sub" id="cardSub" onclick="filterByStatus('Subcontractor')">
                <div class="card-icon"><i class="fas fa-user-friends"></i></div>
                <div class="card-info"><h4>Subcontractor</h4><div class="count">0</div></div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="table-toolbar-combined">
            <div class="search-box-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่องาน, ลูกค้า, S/N..." onkeyup="filterTable()">
            </div>

            <div class="filter-box-wrapper">
                <select id="projectFilter" onchange="filterTable()">
                    <option value="">-- ดูทุกโครงการ --</option>
                    <?php foreach ($project_filter_opt as $proj): ?>
                        <option value="<?= htmlspecialchars($proj) ?>"><?= htmlspecialchars($proj) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-card glass-effect">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="10%" class="text-center">เลขที่โครงการ</th>
                            <th width="12%">วันที่เริ่ม / สิ้นสุด</th>
                            <th width="20%">โครงการ</th>
                            <th width="20%">ลูกค้า</th>
                            <th width="18%">อุปกรณ์</th>
                            <th width="10%" class="text-center">สถานะ</th>
                            <th width="10%" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr><td colspan="7" class="loading-state">กำลังโหลดข้อมูล...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal: เพิ่ม/แก้ไขข้อมูล -->
    <div class="modal-overlay" id="serviceModal">
        <div class="modal-box glass-modal">
            <div class="modal-header">
                <div class="modal-title-group">
                    <div class="modal-icon"><i class="fas fa-edit"></i></div>
                    <div class="modal-text">
                        <h3 id="modalTitle" style="margin:0; font-size:1.2rem;">บันทึกงานบริการ</h3>
                        <span style="color:#64748b; font-size:0.9rem;">จัดการรายละเอียดและผลการดำเนินงาน</span>
                    </div>
                </div>
                <button type="button" class="close-modal" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>

            <form id="serviceForm" enctype="multipart/form-data">
                <input type="hidden" id="service_id" name="service_id" value="0">
                <input type="hidden" id="detail_id" name="detail_id" value="0">

                <div class="form-section-title"><i class="fas fa-user-circle"></i> ข้อมูลลูกค้าและเลขที่อ้างอิง</div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">ลูกค้า <span style="color:red">*</span></label>
                        <div class="input-group-modern">
                            <select id="customers_id" name="customers_id" required class="form-control">
                                <option value="">-- เลือกลูกค้า --</option>
                                <?php foreach ($customers_opt as $cus): ?>
                                    <option value="<?= $cus['customers_id'] ?>">
                                        <?= htmlspecialchars($cus['customers_name']) ?>
                                        <?= !empty($cus['contact_name']) ? " (" . htmlspecialchars($cus['contact_name']) . ")" : "" ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="input-icon"><i class="fas fa-user-tie"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">เลขที่โครงการ</label>
                        <div class="input-group-modern">
                            <input type="text" id="number" name="number" class="form-control" placeholder="เช่น PJ67xxx">
                            <span class="input-icon"><i class="fas fa-hashtag"></i></span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">ชื่อโครงการ (Project)</label>
                    <div class="input-group-modern">
                        <input type="text" id="project_name" name="project_name" class="form-control" list="project_options" placeholder="เลือกโครงการเดิมหรือพิมพ์ใหม่" required>
                        <span class="input-icon"><i class="fas fa-project-diagram"></i></span>
                        <datalist id="project_options">
                            <?php foreach ($project_filter_opt as $proj): ?>
                                <option value="<?= htmlspecialchars($proj) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <div class="form-section-title"><i class="fas fa-tools"></i> รายละเอียดอุปกรณ์และปัญหา</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">อุปกรณ์ (Equipment)</label>
                        <div class="input-group-modern">
                            <input type="text" id="equipment" name="equipment" class="form-control">
                            <span class="input-icon"><i class="fas fa-microchip"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">S/N (Serial Number)</label>
                        <div class="input-group-modern">
                            <input type="text" id="sn" name="sn" class="form-control">
                            <span class="input-icon"><i class="fas fa-barcode"></i></span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">อาการเสีย / สิ่งที่พบ</label>
                    <div class="input-group-modern">
                        <textarea id="symptom" name="symptom" class="form-control" rows="2"></textarea>
                        <span class="input-icon" style="top: 20px;"><i class="fas fa-exclamation-triangle"></i></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">การแก้ไข / ดำเนินการ</label>
                    <div class="input-group-modern">
                        <textarea id="action_taken" name="action_taken" class="form-control" rows="2"></textarea>
                        <span class="input-icon" style="top: 20px;"><i class="fas fa-check-circle"></i></span>
                    </div>
                </div>

                <div class="form-section-title"><i class="fas fa-clock"></i> สถานะและระยะเวลา</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">สถานะ</label>
                        <div class="input-group-modern">
                            <select id="status" name="status" class="form-control">
                                <option value="On-site">On-site</option>
                                <option value="Remote">Remote</option>
                                <option value="Subcontractor">แจ้ง Subcontractor</option>
                            </select>
                            <span class="input-icon"><i class="fas fa-info-circle"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">วันที่เริ่ม</label>
                        <div class="input-group-modern">
                            <input type="date" id="start_date" name="start_date" class="form-control" required>
                            <span class="input-icon"><i class="fas fa-calendar-alt"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">วันที่สิ้นสุด</label>
                        <div class="input-group-modern">
                            <input type="date" id="end_date" name="end_date" class="form-control">
                            <span class="input-icon"><i class="fas fa-calendar-check"></i></span>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <div class="upload-ui" onclick="document.getElementById('service_file').click()">
                        <div class="upload-icon" style="font-size: 2rem; color: #4361ee;"><i class="fas fa-file-upload"></i></div>
                        <div style="font-weight: 600; color: #475569;">แนบรูปภาพหรือไฟล์ PDF</div>
                        <div id="filePreview" style="margin-top:10px; font-size:0.9rem;"></div>
                        <input type="file" id="service_file" name="service_file" style="display:none;" onchange="previewFile(this)">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn-primary-gradient"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: ดูรายละเอียด (View) -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-box glass-modal" style="max-width: 850px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="modal-icon" style="background:#e0f2fe; color:#0ea5e9;">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <h3 id="view_project_name_header" style="margin:0; font-size: 1.4rem; color: #0c4a6e;">กำลังโหลด...</h3>
                        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.95rem;">
                            <span id="view_customer_name_header">-</span> | ID: <span id="view_ref_number_header">-</span>
                        </p>
                    </div>
                </div>
                <button type="button" class="close-modal" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body-custom custom-scroll" style="padding: 30px; background: #f8fafc; overflow-y: auto; flex:1;">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px;">
                    <div class="info-card" style="border-bottom: 4px solid #4361ee;">
                        <label class="info-card-title"><i class="fas fa-info-circle"></i> รูปแบบบริการ</label>
                        <div id="view_status_badge" style="font-size: 1.1rem;">-</div>
                    </div>
                    <div class="info-card" style="border-bottom: 4px solid #10b981;">
                        <label class="info-card-title"><i class="fas fa-calendar-day"></i> ช่วงเวลาดำเนินการ</label>
                        <div id="view_date_range" style="font-weight: 700; color: #1e293b;">-</div>
                    </div>
                    <div class="info-card" style="border-bottom: 4px solid #f59e0b;">
                        <label class="info-card-title"><i class="fas fa-microchip"></i> อุปกรณ์ / SN</label>
                        <div id="view_equipment_sn" style="font-weight: 700; color: #1e293b; line-height: 1.3;">-</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                    <div class="info-card">
                        <div class="info-card-title" style="color:#ef4444;"><i class="fas fa-exclamation-circle"></i> อาการเสีย / สิ่งที่พบ</div>
                        <div id="view_symptom" class="text-box-display">-</div>
                    </div>

                    <div class="info-card">
                        <div class="info-card-title" style="color:#10b981;"><i class="fas fa-check-circle"></i> การแก้ไข / ดำเนินการ</div>
                        <div id="view_action" class="text-box-display">-</div>
                    </div>

                    <div class="info-card">
                        <label class="info-card-title"><i class="fas fa-paperclip"></i> ไฟล์แนบ / หลักฐาน</label>
                        <div id="view_file_container" style="margin-top:10px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom JS (แยกไฟล์) -->
    <script src="assets/js/service_project.js?v=<?php echo time(); ?>"></script>

</body>
</html>