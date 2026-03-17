<?php
// หน้า dashboard ของ admin
session_start();
include_once 'auth.php';
require_once 'db.php';

// 1. รับค่าแยกกัน 3 ตัวแปร (ถ้าไม่มีค่าส่งมา ให้เป็นค่าว่าง)
$year_pm = isset($_GET['year_pm']) ? $_GET['year_pm'] : '';
$year_service = isset($_GET['year_service']) ? $_GET['year_service'] : '';
$year_product = isset($_GET['year_product']) ? $_GET['year_product'] : '';

// 2. เตรียมเงื่อนไข SQL แยกกันสำหรับ 3 กราฟ
$where_pm = "";
$where_service = "";
$where_product = "";

// สร้างเงื่อนไขสำหรับ PM
if ($year_pm !== '') {
    $esc_pm = $conn->real_escape_string($year_pm);
    $where_pm = " WHERE YEAR(deliver_work_date) = '$esc_pm' ";
}

// สร้างเงื่อนไขสำหรับ Service
if ($year_service !== '') {
    $esc_sv = $conn->real_escape_string($year_service);
    $where_service = " WHERE YEAR(d.start_date) = '$esc_sv' ";
}

// สร้างเงื่อนไขสำหรับ Product
if ($year_product !== '') {
    $esc_pd = $conn->real_escape_string($year_product);
    $where_product = " WHERE YEAR(start_date) = '$esc_pd' ";
}

// -------------------------------------------------------
// 1.1 ดึงข้อมูลสำหรับ Stats Cards
// -------------------------------------------------------
$sql_pm_total = "SELECT COUNT(*) as total FROM pm_project";
$res_pm = $conn->query($sql_pm_total);
$pm_total = $res_pm ? $res_pm->fetch_assoc()['total'] : 0;

$sql_service_active = "
SELECT COUNT(*) as total 
FROM service_project_detail d
LEFT JOIN service_project_new n ON d.service_id = n.service_id
";
$res_service = $conn->query($sql_service_active);
$service_active = $res_service ? $res_service->fetch_assoc()['total'] : 0;

$sql_cust_group = "SELECT COUNT(*) as total FROM customer_groups";
$res_cust_group = $conn->query($sql_cust_group);
$group_total = $res_cust_group ? $res_cust_group->fetch_assoc()['total'] : 0;

$sql_product = "SELECT COUNT(*) as total FROM product";
$res_product = $conn->query($sql_product);
$product_total = $res_product ? $res_product->fetch_assoc()['total'] : 0;

// -------------------------------------------------------
// 1.2 Chart Data (สถานะโครงการ PM - กรองตามปี)
// -------------------------------------------------------
$sql_pm_status = "SELECT status, COUNT(*) as count FROM pm_project $where_pm GROUP BY status";
$res_pm_status = $conn->query($sql_pm_status);

$status_aggregated = [];
if ($res_pm_status) {
    while ($row = $res_pm_status->fetch_assoc()) {
        $rawStatus = $row['status'];
        // แปลงสถานะตัวเลขเป็นข้อความตาม Database Comment
        if ($rawStatus == 2)
            $status_th = 'กำลังดำเนินการ';
        elseif ($rawStatus == 3)
            $status_th = 'ดำเนินการเสร็จสิ้น';
        elseif ($rawStatus == 1)
            $status_th = 'รอการตรวจสอบ';
        else
            $status_th = 'อื่นๆ';

        if (!isset($status_aggregated[$status_th])) {
            $status_aggregated[$status_th] = 0;
        }
        $status_aggregated[$status_th] += $row['count'];
    }
}
$pm_labels = array_keys($status_aggregated);
$pm_data = array_values($status_aggregated);

// --- ดึงข้อมูลกราฟ Service Project (ใช้ตาราง service_project_new) ---
$sql_service_chart = "
SELECT d.service_type as status, COUNT(*) as count
FROM service_project_detail d
LEFT JOIN service_project_new n ON d.service_id = n.service_id
$where_service
GROUP BY d.service_type
";
$res_service_chart = $conn->query($sql_service_chart);
$service_labels = [];
$service_data = [];

if ($res_service_chart) {
    while ($row = $res_service_chart->fetch_assoc()) {
        $rawType = $row['status'];
        if ($rawType == 1)
            $status_th = 'On-site';
        elseif ($rawType == 2)
            $status_th = 'Remote';
        elseif ($rawType == 3)
            $status_th = 'แจ้ง Subcontractor';
        else
            $status_th = 'อื่นๆ';

        $service_labels[] = $status_th;
        $service_data[] = $row['count'];
    }
}

// --- ดึงข้อมูลกราฟ Product ---
$sql_product_chart = "SELECT status, COUNT(*) as count FROM product $where_product GROUP BY status";
$res_product_chart = $conn->query($sql_product_chart);
$product_labels = [];
$product_data = [];

if ($res_product_chart) {
    while ($row = $res_product_chart->fetch_assoc()) {
        $rawStatus = $row['status'];
        if ($rawStatus == 1)
            $status_th = 'รอสินค้าจากลูกค้า';
        elseif ($rawStatus == 2)
            $status_th = 'ตรวจสอบ';
        elseif ($rawStatus == 3)
            $status_th = 'รอสินค้าจาก supplier';
        elseif ($rawStatus == 4)
            $status_th = 'ส่งคืนลูกค้า';
        else
            $status_th = 'อื่นๆ';

        $product_labels[] = $status_th;
        $product_data[] = $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - Dashboard</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Dashboard</h2>
                <p class="page-subtitle">
                    <i class="far fa-calendar-alt me-1"></i> ข้อมูลภาพรวมระบบ ณ วันที่ <?php echo date('d/m/Y'); ?>
                </p>
            </div>
            <button class="btn btn-primary" onclick="window.location.reload();">
                <i class="fas fa-sync-alt"></i> อัปเดตข้อมูล
            </button>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stats-card card-pm" onclick="location.href='pm_project.php'">
                    <div class="stats-card-info">
                        <p>Preventive Maintenance</p>
                        <h3 class="stat-val" data-count="<?php echo $pm_total; ?>">0</h3>
                    </div>
                    <div class="card-icon"><i class="fas fa-project-diagram"></i></div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stats-card card-service" onclick="location.href='service_project.php'">
                    <div class="stats-card-info">
                        <p>Service</p>
                        <h3 class="stat-val" data-count="<?php echo $service_active; ?>">0</h3>
                    </div>
                    <div class="card-icon"><i class="fas fa-tools"></i></div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stats-card card-product" onclick="location.href='product.php'">
                    <div class="stats-card-info">
                        <p>Product Claim</p>
                        <h3 class="stat-val" data-count="<?php echo $product_total; ?>">0</h3>
                    </div>
                    <div class="card-icon"><i class="fas fa-microchip"></i></div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stats-card card-customer" onclick="location.href='customers.php'">
                    <div class="stats-card-info">
                        <p>Customers</p>
                        <h3 class="stat-val" data-count="<?php echo $group_total; ?>">0</h3>
                    </div>
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card chart-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <div class="card-icon icon-pm me-3"><i class="fas fa-chart-pie"></i></div>
                            <div>
                                <h4 class="fw-bold text-dark m-0">สถานะโครงการ</h4>
                                <small class="text-muted">Preventive Maintenance</small>
                            </div>
                        </div>
                        <select class="form-select form-select-sm rounded-pill border-0 shadow-sm px-3" style="width: 140px;" id="yearPmSelect">
                            <?php
                            $currentYear = date('Y');
                            $sel_pm = $year_pm;
                            echo "<option value='' " . ($sel_pm === '' ? 'selected' : '') . ">ดูทั้งหมด</option>";
                            for ($i = 0; $i < 10; $i++) {
                                $y = $currentYear - $i;
                                echo "<option value='$y' " . ($sel_pm == $y ? 'selected' : '') . ">" . ($y + 543) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="pmStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card chart-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <div class="card-icon icon-service me-3"><i class="fas fa-tools"></i></div>
                            <div>
                                <h4 class="fw-bold text-dark m-0">สถานะการเข้าบริการ</h4>
                                <small class="text-muted">Service</small>
                            </div>
                        </div>
                        <select class="form-select form-select-sm rounded-pill border-0 shadow-sm px-3" style="width: 120px;" id="yearServiceSelect">
                            <option value="">ดูทั้งหมด</option>
                            <?php
                            for ($i = 0; $i < 10; $i++) {
                                $y = $currentYear - $i;
                                echo "<option value='$y' " . ($year_service == $y ? 'selected' : '') . ">" . ($y + 543) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="serviceStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card chart-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <div class="card-icon icon-product me-3"><i class="fas fa-box-open"></i></div>
                            <div>
                                <h4 class="fw-bold text-dark m-0">สถานะการซ่อมบำรุง</h4>
                                <small class="text-muted">Product Claim</small>
                            </div>
                        </div>
                        <select class="form-select form-select-sm rounded-pill border-0 shadow-sm px-3" style="width: 120px;" id="yearProductSelect">
                            <option value="">ดูทั้งหมด</option>
                            <?php
                            for ($i = 0; $i < 10; $i++) {
                                $y = $currentYear - $i;
                                echo "<option value='$y' " . ($year_product == $y ? 'selected' : '') . ">" . ($y + 543) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="productStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 style="margin:0; font-size: 1.5rem; font-weight: 700;">
                    <i class="fas fa-list-alt text-primary me-2"></i> รายการโครงการล่าสุด
                </h3>
                <a href="pm_project.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    ดูทั้งหมด <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40%;">ชื่อโครงการ</th>
                            <th style="width: 25%;">ลูกค้า</th>
                            <th style="width: 15%;">สถานะ</th>
                            <th style="width: 20%; text-align: right;">วันส่งมอบงาน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_latest = "SELECT p.*, c.customers_name FROM pm_project p 
                       LEFT JOIN customers c ON p.customers_id = c.customers_id 
                       ORDER BY p.pmproject_id DESC LIMIT 10";
                        $res_latest = $conn->query($sql_latest);

                        if ($res_latest && $res_latest->num_rows > 0) {
                            while ($row = $res_latest->fetch_assoc()) {
                                $st = $row['status'];

                                if ($st == 1) {
                                    $status_text = 'รอการตรวจสอบ';
                                    $badge_class = 'badge-pending';
                                } elseif ($st == 2) {
                                    $status_text = 'กำลังดำเนินการ';
                                    $badge_class = 'badge-processing';
                                } elseif ($st == 3) {
                                    $status_text = 'ดำเนินการเสร็จสิ้น';
                                    $badge_class = 'badge-completed';
                                }

                                echo "<tr>
                    <td>" . htmlspecialchars($row['project_name']) . "</td>
                    <td>" . htmlspecialchars($row['customers_name']) . "</td>
                   <td><span class='badge rounded-pill $badge_class' style='padding: 5px 15px;'>$status_text</span></td>
                    <td class='text-end'>" . date('d/m/Y', strtotime($row['deliver_work_date'])) . "</td>
                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center py-4 text-muted'>ไม่พบข้อมูลโครงการล่าสุด</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard.js"></script>
</body>

</html>