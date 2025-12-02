<?php
session_start();
include "includes/config.php";
// Asumsi file ini berisi header HTML, tag pembuka <body>, dan navbar
include "includes/header.php";

require_once __DIR__ . '/function/auth.php';
require_once __DIR__ . '/function/helpers.php'; // Asumsi ini berisi format_rupiah, friendly_date_id, dll.

// ... kode awal session_start(), include, require_once ...

// --- Pengecekan Akses Admin ---
require_login();
if (($_SESSION['user']['role'] ?? 'user') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
// ------------------------------

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

// 1. --- Fetch SEMUA Booking Lapangan (Full Read) ---
$all_bookings_sql = "SELECT b.*, l.nama AS lapangan_nama, u.name AS user_name, u.email AS user_email
    FROM booking b
    JOIN lapangan l ON b.lapangan_id = l.id
    JOIN users u ON b.user_id = u.id -- DIGANTI: Menggunakan tabel 'users'
    ORDER BY b.created_at DESC";
$all_bookings_res = mysqli_query($conn, $all_bookings_sql);
$all_bookings = [];
if ($all_bookings_res) {
    while ($row = mysqli_fetch_assoc($all_bookings_res)) {
        $all_bookings[] = $row;
    }
}

// 2. --- Fetch SEMUA Penyewaan Peralatan (Full Read) ---
$all_rentals_sql = "SELECT spd.id, sp.nama_peralatan, spd.quantity, spd.tanggal_sewa, spd.tanggal_kembali, spd.total_harga, spd.status, sp.satuan, u.name AS user_name
    FROM sewa_peralatan_detail spd -- DIGANTI: Menggunakan tabel 'sewa_peralatan_detail'
    JOIN sewa_peralatan sp ON spd.peralatan_id = sp.id
    JOIN users u ON spd.user_id = u.id -- DIGANTI: Menggunakan tabel 'users'
    ORDER BY spd.tanggal_transaksi DESC";
$all_rentals_res = mysqli_query($conn, $all_rentals_sql);
$all_rentals = [];
if ($all_rentals_res) {
    while ($r = mysqli_fetch_assoc($all_rentals_res)) {
        $all_rentals[] = $r;
    }
}

// 3. --- Data Master Lapangan (CRUD) ---
$lapangan_sql = "SELECT id, nama, harga_per_jam, status FROM lapangan ORDER BY id ASC";
$lapangan_res = mysqli_query($conn, $lapangan_sql);
$lapangan_data = [];
if ($lapangan_res) {
    while ($l = mysqli_fetch_assoc($lapangan_res)) {
        $lapangan_data[] = $l;
    }
}

// 4. --- Data Master Peralatan (CRUD) ---
$peralatan_sql = "SELECT id, nama_peralatan, harga_sewa, satuan, stok, status FROM sewa_peralatan ORDER BY id ASC"; // Menggunakan 'harga_sewa'
$peralatan_res = mysqli_query($conn, $peralatan_sql);
$peralatan_data = [];
if ($peralatan_res) {
    while ($p = mysqli_fetch_assoc($peralatan_res)) {
        $peralatan_data[] = $p;
    }
}

// 5. --- Data Master Users (CRUD) ---
$users_sql = "SELECT id, name, email, role FROM users ORDER BY id ASC";
$users_res = mysqli_query($conn, $users_sql);
$users_data = [];
if ($users_res) {
    while ($u = mysqli_fetch_assoc($users_res)) {
        $users_data[] = $u;
    }
}

// Tambahkan Fungsi Helper untuk Rupiah jika belum ada di helpers.php
if (!function_exists('format_rupiah')) {
    function format_rupiah($angka)
    {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}


// ... (Bagian PHP di atas, termasuk koneksi database dan fetching data, diletakkan di sini) ...

// Tambahkan atau pastikan helper ini ada di file PHP Anda:
if (!function_exists('getStatusClass')) {
    function getStatusClass($status, $type = 'booking')
    {
        $status = strtolower($status);
        if ($type === 'master') {
            return $status === 'ready' || $status === 'tersedia' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
        }
        switch ($status) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-700';
            case 'confirmed':
                return 'bg-blue-100 text-blue-700';
            case 'completed':
                return 'bg-green-100 text-green-700';
            case 'admin':
                return 'bg-red-100 text-red-700';
            default:
                return 'bg-gray-100 text-gray-700';
        }
    }
}
if (!function_exists('format_rupiah')) {
    function format_rupiah($angka)
    {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}
?>

<style>
    /* Styling untuk Print/Cetak */
    @media print {

        /* Sembunyikan elemen non-data saat mencetak */
        header,
        footer,
        .print-hide,
        .aksi-column,
        .nav-menu {
            display: none !important;
        }

        body {
            background-color: #fff !important;
            color: #000 !important;
            padding: 0;
            margin: 0;
        }

        .printable-area {
            display: block !important;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        main {
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Style tabel untuk cetak */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-size: 10px;
        }

        th {
            background-color: #eee;
        }
    }
</style>

<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4">

        <div class="mb-8 print-hide">
            <h1 class="text-3xl font-bold text-blue-500 mb-2">Admin Dashboard</h1>
            <p class="text-gray-600">Kelola  Lapangan, Peralatan, dan Pengguna. Monitor Transaksi.</p>
        </div>

        <?php if ($msg): ?>
            <div class="mb-6 bg-green-100 text-green-800 p-4 rounded-lg shadow print-hide">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="mb-8 print-hide">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs" id="adminTabs">
                    <button data-tab="master_lapangan" class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none border-blue-600 text-blue-600">
                        Lapangan
                    </button>
                    <button data-tab="master_peralatan" class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none">
                        Peralatan
                    </button>
                    <button data-tab="master_users" class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none">
                        Users
                    </button>
                    <button data-tab="all_bookings" class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none">
                        Laporan Booking
                    </button>
                    <button data-tab="all_rentals" class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none">
                        Laporan Penyewaan
                    </button>
                </nav>
            </div>
        </div>

        <div id="master_lapangan" class="tab-content">
            <div class="flex justify-between items-center mb-6 print-hide">
                <h2 class="text-2xl font-semibold text-gray-800"> Lapangan</h2>
                <div>
                    <a href="lapangan_crud.php?action=create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 print-hide mr-2">
                        Tambah Lapangan
                    </a>
                    <button onclick="printTable('lapanganTable', 'Laporan Master Lapangan')" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 print-hide">
                        Cetak PDF
                    </button>
                </div>
            </div>

            <?php if (!empty($lapangan_data)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden printable-area">
                    <div class="overflow-x-auto">
                        <table class="w-full" id="lapanganTable">
                            <thead class="bg-blue-500 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Nama Lapangan</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Harga/Jam</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold aksi-column">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($lapangan_data as $l): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?= $l['id'] ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($l['nama']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700 font-semibold text-green-600">
                                            <?= format_rupiah($l['harga_per_jam']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getStatusClass($l['status'], 'master') ?>">
                                                <?= ucfirst($l['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm aksi-column">
                                            <a href="lapangan_crud.php?action=edit&id=<?= $l['id'] ?>" class="text-indigo-600 hover:text-indigo-800 font-medium mr-3 transition duration-200">
                                                Edit
                                            </a>
                                            <a href="process_crud.php?table=lapangan&action=delete&id=<?= $l['id'] ?>"
                                                class="text-red-600 hover:text-red-800 font-medium transition duration-200"
                                                onclick="return confirm('Yakin ingin menghapus lapangan ini?')">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center print-hide">
                    <p class="text-gray-600 mb-4">Belum ada data Lapangan.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="master_peralatan" class="tab-content hidden">
            <div class="flex justify-between items-center mb-6 print-hide">
                <h2 class="text-2xl font-semibold text-gray-800"> Peralatan</h2>
                <div>
                    <a href="peralatan_crud.php?action=create" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 print-hide mr-2">
                        Tambah Peralatan
                    </a>
                    <button onclick="printTable('peralatanTable', 'Laporan Master Peralatan')" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 print-hide">
                        Cetak PDF
                    </button>
                </div>
            </div>

            <?php if (!empty($peralatan_data)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden printable-area">
                    <div class="overflow-x-auto">
                        <table class="w-full" id="peralatanTable">
                            <thead class="bg-green-500 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Nama Peralatan</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Harga Sewa</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Stok</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold aksi-column">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($peralatan_data as $p): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?= $p['id'] ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($p['nama_peralatan']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700 font-semibold text-green-600">
                                            <?= format_rupiah($p['harga_sewa']) ?> / <?= htmlspecialchars($p['satuan']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= intval($p['stok']) ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getStatusClass($p['status'], 'master') ?>">
                                                <?= ucfirst($p['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm aksi-column">
                                            <a href="peralatan_crud.php?action=edit&id=<?= $p['id'] ?>" class="text-indigo-600 hover:text-indigo-800 font-medium mr-3 transition duration-200">
                                                Edit
                                            </a>
                                            <a href="process_crud.php?table=peralatan&action=delete&id=<?= $p['id'] ?>"
                                                class="text-red-600 hover:text-red-800 font-medium transition duration-200"
                                                onclick="return confirm('Yakin ingin menghapus peralatan ini?')">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center print-hide">
                    <p class="text-gray-600 mb-4">Belum ada data Peralatan.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="master_users" class="tab-content hidden">
            <div class="flex justify-between items-center mb-6 print-hide">
                <h2 class="text-2xl font-semibold text-gray-800"> Pengguna</h2>
                <div>
                    <a href="users_crud.php?action=create" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-200 print-hide mr-2">
                        Tambah Pengguna
                    </a>
                    <button onclick="printTable('usersTable', 'Laporan Master Pengguna')" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 print-hide">
                        Cetak PDF
                    </button>
                </div>
            </div>

            <?php if (!empty($users_data)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden printable-area">
                    <div class="overflow-x-auto">
                        <table class="w-full" id="usersTable">
                            <thead class="bg-indigo-500 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Nama</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Email</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Role</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold aksi-column">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($users_data as $u): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?= $u['id'] ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($u['name']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($u['email']) ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getStatusClass($u['role']) ?>">
                                                <?= ucfirst($u['role']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm aksi-column">
                                            <a href="users_crud.php?action=edit&id=<?= $u['id'] ?>" class="text-indigo-600 hover:text-indigo-800 font-medium mr-3 transition duration-200">
                                                Edit
                                            </a>
                                            <a href="process_crud.php?table=users&action=delete&id=<?= $u['id'] ?>"
                                                class="text-red-600 hover:text-red-800 font-medium transition duration-200"
                                                onclick="return confirm('Yakin ingin menghapus pengguna ini?')">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center print-hide">
                    <p class="text-gray-600 mb-4">Belum ada data Pengguna.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="all_bookings" class="tab-content hidden">
            <div class="flex justify-between items-center mb-6 print-hide">
                <h2 class="text-2xl font-semibold text-gray-800">Laporan Semua Booking Lapangan</h2>
                <button onclick="printTable('allBookingTable', 'Laporan Semua Booking Lapangan')" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 print-hide">
                    Cetak PDF
                </button>
            </div>

            <?php if (!empty($all_bookings)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden printable-area">
                    <div class="overflow-x-auto">
                        <table class="w-full" id="allBookingTable">
                            <thead class="bg-red-500 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Booking ID</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">User</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Lapangan</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Tanggal & Jam</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Total</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold aksi-column">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($all_bookings as $row): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?= $row['id'] ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($row['user_name']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($row['lapangan_nama']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <?= htmlspecialchars($row['tanggal']) ?><br>
                                            <span class="text-xs"><?= htmlspecialchars(substr($row['jam_mulai'], 0, 5)) ?> - <?= htmlspecialchars(substr($row['jam_selesai'], 0, 5)) ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 font-semibold text-red-600">
                                            <?= format_rupiah($row['total_harga']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getStatusClass($row['status']) ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm aksi-column">
                                            <span class="text-gray-400">Read Only</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center print-hide">
                    <p class="text-gray-600 mb-4">Belum ada data Booking Lapangan.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="all_rentals" class="tab-content hidden">
            <div class="flex justify-between items-center mb-6 print-hide">
                <h2 class="text-2xl font-semibold text-gray-800">Laporan Semua Penyewaan Peralatan</h2>
                <button onclick="printTable('allRentalTable', 'Laporan Semua Penyewaan Peralatan')" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 print-hide">
                    Cetak PDF
                </button>
            </div>

            <?php if (!empty($all_rentals)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden printable-area">
                    <div class="overflow-x-auto">
                        <table class="w-full" id="allRentalTable">
                            <thead class="bg-yellow-600 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">User</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Peralatan</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Jumlah</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Tgl Sewa/Kembali</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Total</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold aksi-column">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($all_rentals as $r): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?= $r['id'] ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($r['user_name']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($r['nama_peralatan']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?= intval($r['quantity']) ?> <?= htmlspecialchars($r['satuan']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <?= htmlspecialchars($r['tanggal_sewa']) ?><br>
                                            <span class="text-xs">s/d <?= htmlspecialchars($r['tanggal_kembali']) ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 font-semibold text-green-600"><?= format_rupiah($r['total_harga']) ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getStatusClass($r['status']) ?>">
                                                <?= ucfirst($r['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm aksi-column">
                                            <span class="text-gray-400">Read Only</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center print-hide">
                    <p class="text-gray-600 mb-4">Belum ada data Penyewaan Peralatan.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
    // ============================== JAVASCRIPT UNTUK TAB NAVIGATION ==============================
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tab-button');
        const contents = document.querySelectorAll('.tab-content');

        const activateTab = (tabId) => {
            // Reset semua tombol tab
            tabs.forEach(tab => {
                tab.classList.remove('border-blue-600', 'text-blue-600');
                tab.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            });
            // Sembunyikan semua konten
            contents.forEach(content => {
                content.classList.add('hidden');
            });

            // Aktifkan tombol yang diklik
            const activeTabButton = document.querySelector(`.tab-button[data-tab="${tabId}"]`);
            // Tampilkan konten yang sesuai
            const activeContent = document.getElementById(tabId);

            if (activeTabButton) {
                activeTabButton.classList.add('border-blue-600', 'text-blue-600');
                activeTabButton.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            }
            if (activeContent) {
                activeContent.classList.remove('hidden');
            }
        };

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                activateTab(tab.dataset.tab);
            });
        });

        // Aktifkan tab pertama (Master Lapangan) saat halaman dimuat
        activateTab('master_lapangan');
    });

    // ============================== JAVASCRIPT UNTUK FUNGSI PRINT ==============================
    function printTable(tableId, title) {
        const originalTitle = document.title;
        // Ambil elemen area cetak (yang membungkus tabel)
        const printableArea = document.getElementById(tableId).closest('.printable-area');
        if (!printableArea) {
            alert('Area cetak tidak ditemukan.');
            return;
        }

        const printContents = printableArea.outerHTML;

        // Buat konten HTML baru untuk jendela cetak
        const newHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { text-align: center; margin-bottom: 20px; font-size: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #000; padding: 6px; text-align: left; font-size: 10px; }
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
        const printWindow = window.open('', '_blank', 'height=600,width=800');
        printWindow.document.write(newHtml);
        printWindow.document.close();
        printWindow.print();

        // Mengembalikan judul halaman setelah mencetak dan menutup jendela cetak
        printWindow.onafterprint = function() {
            printWindow.close();
            document.title = originalTitle;
        };
    }
</script>

<?php include "includes/footer.php"; ?>