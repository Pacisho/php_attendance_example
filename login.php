<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['teacher_id'] = $user['id'];
        $_SESSION['teacher_name'] = $user['name'];
        header("Location: index.php");
        exit;
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ - ระบบเช็คชื่อ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Kanit', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 w-full max-w-sm">
        <h2 class="text-2xl font-medium text-gray-800 mb-6 text-center">เข้าสู่ระบบ</h2>
        <?php if(isset($error)): ?>
            <div class="bg-red-50 text-red-500 text-sm p-3 rounded-lg mb-4"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm text-gray-600 mb-1">ชื่อผู้ใช้</label>
                <input type="text" name="username" placeholder="กรุณากรอกชื่อผู้ใช้" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition" required>
            </div>
            <div class="mb-6">
                <label class="block text-sm text-gray-600 mb-1">รหัสผ่าน</label>
                <input type="password" placeholder="กรุณากรอกรหัสผ่าน" name="password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition" required>
            </div>
            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-2 rounded-lg transition duration-200">เข้าสู่ระบบ</button>
        </form>
    </div>
</body>
</html>