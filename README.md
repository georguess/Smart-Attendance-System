# 📊 Smart Attendance System – SMAN 1 Pringsewu

Sistem monitoring absensi berbasis IoT menggunakan PHP Native + PostgreSQL.

---

## 🚀 CARA MENJALANKAN

### Prasyarat
- PHP 8.0+
- PostgreSQL 14+
- Web Server (Apache/Nginx) atau PHP built-in server

### 1. Setup Database
```bash
# Buat database PostgreSQL
createdb smart_attendance

# Import schema + seed data
psql -U postgres -d smart_attendance -f config/schema.sql
```

### 2. Konfigurasi Database
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'smart_attendance');
define('DB_USER', 'postgres');
define('DB_PASS', 'your_password');
```

### 3. Jalankan Server
```bash
# Menggunakan PHP built-in server
cd smart-attendance
php -S localhost:8080

# Buka browser
http://localhost:8080
```

---

## 🔐 DEMO CREDENTIALS

| Role    | Username | Password  |
|---------|----------|-----------|
| Student | 2024001  | 15032006  |
| Teacher | guru001  | guru123   |
| Admin   | admin    | admin123  |

Catatan: Pada mode database, akun dibaca dari tabel `users` di PostgreSQL.

---

## 📁 STRUKTUR FILE

```
smart-attendance/
│
├── index.php          → Landing Page (Hero + Features)
├── dashboard.php      → Dashboard Utama
├── login.php          → Halaman Login
├── my-status.php      → Status Siswa
├── classes.php        → Class Monitoring
│
├── config/
│   ├── database.php   → Konfigurasi DB + Mock Data
│   └── schema.sql     → SQL Schema + Seed Data
│
└── assets/
    ├── css/style.css  → Stylesheet utama
    └── js/app.js      → JavaScript (clock, chart, sidebar, dll)
```

---

## 🎨 COLOR PALETTE

| Warna      | Hex       |
|------------|-----------|
| Primary    | #853953   |
| Secondary  | #612D53   |
| Dark       | #2C2C2C   |
| Background | #F3F4F4   |

---

## 👥 ROLE-BASED ACCESS

| Fitur              | Guest | Student | Teacher | Admin |
|--------------------|-------|---------|---------|-------|
| Statistik          | ✅    | ✅      | ✅      | ✅    |
| Chart              | ✅    | ✅      | ✅      | ✅    |
| Activity Feed      | ❌    | ✅      | ✅      | ✅    |
| Student Table      | ❌    | ❌      | ✅      | ✅    |
| Camera Monitor     | ❌    | ❌      | ✅      | ✅    |
| My Status          | ❌    | ✅      | ❌      | ✅    |
| Class Monitoring   | ❌    | ❌      | ✅      | ✅    |
| Attendance Rules   | ❌    | ❌      | ❌      | ✅    |

---

## ⏰ ATURAN WAKTU ABSENSI

| Status       | Waktu              |
|--------------|--------------------|
| Hadir        | ≤ 07:30 WIB        |
| Terlambat    | 07:31 – 08:15 WIB  |
| Tidak Hadir  | > 08:15 WIB        |
| Belum Check  | Sebelum 08:15 WIB  |

---

## 🛠️ TECH STACK

- **Backend**: PHP Native (no framework)
- **Database**: PostgreSQL
- **Frontend**: HTML + CSS Custom
- **Charts**: Chart.js
- **Icons**: Font Awesome 6
- **Font**: Poppins (Google Fonts)

---

## 📡 IoT Integration

Sistem dirancang untuk menerima data dari ESP32-CAM:
- Face recognition untuk auto check-in
- Real-time data push ke database
- Camera monitoring feed

Endpoint API absensi:
- `POST /api/attendance.php`
- Payload minimal: `rfid_tag`
- Payload opsional: `device_code`, `api_key`, `photo_base64`

---

*Developed by Himpunan Mahasiswa Teknik Elektro – Universitas Lampung*
