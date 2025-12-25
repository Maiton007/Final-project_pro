<?php
require_once __DIR__ . "/../../config/database.php";

// ❌ ไม่ใช้ GET แล้ว
// ✅ รับค่าจาก POST เท่านั้น
if (!isset($_POST['id'])) {
    die("สินค้าไม่ถูกต้อง");
}

$product_id = (int)$_POST['id'];

// 1) เช็กก่อนว่ามีออเดอร์ที่ใช้สินค้านี้อยู่หรือไม่
$checkSql  = "SELECT COUNT(*) FROM order_items WHERE product_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $product_id);
$checkStmt->execute();
$checkStmt->bind_result($usedCount);
$checkStmt->fetch();
$checkStmt->close();

if ($usedCount > 0) {
    // มีประวัติการสั่งซื้อ → ไม่ให้ลบ
    header("Location: products.php?error=used");
    exit();
}

// 2) ถ้าไม่มีการใช้งาน → ลบสินค้า
$sql = "DELETE FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    header("Location: products.php?deleted=1");
    exit();
} else {
    // ใช้ข้อความกลาง ๆ สำหรับ production
    die("เกิดข้อผิดพลาดในการลบสินค้า");
}
