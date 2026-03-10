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
    <title>MaintDash</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="CSS/dashboard.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-content-wrapper">
            <div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="header-left-content">
                    <div class="header-icon-box"><i class="fas fa-chart-line"></i></div>
                    <div class="header-text-group">
                        <h2 class="header-main-title">Dashboard</h2>
                        <p class="header-sub-desc">
                            <i class="far fa-calendar-alt me-1"></i> ข้อมูลภาพรวมระบบ ณ วันที่
                            <?php echo date('d/m/Y'); ?>
                        </p>
                    </div>
                </div>
                <div class="header-right-action mt-3 mt-md-0">
                    <button class="btn-add-custom" onclick="window.location.reload();">
                        <i class="fas fa-sync-alt"></i> อัปเดตข้อมูล
                    </button>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stats-card card-primitive" onclick="location.href='pm_project.php'"
                        style="cursor: pointer;">
                        <div class="stats-card-info">
                            <p>Preventive Maintenance</p>
                            <h3 class="stat-val" data-count="<?php echo $pm_total; ?>">0</h3>
                        </div>
                        <div class="card-icon"><i class="fas fa-project-diagram"></i></div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stats-card card-service" onclick="location.href='service_project.php'"
                        style="cursor: pointer;">
                        <div class="stats-card-info">
                            <p>Service</p>
                            <h3 class="stat-val" data-count="<?php echo $service_active; ?>">0</h3>
                        </div>
                        <div class="card-icon"><i class="fas fa-tools"></i></div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stats-card card-product" onclick="location.href='product.php'" style="cursor: pointer;">
                        <div class="stats-card-info">
                            <p>Product Claim</p>
                            <h3 class="stat-val" data-count="<?php echo $product_total; ?>">0</h3>
                        </div>
                        <div class="card-icon"><i class="fas fa-microchip"></i></div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stats-card card-customer" onclick="location.href='customers.php'"
                        style="cursor: pointer;">
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
                    <div class="stats-card card-white p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-gradient-primary me-3 mb-0"
                                    style="width: 45px; height: 45px; font-size: 1.2rem;"><i
                                        class="fas fa-chart-pie"></i></div>
                                <div>
                                    <h4 class="fw-bold text-dark m-0">สถานะโครงการ</h4>
                                    <small class="fw-bold" style="color: #0056b3 !important;">Preventive
                                        Maintenance</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <select class="form-select form-select-sm rounded-pill border-0 shadow-sm px-3"
                                    style="width: 140px; background-color: #f8f9fa;" id="yearPmSelect">
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
                        </div>
                        <div class="chart-wrapper" style="position: relative; height: 300px; width: 100%;">
                            <canvas id="pmStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="stats-card card-white p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-gradient-success me-3 mb-0"
                                    style="width: 45px; height: 45px; font-size: 1.2rem;"><i class="fas fa-tools"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold text-dark m-0">สถานะการเข้าบริการ</h4>
                                    <small class="fw-bold" style="color: #0056b3 !important;">Service</small>
                                </div>
                            </div>
                            <select class="form-select form-select-sm rounded-pill border-0 shadow-sm px-3"
                                style="width: 120px;" id="yearServiceSelect">
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
                                <div class="card-icon bg-gradient-danger me-3 mb-0"
                                    style="width: 45px; height: 45px; font-size: 1.2rem;"><i
                                        class="fas fa-box-open"></i></div>
                                <div>
                                    <h4 class="fw-bold text-dark m-0">สถานะการซ่อมบำรุง</h4>
                                    <small class="fw-bold" style="color: #0056b3 !important;">Product Claim</small>
                                </div>
                            </div>
                            <select class="form-select form-select-sm rounded-pill border-0 shadow-sm px-3"
                                style="width: 120px;" id="yearProductSelect">
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
                                    // เปลี่ยน LIMIT เป็น 10 รายการล่าสุด
                                    $sql_latest = "SELECT p.*, c.customers_name FROM pm_project p 
                           LEFT JOIN customers c ON p.customers_id = c.customers_id 
                           ORDER BY p.pmproject_id DESC LIMIT 10";
                                    $res_latest = $conn->query($sql_latest);

                                    if ($res_latest && $res_latest->num_rows > 0) {
                                        while ($row = $res_latest->fetch_assoc()) {
                                            $st = $row['status'];

                                            // เปลี่ยนจาก $badge_style เป็นการระบุ class แทน
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // 1. กราฟ PM Status
        const ctx = document.getElementById('pmStatusChart').getContext('2d');
        const pmChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($pm_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($pm_data); ?>,
                    backgroundColor: ['#8b5cf6', '#f59e0b', '#10b981', '#455a64'], // ใช้สีเดิมของคุณ
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 16, // <<< เพิ่มบรรทัดนี้
                                weight: '400'
                            },

                            // ส่วนที่เพิ่มเข้าไปเพื่อให้แสดงตัวเลข
                            generateLabels: (chart) => {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: `${label}: ${data.datasets[0].data[i]}`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: 'transparent',
                                    pointStyle: 'circle',
                                    index: i
                                }));
                            }
                        }
                    }
                }
            }
        });

        // 2. กราฟ Service Status
        const serviceCtx = document.getElementById('serviceStatusChart').getContext('2d');
        const serviceChart = new Chart(serviceCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($service_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($service_data); ?>,
                    backgroundColor: ['#2ecc71', '#3498db', '#9b59b6', '#f1c40f'], // ใช้สีเดิมของคุณ
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 16, // <<< เพิ่มบรรทัดนี้
                                weight: '400'
                            },
                            generateLabels: (chart) => {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: `${label}: ${data.datasets[0].data[i]}`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: 'transparent',
                                    pointStyle: 'circle',
                                    index: i
                                }));
                            }
                        }
                    }
                }
            }
        });

        // 3. กราฟ Product Status
        const productCtx = document.getElementById('productStatusChart').getContext('2d');
        const productChart = new Chart(productCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($product_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($product_data); ?>,
                    backgroundColor: ['#e74c3c', '#34495e', '#cddc39', '#95a5a6'], // ใช้สีเดิมของคุณ
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 16,
                                weight: '400'
                            },
                            generateLabels: (chart) => {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: `${label}: ${data.datasets[0].data[i]}`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: 'transparent',
                                    pointStyle: 'circle',
                                    index: i
                                }));
                            }
                        }
                    }

                }
            }
        });

        async function loadChart(type, year, chart) {
            const res = await fetch(`dashboard_chart_data.php?type=${type}&year=${year}`);
            const data = await res.json();
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.data;
            chart.update();
        }

        document.getElementById('yearPmSelect')
            .addEventListener('change', e => {
                loadChart('pm', e.target.value, pmChart);
            });

        document.getElementById('yearServiceSelect')
            .addEventListener('change', e => {
                loadChart('service', e.target.value, serviceChart);
            });

        document.getElementById('yearProductSelect')
            .addEventListener('change', e => {
                loadChart('product', e.target.value, productChart);
            });

        function animateCounters() {
            $('.stat-val').each(function () {
                const $this = $(this);
                const countTo = parseInt($this.attr('data-count')) || 0;

                $({ countNum: 0 }).animate({
                    countNum: countTo
                }, {
                    duration: 1500, // ความเร็ว (1.5 วินาที)
                    easing: 'swing',
                    step: function () {
                        // อัปเดตตัวเลขและใส่ comma (,)
                        $this.text(Math.floor(this.countNum).toLocaleString());
                    },
                    complete: function () {
                        $this.text(this.countNum.toLocaleString());
                    }
                });
            });
        }

        $(document).ready(function () {
            animateCounters();
        });
    </script>
</body>

</html>