<?php
session_start();
require_once 'config/database.php';

// Check if user logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$user_id = $_SESSION['username'] ?? '';
$current_role = $_SESSION['role'] ?? 'student';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] ?? '' === 'change_password') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error = 'Password baru tidak cocok. Silakan coba lagi.';
    } else if (strlen($new_password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $conn = getDB();
        if ($conn) {
            try {
                // Verify old password
                $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                if ($user && $old_password === $user['password']) {
                    // Update password
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                    $stmt->execute([$new_password, $user_id]);
                    $message = 'Password berhasil diubah!';
                } else {
                    $error = 'Password lama salah.';
                }
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan. Silakan coba lagi.';
            }
        } else {
            $error = 'Database tidak tersedia.';
        }
    }
}

// Handle profile update (for students)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] ?? '' === 'update_profile') {
    $name = $_POST['name'] ?? '';
    $gender = $_POST['gender'] ?? '';

    if (!$name) {
        $error = 'Nama tidak boleh kosong.';
    } else if (!$gender) {
        $error = 'Gender harus dipilih.';
    } else {
        $conn = getDB();
        if ($conn && isset($_SESSION['student_id'])) {
            try {
                $stmt = $conn->prepare("UPDATE students SET name = ?, gender = ? WHERE student_id = ?");
                $stmt->execute([$name, $gender, $_SESSION['student_id']]);
                $_SESSION['name'] = $name;
                $message = 'Profil berhasil diperbarui!';
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan. Silakan coba lagi.';
            }
        } else {
            $error = 'Database tidak tersedia atau profile tidak dapat diubah.';
        }
    }
}

// Get user profile
$conn = getDB();
$student_info = null;
if ($conn && isset($_SESSION['student_id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$_SESSION['student_id']]);
        $student_info = $stmt->fetch();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings – SMAN 1 GADINGREJO</title>
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
        <div><h2>SMAN 1 GADINGREJO</h2><p>Smart Attendance System</p></div>
        <button id="sidebarClose" class="sidebar-close"><i class="fa fa-xmark"></i></button>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Menu Utama</div>
        <a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>
        <?php if ($current_role === 'student'): ?>
        <a href="my-status.php"><i class="fa fa-chart-line"></i> Status Kehadiran</a>
        <?php elseif ($current_role === 'teacher'): ?>
        <a href="classes.php"><i class="fa fa-chalkboard"></i> Kelas Saya</a>
        <?php elseif ($current_role === 'admin'): ?>
        <a href="admin.php"><i class="fa fa-users"></i> Management</a>
        <?php endif; ?>
        <a href="settings.php" class="active"><i class="fa fa-gear"></i> Settings</a>
        <a href="login.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1><i class="fa fa-gear"></i> Settings Akun</h1>
            <p>Kelola profil dan keamanan akun Anda</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-2">
            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa fa-lock"></i> Ganti Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Password Lama</label>
                            <input type="password" name="old_password" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" required class="form-control" minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="confirm_password" required class="form-control" minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Edit Profile (for students) -->
            <?php if ($current_role === 'student' && $student_info): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa fa-user"></i> Edit Profil</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" value="<?php echo $student_info['name']; ?>" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required class="form-control">
                                <option value="">-- Pilih --</option>
                                <option value="L" <?php echo $student_info['gender'] === 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo $student_info['gender'] === 'P' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" value="<?php echo $student_info['date_of_birth']; ?>" disabled class="form-control">
                            <small style="color: var(--muted);">Tanggal lahir tidak dapat diubah</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update Profil
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Account Info -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3><i class="fa fa-info-circle"></i> Informasi Akun</h3>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo $user_id; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nama Pengguna:</span>
                    <span class="info-value"><?php echo $_SESSION['name'] ?? 'N/A'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role:</span>
                    <span class="info-value badge badge-<?php echo $current_role === 'student' ? 'student' : ($current_role === 'teacher' ? 'teacher' : 'admin'); ?>">
                        <?php echo ucfirst($current_role); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: var(--muted);
}

.info-value {
    font-weight: 600;
    color: var(--text);
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-student { background: rgba(96,165,250,0.1); color: #0284c7; }
.badge-teacher { background: rgba(168,85,247,0.1); color: #7c3aed; }
.badge-admin { background: rgba(248,113,113,0.1); color: #dc2626; }
</style>

<script src="assets/js/app.js"></script>
</body>
</html>