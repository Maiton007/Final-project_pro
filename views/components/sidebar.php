<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../config/database.php";

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Sidebar</title>
    <style>
    /* Sidebar theme with grey background, active links green */
    .admin-sidebar {
        width: 250px;
        background-color: #f0f0f0; /* light grey */
        color: #333333;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        padding: 1.5rem 1rem;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        box-sizing: border-box;
    }

    .admin-sidebar h3 {
        margin: 0 0 1.5rem;
        font-size: 1.25rem;
        text-align: center;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        padding-bottom: 0.5rem;
    }

    .admin-sidebar ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .admin-sidebar ul li {
        margin-bottom: 1rem;
    }

    .admin-sidebar ul li a {
        color: #333333;
        text-decoration: none;
        display: block;
        padding: 0.5rem 0.75rem;
        border-radius: 4px;
        transition: background 0.2s;
    }

    .admin-sidebar ul li a:hover {
        background-color: #dddddd; /* darker grey */
    }

    .admin-sidebar ul li a.active {
        background-color: #4caf50; /* green */
        color: #000000; /* black text */
        font-weight: bold;
    }

    
    body {
        padding-left: 260px;  
    }

   
    .admin-content {
        padding: 1rem;
    }

    @media (max-width: 768px) {
        .admin-sidebar {
            position: relative;
            width: 100%;
            height: auto;
            box-shadow: none;
        }

        /* มือถือไม่ต้องเลื่อนคอนเทนต์ออกขวา */
        body {
            padding-left: 0;
        }

        .admin-content {
            margin-left: 0;
            padding: 1rem;
        }
    }
</style>

</head>
<body>
    <div class="admin-sidebar">
        <h3>Admin Panel</h3>
        <ul>
            <li><a href="<?= BASE_URL ?>/views/admin/dashboard.php" >แดชบอร์ด</a></li>
            <li><a href="<?= BASE_URL ?>/views/admin/products.php">จัดการสินค้า</a></li>
            <li><a href="<?= BASE_URL ?>/views/admin/orders.php">คำสั่งซื้อ</a></li>
            <li><a href="<?= BASE_URL ?>/views/admin/users.php">ผู้ใช้งาน</a></li>
            <li><a href="https://dashboard.stripe.com/test/payments" target="_blank">หน้าธุรกรรม</a></li>
            <li><a href="<?= BASE_URL ?>/views/index.php">กลับหน้าหลัก</a></li>
        </ul>
    </div>
</body>
</html>
