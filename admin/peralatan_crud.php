<?php
session_start();
include "includes/config.php"; // Pastikan koneksi ($conn) ada
include "includes/header.php";
require_once __DIR__ . '/function/auth.php';
require_once __DIR__ . '/function/helpers.php'; // Untuk helper format_rupiah

// --- Pengecekan Akses Admin ---
require_login();
if (($_SESSION['user']['role'] ?? 'user') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
// ------------------------------

$action = $_GET['action'] ?? 'create';
$peralatan_id = $_GET['id'] ?? 0;
$peralatan = [
    'nama_peralatan' => '', 
    'harga_sewa' => '', 
    'satuan' => 'hari', // Default satuan
    'stok' => 1, 
    'status' => 'tersedia'
];
$form_title = "Tambah Peralatan Baru";
$msg = '';

// --- (A) Proses GET (Untuk mengisi Form Edit) ---
if ($action === 'edit' && $peralatan_id > 0) {
    $form_title = "Edit Peralatan ID: " . $peralatan_id;
    // PENTING: Nama tabel adalah 'sewa_peralatan'
    $sql = "SELECT * FROM sewa_peralatan WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $peralatan_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $peralatan = mysqli_fetch_assoc($result);
        
        if (!$peralatan) {
            header("Location: admin.php?msg=" . urlencode("Error: Peralatan tidak ditemukan."));
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = "Error DB: " . mysqli_error($conn);
    }
}

// --- (B) Proses POST (Untuk Submit Form: Create atau Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_peralatan'] ?? '');
    $harga = (int)($_POST['harga_sewa'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? '');
    $stok = (int)($_POST['stok'] ?? 0);
    $status = $_POST['status'] ?? 'tidak tersedia';
    $target_id = (int)($_POST['peralatan_id'] ?? 0);
    $is_update = $target_id > 0;

    // Isi ulang data jika ada error
    $peralatan = [
        'nama_peralatan' => $nama, 
        'harga_sewa' => $harga, 
        'satuan' => $satuan, 
        'stok' => $stok, 
        'status' => $status
    ];

    if (empty($nama) || $harga <= 0 || $stok < 0) {
        $msg = "Error: Semua field wajib diisi dan harga/stok harus valid.";
    } else {
        if ($is_update) {
            // Logika UPDATE
            $sql = "UPDATE sewa_peralatan SET nama_peralatan = ?, harga_sewa = ?, satuan = ?, stok = ?, status = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sisisi", $nama, $harga, $satuan, $stok, $status, $target_id);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = "Peralatan ID {$target_id} berhasil diupdate.";
                    header("Location:dashboard_admin.php");
                    exit;
                } else {
                    $msg = "Error Update: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            // Logika CREATE
            $sql = "INSERT INTO sewa_peralatan (nama_peralatan, harga_sewa, satuan, stok, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sisss", $nama, $harga, $satuan, $stok, $status);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = "Peralatan baru '{$nama}' berhasil ditambahkan.";
                    header("Location:dashboard_admin.php");
                    exit;
                } else {
                    $msg = "Error Insert: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<main class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-green-600 mb-6"><?= $form_title ?></h1>

        <?php if (!empty($msg)): ?>
            <div class="mb-6 bg-red-100 text-red-800 p-4 rounded-lg shadow">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <form method="POST" action="peralatan_crud.php?action=<?= $action ?>&id=<?= $peralatan_id ?>">
                <input type="hidden" name="peralatan_id" value="<?= $peralatan_id ?>">
                
                <div class="mb-4">
                    <label for="nama_peralatan" class="block text-sm font-medium text-gray-700">Nama Peralatan</label>
                    <input type="text" id="nama_peralatan" name="nama_peralatan" required 
                           value="<?= htmlspecialchars($peralatan['nama_peralatan']) ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div class="mb-4">
                    <label for="harga_sewa" class="block text-sm font-medium text-gray-700">Harga Sewa per Satuan</label>
                    <input type="number" id="harga_sewa" name="harga_sewa" required min="1"
                           value="<?= htmlspecialchars($peralatan['harga_sewa']) ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="mb-4">
                    <label for="satuan" class="block text-sm font-medium text-gray-700">Satuan (Hari/Jam/Unit)</label>
                    <input type="text" id="satuan" name="satuan" required 
                           value="<?= htmlspecialchars($peralatan['satuan']) ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="mb-4">
                    <label for="stok" class="block text-sm font-medium text-gray-700">Stok</label>
                    <input type="number" id="stok" name="stok" required min="0"
                           value="<?= htmlspecialchars($peralatan['stok']) ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="mb-6">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-green-500 focus:border-green-500">
                        <option value="tersedia" <?= $peralatan['status'] === 'tersedia' ? 'selected' : '' ?>>Tersedia (Ready)</option>
                        <option value="tidak tersedia" <?= $peralatan['status'] === 'tidak tersedia' ? 'selected' : '' ?>>Tidak Tersedia (Out of Stock/Rusak)</option>
                    </select>
                </div>

                <div class="flex justify-end">
                    <a href="admin.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 transition duration-200 mr-4">
                        Batal
                    </a>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                        <?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Peralatan' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include "includes/footer.php"; ?>