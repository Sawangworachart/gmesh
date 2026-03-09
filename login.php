<?php // หน้าเข้าสู่ระบบ (login.php) — ย่อและอธิบายไทยต่อบรรทัด
session_start(); // เริ่ม Session สำหรับเก็บสถานะผู้ใช้
require_once 'includes/db.php'; // นำเข้าการเชื่อมต่อฐานข้อมูล

$error_msg = ""; // เก็บข้อความผิดพลาดไว้แสดงผล

// ===== Auto Login จาก Cookie =====
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) { // ถ้ายังไม่ล็อกอินแต่มีโทเคนจำการใช้งาน
    $tokenHash = hash('sha256', $_COOKIE['remember_token']); // แฮชโทเคนจากคุกกี้เพื่อเทียบกับฐานข้อมูลแบบปลอดภัย
    $stmt = $conn->prepare("SELECT id, username, role FROM user WHERE remember_token_hash=? AND remember_expires > NOW() AND status=1"); // เตรียมคำสั่งค้นหาผู้ใช้ที่โทเคนยังไม่หมดอายุและยังใช้งานได้
    $stmt->bind_param("s", $tokenHash); // ผูกพารามิเตอร์ชนิดสตริง
    $stmt->execute(); // รันคำสั่ง SQL
    $res = $stmt->get_result(); // ดึงผลลัพธ์จากคำสั่งที่รัน
    if ($res->num_rows === 1) { // ถ้าพบผู้ใช้ตรง 1 รายการ
        $u = $res->fetch_assoc(); // ดึงข้อมูลเป็นแอสโซซิเอทีฟอาเรย์
        $_SESSION['user_id'] = $u['id']; // บันทึกไอดีผู้ใช้ลง Session
        $_SESSION['username'] = $u['username']; // บันทึกชื่อผู้ใช้ลง Session
        $_SESSION['user_role'] = $u['role']; // บันทึกบทบาทผู้ใช้ลง Session
        header("Location: " . (($u['role'] === 'superadmin' || $u['role'] === 'admin') ? 'dashboard.php' : 'user_dashboard.php')); // ไปหน้าที่เหมาะกับบทบาท
        exit(); // จบการทำงานทันทีหลังเปลี่ยนเส้นทาง
    }
}

// ===== ดำเนินการล็อกอินแบบส่งฟอร์ม =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // ทำงานเมื่อมีการส่งฟอร์ม POST
    $username = trim($_POST['username'] ?? ''); // รับชื่อผู้ใช้และตัดช่องว่างหัวท้าย
    $password = $_POST['password'] ?? ''; // รับรหัสผ่านตามที่กรอก

    $stmt = $conn->prepare("SELECT id, username, password, role, status FROM user WHERE username=?"); // เตรียมคำสั่งค้นหาผู้ใช้ตามชื่อ
    $stmt->bind_param("s", $username); // ผูกพารามิเตอร์ชื่อผู้ใช้
    $stmt->execute(); // รันคำสั่ง SQL
    $result = $stmt->get_result(); // รับผลลัพธ์การค้นหา

    if ($result->num_rows === 1) { // ถ้าพบชื่อผู้ใช้
        $row = $result->fetch_assoc(); // ดึงข้อมูลผู้ใช้
        $valid = password_verify($password, $row['password']); // ตรวจรหัสผ่านด้วย password_verify

        if ($valid) { // ถ้ารหัสผ่านถูกต้อง
            if ((int) $row['status'] === 1) { // อนุญาตเฉพาะบัญชีที่เปิดใช้งาน
                $_SESSION['user_id'] = $row['id']; // เก็บไอดีผู้ใช้
                $_SESSION['username'] = $row['username']; // เก็บชื่อผู้ใช้
                $_SESSION['user_role'] = $row['role']; // เก็บบทบาทผู้ใช้

                if (!empty($_POST['remember'])) { // ถ้าติ๊กจำการใช้งาน
                    $rawToken = bin2hex(random_bytes(32)); // สร้างโทเคนแบบสุ่มความปลอดภัยสูง
                    $tokenHash = hash('sha256', $rawToken); // แฮชโทเคนก่อนจัดเก็บในฐานข้อมูล
                    $expireDate = date('Y-m-d H:i:s', strtotime('+30 days')); // กำหนดวันหมดอายุอีก 30 วัน

                    $stmt2 = $conn->prepare("UPDATE user SET remember_token_hash=?, remember_expires=? WHERE id=?"); // คำสั่งอัปเดตโทเคนลงฐานข้อมูล
                    $stmt2->bind_param("ssi", $tokenHash, $expireDate, $row['id']); // ผูกพารามิเตอร์แฮช วันหมดอายุ และไอดีผู้ใช้
                    $stmt2->execute(); // รันคำสั่งอัปเดต

                    setcookie('remember_token', $rawToken, [ // ตั้งค่าคุกกี้ฝั่งลูกข่าย
                        'expires' => time() + (86400 * 30), // อายุคุกกี้ 30 วัน
                        'path' => '/', // ส่งทุกเส้นทางของโดเมน
                        'secure' => false, // ควรตั้ง true เมื่อใช้งานผ่าน HTTPS
                        'httponly' => true, // ป้องกัน JS เข้าถึงคุกกี้
                        'samesite' => 'Lax' // ลดความเสี่ยง CSRF ระดับหนึ่ง
                    ]);
                }

                header("Location: " . (($row['role'] === 'superadmin' || $row['role'] === 'admin') ? 'dashboard.php' : 'user_dashboard.php')); // เปลี่ยนเส้นทางตามบทบาท
                exit(); // จบการทำงานหลังเปลี่ยนเส้นทาง
            } else {
                $error_msg = "บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ"; // แจ้งเตือนเมื่อบัญชีถูกปิด
            }
        } else {
            $error_msg = "รหัสผ่านไม่ถูกต้อง"; // แจ้งเตือนเมื่อรหัสผ่านไม่ถูกต้อง
        }
    } else {
        $error_msg = "ไม่พบชื่อผู้ใช้งาน"; // แจ้งเตือนเมื่อไม่มีชื่อผู้ใช้งานนี้
    }
}
?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - เข้าสู่ระบบ</title>

    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logomaintdash1.png">

    <!-- Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
    
    <div class="login-container">
        <!-- ฝั่งซ้าย: รูปภาพและ Branding -->
        <div class="login-left">
            <div class="overlay"></div>
            <div class="content">
                <h1>Welcome Back</h1>
                <p>Maintenance Dashboard Management System</p>
                <div class="dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>

        <!-- ฝั่งขวา: ฟอร์ม Login -->
        <div class="login-right">
            <div class="login-form-wrapper <?php echo !empty($error_msg) ? 'shake' : ''; ?>">
                <div class="brand-logo">
                    <img src="assets/images/logomaintdash.png" alt="Logo">
                </div>
                
                <h2 class="form-title">ลงชื่อเข้าใช้</h2>
                <p class="form-subtitle">กรุณากรอกข้อมูลเพื่อเข้าสู่ระบบ</p>

                <form action="" method="POST">
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="input-field" placeholder="ชื่อผู้ใช้งาน"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required>
                    </div>

                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="input-field" placeholder="รหัสผ่าน" required>
                    </div>

                    <div class="options-row">
                        <label class="custom-checkbox">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            จดจำการใช้งาน
                        </label>
                        <!-- <a href="#" class="forgot-link">ลืมรหัสผ่าน?</a> -->
                    </div>

                    <button type="submit" class="login-button">
                        เข้าสู่ระบบ <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </form>
            </div>
            
            <div class="footer-copy">
                &copy; <?php echo date('Y'); ?> Global Mesh Company Limited. All rights reserved.
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="assets/js/login.js"></script>

    <!-- แจ้งเตือน Error จาก PHP -->
    <?php if (!empty($error_msg)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                })

                Toast.fire({
                    icon: 'error',
                    title: '<?php echo htmlspecialchars($error_msg); ?>'
                })
            });
        </script>
    <?php endif; ?>

</body>
</html>
