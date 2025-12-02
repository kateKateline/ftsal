<?php
session_start();
include "includes/config.php";
include "includes/header.php";

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user']['id']);

$q = mysqli_query($conn, "SELECT b.*, l.nama, l.harga_per_jam 
    FROM booking b 
    JOIN lapangan l ON b.lapangan_id = l.id 
    WHERE b.user_id=$user_id 
    ORDER BY b.created_at DESC");
?>

<main class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8">
    <div class="max-w-4xl mx-auto px-4">

        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 bg-green-100 text-green-800 p-4 rounded-lg shadow">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Booking Lapangan Saya</h1>
                    <p class="text-gray-600">Kelola booking lapangan Anda</p>
                </div>
                <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">Kembali ke Dashboard</a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
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
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($q)): ?>
                        <tr class="border-t">
                            <td class="p-3"><?= $i++ ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['nama']) ?></td>

                            <td class="p-3"><?= htmlspecialchars($row['tanggal']) ?></td>

                            <td class="p-3">
                                <?= htmlspecialchars(substr($row['jam_mulai'],0,5)) ?> -
                                <?= htmlspecialchars(substr($row['jam_selesai'],0,5)) ?>
                            </td>

                            <td class="p-3">
                                Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>
                            </td>

                            <td class="p-3"><?= htmlspecialchars($row['status']) ?></td>

                            <td class="p-3">

                                <?php if ($row['status'] === 'pending'): ?>
                                    
                                    <a href="process_booking.php?action=pay&id=<?= $row['id'] ?>"
                                       class="text-green-600 hover:underline mr-3">
                                        Bayar
                                    </a>

                                    <a href="process_booking.php?action=cancel&id=<?= $row['id'] ?>"
                                       class="text-red-600 hover:underline">
                                        Batal
                                    </a>

                                <?php else: ?>
                                    -
                                <?php endif; ?>

                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include "includes/footer.php"; ?>
