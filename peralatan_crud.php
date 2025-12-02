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
$data = ['nama_peralatan' => '', 'stok' => 0, 'harga_sewa' => '', 'satuan' => '', 'status' => 'tersedia'];
$page_title = ($action === 'create') ? "Tambah Peralatan Baru" : "Edit Peralatan";

if ($action === 'edit' && $id > 0) {
    $sql = "SELECT * FROM sewa_peralatan WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) === 1) {
        $data = mysqli_fetch_assoc($result);
    } else {
        header("Location: dashboard_admin.php?msg=Error: Peralatan tidak ditemukan.");
        exit;
    }
}
?>

<main class="py-8">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-6"><?= $page_title ?></h1>
        <div class="bg-white p-8 rounded-xl shadow-lg">
            
            <form action="process_crud.php" method="POST">
                <input type="hidden" name="table" value="sewa_peralatan">
                <input type="hidden" name="action" value="<?= $action ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div class="mb-4">
                    <label for="nama_peralatan" class="block text-sm font-medium text-gray-700">Nama Peralatan</label>
                    <input type="text" name="nama_peralatan" id="nama_peralatan" required value="<?= htmlspecialchars($data['nama_peralatan']) ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 **text-gray-900**">
                </div>
                
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="harga_sewa" class="block text-sm font-medium text-gray-700">Harga Sewa (Rp)</label>
                        <input type="number" name="harga_sewa" id="harga_sewa" required value="<?= $data['harga_sewa'] ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 **text-gray-900**">
                    </div>
                    <div>
                        <label for="stok" class="block text-sm font-medium text-gray-700">Stok</label>
                        <input type="number" name="stok" id="stok" required value="<?= $data['stok'] ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 **text-gray-900**">
                    </div>
                    <div>
                        <label for="satuan" class="block text-sm font-medium text-gray-700">Satuan (Contoh: buah/unit)</label>
                        <input type="text" name="satuan" id="satuan" required value="<?= htmlspecialchars($data['satuan']) ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 **text-gray-900**">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 **text-gray-900**">
                        <option value="tersedia" <?= $data['status'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="habis" <?= $data['status'] === 'habis' ? 'selected' : '' ?>>Habis</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="dashboard_admin.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                        Batal
                    </a>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        Simpan Peralatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>