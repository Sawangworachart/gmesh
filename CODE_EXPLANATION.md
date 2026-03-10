# คำอธิบายโค้ดรายบรรทัด (Line-by-Line Code Explanation)

เอกสารฉบับนี้รวบรวมคำอธิบายการทำงานของโค้ดในแต่ละไฟล์อย่างละเอียด เพื่อให้เข้าใจหน้าที่และกระบวนการทำงานของระบบ

---

## 1. ไฟล์: `customers_export.php`
**หน้าที่:** ส่งออกรายชื่อลูกค้าและข้อมูลการติดต่อเป็นไฟล์ Excel (.xls)

```php
1: <?php
2: include_once 'db.php';
   // นำเข้าไฟล์ db.php เพื่อเชื่อมต่อฐานข้อมูล MySQL
   // ใช้ include_once เพื่อป้องกันการโหลดซ้ำหากมีการเรียกใช้ไฟล์นี้ไปแล้ว

4: header("Content-Type: application/vnd.ms-excel; charset=utf-8");
   // กำหนด Header ให้ Browser รู้ว่าไฟล์ที่จะส่งกลับไปเป็นไฟล์ Excel (MIME type)
   // และระบุรหัสภาษาเป็น utf-8

5: header("Content-Disposition: attachment; filename=customers.xls");
   // สั่งให้ Browser ดาวน์โหลดไฟล์แทนที่จะแสดงผลบนหน้าจอ
   // และตั้งชื่อไฟล์ว่า "customers.xls"

6: header("Pragma: no-cache");
   // สั่งไม่ให้ Browser จำค่า (Cache) เพื่อให้ได้ข้อมูลล่าสุดเสมอ

7: header("Expires: 0");
   // กำหนดวันหมดอายุของข้อมูลเป็น 0 (หมดอายุทันที) เพื่อบังคับให้โหลดใหม่

9: echo "<meta charset='utf-8'>";
   // พิมพ์ meta tag ลงไปในเนื้อหาไฟล์ เพื่อให้ Excel อ่านภาษาไทยออก (แก้ปัญหาภาษาต่างดาว)

10: echo "<table border='1'>";
    // เริ่มสร้างตาราง HTML (Excel รุ่นเก่าอ่านตาราง HTML ได้)
    // กำหนดขอบตารางเป็น 1

13: echo "<tr style='background:#e9ecef; font-weight:bold;'>
    // เริ่มแถวแรก (Header ของตาราง) ใส่สีพื้นหลังเทาอ่อนและตัวหนา
14:         <th>กลุ่มลูกค้า</th>
15:         <th>ชื่อองค์กร / แผนก</th>
16:         <th>ชื่อผู้ติดต่อ</th>
17:         <th>เบอร์โทร</th>
18:         <th>ที่อยู่</th>
19:         <th>จังหวัด</th>
20:       </tr>";
    // จบแถว Header

23: $sql = "
24: SELECT 
25:     g.group_name,      // เลือกชื่อกลุ่มลูกค้า
26:     c.customers_name,  // เลือกชื่อลูกค้า
27:     c.agency,          // เลือกชื่อหน่วยงาน
28:     c.contact_name,    // เลือกชื่อผู้ติดต่อ
29:     c.phone,           // เลือกเบอร์โทร
30:     c.address,         // เลือกที่อยู่
31:     c.province         // เลือกจังหวัด
32: FROM customer_groups g
    // จากตาราง customer_groups (ตั้งชื่อย่อว่า g)
33: LEFT JOIN customers c ON g.group_id = c.group_id
    // เชื่อมกับตาราง customers (ตั้งชื่อย่อว่า c) ด้วย group_id
    // ใช้ LEFT JOIN เพื่อให้แสดงกลุ่มลูกค้าแม้ว่าจะไม่มีลูกค้าในกลุ่มนั้น
34: ORDER BY g.group_name ASC, c.customers_name ASC
    // เรียงลำดับตามชื่อกลุ่ม ก-ฮ และชื่อลูกค้า ก-ฮ
35: ";

37: $result = mysqli_query($conn, $sql);
    // สั่งรันคำสั่ง SQL ผ่านตัวแปร $conn (ที่มาจาก db.php) เก็บผลลัพธ์ลง $result

40: $current_group = '';
    // ตัวแปรสำหรับจำชื่อกลุ่มล่าสุดที่แสดงผล (ใช้สำหรับทำ Group Header)

42: while ($row = mysqli_fetch_assoc($result)) {
    // วนลูปดึงข้อมูลทีละแถวจาก Database มาใส่ $row

44:     if ($current_group !== $row['group_name']) {
        // เช็คว่าชื่อกลุ่มเปลี่ยนไปจากเดิมหรือไม่?
45:         $current_group = $row['group_name'];
            // ถ้าเปลี่ยน ให้จำชื่อกลุ่มใหม่ไว้

48:         echo "<tr style='background:#dbeafe; font-weight:bold;'>
49:                 <td colspan='6'>{$current_group}</td>
50:               </tr>";
            // พิมพ์แถวคั่นกลุ่ม (Merge 6 คอลัมน์) ใส่สีฟ้าอ่อนและแสดงชื่อกลุ่ม
    }

54:     echo "<tr>
            // เริ่มแถวข้อมูลลูกค้าปกติ
55:             <td></td>
                // คอลัมน์แรกเว้นว่างไว้ (เพราะชื่อกลุ่มอยู่บรรทัดบนแล้ว)
56:             <td>{$row['customers_name']} {$row['agency']}</td>
                // แสดงชื่อลูกค้า + หน่วยงาน
57:             <td>{$row['contact_name']}</td>
58:             <td>{$row['phone']}</td>
59:             <td>{$row['address']}</td>
60:             <td>{$row['province']}</td>
61:           </tr>";
            // จบแถวข้อมูล
}

64: echo "</table>";
    // ปิดตาราง HTML
?>
```

---

## 2. ไฟล์: `db.php`
**หน้าที่:** เชื่อมต่อฐานข้อมูล MySQL

```php
1: <?php
3: $servername = "localhost";
   // ชื่อโฮสต์ฐานข้อมูล ปกติคือ localhost
5: $port = "3304";
   // ระบุพอร์ตเชื่อมต่อ (กรณีนี้ใช้ 3304 แทน 3306 ปกติ)
6: $username = "root"; 
   // ชื่อผู้ใช้ฐานข้อมูล
7: $password = "Global_Secure!2025"; 
   // รหัสผ่านฐานข้อมูล
8: $dbname = "mesh";
   // ชื่อฐานข้อมูลที่ต้องการเชื่อมต่อ

11: $conn = mysqli_connect($servername, $username, $password, $dbname, $port);
    // สร้างการเชื่อมต่อและเก็บไว้ในตัวแปร $conn

14: if (!$conn) {
    // ถ้าเชื่อมต่อไม่สำเร็จ ($conn เป็น false)
15:     die("Connection failed: " . mysqli_connect_error());
        // จบการทำงานทันทีและแสดงข้อความ Error
}

19: mysqli_set_charset($conn, "utf8mb4");
    // ตั้งค่าชุดตัวอักษรเป็น utf8mb4 เพื่อให้รองรับภาษาไทยสมบูรณ์แบบ
?>
```

---

## 3. ไฟล์: `auth.php`
**หน้าที่:** ตรวจสอบสิทธิ์การเข้าใช้งาน (Authentication Check)

```php
1: <?php
5: if (session_status() === PHP_SESSION_NONE) {
    // เช็คว่า Session ถูกเริ่มหรือยัง ถ้ายังให้เริ่มใหม่
6:     session_start();
}

10: if (!isset($_SESSION['user_id'])) {
    // เช็คว่ามีตัวแปร user_id ใน Session ไหม (ล็อกอินยัง?)
12:     header("Location: login.php");
        // ถ้าไม่มี ให้เด้งไปหน้า login.php
13:     exit();
        // จบการทำงานทันที ไม่ให้โค้ดส่วนล่างทำงานต่อ
}

21: function requireRole($allowed_roles)
    // ฟังก์ชันสำหรับจำกัดสิทธิ์หน้าเว็บ รับค่า Role ที่อนุญาต
{
24:     if (!is_array($allowed_roles)) {
25:         $allowed_roles = [$allowed_roles];
            // ถ้าส่งมาเป็น string เดียว ให้แปลงเป็น array
    }

29:     if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        // เช็คว่า Role ของคนที่ล็อกอิน ไม่อยู่ในรายชื่อที่อนุญาตใช่ไหม?
        
33:         if ($_SESSION['user_role'] == 'admin') {
34:             header("Location: dashboard.php");
                // ถ้าเป็น admin ให้เด้งไป dashboard หลัก
35:         } else {
36:             header("Location: user_dashboard.php");
                // ถ้าเป็น user ธรรมดา ให้เด้งไป user_dashboard
            }
38:         exit();
            // จบการทำงาน
    }
}
?>
```

---

## 4. ไฟล์: `login.php`
**หน้าที่:** หน้าเข้าสู่ระบบ (Login)

```php
1: <?php
3: session_start();
   // เริ่มต้น Session เพื่อใช้เก็บข้อมูลการล็อกอิน
4: require_once 'db.php';
   // เรียกไฟล์เชื่อมต่อฐานข้อมูล

6: $error_msg = "";
   // ตัวแปรสำหรับเก็บข้อความแจ้งเตือนข้อผิดพลาด

9: if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
   // ถ้ายังไม่ล็อกอิน (Session ว่าง) แต่มี Cookie 'remember_token' (เคยติ๊กจำไว้)
   
10:    $rawToken = $_COOKIE['remember_token'];
11:    $tokenHash = hash('sha256', $rawToken);
       // แฮช Token ที่ได้จาก Cookie เพื่อนำไปเทียบกับใน Database

13:    $stmt = $conn->prepare("SELECT id, username, role FROM user WHERE remember_token_hash=? AND remember_expires > NOW() AND status=1");
       // เตรียมคำสั่ง SQL ค้นหา User ที่มี Token ตรงกันและยังไม่หมดอายุ
       // ... (ข้ามรายละเอียดการ bind/execute) ...

22:    if ($res->num_rows === 1) {
       // ถ้าเจอผู้ใช้ที่ตรงกัน 1 คน
25:        $_SESSION['user_id'] = $u['id'];
           // สร้าง Session ให้ล็อกอินสำเร็จอัตโนมัติ
           // ... (ตรวจสอบ Role แล้ว Redirect ไปหน้า Dashboard) ...
       }
   }

39: if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ถ้ามีการกดปุ่ม Submit (ส่งค่าแบบ POST)

41:    $username = $_POST['username'];
42:    $password = $_POST['password'];
       // รับค่า username และ password จากฟอร์ม

44:    $stmt = $conn->prepare("SELECT id, username, password, role, status FROM user WHERE username=?");
       // ค้นหาข้อมูล User จาก username ที่กรอกมา

55:        if (password_verify($password, $row['password'])) {
           // ตรวจสอบรหัสผ่านว่าตรงกับ Hash ในฐานข้อมูลหรือไม่
           
60:            if ($row['status'] == 1) {
               // เช็คว่าสถานะบัญชีปกติ (Active) หรือไม่
               
67:                if (isset($_POST['remember'])) {
                   // ถ้าติ๊ก "จดจำการใช้งาน"
                   // ... (สร้าง Token ใหม่, บันทึกลง DB, สร้าง Cookie) ...
                   }
                   
                   // Redirect ไปยัง Dashboard ตาม Role
               }
           }
}
// ... (ส่วน HTML แสดงผลหน้า Login) ...
?>
```
