<?php
// ma_notify_count.php
// ❌ ห้าม session_start
// ❌ ห้าม require db.php

$notify_7days_count = 0;

if (!isset($conn)) {
    return; // กันพัง ถ้าไม่มี DB
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    return;
}

$sql = "
    SELECT COUNT(*) AS total
    FROM maintenance_alerts
    WHERE is_done = 0
    AND alert_date BETWEEN CURDATE()
    AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();
$notify_7days_count = (int)($row['total'] ?? 0);
