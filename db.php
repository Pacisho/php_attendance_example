<?php
$host = 'localhost';
$dbname = 'attendance_system';
$username = 'root'; // เปลี่ยนตามเซิร์ฟเวอร์
$password = ''; // เปลี่ยนตามเซิร์ฟเวอร์

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>