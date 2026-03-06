<?php
require_once 'db.php';

$type = $_GET['type'] ?? '';
$year = $_GET['year'] ?? '';

$where = "";
if ($year !== '') {
    $year = $conn->real_escape_string($year);
}

function output($labels, $data){
    header('Content-Type: application/json');
    echo json_encode(['labels'=>$labels,'data'=>$data]);
    exit;
}

if ($type === 'pm') {
    $where = $year ? "WHERE YEAR(deliver_work_date)='$year'" : "";
    $sql = "SELECT status, COUNT(*) c FROM pm_project $where GROUP BY status";
    $res = $conn->query($sql);

    $map = [
        1=>'รอการตรวจสอบ',
        2=>'กำลังดำเนินการ',
        3=>'ดำเนินการเสร็จสิ้น'
    ];

    $labels=[]; $data=[];
    while($r=$res->fetch_assoc()){
        $labels[] = $map[$r['status']] ?? 'อื่นๆ';
        $data[] = $r['c'];
    }
    output($labels,$data);
}

if ($type === 'service') {
    $where = $year ? "WHERE YEAR(d.start_date)='$year'" : "";
    $sql = "SELECT d.service_type, COUNT(*) c
            FROM service_project_detail d
            $where GROUP BY d.service_type";
    $res = $conn->query($sql);

    $map = [
        1=>'On-site',
        2=>'Remote',
        3=>'แจ้ง Subcontractor'
    ];

    $labels=[]; $data=[];
    while($r=$res->fetch_assoc()){
        $labels[] = $map[$r['service_type']] ?? 'อื่นๆ';
        $data[] = $r['c'];
    }
    output($labels,$data);
}

if ($type === 'product') {
    $where = $year ? "WHERE YEAR(start_date)='$year'" : "";
    $sql = "SELECT status, COUNT(*) c FROM product $where GROUP BY status";
    $res = $conn->query($sql);

    $map = [
        1=>'รอสินค้าจากลูกค้า',
        2=>'ตรวจสอบ',
        3=>'รอสินค้าจาก supplier',
        4=>'ส่งคืนลูกค้า'
    ];

    $labels=[]; $data=[];
    while($r=$res->fetch_assoc()){
        $labels[] = $map[$r['status']] ?? 'อื่นๆ';
        $data[] = $r['c'];
    }
    output($labels,$data);
}
