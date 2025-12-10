<?php
// --- 1. ส่วนของ Backend Logic (ประมวลผลการล็อกอิน) ---

// ตรวจสอบว่า session เริ่มทำงานแล้วหรือยัง ถ้ายัง ให้เริ่ม session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../config/database.php';

// ตัวแปรสำหรับเก็บข้อความแจ้งเตือน
$message = "";

// ====================================================================================
//  ตรรกะการตรวจสอบการล็อกอิน
//  เมื่อผู้ใช้กดปุ่ม "เข้าสู่ระบบ" (ส่งข้อมูลแบบ POST)
// ====================================================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // รับค่า email และ password ที่ผู้ใช้กรอก (ตัดช่องว่างหัวท้ายออก)
    $email    = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if ($email === '' || $password === '') {
        $message = "กรุณากรอกอีเมลและรหัสผ่าน";
    } else {
        // ตรงกับโครงสร้างใหม่: users(id, name, email, phone, password, role, ...)
        $stmt = $conn->prepare("
            SELECT id, name, email, password, role
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user   = $result->fetch_assoc(); // ถ้าไม่เจอจะได้ null

            if (!$user) {
                $message = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
            } else {
                // ตอนนี้ password ใน DB คือ hash แล้ว
                if (!password_verify($password, $user['password'])) {
                    $message = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
                } else {
                    // ล็อกอินสำเร็จ
                    $_SESSION["user_id"]   = $user['id'];
                    $_SESSION["user_name"] = $user['name'];
                    $_SESSION["user_role"] = $user['role'];

                    header("Location: index.php");
                    exit();
                }
            }

            $stmt->close();
        } else {
            $message = "ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
    <!-- <link rel="stylesheet" href="css/styles.css"> -->
</head>

<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="login-body">
        <div class="login-container">
            <h2>เข้าสู่ระบบ</h2>

            <!-- ข้อความแสดงข้อผิดพลาด -->
            <?php if (!empty($message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <!-- ฟอร์มเข้าสู่ระบบ -->
            <form method="POST">
                <div class="input-group">
                    <label for="email">อีเมล</label>
                    <input type="email" id="email" name="email" placeholder="กรอกอีเมล" required>
                </div>

                <div class="input-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required>
                </div>

                <button type="submit">เข้าสู่ระบบ</button>
            </form>

            <div style="display: flex;">
                <p><a href="register.php">สมัครสมาชิก</a></p>
                <p><a href="index.php">หน้าหลัก</a></p>
            </div>
        </div>
    </div>
</body>

<style>
    .login-body {
        font-family: Arial, sans-serif;
        background-color: #f7f7f7;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .login-container {
        background-color: #fff;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }

    h2 {
        text-align: center;
        color: #4CAF50;
        margin-bottom: 20px;
    }

    .input-group {
        margin-bottom: 15px;
    }

    .input-group label {
        font-size: 14px;
        color: #333;
    }

    .input-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 16px;
        margin-top: 5px;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
    }

    button:hover {
        background-color: #45a049;
    }

    .error-message {
        color: red;
        font-size: 14px;
        text-align: center;
    }

    p {
        font-size: 14px;
        text-align: center;
        margin-top: 20px;
        padding-right: 1rem;
    }

    p a {
        color: #4CAF50;
        text-decoration: none;
        padding: 8px 16px;
        border: 2px solid #4CAF50;
        border-radius: 4px;
        font-weight: bold;
        display: inline-block;
        transition: all 0.3s ease;
    }

    p a:hover {
        background-color: #4CAF50;
        color: white;
        border-color: #45a049;
    }
</style>

</html>
