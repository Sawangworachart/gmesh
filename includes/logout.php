<?php
//หน้าออกจากระบบ (logout.php) 
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE user 
                            SET remember_token_hash=NULL,
                                remember_expires=NULL
                            WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
}

setcookie("remember_token", "", time() - 3600, "/");

session_unset();
session_destroy();

header('Location: ../login.php');
exit();
