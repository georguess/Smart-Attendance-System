<?php
session_start();
require_once 'config/database.php';

// Akses: Admin bisa semua, Guru hanya wali kelasnya
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$class_name = $_GET['class'] ?? null;

if (!$class_name) {
    die("Nama kelas tidak ditemukan.");
}

$conn = getDB();
$class_info = null;
$students = [];

try {
    // Ambil info kelas dan wali kelas
    $stmt = $conn->prepare("
        SELECT t.homeroom_class, u.name as teacher_name, t.user_id as teacher_user_id
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        WHERE t.homeroom_class = ?
    ");
    $stmt->execute([$class_name]);
    $class_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validasi akses guru
    if ($role === 'teacher' && (!$class_info || $class_info['teacher_user_id'] != $user_id)) {
        die("Akses ditolak. Anda bukan wali kelas ini.");
    }

    // Ambil daftar siswa di kelas ini
    $stmt = $conn->prepare("SELECT id, name, student_id FROM students WHERE class = ? ORDER BY name");
    $stmt->execute([$class_name]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$pageTitle = "Detail Kelas: " . htmlspecialchars($class_name);
require_once 'includes/layout-wrapper-start.php';
?>

<div class="container-fluid">
    <div class="page-header">
        <div>
            <a href="classes.php" class="btn btn-secondary btn-sm mb-2"><i class="fa fa-arrow-left"></i> Kembali</a>
            <h1><i class="fa fa-chalkboard"></i> <?= htmlspecialchars($class_name) ?></h1>
            <p>Wali Kelas: <strong><?= htmlspecialchars($class_info['teacher_name'] ?? 'Belum ada') ?></strong></p>
        </div>
    </div>

    <ul class="nav nav-tabs" id="classTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab" aria-controls="students" aria-selected="true">Daftar Siswa</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="recap-tab" data-bs-toggle="tab" data-bs-target="#recap" type="button" role="tab" aria-controls="recap" aria-selected="false">Rekap Absensi</button>
        </li>
    </ul>

    <div class="tab-content" id="classTabContent">
        <!-- Tab Daftar Siswa -->
        <div class="tab-pane fade show active" id="students" role="tabpanel" aria-labelledby="students-tab">
            <div class="card mt-3">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>NISN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr><td colspan="3" class="text-center">Belum ada siswa di kelas ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($students as $index => $student): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Rekap Absensi -->
        <div class="tab-pane fade" id="recap" role="tabpanel" aria-labelledby="recap-tab">
            <div class="card mt-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Rekap Absensi Semester</h5>
                            <p class="mb-0 text-muted">Tahun Ajaran 2025/2026 - Semester Ganjil</p>
                        </div>
                        <button id="exportExcelBtn" class="btn btn-success"><i class="fa fa-file-excel"></i> Export ke Excel</button>
                    </div>
                    <ul class="nav nav-pills mt-3" id="month-nav">
                        <!-- Bulan akan di-generate oleh JavaScript -->
                    </ul>
                </div>
                <div class="card-body">
                    <div id="attendance-table-container" class="table-responsive">
                        <p class="text-center">Pilih bulan untuk menampilkan rekap absensi.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk menampilkan foto absensi -->
<div class="modal fade" id="attendancePhotoModal" tabindex="-1" aria-labelledby="attendancePhotoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="attendancePhotoModalLabel">Detail Kehadiran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Nama:</strong> <span id="modal-student-name"></span></p>
        <p><strong>Tanggal:</strong> <span id="modal-attendance-date"></span></p>
        <p><strong>Jam:</strong> <span id="modal-attendance-time"></span></p>
        <img id="modal-attendance-photo" src="" class="img-fluid" alt="Foto Kehadiran">
        <hr>
        <div class="d-flex justify-content-center">
             <button type="button" class="btn btn-sm btn-outline-success me-2" onclick="changeStatus(currentAttendanceCell, 'Hadir')">Hadir</button>
             <button type="button" class="btn btn-sm btn-outline-warning me-2" onclick="changeStatus(currentAttendanceCell, 'Terlambat')">Terlambat</button>
             <button type="button" class="btn btn-sm btn-outline-danger" onclick="changeStatus(currentAttendanceCell, 'Alpha')">Alpha</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthNav = document.getElementById('month-nav');
    const attendanceContainer = document.getElementById('attendance-table-container');
    const recapTab = document.getElementById('recap-tab');
    const exportExcelBtn = document.getElementById('exportExcelBtn');

    const students = <?= json_encode($students) ?>;
    const className = '<?= $class_name ?>';
    let currentMonth = new Date().getMonth() + 1;
    let currentYear = new Date().getFullYear();
    let currentAttendanceData = {};
    let currentAttendanceCell = null;

    const semesterGanjil = [
        { id: 7, name: 'Juli' }, { id: 8, name: 'Agustus' }, { id: 9, name: 'September' },
        { id: 10, name: 'Oktober' }, { id: 11, name: 'November' }, { id: 12, name: 'Desember' }
    ];

    // Generate month navigation
    semesterGanjil.forEach(month => {
        const li = document.createElement('li');
        li.className = 'nav-item';
        const button = document.createElement('button');
        button.className = `nav-link ${month.id === currentMonth ? 'active' : ''}`;
        button.textContent = month.name;
        button.onclick = () => loadAttendance(month.id, currentYear);
        li.appendChild(button);
        monthNav.appendChild(li);
    });

    recapTab.addEventListener('shown.bs.tab', function() {
        loadAttendance(currentMonth, currentYear);
    });
    
    exportExcelBtn.addEventListener('click', function() {
        exportToExcel(currentMonth, currentYear);
    });

    async function loadAttendance(month, year) {
        attendanceContainer.innerHTML = '<p class="text-center">Memuat data...</p>';
        
        // Update active button
        document.querySelectorAll('#month-nav .nav-link').forEach(btn => btn.classList.remove('active'));
        const activeBtn = Array.from(document.querySelectorAll('#month-nav .nav-link')).find(btn => btn.textContent === semesterGanjil.find(m => m.id === month).name);
        if (activeBtn) activeBtn.classList.add('active');

        currentMonth = month;
        currentYear = year;

        try {
            const response = await fetch(`api/attendance-recap.php?class=${encodeURIComponent(className)}&month=${month}&year=${year}`);
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            
            currentAttendanceData = data.attendance;
            renderTable(data.days, data.holidays);

        } catch (error) {
            attendanceContainer.innerHTML = `<p class="text-center text-danger">Gagal memuat data: ${error.message}</p>`;
        }
    }

    function renderTable(days, holidays) {
        let tableHtml = '<table class="table table-bordered table-sm text-center" id="recap-table"><thead><tr><th class="sticky-col">Nama Siswa</th>';
        days.forEach(day => {
            const isHoliday = holidays.includes(day.date);
            tableHtml += `<th class="${isHoliday ? 'bg-light' : ''}">${day.day}<br>${day.date.split('-')[2]}</th>`;
        });
        tableHtml += '</tr></thead><tbody>';

        students.forEach(student => {
            tableHtml += `<tr><td class="sticky-col student-name">${student.name}</td>`;
            days.forEach(day => {
                const isHoliday = holidays.includes(day.date);
                if (isHoliday) {
                    tableHtml += '<td class="bg-light"></td>';
                } else {
                    const attendance = currentAttendanceData[student.student_id]?.[day.date];
                    let status = attendance ? attendance.status.charAt(0).toUpperCase() : 'A';
                    let cellClass = '';
                    let clickable = '';

                    switch (status) {
                        case 'H': cellClass = 'bg-success text-white'; clickable = 'clickable'; break;
                        case 'T': cellClass = 'bg-warning text-dark'; clickable = 'clickable'; break;
                        case 'A': cellClass = 'bg-danger text-white'; break;
                    }
                    
                    tableHtml += `<td class="${cellClass} ${clickable}" data-student-id="${student.student_id}" data-date="${day.date}">${status}</td>`;
                }
            });
            tableHtml += '</tr>';
        });

        tableHtml += '</tbody></table>';
        attendanceContainer.innerHTML = tableHtml;
        
        // Add click listeners
        document.querySelectorAll('.clickable').forEach(cell => {
            cell.addEventListener('click', () => showPhotoModal(cell));
            cell.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                showContextMenu(e, cell);
            });
        });
    }

    function showPhotoModal(cell) {
        currentAttendanceCell = cell;
        const studentId = cell.dataset.studentId;
        const date = cell.dataset.date;
        const attendance = currentAttendanceData[studentId]?.[date];
        const student = students.find(s => s.student_id === studentId);

        if (attendance && student) {
            document.getElementById('modal-student-name').textContent = student.name;
            document.getElementById('modal-attendance-date').textContent = new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('modal-attendance-time').textContent = attendance.check_in_time;
            document.getElementById('modal-attendance-photo').src = attendance.photo_path ? `/${attendance.photo_path}` : 'assets/images/default-avatar.png';
            
            var myModal = new bootstrap.Modal(document.getElementById('attendancePhotoModal'));
            myModal.show();
        }
    }
    
    window.changeStatus = async function(cell, newStatusInitial) {
        const studentId = cell.dataset.studentId;
        const date = cell.dataset.date;
        const newStatus = { 'H': 'Hadir', 'T': 'Terlambat', 'A': 'Alpha' }[newStatusInitial];

        try {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('student_id', studentId);
            formData.append('date', date);
            formData.append('status', newStatus);

            const response = await fetch('api/attendance-recap.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                // Update UI
                cell.textContent = newStatusInitial;
                cell.className = ''; // Clear classes
                let cellClass = '';
                let clickable = '';
                 switch (newStatusInitial) {
                    case 'H': cellClass = 'bg-success text-white'; clickable = 'clickable'; break;
                    case 'T': cellClass = 'bg-warning text-dark'; clickable = 'clickable'; break;
                    case 'A': cellClass = 'bg-danger text-white'; break;
                }
                cell.classList.add(cellClass, clickable, 'text-center');
                
                // Update local data
                if (!currentAttendanceData[studentId]) currentAttendanceData[studentId] = {};
                if (!currentAttendanceData[studentId][date]) currentAttendanceData[studentId][date] = {};
                currentAttendanceData[studentId][date].status = newStatus;

                // Close modal if open
                var myModalEl = document.getElementById('attendancePhotoModal');
                var modal = bootstrap.Modal.getInstance(myModalEl);
                if(modal) modal.hide();

            } else {
                alert('Gagal mengubah status: ' + result.error);
            }
        } catch (error) {
            alert('Terjadi kesalahan: ' + error.message);
        }
    }
    
    function showContextMenu(event, cell) {
        // Hapus context menu yang sudah ada
        const existingMenu = document.getElementById('context-menu');
        if (existingMenu) existingMenu.remove();

        const menu = document.createElement('div');
        menu.id = 'context-menu';
        menu.style.position = 'absolute';
        menu.style.top = `${event.pageY}px`;
        menu.style.left = `${event.pageX}px`;
        menu.className = 'dropdown-menu show';
        
        menu.innerHTML = `
            <a class="dropdown-item" href="#" onclick="changeStatus(currentAttendanceCell, 'H')">Ubah ke Hadir</a>
            <a class="dropdown-item" href="#" onclick="changeStatus(currentAttendanceCell, 'T')">Ubah ke Terlambat</a>
            <a class="dropdown-item" href="#" onclick="changeStatus(currentAttendanceCell, 'A')">Ubah ke Alpha</a>
        `;
        
        currentAttendanceCell = cell;
        document.body.appendChild(menu);

        // Sembunyikan menu saat klik di tempat lain
        document.addEventListener('click', () => menu.remove(), { once: true });
    }
    
    function exportToExcel(month, year) {
        const monthName = semesterGanjil.find(m => m.id === month).name;
        const table = document.getElementById('recap-table');
        if (!table) {
            alert('Tabel rekap tidak ditemukan.');
            return;
        }
        
        const wb = XLSX.utils.table_to_book(table, { sheet: "Rekap Absensi" });
        const ws = wb.Sheets["Rekap Absensi"];
        
        // Apply styling (basic coloring)
        // This is complex with xlsx library, we'll just export data with structure
        
        const fileName = `Rekap_Absensi_${className}_${monthName}_${year}.xlsx`;
        XLSX.writeFile(wb, fileName);
    }
});
</script>
<style>
.sticky-col {
    position: -webkit-sticky;
    position: sticky;
    left: 0;
    z-index: 2;
    background-color: white;
}
#recap-table thead th.sticky-col {
    z-index: 3;
}
.table-responsive {
    max-height: 70vh;
}
.clickable {
    cursor: pointer;
}
.clickable:hover {
    opacity: 0.8;
}
</style>

<?php require_once 'includes/layout-wrapper-end.php'; ?>
