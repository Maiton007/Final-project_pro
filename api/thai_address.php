<?php
// File: api/thai_address.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

if ($action === 'provinces') {
    // ดึงรายชื่อจังหวัดทั้งหมด
    $sql = "SELECT DISTINCT province, province_code 
            FROM tambons 
            ORDER BY province";
    $result = $conn->query($sql);

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'code' => $row['province_code'],
            'name' => $row['province'],
        ];
    }

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'districts') {
    $provinceCode = $_GET['province_code'] ?? '';

    if ($provinceCode === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing province_code'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT DISTINCT amphoe, amphoe_code
        FROM tambons
        WHERE province_code = ?
        ORDER BY amphoe
    ");
    $stmt->bind_param('s', $provinceCode);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = [
            'code' => $row['amphoe_code'],
            'name' => $row['amphoe'],
        ];
    }

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'subdistricts') {
    $amphoeCode = $_GET['amphoe_code'] ?? '';

    if ($amphoeCode === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing amphoe_code'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT tambon, tambon_code, zipcode
        FROM tambons
        WHERE amphoe_code = ?
        ORDER BY tambon
    ");
    $stmt->bind_param('s', $amphoeCode);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = [
            'code'    => $row['tambon_code'],
            'name'    => $row['tambon'],
            'zipcode' => $row['zipcode'],
        ];
    }

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
}

// ถ้า action ไม่ตรงกับที่รองรับ
http_response_code(400);
echo json_encode(['error' => 'invalid action'], JSON_UNESCAPED_UNICODE);
