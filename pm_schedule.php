<?php
// pm_schedule.php

// นำเข้าการเชื่อมต่อฐานข้อมูล
require_once 'db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// เตรียมตัวแปรสำหรับแจ้งเตือน (ดึงจาก Query String)
$alert = [];
if (isset($_GET['alert_type']) && isset($_GET['alert_msg'])) {
    $alert = ['type' => $_GET['alert_type'], 'message' => $_GET['alert_msg']];
}

// ฟังก์ชันสำหรับการ Redirect พร้อมส่ง Alert
function redirect_with_alert($type, $message)
{
    // ใช้งาน URL Parameter แทน Session
    $url = "pm_schedule.php?alert_type=" . urlencode($type) . "&alert_msg=" . urlencode($message);
    header("Location: " . $url);
    exit();
}

/**
 * 1. Logic การบันทึก/แก้ไขข้อมูล (CRUD)
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_form'])) {
    global $conn;
    $action = $_POST['action'] ?? 'add';

    // รับค่าจากฟอร์ม (ใช้ contract_id แทน contract_no)
    $contract_id = (int)$_POST['contract_id']; // ต้องรับเป็น ID จาก Projects
    $department = trim($_POST['department']);
    $device = trim($_POST['device']); // device_equipment
    $tor_year = (int)$_POST['tor_year']; // tor_visits_per_year
    $visit_done = (int)$_POST['visit_count']; // visits_done
    $next_visit_raw = trim($_POST['next_visit']); // next_visit_date
    $alert_email = trim($_POST['alert_email']); // alert_email (เคยชื่อ pm_month)

    if ($action == 'add') {
        // NOTE: ต้องใช้ contract_id (FK), tor_visits_per_year, visits_done, next_visit_date, alert_email
        $stmt = $conn->prepare("INSERT INTO pm_schedules (contract_id, device_equipment, department, tor_visits_per_year, visits_done, next_visit_date, alert_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // i: integer (contract_id, tor_visits_per_year, visits_done), s: string (ที่เหลือ)
        $stmt->bind_param("isssiss", $contract_id, $device, $department, $tor_year, $visit_done, $next_visit_raw, $alert_email);

        if ($stmt->execute()) {
            redirect_with_alert('success', 'เพิ่มตาราง PM สำเร็จ');
        } else {
            redirect_with_alert('error', 'ข้อผิดพลาดในการเพิ่มข้อมูล: ' . $stmt->error);
        }
    } else { // action == 'edit'
        $edit_id = (int)$_POST['edit_id']; // schedule_id
        // NOTE: ต้องใช้ contract_id (FK), tor_visits_per_year, visits_done, next_visit_date, alert_email
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

// ลบข้อมูล
if (isset($_POST['delete_id'])) {
    global $conn;
    $delete_id = (int)$_POST['delete_id']; // schedule_id

    $stmt = $conn->prepare("DELETE FROM pm_schedules WHERE schedule_id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        redirect_with_alert('info', 'ลบข้อมูลเรียบร้อย');
    } else {
        redirect_with_alert('error', 'ข้อผิดพลาดในการลบข้อมูล: ' . $stmt->error);
    }
    $stmt->close();
}

/**
 * 2. Logic การดึงข้อมูล (Read) พร้อม JOIN
 */
global $conn;
$pm_schedules = [];
$projects = []; // สำหรับ Dropdown ใน Modal

// ดึงรายการ Project สำหรับใช้ใน Dropdown Modal
$sql_projects = "SELECT contract_id, contract_number, project_name, customer_id FROM Projects";
$result_projects = mysqli_query($conn, $sql_projects);
if (mysqli_num_rows($result_projects) > 0) {
    while ($row = mysqli_fetch_assoc($result_projects)) {
        $projects[] = $row;
    }
}


// ดึงข้อมูล PM Schedule พร้อม JOIN เพื่อเอาชื่อลูกค้าและเลขที่สัญญามาแสดง
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

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Alert Status Logic (ใกล้ถึงกำหนด: ภายใน 7 วัน และยังมีรอบเหลือ)
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
    <title>Admin: PM Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        /* CSS STYLES (คงเดิม) */
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap');

        :root {
            --sidebar-bg: #000a29;
            --sidebar-color: #f8f9fa;
            --highlight-color: #31507d;
            --active-color: #3498db;
            --main-bg: #f5f5f5;
            --text-color: #333;
            --danger: #e74c3c;
            --warning: #f1c40f;
            --success: #10b981;
            --white: #ffffff;
            --blue-header: #3b82f6;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background-color: var(--main-bg);
            margin: 0;
            display: flex;
            min-height: 100vh;
            color: var(--text-color);
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 280px;
            min-width: 280px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            transition: width 0.3s ease;
        }

        .sidebar-header {
            font-size: 2.5rem;
            font-weight: 700;
            padding: 0 20px 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 15px;
            color: var(--white);
        }

        .sidebar-nav {
            flex-grow: 1;
            padding-left: 0;
            list-style: none;
        }

        .sidebar-nav a {
            display: block;
            padding: 15px 30px;
            color: var(--sidebar-color);
            text-decoration: none;
            font-size: 1.1rem;
            transition: background-color 0.2s, color 0.2s;
        }

        .sidebar-nav a:hover {
            background-color: var(--highlight-color);
            color: var(--white);
        }

        .sidebar-nav .active a {
            background-color: var(--highlight-color);
            border-left: 5px solid var(--active-color);
            padding-left: 25px;
            color: var(--white);
            font-weight: 500;
        }

        .sidebar-footer {
            padding: 20px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            color: #8899aa;
        }

        .sidebar-footer i {
            margin-right: 10px;
            color: var(--active-color);
        }

        .sidebar-nav .active-parent>.toggle-btn {
            background-color: var(--highlight-color);
            border-left: 5px solid var(--active-color);
            padding-left: 25px;
            color: var(--white);
            font-weight: 500;
        }

        .submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            background-color: #1a2a4b;
            width: 100%;
            overflow: hidden;
            display: none;
        }

        .submenu li a {
            padding: 10px 30px 10px 50px;
            background-color: #1a2a4b;
            color: #d1d9e2;
            font-size: 1rem;
            border-left: 5px solid transparent;
        }

        .submenu .active a {
            background-color: var(--highlight-color);
            border-left: 5px solid var(--active-color);
            padding-left: 45px;
            color: var(--white);
            font-weight: 500;
        }


        /* --- MAIN CONTENT --- */
        .main-content {
            flex-grow: 1;
            padding: 40px;
            background-color: var(--white);
            box-shadow: 0 0 0 20px var(--main-bg) inset;
            overflow-y: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--blue-header);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* --- SEARCH & ACTIONS --- */
        .action-container {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .search-input {
            padding: 10px 15px 10px 35px;
            border: 1px solid #cbd5e1;
            border-radius: 30px;
            font-family: 'Sarabun';
            font-size: 0.95rem;
            width: 250px;
            outline: none;
            transition: all 0.2s;
            background-color: #f8fafc;
        }

        .search-input:focus {
            border-color: var(--blue-header);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* --- BUTTONS --- */
        .btn-add-main {
            background-color: #0f172a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-add-main:hover {
            background-color: #333;
        }

        /* --- TABLE STYLE --- */
        .table-wrapper {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background-color: #f8fafc;
            color: #64748b;
            padding: 15px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
        }

        thead th:last-child {
            border-right: none;
        }

        tbody td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: middle;
            text-align: center;
            font-size: 0.95rem;
            white-space: nowrap;
        }

        tbody td:last-child {
            border-right: none;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody td:nth-child(1),
        tbody td:nth-child(2) {
            text-align: left;
        }

        .contract-link {
            color: var(--blue-header);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .customer-name {
            font-weight: 700;
            display: block;
            font-size: 0.95rem;
            margin-bottom: 4px;
            color: #1e293b;
            white-space: normal;
        }

        .device-name {
            font-size: 0.85rem;
            color: #64748b;
            white-space: normal;
        }

        .visit-left {
            color: #10b981;
            font-weight: 700;
        }

        .alert-badge {
            background-color: #fee2e2;
            color: #ef4444;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 5px;
        }

        /* Actions */
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
            margin: 0 4px;
        }

        .btn-edit {
            color: var(--warning);
            border-color: #fcd34d;
        }

        .btn-delete {
            color: var(--danger);
            border-color: #fca5a5;
        }

        /* --- MODAL STYLE --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 900px;
            position: relative;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #aaa;
        }

        .form-header {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .form-row-4 {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1fr 1.5fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1.5fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-full {
            width: 100%;
            margin-bottom: 20px;
        }

        .modal label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            color: #475569;
        }

        .modal label span {
            color: red;
        }

        .modal input,
        .modal select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: 'Sarabun';
            font-size: 0.95rem;
        }

        .modal input:focus,
        .modal select:focus {
            outline: none;
            border-color: var(--active-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-submit {
            background-color: var(--success);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 10px 30px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            float: right;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background-color: #059669;
        }

        .customer-name-dropdown {
            font-size: 0.85rem;
            color: #64748b;
            display: block;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-clock"></i> PM Schedule & Alert</h1>

            <div class="action-container">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="ค้นหาตามสัญญา, ลูกค้า..." onkeyup="searchTable()">
                </div>

                <button class="btn-add-main" onclick="openModal('schedule-modal')">
                    <i class="fas fa-plus"></i> เพิ่มตาราง PM
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th width="15%">สัญญา</th>
                        <th width="30%">ลูกค้า/อุปกรณ์</th>
                        <th width="8%">TOR (ครั้ง/ปี)</th>
                        <th width="8%">เข้าแล้ว</th>
                        <th width="8%">เหลือ</th>
                        <th width="15%">กำหนดครั้งถัดไป</th>
                        <th width="16%">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="scheduleTableBody">
                    <?php if (empty($pm_schedules)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">ไม่พบข้อมูลตาราง PM</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pm_schedules as $row): ?>
                            <tr>
                                <td>
                                    <a href="#" class="contract-link"><?= htmlspecialchars($row['contract_number']) ?></a>
                                </td>
                                <td>
                                    <span class="customer-name"><?= htmlspecialchars($row['customer_name']) ?> (<?= htmlspecialchars($row['department']) ?>)</span>
                                    <span class="device-name"><?= htmlspecialchars($row['device']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['tor_year']) ?></td>
                                <td><?= htmlspecialchars($row['visit_done']) ?></td>
                                <td class="visit-left"><?= htmlspecialchars($row['visit_left']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['next_visit']) ?></strong>
                                    <?php if (isset($row['alert_status']) && $row['alert_status'] == 'near'): ?>
                                        <br><span class="alert-badge">ใกล้ถึงกำหนด!</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-icon btn-edit" onclick='editSchedule(<?= json_encode($row) ?>)'><i class="fas fa-pencil-alt"></i></div>
                                    <form method="POST" onsubmit="return confirmDelete(event, this)" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <button type="submit" class="btn-icon btn-delete"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="schedule-modal" class="modal" onclick="closeModalOnOverlay(event)">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('schedule-modal')">&times;</span>
            <div class="form-header"><i class="fas fa-list-ul"></i> <span id="modal-title">กำหนดตารางการเข้าบำรุงรักษา</span></div>

            <form method="POST" id="scheduleForm">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="edit_id" id="edit_id" value="">
                <input type="hidden" name="submit_form" value="1">
                <div class="form-row-4">
                    <div style="grid-column: span 2;">
                        <label>โครงการ/สัญญา <span>*</span></label>
                        <select name="contract_id" id="contract_id" required>
                            <option value="" disabled selected>--- เลือกโครงการ/สัญญา ---</option>
                            <?php
                            // ใช้ $projects ที่ดึงมาเพื่อแสดงใน Dropdown
                            foreach ($projects as $project):
                            ?>
                                <option value="<?= htmlspecialchars($project['contract_id']) ?>">
                                    <?= htmlspecialchars($project['contract_number']) ?> - <?= htmlspecialchars($project['project_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>หน่วยงาน/แผนก</label>
                        <input type="text" name="department" id="department">
                    </div>
                    <div>
                        <label>อุปกรณ์</label>
                        <input type="text" name="device" id="device">
                    </div>
                </div>

                <div class="form-row-3">
                    <div>
                        <label>TOR (ครั้ง/ปี) <span>*</span></label>
                        <input type="number" name="tor_year" id="tor_year" required min="1">
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

                <div class="form-full">
                    <label>อีเมลแจ้งเตือน (คั่นด้วย comma)</label>
                    <input type="text" name="alert_email" id="alert_email" placeholder="สามารถใส่ได้หลายอีเมลคั่นด้วยเครื่องหมาย , (comma)">
                </div>

                <div style="overflow: hidden; margin-top: 15px;">
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> <span id="submit-text">บันทึกข้อมูล</span></button>
                </div>
            </form>
        </div>
    </div>

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
            // ลบ Query String ออกจาก URL เพื่อป้องกันการแสดงซ้ำเมื่อ Refresh
            history.replaceState(null, '', 'pm_schedule.php');
        </script>
    <?php endif; ?>

    <script>
        // --- Modal Logic ---
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function closeModalOnOverlay(event) {
            if (event.target.classList.contains('modal')) {
                closeModal('schedule-modal');
            }
        }

        // --- Edit Logic ---
        function editSchedule(data) {
            document.getElementById('scheduleForm').reset();

            document.getElementById('form-action').value = 'edit';
            document.getElementById('edit_id').value = data.id; // schedule_id
            document.getElementById('modal-title').textContent = 'แก้ไขตารางการเข้าบำรุงรักษา: ' + data.contract_number;
            document.getElementById('submit-text').textContent = 'บันทึกการแก้ไข';

            // Fill Fields
            // contract_id คือ FK ที่ต้องใช้ในการอัปเดต
            document.getElementById('contract_id').value = data.contract_id;
            document.getElementById('department').value = data.department || '';
            document.getElementById('device').value = data.device || '';
            document.getElementById('tor_year').value = data.tor_year;
            document.getElementById('visit_count').value = data.visit_done;
            document.getElementById('alert_email').value = data.alert_email || ''; // เปลี่ยนชื่อคอลัมน์

            // next_visit_raw คือ YYYY-MM-DD ที่ได้จาก DB
            document.getElementById('next_visit').value = data.next_visit_raw;

            openModal('schedule-modal');
        }

        // Initialize for Add mode
        document.querySelector('.btn-add-main').addEventListener('click', function() {
            document.getElementById('scheduleForm').reset();
            document.getElementById('form-action').value = 'add';
            document.getElementById('edit_id').value = '';
            document.getElementById('modal-title').textContent = 'กำหนดตารางการเข้าบำรุงรักษา';
            document.getElementById('submit-text').textContent = 'บันทึกข้อมูล';
        });

        // --- Delete Confirmation ---
        function confirmDelete(event, form) {
            event.preventDefault();
            Swal.fire({
                title: 'ยืนยันการลบ',
                text: 'คุณต้องการลบข้อมูลนี้หรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        }

        // --- Real-time Search Function ---
        function searchTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("scheduleTableBody");
            const tr = table.getElementsByTagName("tr");

            for (let i = 0; i < tr.length; i++) {
                // ค้นหาจากคอลัมน์: สัญญา (0), ลูกค้า/อุปกรณ์ (1)
                let tdContract = tr[i].getElementsByTagName("td")[0];
                let tdCustomer = tr[i].getElementsByTagName("td")[1];

                if (tdContract || tdCustomer) {
                    let txtContract = tdContract.textContent || tdContract.innerText;
                    let txtCustomer = tdCustomer.textContent || tdCustomer.innerText;

                    if (txtContract.toUpperCase().indexOf(filter) > -1 || txtCustomer.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>

    <script>
        $(document).ready(function() {
            // Your custom JQuery code goes here
        });
    </script>
</body>

</html>