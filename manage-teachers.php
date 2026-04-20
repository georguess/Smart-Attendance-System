<?php
session_start();
require_once 'config/database.php';

// Hanya untuk admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Manajemen Akun Guru';
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

$conn = getDB();
$teachers = [];

if ($conn) {
    try {
        $sql = "
            SELECT 
                t.id as teacher_db_id,
                t.nip,
                t.homeroom_class,
                u.id as user_db_id,
                u.username,
                u.email,
                u.name,
                u.is_active,
                t.birth_date
            FROM teachers t
            JOIN users u ON t.user_id = u.id
            ORDER BY u.name
        ";
        $stmt = $conn->query($sql);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Gagal mengambil data guru: " . $e->getMessage();
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
                <h1><i class="fa fa-user-tie"></i> <?= htmlspecialchars($pageTitle) ?></h1>
                <p>Kelola data akun dan informasi guru di sistem.</p>
            </div>
        </div>
        <div class="header-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                <i class="fa fa-plus"></i> Tambah Guru
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
                            <th>Username</th>
                            <th>Email</th>
                            <th>Wali Kelas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data guru.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?= htmlspecialchars($teacher['name']) ?></td>
                                    <td><?= htmlspecialchars($teacher['username']) ?></td>
                                    <td><?= htmlspecialchars($teacher['email']) ?></td>
                                    <td><?= htmlspecialchars($teacher['homeroom_class'] ?: '-') ?></td>
                                    <td>
                                        <?php if ($teacher['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Non-Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" title="Edit" onclick='openEditModal(<?= json_encode($teacher) ?>)'><i class="fa fa-edit"></i></button>
                                        <button class="btn btn-sm btn-warning" title="Reset Akun" onclick="confirmReset(<?= $teacher['user_db_id'] ?>)"><i class="fa fa-key"></i></button>
                                        <button class="btn btn-sm btn-danger" title="Hapus" onclick="confirmDelete(<?= $teacher['user_db_id'] ?>)"><i class="fa fa-trash"></i></button>
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

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTeacherModalLabel">Tambah Guru Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api/manage-users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_teacher">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_nip" class="form-label">NIP</label>
                        <input type="text" class="form-control" id="add_nip" name="nip" required onkeyup="updateUsername(this.value, 'add_username')">
                    </div>
                    <div class="mb-3">
                        <label for="add_birth_date" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="add_birth_date" name="birth_date" required onchange="updatePassword(this.value, 'add_password')">
                    </div>
                    <div class="mb-3">
                        <label for="add_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="add_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_homeroom_class" class="form-label">Wali Kelas (Opsional)</label>
                        <input type="text" class="form-control" id="add_homeroom_class" name="homeroom_class">
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="add_username" class="form-label">Username (default: NIP)</label>
                        <input type="text" class="form-control" id="add_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_password" class="form-label">Password (default: tgl lahir DDMMYYYY)</label>
                        <input type="text" class="form-control" id="add_password" name="password" required>
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

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTeacherModalLabel">Edit Data Guru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api/manage-users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_teacher">
                    <input type="hidden" id="edit_user_db_id" name="user_db_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_nip" class="form-label">NIP</label>
                        <input type="text" class="form-control" id="edit_nip" name="nip" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_birth_date" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="edit_birth_date" name="birth_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_homeroom_class" class="form-label">Wali Kelas (Opsional)</label>
                        <input type="text" class="form-control" id="edit_homeroom_class" name="homeroom_class">
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                     <div class="mb-3">
                        <label for="edit_is_active" class="form-label">Status Akun</label>
                        <select class="form-select" id="edit_is_active" name="is_active" required>
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
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

<!-- Hidden forms for POST actions -->
<form method="POST" action="api/manage-users.php" id="resetForm" style="display: none;">
    <input type="hidden" name="action" value="reset_teacher_account">
    <input type="hidden" id="reset_user_id" name="user_id">
</form>
<form method="POST" action="api/manage-users.php" id="deleteForm" style="display: none;">
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" id="delete_user_id" name="user_id">
</form>


<script>
function updateUsername(nip, targetId) {
    document.getElementById(targetId).value = nip;
}

function updatePassword(birthDate, targetId) {
    if (birthDate) {
        const date = new Date(birthDate);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        document.getElementById(targetId).value = `${day}${month}${year}`;
    }
}

function openEditModal(teacher) {
    document.getElementById('edit_user_db_id').value = teacher.user_db_id;
    document.getElementById('edit_name').value = teacher.name;
    document.getElementById('edit_nip').value = teacher.nip;
    document.getElementById('edit_birth_date').value = teacher.birth_date;
    document.getElementById('edit_email').value = teacher.email;
    document.getElementById('edit_homeroom_class').value = teacher.homeroom_class;
    document.getElementById('edit_username').value = teacher.username;
    document.getElementById('edit_is_active').value = teacher.is_active;

    var myModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
    myModal.show();
}

function confirmReset(userId) {
    if (confirm("Reset akun ini? Username akan kembali ke NIP dan password ke tanggal lahir.")) {
        document.getElementById('reset_user_id').value = userId;
        document.getElementById('resetForm').submit();
    }
}

function confirmDelete(userId) {
    if (confirm("Anda yakin ingin menghapus akun ini? Tindakan ini tidak dapat dibatalkan.")) {
        document.getElementById('delete_user_id').value = userId;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php require_once 'includes/layout-wrapper-end.php'; ?>
