<?php
header("Content-Type: application/json");

// Koneksi database (pakai pg_connect seperti yang kamu kirim, tapi saya tambah prepared statements untuk keamanan)
$conn = pg_connect("host=localhost port=5432 dbname=smart_attendance user=postgres password=admin123");

if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Ambil input JSON
$data = json_decode(file_get_contents("php://input"), true);
$rfid = $data['rfid_tag'] ?? null;

if (!$rfid) {
    echo json_encode(["error" => "RFID tidak dikirim"]);
    exit;
}

// Cari siswa berdasarkan RFID (pakai prepared statement biar aman)
$query = "SELECT * FROM students WHERE rfid_tag = $1";
$result = pg_query_params($conn, $query, [$rfid]);

if (pg_num_rows($result) == 0) {
    echo json_encode(["error" => "RFID tidak ditemukan"]);
    exit;
}

$student = pg_fetch_assoc($result);
$student_id = $student['student_id'];

// Cek apakah sudah check-in hari ini (fitur tambahan)
$check_query = "SELECT id FROM attendance WHERE student_id = $1 AND date = CURRENT_DATE";
$check_result = pg_query_params($conn, $check_query, [$student_id]);
if (pg_num_rows($check_result) > 0) {
    echo json_encode(["message" => "Sudah check-in hari ini", "student" => $student['name']]);
    exit;
}

// Tentukan status berdasarkan waktu
// Hadir: ≤ 07:30, Terlambat: 07:31 - 08:15, Tidak Hadir: > 08:15
$current_time = date("H:i:s");
$currentHour = (int)date("H");
$currentMin = (int)date("i");
$currentSec = (int)date("s");

if ($currentHour < 7 || ($currentHour == 7 && $currentMin <= 30)) {
    $status = "present";
} elseif ($currentHour == 7 || ($currentHour == 8 && $currentMin <= 15)) {
    $status = "late";
} else {
    $status = "absent";
}

// Insert ke attendance
$insert = "INSERT INTO attendance (student_id, status, check_in_time) VALUES ($1, $2, $3)";
pg_query_params($conn, $insert, [$student_id, $status, $current_time]);

// Response
echo json_encode([
    "message" => "Absensi berhasil",
    "student" => $student['name'],
    "status" => $status,
    "time" => $current_time
]);

pg_close($conn);
?>