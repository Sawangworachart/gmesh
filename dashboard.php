<?php
// =========================================
// หน้า Dashboard (Admin)
// =========================================

session_start();
include_once 'auth.php'; // ตรวจสอบการล็อกอิน
require_once 'db.php';   // เชื่อมต่อฐานข้อมูล

// --- 1. รับค่าปีที่เลือกสำหรับกราฟ (ถ้าไม่มีให้เป็นค่าว่าง) ---
$year_pm      = $_GET['year_pm'] ?? '';
$year_service = $_GET['year_service'] ?? '';
$year_product = $_GET['year_product'] ?? '';

// --- 2. เตรียมเงื่อนไข SQL (WHERE clause) ---
$where_pm = $year_pm ? " WHERE YEAR(deliver_work_date) = '" . $conn->real_escape_string($year_pm) . "' " : "";
$where_service = $year_service ? " WHERE YEAR(d.start_date) = '" . $conn->real_escape_string($year_service) . "' " : "";
$where_product = $year_product ? " WHERE YEAR(start_date) = '" . $conn->real_escape_string($year_product) . "' " : "";

// --- 3. ดึงข้อมูลสถิติรวม (Stats Cards) ---
// 3.1 จำนวน PM ทั้งหมด
$sql_pm_total = "SELECT COUNT(*) as total FROM pm_project";
$pm_total = $conn->query($sql_pm_total)->fetch_assoc()['total'] ?? 0;

// 3.2 จำนวน Service ที่ Active
$sql_service_active = "SELECT COUNT(*) as total FROM service_project_detail d LEFT JOIN service_project_new n ON d.service_id = n.service_id";
$service_active = $conn->query($sql_service_active)->fetch_assoc()['total'] ?? 0;

// 3.3 จำนวนลูกค้า (กลุ่มลูกค้า)
$sql_cust_group = "SELECT COUNT(*) as total FROM customer_groups";
$group_total = $conn->query($sql_cust_group)->fetch_assoc()['total'] ?? 0;

// 3.4 จำนวน Product Claim
$sql_product = "SELECT COUNT(*) as total FROM product";
$product_total = $conn->query($sql_product)->fetch_assoc()['total'] ?? 0;

// --- 4. ดึงข้อมูลสำหรับกราฟ (Chart Data) ---

// 4.1 กราฟสถานะ PM
$sql_pm_status = "SELECT status, COUNT(*) as count FROM pm_project $where_pm GROUP BY status";
$res_pm_status = $conn->query($sql_pm_status);
$status_aggregated = [];

if ($res_pm_status) {
    while ($row = $res_pm_status->fetch_assoc()) {
        $rawStatus = $row['status'];
        // แปลงรหัสสถานะเป็นข้อความภาษาไทย
        $status_th = match ($rawStatus) {
            '2' => 'กำลังดำเนินการ',
            '3' => 'ดำเนินการเสร็จสิ้น',
            '1' => 'รอการตรวจสอบ',
            default => 'อื่นๆ',
        };
        $status_aggregated[$status_th] = ($status_aggregated[$status_th] ?? 0) + $row['count'];
    }
}
$pm_labels = array_keys($status_aggregated);
$pm_data = array_values($status_aggregated);

// 4.2 กราฟสถานะ Service
$sql_service_chart = "SELECT d.service_type as status, COUNT(*) as count FROM service_project_detail d LEFT JOIN service_project_new n ON d.service_id = n.service_id $where_service GROUP BY d.service_type";
$res_service_chart = $conn->query($sql_service_chart);
$service_labels = [];
$service_data = [];

if ($res_service_chart) {
    while ($row = $res_service_chart->fetch_assoc()) {
        $rawType = $row['status'];
        $status_th = match ($rawType) {
            '1' => 'On-site',
            '2' => 'Remote',
            '3' => 'แจ้ง Subcontractor',
            default => 'อื่นๆ',
        };
        $service_labels[] = $status_th;
        $service_data[] = $row['count'];
    }
}

// 4.3 กราฟสถานะ Product
$sql_product_chart = "SELECT status, COUNT(*) as count FROM product $where_product GROUP BY status";
$res_product_chart = $conn->query($sql_product_chart);
$product_labels = [];
$product_data = [];

if ($res_product_chart) {
    while ($row = $res_product_chart->fetch_assoc()) {
        $rawStatus = $row['status'];
        $status_th = match ($rawStatus) {
            '1' => 'รอสินค้าจากลูกค้า',
            '2' => 'ตรวจสอบ',
            '3' => 'รอสินค้าจาก supplier',
            '4' => 'ส่งคืนลูกค้า',
            default => 'อื่นๆ',
        };
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
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS (แยกไฟล์) -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-content-wrapper">
            <!-- Header -->
            <div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="header-left-content">
                    <div class="header-icon-box"><i class="fas fa-chart-line"></i></div>
                    <div class="header-text-group">
                        <h2 class="header-main-title">Dashboard</h2>
                        <p class="header-sub-desc">
                            <i class="far fa-calendar-alt me-1"></i> ข้อมูลภาพรวมระบบ ณ วันที่ <?php echo date('d/m/Y'); ?>
                        </p>
                    </div>
                </div>
                <div class="header-right-action mt-3 mt-md-0">
                    <button class="btn-add-custom" onclick="window.location.reload();">
                        <i class="fas fa-sync-alt"></i> อัปเดตข้อมูล
                    </button>
                </div>
            </div>

            <!-- Stats Cards Row 1 -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stats-card card-primitive" onclick="location.href='pm_project.php'">
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

            <!-- Charts Row 1: PM Status -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="stats-card card-white p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-gradient-primary me-3 mb-0" style="width: 45px; height: 45px; font-size: 1.2rem;"><i class="fas fa-chart-pie"></i></div>
                                <div>
                                    <h4 class="fw-bold text-dark m-0">สถานะโครงการ</h4>
                                    <small class="fw-bold" style="color: #0056b3 !important;">Preventive Maintenance</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <select class="form-select form-select-sm rounded-pill border-0 shadow-sm px-3" style="width: 140px; background-color: #f8f9fa;" id="yearPmSelect">
                                    <?php
                                    $currentYear = date('Y');
                                    echo "<option value='' " . ($year_pm === '' ? 'selected' : '') . ">ดูทั้งหมด</option>";
                                    for ($i = 0; $i < 10; $i++) {
                                        $y = $currentYear - $i;
                                        echo "<option value='$y' " . ($year_pm == $y ? 'selected' : '') . ">" . ($y + 543) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="chart-wrapper" style="position: relative; height: 300px; width: 100%;">
                            <canvas id="pmStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2: Service & Product -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="stats-card card-white p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-gradient-success me-3 mb-0" style="width: 45px; height: 45px; font-size: 1.2rem;"><i class="fas fa-tools"></i></div>
                                <div>
                                    <h4 class="fw-bold text-dark m-0">สถานะการเข้าบริการ</h4>
                                    <small class="fw-bold" style="color: #0056b3 !important;">Service</small>
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
                        <div class="chart-wrapper" style="position: relative; height: 300px; width: 100%;">
                            <canvas id="serviceStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="stats-card card-white p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-gradient-danger me-3 mb-0" style="width: 45px; height: 45px; font-size: 1.2rem;"><i class="fas fa-box-open"></i></div>
                                <div>
                                    <h4 class="fw-bold text-dark m-0">สถานะการซ่อมบำรุง</h4>
                                    <small class="fw-bold" style="color: #0056b3 !important;">Product Claim</small>
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
                        <div class="chart-wrapper" style="position: relative; height: 300px; width: 100%;">
                            <canvas id="productStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Projects Table -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="stats-card card-white p-4 h-100">
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
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%;">ชื่อโครงการ</th>
                                        <th style="width: 25%;">ลูกค้า</th>
                                        <th style="width: 15%;">สถานะ</th>
                                        <th style="width: 20%; text-align: right;">วันส่งมอบงาน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // ดึงข้อมูล 10 รายการล่าสุด
                                    $sql_latest = "SELECT p.*, c.customers_name FROM pm_project p 
                                                   LEFT JOIN customers c ON p.customers_id = c.customers_id 
                                                   ORDER BY p.pmproject_id DESC LIMIT 10";
                                    $res_latest = $conn->query($sql_latest);

                                    if ($res_latest && $res_latest->num_rows > 0) {
                                        while ($row = $res_latest->fetch_assoc()) {
                                            $st = $row['status'];
                                            // กำหนด Class ของ Badge ตามสถานะ
                                            $badge_class = match ($st) {
                                                '1' => 'badge-pending',
                                                '2' => 'badge-processing',
                                                '3' => 'badge-completed',
                                                default => 'badge-secondary',
                                            };
                                            $status_text = match ($st) {
                                                '1' => 'รอการตรวจสอบ',
                                                '2' => 'กำลังดำเนินการ',
                                                '3' => 'ดำเนินการเสร็จสิ้น',
                                                default => 'อื่นๆ',
                                            };

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
            </div>
        </div>
    </div>

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- ส่งข้อมูล PHP ไปให้ JS ใช้งาน -->
    <script>
        window.dashboardData = {
            pm: {
                labels: <?php echo json_encode($pm_labels); ?>,
                data: <?php echo json_encode($pm_data); ?>
            },
            service: {
                labels: <?php echo json_encode($service_labels); ?>,
                data: <?php echo json_encode($service_data); ?>
            },
            product: {
                labels: <?php echo json_encode($product_labels); ?>,
                data: <?php echo json_encode($product_data); ?>
            }
        };
    </script>
    
    <!-- Custom JS (แยกไฟล์) -->
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
