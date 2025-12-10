<?php
require_once __DIR__ . "/../../config/database.php";

if (!isset($_GET['id'])) {
    die("สินค้าไม่ถูกต้อง");
}

$product_id = (int)$_GET['id'];

// 1) เช็กก่อนว่ามีออเดอร์ที่ใช้สินค้านี้อยู่หรือไม่
$checkSql  = "SELECT COUNT(*) FROM order_items WHERE product_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $product_id);
$checkStmt->execute();
$checkStmt->bind_result($usedCount);
$checkStmt->fetch();
$checkStmt->close();

if ($usedCount > 0) {
    // มีประวัติการสั่งซื้อแล้ว → ไม่ให้ลบ
    // แสดงข้อความง่าย ๆ แล้ว redirect กลับไปหน้า products พร้อม query string แจ้งเตือน
    header("Location: products.php?error=used");
    exit();
}

// 2) ถ้าไม่มีการใช้งานใน order_items → ลบได้
$sql = "DELETE FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    header("Location: products.php?deleted=1");
    exit();
} else {
    // ถ้าอยาก debug เพิ่ม เติมข้อความ error จริงของ DB (ช่วงพัฒนาเท่านั้น)
    // die("เกิดข้อผิดพลาดในการลบสินค้า: " . $stmt->error);
    die("เกิดข้อผิดพลาดในการลบสินค้า");
}
