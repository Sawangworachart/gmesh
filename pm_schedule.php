<?php
// =========================================
// หน้าจัดการตาราง PM (Admin) - PM Schedule Management
// =========================================

require_once 'db.php'; // เชื่อมต่อฐานข้อมูล

// ตั้งค่าการแสดงผล Error (ควรปิดเมื่อใช้งานจริง)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// เตรียมตัวแปรสำหรับแจ้งเตือน (รับค่าจาก Query String)
$alert = [];
if (isset($_GET['alert_type']) && isset($_GET['alert_msg'])) {
    $alert = ['type' => $_GET['alert_type'], 'message' => $_GET['alert_msg']];
}

// ฟังก์ชัน Redirect พร้อมส่งแจ้งเตือน
function redirect_with_alert($type, $message)
{
    $url = "pm_schedule.php?alert_type=" . urlencode($type) . "&alert_msg=" . urlencode($message);
    header("Location: " . $url);
    exit();
}

// --------------------------------------------------------------------------
//  1. จัดการการบันทึก/แก้ไขข้อมูล (CRUD Operations)
// --------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_form'])) {
    global $conn;
    $action = $_POST['action'] ?? 'add';

    // รับค่าจากฟอร์ม
    $contract_id = (int)$_POST['contract_id'];
    $department = trim($_POST['department']);
    $device = trim($_POST['device']); 
    $tor_year = (int)$_POST['tor_year']; 
    $visit_done = (int)$_POST['visit_count']; 
    $next_visit_raw = trim($_POST['next_visit']); 
    $alert_email = trim($_POST['alert_email']); 

    if ($action == 'add') {
        // เพิ่มข้อมูลใหม่ (Insert)
        $stmt = $conn->prepare("INSERT INTO pm_schedules (contract_id, device_equipment, department, tor_visits_per_year, visits_done, next_visit_date, alert_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiss", $contract_id, $device, $department, $tor_year, $visit_done, $next_visit_raw, $alert_email);

        if ($stmt->execute()) {
            redirect_with_alert('success', 'เพิ่มตาราง PM สำเร็จ');
        } else {
            redirect_with_alert('error', 'ข้อผิดพลาดในการเพิ่มข้อมูล: ' . $stmt->error);
        }
    } else { 
        // แก้ไขข้อมูลเดิม (Update)
        $edit_id = (int)$_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE pm_schedules SET contract_id=?, device_equipment=?, department=?, tor_visits_per_year=?, visits_done=?, next_visit_date=?, alert_email=? WHERE schedule_id=?");
        $stmt->bind_param("isssissi", $contract_id, $device, $department, $tor_year, $visit_done, $next_visit_raw, $alert_email, $edit_id);

        if ($stmt->execute()) {
            redirect_with_alert('success', 'แก้ไขข้อมูลเรียบร้อย');
        } else {
            redirect_with_alert('error', 'ข้อผิดพลาดในการแก้ไขข้อมูล: ' . $stmt->error);
        }
    }
    $stmt->close();
}

// ลบข้อมูล (Delete)
if (isset($_POST['delete_id'])) {
    global $conn;
    $delete_id = (int)$_POST['delete_id']; 

    $stmt = $conn->prepare("DELETE FROM pm_schedules WHERE schedule_id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        redirect_with_alert('success', 'ลบข้อมูลเรียบร้อย'); // เปลี่ยนเป็น success ให้ไอคอนสีเขียวสวยกว่า
    } else {
        redirect_with_alert('error', 'ข้อผิดพลาดในการลบข้อมูล: ' . $stmt->error);
    }
    $stmt->close();
}

// --------------------------------------------------------------------------
//  2. ดึงข้อมูล (Read Data)
// --------------------------------------------------------------------------
global $conn;
$pm_schedules = [];
$projects = [];

// ดึงรายการ Project สำหรับ Dropdown ใน Modal
$sql_projects = "SELECT contract_id, contract_number, project_name, customer_id FROM Projects ORDER BY contract_number DESC";
$result_projects = mysqli_query($conn, $sql_projects);
if ($result_projects && mysqli_num_rows($result_projects) > 0) {
    while ($row = mysqli_fetch_assoc($result_projects)) {
        $projects[] = $row;
    }
}

// ดึงข้อมูล PM Schedule ทั้งหมด พร้อม JOIN ตารางที่เกี่ยวข้อง
$sql = "
    SELECT 
        ps.schedule_id AS id, 
        p.contract_id,
        p.contract_number, 
        c.customer_name, 
        ps.department, 
        ps.device_equipment AS device, 
        ps.tor_visits_per_year AS tor_year, 
        ps.visits_done AS visit_done, 
        (ps.tor_visits_per_year - ps.visits_done) AS visit_left, 
        DATE_FORMAT(ps.next_visit_date, '%d/%m/%Y') AS next_visit, 
        ps.next_visit_date AS next_visit_raw, 
        ps.alert_email
    FROM 
        pm_schedules ps
    JOIN 
        Projects p ON ps.contract_id = p.contract_id
    JOIN 
        customers c ON p.customer_id = c.customer_id
    ORDER BY 
        ps.next_visit_date ASC";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // คำนวณสถานะแจ้งเตือน (ถ้าเหลือน้อยกว่า 7 วัน และยังมีรอบเหลือ)
        $next_visit_timestamp = strtotime($row['next_visit_raw']);
        $alert_threshold = strtotime('+7 days');

        if ($row['visit_left'] > 0 && $next_visit_timestamp <= $alert_threshold) {
            $row['alert_status'] = 'near';
        } else {
            $row['alert_status'] = '';
        }

        $pm_schedules[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MaintDash - PM Schedule</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/pm_schedule.css?v=<?php echo time(); ?>">
    
    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    
    <?php include 'sidebar.php'; ?> <!-- เมนูด้านข้าง -->

    <div class="main-content">
        
        <!-- ส่วนหัว (Header) -->
        <div class="page-header">
            <div class="header-title">
                <div class="header-icon"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <h1 style="margin:0; font-size:1.8rem;">PM Schedule</h1>
                    <p style="margin:4px 0 0; font-size:0.95rem; color:#64748b; font-weight:400;">จัดการตารางการเข้าบำรุงรักษาตามสัญญา</p>
                </div>
            </div>

            <div class="action-container">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="ค้นหาตามสัญญา, ลูกค้า..." onkeyup="searchTable()">
                </div>

                <button class="btn-add-main" onclick="initAddMode()">
                    <i class="fas fa-plus"></i> เพิ่มตาราง PM
                </button>
            </div>
        </div>

        <!-- ตารางข้อมูล (Table) -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th width="15%">สัญญา</th>
                        <th width="30%">ลูกค้า / อุปกรณ์</th>
                        <th width="8%" class="text-center">TOR (ปี)</th>
                        <th width="8%" class="text-center">เข้าแล้ว</th>
                        <th width="8%" class="text-center">เหลือ</th>
                        <th width="15%">กำหนดครั้งถัดไป</th>
                        <th width="16%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="scheduleTableBody">
                    <?php if (empty($pm_schedules)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <i class="far fa-calendar-times fa-3x" style="margin-bottom:10px;"></i><br>ไม่พบข้อมูลตาราง PM
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pm_schedules as $row): ?>
                            <tr>
                                <td>
                                    <a href="#" class="contract-link"><?= htmlspecialchars($row['contract_number']) ?></a>
                                </td>
                                <td>
                                    <span class="customer-name"><?= htmlspecialchars($row['customer_name']) ?></span>
                                    <?php if (!empty($row['department'])): ?>
                                        <span style="font-size:0.85rem; color:#64748b;">(<?= htmlspecialchars($row['department']) ?>)</span>
                                    <?php endif; ?>
                                    <span class="device-name"><i class="fas fa-microchip"></i> <?= htmlspecialchars($row['device']) ?></span>
                                </td>
                                <td class="text-center"><?= htmlspecialchars($row['tor_year']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['visit_done']) ?></td>
                                <td class="text-center"><span class="visit-left"><?= htmlspecialchars($row['visit_left']) ?></span></td>
                                <td>
                                    <div style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($row['next_visit']) ?></div>
                                    <?php if (isset($row['alert_status']) && $row['alert_status'] == 'near'): ?>
                                        <span class="alert-badge"><i class="fas fa-exclamation-circle"></i> ใกล้ถึงกำหนด!</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="action-btns" style="justify-content:center;">
                                        <button class="btn-icon btn-edit" onclick='editSchedule(<?= json_encode($row) ?>)' title="แก้ไข">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirmDelete(event, this)" style="display:inline;">
                                            <input type="hidden" name="delete_id" value="<?= htmlspecialchars($row['id']) ?>">
                                            <button type="submit" class="btn-icon btn-delete" title="ลบ">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: เพิ่ม/แก้ไขข้อมูล -->
    <div id="schedule-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="form-header-title">
                    <i class="fas fa-calendar-plus"></i> <span id="modal-title">กำหนดตารางการเข้าบำรุงรักษา</span>
                </div>
                <button class="close-btn" onclick="closeModal('schedule-modal')">&times;</button>
            </div>

            <div class="modal-body">
                <form method="POST" id="scheduleForm">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="edit_id" id="edit_id" value="">
                    <input type="hidden" name="submit_form" value="1">
                    
                    <div class="form-row cols-4">
                        <div style="grid-column: span 2;">
                            <label>โครงการ/สัญญา <span>*</span></label>
                            <select name="contract_id" id="contract_id" required>
                                <option value="" disabled selected>--- เลือกโครงการ/สัญญา ---</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= htmlspecialchars($project['contract_id']) ?>">
                                        <?= htmlspecialchars($project['contract_number']) ?> - <?= htmlspecialchars($project['project_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>หน่วยงาน/แผนก</label>
                            <input type="text" name="department" id="department" placeholder="เช่น แผนก IT">
                        </div>
                        <div>
                            <label>อุปกรณ์</label>
                            <input type="text" name="device" id="device" placeholder="เช่น Server Dell R740">
                        </div>
                    </div>

                    <div class="form-row cols-3">
                        <div>
                            <label>TOR (ครั้ง/ปี) <span>*</span></label>
                            <input type="number" name="tor_year" id="tor_year" required min="1" placeholder="จำนวนครั้ง">
                        </div>
                        <div>
                            <label>เข้าแล้ว (ครั้ง)</label>
                            <input type="number" name="visit_count" id="visit_count" value="0" min="0">
                        </div>
                        <div>
                            <label>กำหนดเข้าครั้งถัดไป <span>*</span></label>
                            <input type="date" name="next_visit" id="next_visit" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>อีเมลแจ้งเตือน (คั่นด้วย comma)</label>
                        <input type="text" name="alert_email" id="alert_email" placeholder="เช่น admin@example.com, manager@example.com">
                    </div>

                    <div style="margin-top: 25px;">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> <span id="submit-text">บันทึกข้อมูล</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Alert Handling -->
    <?php if (!empty($alert)): ?>
        <script>
            Swal.fire({
                icon: '<?= $alert['type'] ?>',
                title: '<?= $alert['message'] ?>',
                showConfirmButton: false,
                timer: 1500,
                toast: true,
                position: 'top-end'
            });
            // ลบ Query String เพื่อป้องกัน Alert ซ้ำ
            history.replaceState(null, '', 'pm_schedule.php');
        </script>
    <?php endif; ?>

    <!-- Custom JS -->
    <script src="assets/js/pm_schedule.js?v=<?php echo time(); ?>"></script>

</body>
</html>
