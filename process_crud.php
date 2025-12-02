<?php
// ASUMSI: File koneksi database Anda berada di 'config/database.php'
require_once 'includes/config.php'; 
session_start();

// Pengecekan Akses Admin
if (($_SESSION['user']['role'] ?? 'user') !== 'admin') {
    header("Location: dashboard.php"); 
    exit;
}

// ===============================
// Fungsi Sanitasi Data (PENTING!)
// ===============================
if (!function_exists('sanitize')) {
    function sanitize($conn, $input) {
        if (is_array($input)) {
            // Jika input berupa array, proses setiap elemen (biasanya tidak terjadi di sini)
            return array_map(fn($item) => mysqli_real_escape_string($conn, htmlspecialchars(trim($item))), $input);
        }
        return mysqli_real_escape_string($conn, htmlspecialchars(trim($input)));
    }
}
// Pengecekan Koneksi
if (!isset($conn)) {
    // Jika $conn belum terdefinisi (misalnya di config/database.php), berikan error
    die("Database connection error: \$conn not defined.");
}

$msg = "";
$success = false;

// Ambil parameter dari URL atau POST
$table = sanitize($conn, $_REQUEST['table'] ?? '');
$action = sanitize($conn, $_REQUEST['action'] ?? '');
$id = intval($_REQUEST['id'] ?? 0);

if (!$table || (!$action && $_SERVER['REQUEST_METHOD'] !== 'POST')) {
    header("Location: dashboard_admin.php?msg=Error: Aksi atau tabel tidak valid.");
    exit;
}

// --------------------------------
// Aksi HAPUS (DELETE) - Menggunakan GET request
// --------------------------------
if ($action === 'delete' && $id > 0) {
    $allowed_tables = ['lapangan', 'sewa_peralatan', 'users'];
    if (in_array($table, $allowed_tables)) {
        // Khusus Users: cegah admin menghapus dirinya sendiri
        if ($table === 'users' && $id === ($_SESSION['user']['id'] ?? 0)) {
            $msg = "Error: Anda tidak bisa menghapus akun admin yang sedang login.";
        } else {
            // Delete data di tabel utama
            $delete_sql = "DELETE FROM `$table` WHERE id = $id";
            if (mysqli_query($conn, $delete_sql)) {
                $msg = "Data $table berhasil dihapus!";
                $success = true;
            } else {
                $msg = "Error saat menghapus data $table: " . mysqli_error($conn);
            }
        }
    } else {
        $msg = "Error: Tabel tidak diizinkan untuk dihapus.";
    }
    
// --------------------------------
// Aksi TAMBAH/EDIT (CREATE/UPDATE) - Menggunakan POST request
// --------------------------------
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Logika LAPANGAN ---
    if ($table === 'lapangan') {
        $nama = sanitize($conn, $_POST['nama'] ?? '');
        $harga_per_jam = intval($_POST['harga_per_jam'] ?? 0);
        $status = sanitize($conn, $_POST['status'] ?? 'ready');
        
        if (empty($nama) || $harga_per_jam <= 0) {
            $msg = "Error: Nama dan Harga per jam Lapangan wajib diisi.";
        } else {
            if ($action === 'create') {
                $sql = "INSERT INTO lapangan (nama, harga_per_jam, status) VALUES ('$nama', $harga_per_jam, '$status')";
                $msg_text = "ditambahkan";
            } elseif ($action === 'update' && $id > 0) {
                $sql = "UPDATE lapangan SET nama = '$nama', harga_per_jam = $harga_per_jam, status = '$status' WHERE id = $id";
                $msg_text = "diperbarui";
            }

            if (isset($sql) && mysqli_query($conn, $sql)) {
                $msg = "Data Lapangan berhasil $msg_text!";
                $success = true;
            } else if (isset($sql)) {
                $msg = "Error saat $msg_text data Lapangan: " . mysqli_error($conn);
            }
        }

    // --- Logika PERALATAN ---
    } elseif ($table === 'sewa_peralatan') {
        $nama_peralatan = sanitize($conn, $_POST['nama_peralatan'] ?? '');
        $stok = intval($_POST['stok'] ?? 0);
        $harga_sewa = intval($_POST['harga_sewa'] ?? 0);
        $satuan = sanitize($conn, $_POST['satuan'] ?? '');
        $status = sanitize($conn, $_POST['status'] ?? 'tersedia');
        
        if (empty($nama_peralatan) || $harga_sewa <= 0 || empty($satuan)) {
            $msg = "Error: Nama, Harga, dan Satuan Peralatan wajib diisi.";
        } else {
            if ($action === 'create') {
                $sql = "INSERT INTO sewa_peralatan (nama_peralatan, stok, harga_sewa, satuan, status) 
                        VALUES ('$nama_peralatan', $stok, $harga_sewa, '$satuan', '$status')";
                $msg_text = "ditambahkan";
            } elseif ($action === 'update' && $id > 0) {
                $sql = "UPDATE sewa_peralatan SET nama_peralatan = '$nama_peralatan', stok = $stok, 
                        harga_sewa = $harga_sewa, satuan = '$satuan', status = '$status' WHERE id = $id";
                $msg_text = "diperbarui";
            }

            if (isset($sql) && mysqli_query($conn, $sql)) {
                $msg = "Data Peralatan berhasil $msg_text!";
                $success = true;
            } else if (isset($sql)) {
                $msg = "Error saat $msg_text data Peralatan: " . mysqli_error($conn);
            }
        }

    // --- Logika USERS ---
    } elseif ($table === 'users') {
        $name = sanitize($conn, $_POST['name'] ?? '');
        $email = sanitize($conn, $_POST['email'] ?? '');
        $role = sanitize($conn, $_POST['role'] ?? 'user');
        $password = $_POST['password'] ?? null;
        
        if (empty($name) || empty($email)) {
             $msg = "Error: Nama dan Email Pengguna wajib diisi.";
        } else {
            // Cek apakah email sudah terdaftar (kecuali saat update diri sendiri)
            $email_check_sql = "SELECT id FROM users WHERE email = '$email'";
            if ($action === 'update' && $id > 0) {
                $email_check_sql .= " AND id != $id";
            }
            $email_check_res = mysqli_query($conn, $email_check_sql);
            
            if (mysqli_num_rows($email_check_res) > 0) {
                $msg = "Error: Email sudah digunakan oleh pengguna lain.";
            } else {
                if ($action === 'create') {
                    if (!$password) {
                        $msg = "Error: Password wajib diisi untuk pengguna baru.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')";
                        $msg_text = "ditambahkan";
                    }
                } elseif ($action === 'update' && $id > 0) {
                    $password_update = $password ? ", password = '" . password_hash($password, PASSWORD_DEFAULT) . "'" : "";
                    $sql = "UPDATE users SET name = '$name', email = '$email', role = '$role' $password_update WHERE id = $id";
                    $msg_text = "diperbarui";
                }
                
                if (isset($sql) && mysqli_query($conn, $sql)) {
                    $msg = "Data Pengguna berhasil $msg_text!";
                    $success = true;
                } else if (isset($sql)) {
                     $msg = "Error saat $msg_text data Pengguna: " . mysqli_error($conn);
                }
            }
        }
    } else {
        $msg = "Error: Tabel tidak didukung oleh proses ini.";
    }
}

// Redirect kembali ke halaman admin dashboard
$redirect_url = "dashboard_admin.php?msg=" . urlencode($msg);
header("Location: " . $redirect_url);
exit;
?>