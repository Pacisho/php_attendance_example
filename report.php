<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

// กำหนดวันที่ค้นหา ค่าเริ่มต้นคือวันนี้
$filter_date = $_GET['date'] ?? date('Y-m-d');

// ดึงข้อมูลการเข้าเรียนตามวันที่เลือก Join ตารางนักเรียนและครู
$stmt = $pdo->prepare("
    SELECT a.*, s.student_id as s_id, s.name as student_name, t.name as teacher_name 
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN teachers t ON a.teacher_id = t.id
    WHERE a.check_date = ?
    ORDER BY s.student_id ASC
");
$stmt->execute([$filter_date]);
$records = $stmt->fetchAll();

// แปลงสถานะเป็นภาษาไทยและสี
$status_map = [
    'present' => ['label' => 'มาเรียน', 'color' => 'bg-green-100 text-green-700'],
    'late' => ['label' => 'สาย', 'color' => 'bg-yellow-100 text-yellow-700'],
    'absent' => ['label' => 'ขาดเรียน', 'color' => 'bg-red-100 text-red-700'],
    'leave' => ['label' => 'ลา', 'color' => 'bg-blue-100 text-blue-700']
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานการเข้าเรียน - ClassManager</title>
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
                <a href="teachers.php" class="text-gray-500 hover:text-slate-800 transition">ครู</a>
                <a href="report.php" class="text-slate-800 font-medium border-b-2 border-slate-800">รายงาน</a>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-500">ครู: <?= htmlspecialchars($_SESSION['teacher_name']) ?></span>
            <a href="logout.php" class="text-sm text-red-500 hover:text-red-600">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h2 class="font-medium text-xl">สรุปการเข้าเรียน</h2>
                
                <form method="GET" class="flex gap-2 w-full md:w-auto">
                    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit()" class="px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-slate-400 text-sm cursor-pointer hover:bg-gray-50 transition">
                </form>
            </div>

            <?php if(count($records) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-sm text-gray-500 border-b border-gray-100">
                            <th class="pb-3 font-medium">รหัสนักเรียน</th>
                            <th class="pb-3 font-medium">ชื่อ-สกุล</th>
                            <th class="pb-3 font-medium text-center">เวลาที่เช็ค</th>
                            <th class="pb-3 font-medium text-center">สถานะ</th>
                            <th class="pb-3 font-medium text-right">ผู้บันทึก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($records as $row): 
                            $badge = $status_map[$row['status']];
                        ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition">
                            <td class="py-3 text-sm"><?= htmlspecialchars($row['s_id']) ?></td>
                            <td class="py-3 text-sm"><?= htmlspecialchars($row['student_name']) ?></td>
                            <td class="py-3 text-sm text-center"><?= date('H:i', strtotime($row['check_time'])) ?> น.</td>
                            <td class="py-3 text-sm text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?= $badge['color'] ?>">
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td class="py-3 text-sm text-right text-gray-500"><?= htmlspecialchars($row['teacher_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="text-center py-10 text-gray-400">
                    <p>ไม่มีข้อมูลการเช็คชื่อในวันที่ <?= date('d/m/Y', strtotime($filter_date)) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>