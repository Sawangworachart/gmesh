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
                $summary[$status_key] = (int)$row['count'];
                $summary['Total'] += (int)$row['count'];
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
        if (!empty($r['project_name'])) $project_filter_opt[] = $r['project_name'];
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
    <title>MaintDash</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/service_project.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content animate-enter-right">

        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-box">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Service</h2>
                    <p class="header-sub-desc">ระบบบันทึกข้อมูลการแจ้งซ่อมและประวัติการเข้าบริการลูกค้า</p>
                </div>
            </div>

            <div class="header-right-action" style="display: flex; gap: 10px; align-items: center;">
                <a href="service_project_export.php" class="btn-add-custom" style="background:#10b981;" onclick="exportExcel()">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <button class="btn-add-custom" onclick="openModal()">
                    <i class="fas fa-plus"></i> เพิ่มข้อมูล
                </button>
            </div>
        </div>

        <div class="status-cards">
            <div class="status-card card-total" id="cardTotal" onclick="filterByStatus('')">
                <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                <div class="card-info">
                    <h4>ทั้งหมด</h4>
                    <div class="count counter-anim">0</div>
                </div>
            </div>
            <div class="status-card card-onsite" id="cardOnsite" onclick="filterByStatus('On-site')">
                <div class="card-icon"><i class="fas fa-building"></i></div>
                <div class="card-info">
                    <h4>On-site</h4>
                    <div class="count counter-anim">0</div>
                </div>
            </div>
            <div class="status-card card-remote" id="cardRemote" onclick="filterByStatus('Remote')">
                <div class="card-icon"><i class="fas fa-laptop-house"></i></div>
                <div class="card-info">
                    <h4>Remote</h4>
                    <div class="count counter-anim">0</div>
                </div>
            </div>
            <div class="status-card card-sub" id="cardSub" onclick="filterByStatus('Subcontractor')">
                <div class="card-icon"><i class="fas fa-user-friends"></i></div>
                <div class="card-info">
                    <h4>แจ้ง Subcontractor</h4>
                    <div class="count counter-anim">0</div>
                </div>
            </div>
        </div>

        <div class="table-toolbar-combined">
            <div class="search-box-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่องาน, ชื่อลูกค้า, หรือ S/N..." onkeyup="filterTable()">
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

        <div class="card table-card glass-effect">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="10%" class="text-center">เลขที่โครงการ</th>
                            <th width="12%">วันที่เริ่ม / วันที่สิ้นสุด</th>
                            <th width="20%">โครงการ</th>
                            <th width="20%">ลูกค้า</th>
                            <th width="18%">อุปกรณ์</th>
                            <th width="10%">สถานะ</th>
                            <th width="10%" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="7" class="loading-state">...กำลังโหลดข้อมูล...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="modal-overlay" id="serviceModal">
        <div class="modal-box glass-modal animate-slide-in">
            <div class="modal-header">
                <div class="modal-title-group" style="display: flex; align-items: center; gap: 15px;">
                    <div class="modal-icon"><i class="fas fa-edit"></i></div>
                    <div class="modal-text">
                        <h3 id="modalTitle">บันทึกงานบริการ</h3>
                        <span>จัดการรายละเอียดโครงการและอาการเสีย</span>
                    </div>
                </div>
                <button type="button" class="close-modal" onclick="closeModal()">&times;</button>
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
                            <?php foreach ($project_options_all as $proj): ?>
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
                        <span class="input-icon" style="top: 20px; transform: none;"><i class="fas fa-exclamation-triangle"></i></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">การแก้ไข / ดำเนินการ</label>
                    <div class="input-group-modern">
                        <textarea id="action_taken" name="action_taken" class="form-control" rows="2"></textarea>
                        <span class="input-icon" style="top: 20px; transform: none;"><i class="fas fa-check-circle"></i></span>
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

<div class="modal-overlay" id="viewModal">
    <div class="modal-box glass-modal animate-slide-in" style="max-width: 850px; width: 95%; border-radius: 24px; overflow: hidden; border:none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);">

        <div class="modal-header-custom" style="background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%); color: #1e293b; padding: 30px; position: relative; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="background: #e0f2fe; color: #0ea5e9; padding: 15px; border-radius: 16px; font-size: 1.8rem; border: 1px solid #bae6fd; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.1);">
                    <i class="fas fa-eye"></i>
                </div>
                <div style="flex: 1;">
                    <h3 id="view_project_name_header" style="margin:0; font-size: 1.6rem; font-weight: 800; color: #0c4a6e; letter-spacing: -0.025em; line-height: 1.2;">กำลังโหลดชื่อโครงการ...</h3>
                    <p style="margin: 6px 0 0 0; color: #64748b; font-size: 1rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-tie" style="color: #0ea5e9;"></i> 
                        <span id="view_customer_name_header" style="font-weight: 500;">-</span>
                        <span style="opacity: 0.3;">|</span>
                        <span style="font-family: monospace; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            ID: <span id="view_ref_number_header">-</span>
                        </span>
                    </p>
                </div>
            </div>
            <button onclick="closeViewModal()" style="background: #f1f5f9; border: none; color: #b89494; cursor: pointer; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 30px; right: 30px; transition: 0.3s; border: 1px solid #e2e8f0;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#94a3b8';">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-custom custom-scroll" style="padding: 35px; background: #f8fafc; max-height: 75vh; overflow-y: auto;">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 18px; border: 1px solid #e2e8f0; border-bottom: 4px solid #4361ee; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <label style="display:block; font-size: 0.75rem; color: #64748b; margin-bottom: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">รูปแบบบริการ</label>
                    <div id="view_status_badge" style="font-weight: 700; font-size: 1.1rem; color: #1e293b;">-</div>
                </div>
                <div style="background: white; padding: 20px; border-radius: 18px; border: 1px solid #e2e8f0; border-bottom: 4px solid #10b981; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <label style="display:block; font-size: 0.75rem; color: #64748b; margin-bottom: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">ช่วงเวลาดำเนินการ</label>
                    <span id="view_date_range" style="font-weight: 700; color: #1e293b; font-size: 1.05rem;">-</span>
                </div>
                <div style="background: white; padding: 20px; border-radius: 18px; border: 1px solid #e2e8f0; border-bottom: 4px solid #f59e0b; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <label style="display:block; font-size: 0.75rem; color: #64748b; margin-bottom: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">อุปกรณ์หลัก / SN</label>
                    <div id="view_equipment_sn" style="font-weight: 700; color: #1e293b; font-size: 1.05rem; line-height: 1.3;">-</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr; gap: 25px;">
                <div style="background:white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);">
                    <h4 style="margin-top:0; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; color: #ef4444; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; font-weight: 700;">
                        <i class="fas fa-exclamation-circle"></i> อาการเสีย / สิ่งที่พบ
                    </h4>
                    <div id="view_symptom" style="line-height: 1.8; color: #334155; font-size: 1rem; padding-top: 15px; white-space: pre-line;">-</div>
                </div>

                <div style="background:white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);">
                    <h4 style="margin-top:0; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; color: #10b981; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; font-weight: 700;">
                        <i class="fas fa-check-circle"></i> การแก้ไข / ดำเนินการ
                    </h4>
                    <div id="view_action" style="line-height: 1.8; color: #334155; font-size: 1rem; padding-top: 15px; white-space: pre-line;">-</div>
                </div>

                <div style="background:white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);">
                    <label style="display:block; font-size: 0.75rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.05em;">ไฟล์แนบ / หลักฐาน</label>
                    <div id="view_file_container">
                        <div style="text-align: center; padding: 20px; border: 2px dashed #e2e8f0; border-radius: 12px; color: #cbd5e1;">
                            <i class="fas fa-file-alt" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <div style="font-size: 0.85rem; font-style: italic;">ไม่มีไฟล์แนบ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/service_project.js?v=<?php echo filemtime('js/service_project.js'); ?>"></script>

    <script>
        // Inline script สำหรับ preview file อย่างง่าย
        function previewFile(input) {
            if (input.files && input.files[0]) {
                $('#filePreview').html('<span style="color:#059669"><i class="fas fa-check"></i> เลือกไฟล์: ' + input.files[0].name + '</span>');
            }
        }
    </script>
</body>

</html>