<?php
session_start();
require_once 'config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Username dan password harus diisi.';
    } else {
        // Try to authenticate from database
        $conn = getDB();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT u.id, u.role, u.student_id, s.name FROM users u 
                                       LEFT JOIN students s ON u.student_id = s.student_id 
                                       WHERE u.username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user) {
                    if ($user['student_id'] && !preg_match('/^\d{8}$/', $password)) {
                        $error = 'Password siswa harus berupa 8 digit angka (ddmmyyyy).';
                    } else {
                        $expectedPassword = 'admin123';
                        if ($user['student_id']) {
                            $stmtDob = $conn->prepare("SELECT date_of_birth FROM students WHERE student_id = ?");
                            $stmtDob->execute([$user['student_id']]);
                            $dobRow = $stmtDob->fetch();
                            if ($dobRow && !empty($dobRow['date_of_birth'])) {
                                $expectedPassword = date('dmY', strtotime($dobRow['date_of_birth']));
                            } else {
                                $expectedPassword = '';
                            }
                        }

                        if ($password === $expectedPassword) {
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['student_id'] = $user['student_id'];
                            $_SESSION['name'] = $user['name'] ?? $username;

                            header("Location: dashboard.php");
                            exit;
                        }
                    }
                }

                $error = 'Username atau password salah. Coba lagi.';
            } catch (Exception $e) {
                $error = 'Database error. Silakan coba lagi nanti.';
            }
        } else {
            // Demo mode - hardcoded users
            $demoUsers = [
                '2024001' => '15032006',
                'guru001' => '20100101',
                'admin' => '20240101',
            ];

            if (isset($demoUsers[$username]) && $password === $demoUsers[$username]) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = ($username === 'admin') ? 'admin' : (($username === 'guru001') ? 'teacher' : 'student');
                $_SESSION['student_id'] = ($username === '2024001') ? '2024001' : null;
                $_SESSION['name'] = ($username === '2024001') ? 'Ahmad Fauzi' : ($username === 'guru001' ? 'Guru' : 'Admin');
                
                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Username atau password salah. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – SMAN 1 GADINGREJO</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-page">
    <div class="bg-shapes">
        <span style="width:350px;height:350px;top:-80px;left:-80px;animation-delay:0s"></span>
        <span style="width:200px;height:200px;bottom:80px;right:5%;animation-delay:2s"></span>
        <span style="width:120px;height:120px;top:40%;right:20%;animation-delay:4s"></span>
    </div>

    <div class="login-card">
        <div class="login-logo">
            <div class="logo-circle-lg">S1G</div>
            <h2>SMAN 1 GADINGREJO</h2>
            <p>Smart Attendance System</p>
        </div>

        <?php if ($error): ?>
        <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#dc2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-circle-exclamation"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label><i class="fa fa-user" style="margin-right:5px;color:var(--primary)"></i> Username (NISN)</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan NISN..." required autocomplete="username">
            </div>
            <div class="form-group">
                <label><i class="fa fa-lock" style="margin-right:5px;color:var(--primary)"></i> Password (Tanggal Lahir: ddmmyyyy)</label>
                <div style="position:relative">
                    <input type="password" name="password" id="passInput" class="form-control" placeholder="Contoh: 15032006" required autocomplete="current-password" style="padding-right:40px">
                    <button type="button" onclick="const inp = document.getElementById('passInput'); inp.type = inp.type === 'password' ? 'text' : 'password'; this.innerHTML = inp.type === 'password' ? '<i class=\"fa fa-eye\"></i>' : '<i class=\"fa fa-eye-slash\"></i>';" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:14px;">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
                <i class="fa fa-right-to-bracket"></i> Masuk ke Dashboard
            </button>
        </form>

        <div class="login-divider">atau</div>

        <div class="guest-link">
            <a href="dashboard.php?role=guest" class="btn btn-outline" style="width:100%;justify-content:center;">
                <i class="fa fa-eye"></i> Lanjutkan sebagai Guest
            </a>
        </div>
        <div style="text-align:center;margin-top:16px;">
            <a href="index.php" style="font-size:12px;color:var(--muted);"><i class="fa fa-arrow-left"></i> Kembali ke Beranda</a>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
