<?php 
include 'db.php'; 
$service_id = $_GET['id'];

// ดึงข้อมูลหัวข้อโครงการ
$project_res = $conn->query("SELECT * FROM service_project WHERE service_id = $service_id");
$project = $project_res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Project Details - <?= $project['project_name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
     <?php include_once 'sidebar.php'; ?>
<div class="container mt-5">
    <a href="service_project.php" class="btn btn-secondary mb-3">กลับหน้าหลัก</a>
    <h3>โครงการ: <span class="text-primary"><?= $project['project_name'] ?></span></h3>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white">เพิ่มรายการอุปกรณ์/อาการเสีย</div>
        <div class="card-body">
            <form action="save_detail.php" method="POST" class="row g-3">
                <input type="hidden" name="service_id" value="<?= $service_id ?>">
                <div class="col-md-4">
                    <label class="form-label">ชื่ออุปกรณ์</label>
                    <input type="text" name="equipment" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">อาการที่พบ</label>
                    <input type="text" name="symptom" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">วันที่เริ่ม</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success px-4">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-secondary">
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
                    $details = $conn->query("SELECT * FROM service_project_detail WHERE service_id = $service_id");
                    while($d = $details->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $d['equipment'] ?></td>
                        <td><?= $d['symptom'] ?></td>
                        <td><?= $d['action_taken'] ?></td>
                        <td><span class="badge bg-info text-dark"><?= $d['status'] ?></span></td>
                        <td><?= $d['start_date'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>