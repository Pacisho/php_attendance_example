<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$today = date('Y-m-d');
$current_time = date('H:i:s');

// --- จัดการ AJAX Request เบื้องหลัง ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json'); // บอกว่าตอบกลับเป็น JSON
    
    $student_id = $_POST['student_id'] ?? null;
    
    if ($_POST['ajax_action'] == 'check') {
        $status = $_POST['status'] ?? 'present';
        try {
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, teacher_id, check_date, check_time, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $_SESSION['teacher_id'], $today, $current_time, $status]);
            echo json_encode(['success' => true, 'time' => date('H:i', strtotime($current_time)), 'status' => $status]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด หรือเช็คชื่อไปแล้ว']);
        }
        exit; // สำคัญ: จบการทำงาน PHP ตรงนี้ถ้าเป็น AJAX
    }
    
    if ($_POST['ajax_action'] == 'cancel') {
        try {
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE student_id = ? AND check_date = ?");
            $stmt->execute([$student_id, $today]);
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการยกเลิก']);
        }
        exit;
    }
}
// --- จบส่วน AJAX ---


// ดึงข้อมูลนักเรียนปกติ
$stmt_students = $pdo->prepare("
    SELECT s.*, a.status as current_status, a.check_time 
    FROM students s 
    LEFT JOIN attendance a ON s.id = a.student_id AND a.check_date = ? 
    ORDER BY s.student_id ASC
");
$stmt_students->execute([$today]);
$students = $stmt_students->fetchAll();

$stats_stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE check_date = ? GROUP BY status");
$stats_stmt->execute([$today]);
$today_stats = $stats_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// สร้าง array ไว้ส่งให้ JavaScript ใช้งานด้วย
$status_map = [
    'present' => ['label' => 'มาเรียน', 'color' => 'bg-green-100 text-green-700 border-green-200'],
    'late' => ['label' => 'สาย', 'color' => 'bg-yellow-100 text-yellow-700 border-yellow-200'],
    'absent' => ['label' => 'ขาดเรียน', 'color' => 'bg-red-100 text-red-700 border-red-200'],
    'leave' => ['label' => 'ลา', 'color' => 'bg-blue-100 text-blue-700 border-blue-200']
];
$status_map_json = json_encode($status_map);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบเช็คชื่อ - ClassManager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Kanit', sans-serif; } </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <nav class="bg-white border-b border-gray-200 px-6 py-4 flex flex-wrap justify-between items-center mb-6">
        <div class="flex items-center gap-6">
            <h1 class="text-xl font-medium">ClassManager.</h1>
            <div class="hidden md:flex gap-4 text-sm">
                <a href="index.php" class="text-slate-800 font-medium border-b-2 border-slate-800">หน้าแรก (เช็คชื่อ)</a>
                <a href="students.php" class="text-gray-500 hover:text-slate-800 transition">นักเรียน</a>
                <a href="teachers.php" class="text-gray-500 hover:text-slate-800 transition">ครู</a>
                <a href="report.php" class="text-gray-500 hover:text-slate-800 transition">รายงาน</a>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-500">ครู: <?= htmlspecialchars($_SESSION['teacher_name']) ?></span>
            <a href="logout.php" class="text-sm text-red-500 hover:text-red-600">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-6 mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-medium text-lg mb-4">สถิติวันนี้ (<?= date('d/m/Y') ?>)</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 p-4 rounded-xl text-center">
                        <span class="block text-sm text-green-600 mb-1">มาเรียน</span>
                        <span class="text-2xl font-medium text-green-700" id="stat-present"><?= $today_stats['present'] ?? 0 ?></span>
                    </div>
                    <div class="bg-red-50 p-4 rounded-xl text-center">
                        <span class="block text-sm text-red-600 mb-1">ขาดเรียน</span>
                        <span class="text-2xl font-medium text-red-700" id="stat-absent"><?= $today_stats['absent'] ?? 0 ?></span>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-xl text-center">
                        <span class="block text-sm text-yellow-600 mb-1">สาย</span>
                        <span class="text-2xl font-medium text-yellow-700" id="stat-late"><?= $today_stats['late'] ?? 0 ?></span>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-xl text-center">
                        <span class="block text-sm text-blue-600 mb-1">ลา</span>
                        <span class="text-2xl font-medium text-blue-700" id="stat-leave"><?= $today_stats['leave'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="md:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h2 class="font-medium text-lg mb-4">เช็คชื่อนักเรียน</h2>
            
            <div id="alert-box" class="hidden text-sm p-3 rounded-lg mb-4"></div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-sm text-gray-500 border-b border-gray-100">
                            <th class="pb-3 font-medium">รหัสนักเรียน</th>
                            <th class="pb-3 font-medium">ชื่อ-สกุล</th>
                            <th class="pb-3 font-medium text-right">สถานะ / จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $student): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition" id="row-<?= $student['id'] ?>">
                            <td class="py-3 text-sm"><?= htmlspecialchars($student['student_id']) ?></td>
                            <td class="py-3 text-sm student-name"><?= htmlspecialchars($student['name']) ?></td>
                            <td class="py-3 text-right" id="action-td-<?= $student['id'] ?>">
                                
                                <?php if($student['current_status']): ?>
                                    <?php $badge = $status_map[$student['current_status']]; ?>
                                    <div class="inline-flex flex-col items-end gap-1">
                                        <div class="flex items-center gap-3">
                                            <span class="px-3 py-1 rounded-md text-sm font-medium border <?= $badge['color'] ?>">
                                                เช็คแล้ว: <?= $badge['label'] ?>
                                            </span>
                                            <button type="button" onclick="cancelAttendance(<?= $student['id'] ?>, '<?= $student['current_status'] ?>')" class="text-sm text-red-500 hover:text-red-700 hover:underline transition">
                                                ยกเลิก
                                            </button>
                                        </div>
                                        <span class="text-xs text-gray-400">เวลา <?= date('H:i', strtotime($student['check_time'])) ?> น.</span>
                                    </div>
                                <?php else: ?>
                                    <div class="inline-flex gap-2">
                                        <select id="status-<?= $student['id'] ?>" class="text-sm border border-gray-200 rounded-md px-2 py-1 focus:outline-none focus:border-slate-400">
                                            <option value="present">มา</option>
                                            <option value="late">สาย</option>
                                            <option value="leave">ลา</option>
                                            <option value="absent">ขาด</option>
                                        </select>
                                        <button type="button" onclick="saveAttendance(<?= $student['id'] ?>)" class="text-sm bg-slate-800 text-white px-3 py-1 rounded-md hover:bg-slate-700 transition">บันทึก</button>
                                    </div>
                                <?php endif; ?>

                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const statusMap = <?= $status_map_json ?>;

        function showAlert(message, type) {
            const box = document.getElementById('alert-box');
            box.className = `text-sm p-3 rounded-lg mb-4 ${type === 'success' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500'}`;
            box.innerText = message;
            box.style.display = 'block';
            setTimeout(() => box.style.display = 'none', 3000); // ซ่อนอัตโนมัติหลัง 3 วิ
        }

        // อัปเดตตัวเลขสถิติบนหน้าจอ
        function updateStat(status, change) {
            const el = document.getElementById(`stat-${status}`);
            if(el) {
                let current = parseInt(el.innerText) || 0;
                el.innerText = Math.max(0, current + change);
            }
        }

        // ส่งข้อมูลบันทึก
        function saveAttendance(studentId) {
            const status = document.getElementById(`status-${studentId}`).value;
            const formData = new FormData();
            formData.append('ajax_action', 'check');
            formData.append('student_id', studentId);
            formData.append('status', status);

            fetch('', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // เปลี่ยน UI ทันที
                        const td = document.getElementById(`action-td-${studentId}`);
                        const badge = statusMap[status];
                        td.innerHTML = `
                            <div class="inline-flex flex-col items-end gap-1 fade-in">
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 rounded-md text-sm font-medium border ${badge.color}">
                                        เช็คแล้ว: ${badge.label}
                                    </span>
                                    <button type="button" onclick="cancelAttendance(${studentId}, '${status}')" class="text-sm text-red-500 hover:text-red-700 hover:underline transition">
                                        ยกเลิก
                                    </button>
                                </div>
                                <span class="text-xs text-gray-400">เวลา ${data.time} น.</span>
                            </div>
                        `;
                        updateStat(status, 1);
                        showAlert('บันทึกข้อมูลสำเร็จ', 'success');
                    } else {
                        showAlert(data.message, 'error');
                    }
                });
        }

        // ยกเลิกข้อมูล
        function cancelAttendance(studentId, currentStatus) {
            const studentName = document.querySelector(`#row-${studentId} .student-name`).innerText;
            if(!confirm(`ต้องการยกเลิกการเช็คชื่อของ ${studentName} ใช่หรือไม่?`)) return;

            const formData = new FormData();
            formData.append('ajax_action', 'cancel');
            formData.append('student_id', studentId);

            fetch('', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // คืนค่ากลับเป็นฟอร์ม
                        const td = document.getElementById(`action-td-${studentId}`);
                        td.innerHTML = `
                            <div class="inline-flex gap-2 fade-in">
                                <select id="status-${studentId}" class="text-sm border border-gray-200 rounded-md px-2 py-1 focus:outline-none focus:border-slate-400">
                                    <option value="present">มา</option>
                                    <option value="late">สาย</option>
                                    <option value="leave">ลา</option>
                                    <option value="absent">ขาด</option>
                                </select>
                                <button type="button" onclick="saveAttendance(${studentId})" class="text-sm bg-slate-800 text-white px-3 py-1 rounded-md hover:bg-slate-700 transition">บันทึก</button>
                            </div>
                        `;
                        updateStat(currentStatus, -1);
                        showAlert('ยกเลิกการเช็คชื่อเรียบร้อยแล้ว', 'success');
                    } else {
                        showAlert(data.message, 'error');
                    }
                });
        }
    </script>
    <style>
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</body>
</html>