<?php
// db.php
$servername = "localhost";
// โค้ด SQL dump บอกว่า Host: localhost:3304 ดังนั้นต้องกำหนด port
$port = "3304";
$username = "root"; // เปลี่ยนเป็น username ของคุณ
$password = "Global_Secure!2025"; // เปลี่ยนเป็น password ของคุณ
$dbname = "mesh";

// สร้างการเชื่อมต่อ
$conn = mysqli_connect($servername, $username, $password, $dbname, $port);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตั้งค่า charset ให้รองรับภาษาไทย
mysqli_set_charset($conn, "utf8mb4");

// ไฟล์นี้จะไม่ได้เรียกใช้ mysqli_close() เพราะจะถูกเรียกใน equipment.php
?>