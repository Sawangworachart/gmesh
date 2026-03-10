<?php
// หน้า service ของ user
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
include_once 'auth.php'; 
include_once 'db.php'; 

$services = [];
$stats = ['total' => 0, 'onsite' => 0, 'remote' => 0, 'subcon' => 0];
$project_filter_opt = [];

if(isset($conn) && $conn) {
    try {
        $cnt_sql = "SELECT service_type, COUNT(*) as count FROM service_project_detail GROUP BY service_type";
        $cnt_query = mysqli_query($conn, $cnt_sql);
        
        if ($cnt_query) {
            while ($row = mysqli_fetch_assoc($cnt_query)) {
                $count = (int)$row['count'];
                $type = (int)$row['service_type'];
                $stats['total'] += $count;
                if ($type == 1) $stats['onsite'] += $count;
                elseif ($type == 2) $stats['remote'] += $count;
                elseif ($type == 3) $stats['subcon'] += $count;
            }
        }

        $p_sql = "SELECT DISTINCT project_name FROM service_project_new WHERE project_name != '' ORDER BY project_name ASC";
        $p_query = mysqli_query($conn, $p_sql);
        if ($p_query) {
            while ($row = mysqli_fetch_assoc($p_query)) {
                $project_filter_opt[] = $row['project_name'];
            }
        }

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
    } catch (Exception $e) { }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Project - Mesh Intelligence</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/service_user.css">
</head>
<body>
    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="page-header-card">
            <h1 style="margin:0; font-size:1.8rem;">Service</h1>
            <p style="margin:5px 0 0; color:#64748b;">ระบบบันทึกข้อมูลการแจ้งซ่อมและประวัติการเข้าบริการลูกค้า</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card grad-total">
                <div class="stat-label">งานทั้งหมด</div>
                <div class="stat-val"><?= $stats['total'] ?></div>
                <i class="fas fa-clipboard-list" style="position:absolute; right:10px; bottom:-10px; font-size:5rem; opacity:0.15;"></i>
            </div>
            <div class="stat-card grad-onsite">
                <div class="stat-label">On-site</div>
                <div class="stat-val"><?= $stats['onsite'] ?></div>
                <i class="fas fa-car-side" style="position:absolute; right:10px; bottom:-10px; font-size:5rem; opacity:0.15;"></i>
            </div>
            <div class="stat-card grad-remote">
                <div class="stat-label">Remote</div>
                <div class="stat-val"><?= $stats['remote'] ?></div>
                <i class="fas fa-desktop" style="position:absolute; right:10px; bottom:-10px; font-size:5rem; opacity:0.15;"></i>
            </div>
            <div class="stat-card grad-subcon">
                <div class="stat-label">แจ้ง Subcontractor</div>
                <div class="stat-val"><?= $stats['subcon'] ?></div>
                <i class="fas fa-user-friends" style="position:absolute; right:10px; bottom:-10px; font-size:5rem; opacity:0.15;"></i>
            </div>
        </div>

        <div class="toolbar-container">
            <div class="search-pill">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาข้อมูล..." onkeyup="filterUserTable()">
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
                            <th width="12%">วันที่เริ่ม / วันที่สิ้นสุด</th>
                            <th width="5%" class="text-center">จัดการ</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $row): 
                            $badgeClass = ($row['status'] == 'Remote') ? 'st-remote' : (($row['status'] == 'Subcontractor') ? 'st-subcon' : 'st-onsite');
                        ?>
                        <tr>
                            <td data-label="เลขที่โครงการ"><?= $row['ref_number'] ?></td>
                            <td data-label="ชื่อโครงการ"><strong><?= htmlspecialchars($row['project_name']) ?></strong></td>
                            <td data-label="อาการเสีย" style="color:#64748b; font-size:0.85rem;"><?= mb_strimwidth($row['symptom'], 0, 45, "...") ?></td>
                            <td data-label="ลูกค้า"><?= htmlspecialchars($row['customer']) ?><br><small style="color:#94a3b8;"><?= htmlspecialchars($row['department']) ?></small></td>
                            <td data-label="อุปกรณ์ / S/N">
                                <div style="font-weight: 500;"><?= htmlspecialchars($row['device_model']) ?></div>
                                <div style="font-size: 0.8rem; color: #64748b;">S/N: <?= htmlspecialchars($row['serial_number']) ?></div>
                            </td>
                            <td data-label="สถานะ" style="text-align: center;"><span class="status-pill <?= $badgeClass ?>"><?= $row['status_th'] ?></span></td>
                            <td data-label="สัญญา">-</td>
                            <td data-label="วันที่เริ่ม / สิ้นสุด">
                                <div class="date-info">
                                    <span style="color:#2563eb; font-size:0.9rem;"><i class="fas fa-play"></i> <?= $row['start_date'] ?></span><br>
                                    <span style="color:#dc2626; font-size:0.9rem;"><i class="fas fa-flag"></i> <?= $row['end_date'] ?></span>
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

    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="margin:0;"><i class="fas fa-info-circle"></i> รายละเอียดงานบริการ</h3>
                <span style="cursor:pointer; font-size:1.8rem;" onclick="closeViewModal()">&times;</span>
            </div>
            <div class="modal-body-scroll" id="v_content"></div>
        </div>
    </div>
<script src="js/service_user.js"></script>
</body>
</html>