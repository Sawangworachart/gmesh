<?php
// กำหนดค่าเริ่มต้นเพื่อให้โค้ด HTML ทำงานได้เสมอ และแสดงข้อผิดพลาด
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. เชื่อมต่อฐานข้อมูล (ดึงการเชื่อมต่อ PDO จากไฟล์ db.php)
// สมมติว่าไฟล์ db.php มีการเชื่อมต่อ PDO ในตัวแปร $pdo
// *** กรุณาตรวจสอบให้แน่ใจว่าไฟล์ db.php มีอยู่และตั้งค่าการเชื่อมต่อถูกต้องแล้ว ***
require_once 'includes/db.php'; 

$message = '';
$searchQuery = '';
$editData = null;
$filteredProducts = [];

// ตรวจสอบว่ามีการเชื่อมต่อ $pdo สำเร็จหรือไม่
if (!isset($pdo)) {
    // ใช้ภาษาไทยตามภาพที่สอง: Error: ไม่สามารถดำเนินการได้ เนื่องจากขาดการเชื่อมต่อฐานข้อมูล
    $message = "Error: ไม่สามารถดำเนินการได้ เนื่องจากขาดการเชื่อมต่อฐานข้อมูล กรุณาตรวจสอบ db.php";
}

// ---------- Delete (ลบข้อมูล) ----------
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['item_id'])) {
    $id = trim($_GET['item_id']); // product_id

    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM product WHERE product_id = ?");
            $stmt->execute([$id]);
            $message = "ลบข้อมูล Product ID: $id สำเร็จ!";
        } catch (PDOException $e) {
            $message = "Error: ลบข้อมูลไม่ได้ เนื่องจากเกิดข้อผิดพลาดในการดำเนินการฐานข้อมูล";
        }
    } else {
        $message = "Error: ลบข้อมูลไม่ได้ เนื่องจากขาดการเชื่อมต่อฐานข้อมูล";
    }

    // ล้างพารามิเตอร์ action/item_id ก่อน Redirect
    $redirect_url = strtok($_SERVER['PHP_SELF'], '?') . "?msg=" . urlencode($message);
    header("Location: " . $redirect_url);
    exit();
}

// ---------- Load Edit (โหลดข้อมูลสำหรับแก้ไข) ----------
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['item_id'])) {
    $id = trim($_GET['item_id']); // product_id

    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT *, product_id as auto_id FROM product WHERE product_id = ?");
            $stmt->execute([$id]);
            $editData = $stmt->fetch();
            if (!$editData) {
                 $message = "Error: ไม่พบข้อมูล Product ID: $id";
            }
        } catch (PDOException $e) {
            $message = "Error: เกิดข้อผิดพลาดในการโหลดข้อมูล: " . $e->getMessage();
        }
    }
}

// ---------- Create/Update (เพิ่ม/แก้ไขข้อมูล) ----------
if (isset($_POST['submit_form'])) {

    if (isset($pdo)) {
        $product_id_for_update = trim($_POST['auto_id'] ?? ''); 
        
        // รับค่าตามชื่อคอลัมน์และป้องกัน XSS
        $customers_id = trim($_POST['customers_id'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $repair_details = trim($_POST['repair_details'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $device_name = trim($_POST['device_name'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        // $solution ถูกลบออก

        $isUpdate = !empty($product_id_for_update);

        try {
            if ($isUpdate) {
                // UPDATE (แก้ไข)
                $sql = "UPDATE product SET customers_id=?, address=?, repair_details=?, phone=?, device_name=?, serial_number=? WHERE product_id=?";
                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $customers_id,
                    $address,
                    $repair_details,
                    $phone,
                    $device_name,
                    $serial_number,
                    $product_id_for_update 
                ]);
                $message = "แก้ไขข้อมูล Product ID: $product_id_for_update สำเร็จ!";
            } else {
                // INSERT (เพิ่ม)
                $sql = "INSERT INTO product (customers_id, address, repair_details, phone, device_name, serial_number) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                $stmt->execute([
                    $customers_id,
                    $address,
                    $repair_details,
                    $phone,
                    $device_name,
                    $serial_number,
                ]);
                // ดึง ID ล่าสุด
                $lastId = $pdo->lastInsertId();
                $message = "เพิ่มข้อมูลสำเร็จ! Product ID: $lastId";
            }
        } catch (PDOException $e) {
             $message = "Error: บันทึกข้อมูลไม่ได้ เนื่องจากเกิดข้อผิดพลาดในการดำเนินการฐานข้อมูล: " . $e->getMessage();
        }

    } else {
        $message = "Error: ไม่สามารถบันทึกข้อมูลได้ เนื่องจากขาดการเชื่อมต่อฐานข้อมูล";
    }

    // ล้างพารามิเตอร์ action/item_id ก่อน Redirect
    $redirect_url = strtok($_SERVER['PHP_SELF'], '?') . "?msg=" . urlencode($message);
    header("Location: " . $redirect_url);
    exit();
}

// ---------- Search (ค้นหาและโหลดข้อมูลทั้งหมด) ----------
if (isset($pdo)) {
    $where_clause = '';
    $params = [];
    if (isset($_GET['search_query'])) {
        $searchQuery = trim($_GET['search_query']);
        if ($searchQuery != '') {
            // ค้นหาในหลายคอลัมน์
            $where_clause = " WHERE product_id LIKE ? OR customers_id LIKE ? OR address LIKE ? OR device_name LIKE ? OR serial_number LIKE ? OR phone LIKE ? OR repair_details LIKE ?";
            $like_query = '%' . $searchQuery . '%';
            // 7 พารามิเตอร์สำหรับ 7 LIKE
            $params = array_fill(0, 7, $like_query); 
        }
    }

    $sql = "SELECT *, product_id as auto_id FROM product" . $where_clause . " ORDER BY product_id DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $filteredProducts = $stmt->fetchAll();
    } catch (PDOException $e) {
         $message = (empty($message) ? "Error: ไม่สามารถดึงข้อมูลได้ เนื่องจากเกิดข้อผิดพลาดในการดำเนินการฐานข้อมูล" : $message);
    }
}


// แสดงข้อความแจ้งเตือนที่มาจาก Redirect
if (isset($_GET['msg']))
    $message = htmlspecialchars($_GET['msg']);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการ Product</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap">

    <style>
        /* ---------------------------------------------------- */
        /* === Color Palette Based on Image === */
        /* ---------------------------------------------------- */
        :root {
            /* สีตาม Sidebar ในรูป */
            --sidebar-bg: #f8f9fc;
            /* พื้นหลังสีขาว/อ่อนมาก */
            --sidebar-text: #4285f4;
            /* สีน้ำเงินของเมนูที่ยังไม่ Active */
            --sidebar-active-bg: #4285f4;
            /* สีน้ำเงินของเมนู Active */
            --sidebar-active-text: white;
            /* สีขาวของเมนู Active */
            --main-text: #212529;
            /* สีดำของเนื้อหาหลัก */
            --button-color: #4285f4;
            /* สีน้ำเงินสำหรับปุ่ม */
            --border-color: #e0e0e0;
            /* สีเส้นขอบอ่อน */
            --sidebar-width: 220px; /* เพิ่มความกว้างเพื่อให้ข้อความและไอคอนไม่เบียดกันเกินไป */
            --sidebar-collapsed-width: 60px; /* ความกว้างเมื่อยุบ */
        }

        /* ---------------------------------------------------- */
        /* === 1. General & Layout === */
        /* ---------------------------------------------------- */
        body {
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
            background: #f1f1f1;
            font-size: 15px;
            /* ใช้ transition เพื่อให้การยุบ/ขยาย Sidebar ดูนุ่มนวล */
            transition: all 0.3s ease-in-out; 
        }

        .container {
            display: flex;
        }
        
        /* ---------------------------------------------------- */
        /* === 2. Sidebar Style (ตามภาพ: ขาว/ฟ้า) === */
        /* ---------------------------------------------------- */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            height: 100vh;
            color: var(--sidebar-text);
            padding: 0;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 1px 0 5px rgba(0, 0, 0, 0.1);
            overflow: hidden; 
            transition: width 0.3s ease-in-out; 
        }

        /* **** ปรับขนาดตัวอักษรของ Menu Header **** */
        .menu-header {
            font-size: 1.8em; /* ปรับให้ใหญ่ขึ้นตามคำขอ */
            font-weight: 500;
            color: var(--sidebar-text);
            padding: 20px 20px 10px 20px;
            position: relative;
            white-space: nowrap;
        }
        
        .menu-toggle {
            float: right;
            cursor: pointer;
            color: #6c757d;
            font-size: 1.8em; /* ปรับให้สมดุลกับ Header */
            line-height: 1.5;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }

        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu li {
            padding: 12px 20px; /* เพิ่ม padding แนวตั้งเล็กน้อย */
            margin-bottom: 5px;
            transition: all 0.2s;
            border-radius: 0 50px 50px 0;
        }

        .menu li:hover:not(.active) {
            background: #e9ecef;
        }

        .menu li.active {
            background: var(--sidebar-active-bg);
        }
        
        .menu li.active a, 
        .menu li.active a i.fa {
            color: var(--sidebar-active-text);
        }

        .menu a {
            text-decoration: none;
            color: var(--sidebar-text);
            font-size: 1.1em; /* ปรับให้ใหญ่ขึ้นเล็กน้อย */
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .menu a i.fa {
            margin-right: 12px; /* เพิ่มระยะห่างระหว่างไอคอนกับข้อความ */
            width: 20px;
            text-align: center;
            color: var(--sidebar-text);
        }
        
        /* ---------------------------------------------------- */
        /* === Sidebar Toggle Styles (ยุบ Sidebar) === */
        /* ---------------------------------------------------- */
        body.sidebar-toggled .sidebar {
            width: var(--sidebar-collapsed-width);
        }

        body.sidebar-toggled .sidebar .menu-header {
            padding: 20px 5px 10px 5px;
        }

        body.sidebar-toggled .sidebar .menu-header span {
            display: none; /* ซ่อนคำว่า Menu */
        }
        
        body.sidebar-toggled .sidebar .menu-toggle {
             right: 5px; 
        }

        body.sidebar-toggled .sidebar .menu li {
            padding: 12px 0; 
            text-align: center;
            border-radius: 0; 
        }
        
        body.sidebar-toggled .sidebar .menu li a span {
            display: none; /* ซ่อนชื่อเมนู */
        }

        body.sidebar-toggled .sidebar .menu a i.fa {
            margin: 0;
            width: 100%;
        }


        /* ---------------------------------------------------- */
        /* === 3. Main Content & Controls === */
        /* ---------------------------------------------------- */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
            box-sizing: border-box;
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
        }
        
        body.sidebar-toggled .main-content {
            margin-left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }

        h1 {
            margin-top: 0;
            color: var(--main-text);
            font-weight: 400; 
            font-size: 2em;
            margin-bottom: 25px;
        }
        
        .content-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }


        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0; 
            padding: 0;
        }
        
        .search-area {
            display: flex;
            align-items: center;
        }

        .search-area input {
            padding: 8px 12px;
            width: 250px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .search-area input:focus {
            border-color: var(--button-color);
            box-shadow: 0 0 5px rgba(66, 133, 244, 0.3);
            outline: none;
        }

        .add-button {
            padding: 8px 15px;
            background: var(--button-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background 0.3s;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .add-button:hover {
            background: #3367d6;
        }

        .message-box {
            padding: 10px 15px;
            font-size: 0.9em;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* ---------------------------------------------------- */
        /* === 4. Table Style (Grid Layout) === */
        /* ---------------------------------------------------- */
        .data-table {
            overflow: hidden;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }

        .table-row {
            display: grid;
            /* 8 คอลัมน์: ID | customers_id | phone | address | device_name | serial_number | repair_details | จัดการ */
            grid-template-columns: 0.5fr 1fr 1fr 1.2fr 1.2fr 1.2fr 2.5fr 0.8fr; 
            padding: 10px 10px;
            border-bottom: 1px solid #eee;
            align-items: center; 
            font-size: 0.85em;
        }

        .table-header-row {
            background: #f8f9fa;
            font-weight: 500;
            color: var(--main-text);
            padding: 12px 10px;
        }

        .table-data-row:hover {
            background: #fcfcfc;
        }

        .table-row>span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* ให้คอลัมน์ Repair Details แสดงหลายบรรทัดได้ */
        .table-row>span:nth-child(7) { 
            white-space: normal; 
            display: -webkit-box;
            -webkit-line-clamp: 2; /* จำกัดไม่เกิน 2 บรรทัด */
            -webkit-box-orient: vertical;
        }


        .col-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
            align-items: center;
        }

        .col-actions a {
            color: #495057;
            font-size: 1.1em;
            transition: color 0.2s;
        }

        .col-actions a:hover {
            color: var(--button-color);
        }
        
        .col-actions a[title="ลบ"] {
            color: #dc3545;
        }
        .col-actions a[title="ลบ"]:hover {
            color: #c82333;
        }


        /* ---------------------------------------------------- */
        /* === 5. Modal Style (Form) === */
        /* ---------------------------------------------------- */
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
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 25px;
            width: 90%;
            max-width: 650px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: relative;
            box-sizing: border-box;
        }

        .modal-content h2 {
            color: var(--main-text);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
            margin-bottom: 20px;
            font-size: 1.2em;
        }

        .modal-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .form-group, .form-group-full {
            display: flex;
            flex-direction: column;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-group label,
        .form-group-full label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
            font-size: 0.9em;
        }

        .form-group input[type="text"],
        .form-group-full textarea,
        .form-group textarea /* Added for half-width textarea if needed */ {
            padding: 8px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color 0.3s;
            width: 100%;
            box-sizing: border-box;
            font-size: 0.95em;
        }

        /* กำหนดความสูงเริ่มต้นสำหรับ textarea ที่ไม่ใหญ่เป็นพิเศษ */
        .form-group-full textarea {
            resize: vertical;
            min-height: 80px;
        }


        .form-group input[type="text"]:focus,
        .form-group-full textarea:focus,
        .form-group textarea:focus {
            border-color: var(--button-color);
            box-shadow: 0 0 5px rgba(66, 133, 244, 0.3);
            outline: none;
        }

        .form-actions {
            grid-column: 1 / -1;
            margin-top: 15px;
            text-align: right;
        }

        .form-actions button[type="submit"] {
            padding: 8px 25px;
            background: var(--button-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 500;
            transition: background 0.3s;
        }

        .form-actions button[type="submit"]:hover {
            background: #3367d6;
        }
    </style>

</head>

<body>

    <div class="sidebar">
        <div class="menu-header">
            <span>Menu</span> <i class="fa fa-bars menu-toggle" id="sidebarToggle"></i>
        </div>
        <ul class="menu">
            <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="customers.php"><i class="fa fa-users"></i> <span>ข้อมูลลูกค้า</span></a></li>
            <li><a href="project.php"><i class="fa fa-folder-open"></i> <span>Project</span></a></li>
            <li class="active"><a href="product.php"><i class="fa fa-gears"></i> <span>Product</span></a></li>
            <li><a href="pm_schedule.php"><i class="fa fa-calendar-check-o"></i> <span>PM Schedule</span></a></li>
        </ul>
    </div>
    
    <div class="container">

        <div class="main-content">
            <h1>จัดการ Product</h1>

            <?php if (isset($message) && $message): ?>
                <div class="message-box <?php echo (str_starts_with($message, 'Error') ? 'error-message' : ''); ?>"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="content-box">
                <div class="controls-bar">
                    <form method="GET" class="search-area">
                        <input type="text" name="search_query" placeholder="ค้นหา..."
                            value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </form>
                    <a href="#" class="add-button" onclick="openModal();"><i class="fa fa-plus"></i> เพิ่มข้อมูลใหม่</a>
                </div>

                <div class="table-wrapper">
                    <div class="data-table">
                        <div class="table-header-row table-row">
                            <span>ID</span><span>ลูกค้า ID</span><span>โทรศัพท์</span><span>ที่อยู่</span>
                            <span>ชื่ออุปกรณ์</span><span>S/N</span><span>รายละเอียดซ่อม</span><span>จัดการ</span>
                        </div>

                        <?php if (count($filteredProducts) > 0): ?>
                            <?php foreach ($filteredProducts as $p): ?>
                                <div class="table-data-row table-row">
                                    <span><?= htmlspecialchars($p['product_id'] ?? ''); ?></span>
                                    <span><?= htmlspecialchars($p['customers_id'] ?? ''); ?></span>
                                    <span><?= htmlspecialchars($p['phone'] ?? ''); ?></span>
                                    <span><?= htmlspecialchars($p['address'] ?? ''); ?></span>
                                    <span><?= htmlspecialchars($p['device_name'] ?? ''); ?></span>
                                    <span><?= htmlspecialchars($p['serial_number'] ?? ''); ?></span>
                                    <span
                                        title="<?= htmlspecialchars($p['repair_details'] ?? ''); ?>"><?= htmlspecialchars($p['repair_details'] ?? ''); ?></span>
                                    <span class="col-actions">
                                        <a href="?action=edit&item_id=<?= htmlspecialchars($p['product_id'] ?? ''); ?>" title="แก้ไข"><i
                                                class="fa fa-pencil"></i></a>
                                        <a href="?action=delete&item_id=<?= htmlspecialchars($p['product_id'] ?? ''); ?>"
                                            onclick="return confirm('คุณต้องการลบ Product ID: <?= htmlspecialchars($p['product_id'] ?? ''); ?> ?');"
                                            title="ลบ"><i class="fa fa-trash"></i></a>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="table-row" style="grid-template-columns:1fr;text-align:center;color:#777;">
                                <?php
                                if (!isset($pdo)) {
                                    echo "ไม่พบข้อมูล เนื่องจากไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาตรวจสอบ db.php";
                                } else {
                                    echo "ไม่พบข้อมูลที่ค้นหา";
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div> </div>
    </div>

    <div id="dataModal" class="modal" style="display:<?= $editData ? 'flex' : 'none'; ?>;">
        <div class="modal-content">
            <h2><?= $editData ? "แก้ไขข้อมูล (Product ID: " . htmlspecialchars($editData['product_id'] ?? '') . ")" : "เพิ่มข้อมูลใหม่"; ?>
            </h2>

            <form method="POST" class="modal-form">

                <input type="hidden" name="auto_id" value="<?= htmlspecialchars($editData['product_id'] ?? ""); ?>">

                
                <div class="form-group">
                    <label for="customers_id">ลูกค้า (Customer ID)</label>
                    <input type="text" id="customers_id" name="customers_id" required
                        value="<?= htmlspecialchars($editData['customers_id'] ?? ""); ?>">
                </div>

                <div class="form-group">
                    <label for="phone">โทรศัพท์ (Phone)</label>
                    <input type="text" id="phone" name="phone"
                        value="<?= htmlspecialchars($editData['phone'] ?? ""); ?>">
                </div>

                <div class="form-group">
                    <label for="address">ที่อยู่/หน่วยงาน (Address)</label>
                    <input type="text" id="address" name="address"
                        value="<?= htmlspecialchars($editData['address'] ?? ""); ?>">
                </div>

                <div class="form-group">
                    <label for="device_name">ชื่ออุปกรณ์ (Device Name)</label>
                    <input type="text" id="device_name" name="device_name" required
                        value="<?= htmlspecialchars($editData['device_name'] ?? ""); ?>">
                </div>

                <div class="form-group">
                    <label for="serial_number">เลขซีเรียล (Serial Number)</label>
                    <input type="text" id="serial_number" name="serial_number"
                        value="<?= htmlspecialchars($editData['serial_number'] ?? ""); ?>">
                </div>
                <div class="form-group" style="visibility: hidden;"></div>


                <div class="form-group-full">
                    <label for="repair_details">รายละเอียดการซ่อม (Repair Details)</label>
                    <textarea id="repair_details"
                        name="repair_details" required style="min-height: 120px;"><?= htmlspecialchars($editData['repair_details'] ?? ""); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_form">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="product.js"></script> 

</body>

</html>
