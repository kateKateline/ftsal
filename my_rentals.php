<?php
session_start();
include "includes/config.php";
include "includes/header.php";

require_once __DIR__ . '/function/auth.php';
require_once __DIR__ . '/function/helpers.php';

require_login();
$user_id = current_user_id();

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

// fetch user's rentals
$sql = "SELECT sp.id, sp.nama_peralatan, spd.quantity, spd.tanggal_sewa, spd.tanggal_kembali, spd.total_harga, spd.status, sp.satuan
        FROM sewa_peralatan_detail spd
        JOIN sewa_peralatan sp ON spd.peralatan_id = sp.id
        WHERE spd.user_id = $user_id
        ORDER BY spd.created_at DESC";
$res = mysqli_query($conn, $sql);
$rentals = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $rentals[] = $r;
    }
}

?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4">

        <?php if ($msg): ?>
            <div class="mb-6 bg-green-100 text-green-800 p-3 rounded">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="mb-6">
            <a href="sewa.php" class="text-blue-600 hover:underline">&larr; Kembali ke Sewa Peralatan</a>
        </div>

        <h1 class="text-2xl font-bold mb-4">Riwayat Penyewaan Peralatan</h1>

        <?php if (count($rentals) > 0): ?>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Peralatan</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Jumlah</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tanggal Sewa</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tanggal Kembali</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Total</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($rentals as $r): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($r['nama_peralatan']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700"><?= intval($r['quantity']) ?> <?= htmlspecialchars($r['satuan']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700"><?= friendly_date_id($r['tanggal_sewa']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700"><?= friendly_date_id($r['tanggal_kembali']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700 font-semibold"><?= format_rupiah($r['total_harga']) ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= 
                                        $r['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : (
                                        $r['status'] === 'confirmed' ? 'bg-blue-100 text-blue-700' : (
                                        $r['status'] === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                    )) ?>">
                                        <?= ucfirst($r['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <a href="process_sewa.php?action=cancel&id=<?= intval($r['id']) ?>" class="text-red-600 hover:text-red-800 text-xs" onclick="return confirm('Batalkan penyewaan ini?')">Batalkan</a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-600">
                <p>Anda belum memiliki penyewaan. <a href="sewa.php" class="text-blue-600 hover:underline">Mulai sewa peralatan sekarang</a></p>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php include "includes/footer.php"; ?>
