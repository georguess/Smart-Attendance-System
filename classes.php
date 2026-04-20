<?php
session_start();
require_once 'config/database.php';

$role = $_GET['role'] ?? $_SESSION['role'] ?? 'teacher';
if (!in_array($role, ['teacher','admin'])) {
    header('Location: dashboard.php');
    exit;
}

// Teacher's class logic (Prompt 6 restriction: Guru hanya melihat kelas yang dia walikan)
$conn = getDB();
$teacher_classes = [];
if ($role === 'teacher' && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT class_name FROM teachers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher = $stmt->fetch();
    if ($teacher && $teacher['class_name']) {
        $teacher_classes[] = $teacher['class_name'];
    }
}

// Build classes options
if ($role === 'admin' || empty($teacher_classes)) {
    $classes = ['XII IPA 1','XII IPA 2','XII IPS 1','XII IPS 2','XI IPA 1','XI IPA 2'];
} else {
    $classes = $teacher_classes;
}

$selectedClass = $_GET['class'] ?? $classes[0];
// Force selected class to be within allowed classes if teacher
if ($role === 'teacher' && !in_array($selectedClass, $classes)) {
    $selectedClass = $classes[0] ?? '';
}

$attendance = getMockAttendance();

// Filter by class
$classStudents = array_filter($attendance, fn($s) => $s['class'] === $selectedClass);
$classTotal   = count($classStudents);
$classPresent = count(array_filter($classStudents, fn($s) => $s['status']==='present'));
$classLate    = count(array_filter($classStudents, fn($s) => $s['status']==='late'));
$classAbsent  = count(array_filter($classStudents, fn($s) => $s['status']==='absent'));

$pageTitle = 'Class Monitoring';
require_once 'includes/layout-wrapper-start.php';
?>

    <!-- PAGE HEADER -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div>
            <a href="javascript:history.back()" class="btn btn-outline btn-sm" style="margin-bottom: 12px;">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
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

<?php require_once 'includes/layout-wrapper-end.php'; ?>
