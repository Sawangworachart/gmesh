<?php
//หน้าเข้าสู่ระบบ (login.php) 
session_start();
require_once 'db.php';

$error_msg = "";

/* ================= AUTO LOGIN FROM COOKIE ================= */
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $rawToken = $_COOKIE['remember_token'];
    $tokenHash = hash('sha256', $rawToken);

    $stmt = $conn->prepare("SELECT id, username, role 
                            FROM user 
                            WHERE remember_token_hash=? 
                            AND remember_expires > NOW() 
                            AND status=1");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $u = $res->fetch_assoc();

        $_SESSION['user_id'] = $u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['user_role'] = $u['role'];

        if ($u['role'] === 'superadmin' || $u['role'] === 'admin') {
            header("Location: dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    }
}

/* ================= LOGIN PROCESS ================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role, status 
                            FROM user WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        $is_password_correct = false;

        if (password_verify($password, $row['password'])) {
            $is_password_correct = true;
        }

        if ($is_password_correct) {
            if ($row['status'] == 1) {

                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_role'] = $row['role'];

                /* ========= REMEMBER ME (ปลอดภัยจริง) ========= */
                if (isset($_POST['remember'])) {

                    $rawToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $rawToken);
                    $expireDate = date('Y-m-d H:i:s', strtotime('+30 days'));

                    $stmt2 = $conn->prepare("UPDATE user 
                                             SET remember_token_hash=?, 
                                                 remember_expires=? 
                                             WHERE id=?");
                    $stmt2->bind_param("ssi", $tokenHash, $expireDate, $row['id']);
                    $stmt2->execute();

                    setcookie(
                        "remember_token",
                        $rawToken,
                        [
                            'expires' => time() + (86400 * 30),
                            'path' => '/',
                            'secure' => false,      // ถ้าเป็น https ค่อย true
                            'httponly' => true,
                            'samesite' => 'Lax'      // ⭐ ตัวนี้สำคัญ
                        ]
                    );
                }

                if ($row['role'] === 'superadmin' || $row['role'] === 'admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: user_dashboard.php");
                }
                exit();
            } else {
                $error_msg = "บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ";
            }
        } else {
            $error_msg = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error_msg = "ไม่พบชื่อผู้ใช้งาน";
    }
}
?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaintDash - Login</title>

    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/login.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">
</head>

<body>
    <div class="login-card-wrapper">
        <div class="login-card">
            <div class="login-header">
                <img src="images/logomaintdash.png" alt="LogoMaintDash" class="login-logo">
                <div class="divider"></div>
            </div>

            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label">ชื่อผู้ใช้งาน</label>
                    <input type="text" name="username" class="form-control" placeholder="กรอกชื่อผู้ใช้งาน"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label">รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" required>
                </div>

                <div class="options-row">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember" style="cursor: pointer;">จดจำการใช้งาน</label>
                </div>

                <button type="submit" class="btn btn-primary login-button">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>

    <?php if (!empty($error_msg)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'เข้าสู่ระบบไม่สำเร็จ',
                text: '<?php echo htmlspecialchars($error_msg); ?>',
                confirmButtonColor: '#3b82f6'
            });
        </script>
    <?php endif; ?>

</body>

</html>