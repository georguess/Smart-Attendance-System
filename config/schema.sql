-- ============================================
-- Smart Attendance System – PostgreSQL Schema
-- SMAN 1 Pringsewu
-- ============================================

-- Drop existing tables
DROP TABLE IF EXISTS attendance CASCADE;
DROP TABLE IF EXISTS students   CASCADE;
DROP TABLE IF EXISTS schools    CASCADE;
DROP TABLE IF EXISTS users      CASCADE;

-- Schools
CREATE TABLE schools (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    logo       VARCHAR(255),
    image      VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Students
CREATE TABLE students (
    id         SERIAL PRIMARY KEY,
    school_id  INT REFERENCES schools(id),
    name       VARCHAR(100) NOT NULL,
    student_id VARCHAR(20)  UNIQUE NOT NULL,
    class      VARCHAR(30),
    gender     CHAR(1) CHECK (gender IN ('L', 'P')),
    date_of_birth DATE,
    photo      VARCHAR(255),
    rfid_tag   VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Attendance
CREATE TABLE attendance (
    id            SERIAL PRIMARY KEY,
    student_id    VARCHAR(20) REFERENCES students(student_id),
    status        VARCHAR(20) CHECK (status IN ('present','late','absent','not_checked')),
    check_in_time TIME,
    date          DATE DEFAULT CURRENT_DATE,
    created_at    TIMESTAMP DEFAULT NOW()
);

-- Users (for login) - username adalah NISN
CREATE TABLE users (
    id         SERIAL PRIMARY KEY,
    username   VARCHAR(50) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(20) CHECK (role IN ('student','teacher','admin')),
    student_id VARCHAR(20) REFERENCES students(student_id),
    created_at TIMESTAMP DEFAULT NOW()
);

-- ── SEED DATA ──────────────────────────────

-- School
INSERT INTO schools (name, logo, image) VALUES
('SMAN 1 Gadingrejo', 'images/Logo_SMA_Negeri_1_Gadingrejo.png', 'images/school.jpg');

-- Students
INSERT INTO students (school_id, name, student_id, class, gender, date_of_birth) VALUES
(1, 'Ahmad Fauzi',      '2024001', 'XII IPA 1', 'L', '2006-03-15'),
(1, 'Budi Santoso',     '2024002', 'XII IPA 1', 'L', '2006-05-22'),
(1, 'Citra Dewi',       '2024003', 'XII IPA 2', 'P', '2006-08-10'),
(1, 'Dian Permata',     '2024004', 'XII IPS 1', 'P', '2006-11-28'),
(1, 'Eko Prasetyo',     '2024005', 'XII IPS 1', 'L', '2007-01-05'),
(1, 'Fitri Handayani',  '2024006', 'XII IPA 2', 'P', '2006-07-14'),
(1, 'Gilang Ramadhan',  '2024007', 'XII IPS 2', 'L', '2006-09-20'),
(1, 'Hana Safitri',     '2024008', 'XII IPS 2', 'P', '2006-12-03');

-- Attendance (today + last 5 days)
DO $$
DECLARE
    d DATE;
    i INT;
BEGIN
    FOR i IN 0..4 LOOP
        d := CURRENT_DATE - i;
        INSERT INTO attendance (student_id, status, check_in_time, date) VALUES
        ('2024001', 'present',     '07:15:00', d),
        ('2024002', 'late',        '07:45:00', d),
        ('2024003', 'present',     '07:10:00', d),
        ('2024004', 'absent',      NULL,       d),
        ('2024005', 'present',     '07:25:00', d),
        ('2024006', 'late',        '08:00:00', d),
        ('2024007', 'not_checked', NULL,       d),
        ('2024008', 'present',     '07:20:00', d);
    END LOOP;
END $$;

-- Users (username = NISN, password = tanggal lahir format dd/mm/yyyy)
INSERT INTO users (username, password, role, student_id) VALUES
('2024001', '15/03/2006', 'student', '2024001'),
('2024002', '22/05/2006', 'student', '2024002'),
('2024003', '10/08/2006', 'student', '2024003'),
('guru001', 'guru123', 'teacher', NULL),
('admin', 'admin123', 'admin', NULL);

-- NOTE: In production, use password_hash() in PHP for passwords!
