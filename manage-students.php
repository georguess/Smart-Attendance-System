<?php
session_start();
require_once 'config/database.php';

// Hanya untuk admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Manajemen Akun Siswa';
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

$conn = getDB();
$students = [];

if ($conn) {
    try {
        $sql = "
            SELECT 
                s.id as student_db_id,
                s.student_id as nisn,
                s.name,
                s.class,
                s.birth_date,
                s.gender,
                s.rfid_uid,
                u.id as user_db_id,
                u.username,
                u.email
            FROM students s
            JOIN users u ON s.user_id = u.id
            ORDER BY s.class, s.name
        ";
        $stmt = $conn->query($sql);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Gagal mengambil data siswa: " . $e->getMessage();
    }
} else {
    $error = "Koneksi database gagal.";
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
                <h1><i class="fa fa-users-cog"></i> <?= htmlspecialchars($pageTitle) ?></h1>
                <p>Kelola data akun dan informasi siswa di sistem.</p>
            </div>
        </div>
        <div class="header-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="fa fa-plus"></i> Tambah Siswa
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NISN</th>
                            <th>Username</th>
                            <th>Kelas</th>
                            <th>Status RFID</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data siswa.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td><?= htmlspecialchars($student['nisn']) ?></td>
                                    <td><?= htmlspecialchars($student['username']) ?></td>
                                    <td><?= htmlspecialchars($student['class']) ?></td>
                                    <td>
                                        <?php if ($student['rfid_uid']): ?>
                                            <span class="badge bg-success">Terdaftar</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" title="Edit" onclick='openEditModal(<?= json_encode($student) ?>)'><i class="fa fa-edit"></i></button>
                                        <button class="btn btn-sm btn-warning" title="Reset Akun"><i class="fa fa-key"></i></button>
                                        <button class="btn btn-sm btn-primary" title="Kelola RFID"><i class="fa fa-id-card"></i></button>
                                        <button class="btn btn-sm btn-danger" title="Hapus"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">Tambah Siswa Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api/manage-users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_student">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="nisn" class="form-label">NISN</label>
                        <input type="text" class="form-control" id="nisn" name="nisn" required onkeyup="updateUsername(this.value)">
                    </div>
                    <div class="mb-3">
                        <label for="birth_date" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="birth_date" name="birth_date" required onchange="updatePassword(this.value)">
                    </div>
                     <div class="mb-3">
                        <label for="gender" class="form-label">Jenis Kelamin</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="class" class="form-label">Kelas</label>
                        <input type="text" class="form-control" id="class" name="class" required>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username (default: NISN)</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password (default: tgl lahir DDMMYYYY)</label>
                        <input type="text" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Edit Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api/manage-users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_student">
                    <input type="hidden" id="edit_user_db_id" name="user_db_id">
                    <input type="hidden" id="edit_student_db_id" name="student_db_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_nisn" class="form-label">NISN</label>
                        <input type="text" class="form-control" id="edit_nisn" name="nisn" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_birth_date" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="edit_birth_date" name="birth_date" required>
                    </div>
                     <div class="mb-3">
                        <label for="edit_gender" class="form-label">Jenis Kelamin</label>
                        <select class="form-select" id="edit_gender" name="gender" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_class" class="form-label">Kelas</label>
                        <input type="text" class="form-control" id="edit_class" name="class" required>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                     <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function updateUsername(nisn) {
    document.getElementById('username').value = nisn;
}

function updatePassword(birthDate) {
    if (birthDate) {
        const date = new Date(birthDate);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        document.getElementById('password').value = `${day}${month}${year}`;
    }
}

function openEditModal(student) {
    document.getElementById('edit_user_db_id').value = student.user_db_id;
    document.getElementById('edit_student_db_id').value = student.student_db_id;
    document.getElementById('edit_name').value = student.name;
    document.getElementById('edit_nisn').value = student.nisn;
    document.getElementById('edit_birth_date').value = student.birth_date;
    document.getElementById('edit_gender').value = student.gender;
    document.getElementById('edit_class').value = student.class;
    document.getElementById('edit_username').value = student.username;
    document.getElementById('edit_email').value = student.email;

    var myModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    myModal.show();
}
</script>

<?php require_once 'includes/layout-wrapper-end.php'; ?>
