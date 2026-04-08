<?php
// Test API langsung tanpa HTTP call (biar gak perlu server running)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfid_tag = $_POST['rfid_tag'] ?? '';
    if ($rfid_tag) {
        // Simulasi kode dari attendance.php
        $conn = pg_connect("host=localhost port=5432 dbname=smart_attendance user=postgres password=admin123");

        if (!$conn) {
            $result = ["error" => "Database connection failed"];
            $status = 'error';
        } else {
            $query = "SELECT * FROM students WHERE rfid_tag = $1";
            $pg_result = pg_query_params($conn, $query, [$rfid_tag]);

            if (pg_num_rows($pg_result) == 0) {
                $result = ["error" => "RFID tidak ditemukan"];
                $status = 'error';
            } else {
                $student = pg_fetch_assoc($pg_result);
                $student_id = $student['student_id'];

                // Cek duplikasi
                $check_query = "SELECT id FROM attendance WHERE student_id = $1 AND date = CURRENT_DATE";
                $check_result = pg_query_params($conn, $check_query, [$student_id]);
                if (pg_num_rows($check_result) > 0) {
                    $result = ["message" => "Sudah check-in hari ini", "student" => $student['name']];
                    $status = 'success';
                } else {
                    $current_time = date("H:i:s");
                    $currentHour = (int)date("H");
                    $currentMin = (int)date("i");
                    $currentSec = (int)date("s");

                    if ($currentHour < 7 || ($currentHour == 7 && $currentMin <= 30)) {
                        $status_absen = "present";
                    } elseif ($currentHour == 7 || ($currentHour == 8 && $currentMin <= 15)) {
                        $status_absen = "late";
                    } else {
                        $status_absen = "absent";
                    }

                    $insert = "INSERT INTO attendance (student_id, status, check_in_time) VALUES ($1, $2, $3)";
                    pg_query_params($conn, $insert, [$student_id, $status_absen, $current_time]);

                    $result = [
                        "message" => "Absensi berhasil",
                        "student" => $student['name'],
                        "status" => $status_absen,
                        "time" => $current_time
                    ];
                    $status = 'success';
                }
            }
            pg_close($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Test API RFID</title>
</head>
<body>
    <h1>Test API Absensi RFID</h1>
    <form method="POST">
        <label>RFID Tag:</label>
        <input type="text" name="rfid_tag" value="RFID001" required>
        <button type="submit">Test API</button>
    </form>
    <?php if (isset($result)): ?>
        <h2>Response (<?php echo $status; ?>):</h2>
        <pre><?php echo json_encode($result, JSON_PRETTY_PRINT); ?></pre>
    <?php endif; ?>
</body>
</html>