<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/components/navbar.php';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ตะกร้าสินค้า</title>
    <link rel="stylesheet" href="../assets/css/c.css">
    <style>

    </style>
</head>

<body>
    <section class="cart-container">
        <div class="cart-table">
            <h2>ตะกร้าสินค้า</h2>
            <table id="cart-items-table">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th>ราคา</th>
                        <th>จำนวน</th>
                        <th>ยอดรวม</th>
                        <th>ลบ</th>
                    </tr>
                </thead>
                <tbody id="cart-items">
                    <tr>
                        <td colspan="5" class="loading-text">กำลังโหลดสินค้า...</td>
                    </tr>
                </tbody>
            </table>
            <div class="cart-summary">
                <div class="total-text">
                    <span><strong>ยอดรวม:</strong></span>
                    <span class="total-amount">฿<span id="total-price">0.00</span></span>
                </div>
                <button type="button" class="checkout" id="checkout-button">ไปชำระเงิน</button>
            </div>



        </div>
    </section>

    <script src="../assets/js/cart.js"></script>
</body>

</html>