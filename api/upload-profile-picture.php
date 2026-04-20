<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Endpoint ini hanya untuk upload foto profil oleh pengguna yang sudah login
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed or Not Authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$response = ['success' => false, 'message' => 'Terjadi kesalahan.'];

if (isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $target_dir = "../uploads/profile/";

    // Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Gagal mengupload file. Kode error: ' . $file['error'];
        echo json_encode($response);
        exit;
    }

    // Validasi ukuran file (maks 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        $response['message'] = 'Ukuran file terlalu besar. Maksimal 2MB.';
        echo json_encode($response);
        exit;
    }

    // Validasi tipe file
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);

    if (!in_array($mime_type, $allowed_types)) {
        $response['message'] = 'Format file tidak valid. Hanya JPG, PNG, dan WEBP yang diizinkan.';
        echo json_encode($response);
        exit;
    }

    // Buat nama file yang unik
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = $username . '_' . time() . '.' . $extension;
    $target_file = $target_dir . $new_filename;

    // Pindahkan file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $conn = getDB();
        if ($conn) {
            try {
                // 1. Ambil path foto lama untuk dihapus
                $stmt = $conn->prepare("SELECT profile_picture_path FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $old_path = $stmt->fetchColumn();

                // 2. Update path di database
                $db_path = 'uploads/profile/' . $new_filename;
                $stmt = $conn->prepare("UPDATE users SET profile_picture_path = ? WHERE id = ?");
                $stmt->execute([$db_path, $user_id]);

                // 3. Hapus foto lama jika ada dan bukan default
                if ($old_path && $old_path !== 'assets/images/default-avatar.png' && file_exists('../' . $old_path)) {
                    unlink('../' . $old_path);
                }

                // 4. Update session dan kirim response sukses
                $_SESSION['profile_picture'] = $db_path;
                $response = [
                    'status' => 'success',
                    'message' => 'Foto profil berhasil diperbarui.',
                    'url' => $db_path
                ];
            } catch (Exception $e) {
                // Jika DB gagal, hapus file yang baru diupload
                unlink($target_file);
                $response['message'] = 'Gagal memperbarui database: ' . $e->getMessage();
            }
        } else {
            unlink($target_file);
            $response['message'] = 'Koneksi database gagal.';
        }
    } else {
        $response['message'] = 'Gagal memindahkan file yang diupload.';
    }
} else {
    $response['message'] = 'Tidak ada file yang diterima.';
}

echo json_encode($response);
?>