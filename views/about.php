

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกี่ยวกับเรา</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        header {
            background-color: #2e7d32;
            color: white;
            padding: 10px 20px;
            text-align: center;
        }
        nav {
            display: flex;
            justify-content: center;
            background-color: #388e3c;
            padding: 10px 0;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .content {
            padding: 20px;
            max-width: 800px;
            margin: 20px auto;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .content h1 {
            text-align: center;
            color: #333;
        }
        .content p {
            line-height: 1.6;
            color: #555;
        }
        footer {
            text-align: center;
            padding: 10px 20px;
            background-color: #2e7d32;
            color: white;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <header>
    <?php require_once __DIR__ . "/components/navbar.php"; ?>
        <h1>เกี่ยวกับเรา</h1>
    </header>
    <section class="content">
        <h1>ร้านค้าเราเกี่ยวกับอะไร ?</h1>
        <p>ร้านค้าของเราเป็นผู้นำในการจำหน่ายผลิตภัณฑ์ที่เป็นมิตรกับสิ่งแวดล้อม โดยมีเป้าหมายในการลดผลกระทบต่อธรรมชาติและส่งเสริมการใช้ชีวิตที่ยั่งยืน เราเชื่อมั่นว่าการเปลี่ยนแปลงเล็กๆ ในวันนี้สามารถสร้างความเปลี่ยนแปลงที่ยิ่งใหญ่ในอนาคตได้</p>

        <h2>พันธกิจของเรา</h2>
        <p>เรามุ่งมั่นในการคัดสรรสินค้าที่ช่วยลดมลพิษและเป็นทางเลือกที่ปลอดภัยสำหรับผู้บริโภค เช่น สินค้าที่ย่อยสลายได้ สินค้าจากวัสดุรีไซเคิล และสินค้าที่ช่วยประหยัดพลังงาน นอกจากนี้เรายังสนับสนุนการผลิตที่ยั่งยืนและเป็นธรรม</p>

        <h2>ทำไมต้องเลือกเรา?</h2>
        <ul>
            <li>ผลิตภัณฑ์ทุกชิ้นผ่านการตรวจสอบคุณภาพและเป็นมิตรกับสิ่งแวดล้อม</li>
            <li>เราสนับสนุนธุรกิจที่มีความรับผิดชอบต่อธรรมชาติ</li>
            <li>ทีมงานของเรายินดีให้คำแนะนำในการเลือกผลิตภัณฑ์ที่เหมาะสม</li>
        </ul>

        <h2>ร่วมเป็นส่วนหนึ่งในการสร้างโลกที่ดีกว่า</h2>
        <p>เราขอเชิญคุณร่วมเดินทางไปกับเราในเส้นทางแห่งความยั่งยืน ทุกการเลือกซื้อสินค้าจากร้านค้าของเรา คุณกำลังช่วยลดภาระของโลกและสนับสนุนการเปลี่ยนแปลงที่ดีขึ้น</p>
    </section>
    <footer>
        <p>.....</p>
    </footer>
</body>
</html>
