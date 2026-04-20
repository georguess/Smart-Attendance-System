<?php
// Endpoint untuk menerima data dari ESP32-CAM
// Menerima: rfid_uid, photo (multipart/form-data)

require_once 'config/database.php';

// --- Konfigurasi ---
$upload_dir = "uploads/attendance/";
$log_file = "logs/esp32_log.txt";

// --- Fungsi Logging ---
function write_log($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    // Buat direktori log jika belum ada
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0775, true);
    }
    file_put_contents($log_file, "[$timestamp] " . $message . "\n", FILE_APPEND);
}

// --- Validasi Request ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    write_log("Request ditolak: Method bukan POST.");
    exit;
}

if (empty($_POST['rfid_uid']) || empty($_FILES['photo'])) {
    http_response_code(400);
    echo "Bad Request: rfid_uid atau photo tidak ada.";
    write_log("Request ditolak: rfid_uid atau file foto kosong.");
    exit;
}

// --- Ambil Data ---
$rfid_uid = trim($_POST['rfid_uid']);
$photo = $_FILES['photo'];

write_log("Menerima request untuk RFID: $rfid_uid");

// --- Validasi File Foto ---
if ($photo['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    echo "Error uploading file: " . $photo['error'];
    write_log("Error upload file untuk RFID $rfid_uid. Kode: " . $photo['error']);
    exit;
}

// --- Proses di Database ---
$conn = getDB();
if (!$conn) {
    http_response_code(503);
    echo "Service Unavailable: Database connection failed.";
    write_log("FATAL: Koneksi database gagal.");
    exit;
}

try {
    // 1. Cari siswa berdasarkan RFID UID
    $stmt = $conn->prepare("SELECT student_id, name FROM students WHERE rfid_uid = ?");
    $stmt->execute([$rfid_uid]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo "Not Found: RFID tidak terdaftar.";
        write_log("Error: RFID $rfid_uid tidak ditemukan di database.");
        exit;
    }

    $student_id = $student['student_id'];
    $student_name = $student['name'];
    write_log("RFID $rfid_uid terasosiasi dengan siswa: $student_name (ID: $student_id)");

    // 2. Tentukan status kehadiran (Hadir/Terlambat)
    $current_time = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $attendance_date = $current_time->format('Y-m-d');
    $check_in_time = $current_time->format('H:i:s');

    // Ambil aturan waktu dari database atau hardcode
    $late_threshold = new DateTime($attendance_date . ' 07:30:00', new DateTimeZone('Asia/Jakarta'));
    $absent_threshold = new DateTime($attendance_date . ' 08:15:00', new DateTimeZone('Asia/Jakarta'));

    if ($current_time <= $late_threshold) {
        $status = 'present';
    } elseif ($current_time > $late_threshold && $current_time <= $absent_threshold) {
        $status = 'late';
    } else {
        $status = 'absent'; // Dianggap absen jika tap setelah batas waktu terlambat
    }
    write_log("Status kehadiran ditentukan: $status pada jam $check_in_time");

    // 3. Simpan file foto
    $timestamp = $current_time->getTimestamp();
    $file_extension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
    if (empty($file_extension)) $file_extension = 'jpg'; // Default extension
    $filename = $rfid_uid . '_' . $timestamp . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($photo['tmp_name'], $target_path)) {
        throw new Exception("Gagal memindahkan file foto.");
    }
    write_log("Foto berhasil disimpan ke: $target_path");
    $db_photo_path = $target_path; // Path yang disimpan di DB

    // 4. Cek apakah sudah ada record absensi untuk hari ini
    $stmt = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ?");
    $stmt->execute([$student_id, $attendance_date]);
    $existing_record = $stmt->fetch();

    if ($existing_record) {
        // Update record yang sudah ada (misal, status berubah atau foto diupdate)
        $stmt = $conn->prepare(
            "UPDATE attendance SET status = ?, check_in_time = ?, photo_path = ?, updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$status, $check_in_time, $db_photo_path, $existing_record['id']]);
        write_log("Record absensi untuk siswa ID $student_id hari ini telah diperbarui.");
    } else {
        // Buat record baru
        $stmt = $conn->prepare(
            "INSERT INTO attendance (student_id, attendance_date, status, check_in_time, photo_path) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$student_id, $attendance_date, $status, $check_in_time, $db_photo_path]);
        write_log("Record absensi baru dibuat untuk siswa ID $student_id.");
    }

    // --- Respon Sukses ---
    http_response_code(200);
    echo "OK: Attendance for $student_name recorded as $status.";
    write_log("Respon sukses dikirim ke ESP32.");

} catch (Exception $e) {
    http_response_code(500);
    echo "Internal Server Error: " . $e->getMessage();
    write_log("FATAL EXCEPTION: " . $e->getMessage());
}
?>