<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

// ดึงรายชื่อผู้ใช้ทั้งหมด
$sql = "SELECT id, name, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC";
$result = $conn->query($sql);

// ดึงรายการ role ที่มีในระบบ (เช่น user, admin)
$roles_result = $conn->query("SELECT DISTINCT role FROM users");
$roles = [];
while ($r = $roles_result->fetch_assoc()) {
    $roles[] = $r['role'];
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายการผู้ใช้ทั้งหมด</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: #fff;
        }

        th,
        td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #f0f2f5;
            color: #555;
        }

        tr:nth-child(even) {
            background-color: #fbfcfd;
        }

        tr:hover {
            background-color: #eef2f7;
        }

        select {
            padding: 0.25rem;
            border-radius: 4px;
        }

        .btn-delete {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            background: #e53935;
            color: #fff;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #c62828;
        }

        .btn-add-wrap {
            margin: 0.5rem 0 1rem;
        }

        .btn-add {
            display: inline-block;
            text-decoration: none;
        }

        .btn-add button {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            background: #4CAF50;
            color: #fff;
            cursor: pointer;
        }

        .btn-add button:hover {
            background: #43a047;
        }
    </style>

    <script>
        function updateRole(userId, newRole) {
            fetch('../../api/user_management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update_role',
                    id: userId,
                    role: newRole
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('ปรับสิทธิ์สำเร็จ');
                    } else {
                        alert(data.message || 'ปรับสิทธิ์ไม่สำเร็จ');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('มีข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
                });
        }

        function deleteUser(userId) {
            if (!confirm('ยืนยันการลบผู้ใช้นี้?')) return;

            fetch('../../api/user_management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete_user',
                    id: userId
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('ลบผู้ใช้สำเร็จ');
                        location.reload();
                    } else {
                        alert(data.message || 'ลบผู้ใช้ไม่สำเร็จ');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('มีข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
                });
        }
    </script>
</head>

<body>
    <?php include '../components/sidebar.php'; ?>

    <div class="container">
        <h2>รายการผู้ใช้ทั้งหมด</h2>

        <div class="btn-add-wrap">
            <a href="../register.php" class="btn-add">
                <button>เพิ่มข้อมูลผู้ใช้งาน</button>
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อ</th>
                    <th>อีเมล</th>
                    <th>สิทธิ์</th>
                    <th>วันที่สมัคร</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <select onchange="updateRole(<?= (int)$row['id'] ?>, this.value)">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= htmlspecialchars($role) ?>"
                                            <?= $row['role'] === $role ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td>
                                <button class="btn-delete"
                                        onclick="deleteUser(<?= (int)$row['id'] ?>)">
                                    ลบ
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:1rem; color:#777;">
                            ยังไม่มีผู้ใช้
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
