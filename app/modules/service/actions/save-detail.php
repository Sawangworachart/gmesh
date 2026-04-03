<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: service_project.php');
    exit();
}

$serviceId = isset($_POST['service_id']) ? (string) $_POST['service_id'] : '';
$target = 'details.php';

if ($serviceId !== '') {
    $target .= '?id=' . urlencode($serviceId);
}

header('Location: ' . $target);
exit();
