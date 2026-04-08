<?php
session_start();
require_once 'config/database.php';

$role = $_GET['role'] ?? $_SESSION['role'] ?? 'teacher';
if (!in_array($role, ['teacher','admin'])) {
    header('Location: dashboard.php?role='.$role);
    exit;
}

$selectedClass = $_GET['class'] ?? 'XII IPA 1';
$classes = ['XII IPA 1','XII IPA 2','XII IPS 1','XII IPS 2','XI IPA 1','XI IPA 2'];
$attendance = getMockAttendance();

// Filter by class
$classStudents = array_filter($attendance, fn($s) => $s['class'] === $selectedClass);
$classTotal   = count($classStudents);
$classPresent = count(array_filter($classStudents, fn($s) => $s['status']==='present'));
$classLate    = count(array_filter($classStudents, fn($s) => $s['status']==='late'));
$classAbsent  = count(array_filter($classStudents, fn($s) => $s['status']==='absent'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Monitoring – SMAN 1 Gadingrejo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div id="pageLoader" class="page-loader"><div class="loader-ring"></div></div>
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="logo-big">S1G</div>
        <div><h2>SMAN 1 Gadingrejo</h2><p>Smart Attendance System</p></div>
        <button id="sidebarClose" class="sidebar-close"><i class="fa fa-xmark"></i></button>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Menu Utama</div>
        <a href="dashboard.php?role=<?= $role ?>"><i class="fa fa-gauge"></i> Dashboard</a>
        <a href="classes.php?role=<?= $role ?>" class="active"><i class="fa fa-chalkboard"></i> Class Monitoring</a>
        <a href="#"><i class="fa fa-clock-rotate-left"></i> Activity Logs</a>
        <?php if ($role === 'admin'): ?>
        <a href="#"><i class="fa fa-users"></i> Student Management</a>
        <?php endif; ?>
        <a href="#"><i class="fa fa-gear"></i> Settings</a>
        <div class="sidebar-section">Akun</div>
        <a href="login.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
    <div class="sidebar-footer">Smart Attendance v1.0 • SMAN 1 Gadingrejo</div>
</aside>

<header class="navbar">
    <button id="hamburgerBtn" class="hamburger"><span></span><span></span><span></span></button>
    <div class="brand">
        <div class="logo-circle">S1G</div>
        <div><h1>SMAN 1 Gadingrejo</h1><p>Class Monitoring</p></div>
    </div>
    <div class="nav-right">
        <div class="clock-box">
            <div class="date" id="clock-date"></div>
            <div class="time" id="clock-time"></div>
        </div>
        <span class="badge-role badge-<?= $role ?>">
            <span class="badge-dot"></span> <?= ucfirst($role) ?>
        </span>
    </div>
</header>

<div class="page-wrapper">
<main class="main-content fade-in">

    <!-- PAGE HEADER -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px;font-weight:800">Class Monitoring</h2>
            <p style="font-size:13px;color:var(--muted)">Monitor kehadiran per kelas • <?= date('d F Y') ?></p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <!-- Class selector -->
            <form method="GET" style="display:flex;gap:8px;align-items:center">
                <input type="hidden" name="role" value="<?= $role ?>">
                <label style="font-size:13px;font-weight:600;color:var(--dark)">
                    <i class="fa fa-chalkboard" style="color:var(--primary)"></i> Kelas:
                </label>
                <select name="class" class="filter-select" onchange="this.form.submit()">
                    <?php foreach ($classes as $cls): ?>
                    <option value="<?= $cls ?>" <?= $cls === $selectedClass ? 'selected' : '' ?>><?= $cls ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <!-- Export buttons -->
            <div class="export-btn-wrap">
                <button onclick="exportTable('csv')" class="btn btn-outline btn-sm">
                    <i class="fa fa-file-csv"></i> Export CSV
                </button>
                <button onclick="exportTable('print')" class="btn btn-primary btn-sm">
                    <i class="fa fa-print"></i> Cetak
                </button>
            </div>
        </div>
    </div>

    <!-- CLASS STATS -->
    <div class="class-stats">
        <div class="stat-card present slide-up">
            <div class="stat-info">
                <div class="stat-label">Hadir</div>
                <div class="stat-value"><?= $classPresent ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-user-check"></i></div>
        </div>
        <div class="stat-card late slide-up" style="animation-delay:.08s">
            <div class="stat-info">
                <div class="stat-label">Terlambat</div>
                <div class="stat-value"><?= $classLate ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-clock"></i></div>
        </div>
        <div class="stat-card absent slide-up" style="animation-delay:.16s">
            <div class="stat-info">
                <div class="stat-label">Tidak Hadir</div>
                <div class="stat-value"><?= $classAbsent ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-user-xmark"></i></div>
        </div>
    </div>

    <!-- Attendance Rate Bar -->
    <?php $rate = $classTotal > 0 ? round(($classPresent / $classTotal)*100) : 0; ?>
    <div class="card" style="margin-bottom:24px;padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
            <div>
                <span style="font-size:14px;font-weight:700">Kelas <?= $selectedClass ?></span>
                <span style="font-size:12px;color:var(--muted);margin-left:8px">• Total <?= $classTotal ?> siswa</span>
            </div>
            <span style="font-size:18px;font-weight:800;color:var(--success)"><?= $rate ?>%</span>
        </div>
        <div style="background:var(--border);border-radius:99px;height:10px;overflow:hidden">
            <div style="width:<?= $rate ?>%;height:100%;background:linear-gradient(90deg,var(--success),#34d399);border-radius:99px;transition:width .8s ease"></div>
        </div>
        <div style="font-size:11px;color:var(--muted);margin-top:6px">Tingkat kehadiran hari ini</div>
    </div>

    <!-- STUDENT TABLE -->
    <div class="card">
        <div class="card-header">
            <i class="fa fa-table-list" style="color:var(--primary)"></i>
            <h3>Daftar Siswa – <?= $selectedClass ?></h3>
        </div>
        <div class="table-toolbar">
            <div class="search-wrap">
                <i class="fa fa-magnifying-glass"></i>
                <input type="text" id="studentSearch" class="search-input" placeholder="Cari nama / NIS...">
            </div>
            <select id="studentFilter" class="filter-select">
                <option value="all">Semua Status</option>
                <option value="present">Hadir</option>
                <option value="late">Terlambat</option>
                <option value="absent">Tidak Hadir</option>
                <option value="not_checked">Belum Check-In</option>
            </select>
        </div>
        <div style="overflow-x:auto">
        <table class="data-table" id="classTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Foto</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Status</th>
                    <th>Jam Masuk</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $allStudents = getMockAttendance();
                foreach ($allStudents as $s):
                    $pillCls = 'pill-'.$s['status'];
                    $statusLabel = match($s['status']) {
                        'present'=>'Hadir','late'=>'Terlambat','absent'=>'Tidak Hadir',default=>'Belum Check-In'
                    };
                    $initials = strtoupper(substr($s['name'],0,1));
                ?>
                <tr class="student-row"
                    data-name="<?= strtolower($s['name']) ?>"
                    data-sid="<?= $s['student_id'] ?>"
                    data-status="<?= $s['status'] ?>">
                    <td style="color:var(--muted);font-size:12px"><?= $no++ ?></td>
                    <td><div class="student-avatar"><?= $initials ?></div></td>
                    <td style="font-size:12px;color:var(--muted);font-weight:600"><?= $s['student_id'] ?></td>
                    <td><div class="student-name"><?= htmlspecialchars($s['name']) ?></div></td>
                    <td><span class="status-pill <?= $pillCls ?>"><?= $statusLabel ?></span></td>
                    <td style="font-size:13px;font-weight:600"><?= $s['check_in_time'] ?? '--:--' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

</main>
<footer class="site-footer">
    <h4>Smart Attendance Monitoring System</h4>
    <p>Powered by ESP32-S3 CAM (OV2640) • IoT Technology</p>
    <p class="footer-copy">© <?= date('Y') ?> SMAN 1 Gadingrejo • All rights reserved</p>
</footer>
</div>

<script src="assets/js/app.js"></script>
<script>
function exportTable(type) {
    if (type === 'print') {
        window.print();
    } else if (type === 'csv') {
        const rows = document.querySelectorAll('#classTable tr');
        let csv = '';
        rows.forEach(row => {
            const cells = row.querySelectorAll('th, td');
            const data = Array.from(cells).map(c => '"' + c.innerText.replace(/"/g,'""') + '"');
            csv += data.join(',') + '\n';
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'absensi_<?= str_replace(' ','_',$selectedClass) ?>_<?= date('Y-m-d') ?>.csv';
        a.click();
    }
}
</script>
</body>
</html>
