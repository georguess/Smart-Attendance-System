<?php
session_start();
require_once '../config/database.php';

// Hanya untuk admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$action = $_POST['action'] ?? '';
$conn = getDB();

if (!$conn) {
    $_SESSION['error'] = "Koneksi database gagal.";
    header('Location: ../manage-students.php');
    exit;
}

// Fungsi untuk redirect dengan pesan
function redirect_with_message($type, $message, $location = '../manage-students.php') {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit;
}

// Lakukan aksi berdasarkan parameter
switch ($action) {
    case 'add_student':
        $name = $_POST['name'] ?? null;
        $nisn = $_POST['nisn'] ?? null;
        $birth_date = $_POST['birth_date'] ?? null;
        $gender = $_POST['gender'] ?? null;
        $class = $_POST['class'] ?? null;
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;
        $email = $nisn . '@siswa.sekolah.sch.id'; // Email default

        if (!$name || !$nisn || !$birth_date || !$gender || !$class || !$username || !$password) {
            redirect_with_message('error', 'Semua field wajib diisi.');
        }

        try {
            $conn->beginTransaction();

            // 1. Cek apakah username atau NISN sudah ada
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Username '$username' sudah digunakan.");
            }
            $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
            $stmt->execute([$nisn]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("NISN '$nisn' sudah terdaftar.");
            }

            // 2. Buat user baru
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'student')");
            $stmt->execute([$username, $hashed_password, $email]);
            $user_id = $conn->lastInsertId();

            // 3. Buat data siswa baru
            $stmt = $conn->prepare("INSERT INTO students (user_id, student_id, name, class, gender, birth_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $nisn, $name, $class, $gender, $birth_date]);

            $conn->commit();
            redirect_with_message('message', 'Siswa baru berhasil ditambahkan.');

        } catch (Exception $e) {
            $conn->rollBack();
            redirect_with_message('error', 'Gagal menambahkan siswa: ' . $e->getMessage());
        }
        break;

    case 'edit_student':
        $user_db_id = $_POST['user_db_id'] ?? null;
        $student_db_id = $_POST['student_db_id'] ?? null;
        $name = $_POST['name'] ?? null;
        $nisn = $_POST['nisn'] ?? null;
        $birth_date = $_POST['birth_date'] ?? null;
        $gender = $_POST['gender'] ?? null;
        $class = $_POST['class'] ?? null;
        $username = $_POST['username'] ?? null;
        $email = $_POST['email'] ?? null;

        if (!$user_db_id || !$student_db_id || !$name || !$nisn || !$birth_date || !$gender || !$class || !$username) {
            redirect_with_message('error', 'Data tidak lengkap untuk edit.');
        }

        try {
            $conn->beginTransaction();

            // Cek duplikasi username dan nisn (kecuali untuk user/student itu sendiri)
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_db_id]);
            if ($stmt->fetch()) {
                throw new Exception("Username '$username' sudah digunakan oleh user lain.");
            }
            $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
            $stmt->execute([$nisn, $student_db_id]);
            if ($stmt->fetch()) {
                throw new Exception("NISN '$nisn' sudah digunakan oleh siswa lain.");
            }

            // Update tabel users
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $user_db_id]);

            // Update tabel students
            $stmt = $conn->prepare("UPDATE students SET student_id = ?, name = ?, class = ?, gender = ?, birth_date = ? WHERE id = ?");
            $stmt->execute([$nisn, $name, $class, $gender, $birth_date, $student_db_id]);

            $conn->commit();
            redirect_with_message('message', 'Data siswa berhasil diperbarui.');

        } catch (Exception $e) {
            $conn->rollBack();
            redirect_with_message('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
        break;

    case 'add_teacher':
        $name = $_POST['name'] ?? null;
        $nip = $_POST['nip'] ?? null;
        $birth_date = $_POST['birth_date'] ?? null;
        $email = $_POST['email'] ?? null;
        $homeroom_class = $_POST['homeroom_class'] ?? null;
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$name || !$nip || !$birth_date || !$email || !$username || !$password) {
            redirect_with_message('error', 'Semua field wajib diisi (kecuali wali kelas).', '../manage-teachers.php');
        }

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Username atau Email sudah digunakan.");
            }
            $stmt = $conn->prepare("SELECT COUNT(*) FROM teachers WHERE nip = ?");
            $stmt->execute([$nip]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("NIP sudah terdaftar.");
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, name) VALUES (?, ?, ?, 'teacher', ?)");
            $stmt->execute([$username, $hashed_password, $email, $name]);
            $user_id = $conn->lastInsertId();

            $stmt = $conn->prepare("INSERT INTO teachers (user_id, nip, birth_date, homeroom_class) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $nip, $birth_date, $homeroom_class]);

            $conn->commit();
            redirect_with_message('message', 'Guru baru berhasil ditambahkan.', '../manage-teachers.php');

        } catch (Exception $e) {
            $conn->rollBack();
            redirect_with_message('error', 'Gagal menambahkan guru: ' . $e->getMessage(), '../manage-teachers.php');
        }
        break;

    case 'edit_teacher':
        $user_db_id = $_POST['user_db_id'] ?? null;
        $name = $_POST['name'] ?? null;
        $nip = $_POST['nip'] ?? null;
        $birth_date = $_POST['birth_date'] ?? null;
        $email = $_POST['email'] ?? null;
        $homeroom_class = $_POST['homeroom_class'] ?? null;
        $username = $_POST['username'] ?? null;
        $is_active = $_POST['is_active'] ?? 0;

        if (!$user_db_id || !$name || !$nip || !$birth_date || !$email || !$username) {
            redirect_with_message('error', 'Data tidak lengkap untuk edit.', '../manage-teachers.php');
        }

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_db_id]);
            if ($stmt->fetch()) {
                throw new Exception("Username atau Email sudah digunakan oleh user lain.");
            }
            $stmt = $conn->prepare("SELECT user_id FROM teachers WHERE nip = ? AND user_id != ?");
            $stmt->execute([$nip, $user_db_id]);
            if ($stmt->fetch()) {
                throw new Exception("NIP sudah digunakan oleh guru lain.");
            }

            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, name = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$username, $email, $name, $is_active, $user_db_id]);

            $stmt = $conn->prepare("UPDATE teachers SET nip = ?, birth_date = ?, homeroom_class = ? WHERE user_id = ?");
            $stmt->execute([$nip, $birth_date, $homeroom_class, $user_db_id]);

            $conn->commit();
            redirect_with_message('message', 'Data guru berhasil diperbarui.', '../manage-teachers.php');

        } catch (Exception $e) {
            $conn->rollBack();
            redirect_with_message('error', 'Gagal memperbarui data: ' . $e->getMessage(), '../manage-teachers.php');
        }
        break;

    case 'reset_teacher_account':
        $user_id = $_POST['user_id'] ?? null;
        if (!$user_id) {
            redirect_with_message('error', 'User ID tidak ditemukan.', '../manage-teachers.php');
        }

        try {
            $stmt = $conn->prepare("SELECT nip, birth_date FROM teachers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$teacher) {
                throw new Exception("Data guru tidak ditemukan.");
            }

            $new_username = $teacher['nip'];
            $new_password = date('dmY', strtotime($teacher['birth_date']));
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            $stmt->execute([$new_username, $hashed_password, $user_id]);

            redirect_with_message('message', 'Akun guru berhasil direset.', '../manage-teachers.php');

        } catch (Exception $e) {
            redirect_with_message('error', 'Gagal mereset akun: ' . $e->getMessage(), '../manage-teachers.php');
        }
        break;
    
    case 'delete_user':
        $user_id = $_POST['user_id'] ?? null;
        if (!$user_id) {
            redirect_with_message('error', 'User ID tidak ditemukan.');
        }

        try {
            // ON DELETE CASCADE akan menghapus data di tabel students/teachers
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

            redirect_with_message('message', 'Akun berhasil dihapus.');

        } catch (Exception $e) {
            redirect_with_message('error', 'Gagal menghapus akun: ' . $e->getMessage());
        }
        break;

    case 'add_admin':
        $name = $_POST['name'] ?? null;
        $username = $_POST['username'] ?? null;
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$name || !$username || !$email || !$password) {
            redirect_with_message('error', 'Semua field wajib diisi.', '../manage-admins.php');
        }

        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Username atau Email sudah digunakan.");
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, name) VALUES (?, ?, ?, 'admin', ?)");
            $stmt->execute([$username, $hashed_password, $email, $name]);

            redirect_with_message('message', 'Admin baru berhasil ditambahkan.', '../manage-admins.php');

        } catch (Exception $e) {
            redirect_with_message('error', 'Gagal menambahkan admin: ' . $e->getMessage(), '../manage-admins.php');
        }
        break;

    case 'edit_admin':
        $user_id = $_POST['user_id'] ?? null;
        $name = $_POST['name'] ?? null;
        $username = $_POST['username'] ?? null;
        $email = $_POST['email'] ?? null;

        if (!$user_id || !$name || !$username || !$email) {
            redirect_with_message('error', 'Data tidak lengkap untuk edit.', '../manage-admins.php');
        }
        if ($user_id == $_SESSION['user_id']) {
            redirect_with_message('error', 'Tidak dapat mengedit akun sendiri dari halaman ini.', '../manage-admins.php');
        }

        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception("Username atau Email sudah digunakan oleh user lain.");
            }

            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ? WHERE id = ? AND role = 'admin'");
            $stmt->execute([$name, $username, $email, $user_id]);

            redirect_with_message('message', 'Data admin berhasil diperbarui.', '../manage-admins.php');

        } catch (Exception $e) {
            redirect_with_message('error', 'Gagal memperbarui data: ' . $e->getMessage(), '../manage-admins.php');
        }
        break;

    case 'reset_admin_password':
        $user_id = $_POST['user_id'] ?? null;
        if (!$user_id) {
            redirect_with_message('error', 'User ID tidak ditemukan.', '../manage-admins.php');
        }
        if ($user_id == $_SESSION['user_id']) {
            redirect_with_message('error', 'Tidak dapat mereset password akun sendiri.', '../manage-admins.php');
        }

        try {
            $default_password = 'Admin@2026';
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
            $stmt->execute([$hashed_password, $user_id]);

            redirect_with_message('message', 'Password admin berhasil direset ke default.', '../manage-admins.php');

        } catch (Exception $e) {
            redirect_with_message('error', 'Gagal mereset password: ' . $e->getMessage(), '../manage-admins.php');
        }
        break;

    default:
        redirect_with_message('error', 'Aksi tidak valid.');
        break;
}
