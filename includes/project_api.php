<?php
// project_api.php
header('Content-Type: application/json');
include __DIR__ . '/db.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$action = $_REQUEST['action'] ?? '';

function response($success, $msg, $data = null) {
    echo json_encode(['success' => $success, 'message' => $msg, 'data' => $data]);
    exit();
}

switch ($action) {
    case 'create':
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $cid = $_POST['customer_id'] ?? 1; // Default ลูกค้า ID 1
        $mgr = $_POST['manager'] ?? 'ไม่ระบุ';
        $due = $_POST['due'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'Pending';
        $progress = $_POST['progress'] ?? 0;

        // ตรวจสอบข้อมูลเบื้องต้น
        if(empty($name) || empty($due)) {
            response(false, 'กรุณากรอกข้อมูลที่จำเป็น (ชื่อโปรเจ็ค, วันกำหนดส่ง)');
        }

        $stmt = $conn->prepare("INSERT INTO Projects (contract_id, project_name, customer_id, project_manager, due_date, status, progress) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisssi", $id, $name, $cid, $mgr, $due, $status, $progress);
        
        if ($stmt->execute()) {
            response(true, 'เพิ่มโปรเจ็คสำเร็จ');
        } else {
            response(false, 'Error: ' . $conn->error);
        }
        break;

    case 'update':
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $cid = $_POST['customer_id'] ?? 1;
        $mgr = $_POST['manager'] ?? '';
        $due = $_POST['due'] ?? '';
        $status = $_POST['status'] ?? '';
        $progress = $_POST['progress'] ?? 0;

        $stmt = $conn->prepare("UPDATE Projects SET project_name=?, customer_id=?, project_manager=?, due_date=?, status=?, progress=? WHERE contract_id=?");
        $stmt->bind_param("sisssis", $name, $cid, $mgr, $due, $status, $progress, $id);
        
        if ($stmt->execute()) {
            response(true, 'อัปเดตข้อมูลสำเร็จ');
        } else {
            response(false, 'Error: ' . $conn->error);
        }
        break;

    case 'delete':
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM Projects WHERE contract_id=?");
        $stmt->bind_param("s", $id);
        
        if ($stmt->execute()) {
            response(true, 'ลบข้อมูลสำเร็จ');
        } else {
            response(false, 'Error: ' . $conn->error);
        }
        break;

    case 'get_one':
        $id = $_GET['id'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM Projects WHERE contract_id=?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            response(true, 'Found', $row);
        } else {
            response(false, 'Not found');
        }
        break;

    case 'get_details':
        $id = $_GET['id'] ?? '';
        // Join เพื่อเอาชื่อลูกค้ามาแสดง
        $sql = "SELECT p.*, c.customer_name FROM Projects p 
                LEFT JOIN customers c ON p.customer_id = c.customer_id 
                WHERE p.contract_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            // ป้องกันค่า Null
            $row['customer_name'] = $row['customer_name'] ?? 'ไม่ระบุ';
            response(true, 'Found', $row);
        } else {
            response(false, 'Not found');
        }
        break;

    default:
        response(false, 'Invalid action');
}
?>