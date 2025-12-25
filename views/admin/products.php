<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

// ดึงข้อมูลสินค้าทั้งหมดจากฐานข้อมูล
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แสดงสินค้า</title>
    <link rel="stylesheet" href="../../assets/css/product_.css">
</head>

<body>
    <?php include '../components/sidebar.php'; ?>

    <div class="container">
        <h2>สินค้าทั้งหมด</h2>

        <div>
            <a href="add_product.php" class="report-button">+ เพิ่มข้อมูลสินค้า</a>
            <a href="product_report.php" class="report-button">รายงานสินค้า</a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ชื่อสินค้า</th>
                        <th>ราคา (฿)</th>
                        <th>หมวดหมู่</th>
                        <th>สต็อก</th>
                        <th>รูปภาพ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // Path ต้องถอยออก 2 ชั้น: views/admin → ../../assets/uploads
                        $imgPath = !empty($row['image'])
                            ? '../../assets/uploads/' . basename($row['image'])
                            : '../../assets/images/default-image.jpg';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td>฿<?= number_format((float)$row['price'], 2) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['stock']) ?></td>

                            <td>
                                <img src="<?= htmlspecialchars($imgPath) ?>"
                                    alt="รูปสินค้า"
                                    class="product-image"
                                    style="width:80px; height:auto; border-radius:4px;">
                            </td>

                            <td class="actions">
                                <a href="edit_product.php?id=<?= $row['id'] ?>">แก้ไข</a>
                                <form method="post"
                                    action="delete_product.php"
                                    style="display:inline;"
                                    onsubmit="return confirm('คุณแน่ใจที่จะลบสินค้านี้?');">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="delete">
                                        ลบ
                                    </button>
                                </form>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center; padding:2rem; color:#777;">ยังไม่มีสินค้าบนเว็บไซต์</p>
        <?php endif; ?>
    </div>
</body>

</html>