<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// โหลด .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// ตั้งค่า Stripe Secret Key
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // รับค่าจากฟอร์ม
    $name        = trim($_POST["name"] ?? "");
    $priceInput  = trim($_POST["price"] ?? "");
    $category    = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $stock       = (int)($_POST["stock"] ?? 0);

    // แปลงราคาเป็น float
    $priceValue = (float)$priceInput;

    // ตรวจ validate ขั้นพื้นฐาน
    if ($name === "" || $priceValue <= 0 || $category === "") {
        $message = "กรุณากรอกข้อมูลให้ครบถ้วน และราคา > 0";
    } else {

        // จัดการรูปภาพ
        $target_dir = __DIR__ . "/../../assets/uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $image = "";

        if (!empty($_FILES["image"]["name"])) {
            $file_name     = time() . "_" . basename($_FILES["image"]["name"]);
            $target_file   = $target_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            $allowed = ["jpg", "jpeg", "png", "gif"];
            if (!in_array($imageFileType, $allowed)) {
                $message = "ไฟล์รูปภาพต้องเป็น JPG, JPEG, PNG หรือ GIF เท่านั้น";
            } else {

                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image = $file_name;
                } else {
                    $message = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
                }
            }
        } else {
            $message = "กรุณาอัปโหลดรูปภาพสินค้า";
        }

        // ถ้าไม่มี error → ทำต่อ
        if ($message === "") {

            try {

                // 1) สร้างสินค้าใน Stripe
                $product = \Stripe\Product::create([
                    'name'        => $name,
                    'description' => $description,
                    'type'        => 'good',
                ]);

                // 2) สร้างราคา
                $stripePrice = \Stripe\Price::create([
                    'unit_amount' => (int)round($priceValue * 100),
                    'currency'    => 'thb',
                    'product'     => $product->id,
                ]);

                // 3) บันทึกลง Database
                $sql = "INSERT INTO products 
                        (name, image, description, price, category, stock, stripe_product_id, stripe_price_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssdsiss",
                    $name,          // s
                    $image,         // s
                    $description,   // s
                    $priceValue,    // d (double)
                    $category,      // s (string)
                    $stock,         // i (int)
                    $product->id,   // s
                    $stripePrice->id // s
                );

                $stmt->execute();
                $stmt->close();

                // 4) Redirect กลับไปหน้า product list
                $redirect = $_SERVER['HTTP_REFERER'] ?? "../products.php";
                header("Location: $redirect");
                exit();
            } catch (Exception $e) {
                $message = "❌ Stripe Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เพิ่มสินค้า</title>
    <link rel="stylesheet" href="../../assets/css/add_product.css">
</head>

<body>

    <?php if (!empty($message)): ?>
        <div class="alert-success" id="successMessage"><?= $message; ?></div>
    <?php endif; ?>

    <div class="admin-container">
        <?php include '../components/sidebar.php'; ?>

        <div class="admin-content">
            <h2>เพิ่มสินค้า</h2>

            <form method="POST" enctype="multipart/form-data">

                <input type="text" name="name" placeholder="ชื่อสินค้า" required><br><br>

                <label>รูปภาพสินค้า (อัปโหลดเท่านั้น):</label>
                <input type="file" name="image" required><br><br>

                <textarea name="description" placeholder="รายละเอียดสินค้า"></textarea><br><br>

                <input type="number" name="price" placeholder="ราคา (บาท)" min="0" step="0.01" required><br><br>

                <div class="input-group">
                    <label>จำนวนสินค้า (สต็อก)</label>
                    <input type="number" name="stock" min="0" required>
                </div>
                <br>

                <label>หมวดหมู่สินค้า:</label>
                <input type="text" name="category" placeholder="เช่น เครื่องเขียน, กระเป๋า" required><br><br>

                <button type="submit">บันทึก</button>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const msg = document.getElementById("successMessage");
            if (msg) {
                setTimeout(() => msg.style.display = "none", 3000);
            }
        });
    </script>

</body>

</html>