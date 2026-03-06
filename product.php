<?php
// =========================================
// หน้าจัดการสินค้า (Admin) - Product Management
// =========================================

session_start(); // เริ่มต้น Session
error_reporting(E_ALL); // เปิดการแสดงข้อผิดพลาดทั้งหมด
ini_set('display_errors', 0); // ปิดการแสดงข้อผิดพลาดบนหน้าเว็บ (ควรเปิดใน Environment Dev)

require_once 'db.php'; // เชื่อมต่อฐานข้อมูล

// สร้างโฟลเดอร์สำหรับเก็บไฟล์อัปโหลดหากยังไม่มี
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true); // สร้างโฟลเดอร์และกำหนดสิทธิ์
}

// --------------------------------------------------------------------------
//  API HANDLER (จัดการคำขอ AJAX)
// --------------------------------------------------------------------------
if (isset($_GET['api']) && $_GET['api'] == 'true') {
    ob_clean(); // ล้าง Output Buffer
    header('Content-Type: application/json'); // กำหนด Header เป็น JSON

    $action = $_POST['action'] ?? $_GET['action'] ?? ''; // รับค่า Action

    try {
        // 1. ดึงสถิติ (Get Statistics)
        if ($action == 'get_stats') {
            // Query เพื่อนับจำนวนสถานะต่างๆ
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as s1,
                        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as s2,
                        SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as s3,
                        SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as s4
                    FROM product";
            $res = mysqli_query($conn, $sql);
            $stats = mysqli_fetch_assoc($res);

            // ส่งคืนข้อมูล JSON
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

        // 2. ดึงข้อมูลทั้งหมด (Fetch All)
        if ($action == 'fetch_all') {
            // Query ข้อมูลสินค้าพร้อมข้อมูลลูกค้า
            $sql = "SELECT p.*, c.customers_name, c.agency, c.phone as c_phone 
                    FROM product p 
                    LEFT JOIN customers c ON p.customers_id = c.customers_id 
                    ORDER BY p.product_id DESC";
            $result = mysqli_query($conn, $sql);
            
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row; // เก็บข้อมูลลง Array
            }
            
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        // 3. ดึงข้อมูลรายการเดียว (Fetch Single)
        if ($action == 'fetch_single') {
            $id = intval($_GET['id']); // แปลง ID เป็นตัวเลข
            $sql = "SELECT p.*, c.customers_name, c.agency, c.phone, c.address 
                    FROM product p 
                    LEFT JOIN customers c ON p.customers_id = c.customers_id 
                    WHERE p.product_id = $id";
            $res = mysqli_query($conn, $sql);
            $data = mysqli_fetch_assoc($res);
            
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        // 4. บันทึกข้อมูล (Save - Insert/Update)
        if ($action == 'save') {
            $id = intval($_POST['product_id']); // รับ ID (ถ้าเป็น 0 คือเพิ่มใหม่)
            $customers_id = intval($_POST['customers_id']);
            $device_name = mysqli_real_escape_string($conn, $_POST['device_name']);
            $serial_number = mysqli_real_escape_string($conn, $_POST['serial_number']);
            $repair_details = mysqli_real_escape_string($conn, $_POST['repair_details']);
            $status = intval($_POST['status']);
            $status_remark = mysqli_real_escape_string($conn, $_POST['status_remark'] ?? '');

            // จัดการวันที่ (ถ้าว่างให้เป็น NULL)
            $start_date = !empty($_POST['start_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : "NULL";
            $end_date = !empty($_POST['end_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['end_date']) . "'" : "NULL";

            $file_path = $_POST['existing_file_path'] ?? ''; // ใช้ไฟล์เดิมเป็นค่าเริ่มต้น

            // จัดการอัปโหลดไฟล์
            if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
                $ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION); // นามสกุลไฟล์
                $new_name = 'prod_' . time() . '.' . $ext; // ตั้งชื่อไฟล์ใหม่
                $target = $upload_dir . $new_name; // path ปลายทาง
                
                if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target)) {
                    // ลบไฟล์เดิมถ้ามี
                    if (!empty($file_path) && file_exists($file_path)) {
                        @unlink($file_path);
                    }
                    $file_path = $target; // อัปเดต path ไฟล์ใหม่
                }
            }

            // ตรวจสอบว่าเป็น Insert หรือ Update
            if ($id == 0) {
                // เพิ่มข้อมูลใหม่
                $sql = "INSERT INTO product (customers_id, device_name, serial_number, repair_details, file_path, status, status_remark, start_date, end_date)
                        VALUES ($customers_id, '$device_name', '$serial_number', '$repair_details', '$file_path', $status, '$status_remark', $start_date, $end_date)";
            } else {
                // แก้ไขข้อมูลเดิม
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

            // ประมวลผลคำสั่ง SQL
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อย']);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
            }
            exit;
        }

        // 5. ลบข้อมูล (Delete)
        if ($action == 'delete') {
            $id = intval($_POST['id']);
            // ลบไฟล์แนบก่อน (ถ้าต้องการ) - ในที่นี้ข้ามไป
            $sql = "DELETE FROM product WHERE product_id = $id";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
            }
            exit;
        }

    } catch (Exception $e) {
        // จัดการข้อผิดพลาดทั่วไป
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
    <title>MaintDash - Product Claim</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="assets/css/product.css?v=<?php echo time(); ?>"> <!-- Custom CSS -->
    
    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <?php include 'sidebar.php'; ?> <!-- เมนูด้านข้าง -->

    <div class="main-content">
        
        <!-- ส่วนหัว (Banner) -->
        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-circle"><i class="fas fa-boxes"></i></div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Product Claim</h2>
                    <p class="header-sub-desc">ระบบจัดการและติดตามสถานะงานซ่อม/เคลมสินค้า</p>
                </div>
            </div>

            <div class="header-right-action" style="display:flex; gap:10px; align-items:center;">
                <button class="btn-pill-excel" onclick="exportExcel()">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button class="btn-pill-primary" onclick="openModal('create')">
                    <i class="fas fa-plus"></i> เพิ่มข้อมูล
                </button>
            </div>
        </div>

        <!-- การ์ดสถานะ (Stats Cards) -->
        <div class="stats-row">
            <div class="stat-card card-all active" onclick="filterByStatus('all', this)">
                <div class="stat-icon-box"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info">
                    <p>ทั้งหมด</p>
                    <h3 id="stat_all">0</h3>
                </div>
            </div>
            <div class="stat-card card-s1" onclick="filterByStatus('รอสินค้าจากลูกค้า', this)">
                <div class="stat-icon-box"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <p>รอสินค้าจากลูกค้า</p>
                    <h3 id="stat_s1">0</h3>
                </div>
            </div>
            <div class="stat-card card-s2" onclick="filterByStatus('ตรวจสอบ', this)">
                <div class="stat-icon-box"><i class="fas fa-search"></i></div>
                <div class="stat-info">
                    <p>ตรวจสอบ</p>
                    <h3 id="stat_s2">0</h3>
                </div>
            </div>
            <div class="stat-card card-s3" onclick="filterByStatus('รอสินค้าจาก Supplier', this)">
                <div class="stat-icon-box"><i class="fas fa-truck"></i></div>
                <div class="stat-info">
                    <p>รอ Supplier</p>
                    <h3 id="stat_s3">0</h3>
                </div>
            </div>
            <div class="stat-card card-s4" onclick="filterByStatus('ส่งคืนลูกค้า', this)">
                <div class="stat-icon-box"><i class="fas fa-check-double"></i></div>
                <div class="stat-info">
                    <p>ส่งคืนลูกค้า</p>
                    <h3 id="stat_s4">0</h3>
                </div>
            </div>
        </div>

        <!-- เครื่องมือค้นหา (Toolbar) -->
        <div class="table-toolbar">
            <div class="search-container-custom">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อลูกค้า, อุปกรณ์ หรือ S/N..." onkeyup="filterTable()">
            </div>
        </div>

        <!-- ตารางข้อมูล (Table) -->
        <div class="card-table">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="20%">ลูกค้า / แผนก</th>
                        <th width="20%">อุปกรณ์</th>
                        <th width="15%">S/N</th>
                        <th width="15%" class="text-center">สถานะ</th>
                        <th width="15%">วันที่เริ่ม / สิ้นสุด</th>
                        <th width="10%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- ข้อมูลจะถูกโหลดผ่าน JS -->
                </tbody>
            </table>
            
            <!-- Loading State -->
            <div id="loading" style="text-align:center; padding:40px; color:#64748b;">
                <i class="fas fa-spinner fa-spin fa-2x"></i><br>กำลังโหลดข้อมูล...
            </div>
            
            <!-- No Data State -->
            <div id="noData" style="text-align:center; padding:40px; display:none; color:#94a3b8;">
                <i class="far fa-folder-open fa-3x" style="margin-bottom:10px;"></i><br>ไม่พบข้อมูล
            </div>
        </div>
    </div>

    <!-- Modal: เพิ่ม/แก้ไขข้อมูล -->
    <div id="productModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header-custom">
                <div class="header-left">
                    <div class="upload-icon-bg"><i class="fas fa-pen"></i></div>
                    <div class="header-titles">
                        <h3 id="modalTitle">เพิ่มงานบริการใหม่</h3>
                        <p>จัดการรายละเอียดข้อมูลอุปกรณ์และสถานะ</p>
                    </div>
                </div>
                <button class="close-btn-custom" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>

            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="product_id" name="product_id" value="0">
                <input type="hidden" id="existing_file_path" name="existing_file_path">

                <div class="modal-body custom-scroll">
                    
                    <div class="form-group">
                        <label class="form-label">ลูกค้า <span style="color:red">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-user-tie"></i>
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

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">ชื่ออุปกรณ์</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-microchip"></i>
                                <input type="text" id="device_name" name="device_name" class="form-control-custom" required placeholder="ระบุชื่ออุปกรณ์">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">S/N (Serial Number)</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-barcode"></i>
                                <input type="text" id="serial_number" name="serial_number" class="form-control-custom" placeholder="ระบุ S/N">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">อาการเสีย / รายละเอียด</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-exclamation-triangle" style="top:20px;"></i>
                            <textarea id="repair_details" name="repair_details" class="form-control-custom" rows="3" placeholder="รายละเอียดอาการเสีย..."></textarea>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">สถานะ</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-info-circle"></i>
                                <select id="status" name="status" class="form-control-custom">
                                    <option value="1">รอสินค้าจากลูกค้า</option>
                                    <option value="2">ตรวจสอบ</option>
                                    <option value="3">รอสินค้าจาก Supplier</option>
                                    <option value="4">ส่งคืนลูกค้า</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">วันที่เริ่ม</label>
                            <div class="input-icon-wrapper">
                                <i class="far fa-calendar-alt"></i>
                                <input type="date" id="start_date" name="start_date" class="form-control-custom">
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">วันที่สิ้นสุด</label>
                            <div class="input-icon-wrapper">
                                <i class="far fa-calendar-check"></i>
                                <input type="date" id="end_date" name="end_date" class="form-control-custom">
                            </div>
                        </div>
                        <div class="form-group">
                            <!-- Placeholder for layout balance -->
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:20px;">
                        <label for="file_upload" class="upload-box-dashed">
                            <div class="upload-icon-bg"><i class="fas fa-cloud-upload-alt"></i></div>
                            <span style="font-weight:500; color:#64748b;">คลิกเพื่อแนบรูปภาพหรือไฟล์ PDF</span>
                            <span id="file-name-display" style="font-size:0.9rem;"></span>
                            <input type="file" id="file_upload" name="file_upload" accept=".jpg,.jpeg,.png,.pdf" style="display:none;" onchange="showFileName(this)">
                        </label>
                        <div id="existing_file_container" style="text-align:center; margin-top:10px;"></div>
                    </div>

                </div>
                <div class="modal-footer-custom">
                    <button type="submit" class="btn-save-custom"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: ดูรายละเอียด (View) -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header-custom">
                <div style="flex-grow:1;"></div>
                <button class="close-btn-custom" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body custom-scroll">
                
                <div class="view-header">
                    <div class="view-icon-large"><i class="fas fa-eye"></i></div>
                    <div>
                        <h2 id="view_customer" style="margin:0; font-size:1.4rem; color:#1e293b;">-</h2>
                        <p id="view_ticket_id" style="margin:5px 0 0; color:#64748b;">-</p>
                    </div>
                </div>

                <div class="info-card-grid">
                    <div class="info-card">
                        <div class="info-card-title">สถานะ</div>
                        <div class="info-card-value" id="view_status_text">-</div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-title">อุปกรณ์</div>
                        <div class="info-card-value" id="view_device_name">-</div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-title">S/N</div>
                        <div class="info-card-value" id="view_sn">-</div>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-title"><i class="fas fa-calendar-alt" style="color:#3b82f6;"></i> ระยะเวลาดำเนินการ</div>
                    <div style="display:flex; gap:20px; font-size:0.95rem;">
                        <div><strong style="color:#10b981;">เริ่ม:</strong> <span id="view_start_date">-</span></div>
                        <div><strong style="color:#ef4444;">สิ้นสุด:</strong> <span id="view_end_date">-</span></div>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-title"><i class="fas fa-exclamation-circle" style="color:#ef4444;"></i> อาการเสีย / รายละเอียด</div>
                    <div id="view_details" style="line-height:1.6; color:#334155;">-</div>
                </div>

                <div id="view_file_section" class="detail-section" style="display:none; text-align:center;">
                    <a id="btn_open_file" href="#" target="_blank" class="btn-pill-primary" style="display:inline-flex; text-decoration:none;">
                        <i class="fas fa-file-download"></i> ดูไฟล์แนบ
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="assets/js/product.js?v=<?php echo time(); ?>"></script>

</body>
</html>
