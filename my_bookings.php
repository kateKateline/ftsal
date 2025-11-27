<?php
session_start();
include "includes/config.php";
include "includes/header.php";

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user']['id']);

$q = mysqli_query($conn, "SELECT b.*, l.nama, l.harga_per_jam FROM booking b JOIN lapangan l ON b.lapangan_id = l.id WHERE b.user_id=$user_id ORDER BY b.created_at DESC");

?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-2xl font-bold mb-4">Booking Saya</h1>

        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-4 bg-green-100 text-green-800 p-3 rounded"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full table-auto">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">Lapangan</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Jam</th>
                        <th class="p-3">Total</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; while ($row = mysqli_fetch_assoc($q)): ?>
                        <tr class="border-t">
                            <td class="p-3 align-top"><?= $i++ ?></td>
                            <td class="p-3 align-top"><?= htmlspecialchars($row['nama']) ?></td>
                            <td class="p-3 align-top"><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td class="p-3 align-top"><?= htmlspecialchars(substr($row['jam_mulai'],0,5)) ?> - <?= htmlspecialchars(substr($row['jam_selesai'],0,5)) ?></td>
                            <td class="p-3 align-top">Rp <?= number_format($row['total_harga'],0,',','.') ?></td>
                            <td class="p-3 align-top"><?= htmlspecialchars($row['status']) ?></td>
                            <td class="p-3 align-top">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <a href="process_booking.php?action=cancel&id=<?= $row['id'] ?>" class="text-red-600 hover:underline">Batal</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <a href="lapangan.php" class="text-blue-600 underline">Kembali ke daftar lapangan</a>
        </div>

    </div>
</main>

<?php include "includes/footer.php"; ?>
