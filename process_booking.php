<?php
session_start();
include "includes/config.php";

// function helpers
require_once __DIR__ . '/function/auth.php';
require_once __DIR__ . '/function/helpers.php';
require_once __DIR__ . '/function/booking.php';

// Ensure user logged in
require_login();
// get current user id
$user_id = current_user_id();

// --- HANDLE GET ACTIONS (Cancel and Pay) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // Cek kepemilikan booking
    $q = mysqli_query($conn, "SELECT * FROM booking WHERE id=$id AND user_id=$user_id LIMIT 1");
    
    if (!$q || mysqli_num_rows($q) === 0) {
        header('Location: dashboard.php?msg=' . urlencode('Booking tidak ditemukan atau bukan milik Anda'));
        exit;
    }
    
    $booking = mysqli_fetch_assoc($q);
    $current_status = $booking['status'];

    if ($action === 'cancel') {
        if ($current_status === 'pending') {
            // Hanya bisa membatalkan jika status masih pending
            $update = mysqli_query($conn, "UPDATE booking SET status='canceled' WHERE id=$id");
            if ($update) {
                header('Location: dashboard.php?msg=' . urlencode('Booking dibatalkan'));
            } else {
                header('Location: dashboard.php?msg=' . urlencode('Gagal membatalkan booking'));
            }
        } else {
            header('Location: dashboard.php?msg=' . urlencode('Booking sudah diproses atau dibatalkan, tidak bisa dibatalkan lagi.'));
        }
        exit;

    } elseif ($action === 'pay') {
        if ($current_status === 'pending') {
            // Logika Pembayaran: Ubah status dari pending menjadi confirmed
            $update = mysqli_query($conn, "UPDATE booking SET status='confirmed' WHERE id=$id");
            if ($update) {
                header('Location: dashboard.php?msg=' . urlencode('Pembayaran berhasil! Booking Anda sudah dikonfirmasi.'));
            } else {
                header('Location: dashboard.php?msg=' . urlencode('Gagal mengkonfirmasi pembayaran booking.'));
            }
        } else if ($current_status === 'confirmed') {
             header('Location: dashboard.php?msg=' . urlencode('Booking ini sudah dibayar/dikonfirmasi.'));
        } else {
            header('Location: dashboard.php?msg=' . urlencode('Status booking tidak bisa diubah menjadi dibayar.'));
        }
        exit;
    }
}


// --- HANDLE BOOKING POST (Create New Booking) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pastikan tidak ada karakter spasi non-breaking
    $lapangan_id = intval($_POST['lapangan_id']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam_mulai_input = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
    $durasi = isset($_POST['durasi']) ? floatval($_POST['durasi']) : 0;
    
    // Normalisasi jam_mulai ke format HH:MM:SS
    $jam_mulai = $jam_mulai_input;
    if (strlen($jam_mulai) === 5) { // Format HH:MM
        $jam_mulai .= ':00';
    }
    
    // jika ada jam_selesai (dari lapangan.php old format), gunakan itu
    if (isset($_POST['jam_selesai']) && !empty($_POST['jam_selesai'])) {
        $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai']);
    } else {
        // hitung jam_selesai dari durasi
        if ($durasi <= 0) {
            header('Location: lapangan_detail.php?id=' . $lapangan_id . '&error=' . urlencode('Durasi tidak valid'));
            exit;
        }
        $jam_selesai_time = strtotime($jam_mulai) + ($durasi * 3600);
        $jam_selesai = date('H:i:s', $jam_selesai_time);
    }

    // basic validation
    if (!$lapangan_id || !$tanggal || !$jam_mulai || !$jam_selesai) {
        header('Location: lapangan_detail.php?id=' . $lapangan_id . '&error=' . urlencode('Input tidak lengkap'));
        exit;
    }

    // ensure jam_selesai > jam_mulai
    if (strtotime($jam_selesai) <= strtotime($jam_mulai)) {
        header('Location: lapangan_detail.php?id=' . $lapangan_id . '&error=' . urlencode('Jam selesai harus setelah jam mulai'));
        exit;
    }

    // fetch lapangan price
    $r = mysqli_query($conn, "SELECT * FROM lapangan WHERE id=$lapangan_id LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) {
        header('Location: lapangan.php?error=' . urlencode('Lapangan tidak ditemukan'));
        exit;
    }
    $lap = mysqli_fetch_assoc($r);
    $harga = intval($lap['harga_per_jam']);

    // check overlap using booking helper (Termasuk booking 'pending' dan 'confirmed')
    $conf = check_booking_conflict($conn, $lapangan_id, $tanggal, $jam_mulai, $jam_selesai);
    if ($conf['conflict']) {
        header('Location: lapangan_detail.php?id=' . $lapangan_id . '&error=' . urlencode('Waktu sudah dipesan, pilih slot lain'));
        exit;
    }

    // compute duration in hours (can be fractional)
    $t1 = strtotime($jam_mulai);
    $t2 = strtotime($jam_selesai);
    $hours = ($t2 - $t1) / 3600;
    if ($hours <= 0) {
        header('Location: lapangan_detail.php?id=' . $lapangan_id . '&error=' . urlencode('Durasi tidak valid'));
        exit;
    }

    $total_harga = intval(round($hours * $harga));

    // insert booking via helper DENGAN STATUS PENDING
    $ok = create_booking($conn, $user_id, $lapangan_id, $tanggal, $jam_mulai, $jam_selesai, $total_harga);
    
    if ($ok) {
        // Redirect ke dashboard untuk user membayar
        header('Location: dashboard.php?msg=' . urlencode('Booking berhasil dibuat! Silakan lakukan pembayaran.'));
        exit;
    } else {
        header('Location: lapangan_detail.php?id=' . $lapangan_id . '&error=' . urlencode('Gagal membuat booking'));
        exit;
    }
}

// default redirect
header('Location: lapangan.php');
exit;
?>