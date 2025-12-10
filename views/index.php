<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// ดึงข้อมูลสินค้าทั้งหมดจากฐานข้อมูล
$sql    = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($sql);


$baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/ecommerce-project1.2';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>My-EcoShop</title>

    <!-- CSS หน้าแรก -->
    <link rel="stylesheet" href="../assets/css/home.css">

    <!-- base-url ไว้ให้ cart.js ใช้หา path /api/cart_db.php -->
    <meta name="base-url" content="<?= htmlspecialchars($baseUrl) ?>">
</head>

<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <!-- ตรงกับ selector header ใน home.css -->
    <header>
        <!-- สินค้าสำหรับคนรักสิ่งแวดล้อม -->
         <!-- ตรงกับ .banner ใน home.css -->
    <section class="banner">
        เลือกสินค้า Eco-Friendly เพื่อโลกที่ดีขึ้น
    </section>
    </header>

   

    <main class="container">
        <!-- ตรงกับ .products + .product-list -->
        <section class="products">
            <div class="product-list">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // path รูปภาพ 
                        $imagePath = !empty($row['image'])
                            ? '../assets/uploads/' . basename($row['image'])
                            : '../assets/images/default-image.jpg';

                        $stock = isset($row['stock']) ? (int)$row['stock'] : 0;
                        ?>
                        <div class="product-card">
                            <img
                                src="<?= htmlspecialchars($imagePath) ?>"
                                alt="<?= htmlspecialchars($row['name']) ?>"
                                class="product-image">

                            <h3><?= htmlspecialchars($row['name']) ?></h3>

                            <!-- ใช้ class product-description -->
                            <p class="product-description">
                                <?= nl2br(htmlspecialchars($row['description'] ?? '')) ?>
                            </p>

                            <!-- ราคา -->
                            <p>฿<?= number_format((float)$row['price'], 2) ?></p>

                            <?php if ($stock > 0): ?>
                                <p style="font-size:14px; color:#555; margin-top:4px;">
                                    คงเหลือ <?= $stock ?> ชิ้น
                                </p>
                            <?php else: ?>
                                <p style="font-size:14px; color:#c0392b; margin-top:4px;">
                                    สินค้าหมด
                                </p>
                            <?php endif; ?>

                            <!-- ปุ่มเพิ่มลงตะกร้า: cart.js จะยิงไป api/cart_db.php -->
                            <button
                                class="add-to-cart-btn"
                                data-id="<?= (int)$row['id'] ?>"
                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                data-price="<?= (float)$row['price'] ?>"
                                <?= $stock <= 0 ? 'disabled' : '' ?>>
                                <?= $stock <= 0 ? 'สินค้าหมด' : 'เพิ่มลงตะกร้า' ?>
                            </button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-products">ยังไม่มีสินค้าในระบบ</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> ร้านค้าออนไลน์รักษ์โลก</p>
    </footer>

    <!-- จัดการตะกร้าจาก DB -->
    <script src="../assets/js/cart.js"></script>
</body>

</html>
