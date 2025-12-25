<?php
// ไฟล์: dashboard.php (แทนที่ทั้งไฟล์ได้เลย)
session_start();
require_once __DIR__ . "/../../config/database.php";

// ฟังก์ชันดึงค่าเดียว
function fetchOne($conn, $sql)
{
    $res = $conn->query($sql);
    return $res ? $res->fetch_array()[0] : 0;
}

// Metrics วันที่ปัจจุบัน
$today = date('Y-m-d');
$month = date('Y-m');
$year  = date('Y');

$totalCustomers = fetchOne($conn, "SELECT COUNT(*) FROM users");
$totalItemsSold = fetchOne($conn, "SELECT IFNULL(SUM(quantity),0) FROM order_items");

// สรุปยอดขายตามช่วงเวลา (ใช้ created_at)
$totalRevenueToday = fetchOne(
    $conn,
    "SELECT IFNULL(SUM(total_price),0) 
     FROM orders 
     WHERE DATE(created_at) = '$today'"
);

$orderCountToday = fetchOne(
    $conn,
    "SELECT COUNT(*) 
     FROM orders 
     WHERE DATE(created_at) = '$today'"
);

$totalRevenueMonth = fetchOne(
    $conn,
    "SELECT IFNULL(SUM(total_price),0) 
     FROM orders 
     WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'"
);

$orderCountMonth = fetchOne(
    $conn,
    "SELECT COUNT(*) 
     FROM orders 
     WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'"
);

$totalRevenueYear = fetchOne(
    $conn,
    "SELECT IFNULL(SUM(total_price),0) 
     FROM orders 
     WHERE YEAR(created_at) = '$year'"
);

$orderCountYear = fetchOne(
    $conn,
    "SELECT COUNT(*) 
     FROM orders 
     WHERE YEAR(created_at) = '$year'"
);

// ================== กราฟรายได้รายเดือน (12 เดือนล่าสุด) ================== //
$monthlyRevenueLabels = [];
$monthlyRevenueData   = [];

$revStmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS ym,
        IFNULL(SUM(total_price),0) AS revenue
    FROM orders
    WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01')
    GROUP BY ym
    ORDER BY ym ASC
");
$revStmt->execute();
$revRes = $revStmt->get_result();

$revMap = [];
while ($r = $revRes->fetch_assoc()) {
    $revMap[$r['ym']] = (float)$r['revenue'];
}
$revStmt->close();

for ($i = 11; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i month"));
    $monthlyRevenueLabels[] = $ym;
    $monthlyRevenueData[]   = isset($revMap[$ym]) ? $revMap[$ym] : 0;
}

// ================== ส่วนสรุป STOCK สินค้า ================== //

// กำหนดเกณฑ์ "ใกล้หมด"
$lowStockThreshold = 5;

// จำนวนสินค้าทั้งหมดในสต็อก (รวมทุก product)
$totalStock = fetchOne($conn, "SELECT IFNULL(SUM(stock),0) FROM products");

// จำนวนสินค้าใกล้หมด (กำหนดเกณฑ์ <= $lowStockThreshold ชิ้น)
$lowStockCount = fetchOne($conn, "SELECT COUNT(*) FROM products WHERE stock <= {$lowStockThreshold}");

// ดึงรายการ "สินค้าใกล้หมดสต็อก"
$lowStockProducts = [];
$stmtLow = $conn->prepare("
    SELECT name, stock 
    FROM products 
    WHERE stock <= ? 
    ORDER BY stock ASC, name ASC
");
$stmtLow->bind_param("i", $lowStockThreshold);
$stmtLow->execute();
$resLow = $stmtLow->get_result();
while ($row = $resLow->fetch_assoc()) {
    $lowStockProducts[] = $row;
}
$stmtLow->close();

// ================== Top 5 สินค้าขายดี ================== //
$topProducts = [];
$tpRes = $conn->query("
    SELECT 
        p.name,
        SUM(oi.quantity) AS sold_count,
        p.stock
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    GROUP BY oi.product_id, p.name, p.stock
    ORDER BY sold_count DESC
    LIMIT 5
");
if ($tpRes) {
    while ($row = $tpRes->fetch_assoc()) {
        $topProducts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แดชบอร์ดสรุป</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fa;
            margin: 0;
            padding: 0;
        }

        .dashboard {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        h3 {
            margin-top: 2rem;
            color: #333;
        }

        .cards {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card {
            width: calc(33.333% - 1rem);
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        .card h4 {
            margin: 0 0 .5rem;
            font-size: 1rem;
            color: #555;
        }

        .card p {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .chart-card {
            background: #fff;
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,.08);
        }

        .chart-wrap {
            width: 100%;
            height: 360px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed; /* ให้คอลัมน์บาลานซ์กัน */
        }

        th,
        td {
            padding: .75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            word-wrap: break-word;
        }

        th {
            background: #f0f2f5;
        }

        tr:nth-child(even) {
            background-color: #fbfcfd;
        }

        tr:hover {
            background-color: #eef2f7;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        /* Badge สต็อก */
        .stock-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .stock-low {
            background: #fff3cd;
            color: #8a6d3b;
        }

        .stock-out {
            background: #f8d7da;
            color: #9f3a38;
        }
    </style>
</head>

<body>
    <?php include '../components/sidebar.php'; ?>

    <div class="dashboard">
        <h2>แดชบอร์ดภาพรวม</h2>

        <div class="cards">
            <!-- ถ้าจะเปิดยอดขายวันนี้ / จำนวนออเดอร์วันนี้ ให้เอา comment ออก -->
            <!--
            <div class="card">
                <h4>ยอดขายวันนี้</h4>
                <p>฿<?= number_format($totalRevenueToday, 2) ?></p>
            </div>
            <div class="card">
                <h4>จำนวนคำสั่งซื้อวันนี้</h4>
                <p><?= $orderCountToday ?></p>
            </div>
            -->

            <div class="card">
                <h4>ยอดขายเดือนนี้</h4>
                <p>฿<?= number_format($totalRevenueMonth, 2) ?></p>
            </div>

            <div class="card">
                <h4>ยอดขายปีนี้</h4>
                <p>฿<?= number_format($totalRevenueYear, 2) ?></p>
            </div>

            <div class="card">
                <h4>จำนวนคำสั่งซื้อเดือนนี้</h4>
                <p><?= $orderCountMonth ?></p>
            </div>

            <div class="card">
                <h4>จำนวนคำสั่งซื้อปีนี้</h4>
                <p><?= $orderCountYear ?></p>
            </div>

            <div class="card">
                <h4>จำนวนลูกค้าทั้งหมด</h4>
                <p><?= number_format($totalCustomers) ?> คน</p>
            </div>

            <div class="card">
                <h4>จำนวนรายการสินค้าที่ขายได้ (ทั้งหมด)</h4>
                <p><?= number_format($totalItemsSold) ?> รายการ</p>
            </div>

            <div class="card">
                <h4>จำนวนสินค้าทั้งหมดในสต็อก</h4>
                <p><?= number_format($totalStock) ?> ชิ้น</p>
            </div>

            <div class="card">
                <h4>จำนวนสินค้าใกล้หมดสต็อก (น้อยกว่า <?= $lowStockThreshold ?> ชิ้น)</h4>
                <p><?= number_format($lowStockCount) ?> รายการ</p>
            </div>
        </div>

        <!-- กราฟรายได้รายเดือน -->
        <h3>กราฟรายได้รายเดือน (12 เดือนล่าสุด)</h3>
        <div class="chart-card">
            <div class="chart-wrap">
                <canvas id="revenueMonthlyChart"></canvas>
            </div>
        </div>

        <!-- ตาราง Top 5 สินค้าขายดี -->
        <h3>Top 5 สินค้าขายดี</h3>
        <table>
            <thead>
                <tr>
                    <th style="width:50%;">สินค้า</th>
                    <th style="width:25%;">จำนวนขาย</th>
                    <th style="width:25%;">สต็อกคงเหลือ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topProducts)): ?>
                    <tr>
                        <td colspan="3" class="center">ยังไม่มีข้อมูลการขายสินค้า</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($topProducts as $prod): ?>
                        <tr>
                            <td><?= htmlspecialchars($prod['name']) ?></td>
                            <td class="center"><?= (int)$prod['sold_count'] ?></td>
                            <td class="center"><?= (int)$prod['stock'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!--  ตารางสินค้าใกล้หมดสต็อก -->
        <h3>สินค้าใกล้หมดสต็อก </h3>
        <table>
            <thead>
                <tr>
                    <th style="width:60%;">ชื่อสินค้า</th>
                    <th style="width:20%;">สต็อกคงเหลือ</th>
                    <th style="width:20%;">สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lowStockProducts)): ?>
                    <tr>
                        <td colspan="3" class="center">
                            ขณะนี้ยังไม่มีสินค้าใกล้หมดสต็อก
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lowStockProducts as $p): ?>
                        <?php
                            $stock = (int)$p['stock'];
                            if ($stock <= 0) {
                                $statusText  = 'หมดสต็อก';
                                $statusClass = 'stock-out';
                            } else {
                                $statusText  = 'ใกล้หมด';
                                $statusClass = 'stock-low';
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td class="center"><?= $stock ?></td>
                            <td class="center">
                                <span class="stock-badge <?= $statusClass ?>">
                                    <?= $statusText ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const monthlyLabels = <?= json_encode($monthlyRevenueLabels, JSON_UNESCAPED_UNICODE) ?>;
        const monthlyData   = <?= json_encode($monthlyRevenueData, JSON_UNESCAPED_UNICODE) ?>;

        const canvas = document.getElementById('revenueMonthlyChart');
        if (canvas) {
            const ctx = canvas.getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'รายได้ (บาท)',
                        data: monthlyData,
                        tension: 0.25,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const v = context.raw ?? 0;
                                    return 'รายได้: ฿' + Number(v).toLocaleString('th-TH', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: (value) => '฿' + Number(value).toLocaleString('th-TH')
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>
