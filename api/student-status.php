<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// --- Authentication & Authorization ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak. Silakan login sebagai siswa.']);
    exit;
}

$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID Siswa tidak ditemukan di sesi.']);
    exit;
}

$conn = getDB();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database gagal.']);
    exit;
}

try {
    // --- 1. Weekly Recap (Chart) ---
    // Data untuk 5 hari kerja terakhir (Senin-Jumat minggu ini)
    $today = new DateTime();
    $dayOfWeek = $today->format('N'); // 1 (Mon) to 7 (Sun)
    $startDate = (clone $today)->modify('last monday');
    
    $weekly_recap = [
        'Hadir' => 0,
        'Terlambat' => 0,
        'Izin' => 0,
        'Sakit' => 0,
        'Alpa' => 0,
    ];
    $stmt = $conn->prepare(
        "SELECT status, COUNT(*) as count 
         FROM attendance 
         WHERE student_id = ? AND attendance_date BETWEEN ? AND ?
         GROUP BY status"
    );
    $stmt->execute([$student_id, $startDate->format('Y-m-d'), $today->format('Y-m-d')]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        switch (strtolower($row['status'])) {
            case 'present':
                $weekly_recap['Hadir'] = (int)$row['count'];
                break;
            case 'late':
                $weekly_recap['Terlambat'] = (int)$row['count'];
                break;
            case 'permission':
                $weekly_recap['Izin'] = (int)$row['count'];
                break;
            case 'sick':
                $weekly_recap['Sakit'] = (int)$row['count'];
                break;
            case 'absent':
                $weekly_recap['Alpa'] = (int)$row['count'];
                break;
        }
    }

    // --- 2. Monthly Stats ---
    $startOfMonth = (new DateTime('first day of this month'))->format('Y-m-d');
    $endOfMonth = (new DateTime('last day of this month'))->format('Y-m-d');
    $monthly_stats = [
        'hadir' => 0,
        'terlambat' => 0,
        'tidak_hadir' => 0 // Izin, Sakit, Alpa
    ];
    $stmt = $conn->prepare(
        "SELECT status, COUNT(*) as count 
         FROM attendance 
         WHERE student_id = ? AND attendance_date BETWEEN ? AND ?
         GROUP BY status"
    );
    $stmt->execute([$student_id, $startOfMonth, $endOfMonth]);
    $monthly_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($monthly_results as $row) {
        $status = strtolower($row['status']);
        if ($status === 'present') {
            $monthly_stats['hadir'] = (int)$row['count'];
        } elseif ($status === 'late') {
            $monthly_stats['terlambat'] = (int)$row['count'];
        } else {
            $monthly_stats['tidak_hadir'] += (int)$row['count'];
        }
    }

    // --- 3. Weekly Activity Timeline ---
    $timeline = [];
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $stmt = $conn->prepare(
        "SELECT attendance_date, status, check_in_time 
         FROM attendance 
         WHERE student_id = ? AND attendance_date >= ?"
    );
    $stmt->execute([$student_id, $startDate->format('Y-m-d')]);
    $attendance_map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $attendance_map[$row['attendance_date']] = $row;
    }

    foreach ($days as $index => $dayName) {
        $currentDay = (clone $startDate)->modify("+$index days");
        $dateStr = $currentDay->format('Y-m-d');
        
        if ($currentDay > $today) {
             $timeline[] = [
                'day' => substr($dayName, 0, 3),
                'date' => $currentDay->format('d M'),
                'status' => 'future',
                'status_text' => 'Belum Berlangsung',
                'time' => ''
            ];
        } elseif (isset($attendance_map[$dateStr])) {
            $record = $attendance_map[$dateStr];
            $status_text = ucfirst(str_replace(['present', 'late', 'absent', 'sick', 'permission'], ['Hadir', 'Terlambat', 'Alpa', 'Sakit', 'Izin'], $record['status']));
            $timeline[] = [
                'day' => substr($dayName, 0, 3),
                'date' => $currentDay->format('d M'),
                'status' => $record['status'],
                'status_text' => $status_text,
                'time' => $record['check_in_time'] ? date('H:i', strtotime($record['check_in_time'])) : ''
            ];
        } else {
            $timeline[] = [
                'day' => substr($dayName, 0, 3),
                'date' => $currentDay->format('d M'),
                'status' => 'no_record',
                'status_text' => 'Tidak Ada Data',
                'time' => ''
            ];
        }
    }

    // --- 4. Bukti Kehadiran (Foto) ---
    $today_str = $today->format('Y-m-d');
    $stmt = $conn->prepare(
        "SELECT photo_path FROM attendance 
         WHERE student_id = ? AND attendance_date = ?"
    );
    $stmt->execute([$student_id, $today_str]);
    $photo_path = $stmt->fetchColumn();


    // --- Final Response ---
    echo json_encode([
        'success' => true,
        'weekly_recap' => $weekly_recap,
        'monthly_stats' => $monthly_stats,
        'weekly_timeline' => $timeline,
        'today_photo_path' => $photo_path ?: null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    // Jangan tampilkan pesan error detail di produksi
    echo json_encode(['error' => 'Terjadi kesalahan pada server.', 'detail' => $e->getMessage()]);
}
?>