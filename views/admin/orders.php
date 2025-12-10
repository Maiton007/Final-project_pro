<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

// ⭐ ดึงรายการคำสั่งซื้อ + join กับ users เพื่อดึงชื่อและอีเมล
$sql = "
    SELECT 
        o.id AS order_id,
        o.user_id,
        u.name AS customer_name,
        u.email AS customer_email,
        o.total_price,
        o.status,
        o.created_at
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการคำสั่งซื้อ</title>

    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #f9fafb; 
            margin: 0; 
            padding: 0; 
        }

        .container { 
            max-width: 1200px; 
            margin: 2rem auto; 
            padding: 1rem; 
        }

        h2 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 1.5rem;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 1rem; 
            background: #fff;
            table-layout: fixed; /* ⭐ ให้คอลัมน์ขนาดเท่ากัน */
        }

        th, td { 
            padding: 0.8rem; 
            text-align: left; 
            border-bottom: 1px solid #e0e0e0; 
            font-size: 15px;
            word-wrap: break-word;
        }

        th { 
            background-color: #eef1f4; 
            color: #444; 
            font-weight: bold; 
        }

        tr:nth-child(even) { background-color: #fafbfc; }
        tr:hover { background-color: #f0f4f9; }

        /* สีสถานะเป็นแบบ Badge สวย ๆ */
        .badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
        }

        .status-paid { background: #c8e6c9; color: #256029; }
        .status-pending { background: #fff3cd; color: #8a6d3b; }
        .status-failed { background: #f8d7da; color: #9f3a38; }

        /* คอลัมน์ตัวเงินขวา */
        .right { text-align: right; }

        /* คอลัมน์กึ่งกลาง */
        .center { text-align: center; }
    </style>
</head>

<body>

<?php include '../components/sidebar.php'; ?>

<div class="container">

    <h2>📋 รายการคำสั่งซื้อทั้งหมด</h2>

    <table>
        <thead>
            <tr>
                <th style="width:10%;">Order ID</th>
                <th style="width:20%;">ชื่อลูกค้า</th>
                <th style="width:25%;">อีเมล</th>
                <th style="width:15%;">ยอดรวม (฿)</th>
                <th style="width:15%;">สถานะ</th>
                <th style="width:15%;">วันที่สั่งซื้อ</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>

                <?php
                    // badge สีตามสถานะ
                    $statusClass = [
                        'paid'    => 'status-paid',
                        'pending' => 'status-pending',
                        'failed'  => 'status-failed'
                    ];

                    $cls = $statusClass[strtolower($row["status"])] ?? "";
                ?>

                <tr>
                    <td class="center"><?= htmlspecialchars($row["order_id"]); ?></td>
                    <td><?= htmlspecialchars($row["customer_name"]); ?></td>
                    <td><?= htmlspecialchars($row["customer_email"]); ?></td>
                    <td class="right"><?= number_format($row["total_price"], 2); ?></td>
                    <td><span class="badge <?= $cls ?>"><?= htmlspecialchars($row["status"]); ?></span></td>
                    <td><?= htmlspecialchars($row["created_at"]); ?></td>
                </tr>

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:1rem; color:#777;">
                    ไม่มีคำสั่งซื้อ
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>
