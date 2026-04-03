<?php
// หน้า product ของ admin
session_start();
require_once 'db.php';

$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// --------------------------------------------------------------------------
//  API HANDLER
// --------------------------------------------------------------------------
if (isset($_GET['api']) && $_GET['api'] == 'true') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    try {
        if ($action == 'get_stats') {
            // คำนวณสถิติแยกตาม tinyint 1, 2, 3, 4 จากฐานข้อมูล
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as s1,
                        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as s2,
                        SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as s3,
                        SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as s4
                    FROM product";
            $res = mysqli_query($conn, $sql);
            $stats = mysqli_fetch_assoc($res);
            echo json_encode([
                'success' => true,
                'stats' => [
                    'all' => (int)$stats['total'],
                    's1' => (int)$stats['s1'],
                    's2' => (int)$stats['s2'],
                    's3' => (int)$stats['s3'],
                    's4' => (int)$stats['s4']
                ]
            ]);
            exit;
        }

        if ($action == 'fetch_all') {
            $sql = "SELECT p.*, c.customers_name, c.agency, c.phone as c_phone 
                    FROM product p 
                    LEFT JOIN customers c ON p.customers_id = c.customers_id 
                    ORDER BY p.product_id DESC";
            $result = mysqli_query($conn, $sql);
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        if ($action == 'fetch_single') {
            $id = intval($_GET['id']);
            $sql = "SELECT p.*, c.customers_name, c.agency, c.phone, c.address 
                    FROM product p 
                    LEFT JOIN customers c ON p.customers_id = c.customers_id 
                    WHERE p.product_id = $id";
            $res = mysqli_query($conn, $sql);
            $data = mysqli_fetch_assoc($res);
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        if ($action == 'save') {
            $id = intval($_POST['product_id']);
            $customers_id = intval($_POST['customers_id']);
            $device_name = mysqli_real_escape_string($conn, $_POST['device_name']);
            $serial_number = mysqli_real_escape_string($conn, $_POST['serial_number']);
            $repair_details = mysqli_real_escape_string($conn, $_POST['repair_details']);
            $status = intval($_POST['status']); // รับค่าเป็น Integer ตาม DB
            $status_remark = mysqli_real_escape_string($conn, $_POST['status_remark'] ?? '');

            $start_date = !empty($_POST['start_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : "NULL";
            $end_date = !empty($_POST['end_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['end_date']) . "'" : "NULL";

            $file_path = $_POST['existing_file_path'] ?? '';

            if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
                $ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
                $new_name = 'prod_' . time() . '.' . $ext;
                $target = $upload_dir . $new_name;
                if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target)) {
                    if (!empty($file_path) && file_exists($file_path)) {
                        @unlink($file_path);
                    }
                    $file_path = $target;
                }
            }

            if ($id == 0) {
                $sql = "INSERT INTO product (customers_id, device_name, serial_number, repair_details, file_path, status, status_remark, start_date, end_date)
                        VALUES ($customers_id, '$device_name', '$serial_number', '$repair_details', '$file_path', $status, '$status_remark', $start_date, $end_date)";
            } else {
                $sql = "UPDATE product SET 
                        customers_id=$customers_id, 
                        device_name='$device_name',
                        serial_number='$serial_number', 
                        repair_details='$repair_details', 
                        file_path='$file_path', 
                        status=$status,
                        status_remark='$status_remark',
                        start_date=$start_date,
                        end_date=$end_date
                        WHERE product_id=$id";
            }

            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อย']);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
            }
            exit;
        }

        if ($action == 'delete') {
            $id = intval($_POST['id']);
            $sql = "DELETE FROM product WHERE product_id = $id";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
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
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="CSS/product.css?v=<?php echo time(); ?>">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-circle"><i class="fas fa-boxes"></i></div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Product Claim</h2>
                    <p class="header-sub-desc">ระบบจัดการและติดตามสถานะงานซ่อม ตั้งแต่รับอุปกรณ์จนถึงส่งคืนลูกค้า</p>
                </div>
            </div>

            <div class="header-right-action" style="display:flex; gap:10px; align-items:center;">
                <button class="btn-pill-excel" onclick="exportExcel()">
                    <i class="fas fa-file-excel"></i>Excel
                </button>
                <button class="btn-pill-primary" onclick="openModal('create')">
                    <i class="fas fa-plus"></i> เพิ่มข้อมูล
                </button>
            </div>
        </div>

        <div class="stats-row" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px;">
            <div class="stat-card card-all active" onclick="filterByStatus('all', this)">
                <div class="stat-icon-box bg-light-gray"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info">
                    <p>ทั้งหมด</p>
                    <h3 id="stat_all">0</h3>
                </div>
            </div>
            <div class="stat-card" onclick="filterByStatus('รอสินค้าจากลูกค้า', this)">
                <div class="stat-icon-box bg-light-blue"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <p>รอสินค้าจากลูกค้า</p>
                    <h3 id="stat_s1">0</h3>
                </div>
            </div>
            <div class="stat-card" onclick="filterByStatus('ตรวจสอบ', this)">
                <div class="stat-icon-box bg-light-purple"><i class="fas fa-search"></i></div>
                <div class="stat-info">
                    <p>ตรวจสอบ</p>
                    <h3 id="stat_s2">0</h3>
                </div>
            </div>
            <div class="stat-card" onclick="filterByStatus('รอสินค้าจากsupplier', this)">
                <div class="stat-icon-box bg-light-orange"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <p>รอสินค้าจาก supplier </p>
                    <h3 id="stat_s3">0</h3>
                </div>
            </div>
            <div class="stat-card" onclick="filterByStatus('ส่งคืนลูกค้า', this)">
                <div class="stat-icon-box bg-light-green" style="background:rgba(34,197,94,0.1); color:#16a34a;"><i class="fas fa-check-double"></i></div>
                <div class="stat-info">
                    <p>ส่งคืนลูกค้า</p>
                    <h3 id="stat_s4">0</h3>
                </div>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-container-custom">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อลูกค้า, อุปกรณ์ หรือ S/N..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="card-table">
            <table class="table-custom">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align:center;">ลำดับ</th>
                            <th style="width: 20%;">ลูกค้า / แผนก</th>
                            <th style="width: 15%;">อุปกรณ์</th>
                            <th style="width: 15%;">S/N</th>
                            <th style="width: 10%; text-align:center;">สถานะ</th>
                            <th style="width: 20%;">วันที่เริ่ม / วันที่สิ้นสุด</th>
                            <th style="width: 10%; text-align:center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
                <tbody id="tableBody"></tbody>
            </table>
            <div id="loading" style="text-align:center; padding:30px; display:none;"><i class="fas fa-spinner fa-spin text-primary"></i> กำลังโหลด...</div>
            <div id="noData" style="text-align:center; padding:30px; display:none; color:#999;">ไม่พบข้อมูล</div>
        </div>
    </div>

    <div id="productModal" class="modal-overlay">
        <div class="modal-box custom-modal-style" style="max-width: 800px;">

            <div class="modal-header-custom">
                <div class="header-left">
                    <div class="header-icon-box icon-bg-orange"><i class="fas fa-pen"></i></div>
                    <div class="header-titles">
                        <h3 id="modalTitle">เพิ่มงานบริการใหม่</h3>
                        <p class="header-subtitle">จัดการรายละเอียดข้อมูลอุปกรณ์และอาการเสีย</p>
                    </div>
                </div>
                <button class="close-btn-custom" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>

            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="product_id" name="product_id" value="0">
                <input type="hidden" id="existing_file_path" name="existing_file_path">

                <div class="modal-body custom-scroll">

                    <div class="section-header">
                        <div class="section-indicator"></div>
                        <i class="fas fa-user-circle section-icon"></i>
                        <span>ข้อมูลลูกค้า</span>
                    </div>

                    <div class="form-group form-full">
                        <label class="form-label">ลูกค้า <span style="color:red">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-user-tie input-icon"></i>
                            <select id="customers_id" name="customers_id" class="form-control-custom" required>
                                <option value="">-- เลือกลูกค้า --</option>
                                <?php
                                $c_res = mysqli_query($conn, "SELECT customers_id, customers_name, agency FROM customers ORDER BY customers_name ASC");
                                while ($c = mysqli_fetch_assoc($c_res)) {
                                    $display_name = htmlspecialchars($c['customers_name']);
                                    if (!empty($c['agency'])) {
                                        $display_name .= " (" . htmlspecialchars($c['agency']) . ")";
                                    }
                                    echo '<option value="' . $c['customers_id'] . '">' . $display_name . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="section-header mt-4">
                        <div class="section-indicator"></div>
                        <i class="fas fa-tools section-icon"></i>
                        <span>รายละเอียดอุปกรณ์</span>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">อุปกรณ์ (Equipment)</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-microchip input-icon"></i>
                                <input type="text" id="device_name" name="device_name" class="form-control-custom" required placeholder="ระบุชื่ออุปกรณ์">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">S/N (Serial Number)</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-barcode input-icon"></i>
                                <input type="text" id="serial_number" name="serial_number" class="form-control-custom" placeholder="ระบุ S/N">
                            </div>
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">อาการเสีย / สิ่งที่พบ</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-exclamation-triangle input-icon"></i>
                                <textarea id="repair_details" name="repair_details" class="form-control-custom" rows="3" placeholder="รายละเอียดอาการเสีย..." style="padding-left: 45px;"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="section-header mt-4">
                        <div class="section-indicator"></div>
                        <i class="fas fa-clipboard-list section-icon"></i>
                        <span>สถานะการดำเนินงาน</span>
                    </div>

                    <div class="row-grid-3">
                        <div class="form-group">
                            <label class="form-label-clean">สถานะ</label>
                            <div class="input-wrapper-pill">
                                <div class="icon-circle gray">
                                    <i class="fas fa-info"></i>
                                </div>
                                <select id="status" name="status" class="form-control-pill">
                                    <option value="1">รอสินค้าจากลูกค้า</option>
                                    <option value="2">ตรวจสอบ</option>
                                    <option value="3">รอสินค้าจากsupplier</option>
                                    <option value="4">ส่งคืนลูกค้า</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label-clean">วันที่เริ่ม</label>
                            <div class="input-wrapper-pill">
                                <div class="icon-circle gray">
                                    <i class="far fa-calendar-alt"></i>
                                </div>
                                <input type="date" id="start_date" name="start_date" class="form-control-pill" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label-clean">วันที่สิ้นสุด</label>
                            <div class="input-wrapper-pill">
                                <div class="icon-circle blue-gray">
                                    <i class="far fa-calendar-check"></i>
                                </div>
                                <input type="date" id="end_date" name="end_date" class="form-control-pill">
                            </div>
                        </div>
                    </div>

                    <div class="upload-section mt-4">
                        <label for="file_upload" class="upload-box-dashed">
                            <div class="upload-content">
                                <div class="upload-icon-bg">
                                    <i class="fas fa-file-upload"></i>
                                </div>
                                <span class="upload-text">แนบรูปภาพหรือไฟล์ PDF</span>
                                <span id="file-name-display" class="file-selected-text"></span>
                            </div>
                            <input type="file" id="file_upload" name="file_upload" accept=".jpg,.jpeg,.png,.pdf" onchange="showFileName(this)">
                        </label>
                        <div id="existing_file_container" class="mt-2 text-center text-primary"></div>
                    </div>

                </div>
                <div class="modal-footer-custom">
                    <button type="submit" id="saveBtn" class="btn-save-custom"><i class="fas fa-check-circle"></i> บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewModal" class="modal-overlay">
        <div class="modal-box custom-modal-style" style="max-width: 900px;">

            <div class="modal-header-custom">
                <div style="flex-grow: 1;"></div>
                <button class="close-btn-custom" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body custom-scroll" style="padding: 0 40px 40px 40px;">

                <div class="modal-view-header">
                    <div class="view-icon-large">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="view-title-group">
                        <h2 id="view_customer">-</h2>
                        <p>Ticket ID: <span id="view_ticket_id">#WAITING_DB</span></p>
                    </div>
                </div>

                <div class="info-card-grid">

                    <div class="info-card card-blue">
                        <span class="card-label">รูปแบบบริการ</span>
                        <div class="card-value">
                            <i class="fas fa-building" style="color:#3b82f6;"></i>
                            <span id="view_status_text">On-site</span>
                        </div>
                    </div>

                    <div class="info-card card-green">
                        <span class="card-label">ช่วงเวลาดำเนินการ</span>
                        <div class="card-value">
                            <span id="view_start_date" style="font-size:0.95rem;">-</span>
                        </div>
                        <div class="card-sub-value">
                            ถึง <span id="view_end_date">-</span>
                        </div>
                    </div>

                    <div class="info-card card-orange">
                        <span class="card-label">อุปกรณ์หลัก / SN</span>
                        <div class="card-value" id="view_device_name">-</div>
                        <div class="card-sub-value">
                            SN: <span id="view_sn">-</span>
                        </div>
                    </div>

                </div>

                <div class="detail-section theme-red">
                    <div class="detail-header">
                        <div class="detail-icon"><i class="fas fa-exclamation"></i></div>
                        <span>อาการเสีย / สิ่งที่พบ / รายละเอียด</span>
                    </div>
                    <div class="detail-body">
                        <div id="view_details">-</div>
                    </div>
                </div>

                <div id="view_file_section" style="display:none; margin-top: 25px; padding: 15px 5px; border-top: 1px solid #f1f5f9; align-items: center; justify-content: space-between;">

                    <label style="font-size: 1rem; color: #64748b; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-paperclip" style="color: #94a3b8;"></i> ไฟล์แนบ / หลักฐาน
                    </label>

                    <a id="btn_open_file" href="#" target="_blank" class="btn-view-file" style="display: inline-flex; align-items: center; gap: 8px; background-color: #4361ee; color: #ffffff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: 0.2s; box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);">
                        <i class="fas fa-file-pdf"></i> เปิดดูไฟล์แนบ
                    </a>

                </div>
            </div>

        </div>
    </div>
    </div>

    <script src="js/product.js?v=<?php echo time(); ?>"></script>
    <script>
        function exportExcel() {
            window.open('product_export.php', '_blank');
        }

        function showFileName(input) {
            const display = document.getElementById('file-name-display');
            if (input.files && input.files.length > 0) {
                display.textContent = "ไฟล์ที่เลือก: " + input.files[0].name;
                display.style.color = "#16a34a"; // สีเขียว
            } else {
                display.textContent = "";
            }
        }
    </script>

</body>

</html>