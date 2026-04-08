<?php
session_start();
require_once 'config/database.php';

// Check if student
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'] ?? null;
$schoolInfo = getSchoolInfo();
$currentDate = getCurrentDate();

// Get filter dari request
$filterType = $_GET['filter'] ?? 'week'; // week, month
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days', strtotime($currentDate)));
$dateTo = $_GET['date_to'] ?? $currentDate;

$conn = getDB();
$attendance = [];
$monthSummary = ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];
$studentInfo = null;

if ($conn && $student_id) {
    try {
        // Get student info
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $studentInfo = $stmt->fetch();

        // Get attendance records
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? AND date BETWEEN ? AND ? ORDER BY date DESC");
        $stmt->execute([$student_id, $dateFrom, $dateTo]);
        $attendance = $stmt->fetchAll();

        // Get month summary
        $monthStart = date('Y-m-01', strtotime($currentDate));
        $monthEnd = $currentDate;
        $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? AND date BETWEEN ? AND ? GROUP BY status");
        $stmt->execute([$student_id, $monthStart, $monthEnd]);
        $summaries = $stmt->fetchAll();

        foreach ($summaries as $summary) {
            $monthSummary[$summary['status']]++;
        }
        $monthSummary['total'] = array_sum($monthSummary) - $monthSummary['total'];
    } catch (Exception $e) {}
}

// Jika tidak ada data, gunakan mock
if (empty($attendance)) {
    $attendance = [
        ['id' => 1, 'student_id' => '2024001', 'status' => 'present', 'check_in_time' => '07:15:00', 'date' => date('Y-m-d')],
        ['id' => 2, 'student_id' => '2024001', 'status' => 'late', 'check_in_time' => '07:45:00', 'date' => date('Y-m-d', strtotime('-1 days'))],
        ['id' => 3, 'student_id' => '2024001', 'status' => 'present', 'check_in_time' => '07:10:00', 'date' => date('Y-m-d', strtotime('-2 days'))],
        ['id' => 4, 'student_id' => '2024001', 'status' => 'absent', 'check_in_time' => null, 'date' => date('Y-m-d', strtotime('-3 days'))],
    ];
}

if (!$studentInfo) {
    $studentInfo = ['name' => 'Ahmad Fauzi', 'student_id' => '2024001', 'class' => 'XII IPA 1', 'gender' => 'L'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Kehadiran – SMAN 1 GADINGREJO</title>
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
        <a href="history.php" class="active"><i class="fa fa-history"></i> Riwayat Kehadiran</a>
        <a href="settings.php"><i class="fa fa-gear"></i> Settings</a>
        <a href="login.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fa fa-history"></i> Riwayat Kehadiran</h1>
                <p><?php echo getGenderEmoji($studentInfo['gender'] ?? 'L'); ?> <?php echo $studentInfo['name']; ?> | <?php echo $studentInfo['class']; ?></p>
            </div>
            <div class="date-display">
                <span><?php echo date('d M Y', strtotime($currentDate)); ?></span>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body" style="padding: 1.5rem;">
                <form method="GET" style="display: grid; grid-template-columns: auto auto auto auto 1fr; gap: 1rem; align-items: end;">
                    <div>
                        <label style="font-size: 12px; color: var(--muted);">Filter Tipe</label>
                        <select name="filter" onchange="filterChanged(this.value)" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="week" <?php echo $filterType === 'week' ? 'selected' : ''; ?>>Minggu Ini (7 hari)</option>
                            <option value="month" <?php echo $filterType === 'month' ? 'selected' : ''; ?>>Bulan Ini</option>
                            <option value="custom" <?php echo $filterType === 'custom' ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    <div id="dateFrom" style="display: <?php echo $filterType === 'custom' ? 'block' : 'none'; ?>;">
                        <label style="font-size: 12px; color: var(--muted);">Dari</label>
                        <input type="date" name="date_from" value="<?php echo $dateFrom; ?>" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    <div id="dateTo" style="display: <?php echo $filterType === 'custom' ? 'block' : 'none'; ?>;">
                        <label style="font-size: 12px; color: var(--muted);">Sampai</label>
                        <input type="date" name="date_to" value="<?php echo $dateTo; ?>" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="height: fit-content;">
                        <i class="fa fa-search"></i> Filter
                    </button>
                </form>
            </div>
        </div>

        <!-- Monthly Summary -->
        <div class="grid grid-4" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(34,197,94,0.1);">
                    <i class="fa fa-check" style="color: #22c55e;"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Hadir</div>
                    <div class="stat-value"><?php echo $monthSummary['present']; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(249,115,22,0.1);">
                    <i class="fa fa-clock" style="color: #f97316;"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Terlambat</div>
                    <div class="stat-value"><?php echo $monthSummary['late']; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(239,68,68,0.1);">
                    <i class="fa fa-xmark" style="color: #ef4444;"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Absen</div>
                    <div class="stat-value"><?php echo $monthSummary['absent']; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(59,130,246,0.1);">
                    <i class="fa fa-calendar" style="color: #3b82f6;"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total</div>
                    <div class="stat-value"><?php echo $monthSummary['total']; ?></div>
                </div>
            </div>
        </div>

        <!-- Attendance Table with Photo -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-table"></i> Detail Kehadiran (<?php echo count($attendance); ?> Hari)</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                        <thead>
                            <tr style="background: var(--surface); border-bottom: 2px solid var(--border);">
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--muted);">No</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--muted);">Tanggal</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--muted);">Hari</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: var(--muted);">Status</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: var(--muted);">Jam Masuk</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: var(--muted);">Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance as $key => $record): 
                                $statusColor = [
                                    'present' => ['#22c55e', '🟢 Hadir'],
                                    'late' => ['#f97316', '🟡 Terlambat'],
                                    'absent' => ['#ef4444', '🔴 Absen'],
                                    'not_checked' => ['#999', '⚫ Belum check-in']
                                ];
                                $status = $record['status'];
                                list($color, $label) = $statusColor[$status] ?? ['#999', 'Unknown'];
                                $dayName = date('l', strtotime($record['date'])); // Sabtu, Minggu
                                $dayNameID = [
                                    'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 
                                    'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 
                                    'Sunday' => 'Minggu'
                                ][$dayName] ?? $dayName;
                            ?>
                            <tr style="border-bottom: 1px solid var(--border); hover: background #f5f5f5;">
                                <td style="padding: 12px; text-align: left;"><?php echo $key + 1; ?></td>
                                <td style="padding: 12px; text-align: left;">
                                    <strong><?php echo date('d M Y', strtotime($record['date'])); ?></strong>
                                </td>
                                <td style="padding: 12px; text-align: left;">
                                    <span style="font-size: 12px; color: var(--muted);"><?php echo $dayNameID; ?></span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                        <?php echo $label; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <strong><?php echo $record['check_in_time'] ? date('H:i', strtotime($record['check_in_time'])) : '--:--'; ?></strong>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($record['check_in_time']): ?>
                                    <button onclick="viewPhoto()" style="background: var(--primary); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">
                                        <i class="fa fa-image"></i> Lihat
                                    </button>
                                    <?php else: ?>
                                    <span style="color: var(--muted); font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($attendance)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--muted);">
                    <i class="fa fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                    Tidak ada data kehadiran untuk periode ini
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Photo Viewer Modal -->
<div id="photoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;">
    <div style="position: relative; max-width: 90%; max-height: 90%;">
        <img id="photoImg" src="" alt="Foto Kehadiran" style="max-width: 100%; max-height: 80vh; border-radius: 10px;">
        <button onclick="closePhoto()" style="position: absolute; top: 10px; right: 10px; background: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px;">
            <i class="fa fa-xmark"></i>
        </button>
        <button onclick="downloadPhoto()" style="position: absolute; bottom: 10px; right: 10px; background: var(--primary); color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer;">
            <i class="fa fa-download"></i> Download
        </button>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border);
}

.date-display {
    background: var(--primary);
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 12px;
}

.stat-card {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 12px;
    display: flex;
    gap: 1rem;
    align-items: center;
    border: 1px solid var(--border);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
}

.stat-label {
    font-size: 12px;
    color: var(--muted);
}

.grid-4 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}

table tr:hover {
    background: #f5f5f5;
}

#photoModal {
    display: flex !important;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function filterChanged(value) {
    const dateFromDiv = document.getElementById('dateFrom');
    const dateToDiv = document.getElementById('dateTo');
    if (value === 'custom') {
        dateFromDiv.style.display = 'block';
        dateToDiv.style.display = 'block';
    } else {
        dateFromDiv.style.display = 'none';
        dateToDiv.style.display = 'none';
    }
}

function viewPhoto() {
    // Simulasi foto - di production ambil dari URL actual atau base64
    document.getElementById('photoImg').src = 'https://via.placeholder.com/600x800?text=Foto+Kehadiran';
    document.getElementById('photoModal').style.display = 'flex';
}

function closePhoto() {
    document.getElementById('photoModal').style.display = 'none';
}

function downloadPhoto() {
    alert('Download foto akan diimplementasikan saat ada storage untuk foto');
    // const link = document.createElement('a');
    // link.href = document.getElementById('photoImg').src;
    // link.download = 'attendance-photo.jpg';
    // link.click();
}

// Hide loader
window.addEventListener('load', () => {
    document.getElementById('pageLoader').style.display = 'none';
});
</script>

<script src="assets/js/app.js"></script>
</body>
</html>