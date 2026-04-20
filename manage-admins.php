<?php
session_start();
require_once 'config/database.php';

// Hanya untuk admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Manajemen Admin';
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

$conn = getDB();
$admins = [];
$current_user_id = $_SESSION['user_id'];

if ($conn) {
    try {
        $sql = "SELECT id, name, username, email, last_login FROM users WHERE role = 'admin' ORDER BY name";
        $stmt = $conn->query($sql);
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Gagal mengambil data admin: " . $e->getMessage();
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
                <h1><i class="fa fa-user-shield"></i> <?= htmlspecialchars($pageTitle) ?></h1>
                <p>Kelola akun administrator lain di sistem.</p>
            </div>
        </div>
        <div class="header-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fa fa-plus"></i> Tambah Admin
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
                            <th>Terakhir Login</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada data admin.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?= htmlspecialchars($admin['name']) ?></td>
                                    <td><?= htmlspecialchars($admin['username']) ?></td>
                                    <td><?= htmlspecialchars($admin['email']) ?></td>
                                    <td><?= $admin['last_login'] ? date('d M Y, H:i', strtotime($admin['last_login'])) : 'Belum pernah' ?></td>
                                    <td>
                                        <?php if ($admin['id'] == $current_user_id): ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Edit akun Anda di halaman Pengaturan"><i class="fa fa-edit"></i></button>
                                            <button class="btn btn-sm btn-secondary" disabled title="Tidak dapat mereset akun sendiri"><i class="fa fa-key"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-info" title="Edit" onclick='openEditModal(<?= json_encode($admin) ?>)'><i class="fa fa-edit"></i></button>
                                            <button class="btn btn-sm btn-warning" title="Reset Password" onclick="confirmReset(<?= $admin['id'] ?>)"><i class="fa fa-key"></i></button>
                                        <?php endif; ?>
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

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">Tambah Admin Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api/manage-users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_admin">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="add_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="add_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="add_password" name="password" required>
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

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAdminModalLabel">Edit Data Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api/manage-users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_admin">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
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

<!-- Hidden form for reset action -->
<form method="POST" action="api/manage-users.php" id="resetAdminForm" style="display: none;">
    <input type="hidden" name="action" value="reset_admin_password">
    <input type="hidden" id="reset_admin_user_id" name="user_id">
</form>

<script>
function openEditModal(admin) {
    document.getElementById('edit_user_id').value = admin.id;
    document.getElementById('edit_name').value = admin.name;
    document.getElementById('edit_username').value = admin.username;
    document.getElementById('edit_email').value = admin.email;

    var myModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
    myModal.show();
}

function confirmReset(userId) {
    if (confirm("Anda yakin ingin mereset password untuk akun ini? Password akan diubah ke default sistem ('Admin@2026').")) {
        document.getElementById('reset_admin_user_id').value = userId;
        document.getElementById('resetAdminForm').submit();
    }
}
</script>

<?php require_once 'includes/layout-wrapper-end.php'; ?>
