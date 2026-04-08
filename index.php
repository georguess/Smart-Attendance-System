<?php
$pageTitle = 'Beranda';
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance – SMAN 1 Gadingrejo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- PAGE LOADER -->
<div id="pageLoader" class="page-loader">
    <div class="loader-ring"></div>
    <p style="font-size:13px;color:var(--muted)">Memuat halaman...</p>
</div>

<!-- NAVBAR -->
<nav class="lp-navbar">
    <div class="brand">
        <div class="logo-circle">S1G</div>
        <div>
            <h1>SMAN 1 Gadingrejo</h1>
            <p>Smart Attendance System</p>
        </div>
    </div>
    <div class="lp-nav-links">
        <a href="#features"><i class="fa fa-star" style="font-size:11px"></i> Fitur</a>
        <a href="#preview"><i class="fa fa-eye" style="font-size:11px"></i> Preview</a>
        <a href="login.php" class="btn btn-outline btn-sm">Login</a>
        <a href="dashboard.php?role=guest" class="btn btn-primary btn-sm">
            <i class="fa fa-gauge"></i> Dashboard
        </a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-shapes">
        <span class="hero-shape"></span>
        <span class="hero-shape"></span>
        <span class="hero-shape"></span>
    </div>
    <div class="hero-content slide-up">
        <div class="hero-badge">
            <i class="fa fa-wifi"></i>
            IoT Powered • ESP32-CAM • Face Recognition
        </div>
        <h2>Smart Attendance<br><span>System</span></h2>
        <p class="hero-sub">SMAN 1 Gadingrejo – Sistem Monitoring Absensi Berbasis IoT<br>Real-time, akurat, dan mudah digunakan</p>
        <div class="hero-buttons">
            <a href="dashboard.php?role=guest" class="btn btn-white btn-lg">
                <i class="fa fa-gauge"></i> Buka Dashboard
            </a>
            <a href="login.php" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.35);">
                <i class="fa fa-right-to-bracket"></i> Login Sekarang
            </a>
        </div>
    </div>
    <div class="hero-scroll">
        <span>Scroll untuk melihat fitur</span>
        <i class="fa fa-chevron-down"></i>
    </div>
</section>

<!-- STATS STRIP -->
<div class="stats-section">
    <h2>Dipercaya oleh SMAN 1 Gadingrejo</h2>
    <p>Monitoring absensi lebih mudah dan efisien dengan teknologi terkini</p>
    <div class="stats-numbers">
        <div class="stat-num"><h3>500+</h3><p>Total Siswa</p></div>
        <div class="stat-num"><h3>98%</h3><p>Akurasi Absensi</p></div>
        <div class="stat-num"><h3>Real-time</h3><p>Monitoring Data</p></div>
        <div class="stat-num"><h3>4</h3><p>Level Akses</p></div>
    </div>
</div>

<!-- FEATURES -->
<section class="section" id="features" style="background:var(--white);">
    <div class="section-header">
        <div class="section-badge"><i class="fa fa-star"></i> Fitur Unggulan</div>
        <h2>Teknologi Terdepan untuk Absensi Modern</h2>
        <p>Sistem absensi IoT berbasis face recognition dengan dashboard monitoring real-time</p>
    </div>
    <div class="features-grid">
        <div class="feature-card fade-in">
            <div class="feature-icon"><i class="fa fa-chart-line"></i></div>
            <h3>Real-time Monitoring</h3>
            <p>Pantau kehadiran siswa secara langsung dengan data yang diperbarui otomatis setiap saat.</p>
        </div>
        <div class="feature-card fade-in" style="animation-delay:.1s">
            <div class="feature-icon"><i class="fa fa-camera"></i></div>
            <h3>Face Recognition</h3>
            <p>Integrasi dengan ESP32-CAM untuk absensi otomatis berbasis pengenalan wajah yang akurat.</p>
        </div>
        <div class="feature-card fade-in" style="animation-delay:.2s">
            <div class="feature-icon"><i class="fa fa-shield-halved"></i></div>
            <h3>Role-based Access</h3>
            <p>Kontrol akses berlapis untuk Tamu, Siswa, Guru, dan Admin dengan fitur berbeda tiap level.</p>
        </div>
        <div class="feature-card fade-in" style="animation-delay:.3s">
            <div class="feature-icon"><i class="fa fa-file-chart-column"></i></div>
            <h3>Smart Analytics</h3>
            <p>Laporan dan grafik kehadiran mingguan yang mudah dibaca dan dapat diekspor.</p>
        </div>
    </div>
</section>

<!-- PREVIEW -->
<section class="preview-section" id="preview">
    <div class="preview-inner">
        <div class="section-header">
            <div class="section-badge"><i class="fa fa-eye"></i> Preview</div>
            <h2>Dashboard Monitoring Modern</h2>
            <p>Tampilan bersih dan informatif untuk memantau absensi sekolah</p>
        </div>
        <div class="preview-mockup">
            <div class="mockup-bar">
                <span class="mockup-dot" style="background:#ff5f57"></span>
                <span class="mockup-dot" style="background:#febc2e"></span>
                <span class="mockup-dot" style="background:#28c840"></span>
                <div class="url-bar">dashboard.php?role=student</div>
                <i class="fa fa-lock" style="font-size:11px;color:var(--muted)"></i>
            </div>
            <div class="mockup-content">
                <div class="mock-card c1">
                    <div class="mock-label">Total Siswa</div>
                    <div class="mock-value">512</div>
                </div>
                <div class="mock-card c2">
                    <div class="mock-label">Hadir Hari Ini</div>
                    <div class="mock-value">480</div>
                </div>
                <div class="mock-card c3">
                    <div class="mock-label">Terlambat</div>
                    <div class="mock-value">22</div>
                </div>
                <div class="mock-card c4">
                    <div class="mock-label">Tidak Hadir</div>
                    <div class="mock-value">10</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="cta-inner">
        <div class="section-badge" style="margin:0 auto 14px;display:inline-flex"><i class="fa fa-rocket"></i> Mulai Sekarang</div>
        <h2>Siap Memantau Absensi Sekolah?</h2>
        <p>Akses dashboard sebagai tamu tanpa perlu login, atau masuk dengan akun Anda untuk fitur lengkap.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="dashboard.php?role=guest" class="btn btn-primary btn-lg">
                <i class="fa fa-gauge"></i> Lihat Dashboard
            </a>
            <a href="login.php" class="btn btn-outline btn-lg">
                <i class="fa fa-right-to-bracket"></i> Login Akun
            </a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="site-footer">
    <h4>Smart Attendance Monitoring System</h4>
    <p>Powered by IoT Smart Attendance • ESP32-S3 CAM • Face Recognition Technology</p>
    <hr class="footer-divider">
    <p>Dikembangkan oleh <strong>Himpunan Mahasiswa Teknik Elektro</strong><br>Fakultas Teknik • Universitas Lampung</p>
    <p class="footer-copy">© <?= date('Y') ?> SMAN 1 Gadingrejo • All rights reserved</p>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
