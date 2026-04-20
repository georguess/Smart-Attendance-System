<?php
session_start();
require_once 'config/database.php';

$role = $_GET['role'] ?? $_SESSION['role'] ?? 'student';
if ($role !== 'student' && $role !== 'admin') {
    header('Location: dashboard.php?role='.$role);
    exit;
}

$pageTitle = 'Status Saya';

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

require_once 'includes/layout-wrapper-start.php';
?>

<div class="page-wrapper">
<main class="main-content fade-in">

    <!-- PROFILE CARD -->
    <div class="profile-card">
        <div class="profile-avatar-lg" id="profile-initials"></div>
        <div class="profile-info">
            <h2 id="student-name">Memuat...</h2>
            <p>NIS: <span id="student-nis">Memuat...</span></p>
            <div class="student-class">
                <span class="profile-badge"><i class="fa fa-school"></i> <span id="student-class">Memuat...</span></span>
                <span class="profile-badge"><i class="fa fa-clock"></i> Check-in: <span id="student-checkin-time">--:--</span> WIB</span>
            </div>
        </div>
        <div class="profile-status-box">
             <div class="status-label">Status Hari Ini</div>
             <span id="today-status-pill" class="status-pill pill-loading">Memuat...</span>
        </div>
        <div id="proof-of-attendance-container" class="proof-container">
            <!-- Tombol bukti kehadiran akan dimuat di sini oleh JS -->
        </div>
    </div>

    <div class="content-grid">
        <!-- Kolom Kiri -->
        <div class="status-left-col">
            <!-- REKAP MINGGUAN -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-chart-pie" style="color:var(--primary)"></i>
                    <h3>Rekap Kehadiran Minggu Ini</h3>
                </div>
                <div class="card-body" style="height: 280px; display: flex; align-items: center; justify-content: center;">
                    <canvas id="weekly-recap-chart"></canvas>
                    <div id="weekly-recap-placeholder" class="loading-placeholder">Memuat data chart...</div>
                </div>
            </div>

            <!-- REKAP BULANAN -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-calendar-alt" style="color:var(--info)"></i>
                    <h3>Statistik Bulan Ini (<?= date('F Y') ?>)</h3>
                </div>
                <div id="monthly-stats-body" class="card-body">
                     <div class="loading-placeholder">Memuat statistik bulanan...</div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan (Timeline) -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-timeline" style="color:var(--secondary)"></i>
                <h3>Activity Timeline (Minggu Ini)</h3>
            </div>
            <div id="weekly-timeline-body" class="card-body">
                <div class="loading-placeholder">Memuat timeline mingguan...</div>
            </div>
        </div>
    </div>

</main>

<!-- Modal Bukti Kehadiran -->
<div id="photo-proof-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Bukti Kehadiran Foto</h3>
            <button id="close-modal-btn" class="close-btn">&times;</button>
        </div>
        <div id="modal-body" class="modal-body">
            <img id="attendance-photo" src="" alt="Bukti Kehadiran" style="width: 100%; height: auto; border-radius: 8px;">
            <p id="no-photo-message" style="text-align: center; padding: 40px 0;"></p>
        </div>
    </div>
</div>


<?php require_once 'includes/layout-wrapper-end.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentNameEl = document.getElementById('student-name');
    const studentNisEl = document.getElementById('student-nis');
    const studentClassEl = document.getElementById('student-class');
    const studentCheckinEl = document.getElementById('student-checkin-time');
    const profileInitialsEl = document.getElementById('profile-initials');
    const todayStatusPillEl = document.getElementById('today-status-pill');
    
    const proofContainer = document.getElementById('proof-of-attendance-container');
    const weeklyChartCtx = document.getElementById('weekly-recap-chart').getContext('2d');
    const weeklyChartPlaceholder = document.getElementById('weekly-recap-placeholder');
    const monthlyStatsBody = document.getElementById('monthly-stats-body');
    const weeklyTimelineBody = document.getElementById('weekly-timeline-body');

    // Modal elements
    const photoModal = document.getElementById('photo-proof-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const attendancePhotoEl = document.getElementById('attendance-photo');
    const noPhotoMessageEl = document.getElementById('no-photo-message');

    let weeklyChart;

    function getInitials(name) {
        if (!name) return '';
        const parts = name.split(' ');
        if (parts.length > 1) {
            return parts[0][0] + parts[1][0];
        }
        return name.substring(0, 2);
    }

    function renderProofButton(photoPath) {
        proofContainer.innerHTML = ''; // Clear previous content
        const button = document.createElement('button');
        button.className = 'btn btn-primary';
        button.innerHTML = '<i class="fa fa-camera"></i> Bukti Kehadiran';
        
        button.addEventListener('click', () => {
            if (photoPath) {
                attendancePhotoEl.src = photoPath;
                attendancePhotoEl.style.display = 'block';
                noPhotoMessageEl.style.display = 'none';
            } else {
                attendancePhotoEl.style.display = 'none';
                noPhotoMessageEl.textContent = 'Belum ada foto untuk hari ini.';
                noPhotoMessageEl.style.display = 'block';
            }
            photoModal.style.display = 'flex';
        });
        proofContainer.appendChild(button);
    }

    function renderWeeklyChart(recapData) {
        weeklyChartPlaceholder.style.display = 'none';
        const data = {
            labels: ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa'],
            datasets: [{
                label: 'Jumlah Hari',
                data: [
                    recapData.Hadir,
                    recapData.Terlambat,
                    recapData.Izin,
                    recapData.Sakit,
                    recapData.Alpa
                ],
                backgroundColor: [
                    '#10b981', // Hadir
                    '#f59e0b', // Terlambat
                    '#3b82f6', // Izin
                    '#6366f1', // Sakit
                    '#ef4444'  // Alpa
                ],
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 2,
                hoverOffset: 8
            }]
        };

        if(weeklyChart) weeklyChart.destroy();

        weeklyChart = new Chart(weeklyChartCtx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Poppins', size: 11 },
                            padding: 16,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        titleFont: { family: 'Poppins', weight: 'bold' },
                        bodyFont: { family: 'Poppins' },
                        padding: 10,
                        cornerRadius: 6
                    }
                }
            }
        });
    }

    function renderMonthlyStats(stats) {
        const total = stats.hadir + stats.terlambat + stats.tidak_hadir;
        const attendancePercentage = total > 0 ? Math.round((stats.hadir / total) * 100) : 0;

        monthlyStatsBody.innerHTML = `
            <div class="monthly-stats-grid">
                <div class="stat-item">
                    <div class="stat-value" style="color:var(--success)">${stats.hadir}</div>
                    <div class="stat-label">Hadir</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color:var(--warning)">${stats.terlambat}</div>
                    <div class="stat-label">Terlambat</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color:var(--danger)">${stats.tidak_hadir}</div>
                    <div class="stat-label">Tidak Hadir</div>
                </div>
            </div>
            <div class="progress-bar-info">
                <span>Tingkat Kehadiran: <strong>${attendancePercentage}%</strong></span>
                <span>Total Hari Efektif: ${total}</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: ${attendancePercentage}%;"></div>
            </div>
        `;
    }

    function renderWeeklyTimeline(timeline) {
        if (timeline.length === 0) {
            weeklyTimelineBody.innerHTML = '<div class="placeholder">Tidak ada data timeline untuk minggu ini.</div>';
            return;
        }
        
        let timelineHTML = '<div class="timeline">';
        timeline.forEach(item => {
            const statusClass = item.status || 'no_record';
            const timeDisplay = item.time ? `&bull; ${item.time}` : '';
            timelineHTML += `
                <div class="timeline-item ${statusClass}">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-day-date">
                            <strong>${item.day}</strong>, ${item.date}
                        </div>
                        <div class="timeline-status">
                            ${item.status_text} ${timeDisplay}
                        </div>
                    </div>
                </div>
            `;
        });
        timelineHTML += '</div>';
        weeklyTimelineBody.innerHTML = timelineHTML;
    }

    // Fetch data from API
    fetch('api/student-status.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal mengambil data. Status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Populate profile card
            studentNameEl.textContent = '<?= htmlspecialchars($_SESSION['name'] ?? 'Siswa') ?>';
            studentNisEl.textContent = '<?= htmlspecialchars($_SESSION['student_id'] ?? 'N/A') ?>';
            studentClassEl.textContent = '<?= htmlspecialchars($_SESSION['class'] ?? 'N/A') ?>';
            profileInitialsEl.textContent = getInitials('<?= htmlspecialchars($_SESSION['name'] ?? '') ?>');

            // Find today's status from timeline
            const today = new Date().toISOString().slice(0, 10);
            const todayRecord = data.weekly_timeline.find(item => new Date(item.date + " " + new Date().getFullYear()).toISOString().slice(0,10) === today);
            
            if (todayRecord) {
                studentCheckinEl.textContent = todayRecord.time || '--:--';
                todayStatusPillEl.textContent = todayRecord.status_text;
                todayStatusPillEl.className = `status-pill pill-${todayRecord.status}`;
            } else {
                 todayStatusPillEl.textContent = 'Belum Ada Data';
                 todayStatusPillEl.className = 'status-pill pill-no_record';
            }


            // Render components
            renderProofButton(data.today_photo_path);
            renderWeeklyChart(data.weekly_recap);
            renderMonthlyStats(data.monthly_stats);
            renderWeeklyTimeline(data.weekly_timeline);
        })
        .catch(error => {
            console.error('Error fetching student status:', error);
            weeklyChartPlaceholder.textContent = 'Gagal memuat data chart.';
            monthlyStatsBody.innerHTML = '<div class="error-placeholder">Gagal memuat statistik.</div>';
            weeklyTimelineBody.innerHTML = '<div class="error-placeholder">Gagal memuat timeline.</div>';
        });

    // Modal close logic
    closeModalBtn.addEventListener('click', () => photoModal.style.display = 'none');
    window.addEventListener('click', (event) => {
        if (event.target == photoModal) {
            photoModal.style.display = 'none';
        }
    });
});
</script>
</body>
</html>
