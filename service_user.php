<?php
/**
 * ไฟล์: service_user.php
 * คำอธิบาย: หน้าแสดงข้อมูลงานบริการ (Service) สำหรับผู้ใช้งานทั่วไป (User View)
 * แสดงรายการแจ้งซ่อม สถานะ และประวัติการให้บริการ
 */

session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once 'includes/auth.php'; 
include_once 'includes/db.php'; 

// ตัวแปรสำหรับเก็บข้อมูล
$services = [];
$stats = ['total' => 0, 'onsite' => 0, 'remote' => 0, 'subcon' => 0];
$project_filter_opt = [];

if(isset($conn) && $conn) {
    try {
        // 1. ดึงข้อมูลสถิติ (Count by Service Type)
        $cnt_sql = "SELECT service_type, COUNT(*) as count FROM service_project_detail GROUP BY service_type";
        $cnt_query = mysqli_query($conn, $cnt_sql);
        
        if ($cnt_query) {
            while ($row = mysqli_fetch_assoc($cnt_query)) {
                $count = (int)$row['count'];
                $type = (int)$row['service_type'];
                $stats['total'] += $count;
                // 1=Onsite, 2=Remote, 3=Subcontractor
                if ($type == 1) $stats['onsite'] += $count;
                elseif ($type == 2) $stats['remote'] += $count;
                elseif ($type == 3) $stats['subcon'] += $count;
            }
        }

        // 2. ดึงรายชื่อโครงการสำหรับ Filter Dropdown
        $p_sql = "SELECT DISTINCT project_name FROM service_project_new WHERE project_name != '' ORDER BY project_name ASC";
        $p_query = mysqli_query($conn, $p_sql);
        if ($p_query) {
            while ($row = mysqli_fetch_assoc($p_query)) {
                $project_filter_opt[] = $row['project_name'];
            }
        }

        // 3. ดึงข้อมูลรายการ Service ทั้งหมด
        $sql = "SELECT s.service_id, s.project_name, d.start_date, d.end_date, d.service_type, d.equipment, 
                       d.`s/n` as sn, d.number, d.symptom, d.action_taken, d.file_path, c.customers_name, c.agency
                FROM service_project_new s
                LEFT JOIN service_project_detail d ON s.service_id = d.service_id
                LEFT JOIN customers c ON d.customers_id = c.customers_id
                ORDER BY d.start_date DESC, s.service_id DESC";

        $result = mysqli_query($conn, $sql);
        if ($result) {
            while($row = mysqli_fetch_assoc($result)) {
                $st_type = (int)$row['service_type'];
                // กำหนดสถานะ (Text ภาษาอังกฤษและไทย)
                $st_raw = ($st_type == 2) ? 'Remote' : (($st_type == 3) ? 'Subcontractor' : 'On-site');
                $st_th = ($st_type == 2) ? 'Remote' : (($st_type == 3) ? 'แจ้ง Subcontractor' : 'On-site');

                $services[] = [
                    'id' => $row['service_id'], 
                    'start_date' => $row['start_date'] ? date('d/m/Y', strtotime($row['start_date'])) : '-',
                    'end_date' => $row['end_date'] ? date('d/m/Y', strtotime($row['end_date'])) : '-',
                    'customer' => $row['customers_name'] ?? 'ไม่ระบุ',
                    'department' => $row['agency'] ?? '-',
                    'project_name' => $row['project_name'] ?? 'ไม่ระบุโครงการ',
                    'device_model' => $row['equipment'] ?? '-',
                    'serial_number' => $row['sn'] ?? '-',
                    'ref_number' => (!empty($row['number'])) ? htmlspecialchars($row['number']) : '-',
                    'symptom' => $row['symptom'] ?? '-',
                    'solution' => $row['action_taken'] ?? 'รอดำเนินการ',
                    'file_path' => $row['file_path'] ?? '', 
                    'status' => $st_raw,
                    'status_th' => $st_th
                ];
            }
        }
    } catch (Exception $e) { 
        // Silent fail or log error
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Project - Mesh Intelligence</title>
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logomaintdash1.png">
    
    <!-- External Libs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/service_user.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar_user.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="page-header-card">
            <h1>Service Dashboard</h1>
            <p>ระบบบันทึกข้อมูลการแจ้งซ่อมและประวัติการเข้าบริการลูกค้า</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card grad-total">
                <div class="stat-label">งานทั้งหมด</div>
                <div class="stat-val"><?= number_format($stats['total']) ?></div>
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-card grad-onsite">
                <div class="stat-label">On-site</div>
                <div class="stat-val"><?= number_format($stats['onsite']) ?></div>
                <i class="fas fa-car-side"></i>
            </div>
            <div class="stat-card grad-remote">
                <div class="stat-label">Remote</div>
                <div class="stat-val"><?= number_format($stats['remote']) ?></div>
                <i class="fas fa-desktop"></i>
            </div>
            <div class="stat-card grad-subcon">
                <div class="stat-label">แจ้ง Subcontractor</div>
                <div class="stat-val"><?= number_format($stats['subcon']) ?></div>
                <i class="fas fa-user-friends"></i>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar-container">
            <div class="search-pill">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อลูกค้า, อุปกรณ์, หรือเลขที่โครงการ..." onkeyup="filterUserTable()">
            </div>
            <div class="filter-pill">
                <i class="fas fa-filter"></i>
                <select id="projectFilter" onchange="filterUserTable()">
                    <option value="">-- ดูทุกโครงการ --</option>
                    <?php foreach ($project_filter_opt as $proj): ?>
                        <option value="<?= htmlspecialchars($proj) ?>"><?= htmlspecialchars($proj) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="scroll-area">
                <table class="bordered-table" id="serviceTable">
                    <thead>
                        <tr>
                            <th width="10%">เลขที่โครงการ</th> 
                            <th width="18%">ชื่อโครงการ</th>
                            <th width="15%">อาการเสีย</th>
                            <th width="15%">ลูกค้า</th>
                            <th width="12%">อุปกรณ์ / S/N</th>
                            <th width="10%" style="text-align: center;">สถานะ</th>
                            <th width="10%">สัญญา</th>
                            <th width="12%">ระยะเวลา</th>
                            <th width="5%" class="text-center">ดูข้อมูล</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $row): 
                            $badgeClass = ($row['status'] == 'Remote') ? 'st-remote' : (($row['status'] == 'Subcontractor') ? 'st-subcon' : 'st-onsite');
                        ?>
                        <tr>
                            <td data-label="เลขที่โครงการ" style="font-weight:600; color:#475569;"><?= $row['ref_number'] ?></td>
                            <td data-label="ชื่อโครงการ"><div style="font-weight:600; color:#1e293b;"><?= htmlspecialchars($row['project_name']) ?></div></td>
                            <td data-label="อาการเสีย" style="color:#64748b; font-size:0.85rem;"><?= mb_strimwidth($row['symptom'], 0, 45, "...") ?></td>
                            <td data-label="ลูกค้า">
                                <div style="font-weight:600;"><?= htmlspecialchars($row['customer']) ?></div>
                                <small style="color:#94a3b8;"><?= htmlspecialchars($row['department']) ?></small>
                            </td>
                            <td data-label="อุปกรณ์ / S/N">
                                <div style="font-weight: 500; color:#2563eb;"><?= htmlspecialchars($row['device_model']) ?></div>
                                <div style="font-size: 0.8rem; color: #64748b; font-family:monospace;">S/N: <?= htmlspecialchars($row['serial_number']) ?></div>
                            </td>
                            <td data-label="สถานะ" style="text-align: center;">
                                <span class="status-pill <?= $badgeClass ?>"><?= $row['status_th'] ?></span>
                            </td>
                            <td data-label="สัญญา">-</td>
                            <td data-label="วันที่เริ่ม / สิ้นสุด">
                                <div class="date-info">
                                    <span style="color:#2563eb;"><i class="fas fa-play"></i> <?= $row['start_date'] ?></span>
                                    <span style="color:#dc2626;"><i class="fas fa-flag"></i> <?= $row['end_date'] ?></span>
                                </div>
                            </td>
                            <td data-label="จัดการ">
                                <button class="btn-view" onclick="viewDetail(<?= htmlspecialchars(json_encode($row)) ?>)">
                                    <i class="far fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Viewing Details -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> รายละเอียดงานบริการ</h3>
                <span class="close-btn" onclick="closeViewModal()"><i class="fas fa-times"></i></span>
            </div>
            <div class="modal-body-scroll" id="v_content">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="assets/js/service_user.js"></script>
</body>
</html>
