<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ล้างตัวแปร session
$_SESSION = [];

// ทำลาย session
session_destroy();


?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Logout...</title>
</head>
<body>

<script>
    // เคลียร์ตะกร้าจาก localStorage 
    localStorage.removeItem('cart');

    // เคลียร์จำนวนสินค้าใน badge ด้วย
    localStorage.removeItem('cart_count');

    // หลังจากเคลียร์เสร็จ ค่อย redirect
    window.location.href = "login.php";
</script>

</body>
</html>
