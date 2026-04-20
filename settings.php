<?php
session_start();

// Check if user logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$pageTitle = 'Settings';
$message = '';
$error = '';
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? '';
$current_role = $_SESSION['role'] ?? 'student';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
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
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($old_password, $user['password'])) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                    $stmt->execute([$hashed_password, $username]);
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
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
$user_info = null;
if ($conn) {
    try {
        // Get user info for profile picture
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_info = $stmt->fetch();
        $_SESSION['profile_picture'] = $user_info['profile_picture_path']; // sinkronkan session

        // Get student specific info if role is student
        if ($current_role === 'student' && isset($_SESSION['student_id'])) {
            $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->execute([$_SESSION['student_id']]);
            $student_info = $stmt->fetch();
        }
    } catch (Exception $e) {
        $error = "Gagal mengambil data pengguna.";
    }
}


require_once 'includes/layout-wrapper-start.php';
?>

<div class="container-fluid">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <button onclick="history.back()" class="btn btn-secondary" style="border:1px solid var(--border); background:var(--surface);">
                <i class="fa fa-arrow-left"></i> Kembali
            </button>
            <div>
                <h1><i class="fa fa-gear"></i> Pengaturan Akun</h1>
                <p>Kelola informasi profil dan keamanan akun Anda.</p>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" id="alert-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" id="alert-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- Profile Picture -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-image"></i> Foto Profil</h3>
            </div>
            <div class="card-body profile-picture-section">
                <img id="profile-pic-preview" 
                     src="<?= htmlspecialchars($_SESSION['profile_picture'] ?? 'assets/images/default-avatar.png') ?>" 
                     alt="Foto Profil" 
                     class="profile-pic-large"
                     onerror="this.onerror=null;this.src='assets/images/default-avatar.png';">
                
                <form id="profile-pic-form">
                    <div class="form-group">
                        <label for="profile_picture_input" class="btn btn-secondary"><i class="fa fa-upload"></i> Ganti Foto</label>
                        <input type="file" id="profile_picture_input" accept=".jpg,.jpeg,.png,.webp" style="display: none;">
                        <small>Max 2MB. Format: JPG, PNG, WEBP.</small>
                    </div>
                    <button type="submit" class="btn btn-primary" id="save-pic-btn" style="display: none;"><i class="fa fa-save"></i> Simpan Foto</button>
                </form>
            </div>
        </div>

        <!-- Profile Settings -->
        <?php if ($current_role === 'student' && $student_info): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-user-edit"></i> Edit Profil</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($student_info['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Jenis Kelamin</label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="L" <?= $student_info['gender'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $student_info['gender'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Profil</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Password Settings -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-key"></i> Ubah Password</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label for="old_password">Password Lama</label>
                        <input type="password" name="old_password" id="old_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Ubah Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => loader.style.display = 'none', 400);
    }
    
    const picForm = document.getElementById('profile-pic-form');
    const picInput = document.getElementById('profile_picture_input');
    const picPreview = document.getElementById('profile-pic-preview');
    const savePicBtn = document.getElementById('save-pic-btn');
    const alertContainer = document.querySelector('.container-fluid'); // Untuk menempatkan alert baru

    // Fungsi untuk menampilkan pesan
    function showAlert(message, type = 'success') {
        // Hapus alert lama jika ada
        const oldAlert = document.getElementById('dynamic-alert');
        if (oldAlert) oldAlert.remove();

        const alertDiv = document.createElement('div');
        alertDiv.id = 'dynamic-alert';
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        alertContainer.insertBefore(alertDiv, alertContainer.firstChild);

        setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s';
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);
    }

    // Tampilkan preview saat file dipilih
    if (picInput) {
        picInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                // Validasi sisi klien
                if (file.size > 2 * 1024 * 1024) {
                    showAlert('Ukuran file terlalu besar. Maksimal 2MB.', 'danger');
                    picInput.value = ''; // Reset input
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    picPreview.src = e.target.result;
                    savePicBtn.style.display = 'inline-block';
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Handle form submission dengan AJAX
    if (picForm) {
        picForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const file = picInput.files[0];
        if (!file) return;

        savePicBtn.disabled = true;
        savePicBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menyimpan...';

        const formData = new FormData();
        formData.append('profile_picture', file);

        fetch('api/upload-profile-picture.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                // Update gambar di header juga (jika ada)
                const headerAvatar = document.getElementById('header-profile-pic');
                if (headerAvatar) {
                    headerAvatar.src = data.new_path + '?' + new Date().getTime(); // Cache busting
                }
                savePicBtn.style.display = 'none';
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Terjadi kesalahan jaringan.', 'danger');
        })
        .finally(() => {
            savePicBtn.disabled = false;
            savePicBtn.innerHTML = '<i class="fa fa-save"></i> Simpan Foto';
        });
    });
    }

    // Sembunyikan alert bawaan dari PHP setelah beberapa detik
    const staticAlert = document.getElementById('alert-message');
    if (staticAlert) {
        setTimeout(() => {
            staticAlert.style.transition = 'opacity 0.5s';
            staticAlert.style.opacity = '0';
            setTimeout(() => staticAlert.remove(), 500);
        }, 5000);
    }
});
</script>

<?php require_once 'includes/layout-wrapper-end.php'; ?>
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

<?php require_once 'includes/layout-wrapper-end.php'; ?>