<?php
// หน้า customers ของ admin
include_once 'auth.php';
include_once 'db.php';

// --------------------------------------------------------------------------------
// 1. PHP BACKEND LOGIC (AJAX REQUESTS)
// --------------------------------------------------------------------------------
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        // --- ส่วนจัดการกลุ่ม (Group Management) ---
        if ($action === 'edit_group') {
            $group_id = (int)$_POST['group_id'];
            $group_name = mysqli_real_escape_string($conn, $_POST['group_name']);
            $sql = "UPDATE customer_groups SET group_name = '$group_name' WHERE group_id = $group_id";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['status' => 'success', 'message' => 'แก้ไขชื่อกลุ่มเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            }
            exit;
        }

        elseif ($action === 'delete_group') {
            $group_id = (int)$_POST['group_id'];
            mysqli_begin_transaction($conn);
            try {
                mysqli_query($conn, "DELETE FROM customers WHERE group_id = $group_id");
                mysqli_query($conn, "DELETE FROM customer_groups WHERE group_id = $group_id");
                mysqli_commit($conn);
                echo json_encode(['status' => 'success', 'message' => 'ลบกลุ่มและรายชื่อลูกค้าทั้งหมดเรียบร้อยแล้ว']);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบข้อมูลได้']);
            }
            exit;
        }

        // --- ส่วนจัดการลูกค้า (Customer Management - ปรับปรุง Logic แยก Create/Update) ---
        $customers_id = (isset($_POST['customers_id']) && !empty($_POST['customers_id'])) ? (int)$_POST['customers_id'] : 0;
        $customers_name = mysqli_real_escape_string($conn, $_POST['customers_name'] ?? '');
        $agency = mysqli_real_escape_string($conn, $_POST['agency'] ?? '');
        $contact_name = mysqli_real_escape_string($conn, $_POST['contact_name'] ?? '');
        $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
        $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
        $province = mysqli_real_escape_string($conn, $_POST['province'] ?? '');

        if ($action === 'create') {
            // กรณีสร้างใหม่
            $group_id_to_save = "NULL";
            if (!empty($customers_name)) {
                $check_group = mysqli_query($conn, "SELECT group_id FROM customer_groups WHERE group_name = '$customers_name' LIMIT 1");
                if ($row = mysqli_fetch_assoc($check_group)) {
                    $group_id_to_save = $row['group_id'];
                } else {
                    mysqli_query($conn, "INSERT INTO customer_groups (group_name) VALUES ('$customers_name')");
                    $group_id_to_save = mysqli_insert_id($conn);
                }
            }
            $sql = "INSERT INTO `customers` (`customers_name`, `agency`, `contact_name`, `phone`, `address`, `province`, `group_id`) 
                    VALUES ('$customers_name', '$agency', '$contact_name', '$phone', '$address', '$province', $group_id_to_save)";
            
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลเรียบร้อย']);
            } else {
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            }
        } 
        elseif ($action === 'update' && $customers_id > 0) {
            // 🌟 กรณีแก้ไข: อ้างอิงจาก ID เดิมเสมอ
            $get_current = mysqli_query($conn, "SELECT group_id FROM customers WHERE customers_id = $customers_id");
            $curr = mysqli_fetch_assoc($get_current);
            $current_group_id = $curr['group_id'];

            if ($current_group_id) {
                mysqli_query($conn, "UPDATE customer_groups SET group_name = '$customers_name' WHERE group_id = $current_group_id");
                $group_id_to_save = $current_group_id;
            } else {
                $group_id_to_save = "NULL";
            }

            $sql = "UPDATE `customers` SET 
                    `customers_name`='$customers_name', 
                    `agency`='$agency', 
                    `contact_name`='$contact_name', 
                    `phone`='$phone', 
                    `address`='$address', 
                    `province`='$province', 
                    `group_id`=$group_id_to_save 
                    WHERE `customers_id`=$customers_id";
            
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลเรียบร้อย']);
            } else {
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            }
        }
        elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (mysqli_query($conn, "DELETE FROM `customers` WHERE `customers_id` = $id")) {
                echo json_encode(['status' => 'success', 'message' => 'ลบลูกค้าเรียบร้อย']);
            }
        }
        exit;
    }

    // --- ส่วนดึงข้อมูลแสดงผล ---
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'fetch_single') {
            header('Content-Type: application/json');
            $id = (int)$_GET['id'];
            $res = mysqli_query($conn, "SELECT * FROM `customers` WHERE `customers_id` = $id");
            echo json_encode(mysqli_fetch_assoc($res));
        } 
        elseif ($action === 'fetch_all') {
            header('Content-Type: text/html');
            
            $customers_by_group = [];
            $res_cus = mysqli_query($conn, "SELECT * FROM customers ORDER BY customers_name ASC");
            while ($cus = mysqli_fetch_assoc($res_cus)) {
                $gid = $cus['group_id'] ?: 'uncategorized';
                $customers_by_group[$gid][] = $cus;
            }

            $res_groups = mysqli_query($conn, "SELECT * FROM customer_groups ORDER BY group_id ASC");
            while ($group = mysqli_fetch_assoc($res_groups)) {
                $gid = $group['group_id'];
                $gname = $group['group_name'];
                $count = isset($customers_by_group[$gid]) ? count($customers_by_group[$gid]) : 0;

                echo "<tr class='group-header' onclick=\"toggleGroup('group-{$gid}', this)\">
                    <td colspan='7'>
                        <div class='header-content'>
                            <div class='company-info'>
                                <span class='folder-icon'><i class='fas fa-folder'></i></span>
                                <span class='company-name'>" . htmlspecialchars($gname) . " <span class='text-muted'>({$count})</span></span>
                            </div>
                            <div class='header-actions'>
                                <button class='action-btn text-edit' onclick='event.stopPropagation(); editGroup({$gid}, \"" . htmlspecialchars($gname, ENT_QUOTES) . "\")' title='แก้ไขชื่อกลุ่ม'>
                                    <i class='fas fa-pencil-alt'></i>
                                </button>
                                <button class='action-btn text-delete' onclick='event.stopPropagation(); deleteGroup({$gid})' title='ลบทั้งกลุ่ม'>
                                    <i class='fas fa-trash'></i>
                                </button>
                                <i class='fas fa-chevron-down arrow-icon'></i>
                            </div>
                        </div>
                    </td>
                </tr>";

                if ($count > 0) {
                    foreach ($customers_by_group[$gid] as $row) {
                        echo "<tr class='group-item group-{$gid} customer-row' style='display:none;'>
                            <td class='tree-line-cell'><div class='tree-line-indicator'></div></td>
                            <td><span class='fw-bold'>" . htmlspecialchars($row['customers_name']) . "</span><br><small class='badge-agency'>" . htmlspecialchars($row['agency']) . "</small></td>
                            <td>" . htmlspecialchars($row['contact_name']) . "</td>
                            <td>" . htmlspecialchars($row['phone']) . "</td>
                            <td><span class='address-text'>" . htmlspecialchars($row['address']) . "</span></td>
                            <td><span class='province-tag'>" . htmlspecialchars($row['province']) . "</span></td>
                            <td class='text-center'>
                                <button class='action-btn text-edit' onclick='openModal(\"edit\", {$row['customers_id']})' title='แก้ไขข้อมูล'><i class='fas fa-pencil-alt'></i></button>
                                <button class='action-btn text-delete' onclick='deleteCustomer({$row['customers_id']})' title='ลบข้อมูล'><i class='fas fa-trash-alt'></i></button>
                            </td>
                        </tr>";
                    }
                }
            }

            if (isset($customers_by_group['uncategorized'])) {
                $count_uncat = count($customers_by_group['uncategorized']);
                echo "<tr class='group-header' onclick=\"toggleGroup('group-uncat', this)\">
                    <td colspan='7'>
                        <div class='header-content'>
                            <div class='company-info'>
                                <span class='folder-icon'><i class='fas fa-question-circle'></i></span>
                                <span class='company-name'>ลูกค้ารอจัดกลุ่ม <span class='text-muted'>({$count_uncat})</span></span>
                            </div>
                        </div>
                    </td>
                </tr>";
                foreach ($customers_by_group['uncategorized'] as $row) {
                    echo "<tr class='group-item group-uncat customer-row' style='display:none;'>
                        <td class='tree-line-cell'><div class='tree-line-indicator'></div></td>
                        <td>" . htmlspecialchars($row['customers_name']) . "</td>
                        <td>" . htmlspecialchars($row['contact_name']) . "</td>
                        <td>" . htmlspecialchars($row['phone']) . "</td>
                        <td>" . htmlspecialchars($row['address']) . "</td>
                        <td>" . htmlspecialchars($row['province']) . "</td>
                        <td class='text-center'>
                            <button class='action-btn text-edit' onclick='openModal(\"edit\", {$row['customers_id']})'><i class='fas fa-pencil-alt'></i></button>
                            <button class='action-btn text-delete' onclick='deleteCustomer({$row['customers_id']})'><i class='fas fa-trash-alt'></i></button>
                        </td>
                    </tr>";
                }
            }
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>MaintDash - Customers</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/customers.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Customers</h2>
                <p class="page-subtitle">จัดการข้อมูลลูกค้า แผนก และการติดต่อแยกตามกลุ่มองค์กร</p>
            </div>
            <div class="header-right-action">
                <button class="btn btn-success" onclick="window.location='customers_export.php'"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn btn-primary" onclick="openModal('create')"><i class="fas fa-plus"></i> เพิ่มข้อมูล</button>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อลูกค้า, แผนก, หรือเบอร์โทร..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;"></th>
                            <th>ชื่อองค์กร / แผนก</th>
                            <th>ชื่อผู้ติดต่อ</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>ที่อยู่</th>
                            <th>จังหวัด</th>
                            <th class="text-center" style="width: 120px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="customerModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modalTitle" class="modal-title">ข้อมูลลูกค้า</h3>
                <button class="btn-close" onclick="closeModal()"></button>
            </div>
            <form id="customerForm">
                <div class="modal-body">
                    <input type="hidden" name="customers_id" id="customers_id">
                    <input type="hidden" name="action" id="form_action" value="create">
                    
                    <div class="form-grid">
                        <div class="form-group col-span-2">
                            <label class="form-label">ชื่อองค์กร <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="customers_name" id="customers_name" required>
                        </div>
                        <div class="form-group col-span-2">
                            <label class="form-label">แผนก / หน่วยงาน</label>
                            <input type="text" class="form-control" name="agency" id="agency">
                        </div>
                        <div class="form-group">
                            <label class="form-label">ชื่อผู้ติดต่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contact_name" id="contact_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" id="phone" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        </div>
                        <div class="form-group col-span-2">
                            <label class="form-label">ที่อยู่</label>
                            <textarea class="form-control" name="address" id="address" rows="3"></textarea>
                        </div>
                        <div class="form-group col-span-2">
                            <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="province" id="province" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/customers.js"></script>
    <script>
        function toggleGroup(groupClass, headerElement) {
            const icon = $(headerElement).find('.arrow-icon');
            icon.toggleClass('rotated');
            $('.' + groupClass).slideToggle(200);
        }
    </script>
</body>
</html>