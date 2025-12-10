<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// รับข้อมูลจาก Frontend (JSON)
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลที่ส่งมา']);
    exit;
}

// ดึงค่าจาก $data
$name            = trim($data['name']            ?? '');
$email           = trim($data['email']           ?? '');
$phone           = trim($data['phone']           ?? '');
$password        =        $data['password']      ?? '';

$recipient_name  = trim($data['recipient_name']  ?? '');
$address_phone   = trim($data['address_phone']   ?? '');
$line1           = trim($data['line1']           ?? '');
$line2           = trim($data['line2']           ?? '');
$sub_district    = trim($data['sub_district']    ?? '');
$district        = trim($data['district']        ?? '');
$province        = trim($data['province']        ?? '');
$zipcode         = trim($data['zipcode']         ?? '');

// ตรวจสอบความครบถ้วนเบื้องต้น
$errors = [];

if ($name === '')           $errors[] = 'กรุณากรอกชื่อ';
if ($email === '')          $errors[] = 'กรุณากรอกอีเมล';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
if ($phone === '')          $errors[] = 'กรุณากรอกเบอร์โทร';
if ($password === '')       $errors[] = 'กรุณากรอกรหัสผ่าน';

if ($recipient_name === '') $errors[] = 'กรุณากรอกชื่อผู้รับ';
if ($address_phone === '')  $errors[] = 'กรุณากรอกเบอร์โทรผู้รับ';
if ($line1 === '')          $errors[] = 'กรุณากรอกที่อยู่หลัก';
if ($sub_district === '')   $errors[] = 'กรุณากรอกแขวง/ตำบล';
if ($district === '')       $errors[] = 'กรุณากรอกเขต/อำเภอ';
if ($province === '')       $errors[] = 'กรุณากรอกจังหวัด';
if ($zipcode === '')        $errors[] = 'กรุณากรอกรหัสไปรษณีย์';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode('<br>', $errors)]);
    exit;
}

// ตรวจสอบว่าอีเมลซ้ำหรือไม่
$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    echo json_encode(['status' => 'error', 'message' => 'อีเมลนี้ถูกใช้สมัครแล้ว']);
    exit;
}
$checkStmt->close();

// เริ่ม transaction เพื่อให้ insert users + addresses ไปพร้อมกัน
$conn->begin_transaction();

try {
    // เข้ารหัสรหัสผ่าน (เก็บ hash ในคอลัมน์ password)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // เพิ่มผู้ใช้ใหม่ลงในตาราง users
    $stmtUser = $conn->prepare("
        INSERT INTO users (name, email, phone, password, role)
        VALUES (?, ?, ?, ?, 'user')
    ");
    $stmtUser->bind_param('ssss', $name, $email, $phone, $passwordHash);

    if (!$stmtUser->execute()) {
        throw new Exception('ไม่สามารถบันทึกข้อมูลผู้ใช้ได้: ' . $stmtUser->error);
    }

    $userId = $stmtUser->insert_id;
    $stmtUser->close();

    // เพิ่มที่อยู่ของผู้ใช้ลงในตาราง addresses
    $stmtAddr = $conn->prepare("
        INSERT INTO addresses (
            user_id, recipient_name, phone,
            line1, line2, sub_district, district, province, zipcode,
            is_default
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmtAddr->bind_param(
        'issssssss',
        $userId,
        $recipient_name,
        $address_phone,
        $line1,
        $line2,
        $sub_district,
        $district,
        $province,
        $zipcode
    );

    if (!$stmtAddr->execute()) {
        throw new Exception('ไม่สามารถบันทึกข้อมูลที่อยู่ได้: ' . $stmtAddr->error);
    }

    $stmtAddr->close();

    // ทุกอย่างผ่าน ไม่มี error → commit
    $conn->commit();

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    // มีปัญหา → rollback
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
