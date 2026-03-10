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

    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Sarabun', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('images/login_bg_modern.jpg') center/cover no-repeat;
            position: relative;
            overflow: hidden;
        }

        /* Dark Overlay for better contrast */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.65); /* Dark blue tint */
            backdrop-filter: blur(8px);
            z-index: 1;
        }

        /* --- Glassmorphism Card --- */
        .login-card-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
            animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .login-card-inner {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px 35px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.4);
            text-align: center;
            transform-style: preserve-3d;
            transition: transform 0.1s ease-out;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-logo {
            width: 180px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
            transition: transform 0.3s ease;
        }
        
        .login-logo:hover {
            transform: scale(1.05);
        }

        .login-title {
            font-family: 'Prompt', sans-serif;
            font-size: 1.5rem;
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .login-subtitle {
            font-size: 0.9rem;
            color: #64748b;
        }

        /* --- Form Elements --- */
        .input-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }

        .input-label {
            font-size: 0.9rem;
            color: #334155;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .input-field {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Space for icon */
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
            background: #f8fafc;
            color: #334155;
        }

        .input-field:focus {
            border-color: #3b82f6;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .input-field:focus + .input-icon {
            color: #3b82f6;
        }

        .options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #475569;
        }

        .checkbox-container input {
            margin-right: 8px;
            width: 16px;
            height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
        }

        /* --- Button --- */
        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Prompt', sans-serif;
            cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(37, 99, 235, 0.5);
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .login-button:active {
            transform: translateY(0);
        }

        /* --- Footer --- */
        .login-footer {
            margin-top: 25px;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        /* --- Animations --- */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* --- Floating Orbs --- */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 2;
            opacity: 0.6;
            animation: float 10s infinite ease-in-out;
        }

        .orb-1 {
            width: 300px;
            height: 300px;
            background: #60a5fa;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 250px;
            height: 250px;
            background: #8b5cf6;
            bottom: -50px;
            right: -50px;
            animation-delay: -5s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, 50px); }
        }

    </style>
</head>

<body>

    <!-- Floating Orbs for Ambience -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="login-card-wrapper" id="loginCard">
        <div class="login-card-inner">
            <div class="login-header">
                <img src="images/logomaintdash1.png" alt="LogoMaintDash" class="login-logo" id="mainLogo">
                <h2 class="login-title">ยินดีต้อนรับ</h2>
                <p class="login-subtitle">กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
            </div>

            <form action="" method="POST">
                <div class="input-group">
                    <label class="input-label">ชื่อผู้ใช้งาน</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" class="input-field" placeholder="Username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required autocomplete="username">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">รหัสผ่าน</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" class="input-field" placeholder="Password" required autocomplete="current-password">
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>

                <div class="options-row">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" id="remember">
                        <span>จดจำฉันไว้ในระบบ</span>
                    </label>
                </div>

                <button type="submit" class="login-button">
                    เข้าสู่ระบบ <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="login-footer">
                © <?php echo date('Y'); ?> Mesh Intelligence Co.,Ltd.<br>All rights reserved.
            </div>
        </div>
    </div>

    <script>
        // --- 3D Tilt Effect ---
        const card = document.getElementById('loginCard');
        const inner = document.querySelector('.login-card-inner');

        document.addEventListener('mousemove', (e) => {
            if (window.innerWidth < 768) return; // Disable on mobile

            const rect = inner.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;

            // Reduce effect strength
            const rotateY = x / 25; 
            const rotateX = y / -25;

            inner.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });

        document.addEventListener('mouseleave', () => {
            inner.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg)`;
        });
    </script>

    <?php if (!empty($error_msg)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'เข้าสู่ระบบไม่สำเร็จ',
                text: '<?php echo htmlspecialchars($error_msg); ?>',
                confirmButtonColor: '#3b82f6',
                confirmButtonText: 'ตกลง',
                background: '#fff',
                customClass: {
                    popup: 'rounded-2xl'
                }
            });
        </script>
    <?php endif; ?>

</body>

</html>
