<?php
session_start();
// หน้า service ของ admin
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once 'auth.php';
include_once 'db.php';

// สร้างโฟลเดอร์ uploads ถ้ายังไม่มี
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// --------------------------------------------------------------------------
//  HELPER FUNCTIONS
// --------------------------------------------------------------------------
function getStatusId($text)
{
    // Mapping ตาม DB: 1=On-site, 2=Remote, 3=Subcontractor
    $map = ['On-site' => 1, 'Remote' => 2, 'Subcontractor' => 3];
    return $map[$text] ?? 1;
}

function getStatusText($id)
{
    $map = [1 => 'On-site', 2 => 'Remote', 3 => 'แจ้ง Subcontractor'];
    return $map[$id] ?? 'On-site';
}

// --------------------------------------------------------------------------
//  API HANDLER (UPDATED FOR service_type IN DETAIL)
// --------------------------------------------------------------------------
if (isset($_GET['api']) && $_GET['api'] == 'true') {
    ob_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    try {

        // 1. Fetch Customers
        if ($action == 'fetch_customers') {
            $sql = "SELECT customers_id, customers_name, agency, contact_name 
                    FROM customers ORDER BY customers_name ASC";
            $result = $conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        // 2. Fetch All
        if ($action == 'fetch_all') {
            $sql = "SELECT 
                s.service_id,
                d.detail_id,
                s.project_name,
                d.start_date,
                d.end_date,
                d.service_type,
                d.equipment,
                d.`s/n` as sn,
                d.number,
                d.symptom,
                d.action_taken,
                d.file_path,
                c.customers_name,
                c.agency,
                c.phone
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


        // 3. Status Summary (นับจาก DETAIL)
        if ($action == 'fetch_status_summary') {

            $summary = [
                'On-site' => 0,
                'Remote' => 0,
                'แจ้ง Subcontractor' => 0,
                'Total' => 0
            ];

            $res = $conn->query("
                SELECT service_type, COUNT(*) as count
                FROM service_project_detail
                GROUP BY service_type
            ");

            while ($row = $res->fetch_assoc()) {
                $status_key = getStatusText($row['service_type']);
                $summary[$status_key] = (int) $row['count'];
                $summary['Total'] += (int) $row['count'];
            }

            echo json_encode(['success' => true, 'data' => $summary]);
            exit;
        }

        // 4. Fetch Single
        if ($action == 'fetch_single') {
            $id = intval($_GET['id']);

            $sql = "SELECT 
                        s.service_id,
                        s.project_name,
                        d.start_date,
                        d.end_date,
                        d.detail_id,
                        d.customers_id,
                        d.service_type,
                        d.equipment,
                        d.`s/n` as sn,
                        d.number,
                        d.symptom,
                        d.action_taken,
                        d.file_path,
                        c.customers_name,
                        c.agency,
                        c.phone,
                        c.address,
                        c.contact_name
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

        // 5. Save Data
        // 5. Save Data  ✅ เวอร์ชันถูกต้อง
        if ($action == 'save_data') {

            $service_id = intval($_POST['service_id'] ?? 0);
            $detail_id = intval($_POST['detail_id'] ?? 0);

            $customers_id = intval($_POST['customers_id']);
            $project_name = trim($_POST['project_name']);

            $statusString = $_POST['status'];
            $serviceTypeInt = getStatusId($statusString);

            $start_date = $_POST['start_date'];
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;

            $equipment = $_POST['equipment'] ?? '';
            $sn = $_POST['sn'] ?? '';
            $number = $_POST['number'] ?? '';
            $symptom = $_POST['symptom'] ?? '';
            $action_taken = $_POST['action_taken'] ?? '';

            $conn->begin_transaction();

            try {

                // ---------------- FILE UPLOAD ----------------
                $filenameToSave = null;
                if (isset($_FILES['service_file']) && $_FILES['service_file']['error'] == 0) {
                    $ext = pathinfo($_FILES['service_file']['name'], PATHINFO_EXTENSION);
                    $newFilename = 'service_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $targetPath = 'uploads/' . $newFilename;

                    if (move_uploaded_file($_FILES['service_file']['tmp_name'], $targetPath)) {
                        $filenameToSave = $newFilename;
                    }
                }

                // ---------------- A. หา/สร้าง Project (สำคัญที่สุด) ----------------
                $stmtFind = $conn->prepare("
            SELECT service_id 
            FROM service_project_new 
            WHERE project_name = ?
            LIMIT 1
        ");
                $stmtFind->bind_param("s", $project_name);
                $stmtFind->execute();
                $resFind = $stmtFind->get_result();

                if ($resFind->num_rows > 0) {
                    $row = $resFind->fetch_assoc();
                    $service_id = $row['service_id'];
                } else {
                    $stmtInsert = $conn->prepare("
                INSERT INTO service_project_new (project_name)
                VALUES (?)
            ");
                    $stmtInsert->bind_param("s", $project_name);
                    $stmtInsert->execute();
                    $service_id = $conn->insert_id;
                }

                // ---------------- B. Insert / Update Detail ----------------
                if ($detail_id > 0) {

                    // UPDATE
                    $sql = "UPDATE service_project_detail SET
                        customers_id=?,
                        service_type=?,
                        equipment=?,
                        `s/n`=?,
                        number=?,
                        symptom=?,
                        action_taken=?,
                        start_date=?,
                        end_date=?";

                    if ($filenameToSave) {
                        $sql .= ", file_path=?";
                    }

                    $sql .= " WHERE detail_id=?";

                    $stmtD = $conn->prepare($sql);

                    if ($filenameToSave) {
                        $stmtD->bind_param(
                            "iissssssssi",
                            $customers_id,
                            $serviceTypeInt,
                            $equipment,
                            $sn,
                            $number,
                            $symptom,
                            $action_taken,
                            $start_date,
                            $end_date,
                            $filenameToSave,
                            $detail_id
                        );
                    } else {
                        $stmtD->bind_param(
                            "iisssssssi",
                            $customers_id,
                            $serviceTypeInt,
                            $equipment,
                            $sn,
                            $number,
                            $symptom,
                            $action_taken,
                            $start_date,
                            $end_date,
                            $detail_id
                        );
                    }

                    $stmtD->execute();
                } else {

                    // INSERT
                    $stmtD = $conn->prepare("
                INSERT INTO service_project_detail
                (service_id, customers_id, service_type, equipment, `s/n`, number, symptom, action_taken, start_date, end_date, file_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

                    $stmtD->bind_param(
                        "iiissssssss",
                        $service_id,
                        $customers_id,
                        $serviceTypeInt,
                        $equipment,
                        $sn,
                        $number,
                        $symptom,
                        $action_taken,
                        $start_date,
                        $end_date,
                        $filenameToSave
                    );

                    $stmtD->execute();
                }

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
            } catch (Exception $e) {
                $conn->rollback();
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
//  PHP HTML RENDER PART (Fetch Data for Filters/Dropdowns initially)
// --------------------------------------------------------------------------
// ดึงข้อมูล Project Name สำหรับ Filter
$project_filter_opt = [];
$p_res = $conn->query("SELECT DISTINCT project_name FROM service_project_new ORDER BY project_name ASC");
if ($p_res) {
    while ($r = $p_res->fetch_assoc()) {
        if (!empty($r['project_name']))
            $project_filter_opt[] = $r['project_name'];
    }
}

// ดึงข้อมูล Project Name ทั้งหมดสำหรับ Datalist (Autocomplete)
$project_options_all = $project_filter_opt; // ใช้ชุดเดียวกันไปก่อน

// ดึงข้อมูล Customers สำหรับ Render เบื้องต้น (แต่ JS จะโหลดทับอีกทีก็ได้)
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
    <title>MaintDash - Service</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/service_project.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Service</h2>
                <p class="page-subtitle">บันทึกข้อมูลการแจ้งซ่อมและประวัติการเข้าบริการ</p>
            </div>
            <div class="header-right-action">
                <a href="service_project_export.php" class="btn btn-success"><i class="fas fa-file-excel"></i> Excel</a>
                <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> เพิ่มข้อมูล</button>
            </div>
        </div>

        <div class="status-cards">
            <div class="status-card card-total active" onclick="filterByStatus('', this)">
                <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                <div class="card-info"><h4>ทั้งหมด</h4><div class="count" id="stat_total">0</div></div>
            </div>
            <div class="status-card card-onsite" onclick="filterByStatus(1, this)">
                <div class="card-icon"><i class="fas fa-building"></i></div>
                <div class="card-info"><h4>On-site</h4><div class="count" id="stat_onsite">0</div></div>
            </div>
            <div class="status-card card-remote" onclick="filterByStatus(2, this)">
                <div class="card-icon"><i class="fas fa-laptop-house"></i></div>
                <div class="card-info"><h4>Remote</h4><div class="count" id="stat_remote">0</div></div>
            </div>
            <div class="status-card card-sub" onclick="filterByStatus(3, this)">
                <div class="card-icon"><i class="fas fa-user-friends"></i></div>
                <div class="card-info"><h4>แจ้ง Sub</h4><div class="count" id="stat_sub">0</div></div>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่องาน, ลูกค้า, S/N..." onkeyup="filterTable()">
            </div>
            <select id="projectFilter" class="form-select" style="width: 250px;" onchange="filterTable()">
                <option value="">-- ดูทุกโครงการ --</option>
            </select>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>เลขที่โครงการ</th>
                            <th>วันที่</th>
                            <th>โครงการ</th>
                            <th>ลูกค้า</th>
                            <th>อุปกรณ์</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="serviceModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 850px;">
            <div class="modal-header">
                <h3 id="modalTitle" class="modal-title">บันทึกงานบริการ</h3>
                <button class="btn-close" onclick="closeModal()"></button>
            </div>
            <form id="serviceForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="detail_id" name="detail_id" value="0">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="form-label">ชื่อโครงการ (Project) <span class="text-danger">*</span></label>
                                <input type="text" id="project_name" name="project_name" class="form-control" list="project_options" required>
                                <datalist id="project_options"></datalist>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">เลขที่อ้างอิง</label>
                                <input type="text" id="number" name="number" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ลูกค้า <span class="text-danger">*</span></label>
                        <select id="customers_id" name="customers_id" class="form-control" required></select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">อุปกรณ์</label>
                                <input type="text" id="equipment" name="equipment" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">S/N</label>
                                <input type="text" id="sn" name="sn" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">อาการเสีย / สิ่งที่พบ</label>
                        <textarea id="symptom" name="symptom" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">การแก้ไข / ดำเนินการ</label>
                        <textarea id="action_taken" name="action_taken" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">สถานะ</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="1">On-site</option>
                                    <option value="2">Remote</option>
                                    <option value="3">แจ้ง Subcontractor</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">วันที่เริ่ม</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">วันที่สิ้นสุด</label>
                                <input type="date" id="end_date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">แนบไฟล์</label>
                        <input type="file" id="service_file" name="service_file" class="form-control">
                        <div id="filePreview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 900px;">
            <div class="modal-header">
                <h3 class="modal-title" id="view_project_name_header"></h3>
                <button class="btn-close" onclick="closeViewModal()"></button>
            </div>
            <div class="modal-body" id="viewModalBody"></div>
        </div>
    </div>

    <script src="js/service_project.js"></script>
</body>
</html>