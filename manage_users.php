<?php
/**
 * ไฟล์: manage_users.php
 * คำอธิบาย: ระบบจัดการผู้ใช้งาน (User Management)
 * สามารถ เพิ่ม/ลบ/แก้ไข/กำหนดสิทธิ์ ผู้ใช้งานได้
 */

session_start();
include_once 'includes/auth.php'; 
require_once 'includes/db.php';

// --------------------------------------------------------------------------
//  API HANDLER (Backend Logic)
// --------------------------------------------------------------------------
if (isset($_GET['api']) && $_GET['api'] == 'true') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    try {
        // 1. อ่านข้อมูลผู้ใช้ทั้งหมด
        if ($action == 'fetch_all') {
            $sql = "SELECT id, username, role, status FROM user ORDER BY id DESC";
            $result = mysqli_query($conn, $sql);
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            
            // คำนวณสถิติ
            $total = count($data);
            $active = 0;
            foreach($data as $d) { if($d['status'] == 1) $active++; }
            
            echo json_encode([
                'success' => true, 
                'data' => $data,
                'stats' => ['total' => $total, 'active' => $active, 'inactive' => $total - $active]
            ]);
            exit;
        }

        // 2. อ่านข้อมูลรายคน (สำหรับแก้ไข)
        if ($action == 'fetch_single') {
            $id = intval($_GET['id']);
            $sql = "SELECT id, username, role, status FROM user WHERE id = $id";
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);
            echo json_encode(['success' => true, 'data' => $row]);
            exit;
        }

        // 3. บันทึกข้อมูล (เพิ่ม/แก้ไข)
        if ($action == 'save') {
            $id = intval($_POST['id']);
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $password = $_POST['password']; 
            $role = mysqli_real_escape_string($conn, $_POST['role']); // รับค่า Role
            $status = intval($_POST['status']);

            // ตรวจสอบ Username ซ้ำ (ยกเว้นตัวเอง)
            $checkSql = "SELECT id FROM user WHERE username = '$username' AND id != $id";
            $checkQuery = mysqli_query($conn, $checkSql);
            if (mysqli_num_rows($checkQuery) > 0) {
                throw new Exception("ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว");
            }

            if ($id == 0) {
                // --- กรณีสร้างใหม่ (Create) ---
                if (empty($password)) { throw new Exception("กรุณากำหนดรหัสผ่าน"); }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO user (username, password, role, status) VALUES ('$username', '$hashed_password', '$role', '$status')";
                $msg = "เพิ่มผู้ใช้งานสำเร็จ";
            } else {
                // --- กรณีแก้ไข (Update) ---
                if (!empty($password)) {
                    // ถ้ามีการกรอกรหัสผ่านใหม่ ให้เปลี่ยนรหัสผ่านด้วย
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE user SET username='$username', password='$hashed_password', role='$role', status='$status' WHERE id=$id";
                } else {
                    // ถ้าไม่เปลี่ยนรหัสผ่าน
                    $sql = "UPDATE user SET username='$username', role='$role', status='$status' WHERE id=$id";
                }
                $msg = "แก้ไขข้อมูลสำเร็จ";
            }

            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true, 'message' => $msg]);
            } else {
                throw new Exception(mysqli_error($conn));
            }
            exit;
        }

        // 4. ลบข้อมูล
        if ($action == 'delete') {
            $id = intval($_POST['id']);
            $sql = "DELETE FROM user WHERE id = $id";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true, 'message' => 'ลบผู้ใช้งานเรียบร้อยแล้ว']);
            } else {
                throw new Exception(mysqli_error($conn));
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
    <title>User Management - MaintDash</title>
    
    <!-- External Libs -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/manage_users.css">
</head>
<body>

    <!-- Sidebar -->
    <?php include 'includes/sidebar_user.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="page-header">
            <div class="page-title">
                <h2><i class="fas fa-user-cog"></i> User Management</h2>
                <span>จัดการข้อมูลผู้ใช้งานและกำหนดสิทธิ์เข้าถึง</span>
            </div>
            <button class="btn-add" onclick="openModal('create')">
                <i class="fas fa-plus"></i> เพิ่มผู้ใช้งาน
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-info">
                    <h4>ผู้ใช้งานทั้งหมด</h4>
                    <div class="count" id="statTotal">0</div>
                </div>
                <div class="stat-icon" style="background:#4e73df"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h4>ใช้งานอยู่ (Active)</h4>
                    <div class="count" id="statActive">0</div>
                </div>
                <div class="stat-icon" style="background:#1cc88a"><i class="fas fa-user-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h4>ระงับการใช้งาน</h4>
                    <div class="count" id="statInactive">0</div>
                </div>
                <div class="stat-icon" style="background:#e74c3c"><i class="fas fa-user-slash"></i></div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card">
            <div class="toolbar">
                <div style="font-weight: 600; color: #555;">รายชื่อผู้ใช้งาน</div>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="ค้นหาชื่อผู้ใช้..." onkeyup="filterTable()">
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="10%">ID</th>
                            <th width="30%">Username</th>
                            <th width="20%">Role (สิทธิ์)</th>
                            <th width="20%">Status (สถานะ)</th>
                            <th width="20%" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data loaded via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="userModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modalTitle">เพิ่มผู้ใช้งานใหม่</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="userId" value="0">
                    <input type="hidden" name="action" value="save">

                    <div class="form-group">
                        <label class="form-label">Username (ชื่อผู้ใช้) <span style="color:red">*</span></label>
                        <input type="text" class="form-control" name="username" id="username" required placeholder="ระบุชื่อผู้ใช้งาน">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password (รหัสผ่าน)</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" name="password" id="password" placeholder="ระบุรหัสผ่าน">
                            <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                        </div>
                        <small style="color:#888; display:none;" id="passwordHint">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role (สิทธิ์การใช้งาน)</label>
                        <select class="form-control" name="role" id="role">
                            <option value="user">User (ผู้ใช้งานทั่วไป)</option>
                            <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status (สถานะการใช้งาน)</label>
                        <select class="form-control" name="status" id="status">
                            <option value="1">Active (เปิดใช้งาน)</option>
                            <option value="0">Inactive (ระงับการใช้งาน)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" id="saveBtn"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="assets/js/manage_users.js"></script>
</body>
</html>
