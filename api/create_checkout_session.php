<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

// โหลด .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json; charset=UTF-8');

// ตรวจสอบการล็อกอิน
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// อ่าน cart_id จาก body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
$cart_id = isset($data['cart_id']) ? (int)$data['cart_id'] : 0;

if ($cart_id <= 0) {
    echo json_encode(['error' => 'No active cart']);
    exit;
}

// ดึง cart + items จาก DB
// 1) ตรวจสอบว่าตะกร้านี้เป็นของ user นี้จริงไหม
$stmt = $conn->prepare("
    SELECT id
    FROM carts
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $cart_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$cartRow = $res->fetch_assoc();
$stmt->close();

if (!$cartRow) {
    echo json_encode(['error' => 'No active cart']); // ตะกร้าไม่ใช่ของ user นี้ หรือไม่มีอยู่
    exit;
}

// 2) ดึงรายการสินค้าในตะกร้า
$stmt = $conn->prepare("
    SELECT ci.product_id,
           ci.quantity,
           ci.unit_price,
           p.name
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
");
$stmt->bind_param('i', $cart_id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

if (empty($items)) {
    echo json_encode(['error' => 'Cart is empty']);
    exit;
}

// เตรียมข้อมูลสำหรับ Stripe
$line_items = [];
foreach ($items as $item) {
    $line_items[] = [
        'price_data' => [
            'currency' => 'thb',
            'product_data' => [
                'name' => $item['name'],
                'metadata' => [
                    'product_id' => $item['product_id']
                ],
            ],
            'unit_amount' => (int)round($item['unit_price'] * 100),
        ],
        'quantity' => (int)$item['quantity'],
    ];
}

// ตั้งค่า Stripe
Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY'));

// สร้าง Checkout Session
try {
    $baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost/ecommerce-project1.2';

    $checkout_session = Session::create([
        'payment_method_types' => ['card'],
        'mode' => 'payment',
        'line_items' => $line_items,
        'success_url' => $baseUrl . '/views/order_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $baseUrl . '/views/cart.php',
        'client_reference_id' => $user_id,
        'metadata' => [
            'cart_id' => $cart_id
        ],
    ]);

    echo json_encode([
        'checkout_url' => $checkout_session->url
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Stripe error: ' . $e->getMessage()
    ]);
}
