<?php
require_once 'includes/config.php'; 
session_start();
// ASUMSI: File header/template Anda berada di 'includes/header.php'
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
$data = ['nama' => '', 'harga_per_jam' => '', 'status' => 'ready'];
$page_title = ($action === 'create') ? "Tambah Lapangan Baru" : "Edit Lapangan";

if ($action === 'edit' && $id > 0) {
    $sql = "SELECT * FROM lapangan WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) === 1) {
        $data = mysqli_fetch_assoc($result);
    } else {
        header("Location: dashboard_admin.php?msg=Error: Lapangan tidak ditemukan.");
        exit;
    }
}
?>

<main class="py-8">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-6"><?= $page_title ?></h1>
        <div class="bg-white p-8 rounded-xl shadow-lg">
            
            <form action="process_crud.php" method="POST">
                <input type="hidden" name="table" value="lapangan">
                <input type="hidden" name="action" value="<?= $action ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div class="mb-4">
                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lapangan</label>
                    <input type="text" name="nama" id="nama" required value="<?= htmlspecialchars($data['nama']) ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500 **text-gray-900**">
                </div>
                
                <div class="mb-4">
                    <label for="harga_per_jam" class="block text-sm font-medium text-gray-700">Harga Per Jam (Rp)</label>
                    <input type="number" name="harga_per_jam" id="harga_per_jam" required value="<?= $data['harga_per_jam'] ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500 **text-gray-900**">
                </div>

                <div class="mb-6">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500 **text-gray-900**">
                        <option value="ready" <?= $data['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                        <option value="maintenance" <?= $data['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="dashboard_admin.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-200">
                        Batal
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        Simpan Lapangan
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php 
// ASUMSI: File footer Anda berada di 'includes/footer.php'
require_once 'includes/footer.php'; 
?>