<?php
// =====================================
// FUNGSI SEWA PERALATAN FUTSAL
// =====================================

// ---------------------------
// Ambil semua peralatan (Dari tabel Master: sewa_peralatan)
// ---------------------------
function get_all_peralatan($conn) {
    $sql = "SELECT * FROM sewa_peralatan ORDER BY nama_peralatan ASC";
    $res = mysqli_query($conn, $sql);
    $out = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $out[] = $r;
        }
    }
    return $out;
}

// ---------------------------
// Ambil satu peralatan (Dari tabel Master: sewa_peralatan)
// ---------------------------
function get_peralatan($conn, $id) {
    $id = intval($id);
    $sql = "SELECT * FROM sewa_peralatan WHERE id=$id LIMIT 1";
    $res = mysqli_query($conn, $sql);
    return $res ? mysqli_fetch_assoc($res) : null;
}

// ---------------------------
// Ambil stok (Dari tabel Master: sewa_peralatan)
// ---------------------------
function get_stok_peralatan($conn, $id) {
    $p = get_peralatan($conn, $id);
    return $p ? intval($p['stok']) : 0;
}

// ---------------------------
// Kurangi stok setelah sewa (Update tabel Master: sewa_peralatan)
// ---------------------------
function reduce_stok($conn, $id, $qty) {
    $id = intval($id);
    $qty = intval($qty);
    // GREATEST(0, ...) mencegah stok menjadi negatif jika ada bug lain
    return mysqli_query($conn, "UPDATE sewa_peralatan SET stok = GREATEST(0, stok - $qty) WHERE id=$id");
}

// ---------------------------
// Buat penyewaan (Insert ke tabel Detail Transaksi: sewa_peralatan_detail)
// ---------------------------
function create_rental($conn, $user_id, $booking_id, $peralatan_id, $quantity, $tanggal_sewa, $tanggal_kembali, $total_harga) {
    $user_id = intval($user_id);
    $booking_id = intval($booking_id);
    $peralatan_id = intval($peralatan_id);
    $quantity = intval($quantity);
    $tanggal_sewa = mysqli_real_escape_string($conn, $tanggal_sewa);
    $tanggal_kembali = mysqli_real_escape_string($conn, $tanggal_kembali);
    $total = intval($total_harga);

    // Query INSERT menargetkan tabel sewa_peralatan_detail
    $sql = "INSERT INTO sewa_peralatan_detail 
            (user_id, booking_id, peralatan_id, quantity, tanggal_sewa, tanggal_kembali, total_harga, status) 
            VALUES ($user_id, $booking_id, $peralatan_id, $quantity, '$tanggal_sewa', '$tanggal_kembali', $total, 'pending')";

    // Jika insert berhasil, kurangi stok di tabel master
    if (mysqli_query($conn, $sql)) {
        reduce_stok($conn, $peralatan_id, $quantity);
        return true;
    }

    return false;
}

// ---------------------------
// Ambil daftar sewa user (Untuk my_rentals.php)
// ---------------------------
// ... (Bagian atas kode lainnya)

// ---------------------------
// Ambil daftar sewa user (Untuk my_rentals.php)
// ---------------------------
function get_my_rentals($conn, $user_id) {
    $user_id = intval($user_id);
    
    // FIX: Menggunakan kolom tanggal_transaksi (bukan created_at)
    $sql = "SELECT 
                spd.id AS rental_id, 
                sp.nama_peralatan, 
                spd.quantity, 
                spd.total_harga, 
                spd.status, 
                spd.tanggal_sewa, 
                spd.tanggal_kembali, 
                spd.tanggal_transaksi, 
                spd.booking_id
            FROM 
                sewa_peralatan_detail spd
            JOIN 
                sewa_peralatan sp ON spd.peralatan_id = sp.id
            WHERE 
                spd.user_id = $user_id
            ORDER BY 
                spd.tanggal_transaksi DESC"; // FIX PENGURUTAN

    $res = mysqli_query($conn, $sql);
    $my_rentals = [];

    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $my_rentals[] = $r;
        }
    }
    return $my_rentals;
}

?>