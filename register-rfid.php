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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar RFID – SMAN 1 Pringsewu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div id="pageLoader" class="page-loader"><div class="loader-ring"></div></div>
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<?php include 'includes/header.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fa fa-id-card"></i> Daftar RFID untuk Siswa</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="student_id">Pilih Siswa:</label>
                        <select name="student_id" id="student_id" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>">
                                    <?php echo $student['name'] . ' (' . $student['student_id'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rfid_tag">RFID Tag:</label>
                        <input type="text" name="rfid_tag" id="rfid_tag" placeholder="Masukkan RFID Tag" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Daftar RFID</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="assets/js/app.js"></script>
</body>
</html>