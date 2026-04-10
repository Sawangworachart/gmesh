<?php
include_once 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Product Claim.xls");
header("Pragma: no-cache");
header("Expires: 0");

// --- 1. ฟังก์ชันแปลงวันที่ (แก้ให้แสดง 'ไม่ได้ระบุ') ---
function dateToThai($date) {
    if ($date == "" || $date == "0000-00-00" || $date == null) {
        return "ไม่ได้ระบุ"; // <--- แก้ตรงนี้ครับ
    }
    $timestamp = strtotime($date);
    $year = date("Y", $timestamp) + 543;
    $month = date("m", $timestamp);
    $day = date("d", $timestamp);
    
    return "$day/$month/$year";
}
// ------------------------------------------------

function mapStatus($s) {
    if ($s == 1) return 'รอสินค้าจากลูกค้า';
    if ($s == 2) return 'ตรวจสอบ';
    if ($s == 3) return 'รอสินค้าจาก supplier';
    if ($s == 4) return 'ส่งคืนลูกค้า';
    return '-';
}

echo "<table border='1'>";
echo '<meta http-equiv="Content-type" content="text/html;charset=utf-8" />';

echo "<tr style='background:#f2f2f2; font-weight:bold;'>
        <th>ลำดับ</th>
        <th>ชื่อองค์กร</th>
        <th>แผนก</th>
        <th>อุปกรณ์</th>
        <th>S/N</th>
        <th>สถานะ</th>
        <th>วันที่เริ่ม</th>
        <th>วันที่สิ้นสุด</th>
        <th>อาการเสีย</th>
      </tr>";

$sql = "
SELECT 
    p.*,
    c.customers_name,
    c.agency
FROM product p
LEFT JOIN customers c ON p.customers_id = c.customers_id
ORDER BY p.product_id DESC
";

$result = $conn->query($sql) or die($conn->error);

$no = 1;

while ($row = mysqli_fetch_assoc($result)) {

    // เรียกใช้ฟังก์ชันแปลงวันที่
    $start_date_thai = dateToThai($row['start_date']);
    $end_date_thai = dateToThai($row['end_date']);

    echo "<tr>
            <td>{$no}</td>
            <td>{$row['customers_name']}</td>
            <td>{$row['agency']}</td>
            <td>{$row['device_name']}</td>
            <td style='mso-number-format:\"\@\";'>{$row['serial_number']}</td>
            <td>".mapStatus($row['status'])."</td>
            <td style='text-align:center;'>{$start_date_thai}</td>
            <td style='text-align:center;'>{$end_date_thai}</td>
            <td>{$row['repair_details']}</td>
          </tr>";

    $no++;
}

echo "</table>";
?>