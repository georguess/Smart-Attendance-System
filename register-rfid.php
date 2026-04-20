<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $rfid_tag = $_POST['rfid_tag'] ?? '';

    if ($student_id && $rfid_tag) {
        $conn = getDB();
        if ($conn) {
            try {
                $stmt = $conn->prepare("UPDATE students SET rfid_tag = ? WHERE student_id = ?");
                $stmt->execute([$rfid_tag, $student_id]);
                $message = 'RFID berhasil didaftarkan!';
            } catch (PDOException $e) {
                $message = 'Error: ' . $e->getMessage();
            }
        } else {
            $message = 'Database tidak tersedia. Gunakan mode demo.';
        }
    } else {
        $message = 'Harap isi semua field.';
    }
}

$students = getMockStudents(); // or fetch from DB if available

$pageTitle = 'Daftar RFID';
require_once 'includes/layout-wrapper-start.php';
?>

        <div class="page-header" style="display:flex; align-items:center; gap:16px;">
            <button onclick="history.back()" class="btn btn-secondary" style="border:1px solid var(--border); background:var(--surface);">
                <i class="fa fa-arrow-left"></i> Kembali
            </button>
            <div>
                <h1><i class="fa fa-id-card"></i> Daftar RFID untuk Siswa</h1>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label for="student_id" style="margin-bottom:0.5rem; display:block;">Pilih Siswa:</label>
                        <select name="student_id" id="student_id" required style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border);">
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>">
                                    <?php echo $student['name'] . ' (' . $student['student_id'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label for="rfid_tag" style="margin-bottom:0.5rem; display:block;">RFID Tag:</label>
                        <input type="text" name="rfid_tag" id="rfid_tag" placeholder="Masukkan RFID Tag" required style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border);">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Daftar RFID</button>
                </form>
            </div>
        </div>

<?php require_once 'includes/layout-wrapper-end.php'; ?>