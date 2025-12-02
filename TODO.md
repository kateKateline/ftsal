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

// handle cancel via GET
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // verify ownership
    $q = mysqli_query($conn, "SELECT * FROM booking WHERE id=$id AND user_id=$user_id LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        mysqli_query($conn, "UPDATE booking SET status='canceled' WHERE id=$id");
        header('Location: my_bookings.php?msg=' . urlencode('Booking dibatalkan'));
        exit;
    } else {
        header('Location: my_bookings.php?msg=' . urlencode('Booking tidak ditemukan'));
        exit;
    }
}

// handle booking POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            header('Location: lapangan_detail.php?id=' . $lapangan_id . '&msg=' . urlencode('Durasi tidak valid'));
            exit;
        }
        $jam_selesai_time = strtotime($jam_mulai) + ($durasi * 3600);
        $jam_selesai = date('H:i:s', $jam_selesai_time);
    }

    // basic validation
    if (!$lapangan_id || !$tanggal || !$jam_mulai || !$jam_selesai) {
        header('Location: lapangan_detail.php?id=' . $lapangan_id . '&msg=' . urlencode('Input tidak lengkap'));
        exit;
    }

    // ensure jam_selesai > jam_mulai
    if (strtotime($jam_selesai) <= strtotime($jam_mulai)) {
        header('Location: lapangan_detail.php?id=' . $lapangan_id . '&msg=' . urlencode('Jam selesai harus setelah jam mulai'));
        exit;
    }

    // fetch lapangan price
    $r = mysqli_query($conn, "SELECT * FROM lapangan WHERE id=$lapangan_id LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) {
        header('Location: lapangan.php?msg=' . urlencode('Lapangan tidak ditemukan'));
        exit;
    }
    $lap = mysqli_fetch_assoc($r);
    $harga = intval($lap['harga_per_jam']);

    // check overlap using booking helper
    $conf = check_booking_conflict($conn, $lapangan_id, $tanggal, $jam_mulai, $jam_selesai);
    if ($conf['conflict']) {
        header('Location: lapangan_detail.php?id=' . $lapangan_id . '&msg=' . urlencode('Waktu sudah dipesan, pilih slot lain'));
        exit;
    }

    // compute duration in hours (can be fractional)
    $t1 = strtotime($jam_mulai);
    $t2 = strtotime($jam_selesai);
    $hours = ($t2 - $t1) / 3600;
    if ($hours <= 0) {
        header('Location: lapangan_detail.php?id=' . $lapangan_id . '&msg=' . urlencode('Durasi tidak valid'));
        exit;
    }

    $total_harga = intval(round($hours * $harga));

    // insert booking via helper
    $ok = create_booking($conn, $user_id, $lapangan_id, $tanggal, $jam_mulai, $jam_selesai, $total_harga);
    if ($ok) {
        header('Location: dashboard.php?msg=' . urlencode('Booking berhasil dibuat'));
        exit;
    } else {
        header('Location: dashboard.php?id=' . $lapangan_id . '&msg=' . urlencode('Gagal membuat booking'));
        exit;
    }
}

// default redirect
header('Location: lapangan.php');
exit;
