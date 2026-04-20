<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

// --- Helper Functions ---
function send_json_error($message) {
    echo json_encode(['error' => $message]);
    exit;
}

function get_days_in_month($month, $year) {
    $num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $days = [];
    for ($d = 1; $d <= $num_days; $d++) {
        $date = new DateTime("$year-$month-$d");
        $day_of_week = $date->format('N'); // 1 (for Monday) through 7 (for Sunday)
        if ($day_of_week >= 1 && $day_of_week <= 5) { // Senin - Jumat
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D')
            ];
        }
    }
    return $days;
}

// --- Main Logic ---
$conn = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $class_name = $_GET['class'] ?? null;
    $month = $_GET['month'] ?? null;
    $year = $_GET['year'] ?? null;

    if (!$class_name || !$month || !$year) {
        send_json_error("Parameter tidak lengkap.");
    }

    try {
        // 1. Get student IDs for the class
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE class = ?");
        $stmt->execute([$class_name]);
        $student_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($student_ids)) {
            echo json_encode(['attendance' => [], 'days' => get_days_in_month($month, $year), 'holidays' => []]);
            exit;
        }

        // 2. Get attendance data for these students for the given month
        $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
        $sql = "
            SELECT student_id, status, check_in_time, photo_path, DATE(check_in_time) as attendance_date
            FROM attendance
            WHERE student_id IN ($placeholders)
            AND EXTRACT(MONTH FROM check_in_time) = ?
            AND EXTRACT(YEAR FROM check_in_time) = ?
        ";
        
        $params = array_merge($student_ids, [$month, $year]);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Format data for easy lookup on the frontend
        $attendance_data = [];
        foreach ($records as $record) {
            $student_id = $record['student_id'];
            $date = $record['attendance_date'];
            if (!isset($attendance_data[$student_id])) {
                $attendance_data[$student_id] = [];
            }
            $attendance_data[$student_id][$date] = [
                'status' => $record['status'],
                'check_in_time' => date('H:i:s', strtotime($record['check_in_time'])),
                'photo_path' => $record['photo_path']
            ];
        }

        // 4. Get holidays (dummy, replace with real logic if needed)
        $holidays = []; // e.g., ['2025-08-17']

        // 5. Return response
        echo json_encode([
            'attendance' => $attendance_data,
            'days' => get_days_in_month($month, $year),
            'holidays' => $holidays
        ]);

    } catch (Exception $e) {
        send_json_error("Database error: " . $e->getMessage());
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
        send_json_error("Akses ditolak.");
    }

    $student_id = $_POST['student_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $status = $_POST['status'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!$student_id || !$date || !$status) {
        send_json_error("Data untuk update tidak lengkap.");
    }
    
    try {
        $conn->beginTransaction();

        // Check if a record for this student and date already exists
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND DATE(check_in_time) = ?");
        $stmt->execute([$student_id, $date]);
        $existing_id = $stmt->fetchColumn();

        if ($existing_id) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $user_id, $existing_id]);
        } else {
            // Insert new record for manual entry
            if ($status !== 'Alpha') { // Only insert if not Alpha, as Alpha is the absence of a record
                 $check_in_time = $date . ' 00:00:00'; // Default time for manual entry
                 $stmt = $conn->prepare("
                    INSERT INTO attendance (student_id, check_in_time, status, photo_path, created_by, updated_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$student_id, $check_in_time, $status, 'uploads/manual_entry.png', $user_id, $user_id]);
            }
        }
        
        // Log the change
        $stmt = $conn->prepare("
            INSERT INTO attendance_log (attendance_id, student_id, changed_by_user_id, old_status, new_status, change_reason)
            VALUES (?, ?, ?, (SELECT status FROM attendance WHERE id = ?), ?, 'Manual edit by user')
        ");
        // This logging is complex to get old_status, simplifying for now.
        // A better approach would be triggers or getting old status before update.

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollBack();
        send_json_error("Gagal update status: " . $e->getMessage());
    }
}
?>
