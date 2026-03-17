<?php
// pm_project.php
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
        while ($row = mysqli_fetch_assoc($maResult)) $maSchedule[] = $row;
        echo json_encode(['success' => true, 'data' => $project, 'ma' => $maSchedule]);
        exit;
    }

    // ฟังก์ชัน Save Project (แก้ไขให้รองรับการบันทึก MA เฉพาะตอน Add ใหม่ หรือ Edit ที่ส่งค่ามา)
    if ($action == 'save') {
        $id = intval($_POST['pmproject_id']);
        $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
        $customers_id = intval($_POST['customers_id']);
        $responsible_person = mysqli_real_escape_string($conn, $_POST['responsible_person']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $number = mysqli_real_escape_string($conn, $_POST['number']);
        $contract_period = isset($_POST['contract_period']) ? mysqli_real_escape_string($conn, $_POST['contract_period']) : '-';
        $going_ma = mysqli_real_escape_string($conn, $_POST['going_ma']);
        $deliver_work_date = !empty($_POST['deliver_work_date']) ? "'" . $_POST['deliver_work_date'] . "'" : "NULL";
        $end_date = !empty($_POST['end_date']) ? "'" . $_POST['end_date'] . "'" : "NULL";

        if ($id == 0) {
            $sql = "INSERT INTO pm_project (project_name, customers_id, responsible_person, status, number, contract_period, going_ma, deliver_work_date, end_date) VALUES ('$project_name', $customers_id, '$responsible_person', '$status', '$number', '$contract_period', '$going_ma', $deliver_work_date, $end_date)";
            if (mysqli_query($conn, $sql)) {
                $id = mysqli_insert_id($conn);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
        } else {
            $sql = "UPDATE pm_project SET project_name='$project_name', customers_id=$customers_id, responsible_person='$responsible_person', status='$status', number='$number', contract_period='$contract_period', going_ma='$going_ma', deliver_work_date=$deliver_work_date, end_date=$end_date WHERE pmproject_id=$id";
            if (!mysqli_query($conn, $sql)) {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
        }

        // Handle MA (ทำงานเฉพาะเมื่อมีการส่งค่า ma_dates มาเท่านั้น - กรณี Add Project)
        if (isset($_POST['ma_dates']) && is_array($_POST['ma_dates'])) {
            processMAData($conn, $id);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // *** เพิ่ม Action ใหม่สำหรับบันทึก MA อย่างเดียว ***
    if ($action == 'save_ma_only') {
        $id = intval($_POST['pmproject_id']);
        processMAData($conn, $id);
        echo json_encode(['success' => true]);
        exit;
    }
}

// ฟังก์ชันแยกสำหรับประมวลผล MA (เพื่อลดความซ้ำซ้อน)
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
                mysqli_query($conn, "INSERT INTO ma_schedule (pmproject_id, ma_date, note, remark, file_path) VALUES ($id, '$date', '$note', '$remark', '$finalFilePath')");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ระบบบริหารจัดการโครงการ | MaintDash</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/pm_project.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid" style="padding: 20px;">

            <div class="page-header-custom">
                <h2 style="margin:0; color:#2b2d42;"><i class="fas fa-project-diagram"></i> Primitive Maintenance</h2>
                <div class="header-right">
                    <button class="btn-add-project" onclick="openModal(0)"><i class="fas fa-plus"></i> เพิ่มโครงการ</button>
                </div>
            </div>

            <div class="stats-container">
                <div class="stat-card total">
                    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-info"><label>โครงการทั้งหมด</label><span id="stat_total">0</span></div>
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

            <div class="search-container-modern">
                <div class="search-wrapper-new">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="ค้นหาชื่อโครงการ, ลูกค้า หรือเลขที่โครงการ...">
                </div>
            </div>

            <div class="card-table">
                <table class="table-custom">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th style="width: 80px;">เลขที่โครงการ</th>
                                <th style="width: 20%;">ชื่อโครงการ</th>
                                <th style="width: 20%;">รายละเอียด</th>
                                <th style="width: 15%;">ลูกค้า</th>
                                <th style="width: 100px;">สถานะ</th>
                                <th style="width: 100px;">สัญญา</th>
                                <th style="width: 130px;" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="modalBackdrop"></div>

    <div class="modal fade" id="pmProjectModal">
        <div class="modal-dialog modal-xl">
            <div class="modal-header">
                <h5 id="modalTitle">จัดการโครงการ</h5>
                <button type="button" class="close-modal-x" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="pmProjectForm" enctype="multipart/form-data">
                    <input type="hidden" name="pmproject_id" id="pmproject_id" value="0">
                    <div class="form-row" style="display:flex; gap:20px; margin-bottom:15px;">
                        <div style="flex:1;"><label class="form-label">เลขที่โครงการ</label><input type="text" name="number" id="number" class="form-control" required></div>
                        <div style="flex:1;">
                            <label class="form-label">สถานะรายการ</label>
                            <select name="status" id="status" class="form-control">
                                <option value="รอการตรวจสอบ">รอการตรวจสอบ</option>
                                <option value="กำลังดำเนินการ">กำลังดำเนินการ</option>
                                <option value="ดำเนินการเสร็จสิ้น">ดำเนินการเสร็จสิ้น</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:15px;"><label class="form-label">ชื่อโครงการ</label><input type="text" name="project_name" id="project_name" class="form-control" required></div>
                    <div class="form-group" style="margin-bottom:15px;"><label class="form-label">รายละเอียดโครงการ</label><textarea name="going_ma" id="going_ma" rows="3" class="form-control"></textarea></div>
                    <div class="form-row" style="display:flex; gap:20px; margin-bottom:15px;">
                        <div style="flex:1;">
                            <label class="form-label">ลูกค้า (Customer)</label>
                            <select name="customers_id" id="customers_id" class="form-control">
                                <?php
                                $cSql = mysqli_query($conn, "SELECT * FROM customers ORDER BY customers_name ASC");
                                while ($c = mysqli_fetch_assoc($cSql)) {
                                    echo "<option value='{$c['customers_id']}'>{$c['customers_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div style="flex:1;"><label class="form-label">ผู้รับผิดชอบ</label><input type="text" name="responsible_person" id="responsible_person" class="form-control"></div>
                    </div>
                    <div class="form-row" style="display:flex; gap:20px; margin-bottom:15px;">
                        <div style="flex:1;"><label class="form-label">วันส่งมอบงาน</label><input type="date" name="deliver_work_date" id="deliver_work_date" class="form-control"></div>
                        <div style="flex:1;"><label class="form-label">วันสิ้นสุดสัญญา</label><input type="date" name="end_date" id="end_date" class="form-control"></div>
                    </div>
                    <div class="form-group" style="margin-bottom:15px;"><label class="form-label">ระยะเวลาสัญญา</label><input type="text" name="contract_period" id="contract_period" class="form-control" placeholder="เช่น 1 ปี, 6 เดือน"></div>

                    <div id="maSectionWrapper" style="display:block;">
                        <div style="background:#f8fafc; padding:20px; border-radius:16px; border:1px solid #eee; margin-top:20px;">
                            <label style="font-weight:700; color:var(--primary); margin-bottom:10px; display:block;">สร้างแผน MA เบื้องต้น (Initial MA Plan)</label>
                            <div style="display:flex; gap:10px; margin-bottom:20px;">
                                <select id="calc_frequency" class="form-control" style="width:180px;">
                                    <option value="1">ทุก 1 เดือน</option>
                                    <option value="3">ทุก 3 เดือน</option>
                                    <option value="6">ทุก 6 เดือน</option>
                                    <option value="12">ทุก 1 ปี</option>
                                </select>
                                <button type="button" onclick="calculateMA('#maScheduleContainer', '#deliver_work_date', '#end_date', '#calc_frequency')" class="btn-add-project" style="background:var(--success);">คำนวณอัตโนมัติ</button>
                                <button type="button" onclick="addMARow('#maScheduleContainer')" class="btn-add-project" style="background:#64748b;"><i class="fas fa-plus"></i> เพิ่มแถวเอง</button>
                            </div>
                            <div id="maScheduleContainer"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="padding:20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn-close-gray" onclick="closeModal()">ยกเลิก</button>
                <button type="submit" form="pmProjectForm" class="btn-add-project">บันทึกข้อมูลโครงการ</button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="maManageModal">
        <div class="modal-dialog modal-xl">
            <div class="modal-header" style="background: #f0fdf4;">
                <h5 style="color:#166534;"><i class="fas fa-calendar-check"></i> จัดการแผนบำรุงรักษา (MA Management)</h5>
                <button type="button" class="close-modal-x" onclick="closeMAModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="maManageForm" enctype="multipart/form-data">
                    <input type="hidden" name="pmproject_id" id="ma_pmproject_id" value="">

                    <div style="background:#fff; padding:15px; border-radius:12px; border:1px solid #bbf7d0; margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 id="ma_project_title" style="margin:0; font-weight:700; color:#166534;">Project Name</h4>
                                <span id="ma_project_dates" style="font-size:0.85rem; color:#666;">ระยะเวลาสัญญา: -</span>
                            </div>
                            <div style="display:flex; gap:10px;">
                                <input type="hidden" id="ma_ref_start_date">
                                <input type="hidden" id="ma_ref_end_date">

                                <select id="ma_calc_frequency" class="form-control" style="width:150px;">
                                    <option value="1">ทุก 1 เดือน</option>
                                    <option value="3">ทุก 3 เดือน</option>
                                    <option value="6">ทุก 6 เดือน</option>
                                    <option value="12">ทุก 1 ปี</option>
                                </select>
                                <button type="button" onclick="calculateMA('#maManageContainer', '#ma_ref_start_date', '#ma_ref_end_date', '#ma_calc_frequency')" class="btn-add-project" style="background:var(--success);">คำนวณใหม่</button>
                                <button type="button" onclick="addMARow('#maManageContainer')" class="btn-add-project" style="background:#64748b;"><i class="fas fa-plus"></i> เพิ่มแถว</button>
                            </div>
                        </div>
                    </div>

                    <div id="maManageContainer" style="max-height: 500px; overflow-y: auto;"></div>
                </form>
            </div>
            <div class="modal-footer" style="padding:20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn-close-gray" onclick="closeMAModal()">ปิดหน้าต่าง</button>
                <button type="submit" form="maManageForm" class="btn-add-project" style="background:#166534;">บันทึกแผน MA</button>
            </div>
        </div>
    </div>

   <div class="modal-body" style="padding: 25px;">
    <div class="view-info-container">
        <div class="info-group">
            <label>ลูกค้า / Customer</label>
            <span id="view_customer_name">-</span>
        </div>
        <div class="info-group">
            <label>ผู้รับผิดชอบ</label>
            <span id="view_responsible">-</span>
        </div>
        <div class="info-group">
            <label>วันส่งมอบงาน</label>
            <span id="view_deliver_date">-</span>
        </div>
        <div class="info-group">
            <label>วันสิ้นสุดสัญญา</label>
            <span id="view_end_date">-</span>
        </div>
    </div>

    <h5 style="margin-bottom: 15px; font-weight: 700; color: var(--secondary);">
        <i class="fas fa-list-check"></i> ตารางแผนการบำรุงรักษา
    </h5>
    <table class="table-modal">
        <thead>
            <tr>
                <th>ครั้งที่</th>
                <th>วันที่</th>
                <th>รายละเอียด</th>
                <th>หมายเหตุ</th>
                <th>ไฟล์แนบ</th>
            </tr>
        </thead>
        <tbody id="view_ma_table_body"> </tbody>
    </table>
</div>
                    <div style="margin-top:30px;">
                        <h6 style="font-weight:700; color:var(--secondary); margin-bottom:15px; text-transform:uppercase; font-size:0.8rem;">ตารางแผนการบำรุงรักษา (MA Schedule)</h6>
                        <table class="table-clean" id="view_ma_table">
                            <thead>
                                <tr>
                                    <th>ครั้งที่</th>
                                    <th>วันที่</th>
                                    <th>รายละเอียด</th>
                                    <th>หมายเหตุ</th>
                                    <th>ไฟล์แนบ</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div style="padding:24px; text-align:right; border-top:1px solid #eee;">
                    <button type="button" class="btn-close-gray" onclick="closeViewModal()">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/pm_project.js?v=<?php echo time(); ?>"></script>
</body>

</html>