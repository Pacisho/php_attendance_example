<?php
require 'db.php';

$username = 'admin';
$new_password = 'password123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE username = ?");
    $stmt->execute([$hashed_password, $username]);
    echo "รีเซ็ตรหัสผ่านสำเร็จ! รหัสผ่านใหม่ของคุณคือ: <b>password123</b> <br><a href='login.php'>คลิกที่นี่เพื่อไปหน้าล็อกอิน</a>";
} catch(PDOException $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>