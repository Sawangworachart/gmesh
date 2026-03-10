<?php
// หน้า product ของ user (ปรับ UI ให้เหมือน Admin)
session_start();
include_once 'auth.php'; 
require_once 'db.php';

// --- API HANDLER (เฉพาะ Fetch) ---
if (isset($_GET['api']) && $_GET['api'] == 'true') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    try {
        if ($action == 'get_stats') {
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
    <title>MaintDash - Products</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- ใช้ CSS ตัวเดียวกับ Admin -->
    <link rel="stylesheet" href="CSS/product.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ซ่อนปุ่ม Action ที่ User ไม่ควรเห็น */
        .btn-pill-primary, .btn-action-group, .btn-edit, .btn-delete {
            display: none !important;
        }
        /* แต่ปุ่ม View ต้องเห็น */
        .btn-view { display: inline-block !important; }
        
        /* ปรับ Header */
        .header-right-action { display: none !important; }
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-circle"><i class="fas fa-boxes"></i></div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Product Claim</h2>
                    <p class="header-sub-desc">ระบบจัดการและติดตามสถานะงานซ่อม ตั้งแต่รับอุปกรณ์จนถึงส่งคืนลูกค้า</p>
                </div>
            </div>
            <!-- User ไม่มีปุ่มเพิ่ม/Export -->
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
                <thead>
                    <tr>
                        <th style="width: 50px; text-align:center;">ลำดับ</th>
                        <th style="width: 20%;">ลูกค้า / แผนก</th>
                        <th style="width: 15%;">อุปกรณ์</th>
                        <th style="width: 15%;">S/N</th>
                        <th style="width: 10%; text-align:center;">สถานะ</th>
                        <th style="width: 20%;">วันที่เริ่ม / วันที่สิ้นสุด</th>
                        <th style="width: 10%; text-align:center;">ดูข้อมูล</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
            <div id="loading" style="text-align:center; padding:30px; display:none;"><i class="fas fa-spinner fa-spin text-primary"></i> กำลังโหลด...</div>
            <div id="noData" style="text-align:center; padding:30px; display:none; color:#999;">ไม่พบข้อมูล</div>
        </div>
    </div>

    <!-- View Modal (Read-only) -->
    <div id="productModal" class="modal-overlay">
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
    
    <!-- ใช้ JS ตัวเดียวกับ Admin -->
    <script src="js/product.js?v=<?php echo time(); ?>"></script>
</body>
</html>
