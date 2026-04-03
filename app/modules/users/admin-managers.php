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
    <title>MaintDash</title>

    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/manage_admin.css?v=<?php echo time(); ?>">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/global_delete.js"></script>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">

        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-circle icon-bg-purple-light">
                    <i class="fas fa-user-cog" style="color: #7e22ce;"></i>
                </div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Edit user</h2>
                    <p class="header-sub-desc">จัดการข้อมูลผู้ใช้งานและกำหนดสิทธิ์เข้าถึงระบบ</p>
                </div>
            </div>

            <div class="header-right-action">
                <button class="btn-pill-primary" onclick="openModal('create')">
                    <i class="fas fa-plus"></i> เพิ่มข้อมูล
                </button>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card card-all active" onclick="filterByStatus('all', this)">
                <div class="stat-icon-box bg-light-purple">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <p>ทั้งหมด</p>
                    <h3 id="statTotal">0</h3>
                </div>
            </div>
            <div class="stat-card card-active" onclick="filterByStatus('1', this)">
                <div class="stat-icon-box bg-light-green">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <p>ใช้งานอยู่</p>
                    <h3 id="statActive">0</h3>
                </div>
            </div>
            <div class="stat-card card-inactive" onclick="filterByStatus('0', this)">
                <div class="stat-icon-box bg-light-red">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="stat-info">
                    <p>ระงับการใช้งาน</p>
                    <h3 id="statInactive">0</h3>
                </div>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-container-custom">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อผู้ใช้ หรือสิทธิ์..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="card-table">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th width="10%">ลำดับ</th>
                        <th width="25%">ชื่อผู้ใช้ (Username)</th>
                        <th width="15%">จัดการรหัสผ่าน</th>
                        <th width="15%">สิทธิ์ (Role)</th>
                        <th width="15%">สถานะ (Status)</th>
                        <th width="10%" class="text-center">จัดการ</th>
                    </tr>
                </thead>

                <tbody id="tableBody"></tbody>
            </table>
            <div id="noData" style="text-align:center; padding:30px; display:none; color:#999;">
                <i class="fas fa-inbox fa-2x"></i><br>ไม่พบข้อมูล
            </div>
        </div>
    </div>

    <div id="userModal" class="modal-overlay">
        <div class="modal-box custom-modal-style" style="max-width: 650px;">
            <div class="modal-header-custom">
                <div class="header-left">
                    <div class="header-icon-box icon-bg-purple">
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
                <div class="modal-body custom-scroll">
                    <input type="hidden" name="id" id="userId" value="0">
                    <input type="hidden" name="action" value="save">

                    <div class="section-header">
                        <div class="section-indicator" style="background:#8b5cf6;"></div>
                        <i class="fas fa-id-card section-icon" style="color:#8b5cf6;"></i>
                        <span style="color:#8b5cf6;">ข้อมูลบัญชี</span>
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
                            <input type="password"
                                class="form-control-custom"
                                name="password"
                                id="password"
                                placeholder="ตั้งรหัสผ่านอย่างน้อย 6 ตัว"
                                minlength="6">

                            <i class="fas fa-eye toggle-password" onclick="togglePassword()" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8;"></i>
                        </div>
                        <small style="color:#94a3b8; margin-top:5px; display:block;" id="passwordHint">
                            <i class="fas fa-info-circle"></i> เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน
                        </small>
                    </div>

                    <div class="section-header mt-4">
                        <div class="section-indicator" style="background:#8b5cf6;"></div>
                        <i class="fas fa-sliders-h section-icon" style="color:#8b5cf6;"></i>
                        <span style="color:#8b5cf6;">สิทธิ์การใช้งานและสถานะ</span>
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
                    <button type="submit" id="saveBtn" class="btn-save-custom" style="background:#8b5cf6;">
                        <i class="fas fa-check-circle"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_URL = 'manage_admin.php?api=true';

        $(document).ready(function() {
            loadData();

            $('#userForm').submit(function(e) {
                e.preventDefault();

                const pwd = $('#password').val();
                const userId = $('#userId').val();

                // ถ้าเป็นการเพิ่มใหม่ หรือกรอก password ตอนแก้ไข
                if ((userId == 0 || pwd !== '') && pwd.length < 6) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'รหัสผ่านไม่ถูกต้อง',
                        text: 'รหัสผ่านต้องยาวอย่างน้อย 6 ตัวอักษร'
                    });
                    return;
                }

                const formData = $(this).serialize();
                $.post(API_URL, formData, function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        closeModal();
                        loadData();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            });


            // Close Modal Outside
            $(window).click(function(e) {
                if ($(e.target).hasClass('modal-overlay')) closeModal();
            });
        });

        function loadData() {
            $.post(API_URL, {
                action: 'fetch_all'
            }, function(res) {

                if (res.success) {

                    $('#statTotal').text(res.stats.total);
                    $('#statActive').text(res.stats.active);
                    $('#statInactive').text(res.stats.inactive);

                    let html = '';

                    if (res.data.length === 0) {

                        html = '<tr><td colspan="6" class="text-center" style="padding:30px; color:#999;">ไม่พบข้อมูลผู้ใช้งาน</td></tr>';

                    } else {

                        res.data.forEach((user, index) => {

                            // ===== ROLE BADGE =====
                            let roleBadge = '';

                            if (user.role === 'superadmin') {
                                roleBadge = '<span class="badge badge-super"><i class="fas fa-crown"></i> Super Admin</span>';
                            } else if (user.role === 'admin') {
                                roleBadge = '<span class="badge badge-admin"><i class="fas fa-shield-alt"></i> Admin</span>';
                            } else {
                                roleBadge = '<span class="badge badge-user"><i class="fas fa-user"></i> User</span>';
                            }

                            // ===== AVATAR LETTER =====
                            const avatarLetter = user.username ?
                                user.username.charAt(0).toUpperCase() :
                                '?';

                            // ===== STATUS BADGE =====
                            let statusBadge = '';
                            if (user.status == 1) {
                                statusBadge = '<span class="badge badge-active"><i class="fas fa-check-circle"></i> Active</span>';
                            } else {
                                statusBadge = '<span class="badge badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>';
                            }

                            // ===== ROW HTML =====
                            html += `
                        <tr class="user-row" data-status="${user.status}">
                            <td>
                                <span style="color:#aaa; font-weight:bold;">
                                    ${index + 1}
                                </span>
                            </td>

                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div class="user-avatar">${avatarLetter}</div>
                                    <div style="font-weight:600; color:#334155">
                                        ${user.username}
                                    </div>
                                </div>
                            </td>

                            <td class="password-cell">
                                <button type="button"
                                    class="btn-icon reset-pwd"
                                    data-id="${user.id}"
                                    title="รีเซ็ตรหัสผ่าน">
                                    <i class="fas fa-key"></i>
                                </button>
                            </td>

                            <td>${roleBadge}</td>
                            <td>${statusBadge}</td>

                            <td class="text-center">
                                <button class="btn-icon"
                                    style="color:#8b5cf6;"
                                    onclick="editUser(${user.id})">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn-icon"
                                    style="color:#ef4444;"
                                    onclick="deleteUser(${user.id})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                        });
                    }

                    $('#tableBody').html(html);
                    $('#noData').toggle(res.data.length === 0);
                }

            }, 'json');
        }



        function filterByStatus(status, el) {
            $('.stat-card').removeClass('active');
            $(el).addClass('active');

            const rows = $("#tableBody tr");
            if (status === 'all') {
                rows.show();
            } else {
                rows.hide().filter(function() {
                    return $(this).attr('data-status') == status;
                }).show();
            }
        }

        function filterTable() {
            var value = $('#searchInput').val().toLowerCase();
            $("#tableBody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        }

        function openModal(mode) {
            $('#userForm')[0].reset();
            $('#userId').val('0');
            $('#password').attr('required', true);
            $('#passwordHint').hide();

            if (mode === 'create') {
                $('#modalTitle').text('เพิ่มผู้ใช้งานใหม่');
                $('.header-subtitle').text('กำหนดชื่อผู้ใช้และสิทธิ์การเข้าถึง');
                $('#saveBtn').html('<i class="fas fa-check-circle"></i> บันทึกข้อมูล');
                $('#role').val('user');
            }
            $('#userModal').addClass('show');
        }

        function closeModal() {
            $('.modal-overlay').removeClass('show');
        }

        function togglePassword() {
            const input = $('#password');
            const icon = $('.toggle-password');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        }

        function editUser(id) {
            $.get(API_URL, {
                action: 'fetch_single',
                id: id
            }, function(res) {
                if (res.success) {
                    const d = res.data;
                    $('#userId').val(d.id);
                    $('#username').val(d.username);
                    $('#role').val(d.role);
                    $('#status').val(d.status);
                    $('#password').val('').removeAttr('required');
                    $('#passwordHint').show();

                    $('#modalTitle').text('แก้ไขข้อมูลผู้ใช้งาน');
                    $('.header-subtitle').text('รหัสผู้ใช้: ' + d.id);
                    $('#saveBtn').html('<i class="fas fa-save"></i> อัปเดตข้อมูล');
                    $('#userModal').addClass('show');
                }
            }, 'json');
        }

        // แทนที่ฟังก์ชัน deleteUser เดิมด้วยอันนี้ครับ
        function deleteUser(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "ข้อมูลผู้ใช้งานนี้จะถูกลบถาวร!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'ลบข้อมูล',
                cancelButtonText: 'ยกเลิก',
                customClass: {
                    container: 'swal-z-index' // เพิ่ม class เพื่อจัดการ z-index
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(API_URL, {
                            action: 'delete',
                            id: id
                        }, function(res) {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ลบสำเร็จ',
                                    text: res.message,
                                    timer: 1000,
                                    showConfirmButton: false
                                });
                                loadData(); // โหลดตารางใหม่
                            } else {
                                Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                            }
                        }, 'json')
                        .fail(function() {
                            Swal.fire('Error', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
                        });
                }
            });
        }
        $(document).on('click', '.reset-pwd', function() {
            const userId = $(this).data('id');

            Swal.fire({
                title: 'รีเซ็ตรหัสผ่าน',

                icon: 'warning',

                input: 'password',
                inputLabel: 'กำหนดรหัสผ่านใหม่',
                inputPlaceholder: 'อย่างน้อย 6 ตัวอักษร',

                showCancelButton: true,
                confirmButtonText: 'บันทึก',
                cancelButtonText: 'ยกเลิก',

                confirmButtonColor: '#f59e0b', // ⭐ สีเหลือง
                cancelButtonColor: '#94a3b8', // ⭐ เทา
                reverseButtons: true, // ⭐ ปุ่มสลับข้าง (UX ดี)

                inputValidator: (value) => {
                    if (!value || value.length < 6) {
                        return 'รหัสผ่านต้องยาวอย่างน้อย 6 ตัวอักษร';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(API_URL, {
                        action: 'reset_password',
                        id: userId,
                        password: result.value
                    }, function(res) {
                        if (res.success) {
                            Swal.fire('สำเร็จ', 'รีเซ็ตรหัสผ่านเรียบร้อย', 'success');
                            loadData();
                        } else {
                            Swal.fire('ผิดพลาด', res.message, 'error');
                        }
                    }, 'json');
                }
            });
        });
    </script>
</body>

</html>