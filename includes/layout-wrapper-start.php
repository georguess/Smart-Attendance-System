<?php
// includes/layout-wrapper-start.php
require_once __DIR__ . '/header.php';
?>
<div id="pageLoader" class="page-loader"><div class="loader-ring"></div></div>
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="logo-big"><?= htmlspecialchars($schoolInfo['shortname'] ?? 'S1G') ?></div>
        <div>
            <h2><?= htmlspecialchars($schoolInfo['name'] ?? 'SMAN 1 Gadingrejo') ?></h2>
            <p>Smart Attendance System</p>
        </div>
        <button id="sidebarClose" class="sidebar-close"><i class="fa fa-xmark"></i></button>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Menu Utama</div>
        <a href="dashboard.php" class="<?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>"><i class="fa fa-gauge"></i> Dashboard</a>

        <?php if ($userRole === 'student'): ?>
            <a href="my-status.php" class="<?= ($pageTitle ?? '') === 'Status Saya' ? 'active' : '' ?>"><i class="fa fa-id-card"></i> Status Saya</a>
            <a href="history.php" class="<?= ($pageTitle ?? '') === 'Riwayat Kehadiran' ? 'active' : '' ?>"><i class="fa fa-history"></i> Riwayat Kehadiran</a>
        <?php elseif ($userRole === 'teacher'): ?>
            <a href="teacher.php" class="<?= ($pageTitle ?? '') === 'Kelola Absensi' ? 'active' : '' ?>"><i class="fa fa-chalkboard"></i> Kelola Absensi</a>
        <?php elseif ($userRole === 'admin'): ?>
            <a href="admin.php" class="<?= ($pageTitle ?? '') === 'Admin Panel' ? 'active' : '' ?>"><i class="fa fa-shield"></i> Admin Panel</a>
            <div class="sidebar-section">Manajemen</div>
            <a href="manage-admins.php" class="<?= ($pageTitle ?? '') === 'Manajemen Admin' ? 'active' : '' ?>"><i class="fa fa-user-shield"></i> Manajemen Admin</a>
            <a href="manage-teachers.php" class="<?= ($pageTitle ?? '') === 'Manajemen Guru' ? 'active' : '' ?>"><i class="fa fa-chalkboard-user"></i> Manajemen Guru</a>
            <a href="manage-students.php" class="<?= ($pageTitle ?? '') === 'Manajemen Siswa' ? 'active' : '' ?>"><i class="fa fa-user-graduate"></i> Manajemen Siswa</a>
            <a href="register-rfid.php" class="<?= ($pageTitle ?? '') === 'Daftar RFID' ? 'active' : '' ?>"><i class="fa fa-id-card"></i> Daftar RFID</a>
        <?php endif; ?>

        <div class="sidebar-section">Akun</div>
        <a href="settings.php" class="<?= ($pageTitle ?? '') === 'Settings' ? 'active' : '' ?>"><i class="fa fa-gear"></i> Settings</a>
        <a href="login.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
    <div class="sidebar-footer">
        Smart Attendance v1.0 &nbsp;•&nbsp; SMAN 1 Gadingrejo
    </div>
</aside>

<header class="navbar">
    <button id="hamburgerBtn" class="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <div class="brand">
        <div class="logo-circle"><?= htmlspecialchars($schoolInfo['shortname'] ?? 'S1G') ?></div>
        <div>
            <h1><?= htmlspecialchars($schoolInfo['name'] ?? 'SMAN 1 Gadingrejo') ?></h1>
            <p><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></p>
        </div>
    </div>
    <div class="nav-right">
        <div class="clock-box">
            <div class="date" id="clock-date"></div>
            <div class="time" id="clock-time"></div>
        </div>
        <div class="user-profile-dropdown">
            <button class="user-profile-btn">
                <img id="header-profile-pic" 
                     src="<?= htmlspecialchars($_SESSION['profile_picture'] ?? 'assets/images/default-avatar.png') ?>" 
                     alt="Avatar" 
                     class="user-avatar"
                     onerror="this.onerror=null;this.src='assets/images/default-avatar.png';">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                <i class="fa fa-chevron-down"></i>
            </button>
            <div class="dropdown-content">
                <div class="dropdown-header">
                    <span class="badge-role badge-<?= $userRole ?>">
                        <span class="badge-dot"></span> <?= ucfirst($userRole) ?>
                    </span>
                </div>
                <a href="settings.php"><i class="fa fa-gear"></i> Pengaturan Akun</a>
                <a href="login.php?action=logout"><i class="fa fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<div class="page-wrapper">
    <main class="main-content fade-in">
