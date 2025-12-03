<?php
session_start();
include "includes/config.php";
include "includes/header.php"; // Asumsi header, navbar, dll.
require_once __DIR__ . '/function/auth.php';
require_once __DIR__ . '/function/helpers.php';

// --- Pengecekan Akses Admin ---
require_login();
if (($_SESSION['user']['role'] ?? 'user') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
// ------------------------------

$action = $_GET['action'] ?? 'create';
$lapangan_id = $_GET['id'] ?? 0;
$lapangan = ['nama' => '', 'harga_per_jam' => '', 'status' => 'tersedia'];
$form_title = "Tambah Lapangan Baru";

// --- (A) Proses GET (Untuk mengisi Form Edit) ---
if ($action === 'edit' && $lapangan_id > 0) {
    $form_title = "Edit Lapangan ID: " . $lapangan_id;
    $sql = "SELECT * FROM lapangan WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $lapangan_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $lapangan = mysqli_fetch_assoc($result);
        if (!$lapangan) {
            header("Location: admin.php?msg=" . urlencode("Error: Lapangan tidak ditemukan."));
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}

// --- (B) Proses POST (Untuk Submit Form: Create atau Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $harga = (int)$_POST['harga_per_jam'];
    $status = $_POST['status'];
    $is_update = !empty($_POST['lapangan_id']) && $_POST['lapangan_id'] > 0;
    $target_id = (int)($_POST['lapangan_id'] ?? 0);

    if (empty($nama) || $harga <= 0 || !in_array($status, ['tersedia', 'tidak tersedia'])) {
        $msg = "Error: Semua field wajib diisi dan harga harus valid.";
    } else {
        if ($is_update) {
            // Logika UPDATE
            $sql = "UPDATE lapangan SET nama = ?, harga_per_jam = ?, status = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sisi", $nama, $harga, $status, $target_id);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = "Lapangan ID {$target_id} berhasil diupdate.";
                    header("Location: admin.php?msg=" . urlencode($msg));
                    exit;
                } else {
                    $msg = "Error Update: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            // Logika CREATE
            $sql = "INSERT INTO lapangan (nama, harga_per_jam, status) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sis", $nama, $harga, $status);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = "Lapangan baru '{$nama}' berhasil ditambahkan.";
                    header("Location: admin.php?msg=" . urlencode($msg));
                    exit;
                } else {
                    $msg = "Error Insert: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    // Jika gagal, isi kembali data form untuk ditampilkan (hanya pada POST)
    $lapangan = ['nama' => $nama, 'harga_per_jam' => $harga, 'status' => $status];
}
?>

<main class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-blue-500 mb-6"><?= $form_title ?></h1>

        <?php if (isset($msg) && !empty($msg)): ?>
            <div class="mb-6 bg-red-100 text-red-800 p-4 rounded-lg shadow">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <form method="POST" action="lapangan_crud.php?action=<?= $action ?>&id=<?= $lapangan_id ?>">
                <input type="hidden" name="lapangan_id" value="<?= $lapangan_id ?>">
                
                <div class="mb-4">
                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lapangan</label>
                    <input type="text" id="nama" name="nama" required 
                           value="<?= htmlspecialchars($lapangan['nama'] ?? '') ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="harga_per_jam" class="block text-sm font-medium text-gray-700">Harga per Jam (Rupiah)</label>
                    <input type="number" id="harga_per_jam" name="harga_per_jam" required min="1"
                           value="<?= htmlspecialchars($lapangan['harga_per_jam'] ?? '') ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="mb-6">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="tersedia" <?= ($lapangan['status'] ?? '') === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="tidak tersedia" <?= ($lapangan['status'] ?? '') === 'tidak tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
                    </select>
                </div>

                <div class="flex justify-end">
                    <a href="admin.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 transition duration-200 mr-4">
                        Batal
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        <?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Lapangan' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include "includes/footer.php"; ?>