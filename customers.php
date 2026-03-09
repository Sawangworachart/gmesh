<?php
/**
 * ไฟล์: customers.php
 * คำอธิบาย: หน้าจัดการข้อมูลลูกค้า (Customers Management) สำหรับผู้ดูแลระบบ (Admin)
 * รองรับการ เพิ่ม/ลบ/แก้ไข ลูกค้า, จัดกลุ่มลูกค้า, และค้นหาข้อมูล
 */

// เริ่มต้น Session และเชื่อมต่อฐานข้อมูล
include_once 'includes/auth.php';
include_once 'includes/db.php';

// --------------------------------------------------------------------------------
// 1. PHP BACKEND LOGIC (AJAX REQUESTS)
// --------------------------------------------------------------------------------
// ตรวจสอบว่าเป็น AJAX Request หรือไม่
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // --- จัดการ Request แบบ POST (Create/Update/Delete) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        // [Action: edit_group] แก้ไขชื่อกลุ่ม
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

        // [Action: delete_group] ลบกลุ่มและลูกค้าในกลุ่มทั้งหมด
        elseif ($action === 'delete_group') {
            $group_id = (int)$_POST['group_id'];
            mysqli_begin_transaction($conn);
            try {
                // ลบลูกค้าในกลุ่มก่อน
                mysqli_query($conn, "DELETE FROM customers WHERE group_id = $group_id");
                // ลบกลุ่ม
                mysqli_query($conn, "DELETE FROM customer_groups WHERE group_id = $group_id");
                mysqli_commit($conn);
                echo json_encode(['status' => 'success', 'message' => 'ลบกลุ่มและรายชื่อลูกค้าทั้งหมดเรียบร้อยแล้ว']);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบข้อมูลได้']);
            }
            exit;
        }

        // --- ส่วนจัดการข้อมูลลูกค้า (Customer Management) ---
        $customers_id = (isset($_POST['customers_id']) && !empty($_POST['customers_id'])) ? (int)$_POST['customers_id'] : 0;
        $customers_name = mysqli_real_escape_string($conn, $_POST['customers_name'] ?? '');
        $agency = mysqli_real_escape_string($conn, $_POST['agency'] ?? '');
        $contact_name = mysqli_real_escape_string($conn, $_POST['contact_name'] ?? '');
        $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
        $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
        $province = mysqli_real_escape_string($conn, $_POST['province'] ?? '');

        // [Action: create] เพิ่มลูกค้าใหม่
        if ($action === 'create') {
            $group_id_to_save = "NULL";
            // ตรวจสอบหรือสร้างกลุ่มตามชื่อลูกค้า (Logic เดิม)
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
        // [Action: update] แก้ไขข้อมูลลูกค้า
        elseif ($action === 'update' && $customers_id > 0) {
            // ดึง Group ID เดิมเพื่ออัปเดตชื่อกลุ่มด้วย (ถ้ามี)
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
        // [Action: delete] ลบลูกค้า
        elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (mysqli_query($conn, "DELETE FROM `customers` WHERE `customers_id` = $id")) {
                echo json_encode(['status' => 'success', 'message' => 'ลบลูกค้าเรียบร้อย']);
            }
        }
        exit;
    }

    // --- จัดการ Request แบบ GET (Fetch Data) ---
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // [Action: fetch_single] ดึงข้อมูลลูกค้า 1 คนเพื่อแก้ไข
        if ($action === 'fetch_single') {
            header('Content-Type: application/json');
            $id = (int)$_GET['id'];
            $res = mysqli_query($conn, "SELECT * FROM `customers` WHERE `customers_id` = $id");
            echo json_encode(mysqli_fetch_assoc($res));
        } 
        // [Action: fetch_all] ดึงข้อมูลทั้งหมดและเรนเดอร์เป็น HTML Table Rows
        elseif ($action === 'fetch_all') {
            header('Content-Type: text/html');
            
            // จัดกลุ่มลูกค้า
            $customers_by_group = [];
            $res_cus = mysqli_query($conn, "SELECT * FROM customers ORDER BY customers_name ASC");
            while ($cus = mysqli_fetch_assoc($res_cus)) {
                $gid = $cus['group_id'] ?: 'uncategorized';
                $customers_by_group[$gid][] = $cus;
            }

            // ดึงกลุ่มทั้งหมดมาแสดง
            $res_groups = mysqli_query($conn, "SELECT * FROM customer_groups ORDER BY group_id ASC");
            
            while ($group = mysqli_fetch_assoc($res_groups)) {
                $gid = $group['group_id'];
                $gname = $group['group_name'];
                $count = isset($customers_by_group[$gid]) ? count($customers_by_group[$gid]) : 0;

                // แสดง Header ของกลุ่ม
                echo "<tr class='group-header' onclick=\"toggleGroup('group-{$gid}', this)\">
                    <td colspan='7'>
                        <div class='header-content'>
                            <div class='company-info'>
                                <span class='folder-icon'><i class='fas fa-folder'></i></span>
                                <span class='company-name'>" . htmlspecialchars($gname) . " <span class='text-muted' style='font-size:0.9em; font-weight:normal;'>({$count})</span></span>
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

                // แสดงรายการลูกค้าในกลุ่ม (ซ่อนไว้ก่อน)
                if ($count > 0) {
                    foreach ($customers_by_group[$gid] as $row) {
                        echo "<tr class='group-item group-{$gid} customer-row' style='display:none;'>
                            <td class='text-center'><i class='fas fa-level-up-alt fa-rotate-90' style='color:#cbd5e0; margin-left:15px;'></i></td>
                            <td><span class='fw-bold' style='color:#4e73df;'>" . htmlspecialchars($row['customers_name']) . "</span><br><span class='badge-agency'>" . htmlspecialchars($row['agency']) . "</span></td>
                            <td><i class='fas fa-user-circle' style='color:#a0aec0; margin-right:5px;'></i> " . htmlspecialchars($row['contact_name']) . "</td>
                            <td><span style='font-family:monospace; font-size:1.1em; color:#2c3e50;'>" . htmlspecialchars($row['phone']) . "</span></td>
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

            // แสดงลูกค้าที่ไม่มีกลุ่ม (Uncategorized)
            if (isset($customers_by_group['uncategorized'])) {
                $count_uncat = count($customers_by_group['uncategorized']);
                echo "<tr class='group-header' onclick=\"toggleGroup('group-uncat', this)\">
                    <td colspan='7'>
                        <div class='header-content'>
                            <div class='company-info'>
                                <span class='folder-icon'><i class='fas fa-question-circle'></i></span>
                                <span class='company-name'>ลูกค้ารอจัดกลุ่ม <span class='text-muted'>({$count_uncat})</span></span>
                            </div>
                            <div class='header-actions'>
                                <i class='fas fa-chevron-down arrow-icon'></i>
                            </div>
                        </div>
                    </td>
                </tr>";
                foreach ($customers_by_group['uncategorized'] as $row) {
                    echo "<tr class='group-item group-uncat customer-row' style='display:none;'>
                        <td class='text-center'></td>
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
    <title>Customers Management - MaintDash</title>
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logomaintdash1.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- External Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/customers.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header Banner -->
        <div class="header-banner-custom">
            <div class="header-left-content">
                <div class="header-icon-circle"><i class="fas fa-users-cog"></i></div>
                <div class="header-text-group">
                    <h2 class="header-main-title">Customers Management</h2>
                    <p class="header-sub-desc">ระบบจัดการฐานข้อมูลลูกค้า แยกตามกลุ่มองค์กร</p>
                </div>
            </div>
            <div class="header-right-action">
                <button class="btn-pill-excel" onclick="window.location='customers_export.php'">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button class="btn-pill-primary" onclick="openModal('create')">
                    <i class="fas fa-plus"></i> เพิ่มลูกค้าใหม่
                </button>
            </div>
        </div>

        <!-- Toolbar & Search -->
        <div class="table-toolbar">
            <div class="search-container-custom">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อลูกค้า, แผนก, หรือเบอร์โทร..." onkeyup="filterTable()">
            </div>
        </div>

        <!-- Table Container -->
        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;"></th>
                            <th>ชื่อองค์กร / แผนก</th>
                            <th>ชื่อผู้ติดต่อ</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>ที่อยู่</th>
                            <th>จังหวัด</th>
                            <th class="text-center" style="width: 120px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="customerModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header-custom">
                <div class="header-left" style="display:flex; gap:15px; align-items:center;">
                    <div class="header-icon-box"><i class="fas fa-pen"></i></div>
                    <div class="header-titles">
                        <h3 id="modalTitle">ข้อมูลลูกค้า</h3>
                        <p class="header-subtitle" style="margin:0; font-size:0.85rem; color:#888;">จัดการรายละเอียดข้อมูลองค์กรและผู้ติดต่อ</p>
                    </div>
                </div>
                <button class="close-btn-custom" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            
            <form id="customerForm">
                <div class="modal-body custom-scroll">
                    <input type="hidden" name="customers_id" id="customers_id">
                    <input type="hidden" name="action" id="form_action" value="create">
                    
                    <div class="section-header" style="background:#f1f5f9; padding:8px 15px; border-radius:8px; margin-bottom:15px; color:#4e73df; font-weight:600;">
                        <i class="fas fa-building section-icon"></i> ข้อมูลองค์กรและหน่วยงาน
                    </div>
                    
                    <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">ชื่อองค์กร <span style="color:red">*</span></label>
                            <div class="input-icon-wrapper">
                                <input type="text" class="form-control-custom" name="customers_name" id="customers_name" required placeholder="ระบุชื่อบริษัท / องค์กร">
                                <i class="fas fa-user-tie input-icon"></i>
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">แผนก / หน่วยงาน</label>
                            <div class="input-icon-wrapper">
                                <input type="text" class="form-control-custom" name="agency" id="agency" placeholder="เช่น ฝ่ายไอที, แผนกจัดซื้อ">
                                <i class="fas fa-sitemap input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="section-header" style="background:#f1f5f9; padding:8px 15px; border-radius:8px; margin:20px 0 15px; color:#4e73df; font-weight:600;">
                        <i class="fas fa-address-book section-icon"></i> รายละเอียดการติดต่อ
                    </div>
                    
                    <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label class="form-label">ชื่อผู้ติดต่อ <span style="color:red">*</span></label>
                            <div class="input-icon-wrapper">
                                <input type="text" class="form-control-custom" name="contact_name" id="contact_name" required>
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์ <span style="color:red">*</span></label>
                            <div class="input-icon-wrapper">
                                <input type="text" class="form-control-custom" name="phone" id="phone" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                <i class="fas fa-phone-alt input-icon"></i>
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">ที่อยู่</label>
                            <div class="input-icon-wrapper">
                                <textarea class="form-control-custom" name="address" id="address" rows="2" style="padding-left:15px;"></textarea>
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">จังหวัด <span style="color:red">*</span></label>
                            <div class="input-icon-wrapper">
                                <input type="text" class="form-control-custom" name="province" id="province" required>
                                <i class="fas fa-map input-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="submit" class="btn-save-custom" id="saveBtn"><i class="fas fa-check-circle"></i> บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="assets/js/customers.js"></script>
</body>
</html>
