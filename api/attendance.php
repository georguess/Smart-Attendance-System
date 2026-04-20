<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

function apiResponse(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(405, ['error' => 'Method tidak diizinkan']);
}

$conn = getDB();
if (!$conn) {
    apiResponse(500, ['error' => 'Database connection failed']);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    apiResponse(400, ['error' => 'Payload JSON tidak valid']);
}

$rfid = trim((string)($data['rfid_tag'] ?? ''));
$deviceCode = trim((string)($data['device_code'] ?? ''));
$deviceApiKey = trim((string)($data['api_key'] ?? ''));
$photoBase64 = $data['photo_base64'] ?? null;

if ($rfid === '') {
    apiResponse(400, ['error' => 'RFID tidak dikirim']);
}

try {
    if ($deviceCode !== '') {
        $stmtDevice = $conn->prepare('SELECT api_key, is_active FROM iot_devices WHERE device_code = ?');
        $stmtDevice->execute([$deviceCode]);
        $device = $stmtDevice->fetch();

        if (!$device || !$device['is_active']) {
            apiResponse(401, ['error' => 'Perangkat tidak terdaftar atau nonaktif']);
        }

        if (!empty($device['api_key']) && !hash_equals((string)$device['api_key'], $deviceApiKey)) {
            apiResponse(401, ['error' => 'API key perangkat tidak valid']);
        }

        $stmtSeen = $conn->prepare('UPDATE iot_devices SET last_seen_at = NOW() WHERE device_code = ?');
        $stmtSeen->execute([$deviceCode]);
    }

    $stmtStudent = $conn->prepare('SELECT student_id, name FROM students WHERE rfid_tag = ? AND is_active = TRUE');
    $stmtStudent->execute([$rfid]);
    $student = $stmtStudent->fetch();

    if (!$student) {
        apiResponse(404, ['error' => 'RFID tidak ditemukan']);
    }

    $studentId = $student['student_id'];

    $stmtCheck = $conn->prepare('SELECT id FROM attendance WHERE student_id = ? AND date = CURRENT_DATE');
    $stmtCheck->execute([$studentId]);
    if ($stmtCheck->fetch()) {
        $stmtEvent = $conn->prepare(
            'INSERT INTO attendance_events (student_id, rfid_uid, event_status, rejection_reason, device_code, payload_json)
             VALUES (?, ?, ?, ?, ?, ?::jsonb)'
        );
        $stmtEvent->execute([
            $studentId,
            $rfid,
            'duplicate',
            'Already checked in today',
            $deviceCode !== '' ? $deviceCode : null,
            json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);

        apiResponse(200, ['message' => 'Sudah check-in hari ini', 'student' => $student['name']]);
    }

    $currentTime = date('H:i:s');
    $currentHour = (int)date('H');
    $currentMin = (int)date('i');

    if ($currentHour < 7 || ($currentHour === 7 && $currentMin <= 30)) {
        $status = 'present';
    } elseif ($currentHour === 7 || ($currentHour === 8 && $currentMin <= 15)) {
        $status = 'late';
    } else {
        $status = 'absent';
    }

    $photoPath = null;
    if (is_string($photoBase64) && $photoBase64 !== '') {
        $cleanBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $photoBase64);
        $binary = base64_decode($cleanBase64, true);

        if ($binary !== false) {
            $uploadDir = __DIR__ . '/../uploads/attendance';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = sprintf('%s_%s.jpg', $studentId, date('Ymd_His'));
            $fullPath = $uploadDir . '/' . $fileName;
            file_put_contents($fullPath, $binary);
            $photoPath = 'uploads/attendance/' . $fileName;
        }
    }

    $stmtInsert = $conn->prepare(
        'INSERT INTO attendance (student_id, status, check_in_time, source, photo_path)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmtInsert->execute([
        $studentId,
        $status,
        $currentTime,
        $deviceCode !== '' ? 'iot' : 'web',
        $photoPath,
    ]);

    $stmtEvent = $conn->prepare(
        'INSERT INTO attendance_events (student_id, rfid_uid, event_status, photo_path, device_code, payload_json)
         VALUES (?, ?, ?, ?, ?, ?::jsonb)'
    );
    $stmtEvent->execute([
        $studentId,
        $rfid,
        'accepted',
        $photoPath,
        $deviceCode !== '' ? $deviceCode : null,
        json_encode($data, JSON_UNESCAPED_UNICODE),
    ]);

    apiResponse(200, [
        'message' => 'Absensi berhasil',
        'student' => $student['name'],
        'status' => $status,
        'time' => $currentTime,
        'photo_path' => $photoPath,
    ]);
} catch (Exception $e) {
    apiResponse(500, ['error' => 'Internal server error', 'details' => $e->getMessage()]);
}
?>