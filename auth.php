<?php
// auth.php

// 1. เริ่ม Session ถ้ายังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. ตรวจสอบว่ามี User ID ใน Session หรือไม่ (ล็อกอินหรือยัง?)
if (!isset($_SESSION['user_id'])) {
    // ถ้ายังไม่ล็อกอิน ให้ส่งกลับไปหน้า Login
    header("Location: login.php");
    exit();
}

/**
 * 3. ฟังก์ชันสำหรับตรวจสอบสิทธิ์ (Role)
 * ใช้เรียกในหน้าที่ต้องการจำกัดสิทธิ์เฉพาะกลุ่ม
 * * @param string|array $allowed_roles Role ที่อนุญาตให้เข้าหน้านี้ (เช่น 'admin' หรือ ['admin', 'user'])
 */
function requireRole($allowed_roles)
{
    // แปลงให้เป็น Array เสมอ เพื่อความง่ายในการเช็ค
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    // ตรวจสอบว่า Role ของคนที่ล็อกอินอยู่ มีสิทธิ์ไหม
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        // --- ถ้าไม่มีสิทธิ์ (Access Denied) ---

        // ให้เด้งกลับไป Dashboard ของตัวเอง
        if ($_SESSION['user_role'] == 'admin') {
            header("Location: dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    }
}
?>