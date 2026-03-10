<?php
// หน้า Preventive Maintenance ของ user (ปรับ UI ให้เหมือน Admin)
session_start();
include_once 'auth.php'; 
require_once 'db.php';

// API Logic (เฉพาะส่วนที่ User ต้องใช้ เช่น ดึงข้อมูล View Detail)
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
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>MaintDash - PM Project</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- ใช้ CSS ตัวเดียวกับ Admin -->
    <link rel="stylesheet" href="CSS/pm_project.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ซ่อนปุ่ม Action ที่ User ไม่ควรเห็น */
        .col-action, .btn-pill-primary, .btn-action-group, .btn-edit, .btn-delete, .btn-add-ma {
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
                <div class="header-icon-circle"><i class="fas fa-project-diagram"></i></div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Preventive Maintenance</h2>
                    <p class="header-sub-desc">บริหารจัดการโครงการและแผนการบำรุงรักษา (PM/MA)</p>
                </div>
            </div>
            <!-- User ไม่มีปุ่มเพิ่ม/Export -->
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
                        <th class="col-action text-center" style="display:table-cell !important;">ดูข้อมูล</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>

        <!-- View Project Modal (Copy from Admin but read-only) -->
        <div id="viewProjectModal" class="modal-overlay">
            <div class="modal-box view-project-custom" style="max-width: 900px; width: 95%; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                <div class="modal-header-custom" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); color: white; padding: 25px; border: none;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: rgba(255,255,255,0.25); padding: 12px; border-radius: 12px; font-size: 1.6rem; backdrop-filter: blur(4px);">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div>
                            <h3 id="view_project_name" style="margin:0; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.025em;">ชื่อโครงการ</h3>
                            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 0.95rem; font-weight: 400;">
                                <i class="fas fa-hashtag"></i> <span id="view_number" style="font-weight: 600;">-</span> | ข้อมูลสรุปและสถานะปัจจุบัน
                            </p>
                        </div>
                    </div>
                    <button onclick="closeViewModal()" style="background: #ff4d4d; border: none; color: white; cursor: pointer; font-size: 1rem; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 20px; right: 20px; transition: 0.3s; box-shadow: 0 2px 10px rgba(240, 67, 67, 0.97);">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body custom-scroll" style="padding: 30px; background: #ffffff;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div style="background:#f8faff; padding: 18px; border-radius: 14px; border: 1px solid #e0e8ff; border-left: 5px solid #3b82f6;">
                            <label style="display:block; font-size: 0.75rem; color: #64748b; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">สถานะโครงการ</label>
                            <div id="view_status_container"></div>
                        </div>
                        <div style="background:#f8fffb; padding: 18px; border-radius: 14px; border: 1px solid #e0f5e9; border-left: 5px solid #10b981;">
                            <label style="display:block; font-size: 0.75rem; color: #64748b; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">ระยะเวลาสัญญา</label>
                            <span id="view_contract_period_display" style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">-</span>
                        </div>
                        <div style="background:#f9f8ff; padding: 18px; border-radius: 14px; border: 1px solid #eeeaff; border-left: 5px solid #6366f1;">
                            <label style="display:block; font-size: 0.75rem; color: #64748b; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">ผู้รับผิดชอบ</label>
                            <span id="view_responsible" style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">-</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.8fr 1fr; gap: 25px;">
                        <div style="display: flex; flex-direction: column; gap: 25px;">
                            <div style="background:white; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                                <h4 style="margin-top:0; border-bottom: 2px solid #f8faff; padding-bottom: 15px; color: #334155; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-info-circle" style="color:#3b82f6;"></i> รายละเอียด / ขอบเขตงาน
                                </h4>
                                <div id="view_going_ma" style="line-height: 1.7; color: #475569; font-size: 0.95rem; padding-top: 10px;">-</div>
                            </div>

                            <div style="background:white; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                                <h4 style="margin-top:0; border-bottom: 2px solid #f8faff; padding-bottom: 15px; color: #334155; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-history" style="color:#3b82f6;"></i> แผนบำรุงรักษา (MA Schedule)
                                </h4>
                                <div class="view-table-wrapper" style="overflow-x: auto; margin-top: 10px;">
                                    <table class="view-table" id="view_ma_table" style="width:100%; border-collapse: separate; border-spacing: 0 8px;">
                                        <thead>
                                            <tr style="text-align: left; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase;">
                                                <th style="padding: 12px; border-bottom: 1px solid #f1f5f9; text-align: center;">ครั้งที่</th>
                                                <th style="padding: 12px; border-bottom: 1px solid #f1f5f9;">วันที่กำหนด</th>
                                                <th style="padding: 12px; border-bottom: 1px solid #f1f5f9;">ผลการดำเนินงาน</th>
                                                <th style="padding: 12px; border-bottom: 1px solid #f1f5f9;">หมายเหตุ</th>
                                                <th style="padding: 12px; border-bottom: 1px solid #f1f5f9; text-align: center;">ไฟล์</th>
                                            </tr>
                                        </thead>
                                        <tbody id="view_ma_table_body" style="font-size: 0.9rem;"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div style="background: linear-gradient(to right bottom, #fffbeb, #fffde0); padding: 25px; border-radius: 16px; border: 1px solid #fef3c7;">
                                <h4 style="margin-top:0; color: #92400e; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                    <i class="fas fa-id-card"></i> ข้อมูลลูกค้า
                                </h4>
                                <p id="view_customer_name" style="margin: 10px 0 0 0; font-weight: 700; color: #1e293b; font-size: 1.1rem; line-height: 1.4;">-</p>
                            </div>

                            <div style="background:white; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                                <div style="margin-bottom: 20px;">
                                    <label style="display:block; font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase;">วันเริ่มรับประกัน</label>
                                    <p id="view_deliver_date" style="margin: 5px 0; font-weight: 700; color: #10b981; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                                        <i class="far fa-calendar-check" style="font-size: 1rem;"></i> -
                                    </p>
                                </div>
                                <div>
                                    <label style="display:block; font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase;">วันสิ้นสุดรับประกัน</label>
                                    <p id="view_end_date" style="margin: 5px 0; font-weight: 700; color: #ef4444; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                                        <i class="far fa-calendar-times" style="font-size: 1rem;"></i> -
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ใช้ JS ตัวเดียวกับ Admin แต่จะมี CSS ซ่อนปุ่ม Action ไว้ -->
    <script src="js/pm_project.js"></script>
</body>
</html>
