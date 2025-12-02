<?php
session_start();
include "includes/config.php";
include "includes/header.php"; // Asumsi file ini ada dan berisi tag <head> pembuka

require_once __DIR__ . '/function/auth.php';
require_once __DIR__ . '/function/helpers.php';

require_login();
$user_id = current_user_id();

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

// Fetch user's bookings
$bookings_sql = "SELECT b.*, l.nama, l.harga_per_jam
    FROM booking b
    JOIN lapangan l ON b.lapangan_id = l.id
    WHERE b.user_id=$user_id
    ORDER BY b.created_at DESC";
$bookings_res = mysqli_query($conn, $bookings_sql);
$bookings = [];
if ($bookings_res) {
    while ($row = mysqli_fetch_assoc($bookings_res)) {
        $bookings[] = $row;
    }
}

// Fetch user's rentals
$rentals_sql = "SELECT sp.id, sp.nama_peralatan, spd.quantity, spd.tanggal_sewa, spd.tanggal_kembali, spd.total_harga, spd.status, sp.satuan
    FROM sewa_peralatan_detail spd
    JOIN sewa_peralatan sp ON spd.peralatan_id = sp.id
    WHERE spd.user_id = $user_id
    ORDER BY spd.tanggal_transaksi DESC";
$rentals_res = mysqli_query($conn, $rentals_sql);
$rentals = [];
if ($rentals_res) {
    while ($r = mysqli_fetch_assoc($rentals_res)) {
        $rentals[] = $r;
    }
}

// Calculate total for pending rentals
$total_pending = 0;
$has_pending = false;
foreach ($rentals as $r) {
    if ($r['status'] === 'pending') {
        $total_pending += $r['total_harga'];
        $has_pending = true;
    }
}
?>

<style>
    @media print {
        /* Sembunyikan semua elemen yang tidak perlu saat mencetak */
        header, footer, .print-hide, .nav-menu, .aksi-column, .bg-gradient-to-br, .mb-8, .mb-6 {
            display: none !important;
        }
        /* Tampilkan body, dan pastikan cetakan fokus pada tabel */
        body {
            background-color: #fff !important;
            color: #000 !important;
            padding: 0;
            margin: 0;
        }
        /* Paksa area cetak menjadi terlihat */
        .printable-area {
            display: block !important;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        /* Hapus margin dan padding pada elemen utama */
        main {
            padding: 0 !important;
            margin: 0 !important;
        }
    }
</style>
<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4">

        <?php if ($msg): ?>
            <div class="mb-6 bg-green-100 text-green-800 p-4 rounded-lg shadow print-hide">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="mb-8 print-hide">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Dashboard Pengguna</h1>
            <p class="text-gray-600">Kelola booking lapangan dan penyewaan peralatan Anda</p>
        </div>

        <div class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Booking Lapangan Saya</h2>
                <div>
                    <a href="lapangan.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 print-hide mr-2">Booking Baru</a>
                    <button onclick="printTable('bookingTable', 'Laporan Booking Lapangan')" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 print-hide">
                        📥 Cetak PDF
                    </button>
                </div>
            </div>

            <?php if (count($bookings) > 0): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden printable-area"> 
                    <div class="overflow-x-auto">
                        <table class="w-full" id="bookingTable"> 
                            <thead class="bg-blue-500 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">#</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Lapangan</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Tanggal</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Jam</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Total</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold aksi-column">Aksi</th> 
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php $i = 1; foreach ($bookings as $row): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?= $i++ ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($row['nama']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($row['tanggal']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <?= htmlspecialchars(substr($row['jam_mulai'],0,5)) ?> -
                                            <?= htmlspecialchars(substr($row['jam_selesai'],0,5)) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 font-semibold text-green-600">
                                            Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?=
                                                $row['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : (
                                                $row['status'] === 'confirmed' ? 'bg-blue-100 text-blue-700' : (
                                                $row['status'] === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                                )) ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm aksi-column">
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <a href="process_booking.php?action=pay&id=<?= $row['id'] ?>"
                                                   class="text-green-600 hover:text-green-800 font-medium mr-3 transition duration-200">
                                                     Bayar
                                                </a>
                                                <a href="process_booking.php?action=cancel&id=<?= $row['id'] ?>"
                                                   class="text-red-600 hover:text-red-800 font-medium transition duration-200">
                                                     Batal
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center print-hide">
                    <div class="text-gray-400 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <p class="text-gray-600 mb-4">Anda belum memiliki booking lapangan.</p>
                    <a href="lapangan.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200">Mulai Booking</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Penyewaan Peralatan Saya</h2>
                <div>
                    <a href="sewa.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 print-hide mr-2">Sewa Baru</a>
                    <button onclick="printTable('rentalTable', 'Laporan Penyewaan Peralatan')" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 print-hide">
                        📥 Cetak PDF
                    </button>
                </div>
            </div>

            <?php if (count($rentals) > 0): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden printable-area">
                    <div class="overflow-x-auto">
                        <table class="w-full" id="rentalTable"> 
                            <thead class="bg-green-500 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Peralatan</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Jumlah</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Tanggal Sewa</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Tanggal Kembali</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Total</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold aksi-column">Aksi</th> 
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($rentals as $r): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?= htmlspecialchars($r['nama_peralatan']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= intval($r['quantity']) ?> <?= htmlspecialchars($r['satuan']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= friendly_date_id($r['tanggal_sewa']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= friendly_date_id($r['tanggal_kembali']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700 font-semibold text-green-600"><?= format_rupiah($r['total_harga']) ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?=
                                                $r['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : (
                                                $r['status'] === 'confirmed' ? 'bg-blue-100 text-blue-700' : (
                                                $r['status'] === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                                )) ?>">
                                                <?= ucfirst($r['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm aksi-column">
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <a href="process_sewa.php?action=pay&id=<?= intval($r['id']) ?>" class="text-green-600 hover:text-green-800 font-medium mr-2 transition duration-200" onclick="return confirm('Konfirmasi pembayaran penyewaan ini?')">Bayar</a>
                                                <a href="process_sewa.php?action=cancel&id=<?= intval($r['id']) ?>" class="text-red-600 hover:text-red-800 font-medium transition duration-200" onclick="return confirm('Batalkan penyewaan ini?')">Batalkan</a>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($has_pending): ?>
                    <div class="mt-6 bg-gradient-to-r from-green-50 to-teal-50 rounded-xl shadow-lg p-6 border border-green-200 print-hide">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Bayar Semua Penyewaan Pending</h3>
                        <p class="text-gray-700 mb-4">Total pembayaran untuk semua penyewaan pending: <span class="font-bold text-green-600 text-lg"><?= format_rupiah($total_pending) ?></span></p>
                        <a href="process_sewa.php?action=pay_all" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-200 font-medium" onclick="return confirm('Konfirmasi pembayaran semua penyewaan pending?')">Bayar Semua</a>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center print-hide">
                    <div class="text-gray-400 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <p class="text-gray-600 mb-4">Anda belum memiliki penyewaan peralatan.</p>
                    <a href="sewa.php" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200">Mulai Sewa</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
    /**
     * Fungsi untuk mencetak konten dari ID tabel tertentu
     * dan menambahkan judul halaman.
     */
    function printTable(tableId, title) {
        const originalTitle = document.title;
        const originalContents = document.body.innerHTML;
        const printContents = document.getElementById(tableId).closest('.printable-area').outerHTML;
        
        // Buat konten baru yang akan dicetak
        const newHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${title}</title>
                <style>
                    /* Tambahkan styling dasar untuk cetakan */
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { text-align: center; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; color: #000; }
                    /* Sembunyikan kolom aksi saat mencetak */
                    .aksi-column { display: none; } 
                </style>
            </head>
            <body>
                <h1>${title}</h1>
                ${printContents}
            </body>
            </html>
        `;

        // Buka jendela baru untuk mencetak
        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write(newHtml);
        printWindow.document.close();
        printWindow.print();
        
        // Mengembalikan judul halaman setelah mencetak (Opsional)
        printWindow.onafterprint = function() {
            printWindow.close();
            document.title = originalTitle;
        };
    }
</script>

<?php include "includes/footer.php"; ?>