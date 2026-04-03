<?php
// หน้า dashboard ของ user
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once 'db.php'; 

date_default_timezone_set('Asia/Bangkok');
$today = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));

// =========================================================================================
// 1. AJAX API HANDLER (ดึงข้อมูลตามปี)
// =========================================================================================
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'get_chart_data') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $type = $_GET['type'] ?? '';
    $year = (isset($_GET['year']) && is_numeric($_GET['year'])) ? intval($_GET['year']) : '';
    
    $aggregated = [];
    $where_sql = "";

    try {
        if ($type == 'pm') {
            if (!empty($year)) $where_sql = " WHERE YEAR(deliver_work_date) = '$year' ";
            $sql = "SELECT status, COUNT(*) as count FROM pm_project $where_sql GROUP BY status ORDER BY FIELD(status, 2, 3, 1)";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $st_th = ($row['status'] == 2) ? 'กำลังดำเนินการ' : (($row['status'] == 3) ? 'ดำเนินการเสร็จสิ้น' : 'รอการตรวจสอบ');
                $aggregated[$st_th] = (int)$row['count'];
            }
        } 
        elseif ($type == 'service') {
            if (!empty($year)) $where_sql = " WHERE YEAR(d.start_date) = '$year' ";
            $sql = "SELECT d.service_type as status, COUNT(*) as count 
                    FROM service_project_detail d 
                    INNER JOIN service_project_new n ON d.service_id = n.service_id 
                    $where_sql 
                    GROUP BY d.service_type";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rawType = $row['status'];
                    $st_th = ($rawType == 1) ? 'On-site' : (($rawType == 2) ? 'Remote' : 'แจ้ง Subcontractor');
                    $aggregated[$st_th] = (int)$row['count'];
                }
            }
        } 
        elseif ($type == 'product') {
            if (!empty($year)) $where_sql = " WHERE YEAR(start_date) = '$year' ";
            $sql = "SELECT status, COUNT(*) as count FROM product $where_sql GROUP BY status";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $rawStatus = $row['status'];
                $st_th = ($rawStatus == 1) ? 'รอสินค้าจากลูกค้า' : (($rawStatus == 2) ? 'ตรวจสอบ' : (($rawStatus == 3) ? 'รอสินค้าจาก supplier' : 'ส่งคืนลูกค้า'));
                $aggregated[$st_th] = (int)$row['count'];
            }
        }

        echo json_encode([
            'labels' => array_keys($aggregated), 
            'data' => array_values($aggregated),
            'debug_year' => $year 
        ]);

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// =========================================================================================
// 2. PHP PAGE LOAD LOGIC
// =========================================================================================
$pm_total = 0; $service_active = 0; $group_total = 0; $product_total = 0; 

if(isset($conn) && $conn) {
    try {
        $pm_total = $conn->query("SELECT COUNT(*) as total FROM pm_project")->fetch_assoc()['total'];
        $service_active = $conn->query("SELECT COUNT(*) as total FROM service_project_detail")->fetch_assoc()['total'];
        $group_total = $conn->query("SELECT COUNT(*) as total FROM customer_groups")->fetch_assoc()['total'];
        $product_total = $conn->query("SELECT COUNT(*) as total FROM product")->fetch_assoc()['total'];
        
        $result_ma_soon = $conn->query("SELECT m.ma_date, p.project_name, c.customers_name FROM ma_schedule m JOIN pm_project p ON m.pmproject_id = p.pmproject_id LEFT JOIN customers c ON p.customers_id = c.customers_id WHERE m.ma_date BETWEEN '$today' AND '$next_week' ORDER BY m.ma_date ASC LIMIT 5");
        $result_recent_proj = $conn->query("SELECT p.*, c.customers_name FROM pm_project p LEFT JOIN customers c ON p.customers_id = c.customers_id ORDER BY p.pmproject_id DESC LIMIT 8");
    } catch(Exception $e) {}
}

$current_date_display = date('d/m/Y');
$current_year = date('Y');

$year_options = "<option value=''>ทั้งหมด</option>";
for ($i = 0; $i < 10; $i++) {
    $y = $current_year - $i;
    $year_options .= "<option value='$y'>" . ($y + 543) . "</option>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด - Mesh Intelligence</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="CSS/user_dashboard.css">
    
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <div class="dash-header-card">
            <h2>Dashboard</h2>
            <small style="color:#64748b;"><i class="far fa-calendar-alt"></i> ข้อมูลอัปเดตล่าสุด ณ วันที่ <?= $current_date_display; ?></small>
        </div>

        <div class="stats-grid">
            <div class="stat-card" style="background: linear-gradient(135deg, #1e40af, #3b82f6);" onclick="location.href='pmproject_user.php'">
                <div><div class="stat-label">Preventive Maintenance</div><div class="stat-val"><?= number_format($pm_total); ?></div></div>
                <i class="fas fa-project-diagram stat-icon-bg"></i>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #15803d, #22c55e);" onclick="location.href='service_user.php'">
                <div><div class="stat-label">Service</div><div class="stat-val"><?= number_format($service_active); ?></div></div>
                <i class="fas fa-tools stat-icon-bg"></i>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #c2410c, #f97316);" onclick="location.href='product_user.php'">
                <div><div class="stat-label">Product Claim</div><div class="stat-val"><?= number_format($product_total); ?></div></div>
                <i class="fas fa-microchip stat-icon-bg"></i>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #7e22ce, #a855f7);" onclick="location.href='customers_user.php'">
                <div><div class="stat-label">Customers</div><div class="stat-val"><?= number_format($group_total); ?></div></div>
                <i class="fas fa-users stat-icon-bg"></i>
            </div>
        </div>

        <div class="grid-split">
            <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
                <div class="white-card" style="border-top: 5px solid #fbc531;">
                    <div class="card-header-flex">
                        <h3><i class="fas fa-chart-pie" style="color:#fbc531"></i> สถานะโครงการ (Preventive Maintenance)</h3>
                        <select class="year-select" onchange="updateChart('pm', this.value)">
                            <?= $year_options; ?>
                        </select>
                    </div>
                    <div class="chart-container-main">
                        <canvas id="pmStatusChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-inner-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="white-card" style="border-top: 5px solid #3b82f6;">
                        <div class="card-header-flex">
                            <h3><i class="fas fa-tools" style="color:#3b82f6"></i> สถานะบริการ (Service)</h3>
                            <select class="year-select" id="serviceYearFilter" onchange="updateChart('service', this.value)">
                                <?= $year_options; ?>
                            </select>
                        </div>
                        <div class="chart-container-sub"><canvas id="serviceStatusChart"></canvas></div>
                    </div>

                    <div class="white-card" style="border-top: 5px solid #22c55e;">
                        <div class="card-header-flex">
                            <h3><i class="fas fa-microchip" style="color:#22c55e"></i> สถานะซ่อมบำรุง (Product Claim)</h3>
                            <select class="year-select" onchange="updateChart('product', this.value)">
                                <?= $year_options; ?>
                            </select>
                        </div>
                        <div class="chart-container-sub"><canvas id="productStatusChart"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="white-card" style="border-top: 5px solid #dc3545;">
                <h3 style="font-size: 1.2rem; margin-bottom: 15px;"><i class="fas fa-bell" style="color:#dc3545"></i> กำหนดการ PM เร็วๆ นี้</h3>
                <div class="timeline-box">
                    <?php if($result_ma_soon && $result_ma_soon->num_rows > 0): ?>
                        <?php while($row = $result_ma_soon->fetch_assoc()): $ts = strtotime($row['ma_date']); ?>
                        <div class="tl-item">
                            <div class="tl-date"><strong><?= date('d', $ts); ?></strong><span><?= date('M', $ts); ?></span></div>
                            <div class="tl-info" style="min-width: 0;">
                                <strong style="display:block; color:#1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.95rem;"><?= $row['project_name']; ?></strong>
                                <small style="color:#64748b;"><i class="fas fa-user-tie"></i> <?= $row['customers_name']; ?></small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align:center; color:#94a3b8; padding: 40px 0;">
                            <i class="fas fa-calendar-check fa-3x" style="opacity:0.2; margin-bottom:10px;"></i>
                            <p>ไม่มีงาน PM ในช่วงนี้</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="white-card table-section" style="border-top: 5px solid #9b59b6;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin:0; font-size: 1.25rem;"><i class="fas fa-list-alt" style="color:#9b59b6"></i> รายการโครงการล่าสุด</h3>
                <a href="pmproject_user.php" style="font-size: 0.95rem; color: var(--btn-blue); text-decoration: none; font-weight: 600;">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
            </div>
            <div style="overflow-x: auto;">
                <table class="bordered-table">
                    <thead>
                        <tr>
                            <th>ชื่อโครงการ</th>
                            <th>ลูกค้า</th>
                            <th>วันส่งมอบ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($result_recent_proj): while($row = $result_recent_proj->fetch_assoc()): 
                            $st = $row['status'];
                            if ($st == 2) { $st_th = 'กำลังดำเนินการ'; $cls = 'st-progress'; }
                            elseif ($st == 3) { $st_th = 'ดำเนินการเสร็จสิ้น'; $cls = 'st-completed'; }
                            elseif ($st == 1) { $st_th = 'รอการตรวจสอบ'; $cls = 'st-pending'; }
                            else { $st_th = 'อื่นๆ'; $cls = 'st-default'; }
                        ?>
                        <tr>
                            <td style="font-weight: 600; color: #1e293b; font-size: 0.95rem;"><?= mb_strimwidth($row['project_name'], 0, 60, "..."); ?></td>
                            <td><?= $row['customers_name']; ?></td>
                            <td style="white-space:nowrap;"><i class="far fa-calendar-alt" style="color:#94a3b8"></i> <?= date('d/m/Y', strtotime($row['deliver_work_date'])); ?></td>
                            <td><span class="pill-status <?= $cls; ?>"><?= $st_th; ?></span></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="js/user_dashboard.js"></script>
</body>
</html>