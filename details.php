<?php 
/**
 * ไฟล์: details.php
 * คำอธิบาย: หน้าแสดงรายละเอียดโครงการและบันทึกข้อมูลย่อย (Sub-details)
 * เชื่อมโยงกับ service_project_detail
 */

session_start();
include_once 'auth.php';
include_once 'db.php'; 

// ตรวจสอบ ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: service_project.php");
    exit;
}

$service_id = intval($_GET['id']);

// ดึงข้อมูลหัวข้อโครงการ
$project_res = $conn->query("SELECT * FROM service_project_new WHERE service_id = $service_id");
if ($project_res->num_rows == 0) {
    // ถ้าไม่เจอในตารางใหม่ ลองหาในตารางเก่า (เผื่อ Legacy) หรือ Redirect
    header("Location: service_project.php");
    exit;
}
$project = $project_res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details - <?= htmlspecialchars($project['project_name']) ?></title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
    
    <!-- External Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/details.css">
</head>
<body>
    
    <!-- Sidebar -->
    <?php include_once 'sidebar.php'; ?>

    <div class="main-container">
        
        <!-- Header -->
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-project-diagram"></i>
                <?= htmlspecialchars($project['project_name']) ?>
            </h2>
            <a href="service_project.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
            </a>
        </div>

        <!-- Form Card -->
        <div class="card-form">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle"></i> เพิ่มรายการอุปกรณ์ / อาการเสีย
            </div>
            <div class="card-body-custom">
                <!-- หมายเหตุ: ปกติควรใช้ AJAX ใน service_project.php แต่หน้านี้เป็นแบบ Form Submit ธรรมดา (Legacy Support) -->
                <form action="save_detail.php" method="POST" class="row g-3">
                    <input type="hidden" name="service_id" value="<?= $service_id ?>">
                    
                    <div class="col-md-4">
                        <label class="form-label">ชื่ออุปกรณ์ (Equipment)</label>
                        <input type="text" name="equipment" class="form-control-custom" required placeholder="ระบุชื่ออุปกรณ์">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">อาการที่พบ (Symptom)</label>
                        <input type="text" name="symptom" class="form-control-custom" required placeholder="ระบุอาการเสีย">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">วันที่เริ่ม (Start Date)</label>
                        <input type="date" name="start_date" class="form-control-custom" required>
                    </div>
                    
                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>อุปกรณ์</th>
                            <th>อาการ</th>
                            <th>การแก้ไข</th>
                            <th>สถานะ</th>
                            <th>วันที่เริ่ม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ดึงข้อมูลรายละเอียด
                        $details = $conn->query("SELECT * FROM service_project_detail WHERE service_id = $service_id ORDER BY start_date DESC");
                        
                        if ($details->num_rows > 0):
                            while($d = $details->fetch_assoc()):
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;"><?= htmlspecialchars($d['equipment']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($d['s/n'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($d['symptom']) ?></td>
                            <td><?= htmlspecialchars($d['action_taken']) ?></td>
                            <td>
                                <?php 
                                    $status_text = 'Unknown';
                                    // Map status ID to text (Example logic)
                                    if($d['service_type'] == 1) $status_text = 'On-site';
                                    elseif($d['service_type'] == 2) $status_text = 'Remote';
                                    elseif($d['service_type'] == 3) $status_text = 'Subcontractor';
                                ?>
                                <span class="status-badge info"><?= $status_text ?></span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($d['start_date'])) ?></td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">ยังไม่มีข้อมูลรายการย่อย</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/details.js"></script>
</body>
</html>
