<?php
// api/user_management.php
header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once __DIR__ . "/../config/database.php";

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || empty($data['action'])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request"
    ]);
    exit;
}

$action = $data['action'];

try {
    if ($action === 'update_role') {
        // ------------------ เปลี่ยน role ผู้ใช้ ------------------
        if (empty($data['id']) || empty($data['role'])) {
            echo json_encode([
                "success" => false,
                "message" => "Missing id or role"
            ]);
            exit;
        }

        $user_id  = (int)$data['id'];
        $new_role = $data['role'];

        $sql  = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_role, $user_id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Update failed: " . $stmt->error
            ]);
        }
        $stmt->close();
        exit;

    } elseif ($action === 'delete_user') {
        // ------------------ ลบผู้ใช้ ------------------
        if (empty($data['id'])) {
            echo json_encode([
                "success" => false,
                "message" => "Missing id"
            ]);
            exit;
        }

        $user_id = (int)$data['id'];

        // ป้องกันการลบ user ที่กำลังล็อกอินอยู่เอง
        if (!empty($_SESSION['user_id']) && $user_id === (int)$_SESSION['user_id']) {
            echo json_encode([
                "success" => false,
                "message" => "ไม่สามารถลบผู้ใช้ที่กำลังล็อกอินอยู่ได้"
            ]);
            exit;
        }

        $sql  = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Delete failed: " . $stmt->error
            ]);
        }
        $stmt->close();
        exit;

    } else {
        echo json_encode([
            "success" => false,
            "message" => "Unknown action"
        ]);
        exit;
    }

} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
    exit;
}
