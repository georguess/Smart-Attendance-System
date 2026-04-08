// ============================================
// SMART ATTENDANCE – Main JS
// ============================================

// ── Real-time Clock ──────────────────────
function updateClock() {
    const now  = new Date();
    const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const dayName = days[now.getDay()];
    const date    = now.getDate();
    const month   = months[now.getMonth()];
    const year    = now.getFullYear();
    const hh = String(now.getHours()).padStart(2,'0');
    const mm = String(now.getMinutes()).padStart(2,'0');
    const ss = String(now.getSeconds()).padStart(2,'0');

    const dateEl = document.getElementById('clock-date');
    const timeEl = document.getElementById('clock-time');
    if (dateEl) dateEl.textContent = `${dayName}, ${date} ${month} ${year}`;
    if (timeEl) timeEl.textContent = `${hh}.${mm}.${ss}`;
}
updateClock();
setInterval(updateClock, 1000);

// ── Sidebar ──────────────────────────────
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');
const openBtn  = document.getElementById('hamburgerBtn');
const closeBtn = document.getElementById('sidebarClose');

function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
}

openBtn?.addEventListener('click', openSidebar);
closeBtn?.addEventListener('click', closeSidebar);
overlay?.addEventListener('click', closeSidebar);

// ── Camera Toggle ────────────────────────
const camToggle  = document.getElementById('camToggle');
const camMonitor = document.getElementById('camMonitor');
const camBadge   = document.getElementById('camBadge');
const camOff     = document.getElementById('camOff');
const camLive    = document.getElementById('camLive');

if (camToggle) {
    camToggle.addEventListener('click', function() {
        const isOn = this.classList.toggle('on');
        if (camOff)  camOff.style.display  = isOn ? 'none'  : 'flex';
        if (camLive) camLive.style.display  = isOn ? 'flex'  : 'none';
        if (camBadge) camBadge.textContent  = isOn ? 'LIVE'  : 'OFF';
    });
}

// ── Student Table Search & Filter ────────
const searchInput  = document.getElementById('studentSearch');
const filterSelect = document.getElementById('studentFilter');
const tableRows    = document.querySelectorAll('.student-row');

function filterTable() {
    const q      = (searchInput?.value || '').toLowerCase();
    const status = (filterSelect?.value || 'all').toLowerCase();
    tableRows.forEach(row => {
        const name   = (row.dataset.name   || '').toLowerCase();
        const sid    = (row.dataset.sid    || '').toLowerCase();
        const rStatus = (row.dataset.status || '').toLowerCase();
        const matchQ = !q || name.includes(q) || sid.includes(q);
        const matchS = status === 'all' || rStatus === status;
        row.style.display = (matchQ && matchS) ? '' : 'none';
    });
}

searchInput?.addEventListener('input',  filterTable);
filterSelect?.addEventListener('change', filterTable);

// ── Attendance Chart ─────────────────────
function initAttendanceChart(data) {
    const ctx = document.getElementById('attendanceChart');
    if (!ctx) return;

    const labels = Object.keys(data);
    const present = labels.map(d => data[d].present);
    const late    = labels.map(d => data[d].late);
    const absent  = labels.map(d => data[d].absent);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label:'Present', data: present, backgroundColor:'#3b82f6', borderRadius: 6, borderSkipped: false },
                { label:'Late',    data: late,    backgroundColor:'#f59e0b', borderRadius: 6, borderSkipped: false },
                { label:'Absent',  data: absent,  backgroundColor:'#ef4444', borderRadius: 6, borderSkipped: false },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { family:'Poppins', size:12 }, padding: 20, usePointStyle: true }
                },
                tooltip: {
                    backgroundColor:'#fff', titleColor:'#2C2C2C', bodyColor:'#6b7280',
                    borderColor:'#e5e7eb', borderWidth:1,
                    titleFont: { family:'Poppins', weight:'700' },
                    bodyFont:  { family:'Poppins' },
                    padding: 12, cornerRadius: 10,
                }
            },
            scales: {
                x: { grid: { display:false }, ticks: { font: { family:'Poppins', size:12 } } },
                y: {
                    grid: { color:'#f3f4f4', drawBorder:false },
                    ticks: { font: { family:'Poppins', size:11 } },
                    beginAtZero: true
                }
            }
        }
    });
}

// ── Donut Chart (My Status) ───────────────
function initDonutChart(present, late, absent) {
    const ctx = document.getElementById('statusDonut');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels:['Hadir','Terlambat','Tidak Hadir'],
            datasets: [{
                data: [present, late, absent],
                backgroundColor: ['#10b981','#f59e0b','#ef4444'],
                borderWidth: 0, hoverOffset: 6
            }]
        },
        options: {
            cutout: '72%',
            plugins: {
                legend: { position:'bottom', labels: { font:{family:'Poppins',size:11}, padding:16, usePointStyle:true } }
            }
        }
    });
}

// ── Loader ────────────────────────────────
window.addEventListener('load', () => {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => loader.style.display = 'none', 400);
    }
});

// ── Active sidebar link ───────────────────
const currentPage = window.location.pathname.split('/').pop();
document.querySelectorAll('.sidebar-nav a').forEach(a => {
    if (a.getAttribute('href') && a.getAttribute('href').includes(currentPage)) {
        a.classList.add('active');
    }
});

// ── Activity feed auto-scroll ─────────────
const feed = document.querySelector('.activity-feed');
if (feed && feed.scrollHeight > feed.clientHeight) {
    let scrollDir = 1;
    let feedInterval;
    const startFeedScroll = () => {
        if (feedInterval) return;
        feedInterval = setInterval(() => {
            feed.scrollTop += scrollDir;
            if (feed.scrollTop >= feed.scrollHeight - feed.clientHeight - 1) {
                scrollDir = -1;
            }
            if (feed.scrollTop <= 0) {
                scrollDir = 1;
            }
        }, 80);
    };
    const stopFeedScroll = () => {
        clearInterval(feedInterval);
        feedInterval = null;
    };

    feed.addEventListener('mouseenter', stopFeedScroll);
    feed.addEventListener('mouseleave', startFeedScroll);
    startFeedScroll();
}
