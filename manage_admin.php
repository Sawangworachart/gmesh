<?php
/**
 * ไฟล์: manage_admin.php
 * คำอธิบาย: ระบบจัดการผู้ใช้งานสำหรับ Super Admin (Edit User)
 * สามารถ เพิ่ม/ลบ/แก้ไข/รีเซ็ตรหัสผ่าน และกำหนดสิทธิ์การเข้าถึงได้
 */

session_start();
include_once 'auth.php';
require_once 'db.php';

/* 🔐 ตรวจสอบสิทธิ์: เฉพาะ Super Admin เท่านั้น */
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: dashboard.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --------------------------------------------------------------------------
//  API HANDLER (Backend Logic)
// --------------------------------------------------------------------------
if (isset($_GET['api']) && $_GET['api'] == 'true') {

    // Double check permission for API
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    header('Content-Type: application/json');

    try {
        // 1. ดึงข้อมูลผู้ใช้ทั้งหมด
        if ($action == 'fetch_all') {
            $result = $conn->query("SELECT id, username, role, status FROM user ORDER BY id DESC");
            $data = $result->fetch_all(MYSQLI_ASSOC);

            // คำนวณสถิติ
            $total = count($data);
            $active = array_sum(array_column($data, 'status'));

            echo json_encode([
                'success' => true,
                'data' => $data,
                'stats' => [
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $total - $active
                ]
            ]);
            exit;
        }

        // 2. ดึงข้อมูลรายคน (สำหรับแก้ไข)
        if ($action == 'fetch_single') {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT id, username, role, status FROM user WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();

            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        // 3. บันทึกข้อมูล (เพิ่ม/แก้ไข)
        if ($action == 'save') {
            $id = intval($_POST['id']);
            $username = $_POST['username'];
            $password = $_POST['password'];
            $role = $_POST['role'];
            $status = intval($_POST['status']);

            // เช็คชื่อซ้ำ
            $check = $conn->prepare("SELECT id FROM user WHERE username=? AND id!=?");
            $check->bind_param("si", $username, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("ชื่อผู้ใช้งานซ้ำ (Username already exists)");
            }

            if ($id == 0) {
                // Create
                if (strlen($password) < 6) throw new Exception("รหัสผ่านต้องอย่างน้อย 6 ตัว");
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO user (username,password,role,status) VALUES (?,?,?,?)");
                $stmt->bind_param("sssi", $username, $hash, $role, $status);
            } else {
                // Update
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE user SET username=?, password=?, role=?, status=? WHERE id=?");
                    $stmt->bind_param("sssii", $username, $hash, $role, $status, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE user SET username=?, role=?, status=? WHERE id=?");
                    $stmt->bind_param("ssii", $username, $role, $status, $id);
                }
            }

            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'บันทึกสำเร็จ']);
            exit;
        }

        // 4. รีเซ็ตรหัสผ่าน
        if ($action == 'reset_password') {
            $id = intval($_POST['id']);
            $password = $_POST['password'];

            if (strlen($password) < 6) throw new Exception("รหัสผ่านสั้นเกินไป");

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE user SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $id);
            $stmt->execute();

            echo json_encode(['success' => true]);
            exit;
        }

        // 5. ลบผู้ใช้งาน
        if ($action == 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM user WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            echo json_encode(['success' => true]);
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
    <title>Edit User (Super Admin) - MaintDash</title>

    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- External Libs -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/manage_admin.css">
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Header -->
        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-circle">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div class="header-text-group">
                    <h2>Edit User Management</h2>
                    <p>ระบบจัดการผู้ใช้งานและกำหนดสิทธิ์เข้าถึง (สำหรับ Super Admin)</p>
                </div>
            </div>

            <div class="header-right-action">
                <button class="btn-pill-primary" onclick="openModal('create')">
                    <i class="fas fa-plus"></i> เพิ่มผู้ใช้งาน
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-row">
            <div class="stat-card card-all active" onclick="filterByStatus('all', this)">
                <div class="stat-icon-box bg-light-purple"><i class="fas fa-users"></i></div>
                <div class="stat-info"><p>ทั้งหมด</p><h3 id="statTotal">0</h3></div>
            </div>
            <div class="stat-card card-active" onclick="filterByStatus('1', this)">
                <div class="stat-icon-box bg-light-green"><i class="fas fa-user-check"></i></div>
                <div class="stat-info"><p>ใช้งานอยู่</p><h3 id="statActive">0</h3></div>
            </div>
            <div class="stat-card card-inactive" onclick="filterByStatus('0', this)">
                <div class="stat-icon-box bg-light-red"><i class="fas fa-user-slash"></i></div>
                <div class="stat-info"><p>ระงับการใช้งาน</p><h3 id="statInactive">0</h3></div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="table-toolbar">
            <div class="search-container-custom">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อผู้ใช้ หรือสิทธิ์..." onkeyup="filterTable()">
            </div>
        </div>

        <!-- Table -->
        <div class="card-table">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th width="10%">ลำดับ</th>
                        <th width="25%">ชื่อผู้ใช้ (Username)</th>
                        <th width="10%">รหัสผ่าน</th>
                        <th width="15%">สิทธิ์ (Role)</th>
                        <th width="15%">สถานะ (Status)</th>
                        <th width="15%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Data loaded via JS -->
                </tbody>
            </table>
            <div id="noData" style="text-align:center; padding:40px; display:none; color:#94a3b8;">
                <i class="fas fa-folder-open fa-2x"></i><br>ไม่พบข้อมูล
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="userModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header-custom">
                <div class="header-left">
                    <div class="header-icon-box">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="header-titles">
                        <h3 id="modalTitle">เพิ่มผู้ใช้งานใหม่</h3>
                        <p class="header-subtitle">กำหนดชื่อผู้ใช้และสิทธิ์การเข้าถึง</p>
                    </div>
                </div>
                <button class="close-btn-custom" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="userId" value="0">
                    <input type="hidden" name="action" value="save">

                    <div class="section-header">
                        <i class="fas fa-id-card"></i> ข้อมูลบัญชี
                    </div>

                    <div class="form-group">
                        <label class="form-label">ชื่อผู้ใช้งาน (Username) <span style="color:red">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control-custom" name="username" id="username" required placeholder="เช่น admin_office">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">รหัสผ่าน (Password)</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control-custom" name="password" id="password" placeholder="ตั้งรหัสผ่านอย่างน้อย 6 ตัว" minlength="6">
                            <i class="fas fa-eye toggle-password" onclick="togglePassword()" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8;"></i>
                        </div>
                        <small style="color:#94a3b8; margin-top:5px; display:block;" id="passwordHint">
                            <i class="fas fa-info-circle"></i> เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน
                        </small>
                    </div>

                    <div class="section-header" style="margin-top:20px;">
                        <i class="fas fa-sliders-h"></i> สิทธิ์การใช้งานและสถานะ
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">สิทธิ์การใช้งาน (Role)</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-user-tag input-icon"></i>
                                <select class="form-control-custom" name="role" id="role">
                                    <option value="user">User (ผู้ใช้ทั่วไป)</option>
                                    <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                                    <option value="superadmin">Super Admin (สิทธิ์สูงสุด)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">สถานะ (Status)</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-toggle-on input-icon"></i>
                                <select class="form-control-custom" name="status" id="status">
                                    <option value="1">เปิดใช้งาน (Active)</option>
                                    <option value="0">ระงับใช้งาน (Inactive)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-custom">
                    <button type="submit" id="saveBtn" class="btn-save-custom">
                        <i class="fas fa-check-circle"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="assets/js/manage_admin.js"></script>

</body>
</html>
