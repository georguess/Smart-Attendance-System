<?php
session_start();
require_once 'config/database.php';

// Check if admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$schoolInfo = getSchoolInfo();
$currentDate = getCurrentDate();
$conn = getDB();

// Default data
$allStudents = [];
$allClasses = ['XII IPA 1', 'XII IPA 2', 'XII IPS 1', 'XII IPS 2', 'XI IPA 1', 'XI IPA 2'];
$totalAttendanceToday = ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];

if ($conn) {
    try {
        // Get all students
        $stmt = $conn->prepare("SELECT * FROM students ORDER BY class, student_id");
        $stmt->execute([]);
        $allStudents = $stmt->fetchAll();

        // Get today's attendance summary
        $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE date = ? GROUP BY status");
        $stmt->execute([$currentDate]);
        $summaries = $stmt->fetchAll();

        foreach ($summaries as $summary) {
            $totalAttendanceToday[$summary['status']] = $summary['count'];
        }
        $totalAttendanceToday['total'] = count($allStudents);
    } catch (Exception $e) {}
}

// Mock data jika kosong
if (empty($allStudents)) {
    $allStudents = [
        ['id' => 1, 'name' => 'Ahmad Fauzi', 'student_id' => '2024001', 'class' => 'XII IPA 1', 'gender' => 'L'],
        ['id' => 2, 'name' => 'Budi Santoso', 'student_id' => '2024002', 'class' => 'XII IPA 1', 'gender' => 'L'],
        ['id' => 3, 'name' => 'Citra Dewi', 'student_id' => '2024003', 'class' => 'XII IPA 2', 'gender' => 'P'],
        ['id' => 4, 'name' => 'Dian Permata', 'student_id' => '2024004', 'class' => 'XII IPS 1', 'gender' => 'P'],
        ['id' => 5, 'name' => 'Eko Prasetyo', 'student_id' => '2024005', 'class' => 'XII IPS 1', 'gender' => 'L'],
        ['id' => 6, 'name' => 'Fitri Handayani', 'student_id' => '2024006', 'class' => 'XII IPA 2', 'gender' => 'P'],
    ];
}

// Get tab
$tab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – SMAN 1 GADINGREJO</title>
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
        <a href="admin.php" class="active"><i class="fa fa-shield"></i> Admin Panel</a>
        <a href="settings.php"><i class="fa fa-gear"></i> Settings</a>
        <a href="login.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fa fa-shield"></i> Admin Dashboard</h1>
                <p>Kelola siswa, guru, dan kelas</p>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid var(--border); flex-wrap: wrap;">
            <a href="?tab=overview" class="tab-btn <?php echo $tab === 'overview' ? 'active' : ''; ?>" style="text-decoration: none; display: inline-flex;">
                <i class="fa fa-chart-pie"></i> Overview
            </a>
            <a href="?tab=students" class="tab-btn <?php echo $tab === 'students' ? 'active' : ''; ?>" style="text-decoration: none; display: inline-flex;">
                <i class="fa fa-users"></i> Siswa (<?php echo count($allStudents); ?>)
            </a>
            <a href="?tab=classes" class="tab-btn <?php echo $tab === 'classes' ? 'active' : ''; ?>" style="text-decoration: none; display: inline-flex;">
                <i class="fa fa-chalkboard"></i> Kelas
            </a>
            <a href="?tab=reports" class="tab-btn <?php echo $tab === 'reports' ? 'active' : ''; ?>" style="text-decoration: none; display: inline-flex;">
                <i class="fa fa-file-pdf"></i> Laporan
            </a>
        </div>

        <!-- Tab: Overview -->
        <?php if ($tab === 'overview'): ?>
        <div class="grid grid-4" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(34,197,94,0.1);">
                    <i class="fa fa-check" style="color: #22c55e;"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Hadir Hari Ini</div>
                    <div class="stat-value"><?php echo $totalAttendanceToday['present']; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(249,115,22,0.1);">
                    <i class="fa fa-clock" style="color: #f97316;"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Terlambat</div>
                    <div class="stat-value"><?php echo $totalAttendanceToday['late']; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(239,68,68,0.1);">
                    <i class="fa fa-xmark" style="color: #ef4444;"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Absen</div>
                    <div class="stat-value"><?php echo $totalAttendanceToday['absent']; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(59,130,246,0.1);">
                    <i class="fa fa-users" style="color: #3b82f6;"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Siswa</div>
                    <div class="stat-value"><?php echo $totalAttendanceToday['total']; ?></div>
                </div>
            </div>
        </div>

        <!-- Class Summary -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-list"></i> Ringkasan Per Kelas (Hari Ini)</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--surface); border-bottom: 2px solid var(--border);">
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Kelas</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600;">Total Siswa</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #22c55e;">Hadir</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #f97316;">Terlambat</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #ef4444;">Absen</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600;">Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $classStats = [];
                            foreach ($allStudents as $student) {
                                if (!isset($classStats[$student['class']])) {
                                    $classStats[$student['class']] = ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];
                                }
                                $classStats[$student['class']]['total']++;
                            }
                            
                            foreach ($classStats as $class => $stats):
                            ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px; font-weight: 600;"><?php echo $class; ?></td>
                                <td style="padding: 12px; text-align: center;"><?php echo $stats['total']; ?></td>
                                <td style="padding: 12px; text-align: center; color: #22c55e; font-weight: 600;">0</td>
                                <td style="padding: 12px; text-align: center; color: #f97316; font-weight: 600;">0</td>
                                <td style="padding: 12px; text-align: center; color: #ef4444; font-weight: 600;">0</td>
                                <td style="padding: 12px; text-align: center;">
                                    <div style="background: #f5f5f5; border-radius: 20px; overflow: hidden; height: 6px;">
                                        <div style="width: 0%; height: 100%; background: #22c55e;"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Students -->
        <?php elseif ($tab === 'students'): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-users"></i> Daftar Siswa (<?php echo count($allStudents); ?>)</h3>
                <button onclick="alert('Fitur tambah siswa akan ditambahkan')" class="btn btn-primary" style="margin-left: auto;">
                    <i class="fa fa-plus"></i> Tambah Siswa
                </button>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--surface); border-bottom: 2px solid var(--border);">
                                <th style="padding: 12px; text-align: left; font-weight: 600;">No</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600;">NISN</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Nama</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Kelas</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600;">Gender</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allStudents as $key => $student): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px;"><?php echo $key + 1; ?></td>
                                <td style="padding: 12px;">
                                    <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;"><?php echo $student['student_id']; ?></code>
                                </td>
                                <td style="padding: 12px;">
                                    <strong><?php echo getGenderEmoji($student['gender']); ?> <?php echo $student['name']; ?></strong>
                                </td>
                                <td style="padding: 12px;"><?php echo $student['class']; ?></td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php echo getGenderEmoji($student['gender']); ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <button onclick="alert('Edit siswa')" class="btn-icon" style="background: var(--primary); color: white; border: none; padding: 6px; border-radius: 6px; cursor: pointer;">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button onclick="alert('Hapus siswa')" class="btn-icon" style="background: #ef4444; color: white; border: none; padding: 6px; border-radius: 6px; cursor: pointer; margin-left: 4px;">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Classes -->
        <?php elseif ($tab === 'classes'): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-chalkboard"></i> Manajemen Kelas</h3>
            </div>
            <div class="card-body">
                <p style="color: var(--muted); margin-bottom: 1rem;">Setiap kelas bisa diassign ke guru/wali kelas tertentu.</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <?php foreach ($allClasses as $class): 
                        $classStudentCount = count(array_filter($allStudents, fn($s) => $s['class'] === $class));
                    ?>
                    <div style="background: var(--surface); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border);">
                        <h4 style="margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fa fa-chalkboard"></i> <?php echo $class; ?>
                        </h4>
                        <p style="margin: 0; font-size: 12px; color: var(--muted);">
                            👥 <?php echo $classStudentCount; ?> Siswa
                        </p>
                        <button onclick="alert('Assign guru ke kelas')" class="btn btn-primary" style="width: 100%; margin-top: 1rem; cursor: pointer;">
                            <i class="fa fa-user-tie"></i> Assign Guru
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Tab: Reports -->
        <?php elseif ($tab === 'reports'): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-file-pdf"></i> Laporan & Export</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <button onclick="alert('Export laporan kehadiran bulan ini')" class="btn btn-success" style="padding: 1rem; cursor: pointer;">
                        <i class="fa fa-file-excel"></i><br>
                        <strong>Laporan Kehadiran</strong><br>
                        <small>Export ke Excel</small>
                    </button>
                    <button onclick="alert('Print laporan absensi')" class="btn btn-primary" style="padding: 1rem; cursor: pointer;">
                        <i class="fa fa-print"></i><br>
                        <strong>Print Laporan</strong><br>
                        <small>Format PDF/Print</small>
                    </button>
                    <button onclick="alert('Generate statistik kehadiran')" class="btn btn-info" style="padding: 1rem; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fa fa-chart-bar"></i><br>
                        <strong>Statistik</strong><br>
                        <small>Per kelas / siswa</small>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<style>
.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border);
}

.tab-btn {
    padding: 10px 16px;
    border: none;
    background: none;
    color: var(--muted);
    font-weight: 600;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.tab-btn:hover {
    color: var(--text);
}

.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.stat-card {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 12px;
    display: flex;
    gap: 1rem;
    align-items: center;
    border: 1px solid var(--border);
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
}

.stat-label {
    font-size: 12px;
    color: var(--muted);
}

.grid-4 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}

.btn-icon {
    background: none;
    border: none;
    padding: 6px 10px;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

@media (max-width: 768px) {
    .tab-btn {
        font-size: 12px;
    }

    .grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
window.addEventListener('load', () => {
    document.getElementById('pageLoader').style.display = 'none';
});
</script>

<script src="assets/js/app.js"></script>
</body>
</html>