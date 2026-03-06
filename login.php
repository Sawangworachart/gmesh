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
    <title>MaintDash</title>
<!-- 
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" sizes="32x32" href="images/logomaintdash1.png"> -->

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Kanit', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            display: flex;
            justify-content: center;
            align-items: center;

            position: relative;
            overflow: hidden;
            perspective: 1000px;
            background: url('images/information.png') center/cover no-repeat;
        }



        /* --- Effect เมาส์เรืองแสง (คงเดิม) --- */
        .mouse-glow {
            position: fixed;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(243, 156, 18, 0.8) 0%, rgba(243, 156, 18, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
            transform: translate(-50%, -50%);
            z-index: 9999;
            filter: blur(10px);
            mix-blend-mode: screen;
            opacity: 0.9;
            transition: width 0.2s, height 0.2s, opacity 0.2s;
        }

        .mouse-glow.active {
            width: 100px;
            height: 100px;
            opacity: 1;
            background: radial-gradient(circle, rgba(243, 156, 18, 1) 0%, rgba(26, 42, 68, 0) 70%);
        }

        /* ---------------------------------- */

        .orb {
            position: absolute;
            border-radius: 50%;
            z-index: 1;
        }

        .orb-blue {
            background: #1a2a44;
            width: 40px;
            height: 40px;
            top: 10%;
            left: 15%;
            opacity: 0.6;
        }

        .orb-orange {
            background: #f39c12;
            width: 60px;
            height: 60px;
            bottom: 15%;
            left: 10%;
            opacity: 0.7;
        }

        .orb-blue-lg {
            background: #1a2a44;
            width: 80px;
            height: 80px;
            bottom: 10%;
            right: 10%;
            opacity: 0.6;
        }

        /* --- ลบส่วนนี้ออกเพื่อปิดแสงสีเหลืองรอบกล่อง --- */
        .login-card-wrapper::before {
            content: '';
            position: absolute;
            inset: -4px;
            /* ความหนาของกรอบ */
            background: linear-gradient(45deg, #f39c12, #1a2a44, #f39c12, #2c3e50);
            background-size: 400% 400%;
            z-index: -1;
            border-radius: 18px;
            /* ใหญ่กว่า inner เล็กน้อย */
            filter: blur(5px);
            /* ทำให้แสงฟุ้ง */
            animation: gradientMove 5s ease infinite alternate;
        }

        /* --- ปรับปรุง Login Card (กรอบเคลื่อนไหว) --- */
        .login-card-wrapper {
            position: relative;
            width: 90%;
            max-width: 380px;
            z-index: 10;

            /* เรียกใช้ slideInLeft นาน 0.8 วินาที */
            animation: slideInLeft 2.8s cubic-bezier(0.215, 0.610, 0.355, 1.000) both;
        }

        /* เนื้อหาข้างในการ์ด (พื้นหลังสีขาว) */
        .login-card-inner {
            background: #ffffff;
            /* <--- สีขาวทึบ */
            border-radius: 15px;
            padding: 40px 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            height: 100%;
            width: 100%;
        }

        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* --- Animation: พุ่งมาจากซ้ายแล้วเด้ง --- */
        @keyframes slideInLeft {
            0% {
                opacity: 0;
                transform: translateX(-100vw);
                /* เริ่มจากนอกจอทางซ้าย */
            }

            60% {
                opacity: 1;
                transform: translateX(30px);
                /* วิ่งเลยจุดกึ่งกลางไปทางขวานิดหน่อย */
            }

            80% {
                transform: translateX(-10px);
                /* เด้งกลับมาทางซ้าย */
            }

            100% {
                transform: translateX(0);
                /* หยุดที่ตรงกลางเป๊ะ */
            }
        }

        /* ---------------------------------- */

        .login-header {
            text-align: center;
            margin-bottom: 30px;
            perspective: 1000px;
            /* เพื่อให้โลโก้มีมิติ */
        }

        /* --- ปรับปรุง Logo (3D Tilt Effect) --- */
        .login-logo {
            width: 200px;
            margin-bottom: 10px;
            /* เพิ่มการรองรับ 3D */
            transform-style: preserve-3d;
            transition: transform 0.1s ease-out;
            /* ให้ขยับตามเมาส์ได้นุ่มนวล */
            /* เพิ่มเงาให้รู้สึกลอย */
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.2));
        }

        /* ---------------------------------- */

        .divider {
            height: 1px;
            background: #eee;
            margin: 15px 0 25px 0;
        }

        .form-label {
            display: block;
            color: #1a2a44;
            font-size: 0.95rem;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-field {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s ease;
            background: #f9f9f9;
            /* ปรับพื้นหลัง input เล็กน้อยให้ดูมีมิติ */
        }

        .input-field:focus {
            border-color: #f39c12;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.1);
            background: #fff;
        }

        .options-row {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 25px;
        }

        .options-row input {
            margin-right: 8px;
            cursor: pointer;
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background: #1a2a44;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(26, 42, 68, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-button:hover {
            background: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 42, 68, 0.4);
        }

        /* สั่งปิด Animation ถ้าระบบเติม Class นี้เข้ามา */
        .no-anim {
            animation: none !important;
            transform: translateX(0) !important;
            /* บังคับให้อยู่ตรงกลางเลย */
        }
    </style>
</head>

<body>
    <div class="mouse-glow" id="mouseGlow"></div>

    <div class="orb orb-blue"></div>
    <div class="orb orb-orange"></div>
    <div class="orb orb-blue-lg"></div>

    <div class="login-card-wrapper <?php echo !empty($error_msg) ? 'no-anim' : ''; ?>" id="loginCard">
        <div class="login-card-inner">
            <div class="login-header">
                <img src="images/logomaintdash.png" alt="LogoMaintDash" class="login-logo" id="mainLogo">
                <div class="divider"></div>
            </div>

            <form action="" method="POST">
                <div class="input-group">
                    <label class="form-label">ชื่อผู้ใช้งาน</label>
                    <input type="text" name="username" class="input-field" placeholder="กรอกชื่อผู้ใช้งาน"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required>
                </div>

                <div class="input-group">
                    <label class="form-label">รหัสผ่าน</label>
                    <input type="password" name="password" class="input-field" placeholder="กรอกรหัสผ่าน" required>
                </div>

                <div class="options-row">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember" style="cursor: pointer;">จดจำการใช้งาน</label>
                </div>

                <button type="submit" class="login-button">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>

    <script>
        // --- Script สำหรับ Effect เมาส์เรืองแสง (คงเดิม) ---
        const glow = document.getElementById('mouseGlow');
        document.addEventListener('mousemove', (e) => {
            glow.style.left = e.clientX + 'px';
            glow.style.top = e.clientY + 'px';
        });
        const interactiveElements = document.querySelectorAll('button, input, a, label, .login-card-inner');
        interactiveElements.forEach(el => {
            el.addEventListener('mouseenter', () => {
                glow.classList.add('active');
            });
            el.addEventListener('mouseleave', () => {
                glow.classList.remove('active');
            });
        });
        // ----------------------------------

        // --- Script สำหรับ Effect โลโก้ 3D Tilt ---
        const card = document.getElementById('loginCard');
        const logo = document.getElementById('mainLogo');

        // เมื่อขยับเมาส์บนการ์ด
        card.addEventListener('mousemove', (e) => {
            // หาจุดกึ่งกลางของการ์ด
            const rect = card.getBoundingClientRect();
            const cardCenterX = rect.left + rect.width / 2;
            const cardCenterY = rect.top + rect.height / 2;

            // หาตำแหน่งเมาส์เทียบกับจุดกึ่งกลาง
            const mouseX = e.clientX - cardCenterX;
            const mouseY = e.clientY - cardCenterY;

            // คำนวณองศาการเอียง (ปรับตัวเลขหารเพื่อเพิ่ม/ลดความแรง)
            const rotateY = mouseX / 15;
            const rotateX = mouseY / 15 * -1; // คูณ -1 เพื่อให้เอียงตามธรรมชาติ

            // ใส่ค่า Transform ให้โลโก้
            logo.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
        });

        // เมื่อเมาส์ออกจากการ์ด ให้โลโก้กลับมาตรงเหมือนเดิม
        card.addEventListener('mouseleave', () => {
            logo.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
        });
        // ----------------------------------
    </script>

    <?php if (!empty($error_msg)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'เข้าสู่ระบบไม่สำเร็จ',
                text: '<?php echo htmlspecialchars($error_msg); ?>',
                heightAuto: false,
                confirmButtonColor: '#1a2a44'
            });
        </script>
    <?php endif; ?>

</body>

</html>