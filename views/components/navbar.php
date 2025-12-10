<?php
require_once __DIR__ . "/../../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = isset($_SESSION["user_role"]) && strtolower($_SESSION["user_role"]) === "admin";

// ใช้ 0 เป็นค่าเริ่มต้น (เดี๋ยว cart.js จะไปดึงจาก DB มาอัปเดต)
$initialCartCount = 0;

// ถ้ามี BASE_URL ตั้งไว้ใน config แล้วใช้เลย
if (!defined('BASE_URL')) {
    // สำรองเผื่อไม่ได้ define ไว้
    define('BASE_URL', 'http://localhost/ecommerce-project1.2');
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

<header class="navbar">
    <div class="container">
        <h1><a href="<?= BASE_URL ?>/views/index.php">My-E</a></h1>
        <nav>
            <a href="<?= BASE_URL ?>/views/index.php">หน้าหลัก</a>
            <a href="<?= BASE_URL ?>/views/about.php">เกี่ยวกับเรา</a>

            <?php if ($isAdmin): ?>
                <a href="<?= BASE_URL ?>/views/admin/dashboard.php">Admin Panel</a>
            <?php endif; ?>

            <?php if (isset($_SESSION["user_id"])): ?>
                <a href="<?= BASE_URL ?>/views/logout.php">ออกจากระบบ</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/views/login.php">เข้าสู่ระบบ</a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/views/cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?= (int)$initialCartCount ?></span>
            </a>
        </nav>
    </div>
</header>

<style>
    
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        background-color: #f4f4f4;
    }

    /* แถบด้านบน */
    .navbar {
        background-color: #2e7d32;
        padding: 15px 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar .container {
        display: flex;
        align-items: center;
        width: 90%;
        max-width: 1200px;
        justify-content: space-between;
    }

    .navbar h1 a {
        color: white;
        text-decoration: none;
        font-size: 24px;
        font-weight: bold;
        transition: color 0.3s;
    }

    .navbar h1 a:hover {
        color: #ffd700;
    }

    .navbar nav {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .navbar nav a {
        color: white;
        text-decoration: none;
        font-size: 16px;
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 5px;
        transition: background 0.3s, color 0.3s;
    }

    .navbar nav a:hover {
        background-color: #1b5e20;
        color: #ffd700;
    }

    .cart-icon {
        display: inline-flex;
        align-items: center;
        position: relative;
    }

    .cart-count {
        background-color: red;
        color: white;
        padding: 4px 8px;
        border-radius: 50%;
        font-size: 14px;
        margin-left: 5px;
        min-width: 22px;
        text-align: center;
    }
</style>
