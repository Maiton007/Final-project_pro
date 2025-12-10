<?php
// export_pdf.php
// สร้างใบเสร็จเป็น PDF จากคำสั่งซื้อ โดยอ้างอิงจาก Stripe Session ID

ob_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

use Dotenv\Dotenv;

// โหลด .env (เผื่อใช้ค่าอื่น ๆ ภายหลัง)
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// ตั้ง timezone
date_default_timezone_set('Asia/Bangkok');

// -------------------- 1) รับค่า session_id -------------------- //
$session_id = filter_input(INPUT_GET, 'session_id', FILTER_SANITIZE_STRING);
if (!$session_id) {
    die('Missing session ID');
}

// -------------------- 2) ดึงข้อมูลคำสั่งซื้อจาก orders -------------------- //
// ใน DB ใหม่: ใช้ stripe_session_id และ primary key คือ id
$stmt = $conn->prepare("
    SELECT id, user_id, address_id, total_price, created_at
    FROM orders
    WHERE stripe_session_id = ?
    LIMIT 1
");
$stmt->bind_param('s', $session_id);
$stmt->execute();
$stmt->bind_result($order_id, $user_id, $address_id, $grand_total, $order_date);
if (!$stmt->fetch()) {
    $stmt->close();
    die('Order not found');
}
$stmt->close();

// -------------------- 3) ดึงข้อมูลผู้ใช้ (users) -------------------- //
$stmtU = $conn->prepare("
    SELECT name, email
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmtU->bind_param("i", $user_id);
$stmtU->execute();
$stmtU->bind_result($customer_name, $customer_email);
$stmtU->fetch();
$stmtU->close();

// -------------------- 4) ดึงที่อยู่จาก addresses -------------------- //
$full_address = '';
if ($address_id) {
    $stmtA = $conn->prepare("
        SELECT recipient_name, phone, line1, line2, sub_district, district, province, zipcode
        FROM addresses
        WHERE id = ?
        LIMIT 1
    ");
    $stmtA->bind_param("i", $address_id);
    $stmtA->execute();
    $stmtA->bind_result($r_name, $r_phone, $line1, $line2, $sub, $dist, $prov, $zip);
    if ($stmtA->fetch()) {
        $full_address = trim(
            ($line1 ? $line1 . ' ' : '') .
            ($line2 ? $line2 . ' ' : '') .
            ($sub ? $sub . ' ' : '') .
            ($dist ? $dist . ' ' : '') .
            ($prov ? $prov . ' ' : '') .
            ($zip ? $zip : '')
        );
    }
    $stmtA->close();
}

// -------------------- 5) ดึงรายการสินค้าในคำสั่งซื้อ (order_items + products) -------------------- //
$stmtItems = $conn->prepare("
    SELECT 
        p.name,
        oi.quantity,
        oi.unit_price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$resultItems = $stmtItems->get_result();

$items = [];
while ($row = $resultItems->fetch_assoc()) {
    $items[] = $row;
}
$stmtItems->close();

// -------------------- 6) เริ่มสร้าง PDF ด้วย TCPDF -------------------- //
$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(20, 25, 20);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

// ฟอนต์ที่รองรับภาษาไทย (ต้องมี freeserif ใน TCPDF)
$pdf->SetFont('freeserif', '', 14);

ob_start();
?>
<style>
    h2 {
        text-align: center;
        margin-bottom: 25px;
        font-size: 22px;
    }
    h3 {
        margin-top: 20px;
        font-size: 18px;
    }

    /* ตารางหัวใบเสร็จ (ข้อมูลลูกค้า/วันที่) */
    table.header-table {
        width: 100%;
        margin-bottom: 18px;
    }
    table.header-table td {
        padding: 6px 4px;
        font-size: 14px;
        vertical-align: top;
    }

    /* ตารางสินค้า */
    table.product-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
        table-layout: fixed; /* ให้คอลัมน์นิ่งและเท่ากันตาม colgroup */
    }
    table.product-table th,
    table.product-table td {
        border: 1px solid #000;
        padding: 8px;
        font-size: 14px;
        vertical-align: middle;
        word-wrap: break-word;
    }
    table.product-table th {
        background: #f0f0f0;
        text-align: center;
    }

    /* จัดตำแหน่งข้อความ */
    .center { text-align: center; }
    .right  { text-align: right; }

    /* แถวสรุปรวม */
    .summary-row td {
        font-weight: bold;
        background: #f9f9f9;
    }
</style>

<h2>ใบเสร็จรับเงิน</h2>

<table class="header-table">
    <tr>
        <td><strong>รหัสคำสั่งซื้อ:</strong> <?= htmlspecialchars($order_id) ?></td>
        <td class="right"><strong>วันที่:</strong> <?= htmlspecialchars($order_date) ?></td>
    </tr>
    <tr>
        <td colspan="2"><strong>ชื่อลูกค้า:</strong> <?= htmlspecialchars($customer_name) ?></td>
    </tr>
    <tr>
        <td colspan="2"><strong>อีเมล:</strong> <?= htmlspecialchars($customer_email) ?></td>
    </tr>
    <tr>
        <td colspan="2"><strong>ที่อยู่จัดส่ง:</strong> <?= htmlspecialchars($full_address) ?></td>
    </tr>
</table>

<h3>รายการสินค้าที่สั่งซื้อ</h3>

<table class="product-table">
    <!-- กำหนดความกว้างคอลัมน์ให้เท่ากันตามสัดส่วน -->
    <colgroup>
        <col style="width:10%;">
        <col style="width:40%;">
        <col style="width:10%;">
        <col style="width:20%;">
        <col style="width:20%;">
    </colgroup>
    <thead>
        <tr>
            <th>ลำดับ</th>
            <th>สินค้า</th>
            <th>จำนวน</th>
            <th>ราคา/หน่วย</th>
            <th>รวม</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $index = 1;
        foreach ($items as $it):
            $qty   = (int)$it['quantity'];
            $price = (float)$it['unit_price'];
            $sub   = $qty * $price;
        ?>
        <tr>
            <td class="center"><?= $index++; ?></td>
            <td><?= htmlspecialchars($it['name']); ?></td>
            <td class="center"><?= $qty; ?></td>
            <td class="right"><?= number_format($price, 2); ?></td>
            <td class="right"><?= number_format($sub, 2); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="summary-row">
            <td colspan="4" class="right">รวมทั้งหมด</td>
            <td class="right"><?= number_format($grand_total, 2); ?></td>
        </tr>
    </tbody>
</table>
<?php
// ดึง HTML จาก buffer
$html = ob_get_clean();

// เขียน HTML ลงใน PDF
$pdf->writeHTML($html, true, false, true, false, '');

// ไปหน้าสุดท้าย (เผื่อมีหลายหน้า)
$pdf->lastPage();

// ส่งออก PDF แบบเปิดในเบราว์เซอร์
$pdf->Output("receipt_{$order_id}.pdf", 'I');
exit;
