<?php
session_start();
require_once 'config/database.php';

$role = $_GET['role'] ?? $_SESSION['role'] ?? 'guest';
$validRoles = ['guest','student','teacher','admin'];
if (!in_array($role, $validRoles)) $role = 'guest';

// Load mock data
$attendance  = getMockAttendance();
$weeklyData  = getMockWeeklyData();
$activities  = getMockActivities();
$students    = getMockStudents();

// Stats
$total   = count($attendance);
$present = count(array_filter($attendance, fn($r) => $r['status'] === 'present'));
$late    = count(array_filter($attendance, fn($r) => $r['status'] === 'late'));
$absent  = count(array_filter($attendance, fn($r) => $r['status'] === 'absent'));

$roleLabels = [
    'guest'   => ['label'=>'Viewing as Guest','class'=>'badge-guest'],
    'student' => ['label'=>'Student','class'=>'badge-student'],
    'teacher' => ['label'=>'Teacher','class'=>'badge-teacher'],
    'admin'   => ['label'=>'Admin','class'=>'badge-admin'],
];
$rl = $roleLabels[$role];

$pageTitle = 'Dashboard';
require_once 'includes/layout-wrapper-start.php';
?>

    <?php if ($role === 'guest'): ?>
    <!-- GUEST NOTICE -->
    <div class="guest-notice">
        <i class="fa fa-circle-info"></i>
        <div class="guest-notice-text">
            <h4>Guest Access</h4>
            <p>You are viewing limited information as a guest. Student names and detailed records are not visible in this mode.</p>
        </div>
        <a href="login.php" class="btn btn-primary btn-sm">
            <i class="fa fa-right-to-bracket"></i> Login for Full Access
        </a>
    </div>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="stats-grid">
        <div class="stat-card total slide-up">
            <div class="stat-info">
                <div class="stat-label">Total Students</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-users"></i></div>
        </div>
        <div class="stat-card present slide-up" style="animation-delay:.08s">
            <div class="stat-info">
                <div class="stat-label">Present Today</div>
                <div class="stat-value"><?= $present ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-user-check"></i></div>
        </div>
        <div class="stat-card late slide-up" style="animation-delay:.16s">
            <div class="stat-info">
                <div class="stat-label">Late Today</div>
                <div class="stat-value"><?= $late ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-clock"></i></div>
        </div>
        <div class="stat-card absent slide-up" style="animation-delay:.24s">
            <div class="stat-info">
                <div class="stat-label">Absent Today</div>
                <div class="stat-value"><?= $absent ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-user-xmark"></i></div>
        </div>
    </div>

    <!-- CHART + ACTIVITY -->
    <div class="content-grid">
        <!-- CHART -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-chart-bar" style="color:var(--primary)"></i>
                <h3>Weekly Attendance Trend</h3>
            </div>
            <div class="card-body" style="height:320px">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <!-- ACTIVITY FEED -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-bolt" style="color:var(--warning)"></i>
                <h3>Live Attendance Feed</h3>
                <?php if ($role === 'guest'): ?>
                    <span style="font-size:11px;color:var(--muted);margin-left:auto">Login required</span>
                <?php else: ?>
                    <span class="status-pill pill-present" style="margin-left:auto;font-size:9px">● LIVE</span>
                <?php endif; ?>
            </div>

            <?php if ($role === 'guest'): ?>
            <div class="lock-state" style="min-height:280px">
                <i class="fa fa-lock"></i>
                <h4>This feature is locked</h4>
                <p>Login as Student, Teacher, or Admin to view live attendance feed</p>
                <a href="login.php" class="btn btn-primary btn-sm" style="margin-top:8px">Login Now</a>
            </div>
            <?php else: ?>
            <div class="activity-feed">
                <?php foreach ($activities as $act):
                    $initials = strtoupper(substr($act['name'],0,1));
                    $statusClass = 'act-'.$act['status'];
                    $pillClass = 'pill-'.($act['status'] === 'not_checked' ? 'not_checked' : $act['status']);
                    $statusLabel = match($act['status']) {
                        'present' => 'Hadir', 'late' => 'Terlambat',
                        'absent' => 'Absen', default => 'Belum Check'
                    };
                ?>
                <div class="activity-item">
                    <div class="activity-avatar <?= $statusClass ?>"><?= $initials ?></div>
                    <div class="activity-info">
                        <div class="activity-name"><?= htmlspecialchars($act['name']) ?></div>
                        <div class="activity-meta"><?= $act['class'] ?> • <span class="status-pill <?= $pillClass ?>"><?= $statusLabel ?></span></div>
                    </div>
                    <div class="activity-time"><?= $act['time'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (in_array($role, ['teacher','admin'])): ?>
    <!-- CAMERA MONITOR -->
    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <i class="fa fa-video" style="color:var(--danger)"></i>
            <h3>Camera Monitor (ESP32-CAM)</h3>
            <div style="margin-left:auto;display:flex;align-items:center;gap:10px;">
                <span id="camBadge" style="font-size:11px;font-weight:700;color:var(--muted)">OFF</span>
                <button id="camToggle" class="camera-toggle-btn" title="Toggle Camera"></button>
            </div>
        </div>
        <div class="card-body">
            <div class="camera-monitor" id="camMonitor">
                <div id="camOff" class="camera-off-screen">
                    <i class="fa fa-video-slash"></i>
                    <p>Camera is turned off</p>
                    <p style="font-size:10px">Toggle switch untuk mengaktifkan kamera</p>
                </div>
                <div id="camLive" style="display:none;position:absolute;inset:0;">
                    <div class="camera-live-badge"><span class="live-dot"></span>LIVE</div>
                    <div class="camera-grid">
                        <div class="cam-cell"><i class="fa fa-camera" style="font-size:20px"></i></div>
                        <div class="cam-cell" style="font-size:11px">CAM-2<br>Pintu Belakang</div>
                        <div class="cam-cell" style="font-size:11px">CAM-3<br>Lobby</div>
                        <div class="cam-cell"><i class="fa fa-signal" style="font-size:14px"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array($role, ['teacher','admin'])): ?>
    <!-- STUDENT TABLE -->
    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <i class="fa fa-table-list" style="color:var(--info)"></i>
            <h3>Student Attendance Table</h3>
            <span style="margin-left:auto;font-size:12px;color:var(--muted)"><?= date('d M Y') ?></span>
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
        <table class="data-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Jam Masuk</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance as $s):
                    $pillCls = 'pill-'.$s['status'];
                    $statusLabel = match($s['status']) {
                        'present' => 'Hadir', 'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir', default => 'Belum Check-In'
                    };
                    $initials = strtoupper(substr($s['name'],0,1));
                ?>
                <tr class="student-row"
                    data-name="<?= strtolower($s['name']) ?>"
                    data-sid="<?= $s['student_id'] ?>"
                    data-status="<?= $s['status'] ?>">
                    <td>
                        <div class="student-avatar"><?= $initials ?></div>
                    </td>
                    <td style="font-size:12px;color:var(--muted);font-weight:600"><?= $s['student_id'] ?></td>
                    <td>
                        <div class="student-cell">
                            <div>
                                <div class="student-name"><?= htmlspecialchars($s['name']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:12px"><?= $s['class'] ?></td>
                    <td><span class="status-pill <?= $pillCls ?>"><?= $statusLabel ?></span></td>
                    <td style="font-size:13px;font-weight:600"><?= $s['check_in_time'] ?? '--:--' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
    <!-- ATTENDANCE RULES -->
    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <i class="fa fa-shield-halved" style="color:var(--primary)"></i>
            <h3>Aturan Waktu Absensi</h3>
        </div>
        <div class="card-body">
            <div class="rules-list">
                <div class="rule-item">
                    <div class="rule-dot" style="background:var(--success)"></div>
                    <div class="rule-label">Hadir (Present)</div>
                    <div class="rule-time">≤ 07:30 WIB</div>
                </div>
                <div class="rule-item">
                    <div class="rule-dot" style="background:var(--warning)"></div>
                    <div class="rule-label">Terlambat (Late)</div>
                    <div class="rule-time">07:31 – 08:15 WIB</div>
                </div>
                <div class="rule-item">
                    <div class="rule-dot" style="background:var(--danger)"></div>
                    <div class="rule-label">Tidak Hadir (Absent)</div>
                    <div class="rule-time">> 08:15 WIB</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
    const weeklyData = <?= json_encode($weeklyData) ?>;
    initAttendanceChart(weeklyData);
    </script>

<?php require_once 'includes/layout-wrapper-end.php'; ?>
