<?php
session_start();
require_once 'config/database.php';

$role = $_GET['role'] ?? $_SESSION['role'] ?? 'student';
if ($role !== 'student' && $role !== 'admin') {
    header('Location: dashboard.php?role='.$role);
    exit;
}

// Mock student profile (logged in student)
$student = [
    'name'       => 'Ahmad Fauzi',
    'student_id' => '2024001',
    'class'      => 'XII IPA 1',
    'status'     => 'present',
    'check_in'   => '07:15',
];

$weekly = [
    ['day'=>'Sen','status'=>'present','h'=>80],
    ['day'=>'Sel','status'=>'present','h'=>80],
    ['day'=>'Rab','status'=>'late',   'h'=>50],
    ['day'=>'Kam','status'=>'present','h'=>80],
    ['day'=>'Jum','status'=>'present','h'=>80],
];

$monthSummary = ['present'=>18,'late'=>3,'absent'=>1,'total'=>22];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Saya – SMAN 1 Gadingrejo</title>
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
        <a href="dashboard.php?role=student"><i class="fa fa-gauge"></i> Dashboard</a>
        <a href="my-status.php?role=student" class="active"><i class="fa fa-id-card"></i> Status Saya</a>
        <a href="#"><i class="fa fa-clock-rotate-left"></i> Activity Logs</a>
        <div class="sidebar-section">Akun</div>
        <a href="login.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
    <div class="sidebar-footer">Smart Attendance v1.0 • SMAN 1 Pringsewu</div>
</aside>

<header class="navbar">
    <button id="hamburgerBtn" class="hamburger"><span></span><span></span><span></span></button>
    <div class="brand">
        <div class="logo-circle">S1P</div>
        <div><h1>SMAN 1 Pringsewu</h1><p>My Status</p></div>
    </div>
    <div class="nav-right">
        <div class="clock-box">
            <div class="date" id="clock-date"></div>
            <div class="time" id="clock-time"></div>
        </div>
        <span class="badge-role badge-student"><span class="badge-dot"></span> Student</span>
    </div>
</header>

<div class="page-wrapper">
<main class="main-content fade-in">

    <!-- PROFILE CARD -->
    <div class="profile-card">
        <div class="profile-avatar-lg">AF</div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($student['name']) ?></h2>
            <p>NIS: <?= $student['student_id'] ?></p>
            <div class="student-class">
                <span class="profile-badge"><i class="fa fa-school"></i> <?= $student['class'] ?></span>
                <?php
                $pillCls = 'pill-'.$student['status'];
                $statusLabel = match($student['status']) {
                    'present'=>'Hadir','late'=>'Terlambat','absent'=>'Tidak Hadir',default=>'Belum Check'
                };
                ?>
                <span class="profile-badge"><i class="fa fa-clock"></i> Check-in: <?= $student['check_in'] ?> WIB</span>
            </div>
        </div>
        <div style="margin-left:auto;text-align:center">
            <div style="font-size:11px;opacity:.8;margin-bottom:6px">Status Hari Ini</div>
            <span class="status-pill <?= $pillCls ?>" style="font-size:14px;padding:8px 18px"><?= $statusLabel ?></span>
        </div>
    </div>

    <div class="content-grid">
        <!-- LEFT COLUMN -->
        <div style="display:flex;flex-direction:column;gap:24px">

            <!-- WEEKLY SUMMARY -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-calendar-week" style="color:var(--primary)"></i>
                    <h3>Rekap Mingguan</h3>
                </div>
                <div class="card-body">
                    <div class="weekly-grid">
                        <?php foreach ($weekly as $w):
                            $color = match($w['status']) {
                                'present'=>'var(--success)','late'=>'var(--warning)','absent'=>'var(--danger)',default=>'var(--muted)'
                            };
                            $sLabel = match($w['status']) {
                                'present'=>'H','late'=>'T','absent'=>'A',default=>'?'
                            };
                        ?>
                        <div class="week-day">
                            <div class="week-day-label"><?= $w['day'] ?></div>
                            <div class="week-day-bar-wrap">
                                <div class="week-day-bar <?= $w['status'] ?>" style="height:<?= $w['h'] ?>%;background:<?= $color ?>"></div>
                            </div>
                            <div class="week-day-status" style="color:<?= $color ?>"><?= $sLabel ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display:flex;gap:16px;justify-content:center;margin-top:16px;font-size:11px;">
                        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--success);margin-right:4px"></span>Hadir</span>
                        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--warning);margin-right:4px"></span>Terlambat</span>
                        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--danger);margin-right:4px"></span>Tidak Hadir</span>
                    </div>
                </div>
            </div>

            <!-- MONTHLY SUMMARY -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-calendar" style="color:var(--info)"></i>
                    <h3>Rekap Bulan Ini</h3>
                    <span style="margin-left:auto;font-size:11px;color:var(--muted)"><?= date('F Y') ?></span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
                        <div style="text-align:center;padding:16px;background:var(--bg);border-radius:10px">
                            <div style="font-size:28px;font-weight:800;color:var(--success)"><?= $monthSummary['present'] ?></div>
                            <div style="font-size:11px;color:var(--muted);font-weight:600">Hadir</div>
                        </div>
                        <div style="text-align:center;padding:16px;background:var(--bg);border-radius:10px">
                            <div style="font-size:28px;font-weight:800;color:var(--warning)"><?= $monthSummary['late'] ?></div>
                            <div style="font-size:11px;color:var(--muted);font-weight:600">Terlambat</div>
                        </div>
                        <div style="text-align:center;padding:16px;background:var(--bg);border-radius:10px">
                            <div style="font-size:28px;font-weight:800;color:var(--danger)"><?= $monthSummary['absent'] ?></div>
                            <div style="font-size:11px;color:var(--muted);font-weight:600">Tidak Hadir</div>
                        </div>
                    </div>
                    <!-- Progress bar -->
                    <?php $pct = round(($monthSummary['present'] / $monthSummary['total']) * 100); ?>
                    <div style="font-size:12px;color:var(--muted);margin-bottom:6px;">Tingkat Kehadiran: <strong style="color:var(--success)"><?= $pct ?>%</strong></div>
                    <div style="background:var(--border);border-radius:99px;height:8px;overflow:hidden">
                        <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--success),#34d399);border-radius:99px;transition:width .5s ease"></div>
                    </div>
                    <div style="font-size:11px;color:var(--muted);margin-top:8px">Total <?= $monthSummary['total'] ?> hari efektif</div>
                </div>
            </div>
        </div>

        <!-- TIMELINE -->
        <div class="card" style="height:fit-content">
            <div class="card-header">
                <i class="fa fa-timeline" style="color:var(--secondary)"></i>
                <h3>Activity Timeline</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item present">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">Jumat, 29 Mar 2026 • 07:15</div>
                        <div class="timeline-label">Check-in – Hadir ✅</div>
                    </div>
                    <div class="timeline-item late">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">Kamis, 28 Mar 2026 • 07:48</div>
                        <div class="timeline-label">Check-in – Terlambat ⚠️</div>
                    </div>
                    <div class="timeline-item present">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">Rabu, 27 Mar 2026 • 07:12</div>
                        <div class="timeline-label">Check-in – Hadir ✅</div>
                    </div>
                    <div class="timeline-item present">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">Selasa, 26 Mar 2026 • 07:05</div>
                        <div class="timeline-label">Check-in – Hadir ✅</div>
                    </div>
                    <div class="timeline-item absent">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">Senin, 25 Mar 2026 • --:--</div>
                        <div class="timeline-label">Tidak Hadir ❌</div>
                    </div>
                    <div class="timeline-item present">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">Jumat, 22 Mar 2026 • 07:20</div>
                        <div class="timeline-label">Check-in – Hadir ✅</div>
                    </div>
                    <div class="timeline-item present">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time">Kamis, 21 Mar 2026 • 07:18</div>
                        <div class="timeline-label">Check-in – Hadir ✅</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>
<footer class="site-footer">
    <h4>Smart Attendance Monitoring System</h4>
    <p>Powered by ESP32-S3 CAM (OV2640) • IoT Technology</p>
    <p class="footer-copy">© <?= date('Y') ?> SMAN 1 Pringsewu • All rights reserved</p>
</footer>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
