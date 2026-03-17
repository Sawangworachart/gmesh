<?php
// หน้า Edit user ของ superadmin
session_start();
include_once 'auth.php';
require_once 'db.php';

/* 🔐 ป้องกันสิทธิ์ */
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: dashboard.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (isset($_GET['api']) && $_GET['api'] == 'true') {

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    header('Content-Type: application/json');

    try {

        if ($action == 'fetch_all') {
            $result = $conn->query("SELECT id, username, role, status FROM user ORDER BY id DESC");
            $data = $result->fetch_all(MYSQLI_ASSOC);

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

        if ($action == 'fetch_single') {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT id, username, role, status FROM user WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();

            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        if ($action == 'save') {
            $id = intval($_POST['id']);
            $username = $_POST['username'];
            $password = $_POST['password'];
            $role = $_POST['role'];
            $status = intval($_POST['status']);

            $check = $conn->prepare("SELECT id FROM user WHERE username=? AND id!=?");
            $check->bind_param("si", $username, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("ชื่อผู้ใช้งานซ้ำ");
            }

            if ($id == 0) {
                if (strlen($password) < 6) throw new Exception("รหัสผ่านต้องอย่างน้อย 6 ตัว");

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO user (username,password,role,status) VALUES (?,?,?,?)");
                $stmt->bind_param("sssi", $username, $hash, $role, $status);
            } else {
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
    <title>User Management - G-Mesh</title>
    <link rel="icon" type="image/png" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/user_management.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1 class="page-title">Superadmin User Management</h1>
            <p class="page-description">จัดการข้อมูลผู้ใช้งานและกำหนดสิทธิ์ระดับสูง</p>
            <div class="page-actions">
                <button class="btn btn-primary" onclick="openModal('create')">
                    <i class="fas fa-plus"></i> เพิ่มผู้ใช้งาน
                </button>
            </div>
        </header>

        <div class="stat-cards-3-cols">
            <div class="stat-card-item active" onclick="filterByStatus('all', this)">
                <div class="icon-box bg-total"><i class="fas fa-users"></i></div>
                <div class="info">
                    <p>ผู้ใช้ทั้งหมด</p>
                    <h3 id="statTotal">0</h3>
                </div>
            </div>
            <div class="stat-card-item" onclick="filterByStatus('1', this)">
                <div class="icon-box bg-active"><i class="fas fa-user-check"></i></div>
                <div class="info">
                    <p>ใช้งานอยู่</p>
                    <h3 id="statActive">0</h3>
                </div>
            </div>
            <div class="stat-card-item" onclick="filterByStatus('0', this)">
                <div class="icon-box bg-inactive"><i class="fas fa-user-slash"></i></div>
                <div class="info">
                    <p>ถูกระงับ</p>
                    <h3 id="statInactive">0</h3>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>รายชื่อผู้ใช้งานในระบบ</h2>
                <div class="card-toolbar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="ค้นหา...">
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="table" id="userTable">
                    <thead>
                        <tr>
                            <th>ผู้ใช้งาน</th>
                            <th class="text-center">สิทธิ์</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="userModal">
        <div class="modal-content">
            <form id="userForm">
                <div class="modal-header">
                    <h3 class="modal-title" id="modalTitle">เพิ่มผู้ใช้งานใหม่</h3>
                    <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="userId" value="0">
                    <input type="hidden" name="action" value="save">

                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้งาน (Username) <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" required placeholder="e.g., admin_gmesh">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">รหัสผ่าน (Password)</label>
                        <div class="input-wrapper password-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="กรอกเพื่อตั้งค่าหรือเปลี่ยนรหัสผ่าน">
                            <i class="fas fa-eye toggle-password"></i>
                        </div>
                        <small class="form-hint" id="passwordHint">เว้นว่างไว้หากไม่ต้องการเปลี่ยน</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">สิทธิ์การใช้งาน (Role)</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user-shield"></i>
                                <select id="role" name="role">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="superadmin">Super Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="status">สถานะ (Status)</label>
                            <div class="input-wrapper">
                                <i class="fas fa-toggle-on"></i>
                                <select id="status" name="status">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/manage_admin.js"></script>
</body>
</html>