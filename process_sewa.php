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

// Handle GET actions for payment and cancellation
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_id = $_SESSION['user']['id'];

    if ($action === 'cancel' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $q = mysqli_query($conn, "UPDATE sewa_peralatan_detail SET status='cancelled' WHERE id=$id AND user_id=$user_id");
        if ($q) {
            header("Location: dashboard.php?msg=" . urlencode("Penyewaan berhasil dibatalkan."));
        } else {
            header("Location: dashboard.php?msg=" . urlencode("Gagal membatalkan penyewaan."));
        }
        exit;
    } elseif ($action === 'pay' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $q = mysqli_query($conn, "UPDATE sewa_peralatan_detail SET status='confirmed' WHERE id=$id AND user_id=$user_id");
        if ($q) {
            header("Location: dashboard.php?msg=" . urlencode("Penyewaan berhasil dikonfirmasi!"));
        } else {
            header("Location: dashboard.php?msg=" . urlencode("Gagal mengonfirmasi penyewaan."));
        }
        exit;
    } elseif ($action === 'pay_all') {
        $q = mysqli_query($conn, "UPDATE sewa_peralatan_detail SET status='confirmed' WHERE user_id=$user_id AND status='pending'");
        if ($q) {
            header("Location: dashboard.php?msg=" . urlencode("Semua penyewaan pending berhasil dikonfirmasi!"));
        } else {
            header("Location: dashboard.php?msg=" . urlencode("Gagal mengonfirmasi penyewaan."));
        }
        exit;
    } else {
        header("Location: dashboard.php?msg=" . urlencode("Aksi tidak dikenali."));
        exit;
    }
}

// Cek jika request adalah POST dan tombol 'sewa' ditekan
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sewa'])) {
    // Ambil data dari form
    $user_id        = $_SESSION['user']['id']; // <<< BARIS 10 (Sudah Diperbaiki)
    $booking_id     = $_POST['booking_id'] ?? null;
    $peralatan_id   = $_POST['peralatan_id'] ?? null;
    $quantity       = $_POST['quantity'] ?? null;
    $harga_satuan   = $_POST['harga_satuan'] ?? null;
    // Tanggal diisi secara tersembunyi (hidden) dari tanggal booking yang dipilih
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

    // --- Perhitungan Hari Sewa ---
    try {
        // Cek apakah tanggal sewa dan kembali valid secara format dan bukan tanggal yang sudah lewat
        $tgl_sewa = new DateTime($tanggal_sewa);
        $tgl_kembali = new DateTime($tanggal_kembali);
        $today = new DateTime(date('Y-m-d'));

        if ($tgl_sewa < $today) {
            header('Location: sewa.php?error=' . urlencode("Tanggal sewa tidak boleh sebelum hari ini. Pastikan booking lapangan valid."));
            exit;
        }

        // Karena sewa terikat pada booking lapangan, kita asumsikan lama sewa adalah 1 hari/sesi booking.
        $rent_days = 1;

        // Pastikan tanggal sewa sama dengan tanggal kembali untuk 1 sesi booking
        if ($tgl_sewa->format('Y-m-d') !== $tgl_kembali->format('Y-m-d')) {
            header('Location: sewa.php?error=' . urlencode("Kesalahan sistem: Tanggal sewa dan kembali harus sama dengan tanggal booking lapangan."));
            exit;
        }

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
        // Redirect ke dashboard setelah berhasil POST
        header('Location: dashboard.php?msg=' . urlencode("Penyewaan berhasil dibuat! Silakan tunggu konfirmasi admin."));
        exit;
    } else {
        header('Location: dashboard.php?error=' . urlencode("Gagal membuat penyewaan. Error database."));
        exit;
    }

} else {
    // Jika akses langsung ke file ini tanpa POST
    header('Location: sewa.php');
    exit;
}
?>