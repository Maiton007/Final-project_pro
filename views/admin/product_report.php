<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสินค้า</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 0;
        }

        .report-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem 1.5rem;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 1rem;
        }

        .report-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.6rem;
        }

        .report-subtitle {
            font-size: 0.9rem;
            color: #777;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 0.75rem 0.9rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 0.9rem;
        }

        thead th {
            background-color: #f0f2f5;
            color: #555;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.03em;
        }

        tbody tr:nth-child(even) {
            background-color: #fbfcfd;
        }

        tbody tr:hover {
            background-color: #eef2f7;
        }

        .product-image {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #ddd;
        }

        .text-center {
            text-align: center;
        }

        .badge-stock {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-ok {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-low {
            background: #fff3cd;
            color: #8a6d3b;
        }

        .badge-out {
            background: #f8d7da;
            color: #9f3a38;
        }

        .empty-text {
            text-align: center;
            padding: 2rem;
            color: #777;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="report-container">
        <div class="report-header">
            <h2>📊 รายงานสินค้า</h2>
            <div class="report-subtitle">
                ภาพรวมสินค้าทั้งหมดในระบบ (ชื่อ, ราคา, หมวดหมู่, สต็อก, รูปภาพ)
            </div>
        </div>

        <?php
        $sql = "SELECT id, name, price, category, stock, image FROM products ORDER BY id DESC";
        $result = $conn->query($sql);
        ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ชื่อสินค้า</th>
                        <th>ราคา (฿)</th>
                        <th>หมวดหมู่</th>
                        <th>สต็อกคงเหลือ</th>
                        <th class="text-center">รูปภาพ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // path รูป: ไฟล์นี้อยู่ที่ views/admin → ต้องถอยออก 2 ขั้นไป assets
                        $img = !empty($row['image'])
                            ? '../../assets/uploads/' . basename($row['image'])
                            : '../../assets/images/default-image.jpg';

                        $stock = (int)($row['stock'] ?? 0);
                        if ($stock <= 0) {
                            $badgeCls = 'badge-out';
                            $badgeText = 'หมดสต็อก';
                        } elseif ($stock <= 5) {
                            $badgeCls = 'badge-low';
                            $badgeText = "ใกล้หมด ({$stock})";
                        } else {
                            $badgeCls = 'badge-ok';
                            $badgeText = "{$stock} ชิ้น";
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td>฿<?= number_format((float)$row['price'], 2) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td>
                                <span class="badge-stock <?= $badgeCls; ?>">
                                    <?= $badgeText; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <img src="<?= htmlspecialchars($img) ?>"
                                     alt="<?= htmlspecialchars($row['name']) ?>"
                                     class="product-image">
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-text">ไม่มีสินค้าสำหรับรายงาน</p>
        <?php endif; ?>
    </div>
</body>

</html>
