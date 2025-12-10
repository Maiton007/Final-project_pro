<?php
header('Content-Type: application/json; charset=UTF-8');

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error'   => 'User not logged in',
        'message' => 'User not logged in',
    ]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// -----------------------------------------------------------------------------
// หา / สร้าง cart ที่ active ของ user
// -----------------------------------------------------------------------------
function getActiveCartId(mysqli $conn, int $user_id): int
{
    // หา cart ที่ active
    $stmt = $conn->prepare("
        SELECT id
        FROM carts
        WHERE user_id = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($cart_id);
    if ($stmt->fetch()) {
        $stmt->close();
        return (int)$cart_id;
    }
    $stmt->close();

    // ถ้าไม่มีก็สร้างใหม่
    $stmt = $conn->prepare("
        INSERT INTO carts (user_id, status)
        VALUES (?, 'active')
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $new_id = $stmt->insert_id;
    $stmt->close();

    return (int)$new_id;
}

// -----------------------------------------------------------------------------
// อ่าน input JSON
// -----------------------------------------------------------------------------
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$action = $data['action'] ?? '';

// mapping ชื่อ action จากฝั่ง JS ที่เก่า/ใหม่
switch ($action) {
    case 'add_item':
        $action = 'add';
        break;
    case 'get_cart':
        $action = 'get';
        break;
    case 'update_qty':
        $action = 'update';
        break;
    case 'remove_item':
        $action = 'remove';
        break;
}

try {
    // -------------------------------------------------------------------------
    // ADD: เพิ่มสินค้า
    // -------------------------------------------------------------------------
    if ($action === 'add') {
        $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
        $qty        = isset($data['quantity']) ? (int)$data['quantity'] : 1;

        if ($product_id <= 0 || $qty <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            exit;
        }

        // เช็คสินค้า + stock
        $stmt = $conn->prepare("
            SELECT price, stock
            FROM products
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $stmt->bind_result($price, $stock);
        if (!$stmt->fetch()) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        $stmt->close();

        if ($stock <= 0) {
            echo json_encode(['success' => false, 'message' => 'สินค้าหมดสต็อก']);
            exit;
        }

        $qty = max(1, min($qty, (int)$stock)); // กันจำนวนเกิน stock

        $cart_id = getActiveCartId($conn, $user_id);

        // insert / update cart_items
        $stmt = $conn->prepare("
            INSERT INTO cart_items (cart_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->bind_param('iiid', $cart_id, $product_id, $qty, $price);
        $stmt->execute();
        $stmt->close();

        // ดึง cart ปัจจุบันกลับไป
        $res = $conn->prepare("
            SELECT ci.id,
                   ci.product_id,
                   ci.quantity,
                   ci.unit_price,
                   p.name,
                   p.image
            FROM cart_items ci
            JOIN products p ON p.id = ci.product_id
            WHERE ci.cart_id = ?
            ORDER BY ci.id DESC
        ");
        $res->bind_param('i', $cart_id);
        $res->execute();
        $result = $res->get_result();

        $items = [];
        $total = 0.0;
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $row['line_total'] = $row['unit_price'] * $row['quantity'];
            $total += $row['line_total'];
            $count += $row['quantity'];
            $items[] = $row;
        }
        $res->close();

        echo json_encode([
            'success'        => true,
            'items'          => $items,
            'total'          => $total,
            'item_count'     => $count,
            'total_quantity' => $count,
            'cart_id'        => $cart_id,
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // GET: ดึง cart ปัจจุบัน
    // -------------------------------------------------------------------------
    if ($action === 'get') {
        // อาจยังไม่มี cart
        $stmt = $conn->prepare("
            SELECT id
            FROM carts
            WHERE user_id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($cart_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            echo json_encode([
                'success'        => true,
                'items'          => [],
                'total'          => 0,
                'item_count'     => 0,
                'total_quantity' => 0,
                'cart_id'        => null,
            ]);
            exit;
        }
        $stmt->close();

        $res = $conn->prepare("
            SELECT ci.id,
                   ci.product_id,
                   ci.quantity,
                   ci.unit_price,
                   p.name,
                   p.image
            FROM cart_items ci
            JOIN products p ON p.id = ci.product_id
            WHERE ci.cart_id = ?
            ORDER BY ci.id DESC
        ");
        $res->bind_param('i', $cart_id);
        $res->execute();
        $result = $res->get_result();

        $items = [];
        $total = 0.0;
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $row['line_total'] = $row['unit_price'] * $row['quantity'];
            $total += $row['line_total'];
            $count += $row['quantity'];
            $items[] = $row;
        }
        $res->close();

        echo json_encode([
            'success'        => true,
            'items'          => $items,
            'total'          => $total,
            'item_count'     => $count,
            'total_quantity' => $count,
            'cart_id'        => $cart_id,
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // UPDATE: เปลี่ยนจำนวนสินค้า
    // -------------------------------------------------------------------------
    if ($action === 'update') {
        $item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;
        $qty     = isset($data['quantity']) ? (int)$data['quantity'] : 1;

        if ($item_id <= 0 || $qty <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item or quantity']);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE cart_items ci
            JOIN carts c ON c.id = ci.cart_id
            SET ci.quantity = ?
            WHERE ci.id = ? AND c.user_id = ? AND c.status = 'active'
        ");
        $stmt->bind_param('iii', $qty, $item_id, $user_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        echo json_encode(['success' => $affected > 0]);
        exit;
    }

    // -------------------------------------------------------------------------
    // REMOVE: ลบสินค้าออกจาก cart
    // -------------------------------------------------------------------------
    if ($action === 'remove') {
        $item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;

        if ($item_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item']);
            exit;
        }

        $stmt = $conn->prepare("
            DELETE ci
            FROM cart_items ci
            JOIN carts c ON c.id = ci.cart_id
            WHERE ci.id = ? AND c.user_id = ? AND c.status = 'active'
        ");
        $stmt->bind_param('ii', $item_id, $user_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        echo json_encode(['success' => $affected > 0]);
        exit;
    }

    // action ไม่รู้จัก
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
    exit;
}
