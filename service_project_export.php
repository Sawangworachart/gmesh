<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'db.php';

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Service.xls");
echo "<meta charset='utf-8'>";

// --- 1. เพิ่มฟังก์ชันแปลงวันที่เป็น พ.ศ. ตรงนี้ครับ ---
function dateToThai($date)
{
    if ($date == "" || $date == "0000-00-00" || $date == null) {
        return "ไม่ได้ระบุ"; // หรือจะใช้ "-" ก็ได้ครับ
    }
    $timestamp = strtotime($date);
    $year = date("Y", $timestamp) + 543;
    $month = date("m", $timestamp);
    $day = date("d", $timestamp);

    return "$day/$month/$year";
}
// ------------------------------------------------

echo "<table border='1'>";
echo '<meta http-equiv="Content-type" content="text/html;charset=utf-8" />';

// 2. CSS
echo "<style>
    table { border-collapse: collapse; width: 100%; font-family: Tahoma, sans-serif; font-size: 14px; }
    th { background-color: #f2f2f2; border: 1px solid #000; padding: 5px; text-align: center; }
    td { border: 1px solid #000; padding: 5px; vertical-align: top; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }
</style>";

echo "<table>";

// 3. หัวตาราง
echo "<thead>
        <tr>
            <th>เลขที่โครงการ</th>
            <th>วันที่เริ่ม</th>
            <th>วันที่เสร็จสิ้น</th>
            <th>ลูกค้า</th>
            <th>อุปกรณ์</th>
            <th>S/N</th>
            <th>สถานะ</th>
            <th>อาการเสีย</th>
            <th>การแก้ไข</th>
        </tr>
      </thead>";
echo "<tbody>";

// 4. SQL
$sql = "SELECT 
    d.number,
    d.start_date,
    d.end_date,
    s.project_name,
    c.customers_name,
    d.equipment,
    d.`s/n` AS sn_number,
    CASE 
        WHEN d.service_type = 1 THEN 'On-site'
        WHEN d.service_type = 2 THEN 'Remote'
        WHEN d.service_type = 3 THEN 'แจ้ง Subcontractor'
        ELSE 'ไม่ระบุ'
    END as status_text,
    d.symptom,
    d.action_taken
FROM service_project_new s
LEFT JOIN service_project_detail d ON s.service_id = d.service_id
LEFT JOIN customers c ON d.customers_id = c.customers_id
ORDER BY s.project_name ASC, d.start_date DESC";

$result = $conn->query($sql) or die($conn->error);

$current_project = null;
$count_per_group = 0;

while ($row = $result->fetch_assoc()) {

    // --- เรียกใช้ฟังก์ชันแปลงวันที่ตรงนี้ครับ ---
    $start_date_thai = dateToThai($row['start_date']);
    $end_date_thai = dateToThai($row['end_date']);
    // ---------------------------------------

    // 5. Logic การจัดกลุ่ม
    if ($current_project !== $row['project_name']) {

        // 5.1 สรุปยอดกลุ่มเก่า
        if ($current_project !== null) {
            echo "<tr>
                    <td colspan='9' style='background-color: #fcf8e3; border: 1px solid #000; text-align: right; font-weight: bold;'>
                        รวมงานของโครงการ '{$current_project}': {$count_per_group} รายการ
                    </td>
                  </tr>";
        }

        // 5.2 เริ่มกลุ่มใหม่
        $current_project = $row['project_name'];
        $count_per_group = 0;

        echo "<tr>
                <td colspan='9' style='background-color: #d9edf7; border: 1px solid #000; text-align: left; font-weight: bold;'>
                    โครงการ: {$current_project}
                </td>
              </tr>";
    }

    // 6. แสดงข้อมูล (เปลี่ยนตัวแปรวันที่เป็นตัวแปรภาษาไทยที่สร้างไว้)
    echo "<tr>
            <td style='mso-number-format:\"\@\"; text-align:center;'>{$row['number']}</td>
            <td style='text-align:center;'>{$start_date_thai}</td> <td style='text-align:center;'>{$end_date_thai}</td>   <td>{$row['customers_name']}</td>
            <td>{$row['equipment']}</td>
            <td style='mso-number-format:\"\@\"; text-align:center;'>{$row['sn_number']}</td>
            <td style='text-align:center;'>{$row['status_text']}</td>
            <td>{$row['symptom']}</td>
            <td>{$row['action_taken']}</td>
          </tr>";

    $count_per_group++;
}

// 7. สรุปยอดกลุ่มสุดท้าย
if ($current_project !== null) {
    echo "<tr>
            <td colspan='9' style='background-color: #fcf8e3; border: 1px solid #000; text-align: right; font-weight: bold;'>
                รวมงานของโครงการ '{$current_project}': {$count_per_group} รายการ
            </td>
          </tr>";
}

echo "</tbody>";
echo "</table>";
?>