<?php
// PostgreSQL Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'smart_attendance');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: 'admin123');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        try {
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            // Return null if DB not connected (demo mode)
            return null;
        }
    }
    return $conn;
}

// Mock data for demo (when DB not available)
function getMockStudents() {
    return [
        ['id' => 1, 'name' => 'Ahmad Fauzi',      'student_id' => '2024001', 'class' => 'XII IPA 1', 'photo' => '', 'rfid_tag' => 'RFID001'],
        ['id' => 2, 'name' => 'Budi Santoso',     'student_id' => '2024002', 'class' => 'XII IPA 1', 'photo' => '', 'rfid_tag' => 'RFID002'],
        ['id' => 3, 'name' => 'Citra Dewi',       'student_id' => '2024003', 'class' => 'XII IPA 2', 'photo' => '', 'rfid_tag' => 'RFID003'],
        ['id' => 4, 'name' => 'Dian Permata',     'student_id' => '2024004', 'class' => 'XII IPS 1', 'photo' => '', 'rfid_tag' => 'RFID004'],
        ['id' => 5, 'name' => 'Eko Prasetyo',     'student_id' => '2024005', 'class' => 'XII IPS 1', 'photo' => '', 'rfid_tag' => 'RFID005'],
        ['id' => 6, 'name' => 'Fitri Handayani',  'student_id' => '2024006', 'class' => 'XII IPA 2', 'photo' => '', 'rfid_tag' => 'RFID006'],
        ['id' => 7, 'name' => 'Gilang Ramadhan',  'student_id' => '2024007', 'class' => 'XII IPS 2', 'photo' => '', 'rfid_tag' => 'RFID007'],
        ['id' => 8, 'name' => 'Hana Safitri',     'student_id' => '2024008', 'class' => 'XII IPS 2', 'photo' => '', 'rfid_tag' => 'RFID008'],
    ];
}

function getMockAttendance() {
    return [
        ['student_id' => '2024001', 'name' => 'Ahmad Fauzi',     'status' => 'present', 'check_in_time' => '07:15:00', 'date' => date('Y-m-d'), 'class' => 'XII IPA 1'],
        ['student_id' => '2024002', 'name' => 'Budi Santoso',    'status' => 'late',    'check_in_time' => '07:45:00', 'date' => date('Y-m-d'), 'class' => 'XII IPA 1'],
        ['student_id' => '2024003', 'name' => 'Citra Dewi',      'status' => 'present', 'check_in_time' => '07:10:00', 'date' => date('Y-m-d'), 'class' => 'XII IPA 2'],
        ['student_id' => '2024004', 'name' => 'Dian Permata',    'status' => 'absent',  'check_in_time' => null,       'date' => date('Y-m-d'), 'class' => 'XII IPS 1'],
        ['student_id' => '2024005', 'name' => 'Eko Prasetyo',    'status' => 'present', 'check_in_time' => '07:25:00', 'date' => date('Y-m-d'), 'class' => 'XII IPS 1'],
        ['student_id' => '2024006', 'name' => 'Fitri Handayani', 'status' => 'late',    'check_in_time' => '08:00:00', 'date' => date('Y-m-d'), 'class' => 'XII IPA 2'],
        ['student_id' => '2024007', 'name' => 'Gilang Ramadhan', 'status' => 'not_checked', 'check_in_time' => null,   'date' => date('Y-m-d'), 'class' => 'XII IPS 2'],
        ['student_id' => '2024008', 'name' => 'Hana Safitri',    'status' => 'present', 'check_in_time' => '07:20:00', 'date' => date('Y-m-d'), 'class' => 'XII IPS 2'],
    ];
}

function getMockWeeklyData() {
    return [
        'Mon' => ['present' => 42, 'late' => 5, 'absent' => 3],
        'Tue' => ['present' => 44, 'late' => 4, 'absent' => 2],
        'Wed' => ['present' => 40, 'late' => 6, 'absent' => 4],
        'Thu' => ['present' => 43, 'late' => 3, 'absent' => 4],
        'Fri' => ['present' => 45, 'late' => 2, 'absent' => 3],
    ];
}

function getMockActivities() {
    return [
        ['name' => 'Ahmad Fauzi',     'student_id' => '2024001', 'status' => 'present', 'time' => '07:15', 'class' => 'XII IPA 1'],
        ['name' => 'Citra Dewi',      'student_id' => '2024003', 'status' => 'present', 'time' => '07:10', 'class' => 'XII IPA 2'],
        ['name' => 'Eko Prasetyo',    'student_id' => '2024005', 'status' => 'present', 'time' => '07:25', 'class' => 'XII IPS 1'],
        ['name' => 'Hana Safitri',    'student_id' => '2024008', 'status' => 'present', 'time' => '07:20', 'class' => 'XII IPS 2'],
        ['name' => 'Budi Santoso',    'student_id' => '2024002', 'status' => 'late',    'time' => '07:45', 'class' => 'XII IPA 1'],
        ['name' => 'Fitri Handayani', 'student_id' => '2024006', 'status' => 'late',    'time' => '08:00', 'class' => 'XII IPA 2'],
        ['name' => 'Dian Permata',    'student_id' => '2024004', 'status' => 'absent',  'time' => '--:--', 'class' => 'XII IPS 1'],
        ['name' => 'Gilang Ramadhan', 'student_id' => '2024007', 'status' => 'not_checked', 'time' => '--:--', 'class' => 'XII IPS 2'],
    ];
}

// Helper functions
function getSchoolInfo() {
    return [
        'name' => 'SMAN 1 GADINGREJO',
        'logo' => 'images/Logo_SMA_Negeri_1_Gadingrejo.png',
        'shortname' => 'S1G'
    ];
}

// Format tanggal ke format dd/mm/yyyy
function formatDateToDMY($date) {
    return date('d/m/Y', strtotime($date));
}

// Gender emoji
function getGenderEmoji($gender) {
    return ($gender === 'L') ? '🙋‍♂️' : '🙋‍♀️';
}

// Hitung hari kerja sekolah (exclude Sabtu=6, Minggu=0)
function countSchoolDays($startDate, $endDate) {
    $count = 0;
    $current = strtotime($startDate);
    $end = strtotime($endDate);
    
    while ($current <= $end) {
        $dayOfWeek = date('w', $current);
        // 0 = Minggu, 6 = Sabtu
        if ($dayOfWeek != 0 && $dayOfWeek != 6) {
            $count++;
        }
        $current = strtotime('+1 day', $current);
    }
    return $count;
}

// Get current server date (Sesuai tanggal sistem: 7 April 2026)
function getCurrentDate() {
    return date('Y-m-d');
}

// Get current server datetime
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}
?>
