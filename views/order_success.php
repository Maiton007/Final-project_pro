<?php
// views/order_success.php

session_start();

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Dotenv\Dotenv;

// โหลด .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// เวลาไทย
date_default_timezone_set('Asia/Bangkok');

// ===== 1) ตรวจสอบ session_id จาก Stripe =====
$sessionId = $_GET['session_id'] ?? '';
if (empty($sessionId)) {
    die("❌ ไม่มี session_id กรุณาตรวจสอบการชำระเงิน");
}

// ตั้งค่า Stripe
Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

// ===== 2) ดึง Checkout Session + Line Items จาก Stripe =====
try {
    $session    = StripeSession::retrieve($sessionId);
    $line_items = StripeSession::allLineItems($sessionId, ['limit' => 100]);
} catch (ApiErrorException $e) {
    die("❌ เกิดข้อผิดพลาดจาก Stripe: " . $e->getMessage());
}

// ตรวจสอบสถานะการจ่ายเงิน
if ($session->payment_status !== 'paid') {
    die("❌ การชำระเงินยังไม่เสร็จสมบูรณ์");
}

// ===== 3) หาผู้ใช้ (จาก PHP session หรือ client_reference_id ใน Stripe) =====
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id && !empty($session->client_reference_id)) {
    $user_id = (int)$session->client_reference_id;
}
if (!$user_id) {
    die("❌ ไม่พบข้อมูลผู้ใช้ในระบบ");
}

// ===== 4) ดึงข้อมูลผู้ใช้จาก DB (users) =====
$stmtU = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
$stmtU->bind_param("i", $user_id);
$stmtU->execute();
$stmtU->bind_result($db_user_name, $db_user_email);
if (!$stmtU->fetch()) {
    $stmtU->close();
    die("❌ ไม่พบข้อมูลผู้ใช้");
}
$stmtU->close();

// ===== 5) ดึงที่อยู่ล่าสุดของผู้ใช้จาก addresses =====
$stmtA = $conn->prepare("
    SELECT id, recipient_name, phone, line1, line2, sub_district, district, province, zipcode
    FROM addresses
    WHERE user_id = ?
    ORDER BY is_default DESC, id DESC
    LIMIT 1
");
$stmtA->bind_param("i", $user_id);
$stmtA->execute();
$stmtA->bind_result($address_id, $r_name, $r_phone, $line1, $line2, $sub, $dist, $prov, $zip);
$stmtA->fetch();
$stmtA->close();

$full_address = trim(
    ($line1 ? $line1 . ' ' : '') .
    ($line2 ? $line2 . ' ' : '') .
    ($sub ? $sub . ' ' : '') .
    ($dist ? $dist . ' ' : '') .
    ($prov ? $prov . ' ' : '') .
    ($zip ? $zip : '')
);

// ===== 6) ป้องกันการบันทึกซ้ำ (กัน refresh แล้ว insert ซ้ำ) =====
$stmtC = $conn->prepare("SELECT id FROM orders WHERE stripe_session_id = ? LIMIT 1");
$stmtC->bind_param("s", $session->id);
$stmtC->execute();
$stmtC->bind_result($existing_order_id);
$order_exists = $stmtC->fetch();
$stmtC->close();

if ($order_exists) {
    // ถ้ามี order นี้อยู่แล้ว ใช้ id เดิม
    $order_id   = (int)$existing_order_id;
    $order_date = '';
} else {
    // ===== 7) หา cart ที่ active ของ user =====
    $cart_id = null;
    $stmtCart = $conn->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
    $stmtCart->bind_param("i", $user_id);
    $stmtCart->execute();
    $stmtCart->bind_result($found_cart_id);
    if ($stmtCart->fetch()) {
        $cart_id = (int)$found_cart_id;
    }
    $stmtCart->close();

    // ===== 8) บันทึกคำสั่งซื้อ (orders) =====
    $total_price    = ($session->amount_total ?? 0) / 100; // บาท
    $status         = 'paid';
    $now            = date('Y-m-d H:i:s');
    $payment_intent = $session->payment_intent ?? null;

    $sqlOrder = "
        INSERT INTO orders
            (user_id, cart_id, address_id, total_price, status, stripe_session_id, stripe_payment_intent_id, created_at, paid_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmtO = $conn->prepare($sqlOrder);

    $cart_id_param    = $cart_id ?: null;
    $address_id_param = $address_id ?: null;

    $stmtO->bind_param(
        'iiidsssss',
        $user_id,
        $cart_id_param,
        $address_id_param,
        $total_price,
        $status,
        $session->id,
        $payment_intent,
        $now,
        $now
    );
    $stmtO->execute();
    $order_id = $stmtO->insert_id;
    $stmtO->close();

    // ===== 9) ย้ายสินค้าใน cart_items → order_items + ตัด stock =====
    if ($cart_id) {
        $stmtCI = $conn->prepare("
            SELECT product_id, quantity, unit_price
            FROM cart_items
            WHERE cart_id = ?
        ");
        $stmtCI->bind_param("i", $cart_id);
        $stmtCI->execute();
        $resCI = $stmtCI->get_result();

        $stmtOI = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");

        $stmtStock = $conn->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ?
        ");

        while ($row = $resCI->fetch_assoc()) {
            $pid    = (int)$row['product_id'];
            $qty    = (int)$row['quantity'];
            $uprice = (float)$row['unit_price'];

            // order_items
            $stmtOI->bind_param("iiid", $order_id, $pid, $qty, $uprice);
            $stmtOI->execute();

            // ตัดสต็อก
            $stmtStock->bind_param("ii", $qty, $pid);
            $stmtStock->execute();
        }

        $stmtCI->close();
        $stmtOI->close();
        $stmtStock->close();

        // อัปเดตสถานะ cart & ลบ cart_items
        $stmtUpCart = $conn->prepare("UPDATE carts SET status = 'converted' WHERE id = ?");
        $stmtUpCart->bind_param("i", $cart_id);
        $stmtUpCart->execute();
        $stmtUpCart->close();

        $stmtDelItems = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmtDelItems->bind_param("i", $cart_id);
        $stmtDelItems->execute();
        $stmtDelItems->close();
    }

    $order_date = $now;
}

// ===== 10) เตรียมข้อมูลสำหรับแสดงผล (ใช้ชื่อ/อีเมลจาก DB เป็นหลัก) =====

// ถ้าใน DB ไม่มีค่า (เผื่อบางกรณี) ค่อย fallback ไปใช้จาก Stripe
$raw_stripe_email = $session->customer_details->email ?? '';
$raw_stripe_name  = $session->customer_details->name ?? '';

$customer_email = htmlspecialchars($db_user_email !== '' ? $db_user_email : $raw_stripe_email);
$customer_name  = htmlspecialchars($db_user_name !== '' ? $db_user_name : $raw_stripe_name);

$total_amount = number_format(($session->amount_total ?? 0) / 100, 2);

// ถ้ายังไม่ได้ตั้ง $order_date (กรณีเจอ order เดิม) → โหลดจาก DB
if (empty($order_date)) {
    $stmtTime = $conn->prepare("SELECT created_at FROM orders WHERE id = ? LIMIT 1");
    $stmtTime->bind_param("i", $order_id);
    $stmtTime->execute();
    $stmtTime->bind_result($created_at);
    if ($stmtTime->fetch()) {
        $order_date = $created_at;
    } else {
        $order_date = date('Y-m-d H:i:s');
    }
    $stmtTime->close();
}

// ===== 11) กันกรณีมี cart_id อยู่ใน metadata ของ Stripe (เผื่อใช้) =====
$cart_id_meta = isset($session->metadata->cart_id) ? (int)$session->metadata->cart_id : null;

if ($cart_id_meta) {
    // เปลี่ยนสถานะ cart เป็น converted
    $stmt = $conn->prepare(
        "UPDATE carts SET status = 'converted' WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $cart_id_meta, $user_id);
    $stmt->execute();
    $stmt->close();

    // ลบรายการใน cart_items
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id_meta);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>คำสั่งซื้อของคุณ</title>
    <link rel="stylesheet" href="../assets/css/order_success.css">
</head>

<body>
    <div class="container">
        <h2>การชำระเงินเสร็จสมบูรณ์</h2>
        <h3>รายละเอียดคำสั่งซื้อ</h3>
        <table class="order-table">
            <tr>
                <th>เวลา</th>
                <td><?= htmlspecialchars($order_date) ?></td>
            </tr>
            <tr>
                <th>ชื่อผู้ใช้</th>
                <td><?= $customer_name ?></td>
            </tr>
            <tr>
                <th>อีเมลลูกค้า</th>
                <td><?= $customer_email ?></td>
            </tr>
            <tr>
                <th>ยอดรวม</th>
                <td>฿<?= $total_amount ?></td>
            </tr>
            <tr>
                <th>ที่อยู่จัดส่ง</th>
                <td><?= htmlspecialchars($full_address) ?></td>
            </tr>
        </table>

        <h3>รายการสินค้าที่สั่งซื้อ</h3>
        <table class="order-table">
            <thead>
                <tr>
                    <th>สินค้า</th>
                    <th>จำนวน</th>
                    <th>ราคา/หน่วย</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($line_items->data as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item->description) ?></td>
                        <td><?= (int)$item->quantity ?></td>
                        <td>฿<?= number_format($item->price->unit_amount / 100, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a
            href="../api/export_pdf.php?session_id=<?= urlencode($session->id) ?>"
            target="_blank"
            class="home-button">
            ดาวน์โหลดใบเสร็จ
        </a>

        <button class="home-button" id="reset-home">กลับหน้าหลัก</button>
    </div>

    <script>
        document.getElementById('reset-home').addEventListener('click', function() {
            // ตอนนี้ใช้ cart จาก DB แล้ว ไม่ต้องลบ localStorage
            window.location.href = 'index.php';
        });
    </script>
</body>

</html>
