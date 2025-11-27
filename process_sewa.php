<?php
session_start();
include "includes/config.php";

require_once __DIR__ . '/function/peralatan.php';
require_once __DIR__ . '/function/auth.php';

// Cek apakah user sudah login
if (!is_logged_in()) {
    header('Location: login.php?error=' . urlencode("Silakan login untuk memproses penyewaan."));
    exit;
}

// Cek jika request adalah POST dan tombol 'sewa' ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sewa'])) {
    // Ambil data dari form
    $user_id        = $_SESSION['user']['id'];
    $booking_id     = $_POST['booking_id'] ?? null;
    $peralatan_id   = $_POST['peralatan_id'] ?? null;
    $quantity       = $_POST['quantity'] ?? null;
    $harga_satuan   = $_POST['harga_satuan'] ?? null;
    $tanggal_sewa   = $_POST['tanggal_sewa'] ?? null;
    $tanggal_kembali= $_POST['tanggal_kembali'] ?? null;

    // --- Validasi Dasar ---
    if (!$booking_id || !$peralatan_id || !$quantity || !$harga_satuan || !$tanggal_sewa || !$tanggal_kembali) {
        header('Location: sewa.php?error=' . urlencode("Semua kolom harus diisi dengan benar."));
        exit;
    }

    $quantity = intval($quantity);
    $harga_satuan = intval($harga_satuan);

    // Cek stok peralatan
    $stok_tersedia = get_stok_peralatan($conn, $peralatan_id);
    if ($quantity <= 0 || $quantity > $stok_tersedia) {
        header('Location: sewa.php?error=' . urlencode("Jumlah sewa tidak valid atau melebihi stok tersedia (Stok: $stok_tersedia)."));
        exit;
    }

    // Cek tanggal
    try {
        $tgl_sewa = new DateTime($tanggal_sewa);
        $tgl_kembali = new DateTime($tanggal_kembali);
        $today = new DateTime(date('Y-m-d'));

        if ($tgl_sewa > $tgl_kembali) {
            header('Location: sewa.php?error=' . urlencode("Tanggal kembali tidak boleh sebelum tanggal sewa."));
            exit;
        }
        if ($tgl_sewa < $today) {
            header('Location: sewa.php?error=' . urlencode("Tanggal sewa tidak boleh sebelum hari ini."));
            exit;
        }

        // Hitung jumlah hari sewa
        $interval = $tgl_sewa->diff($tgl_kembali);
        $rent_days = $interval->days + 1;

    } catch (Exception $e) {
        header('Location: sewa.php?error=' . urlencode("Format tanggal tidak valid."));
        exit;
    }

    // Hitung total harga
    $total_harga = $quantity * $harga_satuan * $rent_days;

    // --- Panggil Fungsi Create Rental ---
    $result = create_rental(
        $conn,
        $user_id,
        $booking_id,
        $peralatan_id,
        $quantity,
        $tanggal_sewa,
        $tanggal_kembali,
        $total_harga
    );

    if ($result) {
        // Redirect ke halaman daftar sewa setelah berhasil
        header('Location: my_rentals.php?msg=' . urlencode("Penyewaan berhasil dibuat! Silakan tunggu konfirmasi admin."));
        exit;
    } else {
        header('Location: sewa.php?error=' . urlencode("Gagal membuat penyewaan. Error database."));
        exit;
    }

} else {
    // Jika akses langsung ke file ini tanpa POST
    header('Location: sewa.php');
    exit;
}
?>