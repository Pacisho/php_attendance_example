<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

// ลบข้อมูลนักเรียน
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: students.php");
    exit;
}

// เพิ่มหรือแก้ไขข้อมูลนักเรียน
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $id = $_POST['id'] ?? null;

    if ($id) { // กรณีแก้ไข
        $stmt = $pdo->prepare("UPDATE students SET student_id = ?, name = ? WHERE id = ?");
        $stmt->execute([$student_id, $name, $id]);
    } else { // กรณีเพิ่มใหม่
        $stmt = $pdo->prepare("INSERT INTO students (student_id, name) VALUES (?, ?)");
        $stmt->execute([$student_id, $name]);
    }
    header("Location: students.php");
    exit;
}

// ดึงข้อมูลนักเรียนทั้งหมด
$students = $pdo->query("SELECT * FROM students ORDER BY student_id ASC")->fetchAll();

// กรณีคลิกแก้ไข จะดึงข้อมูลเดิมมาแสดงในฟอร์ม
$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการข้อมูลนักเรียน - ClassManager</title>
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
                <a href="students.php" class="text-slate-800 font-medium border-b-2 border-slate-800">นักเรียน</a>
                <a href="teachers.php" class="text-gray-500 hover:text-slate-800 transition">ครู</a>
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
                <h3 class="font-medium text-lg mb-4"><?= $edit_data ? 'แก้ไขข้อมูลนักเรียน' : 'เพิ่มนักเรียนใหม่' ?></h3>
                <form method="POST">
                    <?php if($edit_data): ?>
                        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-4">
                        <label class="block text-sm text-gray-600 mb-1">รหัสนักเรียน</label>
                        <input type="text" name="student_id" value="<?= $edit_data ? htmlspecialchars($edit_data['student_id']) : '' ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-slate-400" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm text-gray-600 mb-1">ชื่อ - นามสกุล</label>
                        <input type="text" name="name" value="<?= $edit_data ? htmlspecialchars($edit_data['name']) : '' ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-slate-400" required>
                    </div>
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-2 rounded-lg transition duration-200">
                        <?= $edit_data ? 'บันทึกการแก้ไข' : 'เพิ่มข้อมูล' ?>
                    </button>
                    <?php if($edit_data): ?>
                        <a href="students.php" class="block text-center mt-2 text-sm text-gray-500 hover:underline">ยกเลิก</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="md:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h2 class="font-medium text-lg mb-4">รายชื่อนักเรียนทั้งหมด</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-sm text-gray-500 border-b border-gray-100">
                            <th class="pb-3 font-medium">รหัสนักเรียน</th>
                            <th class="pb-3 font-medium">ชื่อ-สกุล</th>
                            <th class="pb-3 font-medium text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $s): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition">
                            <td class="py-3 text-sm"><?= htmlspecialchars($s['student_id']) ?></td>
                            <td class="py-3 text-sm"><?= htmlspecialchars($s['name']) ?></td>
                            <td class="py-3 text-right">
                                <a href="students.php?edit=<?= $s['id'] ?>" class="text-blue-500 hover:text-blue-700 text-sm mr-3">แก้ไข</a>
                                <a href="students.php?delete=<?= $s['id'] ?>" onclick="return confirm('ยืนยันการลบ?')" class="text-red-500 hover:text-red-700 text-sm">ลบ</a>
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