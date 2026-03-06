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
    <title>MaintDash</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/pm_project.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-circle"><i class="fas fa-project-diagram"></i></div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Preventive Maintenance</h2>
                    <p class="header-sub-desc">บริหารจัดการโครงการและแผนการบำรุงรักษา (PM/MA)</p>
                </div>
            </div>
            <div class="header-right-action" style="display:flex; gap:10px;">
                <button class="btn-pill-primary" style="background:#10b981;" onclick="exportExcel()">
                    <i class="fas fa-file-excel"></i> Excel
                </button>

                <button class="btn-pill-primary" onclick="openModal(0)">
                    <i class="fas fa-plus"></i> เพิ่มข้อมูล
                </button>
            </div>


        </div>

        <div class="stats-container">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info"><label>ทั้งหมด</label><span id="stat_total">0</span></div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                <div class="stat-info"><label>รอการตรวจสอบ</label><span id="stat_pending">0</span></div>
            </div>
            <div class="stat-card processing">
                <div class="stat-icon"><i class="fas fa-spinner fa-spin-hover"></i></div>
                <div class="stat-info"><label>กำลังดำเนินการ</label><span id="stat_processing">0</span></div>
            </div>
            <div class="stat-card completed">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info"><label>ดำเนินการเสร็จสิ้น</label><span id="stat_completed">0</span></div>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-container-custom">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อโครงการ, ลูกค้า...">
            </div>
        </div>

        <div class="card-table">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th class="col-id">เลขที่โครงการ</th>
                        <th class="col-name">ชื่อโครงการ</th>
                        <th class="col-customer">ลูกค้า</th>
                        <th class="col-status">สถานะ</th>
                        <th class="col-contract">สัญญา</th>
                        <th class="col-contract" style="width: 150px;">เริ่มประกัน / สิ้นสุดประกัน</th>
                        <th class="col-action text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>

        <div id="pmProjectModal" class="modal-overlay">
            <div class="modal-box custom-modal-style">
                <div class="modal-header-custom">
                    <div class="header-left">
                        <div class="header-icon-box"><i class="fas fa-pen"></i></div>
                        <div class="header-titles">
                            <h3 id="modalTitle">เพิ่มข้อมูลโครงการใหม่</h3>
                            <p class="header-subtitle">จัดการรายละเอียดโครงการและสัญญา</p>
                        </div>
                    </div>
                    <button class="close-btn-custom" onclick="closeModal()"><i class="fas fa-times"></i></button>
                </div>

                <form id="pmProjectForm" enctype="multipart/form-data">
                    <div class="modal-body custom-scroll">
                        <input type="hidden" name="pmproject_id" id="pmproject_id" value="0">

                        <div class="section-header">
                            <div class="section-indicator"></div>
                            <i class="fas fa-building section-icon"></i><span>ข้อมูลโครงการและหน่วยงาน</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group form-full">
                                <label class="form-label">ชื่อโครงการ (Project Name) <span
                                        style="color:red">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-briefcase input-icon"></i>
                                    <input type="text" class="form-control-custom" name="project_name" id="project_name"
                                        required placeholder="ระบุชื่อโครงการ">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">เลขที่โครงการ (ID)</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-barcode input-icon"></i>
                                    <input type="text" class="form-control-custom" name="number" id="number"
                                        placeholder="เช่น PJ66xxxxx" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">สถานะ (Status)</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-flag input-icon"></i>
                                    <select name="status" id="status" class="form-control-custom status-select">
                                        <option value="รอการตรวจสอบ">รอการตรวจสอบ</option>
                                        <option value="กำลังดำเนินการ">กำลังดำเนินการ</option>
                                        <option value="ดำเนินการเสร็จสิ้น">ดำเนินการเสร็จสิ้น</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group form-full">
                                <label class="form-label">ลูกค้า (Customer) <span style="color:red">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-user-tie input-icon"></i>
                                    <select name="customers_id" id="customers_id" class="form-control-custom">
                                        <option value="">-- เลือกลูกค้า --</option>
                                        <?php
                                        $cSql = mysqli_query($conn, "SELECT * FROM customers ORDER BY customers_name ASC");
                                        while ($c = mysqli_fetch_assoc($cSql)) {
                                            $displayName = $c['customers_name'] . " (" . $c['contact_name'] . ")";
                                            echo "<option value='{$c['customers_id']}'>{$displayName}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group form-full">
                                <label class="form-label">ผู้รับผิดชอบ (Responsible Person)</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-user-circle input-icon"></i>
                                    <input type="text" class="form-control-custom" name="responsible_person"
                                        id="responsible_person" placeholder="ชื่อวิศวกร/หัวหน้าโครงการ">
                                </div>
                            </div>
                            <div class="form-group form-full">
                                <label class="form-label">รายละเอียด / ขอบเขตงาน</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-align-left input-icon" style="top: 20px;"></i>
                                    <textarea name="going_ma" id="going_ma" rows="2" class="form-control-custom"
                                        style="height:auto;" placeholder="รายละเอียด..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="section-header mt-4">
                            <div class="section-indicator"></div>
                            <i class="fas fa-file-contract section-icon"></i><span>ระยะเวลาและสัญญา</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">ระยะเวลาสัญญา</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-clock input-icon"></i>
                                    <input type="text" class="form-control-custom" name="contract_period"
                                        id="contract_period" placeholder="เช่น 1 ปี">
                                </div>
                            </div>
                            <div class="form-group"></div>

                            <div class="form-group-custom mt-3" id="statusRemarkWrapper"
                                style="display:none; width: 100%;">
                                <label class="form-label" style="color:#b45309; font-weight:600;">
                                    <i class="fas fa-exclamation-circle"></i> หมายเหตุรอการตรวจสอบ
                                    (เฉพาะสถานะรอการตรวจสอบ)
                                </label>
                                <textarea name="status_remark" id="status_remark" class="form-control-custom" rows="3"
                                    placeholder="ระบุสิ่งที่ต้องแก้ไข / หมายเหตุสำหรับการตรวจสอบ"></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">วันเริ่มรับประกัน</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-calendar-check input-icon"></i>
                                    <input type="text" name="deliver_work_date" id="deliver_work_date"
                                        class="form-control-custom">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">วันสิ้นสุดรับประกัน</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-calendar-times input-icon"></i>
                                    <input type="text" name="end_date" id="end_date"
                                        class="form-control-modern form-control-custom">
                                </div>
                            </div>
                        </div>

                        <div id="maSectionWrapper" class="mt-4">
                            <div class="section-header">
                                <div class="section-indicator"></div>
                                <i class="fas fa-magic section-icon"></i><span>สร้างแผน MA อัตโนมัติ</span>
                            </div>
                            <div style="background:#f9fafb; padding:15px; border-radius:10px; border:1px solid #eee;">
                                <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
                                    <select id="calc_frequency" class="form-control-custom" style="width:200px;">
                                        <option value="1">ทุก 1 เดือน</option>
                                        <option value="2">ทุก 2 เดือน</option>
                                        <option value="3">ทุก 3 เดือน</option>
                                        <option value="4">ทุก 4 เดือน</option>
                                        <option value="5">ทุก 5 เดือน</option>
                                        <option value="6">ทุก 6 เดือน</option>
                                        <option value="7">ทุก 7 เดือน</option>
                                        <option value="8">ทุก 8 เดือน</option>
                                        <option value="9">ทุก 9 เดือน</option>
                                        <option value="10">ทุก 10 เดือน</option>
                                        <option value="11">ทุก 11 เดือน</option>
                                        <option value="12">ทุก 1 ปี (12 เดือน)</option>
                                    </select>
                                    <button type="button"
                                        onclick="calculateMA('#maScheduleContainer', '#deliver_work_date', '#end_date', '#calc_frequency')"
                                        class="btn-pill-primary" style="font-size:0.8rem; padding:8px 16px;">
                                        <i class="fas fa-calculator"></i> คำนวณ
                                    </button>
                                </div>
                                <div id="maScheduleContainer" class="ma-rows-grid"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer-custom">
                        <button type="submit" class="btn-save-custom"><i class="fas fa-check-circle"></i>
                            บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>

        <?php include 'pm_project_extra_modals.php'; ?>

        <div id="maManageModal" class="modal-overlay">
            <div class="modal-box custom-modal-style">
                <div class="modal-header-custom">
                    <div class="header-left">
                        <div class="header-icon-box icon-bg-green">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="header-titles">
                            <h3>จัดการแผนบำรุงรักษา (MA)</h3>
                            <p class="header-subtitle">กำหนดงวดงานและบันทึกผลการดำเนินงาน</p>
                        </div>
                    </div>
                    <button class="close-btn-custom" onclick="closeMAModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="maManageForm" enctype="multipart/form-data">
                    <input type="hidden" name="pmproject_id" id="ma_pmproject_id">
                    <div class="modal-body custom-scroll">
                        <div class="project-info-bar"
                            style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                            <div style="flex: 1; min-width: 0;">
                                <h4 id="ma_project_title"
                                    style="margin: 0 0 5px 0; font-size: 1.1rem; font-weight: 600; color: #166534; line-height: 1.4; word-break: break-word;">
                                    กำลังโหลด...</h4>
                                <div id="ma_project_dates" style="font-size: 0.9rem; color: #16a34a;">-</div>
                            </div>
                            <div style="flex-shrink: 0;"> <span class="status-pill status-กำลังดำเนินการ"
                                    id="ma_project_status" style="margin: 0;">Active</span>
                            </div>
                        </div>
                        <div class="ma-tools-bar">
                            <input type="hidden" id="ma_ref_start_date">
                            <input type="hidden" id="ma_ref_end_date">
                            <span style="font-size:0.85rem; color:#666;">สร้างแผนใหม่: </span>
                            <select id="ma_calc_frequency" class="form-control-custom"
                                style="width:140px; padding:6px 10px; height:auto; padding-left:15px;">
                                <option value="1">ทุก 1 เดือน</option>
                                <option value="2">ทุก 2 เดือน</option>
                                <option value="3">ทุก 3 เดือน</option>
                                <option value="4">ทุก 4 เดือน</option>
                                <option value="5">ทุก 5 เดือน</option>
                                <option value="6">ทุก 6 เดือน</option>
                                <option value="7">ทุก 7 เดือน</option>
                                <option value="8">ทุก 8 เดือน</option>
                                <option value="9">ทุก 9 เดือน</option>
                                <option value="10">ทุก 10 เดือน</option>
                                <option value="11">ทุก 11 เดือน</option>
                                <option value="12">ทุก 1 ปี (12 เดือน)</option>
                            </select>
                            <button type="button"
                                onclick="calculateMA('#maManageContainer', '#ma_ref_start_date', '#ma_ref_end_date', '#ma_calc_frequency')"
                                class="btn-calc-sm">
                                <i class="fas fa-calculator"></i> คำนวณใหม่
                            </button>
                            <div style="width:1px; height:20px; background:#ddd; margin:0 10px;"></div>
                            <button type="button" onclick="addMARow('#maManageContainer')" class="btn-add-row-sm">
                                <i class="fas fa-plus"></i> เพิ่มแถวเอง
                            </button>
                        </div>

                        <div id="maManageContainer" style="padding-bottom: 20px;">
                        </div>
                    </div>

                    <div class="modal-footer-custom">
                        <button type="submit" class="btn-save-custom" style="background:#10b981;">
                            <i class="fas fa-save"></i> บันทึกแผน MA
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="viewProjectModal" class="modal-overlay">
            <div class="modal-box view-project-custom"
                style="max-width: 900px; width: 95%; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                <div class="modal-header-custom"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); color: white; padding: 25px; border: none;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div
                            style="background: rgba(255,255,255,0.25); padding: 12px; border-radius: 12px; font-size: 1.6rem; backdrop-filter: blur(4px);">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div>
                            <h3 id="view_project_name"
                                style="margin:0; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.025em;">
                                ชื่อโครงการ</h3>
                            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 0.95rem; font-weight: 400;">
                                <i class="fas fa-hashtag"></i> <span id="view_number" style="font-weight: 600;">-</span>
                                | ข้อมูลสรุปและสถานะปัจจุบัน
                            </p>
                        </div>
                    </div>
                    <button onclick="closeViewModal()"
                        style="background: #ff4d4d; border: none; color: white; cursor: pointer; font-size: 1rem; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 20px; right: 20px; transition: 0.3s; box-shadow: 0 2px 10px rgba(240, 67, 67, 0.97);"
                        onmouseover="this.style.background='#c74343'" onmouseout="this.style.background='#ff4d4d'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body custom-scroll" style="padding: 30px; background: #ffffff;">
                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div
                            style="background:#f8faff; padding: 18px; border-radius: 14px; border: 1px solid #e0e8ff; border-left: 5px solid #3b82f6;">
                            <label
                                style="display:block; font-size: 0.75rem; color: #64748b; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">สถานะโครงการ</label>
                            <div id="view_status_container"></div>
                        </div>
                        <div
                            style="background:#f8fffb; padding: 18px; border-radius: 14px; border: 1px solid #e0f5e9; border-left: 5px solid #10b981;">
                            <label
                                style="display:block; font-size: 0.75rem; color: #64748b; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">ระยะเวลาสัญญา</label>
                            <span id="view_contract_period_display"
                                style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">-</span>
                        </div>
                        <div
                            style="background:#f9f8ff; padding: 18px; border-radius: 14px; border: 1px solid #eeeaff; border-left: 5px solid #6366f1;">
                            <label
                                style="display:block; font-size: 0.75rem; color: #64748b; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">ผู้รับผิดชอบ</label>
                            <span id="view_responsible"
                                style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">-</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.8fr 1fr; gap: 25px;">
                        <div style="display: flex; flex-direction: column; gap: 25px;">
                            <div
                                style="background:white; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                                <h4
                                    style="margin-top:0; border-bottom: 2px solid #f8faff; padding-bottom: 15px; color: #334155; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-info-circle" style="color:#3b82f6;"></i> รายละเอียด / ขอบเขตงาน
                                </h4>
                                <div id="view_going_ma"
                                    style="line-height: 1.7; color: #475569; font-size: 0.95rem; padding-top: 10px;">-
                                </div>
                            </div>

                            <div
                                style="background:white; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                                <h4
                                    style="margin-top:0; border-bottom: 2px solid #f8faff; padding-bottom: 15px; color: #334155; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-history" style="color:#3b82f6;"></i> แผนบำรุงรักษา (MA Schedule)
                                </h4>
                                <div class="view-table-wrapper" style="overflow-x: auto; margin-top: 10px;">
                                    <table class="view-table" id="view_ma_table"
                                        style="width:100%; border-collapse: separate; border-spacing: 0 8px;">
                                        <thead>
                                            <tr
                                                style="text-align: left; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase;">
                                                <th
                                                    style="padding: 12px; border-bottom: 1px solid #f1f5f9; text-align: center;">
                                                    ครั้งที่</th>
                                                <th style="padding: 12px; border-bottom: 1px solid #f1f5f9;">วันที่กำหนด
                                                </th>
                                                <th style="padding: 12px; border-bottom: 1px solid #f1f5f9;">
                                                    ผลการดำเนินงาน</th>
                                                <th style="padding: 12px; border-bottom: 1px solid #f1f5f9;">หมายเหตุ
                                                </th>
                                                <th
                                                    style="padding: 12px; border-bottom: 1px solid #f1f5f9; text-align: center;">
                                                    ไฟล์</th>
                                            </tr>
                                        </thead>
                                        <tbody id="view_ma_table_body" style="font-size: 0.9rem;"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div
                                style="background: linear-gradient(to right bottom, #fffbeb, #fffde0); padding: 25px; border-radius: 16px; border: 1px solid #fef3c7;">
                                <h4
                                    style="margin-top:0; color: #92400e; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                    <i class="fas fa-id-card"></i> ข้อมูลลูกค้า
                                </h4>
                                <p id="view_customer_name"
                                    style="margin: 10px 0 0 0; font-weight: 700; color: #1e293b; font-size: 1.1rem; line-height: 1.4;">
                                    -</p>
                            </div>

                            <div
                                style="background:white; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                                <div style="margin-bottom: 20px;">
                                    <label
                                        style="display:block; font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase;">วันเริ่มรับประกัน</label>
                                    <p id="view_deliver_date"
                                        style="margin: 5px 0; font-weight: 700; color: #10b981; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                                        <i class="far fa-calendar-check" style="font-size: 1rem;"></i> -
                                    </p>
                                </div>
                                <div>
                                    <label
                                        style="display:block; font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase;">วันสิ้นสุดรับประกัน</label>
                                    <p id="view_end_date"
                                        style="margin: 5px 0; font-weight: 700; color: #ef4444; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                                        <i class="far fa-calendar-times" style="font-size: 1rem;"></i> -
                                    </p>
                                </div>
                            </div>

                            <div id="view_remark_wrapper"
                                style="background:#fff1f1; padding: 20px; border-radius: 16px; border: 1px solid #fee2e2; display: none;">
                                <label
                                    style="display:block; font-size: 0.75rem; color: #b91c1c; font-weight: 700; text-transform: uppercase;"><i
                                        class="fas fa-exclamation-circle"></i> หมายเหตุรอการตรวจสอบ</label>
                                <p id="view_status_remark"
                                    style="margin: 8px 0 0 0; font-size: 0.9rem; color: #991b1b; line-height: 1.5;">-
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/pm_project.js?v=<?php echo time(); ?>"></script>
    <script>
        function exportExcel() {
            window.open('pm_project_export.php', '_blank');
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            flatpickr("#deliver_work_date", {
                dateFormat: "Y-m-d",   // format ที่ส่งเข้า PHP
                altInput: true,
                altFormat: "d/m/Y",    // format ที่แสดง
                allowInput: true
            });

            flatpickr("#end_date", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                allowInput: true
            });

        });
    </script>
</body>

</html>