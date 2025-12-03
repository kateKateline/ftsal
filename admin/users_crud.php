<?php
session_start();
include "includes/config.php"; // Pastikan koneksi ($conn) ada
include "includes/header.php";
require_once __DIR__ . '/function/auth.php';
// require_once __DIR__ . '/function/helpers.php'; // Helper tidak diperlukan di sini, kecuali untuk password hashing

// --- Pengecekan Akses Admin ---
require_login();
if (($_SESSION['user']['role'] ?? 'user') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
// ------------------------------

$action = $_GET['action'] ?? 'create';
$user_id = $_GET['id'] ?? 0;
$user = [
    'name' => '', 
    'email' => '', 
    'role' => 'user', 
    'password_placeholder' => '' // Tidak menyimpan password lama, hanya untuk form
];
$form_title = "Tambah Pengguna Baru";
$msg = '';

// --- (A) Proses GET (Untuk mengisi Form Edit) ---
if ($action === 'edit' && $user_id > 0) {
    $form_title = "Edit Pengguna ID: " . $user_id;
    $sql = "SELECT id, name, email, role FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if (!$user) {
            header("Location: admin.php?msg=" . urlencode("Error: Pengguna tidak ditemukan."));
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = "Error DB: " . mysqli_error($conn);
    }
}

// --- (B) Proses POST (Untuk Submit Form: Create atau Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $target_id = (int)($_POST['user_id'] ?? 0);
    $is_update = $target_id > 0;

    // Isi ulang data jika ada error
    $user = [
        'name' => $name, 
        'email' => $email, 
        'role' => $role
    ];
    
    if (empty($name) || empty($email) || (!$is_update && empty($password))) {
        $msg = "Error: Nama, Email, dan Password (untuk pengguna baru) wajib diisi.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Error: Format email tidak valid.";
    } else {
        // PENTING: Gunakan password_hash() untuk hashing yang aman (Asumsi ada)
        $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
        
        if ($is_update) {
            // Logika UPDATE
            if ($hashed_password) {
                // Update dengan password baru
                $sql = "UPDATE users SET name = ?, email = ?, password = ?, role = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                $types = "ssssi";
                $params = [$name, $email, $hashed_password, $role, $target_id];
            } else {
                // Update tanpa mengubah password
                $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                $types = "sssi";
                $params = [$name, $email, $role, $target_id];
            }
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = "Pengguna ID {$target_id} berhasil diupdate.";
                    header("Location: admin.php?msg=" . urlencode($msg));
                    exit;
                } else {
                    $msg = "Error Update: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            // Logika CREATE
            // Cek duplikasi email sebelum insert
            $check_sql = "SELECT id FROM users WHERE email = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                 $msg = "Error: Email sudah terdaftar.";
            } else {
                $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed_password, $role);
                    if (mysqli_stmt_execute($stmt)) {
                        $msg = "Pengguna baru '{$name}' berhasil ditambahkan.";
                        header("Location: admin.php?msg=" . urlencode($msg));
                        exit;
                    } else {
                        $msg = "Error Insert: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            mysqli_stmt_close($check_stmt);
        }
    }
}
?>

<main class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-indigo-600 mb-6"><?= $form_title ?></h1>

        <?php if (!empty($msg)): ?>
            <div class="mb-6 bg-red-100 text-red-800 p-4 rounded-lg shadow">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <form method="POST" action="users_crud.php?action=<?= $action ?>&id=<?= $user_id ?>">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" id="name" name="name" required 
                           value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password 
                    <?php if ($action === 'edit'): ?>
                        <span class="text-xs text-gray-500">(Kosongkan jika tidak ingin diubah)</span>
                    <?php endif; ?>
                    </label>
                    <input type="password" id="password" name="password" <?= $action === 'create' ? 'required' : '' ?>
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="mb-6">
                    <label for="role" class="block text-sm font-medium text-gray-700">Role Pengguna</label>
                    <select id="role" name="role" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User Biasa</option>
                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <div class="flex justify-end">
                    <a href="admin.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 transition duration-200 mr-4">
                        Batal
                    </a>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-200">
                        <?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Pengguna' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include "includes/footer.php"; ?>