<?php // auth.php — โค้ดสั้น กระชับ พร้อมคำอธิบายไทยต่อบรรทัด

session_status() === PHP_SESSION_NONE && session_start(); // เริ่ม Session เฉพาะเมื่อยังไม่ถูกเริ่ม (ย่อรูปแบบ if)

if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit(); } // ถ้าไม่ได้ล็อกอิน ให้เปลี่ยนเส้นทางไปหน้าเข้าสู่ระบบแล้วจบสคริปต์

function requireRole($allowed_roles) { // ฟังก์ชันบังคับสิทธิ์การเข้าถึงตามบทบาท
    $role = $_SESSION['user_role'] ?? null; // ดึงบทบาทจาก Session; ถ้าไม่มีให้เป็น null เพื่อเลี่ยง Notice
    $allowed = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles]; // รองรับทั้งสตริงเดี่ยวและอาร์เรย์ โดยบังคับให้เป็นอาร์เรย์
    if (!in_array($role, $allowed, true)) { // ตรวจสิทธิ์แบบเข้มงวด (strict) ว่าบทบาทอยู่ในรายการที่อนุญาตหรือไม่
        $target = ($role === 'superadmin' || $role === 'admin') ? 'dashboard.php' : 'user_dashboard.php'; // กำหนดหน้าปลายทางตามบทบาท
        header("Location: $target"); // เปลี่ยนเส้นทางไปหน้าที่เหมาะสม
        exit(); // จบการทำงานทันทีเพื่อความปลอดภัย
    }
}
?>

