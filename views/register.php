<?php
// File: views/register.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก</title>

    <link rel="stylesheet" href="../assets/css/reu.css">
</head>
<body>
<?php include __DIR__ . '/components/navbar.php'; ?>

<div class="register-body">
    <div class="register-container">
        <h2>สมัครสมาชิก</h2>

        <div id="error-box" class="error-box" style="display:none;"></div>

        <form id="register-form" onsubmit="event.preventDefault(); submitForm();">
            <!-- ====================== ข้อมูลผู้ใช้ ====================== -->
            <h3>ข้อมูลผู้ใช้</h3>

            <div class="input-group">
                <label for="name">ชื่อ - นามสกุล</label>
                <input type="text" id="name" name="name" required placeholder="เช่น สมชาย ใจดี">
            </div>

            <div class="input-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" required placeholder="example@mail.com">
            </div>

            <div class="input-group">
                <label for="phone">เบอร์โทร</label>
                <input type="text" id="phone" name="phone" required placeholder="เช่น 0812345678">
            </div>

            <div class="input-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" required placeholder="กรอกรหัสผ่าน">
            </div>

            <div class="input-group">
                <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="กรอกรหัสผ่านอีกครั้ง">
            </div>

            <hr>

            <!-- ====================== ที่อยู่สำหรับจัดส่ง ====================== -->
            <h3>ที่อยู่สำหรับจัดส่ง</h3>

            <div class="input-group">
                <label for="recipient_name">ชื่อผู้รับ</label>
                <input type="text" id="recipient_name" name="recipient_name" required placeholder="ชื่อผู้รับสินค้า">
            </div>

            <div class="input-group">
                <label for="address_phone">เบอร์โทรผู้รับ</label>
                <input type="text" id="address_phone" name="address_phone" required placeholder="เบอร์โทรผู้รับสินค้า">
            </div>

            <div class="input-group">
                <label for="line1">ที่อยู่ (บรรทัดหลัก)</label>
                <input type="text" id="line1" name="line1" required placeholder="บ้านเลขที่, หมู่บ้าน, ถนน">
            </div>

            <div class="input-group">
                <label for="line2">รายละเอียดเพิ่มเติม (ถ้ามี)</label>
                <input type="text" id="line2" name="line2" placeholder="ชั้น, อาคาร, ห้อง ฯลฯ">
            </div>

            <!-- ===== dropdown จังหวัด / อำเภอ / ตำบล / รหัสไปรษณีย์ ===== -->
            <div class="input-group">
                <label for="province">จังหวัด</label>
                <select id="province" name="province" required>
                    <option value="">-- เลือกจังหวัด --</option>
                </select>
            </div>

            <div class="input-group">
                <label for="district">อำเภอ / เขต</label>
                <select id="district" name="district" required disabled>
                    <option value="">-- เลือกอำเภอ / เขต --</option>
                </select>
            </div>

            <div class="input-group">
                <label for="sub_district">ตำบล / แขวง</label>
                <select id="sub_district" name="sub_district" required disabled>
                    <option value="">-- เลือกตำบล / แขวง --</option>
                </select>
            </div>

            <div class="input-group">
                <label for="zipcode">รหัสไปรษณีย์</label>
                <input type="text" id="zipcode" name="zipcode" readonly placeholder="ระบบจะกรอกให้" required>
            </div>

            <div style="margin-top: 20px; text-align: center;">
                <button type="submit">สมัครสมาชิก</button>
            </div>
        </form>

        <div style="margin-top: 20px; text-align: center; display:flex; justify-content:center;">
            <p><a href="login.php">เข้าสู่ระบบ</a></p>
            <p><a href="index.php">กลับหน้าหลัก</a></p>
        </div>
    </div>
</div>

<script>
//---------------------- helper แสดง error ----------------------//
function showError(message) {
    const box = document.getElementById('error-box');
    box.style.display = 'block';
    box.innerHTML = message;
}
function clearError() {
    const box = document.getElementById('error-box');
    box.style.display = 'none';
    box.innerHTML = '';
}

//---------------------- dropdown address ----------------------//
const provinceSelect    = document.getElementById('province');
const districtSelect    = document.getElementById('district');
const subDistrictSelect = document.getElementById('sub_district');
const zipcodeInput      = document.getElementById('zipcode');

// โหลดจังหวัดทั้งหมดจาก API
function loadProvinces() {
    fetch('../api/thai_address.php?action=provinces')
        .then(res => res.json())
        .then(data => {
            data.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.code;       // province_code
                opt.textContent = p.name; // ชื่อจังหวัด (ไทย)
                provinceSelect.appendChild(opt);
            });
        })
        .catch(err => {
            console.error(err);
            showError('โหลดข้อมูลจังหวัดไม่สำเร็จ');
        });
}

// เมื่อเปลี่ยนจังหวัด -> โหลดอำเภอของจังหวัดนั้น
provinceSelect.addEventListener('change', () => {
    const provinceCode = provinceSelect.value;

    districtSelect.innerHTML    = '<option value="">-- เลือกอำเภอ / เขต --</option>';
    subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล / แขวง --</option>';
    districtSelect.disabled     = true;
    subDistrictSelect.disabled  = true;
    zipcodeInput.value          = '';

    if (!provinceCode) return;

    fetch('../api/thai_address.php?action=districts&province_code=' + encodeURIComponent(provinceCode))
        .then(res => res.json())
        .then(data => {
            data.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.code;       // amphoe_code
                opt.textContent = d.name; // ชื่ออำเภอ
                districtSelect.appendChild(opt);
            });
            districtSelect.disabled = false;
        })
        .catch(err => {
            console.error(err);
            showError('โหลดข้อมูลอำเภอไม่สำเร็จ');
        });
});

// เมื่อเปลี่ยนอำเภอ -> โหลดตำบล
districtSelect.addEventListener('change', () => {
    const amphoeCode = districtSelect.value;

    subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล / แขวง --</option>';
    subDistrictSelect.disabled  = true;
    zipcodeInput.value          = '';

    if (!amphoeCode) return;

    fetch('../api/thai_address.php?action=subdistricts&amphoe_code=' + encodeURIComponent(amphoeCode))
        .then(res => res.json())
        .then(data => {
            data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.code;        // tambon_code
                opt.textContent = s.name;  // ชื่อตำบล
                opt.dataset.zipcode = s.zipcode; // รหัสไปรษณีย์
                subDistrictSelect.appendChild(opt);
            });
            subDistrictSelect.disabled = false;
        })
        .catch(err => {
            console.error(err);
            showError('โหลดข้อมูลตำบลไม่สำเร็จ');
        });
});

// เมื่อเลือกตำบล -> ใส่รหัสไปรษณีย์อัตโนมัติ
subDistrictSelect.addEventListener('change', () => {
    const sel = subDistrictSelect.options[subDistrictSelect.selectedIndex];
    zipcodeInput.value = sel ? (sel.dataset.zipcode || '') : '';
});

//---------------------- submit ฟอร์ม ไป save_user_and_address.php ----------------------//
function submitForm() {
    clearError();

    const name            = document.getElementById('name').value.trim();
    const email           = document.getElementById('email').value.trim();
    const phone           = document.getElementById('phone').value.trim();
    const password        = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    const recipient_name  = document.getElementById('recipient_name').value.trim();
    const address_phone   = document.getElementById('address_phone').value.trim();
    const line1           = document.getElementById('line1').value.trim();
    const line2           = document.getElementById('line2').value.trim();

    if (password !== confirmPassword) {
        showError('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
        return;
    }

    if (!provinceSelect.value || !districtSelect.value || !subDistrictSelect.value || !zipcodeInput.value) {
        showError('กรุณาเลือก จังหวัด / อำเภอ / ตำบล และรหัสไปรษณีย์ให้ครบ');
        return;
    }

    // ดึง "ชื่อจังหวัด/อำเภอ/ตำบล" (ไม่ใช่ code) ไปเก็บใน DB
    const provinceText    = provinceSelect.options[provinceSelect.selectedIndex].textContent;
    const districtText    = districtSelect.options[districtSelect.selectedIndex].textContent;
    const subDistrictText = subDistrictSelect.options[subDistrictSelect.selectedIndex].textContent;
    const zipcode         = zipcodeInput.value.trim();

    const payload = {
        name,
        email,
        phone,
        password,
        recipient_name,
        address_phone,
        line1,
        line2,
        province:     provinceText,
        district:     districtText,
        sub_district: subDistrictText,
        zipcode
    };

    fetch('../api/save_user_and_address.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ');
            window.location.href = 'login.php';
        } else {
            showError(data.message || 'เกิดข้อผิดพลาดในการสมัครสมาชิก');
        }
    })
    .catch(err => {
        console.error(err);
        showError('ไม่สามารถติดต่อเซิร์ฟเวอร์ได้');
    });
}

// โหลดจังหวัดทันทีเมื่อหน้าเว็บพร้อม
document.addEventListener('DOMContentLoaded', loadProvinces);
</script>

<style>
    .register-body {
        font-family: Arial, sans-serif;
        background-color: #f7f7f7;
        margin: 0;
        padding: 40px 0;
        display: flex;
        justify-content: center;
    }
    .register-container {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 600px;
    }
    h2, h3 { text-align: center; color: #4CAF50; }
    .input-group { margin-bottom: 12px; }
    .input-group label {
        display: block;
        font-size: 14px;
        color: #333;
        margin-bottom: 4px;
    }
    .input-group input,
    .input-group select {
        width: 100%;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #ccc;
        font-size: 14px;
    }
    button[type="submit"] {
        padding: 10px 20px;
        background-color: #4CAF50;
        border: none;
        color: #fff;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    button[type="submit"]:hover { background-color: #45a049; }
    .error-box {
        background-color: #ffe5e5;
        color: #c00;
        border: 1px solid #c00;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 10px;
    }
    p { font-size: 14px; margin: 0 10px; }
    p a { color: #4CAF50; text-decoration: none; }
    p a:hover { text-decoration: underline; }
</style>

</body>
</html>
