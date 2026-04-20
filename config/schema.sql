-- ============================================
-- Smart Attendance System – PostgreSQL Schema
-- SMAN 1 Gadingrejo
-- ============================================

-- Drop existing tables (order matters because of foreign keys)
DROP TABLE IF EXISTS attendance_events CASCADE;
DROP TABLE IF EXISTS attendance CASCADE;
DROP TABLE IF EXISTS iot_devices CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS students CASCADE;
DROP TABLE IF EXISTS schools CASCADE;

-- Schools
CREATE TABLE schools (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    logo       VARCHAR(255),
    image      VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Students
CREATE TABLE students (
    id            SERIAL PRIMARY KEY,
    school_id     INT REFERENCES schools(id) ON DELETE SET NULL,
    name          VARCHAR(100) NOT NULL,
    student_id    VARCHAR(20) UNIQUE NOT NULL,
    class         VARCHAR(30),
    gender        CHAR(1) CHECK (gender IN ('L', 'P')),
    date_of_birth DATE,
    photo         VARCHAR(255),
    rfid_tag      VARCHAR(50) UNIQUE,
    is_active     BOOLEAN NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Users (authentication + authorization)
CREATE TABLE users (
    id         SERIAL PRIMARY KEY,
    username   VARCHAR(50) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'teacher', 'student')),
    profile_picture_path VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP WITH TIME ZONE
);

-- Tabel Siswa
CREATE TABLE students (
    id SERIAL PRIMARY KEY,
    user_id INTEGER UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    student_id VARCHAR(20) UNIQUE NOT NULL, -- NIS/NISN
    name VARCHAR(100) NOT NULL,
    class VARCHAR(50),
    gender CHAR(1) CHECK (gender IN ('L', 'P')),
    birth_date DATE,
    rfid_uid VARCHAR(50) UNIQUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Perangkat IoT
CREATE TABLE iot_devices (
    id SERIAL PRIMARY KEY,
    device_code VARCHAR(50) UNIQUE NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    location VARCHAR(100),
    last_heartbeat TIMESTAMP WITH TIME ZONE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Absensi
CREATE TABLE attendance (
    id SERIAL PRIMARY KEY,
    student_id INTEGER REFERENCES students(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    check_in_time TIME,
    status VARCHAR(20) NOT NULL CHECK (status IN ('present', 'late', 'absent')),
    photo_path VARCHAR(255),
    recorded_by_device_id INTEGER REFERENCES iot_devices(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(student_id, date)
);

-- Tabel Log Event Absensi (untuk audit dan live feed)
CREATE TABLE attendance_events (
    id SERIAL PRIMARY KEY,
    attendance_id INTEGER REFERENCES attendance(id) ON DELETE CASCADE,
    event_type VARCHAR(50) NOT NULL, -- e.g., 'check-in', 'status-update-manual'
    description TEXT,
    event_time TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    actor_user_id INTEGER REFERENCES users(id) -- Siapa yang melakukan aksi (bisa null jika sistem/IoT)
);

-- Indeks untuk optimasi query
CREATE INDEX idx_students_user_id ON students(user_id);
CREATE INDEX idx_attendance_student_id ON attendance(student_id);
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_iot_devices_device_code ON iot_devices(device_code);

-- Data Awal (Seed Data)
-- Hapus data lama untuk menghindari konflik
TRUNCATE TABLE users, students, attendance, iot_devices, attendance_events RESTART IDENTITY CASCADE;

-- 1. Akun Admin
-- Password untuk semua akun default: '123456' (di-hash)
INSERT INTO users (username, password, email, role, name, profile_picture_path) VALUES 
('admin', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', 'admin@sekolah.sch.id', 'admin', 'Administrator Utama', 'uploads/profile/default-avatar.png');

INSERT INTO users (username, password, email, role, name) VALUES
('admin_it', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', 'it.support@sekolah.sch.id', 'admin', 'IT Support');

INSERT INTO users (username, password, email, role, name) VALUES
('kepsek', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', 'kepsek@sekolah.sch.id', 'admin', 'Kepala Sekolah');

-- 2. Guru
INSERT INTO users (username, password, email, role, name) VALUES
('guru001', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', 'budi.s@guru.sekolah.sch.id', 'teacher', 'Budi Setiawan, S.Pd.');
INSERT INTO teachers (user_id, nip, birth_date, homeroom_class) VALUES
((SELECT id FROM users WHERE username = 'guru001'), '199001012015031001', '1990-01-01', 'XII IPA 1');

('guru.mtk', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', 'guru.mtk@sekolah.sch.id', 'teacher');

-- 3. Akun Siswa & Data Siswa
-- Siswa 1
INSERT INTO users (username, password, email, role) VALUES
('2024001', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', '2024001@siswa.sekolah.sch.id', 'student') RETURNING id;
INSERT INTO students (user_id, student_id, name, class, gender, birth_date) VALUES
((SELECT id FROM users WHERE username = '2024001'), '2024001', 'Ahmad Fauzi', 'XII IPA 1', 'L', '2006-01-15');

-- Siswa 2
INSERT INTO users (username, password, email, role) VALUES
('2024002', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', '2024002@siswa.sekolah.sch.id', 'student');
INSERT INTO students (user_id, student_id, name, class, gender, birth_date) VALUES
((SELECT id FROM users WHERE username = '2024002'), '2024002', 'Budi Santoso', 'XII IPA 1', 'L', '2006-02-20');

-- Siswa 3
INSERT INTO users (username, password, email, role) VALUES
('2024003', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', '2024003@siswa.sekolah.sch.id', 'student');
INSERT INTO students (user_id, student_id, name, class, gender, birth_date, rfid_uid) VALUES
((SELECT id FROM users WHERE username = '2024003'), '2024003', 'Citra Lestari', 'XII IPS 2', 'P', '2006-03-10', 'C3D4E5F6');

-- Siswa 4
INSERT INTO users (username, password, email, role) VALUES
('2024004', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', '2024004@siswa.sekolah.sch.id', 'student');
INSERT INTO students (user_id, student_id, name, class, gender, birth_date) VALUES
((SELECT id FROM users WHERE username = '2024004'), '2024004', 'Dewi Anggraini', 'XII IPS 2', 'P', '2006-04-12');

-- Siswa 5
INSERT INTO users (username, password, email, role) VALUES
('2024005', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', '2024005@siswa.sekolah.sch.id', 'student');
INSERT INTO students (user_id, student_id, name, class, gender, birth_date) VALUES
((SELECT id FROM users WHERE username = '2024005'), '2024005', 'Eko Prasetyo', 'XI IPA 3', 'L', '2007-05-25');

-- Siswa 6
INSERT INTO users (username, password, email, role) VALUES
('2024006', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', '2024006@siswa.sekolah.sch.id', 'student');
INSERT INTO students (user_id, student_id, name, class, gender, birth_date, rfid_uid) VALUES
((SELECT id FROM users WHERE username = '2024006'), '2024006', 'Fitriani', 'XI IPA 3', 'P', '2007-06-30', 'D4E5F6G7');

-- Siswa 7
INSERT INTO users (username, password, email, role) VALUES
('2024007', '$2y$10$E9p33lV2iS9j2LzG5nQcZe.v2.3mOq4/fB.i.uLS9.wP8/L.a.1iO', '2024007@siswa.sekolah.sch.id', 'student');
INSERT INTO students (user_id, student_id, name, class, gender, birth_date) VALUES
((SELECT id FROM users WHERE username = '2024007'), '2024007', 'Gilang Ramadhan', 'X-A', 'L', '2008-07-01');

-- 4. Perangkat IoT
INSERT INTO iot_devices (device_code, api_key, location) VALUES
('ESP32_CAM_01', 'super-secret-api-key-12345', 'Gerbang Depan');

-- 5. Contoh Data Absensi
INSERT INTO attendance (student_id, date, check_in_time, status, recorded_by_device_id) VALUES
((SELECT id FROM students WHERE student_id = '2024001'), CURRENT_DATE, '07:05:00', 'present', 1),
((SELECT id FROM students WHERE student_id = '2024002'), CURRENT_DATE, '07:20:00', 'late', 1),
((SELECT id FROM students WHERE student_id = '2024001'), CURRENT_DATE - 1, '07:10:00', 'present', 1),
((SELECT id FROM students WHERE student_id = '2024002'), CURRENT_DATE - 1, '07:00:00', 'present', 1),
((SELECT id FROM students WHERE student_id = '2024003'), CURRENT_DATE - 1, '07:35:00', 'late', 1);

COMMIT;

-- IoT device registry (ESP32 / ESP32-CAM, etc)
CREATE TABLE iot_devices (
    id           SERIAL PRIMARY KEY,
    device_code  VARCHAR(50) UNIQUE NOT NULL,
    device_name  VARCHAR(100) NOT NULL,
    location     VARCHAR(100),
    api_key      VARCHAR(128) UNIQUE,
    is_active    BOOLEAN NOT NULL DEFAULT TRUE,
    last_seen_at TIMESTAMP,
    created_at   TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Daily attendance summary (main table used by web)
CREATE TABLE attendance (
    id            SERIAL PRIMARY KEY,
    student_id    VARCHAR(20) NOT NULL REFERENCES students(student_id) ON DELETE CASCADE,
    status        VARCHAR(20) NOT NULL CHECK (status IN ('present', 'late', 'absent', 'not_checked')),
    check_in_time TIME,
    date          DATE NOT NULL DEFAULT CURRENT_DATE,
    source        VARCHAR(20) NOT NULL DEFAULT 'web' CHECK (source IN ('web', 'iot', 'manual')),
    photo_path    VARCHAR(255),
    created_at    TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (student_id, date)
);

-- Raw IoT event log (optional but important for audit trail)
CREATE TABLE attendance_events (
    id                BIGSERIAL PRIMARY KEY,
    student_id        VARCHAR(20) REFERENCES students(student_id) ON DELETE SET NULL,
    rfid_uid          VARCHAR(100) NOT NULL,
    event_time        TIMESTAMP NOT NULL DEFAULT NOW(),
    event_status      VARCHAR(20) NOT NULL CHECK (event_status IN ('accepted', 'rejected', 'duplicate')),
    rejection_reason  VARCHAR(255),
    photo_path        VARCHAR(255),
    photo_sha256      VARCHAR(64),
    payload_json      JSONB,
    device_code       VARCHAR(50) REFERENCES iot_devices(device_code) ON DELETE SET NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_students_class ON students(class);
CREATE INDEX idx_students_rfid ON students(rfid_tag);
CREATE INDEX idx_attendance_date_status ON attendance(date, status);
CREATE INDEX idx_attendance_student_date ON attendance(student_id, date DESC);
CREATE INDEX idx_events_time ON attendance_events(event_time DESC);
CREATE INDEX idx_events_rfid ON attendance_events(rfid_uid);

-- ── SEED DATA ──────────────────────────────

INSERT INTO schools (name, logo, image) VALUES
('SMAN 1 Gadingrejo', 'images/Logo_SMA_Negeri_1_Gadingrejo.png', 'images/school.jpg');

INSERT INTO students (school_id, name, student_id, class, gender, date_of_birth, rfid_tag) VALUES
(1, 'Ahmad Fauzi',      '2024001', 'XII IPA 1', 'L', '2006-03-15', 'RFID001'),
(1, 'Budi Santoso',     '2024002', 'XII IPA 1', 'L', '2006-05-22', 'RFID002'),
(1, 'Citra Dewi',       '2024003', 'XII IPA 2', 'P', '2006-08-10', 'RFID003'),
(1, 'Dian Permata',     '2024004', 'XII IPS 1', 'P', '2006-11-28', 'RFID004'),
(1, 'Eko Prasetyo',     '2024005', 'XII IPS 1', 'L', '2007-01-05', 'RFID005'),
(1, 'Fitri Handayani',  '2024006', 'XII IPA 2', 'P', '2006-07-14', 'RFID006'),
(1, 'Gilang Ramadhan',  '2024007', 'XII IPS 2', 'L', '2006-09-20', 'RFID007'),
(1, 'Hana Safitri',     '2024008', 'XII IPS 2', 'P', '2006-12-03', 'RFID008');

-- Password seed intentionally plain for dev; for production use password_hash() result.
INSERT INTO users (username, password, role, student_id) VALUES
('2024001', '15032006', 'student', '2024001'),
('2024002', '22052006', 'student', '2024002'),
('2024003', '10082006', 'student', '2024003'),
('2024004', '28112006', 'student', '2024004'),
('guru001', 'guru123', 'teacher', NULL),
('admin', 'admin123', 'admin', NULL);

INSERT INTO iot_devices (device_code, device_name, location, api_key) VALUES
('ESP32-GATE-1', 'ESP32 Gate Utama', 'Gerbang Utama', 'CHANGE_ME_GATE_1_API_KEY'),
('ESP32-CAM-1', 'ESP32-CAM Kelas XII', 'Koridor Kelas XII', 'CHANGE_ME_CAM_1_API_KEY');

-- Attendance seed (last 5 school days)
DO $$
DECLARE
    d DATE;
    i INT;
BEGIN
    FOR i IN 0..4 LOOP
        d := CURRENT_DATE - i;
        INSERT INTO attendance (student_id, status, check_in_time, date, source) VALUES
        ('2024001', 'present',     '07:15:00', d, 'iot'),
        ('2024002', 'late',        '07:45:00', d, 'iot'),
        ('2024003', 'present',     '07:10:00', d, 'iot'),
        ('2024004', 'absent',      NULL,       d, 'web'),
        ('2024005', 'present',     '07:25:00', d, 'iot'),
        ('2024006', 'late',        '08:00:00', d, 'iot'),
        ('2024007', 'not_checked', NULL,       d, 'web'),
        ('2024008', 'present',     '07:20:00', d, 'iot')
        ON CONFLICT (student_id, date) DO NOTHING;
    END LOOP;
END $$;
