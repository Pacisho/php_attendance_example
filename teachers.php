<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

// ลบครู (ป้องกันการลบตัวเอง)
if (isset($_GET['delete']) && $_GET['delete'] != $_SESSION['teacher_id']) {
    $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: teachers.php");
    exit;
}

// เพิ่มครูใหม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = trim($_POST['name']);

    $stmt = $pdo->prepare("INSERT INTO teachers (username, password, name) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $name]);
    header("Location: teachers.php");
    exit;
}

$teachers = $pdo->query("SELECT id, username, name FROM teachers ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการข้อมูลครู - ClassManager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Kanit', sans-serif; } </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <nav class="bg-white border-b border-gray-200 px-6 py-4 flex flex-wrap justify-between items-center mb-6">
        <div class="flex items-center gap-6">
            <h1 class="text-xl font-medium">ClassManager.</h1>
            <div class="hidden md:flex gap-4 text-sm">
                <a href="index.php" class="text-gray-500 hover:text-slate-800 transition">หน้าแรก (เช็คชื่อ)</a>
                <a href="students.php" class="text-gray-500 hover:text-slate-800 transition">นักเรียน</a>
                <a href="teachers.php" class="text-slate-800 font-medium border-b-2 border-slate-800">ครู</a>
                <a href="report.php" class="text-gray-500 hover:text-slate-800 transition">รายงาน</a>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-500">ครู: <?= htmlspecialchars($_SESSION['teacher_name']) ?></span>
            <a href="logout.php" class="text-sm text-red-500 hover:text-red-600">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-medium text-lg mb-4">เพิ่มผู้ดูแลระบบ/ครู</h3>
                <form method="POST">
                    <input type="hidden" name="add" value="1">
                    <div class="mb-4">
                        <label class="block text-sm text-gray-600 mb-1">ชื่อผู้ใช้ (Username)</label>
                        <input type="text" name="username" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-slate-400" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm text-gray-600 mb-1">รหัสผ่าน</label>
                        <input type="password" name="password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-slate-400" required>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm text-gray-600 mb-1">ชื่อ - นามสกุล</label>
                        <input type="text" name="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-slate-400" required>
                    </div>
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-2 rounded-lg transition duration-200">เพิ่มข้อมูล</button>
                </form>
            </div>
        </div>

        <div class="md:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h2 class="font-medium text-lg mb-4">รายชื่อครูในระบบ</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-sm text-gray-500 border-b border-gray-100">
                            <th class="pb-3 font-medium">Username</th>
                            <th class="pb-3 font-medium">ชื่อ-สกุล</th>
                            <th class="pb-3 font-medium text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($teachers as $t): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition">
                            <td class="py-3 text-sm"><?= htmlspecialchars($t['username']) ?></td>
                            <td class="py-3 text-sm"><?= htmlspecialchars($t['name']) ?></td>
                            <td class="py-3 text-right">
                                <?php if($t['id'] != $_SESSION['teacher_id']): ?>
                                    <a href="teachers.php?delete=<?= $t['id'] ?>" onclick="return confirm('ยืนยันการลบล็อกอินนี้?')" class="text-red-500 hover:text-red-700 text-sm">ลบ</a>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">คุณ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>