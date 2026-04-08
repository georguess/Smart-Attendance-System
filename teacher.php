<?php
session_start();
require_once 'config/database.php';

// Check if teacher or admin
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit;
}

$schoolInfo = getSchoolInfo();
$currentDate = getCurrentDate();
$conn = getDB();

// Get filter
$filterType = $_GET['filter'] ?? 'day'; // day, week, month
$selectedClass = $_GET['class'] ?? 'XII IPA 1';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 days', strtotime($currentDate)));
$dateTo = $_GET['date_to'] ?? $currentDate;

// Get student list and attendance for selected class
$students = [];
$attendance = [];
$classes = ['XII IPA 1', 'XII IPA 2', 'XII IPS 1', 'XII IPS 2'];

if ($conn) {
    try {
        // Get students in class
        $stmt = $conn->prepare("SELECT * FROM students WHERE class = ? ORDER BY student_id");
        $stmt->execute([$selectedClass]);
        $students = $stmt->fetchAll();

        // Get attendance for these students
        if (!empty($students)) {
            $studentIds = array_map(fn($s) => $s['student_id'], $students);
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            
            $query = "SELECT a.*, s.name, s.gender FROM attendance a 
                     JOIN students s ON a.student_id = s.student_id 
                     WHERE a.student_id IN ($placeholders) 
                     AND a.date BETWEEN ? AND ? 
                     ORDER BY s.student_id, a.date DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute(array_merge($studentIds, [$dateFrom, $dateTo]));
            $attendance = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        error_log('Teacher dashboard error: ' . $e->getMessage());
    }
}

// Mock data jika kosong
if (empty($students)) {
    $students = [
        ['id' => 1, 'name' => 'Ahmad Fauzi', 'student_id' => '2024001', 'class' => 'XII IPA 1', 'gender' => 'L'],
        ['id' => 2, 'name' => 'Budi Santoso', 'student_id' => '2024002', 'class' => 'XII IPA 1', 'gender' => 'L'],
        ['id' => 3, 'name' => 'Citra Dewi', 'student_id' => '2024003', 'class' => 'XII IPA 1', 'gender' => 'P'],
    ];
}

// Handle attendance cancellation
$cancelMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_attendance_id'])) {
    $attendanceId = $_POST['cancel_attendance_id'];
    if ($conn) {
        try {
            $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
            $stmt->execute([$attendanceId]);
            $cancelMessage = 'Absensi berhasil dibatalkan';
            header('Refresh: 2');
        } catch (Exception $e) {
            $cancelMessage = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru – SMAN 1 GADINGREJO</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div id="pageLoader" class="page-loader"><div class="loader-ring"></div></div>
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="logo-big"><?php echo $schoolInfo['shortname']; ?></div>
        <div><h2><?php echo $schoolInfo['name']; ?></h2><p>Smart Attendance System</p></div>
        <button id="sidebarClose" class="sidebar-close"><i class="fa fa-xmark"></i></button>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Menu Utama</div>
        <a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>
        <a href="teacher.php" class="active"><i class="fa fa-chalkboard"></i> Kelola Absensi</a>
        <a href="settings.php"><i class="fa fa-gear"></i> Settings</a>
        <a href="login.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fa fa-chalkboard"></i> Kelola Absensi Kelas</h1>
                <p>Pantau dan kelola absensi siswa</p>
            </div>
        </div>

        <?php if ($cancelMessage): ?>
        <div class="alert alert-success" style="margin-bottom: 1rem;">
            <i class="fa fa-check-circle"></i> <?php echo $cancelMessage; ?>
        </div>
        <?php endif; ?>

        <!-- Filter & Class Selection -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body" style="padding: 1.5rem;">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <label style="font-size: 12px; color: var(--muted); display: block; margin-bottom: 6px;">Kelas</label>
                        <select name="class" onchange="this.form.submit()" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px;">
                            <?php foreach ($classes as $cls): ?>
                            <option value="<?php echo $cls; ?>" <?php echo $cls === $selectedClass ? 'selected' : ''; ?>>
                                <?php echo $cls; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: var(--muted); display: block; margin-bottom: 6px;">Filter Waktu</label>
                        <select name="filter" onchange="filterChanged(this.value)" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="day" <?php echo $filterType === 'day' ? 'selected' : ''; ?>>Hari Ini</option>
                            <option value="week" <?php echo $filterType === 'week' ? 'selected' : ''; ?>>Minggu Ini (7 hari)</option>
                            <option value="month" <?php echo $filterType === 'month' ? 'selected' : ''; ?>>Bulan Ini</option>
                            <option value="custom" <?php echo $filterType === 'custom' ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    <div id="dateFromDiv" style="display: <?php echo $filterType === 'custom' ? 'block' : 'none'; ?>;">
                        <label style="font-size: 12px; color: var(--muted); display: block; margin-bottom: 6px;">Dari</label>
                        <input type="date" name="date_from" value="<?php echo $dateFrom; ?>" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    <div id="dateToDiv" style="display: <?php echo $filterType === 'custom' ? 'block' : 'none'; ?>;">
                        <label style="font-size: 12px; color: var(--muted); display: block; margin-bottom: 6px;">Sampai</label>
                        <input type="date" name="date_to" value="<?php echo $dateTo; ?>" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="height: fit-content; align-self: flex-end;">
                        <i class="fa fa-search"></i> Filter
                    </button>
                    <button type="button" onclick="exportExcel()" class="btn btn-success" style="height: fit-content; align-self: flex-end;">
                        <i class="fa fa-file-excel"></i> Export Excel
                    </button>
                </form>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-table"></i> Daftar Absensi - <?php echo $selectedClass; ?> (<?php echo count($students); ?> Siswa)</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--surface); border-bottom: 2px solid var(--border);">
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--muted);">No</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--muted);">NISN</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--muted);">Nama Siswa</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: var(--muted);">Tanggal</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: var(--muted);">Status</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: var(--muted);">Jam Masuk</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: var(--muted);">Foto</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: var(--muted);">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $attendanceByStudent = [];
                            
                            // Group attendance by student
                            foreach ($attendance as $record) {
                                $attendanceByStudent[$record['student_id']][] = $record;
                            }

                            foreach ($students as $student):
                                $studentAttendance = $attendanceByStudent[$student['student_id']] ?? [];
                                if (empty($studentAttendance)) {
                                    $studentAttendance = [['status' => 'not_checked', 'date' => $currentDate, 'check_in_time' => null, 'name' => $student['name'], 'gender' => $student['gender']]];
                                }
                                
                                foreach ($studentAttendance as $att):
                                    $statusColor = ['present' => '#22c55e', 'late' => '#f97316', 'absent' => '#ef4444', 'not_checked' => '#999'];
                                    $statusLabel = ['present' => '✓ Hadir', 'late' => '⚠ Terlambat', 'absent' => '✗ Absen', 'not_checked' => '? Belum Check-in'];
                            ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px; text-align: left;"><?php echo $no++; ?></td>
                                <td style="padding: 12px; text-align: left;"><?php echo $student['student_id']; ?></td>
                                <td style="padding: 12px; text-align: left;">
                                    <strong><?php echo getGenderEmoji($student['gender']); ?> <?php echo $student['name']; ?></strong>
                                </td>
                                <td style="padding: 12px; text-align: center; font-size: 12px;">
                                    <?php echo date('d M Y', strtotime($att['date'])); ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="background: <?php echo $statusColor[$att['status']] ?? '#999'; ?>20; color: <?php echo $statusColor[$att['status']] ?? '#999'; ?>; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                        <?php echo $statusLabel[$att['status']] ?? 'Unknown'; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center; font-weight: 600;">
                                    <?php echo $att['check_in_time'] ? date('H:i', strtotime($att['check_in_time'])) : '--:--'; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($att['check_in_time']): ?>
                                    <button onclick="viewPhoto('<?php echo urlencode($student['name']); ?>')" class="btn-icon" title="Lihat Foto">
                                        <i class="fa fa-image"></i> Lihat
                                    </button>
                                    <?php else: ?>
                                    <span style="color: var(--muted); font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($att['status'] !== 'not_checked'): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Batalkan absensi ini?');">
                                        <input type="hidden" name="cancel_attendance_id" value="<?php echo $att['id'] ?? ''; ?>">
                                        <button type="submit" class="btn-icon btn-danger" title="Batalkan">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span style="color: var(--muted);">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RFID Scanner Info -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3><i class="fa fa-qrcode"></i> RFID Scanner</h3>
            </div>
            <div class="card-body">
                <div style="background: rgba(59,130,246,0.1); padding: 1rem; border-radius: 8px; border-left: 4px solid #3b82f6;">
                    <p><strong>📱 Cara Menggunakan Hardware RFID:</strong></p>
                    <ol style="margin: 0.5rem 0 0 1.5rem;">
                        <li>Hardware RFID terhubung ke <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">http://localhost:8000/api/attendance.php</code></li>
                        <li>Siswa tap RFID tag mereka ke scanner</li>
                        <li>Foto otomatis tersimpan (jika camera tersedia)</li>
                        <li>Data absensi langsung tersimpan di database</li>
                        <li>Guru bisa verifikasi dan cancel jika ada kesalahan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Photo Modal -->
<div id="photoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;">
    <div style="position: relative;">
        <img id="photoImg" src="" alt="Foto Kehadiran" style="max-width: 90vw; max-height: 80vh; border-radius: 10px;">
        <button onclick="closePhoto()" style="position: absolute; top: 10px; right: 10px; background: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center;">
            <i class="fa fa-xmark"></i>
        </button>
    </div>
</div>

<style>
.btn-icon {
    background: var(--primary);
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: var(--secondary);
}

.btn-danger {
    background: #ef4444 !important;
}

.btn-success {
    background: #22c55e !important;
}

.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border);
}

table tr:hover {
    background: #f5f5f5;
}

code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>

<script>
function filterChanged(value) {
    document.getElementById('dateFromDiv').style.display = value === 'custom' ? 'block' : 'none';
    document.getElementById('dateToDiv').style.display = value === 'custom' ? 'block' : 'none';
}

function viewPhoto(studentName) {
    document.getElementById('photoImg').src = 'https://via.placeholder.com/600x800?text=' + encodeURIComponent('Foto ' + studentName);
    document.getElementById('photoModal').style.display = 'flex';
}

function closePhoto() {
    document.getElementById('photoModal').style.display = 'none';
}

function downloadPhoto() {
    alert('Download akan diimplementasikan saat ada storage untuk foto');
}

function exportExcel() {
    // Collect table data
    const table = document.querySelector('table');
    let csvContent = 'data:text/csv;charset=utf-8,';
    
    // Add headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csvContent += headers.join(',') + '\n';
    
    // Add rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const cells = [];
        tr.querySelectorAll('td').forEach(td => {
            cells.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
        });
        csvContent += cells.join(',') + '\n';
    });
    
    // Download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', 'Absensi_<?php echo $selectedClass; ?>_<?php echo date('Y-m-d'); ?>.csv');
    link.click();
}

window.addEventListener('load', () => {
    document.getElementById('pageLoader').style.display = 'none';
});
</script>

<script src="assets/js/app.js"></script>
</body>
</html>