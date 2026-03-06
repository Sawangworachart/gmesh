<?php
require_once 'db.php';

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Preventive_Maintenance.xls");
echo "<meta charset='utf-8'>";

// --- ฟังก์ชันแปลงวันที่เป็น พ.ศ. ---
function dateToThai($date) {
    if ($date == "" || $date == "0000-00-00" || $date == null) {
        return "-";
    }
    // แปลงสตริงวันที่เป็น Timestamp
    $timestamp = strtotime($date);
    // จัดรูปแบบ d/m/Y โดยบวก 543 ปี
    $year = date("Y", $timestamp) + 543;
    $month = date("m", $timestamp);
    $day = date("d", $timestamp);
    
    return "$day/$month/$year";
}
// --------------------------------

echo "<table border='1'>";

// หัวตาราง
echo "<tr style='background:#e9ecef; font-weight:bold;'>
        <th>เลขที่โครงการ</th>
        <th>ชื่อโครงการ</th>
        <th>ลูกค้า</th>
        <th>ผู้รับผิดชอบ</th>
        <th>สถานะ</th>
        <th>สัญญา</th>
        <th>วันส่งมอบ</th>
        <th>วันสิ้นสุด</th>
        <th>รายละเอียด / ขอบเขตงาน</th>
        <th>หมายเหตุ (รอการตรวจสอบ) </th>

        <th>MA วันที่</th>
        <th>รายละเอียด MA</th>
        <th>หมายเหตุการเข้า (MA) </th>
        <th>สถานะ (MA)</th>
      </tr>";

// ดึงเฉพาะโปรเจกต์ก่อน
$projects = mysqli_query($conn, "
    SELECT p.*, c.customers_name,
        CASE 
            WHEN p.status = 1 THEN 'รอการตรวจสอบ'
            WHEN p.status = 2 THEN 'กำลังดำเนินการ'
            WHEN p.status = 3 THEN 'ดำเนินการเสร็จสิ้น'
        END as status_text,
        (SELECT COUNT(*) FROM ma_schedule ms WHERE ms.pmproject_id = p.pmproject_id) as total_ma
    FROM pm_project p
    LEFT JOIN customers c ON p.customers_id = c.customers_id
    ORDER BY p.pmproject_id DESC
");

while ($p = mysqli_fetch_assoc($projects)) {

    // แปลงวันที่ส่วนของโปรเจกต์เตรียมไว้ก่อน
    $deliver_date = dateToThai($p['deliver_work_date']);
    $end_date = dateToThai($p['end_date']);

    // แถวหัวข้อโปรเจกต์
    echo "<tr style='background:#dbeafe; font-weight:bold;'>
            <td colspan='14'>
                โครงการ: {$p['number']} - {$p['project_name']} | ลูกค้า: {$p['customers_name']} | ต้องเข้า MA {$p['total_ma']} ครั้ง
            </td>
          </tr>";

    // ดึง MA ของโปรเจกต์นี้
    $sql_ma = "SELECT * FROM ma_schedule WHERE pmproject_id = '{$p['pmproject_id']}' ORDER BY ma_date ASC";
    $ma = mysqli_query($conn, $sql_ma);

    if (mysqli_num_rows($ma) > 0) {
        while ($m = mysqli_fetch_assoc($ma)) {
            
            // แปลงวันที่ MA
            $ma_date_thai = dateToThai($m['ma_date']);
            
            echo "<tr>
                    <td>{$p['number']}</td>
                    <td>{$p['project_name']}</td>
                    <td>{$p['customers_name']}</td>
                    <td>{$p['responsible_person']}</td>
                    <td>{$p['status_text']}</td>
                    <td>{$p['contract_period']}</td>
                    <td>{$deliver_date}</td> <td>{$end_date}</td>     <td>{$p['going_ma']}</td>
                    <td>{$p['status_remark']}</td>

                    <td>{$ma_date_thai}</td> <td>{$m['note']}</td>
                    <td>{$m['remark']}</td>
                    <td>" . ($m['is_done'] ? 'เสร็จสิ้น' : 'รอดำเนินการ') . "</td>
                  </tr>";
        }
    } else {
        // ถ้ายังไม่มี MA
        echo "<tr>
                <td>{$p['number']}</td>
                <td>{$p['project_name']}</td>
                <td>{$p['customers_name']}</td>
                <td>{$p['responsible_person']}</td>
                <td>{$p['status_text']}</td>
                <td>{$p['contract_period']}</td>
                <td>{$deliver_date}</td> <td>{$end_date}</td>     <td>{$p['going_ma']}</td>
                <td>{$p['status_remark']}</td>

                <td colspan='4' style='text-align:center;'> ยังไม่มี (MA) หรือ ไม่มี (MA) </td>
              </tr>";
    }
}

echo "</table>";
?>