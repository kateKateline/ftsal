<?php
require_once 'includes/config.php'; 
session_start();
require_once 'includes/header.php'; 

// Pengecekan Akses Admin
if (($_SESSION['user']['role'] ?? 'user') !== 'admin') {
    header("Location: dashboard.php"); 
    exit;
}

// Fungsi sanitize (bisa di-include dari helper, atau didefinisikan di sini jika belum ada)
if (!function_exists('sanitize')) {
    function sanitize($conn, $input) { return mysqli_real_escape_string($conn, htmlspecialchars(trim($input))); }
}

$action = $_GET['action'] ?? 'create';
$id = intval($_GET['id'] ?? 0);
$data = ['name' => '', 'email' => '', 'role' => 'user', 'password' => ''];
$page_title = ($action === 'create') ? "Tambah Pengguna Baru" : "Edit Pengguna";

if ($action === 'edit' && $id > 0) {
    $sql = "SELECT id, name, email, role FROM users WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) === 1) {
        $data = mysqli_fetch_assoc($result);
    } else {
        header("Location: dashboard_admin.php?msg=Error: Pengguna tidak ditemukan.");
        exit;
    }
}
?>

<main class="py-8">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-6"><?= $page_title ?></h1>
        <div class="bg-white p-8 rounded-xl shadow-lg">
            
            <form action="process_crud.php" method="POST">
                <input type="hidden" name="table" value="users">
                <input type="hidden" name="action" value="<?= $action ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama</label>
                    <input type="text" name="name" id="name" required value="<?= htmlspecialchars($data['name']) ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 **text-gray-900**">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" required value="<?= htmlspecialchars($data['email']) ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 **text-gray-900**">
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password 
                        <?php if ($action === 'edit'): ?>
                            <span class="text-xs text-gray-500">(Kosongkan jika tidak ingin diubah)</span>
                        <?php endif; ?>
                    </label>
                    <input type="password" name="password" id="password" <?= $action === 'create' ? 'required' : '' ?>
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 **text-gray-900**">
                </div>

                <div class="mb-6">
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select name="role" id="role" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 **text-gray-900**">
                        <option value="user" <?= $data['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $data['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="dashboard_admin.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                        Batal
                    </a>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>