<?php
// =========================================
// หน้า Preventive Maintenance (PM) สำหรับ Admin
// =========================================

session_start(); // เริ่มต้น Session
require_once 'db.php'; // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

// ตั้งค่า Header เพื่อป้องกันการแคช (Cache) ของเบราว์เซอร์
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// ตั้งค่า PHP ให้รองรับการอัปโหลดไฟล์ขนาดใหญ่
@ini_set('upload_max_filesize', '128M');
@ini_set('post_max_size', '128M');
@ini_set('max_execution_time', '300');

// --- ส่วนจัดการ API (สำหรับ AJAX Request) ---
if (isset($_GET['api']) && $_GET['api'] == 'true') {
    header('Content-Type: application/json'); // ตอบกลับเป็น JSON
    $action = $_POST['action'] ?? $_GET['action'] ?? ''; // รับค่า Action

    // 1. ดึงข้อมูลโครงการทั้งหมด
    if ($action == 'fetch_all') {
        // Query ข้อมูลโครงการพร้อมชื่อลูกค้า
        $sql = "SELECT p.*, c.customers_name FROM pm_project p LEFT JOIN customers c ON p.customers_id = c.customers_id ORDER BY p.pmproject_id DESC";
        $result = mysqli_query($conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row; // เก็บข้อมูลลง Array
            }
        }
        echo json_encode(['success' => true, 'data' => $data]); // ส่งกลับ JSON
        exit;
    }

    // 2. ดึงข้อมูลโครงการเดี่ยว (พร้อมแผน MA)
    if ($action == 'fetch_single') {
        $id = intval($_GET['id']);
        // ดึงข้อมูลโครงการ
        $projectResult = mysqli_query($conn, "SELECT p.*, c.customers_name FROM pm_project p LEFT JOIN customers c ON p.customers_id = c.customers_id WHERE p.pmproject_id = $id");
        $project = mysqli_fetch_assoc($projectResult);
        
        // ดึงแผน MA ที่เกี่ยวข้อง
        $maResult = mysqli_query($conn, "SELECT * FROM ma_schedule WHERE pmproject_id = $id ORDER BY ma_date ASC");
        $maSchedule = [];
        while ($row = mysqli_fetch_assoc($maResult))
            $maSchedule[] = $row;
            
        echo json_encode(['success' => true, 'data' => $project, 'ma' => $maSchedule]);
        exit;
    }

    // 3. บันทึกข้อมูลโครงการ (เพิ่ม/แก้ไข)
    if ($action == 'save') {
        $id = intval($_POST['pmproject_id']);
        $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
        $customers_id = intval($_POST['customers_id']);
        $responsible_person = mysqli_real_escape_string($conn, $_POST['responsible_person']);

        // แปลงสถานะข้อความเป็นตัวเลข
        $status_post = $_POST['status'] ?? 'รอการตรวจสอบ';
        $status_map = ['รอการตรวจสอบ' => 1, 'กำลังดำเนินการ' => 2, 'ดำเนินการเสร็จสิ้น' => 3];
        $status = $status_map[$status_post] ?? 1;

        $number = mysqli_real_escape_string($conn, $_POST['number']);
        $contract_period = isset($_POST['contract_period']) ? mysqli_real_escape_string($conn, $_POST['contract_period']) : '-';
        $going_ma = mysqli_real_escape_string($conn, $_POST['going_ma']);

        // จัดการวันที่ (ถ้าว่างให้เป็น NULL)
        $deliver_work_date = (!empty($_POST['deliver_work_date'])) ? "'" . $_POST['deliver_work_date'] . "'" : "NULL";
        $end_date = (!empty($_POST['end_date'])) ? "'" . $_POST['end_date'] . "'" : "NULL";

        // จัดการหมายเหตุสถานะ
        $remark_val = isset($_POST['status_remark']) ? mysqli_real_escape_string($conn, $_POST['status_remark']) : '';
        $status_remark = empty($remark_val) ? "NULL" : "'$remark_val'";

        if ($id == 0) {
            // กรณีเพิ่มใหม่ (INSERT)
            $sql = "INSERT INTO pm_project 
                (project_name, customers_id, responsible_person, status, number, contract_period, going_ma, deliver_work_date, end_date, status_remark) 
                VALUES 
                ('$project_name', $customers_id, '$responsible_person', $status, '$number', '$contract_period', '$going_ma', $deliver_work_date, $end_date, $status_remark)";

            if (!mysqli_query($conn, $sql)) {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
            $id = mysqli_insert_id($conn); // ดึง ID ล่าสุดที่เพิ่งเพิ่ม
        } else {
            // กรณีแก้ไข (UPDATE)
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

        // บันทึกแผน MA ถ้ามีการส่งมาด้วย
        if (isset($_POST['ma_dates']) && is_array($_POST['ma_dates'])) {
            processMAData($conn, $id);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // 4. บันทึกเฉพาะแผน MA
    if ($action == 'save_ma_only') {
        $id = intval($_POST['pmproject_id']);
        if ($id > 0) {
            processMAData($conn, $id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบ ID โครงการ']);
        }
        exit;
    }

    // 5. บันทึกหมายเหตุตรวจสอบ
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

// --- ฟังก์ชันช่วยบันทึกข้อมูล MA ---
function processMAData($conn, $id)
{
    $maUploadDir = 'uploads/ma/';
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($maUploadDir)) {
        mkdir($maUploadDir, 0777, true);
    }

    if (isset($_POST['ma_dates']) && is_array($_POST['ma_dates'])) {
        $ma_dates = $_POST['ma_dates'];
        $ma_notes = $_POST['ma_notes'] ?? [];
        $ma_remarks = $_POST['ma_remarks'] ?? [];
        $ma_existing_files = $_POST['ma_existing_files'] ?? [];
        $ma_files = $_FILES['ma_files'] ?? [];

        // ลบข้อมูลเก่าออกก่อน (เพื่อบันทึกใหม่ทั้งหมด)
        mysqli_query($conn, "DELETE FROM ma_schedule WHERE pmproject_id = $id");

        foreach ($ma_dates as $index => $date) {
            if (!empty($date)) {
                $note = mysqli_real_escape_string($conn, $ma_notes[$index] ?? '');
                $remark = mysqli_real_escape_string($conn, $ma_remarks[$index] ?? '');
                $finalFilePath = '';

                // จัดการอัปโหลดไฟล์
                if (isset($ma_files['name'][$index]) && $ma_files['error'][$index] == UPLOAD_ERR_OK) {
                    $tmpName = $ma_files['tmp_name'][$index];
                    $fileName = $ma_files['name'][$index];
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $newMaFileName = 'ma_' . $id . '_' . $index . '_' . time() . '.' . $ext;
                    $targetMaPath = $maUploadDir . $newMaFileName;
                    if (move_uploaded_file($tmpName, $targetMaPath)) {
                        $finalFilePath = $targetMaPath;
                        // ลบไฟล์เก่าถ้ามี
                        if (!empty($ma_existing_files[$index]) && file_exists($ma_existing_files[$index])) {
                            @unlink($ma_existing_files[$index]);
                        }
                    }
                } else {
                    $finalFilePath = $ma_existing_files[$index] ?? '';
                }

                $finalFilePath = mysqli_real_escape_string($conn, $finalFilePath);
                // ถ้ามีหมายเหตุผลการดำเนินงาน ถือว่าเสร็จสิ้น (is_done = 1)
                $is_done = (!empty($remark)) ? 1 : 0;

                // เพิ่มข้อมูลลงฐานข้อมูล
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
    <title>MaintDash - Preventive Maintenance</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Custom CSS (แยกไฟล์) -->
    <link rel="stylesheet" href="assets/css/pm_project.css?v=<?php echo time(); ?>">

    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header Banner -->
        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-circle"><i class="fas fa-project-diagram"></i></div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Preventive Maintenance</h2>
                    <p class="header-sub-desc">บริหารจัดการโครงการและแผนการบำรุงรักษา (PM/MA)</p>
                </div>
            </div>
            <div class="header-right-action">
                <button class="btn-pill-primary" style="background:#10b981;" onclick="exportExcel()">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button class="btn-pill-primary" onclick="openModal(0)">
                    <i class="fas fa-plus"></i> เพิ่มข้อมูล
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card total" data-status="all">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info"><label>ทั้งหมด</label><span id="stat_total">0</span></div>
            </div>
            <div class="stat-card pending" data-status="รอการตรวจสอบ">
                <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                <div class="stat-info"><label>รอการตรวจสอบ</label><span id="stat_pending">0</span></div>
            </div>
            <div class="stat-card processing" data-status="กำลังดำเนินการ">
                <div class="stat-icon"><i class="fas fa-spinner fa-spin-hover"></i></div>
                <div class="stat-info"><label>กำลังดำเนินการ</label><span id="stat_processing">0</span></div>
            </div>
            <div class="stat-card completed" data-status="ดำเนินการเสร็จสิ้น">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info"><label>ดำเนินการเสร็จสิ้น</label><span id="stat_completed">0</span></div>
            </div>
        </div>

        <!-- Search Toolbar -->
        <div class="table-toolbar">
            <div class="search-container-custom">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อโครงการ, ลูกค้า...">
            </div>
        </div>

        <!-- Table -->
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

        <!-- Modal: เพิ่ม/แก้ไขโครงการ -->
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

                        <!-- Section 1: ข้อมูลโครงการ -->
                        <div class="section-header">
                            <div class="section-indicator"></div>
                            <i class="fas fa-building section-icon"></i><span>ข้อมูลโครงการและหน่วยงาน</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group form-full">
                                <label class="form-label">ชื่อโครงการ (Project Name) <span style="color:red">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-briefcase input-icon"></i>
                                    <input type="text" class="form-control-custom" name="project_name" id="project_name" required placeholder="ระบุชื่อโครงการ">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">เลขที่โครงการ (ID)</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-barcode input-icon"></i>
                                    <input type="text" class="form-control-custom" name="number" id="number" placeholder="เช่น PJ66xxxxx" required>
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
                                    <input type="text" class="form-control-custom" name="responsible_person" id="responsible_person" placeholder="ชื่อวิศวกร/หัวหน้าโครงการ">
                                </div>
                            </div>
                            <div class="form-group form-full">
                                <label class="form-label">รายละเอียด / ขอบเขตงาน</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-align-left input-icon" style="top: 20px;"></i>
                                    <textarea name="going_ma" id="going_ma" rows="2" class="form-control-custom" style="height:auto;" placeholder="รายละเอียด..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: ระยะเวลาและสัญญา -->
                        <div class="section-header mt-4">
                            <div class="section-indicator"></div>
                            <i class="fas fa-file-contract section-icon"></i><span>ระยะเวลาและสัญญา</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">ระยะเวลาสัญญา</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-clock input-icon"></i>
                                    <input type="text" class="form-control-custom" name="contract_period" id="contract_period" placeholder="เช่น 1 ปี">
                                </div>
                            </div>
                            <div class="form-group"></div>

                            <div class="form-group-custom mt-3" id="statusRemarkWrapper" style="display:none; width: 100%;">
                                <label class="form-label" style="color:#b45309; font-weight:600;">
                                    <i class="fas fa-exclamation-circle"></i> หมายเหตุรอการตรวจสอบ (เฉพาะสถานะรอการตรวจสอบ)
                                </label>
                                <textarea name="status_remark" id="status_remark" class="form-control-custom" rows="3" placeholder="ระบุสิ่งที่ต้องแก้ไข / หมายเหตุสำหรับการตรวจสอบ"></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">วันเริ่มรับประกัน</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-calendar-check input-icon"></i>
                                    <input type="text" name="deliver_work_date" id="deliver_work_date" class="form-control-custom">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">วันสิ้นสุดรับประกัน</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-calendar-times input-icon"></i>
                                    <input type="text" name="end_date" id="end_date" class="form-control-custom">
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: แผน MA อัตโนมัติ -->
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
                                        <option value="6">ทุก 6 เดือน</option>
                                        <option value="12">ทุก 1 ปี (12 เดือน)</option>
                                    </select>
                                    <button type="button" onclick="calculateMA('#maScheduleContainer', '#deliver_work_date', '#end_date', '#calc_frequency')" class="btn-pill-primary" style="font-size:0.8rem; padding:8px 16px;">
                                        <i class="fas fa-calculator"></i> คำนวณ
                                    </button>
                                </div>
                                <div id="maScheduleContainer" class="ma-rows-grid"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer-custom">
                        <button type="submit" class="btn-save-custom"><i class="fas fa-check-circle"></i> บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>

        <?php include 'pm_project_extra_modals.php'; ?>

        <!-- Modal: จัดการ MA -->
        <div id="maManageModal" class="modal-overlay">
            <div class="modal-box custom-modal-style">
                <div class="modal-header-custom">
                    <div class="header-left">
                        <div class="header-icon-box icon-bg-green"><i class="fas fa-edit"></i></div>
                        <div class="header-titles">
                            <h3>จัดการแผนบำรุงรักษา (MA)</h3>
                            <p class="header-subtitle">กำหนดงวดงานและบันทึกผลการดำเนินงาน</p>
                        </div>
                    </div>
                    <button class="close-btn-custom" onclick="closeMAModal()"><i class="fas fa-times"></i></button>
                </div>

                <form id="maManageForm" enctype="multipart/form-data">
                    <input type="hidden" name="pmproject_id" id="ma_pmproject_id">
                    <div class="modal-body custom-scroll">
                        <div class="project-info-bar">
                            <div>
                                <h4 id="ma_project_title">กำลังโหลด...</h4>
                                <div id="ma_project_dates">-</div>
                            </div>
                            <div><span id="ma_project_status">Active</span></div>
                        </div>
                        <div class="ma-tools-bar">
                            <input type="hidden" id="ma_ref_start_date">
                            <input type="hidden" id="ma_ref_end_date">
                            <span>สร้างแผนใหม่: </span>
                            <select id="ma_calc_frequency" class="form-control-custom" style="width:140px; padding:6px 10px; height:auto;">
                                <option value="1">ทุก 1 เดือน</option>
                                <option value="3">ทุก 3 เดือน</option>
                                <option value="6">ทุก 6 เดือน</option>
                                <option value="12">ทุก 1 ปี</option>
                            </select>
                            <button type="button" onclick="calculateMA('#maManageContainer', '#ma_ref_start_date', '#ma_ref_end_date', '#ma_calc_frequency')" class="btn-calc-sm">
                                <i class="fas fa-calculator"></i> คำนวณใหม่
                            </button>
                            <button type="button" onclick="addMARow('#maManageContainer')" class="btn-add-row-sm">
                                <i class="fas fa-plus"></i> เพิ่มแถวเอง
                            </button>
                        </div>
                        <div id="maManageContainer" style="padding-bottom: 20px;"></div>
                    </div>

                    <div class="modal-footer-custom">
                        <button type="submit" class="btn-save-custom" style="background:#10b981;">
                            <i class="fas fa-save"></i> บันทึกแผน MA
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: ดูรายละเอียด (View) -->
        <div id="viewProjectModal" class="modal-overlay">
            <div class="modal-box custom-modal-style" style="max-width: 900px;">
                <div class="modal-header-custom" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); color: white;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: rgba(255,255,255,0.25); padding: 12px; border-radius: 12px; backdrop-filter: blur(4px);">
                            <i class="fas fa-eye" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h3 id="view_project_name" style="margin:0; font-size: 1.5rem;">ชื่อโครงการ</h3>
                            <p style="margin: 4px 0 0 0; opacity: 0.9;"><span id="view_number">-</span> | ข้อมูลสรุป</p>
                        </div>
                    </div>
                    <button class="close-btn-custom" onclick="closeViewModal()" style="background:rgba(255,255,255,0.2); color:white;"><i class="fas fa-times"></i></button>
                </div>

                <div class="modal-body custom-scroll">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div class="view-item-box" style="background:#f8faff; padding:15px; border-radius:10px; border-left:4px solid #3b82f6;">
                            <label style="font-size:0.8rem; color:#64748b;">สถานะ</label>
                            <div id="view_status_container"></div>
                        </div>
                        <div class="view-item-box" style="background:#f8fffb; padding:15px; border-radius:10px; border-left:4px solid #10b981;">
                            <label style="font-size:0.8rem; color:#64748b;">สัญญา</label>
                            <div id="view_contract_period_display" style="font-weight:bold;">-</div>
                        </div>
                        <div class="view-item-box" style="background:#f9f8ff; padding:15px; border-radius:10px; border-left:4px solid #6366f1;">
                            <label style="font-size:0.8rem; color:#64748b;">ผู้รับผิดชอบ</label>
                            <div id="view_responsible" style="font-weight:bold;">-</div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
                        <div>
                            <h4 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">รายละเอียด / ขอบเขตงาน</h4>
                            <div id="view_going_ma" style="color: #475569; line-height: 1.6;">-</div>

                            <h4 style="margin-top:30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">แผนบำรุงรักษา (MA Schedule)</h4>
                            <div class="view-table-wrapper">
                                <table class="view-table" id="view_ma_table">
                                    <thead>
                                        <tr>
                                            <th class="text-center">ครั้งที่</th>
                                            <th>วันที่</th>
                                            <th>สถานะ</th>
                                            <th>หมายเหตุ</th>
                                            <th class="text-center">ไฟล์</th>
                                        </tr>
                                    </thead>
                                    <tbody id="view_ma_table_body"></tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <div style="background:#fffbeb; padding:20px; border-radius:12px; border:1px solid #fef3c7; margin-bottom:20px;">
                                <label style="font-size:0.8rem; color:#92400e; font-weight:bold;">ข้อมูลลูกค้า</label>
                                <div id="view_customer_name" style="font-size:1.1rem; font-weight:bold; margin-top:5px;">-</div>
                            </div>
                            
                            <div style="background:#fff; padding:20px; border-radius:12px; border:1px solid #f0f0f0; box-shadow:0 4px 6px rgba(0,0,0,0.02);">
                                <div style="margin-bottom:15px;">
                                    <label style="font-size:0.8rem; color:#94a3b8;">วันเริ่มรับประกัน</label>
                                    <div id="view_deliver_date" style="color:#10b981; font-weight:bold; font-size:1.1rem;">-</div>
                                </div>
                                <div>
                                    <label style="font-size:0.8rem; color:#94a3b8;">วันสิ้นสุดรับประกัน</label>
                                    <div id="view_end_date" style="color:#ef4444; font-weight:bold; font-size:1.1rem;">-</div>
                                </div>
                            </div>

                            <div id="view_remark_wrapper" style="background:#fff1f1; padding:20px; border-radius:12px; border:1px solid #fee2e2; margin-top:20px; display:none;">
                                <label style="font-size:0.8rem; color:#b91c1c; font-weight:bold;">หมายเหตุรอตรวจสอบ</label>
                                <div id="view_status_remark" style="color:#991b1b; margin-top:5px;">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom JS (แยกไฟล์) -->
    <script src="assets/js/pm_project.js?v=<?php echo time(); ?>"></script>
</body>

</html>
