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
    <title>MaintDash - Product Claim</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/product.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Product Claim</h2>
                <p class="page-subtitle">ระบบจัดการและติดตามสถานะงานซ่อม</p>
            </div>
            <div class="header-right-action">
                <button class="btn btn-success" onclick="exportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn btn-primary" onclick="openModal('create')"><i class="fas fa-plus"></i> เพิ่มข้อมูล</button>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card active" onclick="filterByStatus('all', this)">
                <div class="stat-icon-box bg-all"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info"><p>ทั้งหมด</p><h3 id="stat_all">0</h3></div>
            </div>
            <div class="stat-card" onclick="filterByStatus(1, this)">
                <div class="stat-icon-box bg-s1"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><p>รอสินค้า</p><h3 id="stat_s1">0</h3></div>
            </div>
            <div class="stat-card" onclick="filterByStatus(2, this)">
                <div class="stat-icon-box bg-s2"><i class="fas fa-search"></i></div>
                <div class="stat-info"><p>ตรวจสอบ</p><h3 id="stat_s2">0</h3></div>
            </div>
            <div class="stat-card" onclick="filterByStatus(3, this)">
                <div class="stat-icon-box bg-s3"><i class="fas fa-truck-loading"></i></div>
                <div class="stat-info"><p>รออะไหล่</p><h3 id="stat_s3">0</h3></div>
            </div>
            <div class="stat-card" onclick="filterByStatus(4, this)">
                <div class="stat-icon-box bg-s4"><i class="fas fa-check-double"></i></div>
                <div class="stat-info"><p>ส่งคืน</p><h3 id="stat_s4">0</h3></div>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อลูกค้า, อุปกรณ์ หรือ S/N..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>ลูกค้า / แผนก</th>
                            <th>อุปกรณ์ / S/N</th>
                            <th class="text-center">สถานะ</th>
                            <th>วันที่เริ่ม / สิ้นสุด</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="productModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="modalTitle" class="modal-title">เพิ่มข้อมูล</h3>
                <button class="btn-close" onclick="closeModal()"></button>
            </div>
            <form id="productForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="product_id" name="product_id" value="0">
                    <input type="hidden" id="existing_file_path" name="existing_file_path">
                    
                    <div class="form-group">
                        <label class="form-label">ลูกค้า <span class="text-danger">*</span></label>
                        <select id="customers_id" name="customers_id" class="form-control" required></select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">อุปกรณ์</label>
                                <input type="text" id="device_name" name="device_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">S/N</label>
                                <input type="text" id="serial_number" name="serial_number" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">อาการเสีย / สิ่งที่พบ</label>
                        <textarea id="repair_details" name="repair_details" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">สถานะ</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="1">รอสินค้าจากลูกค้า</option>
                                    <option value="2">ตรวจสอบ</option>
                                    <option value="3">รอสินค้าจากsupplier</option>
                                    <option value="4">ส่งคืนลูกค้า</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">หมายเหตุสถานะ</label>
                                <input type="text" id="status_remark" name="status_remark" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">วันที่เริ่ม</label>
                                <input type="date" id="start_date" name="start_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">วันที่สิ้นสุด</label>
                                <input type="date" id="end_date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">แนบไฟล์</label>
                        <input type="file" id="file_upload" name="file_upload" class="form-control">
                        <div id="existing_file_container" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">รายละเอียด Product Claim</h3>
                <button class="btn-close" onclick="closeModal()"></button>
            </div>
            <div class="modal-body" id="viewModalBody"></div>
        </div>
    </div>

    <script src="js/product.js"></script>
</body>
</html>