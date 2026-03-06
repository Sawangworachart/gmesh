<?php
include_once 'db.php';

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=customers.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<meta charset='utf-8'>";
echo "<table border='1'>";

// Header ตาราง
echo "<tr style='background:#e9ecef; font-weight:bold;'>
        <th>กลุ่มลูกค้า</th>
        <th>ชื่อองค์กร / แผนก</th>
        <th>ชื่อผู้ติดต่อ</th>
        <th>เบอร์โทร</th>
        <th>ที่อยู่</th>
        <th>จังหวัด</th>
      </tr>";

// ดึงข้อมูลแบบ JOIN ตาม Group
$sql = "
SELECT 
    g.group_name,
    c.customers_name,
    c.agency,
    c.contact_name,
    c.phone,
    c.address,
    c.province
FROM customer_groups g
LEFT JOIN customers c ON g.group_id = c.group_id
ORDER BY g.group_name ASC, c.customers_name ASC
";

$result = mysqli_query($conn, $sql);

// แสดงผลแบบ Group Header
$current_group = '';

while ($row = mysqli_fetch_assoc($result)) {

    if ($current_group !== $row['group_name']) {
        $current_group = $row['group_name'];

        // แถวหัวข้อกลุ่ม
        echo "<tr style='background:#dbeafe; font-weight:bold;'>
                <td colspan='6'>{$current_group}</td>
              </tr>";
    }

    // แถวข้อมูลลูกค้า
    echo "<tr>
            <td></td>
            <td>{$row['customers_name']} {$row['agency']}</td>
            <td>{$row['contact_name']}</td>
            <td>{$row['phone']}</td>
            <td>{$row['address']}</td>
            <td>{$row['province']}</td>
          </tr>";
}

echo "</table>";
