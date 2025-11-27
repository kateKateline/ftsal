<?php
session_start();
include "includes/config.php";

// Cek admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$msg = '';
$error = '';

// Handle form submission untuk tambah/edit lapangan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $harga_per_jam = intval($_POST['harga_per_jam']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $fasilitas = mysqli_real_escape_string($conn, $_POST['fasilitas']);
    
    $lapangan_id = isset($_POST['lapangan_id']) ? intval($_POST['lapangan_id']) : 0;
    
    // Handle upload gambar
    $gambar = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['size'] > 0) {
        $file = $_FILES['gambar'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $filename = 'lapangan_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], 'assets/images/' . $filename)) {
                $gambar = $filename;
            } else {
                $error = 'Gagal upload gambar';
            }
        } else {
            $error = 'Format gambar tidak didukung (jpg, jpeg, png, gif)';
        }
    }
    
    if ($action === 'tambah' && !$error) {
        $ins = "INSERT INTO lapangan (nama, harga_per_jam, status, gambar, deskripsi, fasilitas) VALUES ('$nama', $harga_per_jam, '$status', '$gambar', '$deskripsi', '$fasilitas')";
        if (mysqli_query($conn, $ins)) {
            $msg = 'Lapangan berhasil ditambahkan';
        } else {
            $error = 'Gagal menambahkan lapangan';
        }
    } elseif ($action === 'edit' && $lapangan_id && !$error) {
        $set = "nama='$nama', harga_per_jam=$harga_per_jam, status='$status', deskripsi='$deskripsi', fasilitas='$fasilitas'";
        if ($gambar) {
            $set .= ", gambar='$gambar'";
        }
        $upd = "UPDATE lapangan SET $set WHERE id=$lapangan_id";
        if (mysqli_query($conn, $upd)) {
            $msg = 'Lapangan berhasil diupdate';
        } else {
            $error = 'Gagal mengupdate lapangan';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    $id = intval($_GET['delete']);
    $del_q = mysqli_query($conn, "SELECT gambar FROM lapangan WHERE id=$id");
    if ($del_q && $row = mysqli_fetch_assoc($del_q)) {
        if (!empty($row['gambar']) && file_exists('assets/images/' . $row['gambar'])) {
            unlink('assets/images/' . $row['gambar']);
        }
    }
    if (mysqli_query($conn, "DELETE FROM lapangan WHERE id=$id")) {
        $msg = 'Lapangan berhasil dihapus';
    } else {
        $error = 'Gagal menghapus lapangan';
    }
}

// Fetch semua lapangan
$lapangans_q = mysqli_query($conn, "SELECT * FROM lapangan ORDER BY nama ASC");
$lapangans = [];
while ($row = mysqli_fetch_assoc($lapangans_q)) {
    $lapangans[] = $row;
}

// Fetch lapangan untuk edit (jika ada di GET)
$edit_lapangan = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_q = mysqli_query($conn, "SELECT * FROM lapangan WHERE id=$id");
    if ($edit_q && mysqli_num_rows($edit_q) > 0) {
        $edit_lapangan = mysqli_fetch_assoc($edit_q);
    }
}

include "includes/header.php";
?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4">

        <h1 class="text-3xl font-bold mb-6">Kelola Lapangan</h1>

        <?php if ($msg): ?>
            <div class="mb-6 bg-green-100 text-green-800 p-4 rounded"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-100 text-red-800 p-4 rounded"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form Tambah/Edit -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold mb-4"><?= $edit_lapangan ? 'Edit Lapangan' : 'Tambah Lapangan Baru' ?></h2>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="<?= $edit_lapangan ? 'edit' : 'tambah' ?>">
                <?php if ($edit_lapangan): ?>
                    <input type="hidden" name="lapangan_id" value="<?= $edit_lapangan['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lapangan</label>
                    <input type="text" name="nama" required value="<?= htmlspecialchars($edit_lapangan['nama'] ?? '') ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga per Jam</label>
                    <input type="number" name="harga_per_jam" required value="<?= htmlspecialchars($edit_lapangan['harga_per_jam'] ?? '') ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="ready" <?= ($edit_lapangan['status'] ?? '') === 'ready' ? 'selected' : '' ?>>Ready</option>
                        <option value="maintenance" <?= ($edit_lapangan['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gambar Lapangan</label>
                    <input type="file" name="gambar" accept="image/*" class="w-full p-2 border rounded">
                    <?php if ($edit_lapangan && !empty($edit_lapangan['gambar'])): ?>
                        <p class="text-sm text-gray-500 mt-1">Gambar saat ini: <?= htmlspecialchars($edit_lapangan['gambar']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($edit_lapangan['deskripsi'] ?? '') ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fasilitas (pisahkan dengan koma)</label>
                    <input type="text" name="fasilitas" value="<?= htmlspecialchars($edit_lapangan['fasilitas'] ?? 'Lapangan - Tempat Parkir - Toilet - Kantin') ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition font-semibold">
                        <?= $edit_lapangan ? 'Update Lapangan' : 'Tambah Lapangan' ?>
                    </button>
                    <?php if ($edit_lapangan): ?>
                        <a href="manage_lapangan.php" class="px-6 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 transition font-semibold">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Daftar Lapangan -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Daftar Lapangan</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold">Nama</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold">Harga/Jam</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold">Gambar</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($lapangans as $lap): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm"><?= htmlspecialchars($lap['nama']) ?></td>
                                <td class="px-6 py-3 text-sm">Rp <?= number_format($lap['harga_per_jam'],0,',','.') ?></td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="px-2 py-1 rounded text-xs <?= $lap['status']=='ready' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= htmlspecialchars($lap['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    <?php if (!empty($lap['gambar'])): ?>
                                        <img src="assets/images/<?= htmlspecialchars($lap['gambar']) ?>" alt="Gambar" class="w-12 h-12 object-cover rounded">
                                    <?php else: ?>
                                        <span class="text-gray-400">Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    <a href="manage_lapangan.php?edit=<?= $lap['id'] ?>" class="text-blue-600 hover:underline mr-3">Edit</a>
                                    <a href="manage_lapangan.php?delete=<?= $lap['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<?php include "includes/footer.php"; ?>
