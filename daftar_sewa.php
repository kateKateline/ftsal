<?php
session_start();
include "includes/config.php";
include "includes/header.php";

require_once __DIR__ . '/function/peralatan.php';
require_once __DIR__ . '/function/auth.php';

// Cek user login
if (!is_logged_in()) {
    header('Location: login.php?error=' . urlencode("Silakan login untuk melihat riwayat sewa Anda."));
    exit;
}

$user_id = $_SESSION['user']['id'];
// Baris ini akan memanggil fungsi yang sudah diperbaiki di peralatan.php
$my_rentals = get_my_rentals($conn, $user_id); 

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

function get_status_class($status) {
    switch ($status) {
        case 'confirmed':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'completed':
            return 'bg-blue-100 text-blue-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4">

        <h1 class="text-2xl font-bold mb-6">Riwayat Sewa Peralatan Saya</h1>

        <?php if ($msg): ?>
            <div class="mb-4 bg-green-100 text-green-800 p-3 rounded">
                <?= $msg ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 bg-red-100 text-red-800 p-3 rounded">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (empty($my_rentals)): ?>
            <div class="bg-white p-6 rounded-lg shadow text-center">
                <p class="text-gray-600">Anda belum memiliki riwayat penyewaan peralatan.</p>
                <a href="sewa.php" class="mt-4 inline-block text-blue-600 underline">Mulai Sewa Sekarang</a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Sewa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peralatan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jml/Total Harga</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Sewa/Kembali</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Transaksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($my_rentals as $rental): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($rental['rental_id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($rental['nama_peralatan']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 underline">
                                    <a href="my_booking_detail.php?id=<?= $rental['booking_id'] ?>">#<?= htmlspecialchars($rental['booking_id']) ?></a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($rental['quantity']) ?> unit <br>
                                    <span class="font-semibold">Rp <?= number_format($rental['total_harga'], 0, ',', '.') ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= date('d M Y', strtotime($rental['tanggal_sewa'])) ?> s/d <br>
                                    <?= date('d M Y', strtotime($rental['tanggal_kembali'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= get_status_class($rental['status']) ?>">
                                        <?= strtoupper(htmlspecialchars($rental['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d M Y H:i', strtotime($rental['tanggal_transaksi'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php include "includes/footer.php"; ?>