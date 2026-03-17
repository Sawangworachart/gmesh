<?php
// หน้า Preventive Maintenance ของ admin
session_start();
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

@ini_set('upload_max_filesize', '128M');
@ini_set('post_max_size', '128M');
@ini_set('max_execution_time', '300');

// API Logic
if (isset($_GET['api']) && $_GET['api'] == 'true') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($action == 'fetch_all') {
        $sql = "SELECT p.*, c.customers_name FROM pm_project p LEFT JOIN customers c ON p.customers_id = c.customers_id ORDER BY p.pmproject_id DESC";
        $result = mysqli_query($conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action == 'fetch_single') {
        $id = intval($_GET['id']);
        $projectResult = mysqli_query($conn, "SELECT p.*, c.customers_name FROM pm_project p LEFT JOIN customers c ON p.customers_id = c.customers_id WHERE p.pmproject_id = $id");
        $project = mysqli_fetch_assoc($projectResult);
        $maResult = mysqli_query($conn, "SELECT * FROM ma_schedule WHERE pmproject_id = $id ORDER BY ma_date ASC");
        $maSchedule = [];
        while ($row = mysqli_fetch_assoc($maResult))
            $maSchedule[] = $row;
        echo json_encode(['success' => true, 'data' => $project, 'ma' => $maSchedule]);
        exit;
    }

    if ($action == 'save') {
        $id = intval($_POST['pmproject_id']);
        $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
        $customers_id = intval($_POST['customers_id']);
        $responsible_person = mysqli_real_escape_string($conn, $_POST['responsible_person']);

        // รับค่าสถานะ
        $status_post = $_POST['status'] ?? 'รอการตรวจสอบ';
        $status_map = ['รอการตรวจสอบ' => 1, 'กำลังดำเนินการ' => 2, 'ดำเนินการเสร็จสิ้น' => 3];
        $status = $status_map[$status_post] ?? 1;

        $number = mysqli_real_escape_string($conn, $_POST['number']);
        $contract_period = isset($_POST['contract_period']) ? mysqli_real_escape_string($conn, $_POST['contract_period']) : '-';
        $going_ma = mysqli_real_escape_string($conn, $_POST['going_ma']);

        // จัดการวันที่
        $deliver_work_date = (!empty($_POST['deliver_work_date'])) ? "'" . $_POST['deliver_work_date'] . "'" : "NULL";
        $end_date = (!empty($_POST['end_date'])) ? "'" . $_POST['end_date'] . "'" : "NULL";

        // --- แก้ไขจุดที่ 1: รับค่า status_remark ---
        $remark_val = isset($_POST['status_remark']) ? mysqli_real_escape_string($conn, $_POST['status_remark']) : '';
        $status_remark = empty($remark_val) ? "NULL" : "'$remark_val'";

        if ($id == 0) {
            // เพิ่มข้อมูลใหม่
            $sql = "INSERT INTO pm_project 
                (project_name, customers_id, responsible_person, status, number, contract_period, going_ma, deliver_work_date, end_date, status_remark) 
                VALUES 
                ('$project_name', $customers_id, '$responsible_person', $status, '$number', '$contract_period', '$going_ma', $deliver_work_date, $end_date, $status_remark)";

            if (!mysqli_query($conn, $sql)) {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
            // --- แก้ไขจุดที่ 2: ดึง ID ที่เพิ่ง INSERT สำเร็จมาใช้บันทึก MA ---
            $id = mysqli_insert_id($conn);
        } else {
            // แก้ไขข้อมูลเดิม
            $sql = "UPDATE pm_project SET 
                project_name='$project_name', 
                customers_id=$customers_id, 
                responsible_person='$responsible_person', 
                status=$status, 
                number='$number', 
                contract_period='$contract_period', 
                going_ma='$going_ma', 
                deliver_work_date=$deliver_work_date, 
                end_date=$end_date,
                status_remark=$status_remark 
                WHERE pmproject_id=$id";

            if (!mysqli_query($conn, $sql)) {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
        }

        // บันทึก MA (ตอนนี้ $id จะเป็นค่าที่ถูกต้องเสมอ ทั้งเคสเพิ่มและแก้ไข)
        if (isset($_POST['ma_dates']) && is_array($_POST['ma_dates'])) {
            processMAData($conn, $id);
        }

        echo json_encode(['success' => true]);
        exit;
    }
    if ($action == 'save_ma_only') {
        $id = intval($_POST['pmproject_id']);

        if ($id > 0) {
            // เรียกใช้ฟังก์ชันจัดการข้อมูล MA ที่มีอยู่ในไฟล์นี้แล้ว
            processMAData($conn, $id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบ ID โครงการ']);
        }
        exit;
    }
    // --- เพิ่มส่วนนี้เพื่อรองรับการบันทึกหมายเหตุการตรวจสอบ (Check Remark) ---
    if ($action == 'save_check_remark') {
        $id = intval($_POST['id']);
        $remark = mysqli_real_escape_string($conn, $_POST['remark']);

        if ($id > 0) {
            $sql = "UPDATE pm_project SET status_remark = '$remark' WHERE pmproject_id = $id";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสโครงการ']);
        }
        exit;
    }
}


function processMAData($conn, $id)
{
    $maUploadDir = 'uploads/ma/';
    if (!is_dir($maUploadDir)) {
        mkdir($maUploadDir, 0777, true);
    }

    if (isset($_POST['ma_dates']) && is_array($_POST['ma_dates'])) {
        $ma_dates = $_POST['ma_dates'];
        $ma_notes = $_POST['ma_notes'] ?? [];
        $ma_remarks = $_POST['ma_remarks'] ?? [];
        $ma_existing_files = $_POST['ma_existing_files'] ?? [];
        $ma_files = $_FILES['ma_files'] ?? [];

        mysqli_query($conn, "DELETE FROM ma_schedule WHERE pmproject_id = $id");

        foreach ($ma_dates as $index => $date) {
            if (!empty($date)) {
                $note = mysqli_real_escape_string($conn, $ma_notes[$index] ?? '');
                $remark = mysqli_real_escape_string($conn, $ma_remarks[$index] ?? '');
                $finalFilePath = '';

                if (isset($ma_files['name'][$index]) && $ma_files['error'][$index] == UPLOAD_ERR_OK) {
                    $tmpName = $ma_files['tmp_name'][$index];
                    $fileName = $ma_files['name'][$index];
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $newMaFileName = 'ma_' . $id . '_' . $index . '_' . time() . '.' . $ext;
                    $targetMaPath = $maUploadDir . $newMaFileName;
                    if (move_uploaded_file($tmpName, $targetMaPath)) {
                        $finalFilePath = $targetMaPath;
                        if (!empty($ma_existing_files[$index]) && file_exists($ma_existing_files[$index])) {
                            @unlink($ma_existing_files[$index]);
                        }
                    }
                } else {
                    $finalFilePath = $ma_existing_files[$index] ?? '';
                }

                $finalFilePath = mysqli_real_escape_string($conn, $finalFilePath);
                // ตรวจสอบว่ามีการกรอกหมายเหตุหรือไม่ ถ้ามีให้ is_done = 1
                $is_done = (!empty($remark)) ? 1 : 0;

                mysqli_query($conn, "INSERT INTO ma_schedule (pmproject_id, ma_date, note, remark, file_path, is_done) 
                    VALUES ($id, '$date', '$note', '$remark', '$finalFilePath', $is_done)");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>MaintDash - PM</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/pm_project.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Preventive Maintenance</h2>
                <p class="page-subtitle">บริหารจัดการโครงการและแผนการบำรุงรักษา (PM/MA)</p>
            </div>
            <div class="header-right-action">
                <button class="btn btn-success" onclick="exportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn btn-primary" onclick="openModal(0)"><i class="fas fa-plus"></i> เพิ่มข้อมูล</button>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card total"><div class="stat-icon"><i class="fas fa-layer-group"></i></div><div class="stat-info"><label>ทั้งหมด</label><span id="stat_total">0</span></div></div>
            <div class="stat-card pending"><div class="stat-icon"><i class="fas fa-clipboard-check"></i></div><div class="stat-info"><label>รอตรวจสอบ</label><span id="stat_pending">0</span></div></div>
            <div class="stat-card processing"><div class="stat-icon"><i class="fas fa-spinner"></i></div><div class="stat-info"><label>กำลังดำเนินการ</label><span id="stat_processing">0</span></div></div>
            <div class="stat-card completed"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-info"><label>เสร็จสิ้น</label><span id="stat_completed">0</span></div></div>
        </div>

        <div class="table-toolbar">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อโครงการ, ลูกค้า...">
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
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
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Project Modal -->
    <div id="pmProjectModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="modalTitle" class="modal-title">เพิ่มข้อมูลโครงการ</h3>
                <button class="btn-close" onclick="closeModal()"></button>
            </div>
            <form id="pmProjectForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="pmproject_id" id="pmproject_id" value="0">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="form-label">ชื่อโครงการ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="project_name" id="project_name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">เลขที่โครงการ</label>
                                <input type="text" class="form-control" name="number" id="number">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ลูกค้า <span class="text-danger">*</span></label>
                        <select name="customers_id" id="customers_id" class="form-control"></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ผู้รับผิดชอบ</label>
                        <input type="text" class="form-control" name="responsible_person" id="responsible_person">
                    </div>
                    <div class="form-group">
                        <label class="form-label">รายละเอียด / ขอบเขตงาน</label>
                        <textarea name="going_ma" id="going_ma" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">สถานะ</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="1">รอการตรวจสอบ</option>
                                    <option value="2">กำลังดำเนินการ</option>
                                    <option value="3">ดำเนินการเสร็จสิ้น</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">หมายเหตุสถานะ</label>
                                <input type="text" name="status_remark" id="status_remark" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">ระยะเวลาสัญญา</label>
                                <input type="text" class="form-control" name="contract_period" id="contract_period">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">วันเริ่มรับประกัน</label>
                                <input type="text" name="deliver_work_date" id="deliver_work_date" class="form-control flatpickr-input">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">วันสิ้นสุดรับประกัน</label>
                                <input type="text" name="end_date" id="end_date" class="form-control flatpickr-input">
                            </div>
                        </div>
                    </div>
                    <div id="maSectionWrapper" class="mt-3">
                        <h5>สร้างแผน MA อัตโนมัติ</h5>
                        <div class="d-flex gap-2 mb-2">
                            <select id="calc_frequency" class="form-select" style="width: 200px;">
                                <option value="1">ทุก 1 เดือน</option>
                                <option value="2">ทุก 2 เดือน</option>
                                <option value="3">ทุก 3 เดือน</option>
                                <option value="6">ทุก 6 เดือน</option>
                                <option value="12">ทุก 1 ปี</option>
                            </select>
                            <button type="button" onclick="calculateMA()" class="btn btn-secondary">คำนวณ</button>
                        </div>
                        <div id="maScheduleContainer" class="ma-rows-grid"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage MA Modal -->
    <div id="maManageModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 900px;">
            <div class="modal-header">
                <h3 class="modal-title">จัดการแผนบำรุงรักษา (MA)</h3>
                <button class="btn-close" onclick="closeMAModal()"></button>
            </div>
            <form id="maManageForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="pmproject_id" id="ma_pmproject_id">
                    <div class="project-info-bar">
                        <h4 id="ma_project_title"></h4>
                        <span id="ma_project_status"></span>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mb-3">
                        <button type="button" onclick="addMARow()" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> เพิ่มแถว</button>
                    </div>
                    <div id="maManageContainer" class="ma-rows-grid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeMAModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกแผน MA</button>
                </div>
            </form>
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

    <script src="js/pm_project.js"></script>
    <script>
        function exportExcel() {
            window.open('pm_project_export.php', '_blank');
        }
    </script>
</body>
</html>