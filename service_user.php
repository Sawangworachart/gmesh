<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
include_once 'auth.php';
include_once 'db.php';

$services = [];
$stats = ['total' => 0, 'onsite' => 0, 'remote' => 0, 'subcon' => 0];
$project_filter_opt = [];

if (isset($conn) && $conn) {
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
            while ($row = mysqli_fetch_assoc($result)) {
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
    } catch (Exception $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Project - G-Mesh</title>
    <link rel="icon" type="image/png" href="images/logomaintdash1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/service_project.css">
</head>
<body>
    <?php include 'sidebar_user.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1 class="page-title">Service</h1>
            <p class="page-description">ระบบบันทึกข้อมูลการแจ้งซ่อมและประวัติการเข้าบริการลูกค้า</p>
        </header>

        <div class="status-cards">
            <div class="status-card card-total">
                <div class="card-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="card-info">
                    <p>งานทั้งหมด</p>
                    <span><?= $stats['total'] ?></span>
                </div>
            </div>
            <div class="status-card card-onsite">
                <div class="card-icon"><i class="fas fa-car-side"></i></div>
                <div class="card-info">
                    <p>On-site</p>
                    <span><?= $stats['onsite'] ?></span>
                </div>
            </div>
            <div class="status-card card-remote">
                <div class="card-icon"><i class="fas fa-desktop"></i></div>
                <div class="card-info">
                    <p>Remote</p>
                    <span><?= $stats['remote'] ?></span>
                </div>
            </div>
            <div class="status-card card-sub">
                <div class="card-icon"><i class="fas fa-user-friends"></i></div>
                <div class="card-info">
                    <p>Subcontractor</p>
                    <span><?= $stats['subcon'] ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>รายการแจ้งซ่อมทั้งหมด</h2>
                <div class="card-toolbar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="ค้นหา...">
                    </div>
                    <div class="filter-box">
                        <i class="fas fa-filter"></i>
                        <select id="projectFilter">
                            <option value="">-- ทุกโครงการ --</option>
                            <?php foreach ($project_filter_opt as $proj): ?>
                                <option value="<?= htmlspecialchars($proj) ?>"><?= htmlspecialchars($proj) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="table" id="serviceTable">
                    <thead>
                        <tr>
                            <th>เลขที่โครงการ</th>
                            <th>ชื่อโครงการ</th>
                            <th>ลูกค้า</th>
                            <th>อุปกรณ์ / S/N</th>
                            <th class="text-center">สถานะ</th>
                            <th>วันที่เริ่ม / สิ้นสุด</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($services)): ?>
                            <tr>
                                <td colspan="7" class="text-center">ไม่มีข้อมูล</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($services as $row):
                                $badgeClass = '';
                                switch ($row['status']) {
                                    case 'On-site': $badgeClass = 'badge-onsite'; break;
                                    case 'Remote': $badgeClass = 'badge-remote'; break;
                                    case 'Subcontractor': $badgeClass = 'badge-sub'; break;
                                }
                            ?>
                            <tr>
                                <td data-label="เลขที่โครงการ"><?= $row['ref_number'] ?></td>
                                <td data-label="ชื่อโครงการ">
                                    <div class="text-bold"><?= htmlspecialchars($row['project_name']) ?></div>
                                    <div class="text-muted text-small" title="<?= htmlspecialchars($row['symptom']) ?>">
                                        อาการ: <?= mb_strimwidth(htmlspecialchars($row['symptom']), 0, 40, "...") ?>
                                    </div>
                                </td>
                                <td data-label="ลูกค้า">
                                    <div class="text-bold"><?= htmlspecialchars($row['customer']) ?></div>
                                    <div class="text-muted text-small"><?= htmlspecialchars($row['department']) ?></div>
                                </td>
                                <td data-label="อุปกรณ์ / S/N">
                                    <div class="text-bold"><?= htmlspecialchars($row['device_model']) ?></div>
                                    <div class="text-muted text-small">S/N: <?= htmlspecialchars($row['serial_number']) ?></div>
                                </td>
                                <td data-label="สถานะ" class="text-center">
                                    <span class="badge-status <?= $badgeClass ?>"><?= $row['status_th'] ?></span>
                                </td>
                                <td data-label="วันที่เริ่ม / สิ้นสุด">
                                    <div class="text-small">
                                        <span class="text-success"><i class="fas fa-play-circle"></i> <?= $row['start_date'] ?></span>
                                    </div>
                                    <div class="text-small">
                                        <span class="text-danger"><i class="fas fa-stop-circle"></i> <?= $row['end_date'] ?></span>
                                    </div>
                                </td>
                                <td data-label="จัดการ" class="text-center">
                                    <button class="btn btn-icon btn-view" onclick='viewDetail(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-info-circle"></i> รายละเอียดงานบริการ</h3>
                <button class="modal-close" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="v_content">
                <!-- Content will be injected by JavaScript -->
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/service_user.js"></script>
</body>
</html>
