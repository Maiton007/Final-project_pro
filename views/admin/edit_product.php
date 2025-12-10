<?php
require_once __DIR__ . "/../../config/database.php";

// ตรวจสอบว่า id ของสินค้าถูกส่งมา
if (!isset($_GET['id'])) {
    die("สินค้าไม่ถูกต้อง");
}

$product_id = (int)$_GET['id'];

// ดึงข้อมูลสินค้าจากฐานข้อมูล
$sql  = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("ไม่พบข้อมูลสินค้า");
}

$product = $result->fetch_assoc();

// ค่าต่างๆ ของสินค้า
$name        = $product['name'];
$price       = $product['price'];
$category    = $product['category'];
$image       = $product['image'];
$description = $product['description'];
$stock       = $product['stock'];

$message = "";

// path รูปสำหรับพรีวิว
$previewImage = !empty($image)
    ? "../../assets/uploads/" . basename($image)
    : "../../assets/images/default-image.jpg";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name        = $_POST["name"];
    $price       = (float)$_POST["price"];
    $category    = $_POST["category"];
    $description = $_POST["description"];
    $stock       = (int)$_POST["stock"];
    $image       = $product['image']; // ถ้าไม่ได้อัปโหลดรูปใหม่ ใช้รูปเดิม

    // ถ้ามีการอัปโหลดรูปใหม่
    if (!empty($_FILES["image"]["name"])) {
        $target_dir  = __DIR__ . "/../../assets/uploads/";
        $file_name   = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image        = $file_name;
            $previewImage = "../../assets/uploads/" . $file_name;
        } else {
            $message = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
        }
    }

    // อัปเดตข้อมูลในฐานข้อมูล
    $sql = "UPDATE products 
            SET name = ?, price = ?, category = ?, stock = ?, image = ?, description = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsissi", $name, $price, $category, $stock, $image, $description, $product_id);

    if ($stmt->execute()) {
        $message = "ข้อมูลสินค้าได้ถูกอัปเดตสำเร็จ";

        // อัปเดตค่าที่ใช้แสดงบนหน้าฟอร์ม
        $product['name']        = $name;
        $product['price']       = $price;
        $product['category']    = $category;
        $product['description'] = $description;
        $product['image']       = $image;
        $product['stock']       = $stock;
    } else {
        $message = "เกิดข้อผิดพลาดในการอัปเดตสินค้า";
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แก้ไขสินค้า</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* ... (สไตล์ที่คุณมีอยู่แล้ว ผมคงไว้เหมือนเดิม) ... */
        .product-edit-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .product-preview-card,
        .product-form-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
        }
        .product-preview-card {
            flex: 1 1 280px;
            max-width: 320px;
        }
        .product-form-card {
            flex: 2 1 380px;
        }
        .product-preview-card h3 {
            margin: 0 0 .75rem;
            font-size: 18px;
            color: #333;
        }
        .preview-image-wrapper {
            width: 100%;
            text-align: center;
            margin-bottom: .75rem;
        }
        .preview-image-wrapper img {
            max-width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .preview-meta {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
        }
        .preview-meta p {
            margin: .25rem 0;
        }
        .badge-stock {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-ok   { background: #e8f5e9; color: #2e7d32; }
        .badge-low  { background: #fff3cd; color: #8a6d3b; }
        .badge-out  { background: #f8d7da; color: #9f3a38; }

        .product-form-card h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 18px;
            color: #333;
        }
        .form-group {
            margin-bottom: 0.9rem;
        }
        .form-group label {
            display: block;
            margin-bottom: .25rem;
            font-size: 14px;
            color: #444;
            font-weight: 600;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 0.45rem 0.6rem;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .alert-message {
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 14px;
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-submit {
            margin-top: .5rem;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            background: #2e7d32;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.25s ease, transform 0.1s;
        }
        .btn-submit:hover {
            background: #1b5e20;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include '../components/sidebar.php'; ?>

        <div class="admin-content">
            <h2>แก้ไขสินค้า</h2>

            <?php if (!empty($message)): ?>
                <div class="alert-message"><?= htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="product-edit-wrapper">
                <div class="product-preview-card">
                    <h3>ภาพรวมสินค้า</h3>
                    <div class="preview-image-wrapper">
                        <img src="<?= htmlspecialchars($previewImage); ?>" alt="รูปสินค้า">
                    </div>
                    <div class="preview-meta">
                        <p><strong>ชื่อ:</strong> <?= htmlspecialchars($name); ?></p>
                        <p><strong>ราคา:</strong> ฿<?= number_format((float)$price, 2); ?></p>
                        <p><strong>หมวดหมู่:</strong> <?= htmlspecialchars($category); ?></p>
                        <p>
                            <strong>สต็อกคงเหลือ:</strong>
                            <?php
                            $stockInt = (int)$stock;
                            if ($stockInt <= 0) {
                                $cls = "badge-out";
                                $txt = "หมดสต็อก ({$stockInt})";
                            } elseif ($stockInt <= 5) {
                                $cls = "badge-low";
                                $txt = "ใกล้หมด ({$stockInt})";
                            } else {
                                $cls = "badge-ok";
                                $txt = "{$stockInt} ชิ้น";
                            }
                            ?>
                            <span class="badge-stock <?= $cls; ?>"><?= $txt; ?></span>
                        </p>
                    </div>
                </div>

                <div class="product-form-card">
                    <h3>ฟอร์มแก้ไขรายละเอียดสินค้า</h3>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">ชื่อสินค้า</label>
                            <input type="text" id="name" name="name"
                                   value="<?= htmlspecialchars($name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="price">ราคา (บาท)</label>
                            <input type="number" step="0.01" id="price" name="price"
                                   value="<?= htmlspecialchars($price); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">รายละเอียดสินค้า</label>
                            <textarea id="description" name="description" required><?= htmlspecialchars($description); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="category">หมวดหมู่</label>
                            <input type="text" id="category" name="category"
                                   value="<?= htmlspecialchars($category); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="image">เลือกรูปภาพใหม่ (ถ้าต้องการเปลี่ยน)</label>
                            <input type="file" id="image" name="image">
                        </div>

                        <div class="form-group">
                            <label for="stock">สต็อกสินค้า (จำนวนชิ้น)</label>
                            <input type="number" id="stock" name="stock"
                                   value="<?= htmlspecialchars($stock); ?>" required>
                        </div>

                        <button type="submit" class="btn-submit">บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
