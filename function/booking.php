<?php
// Booking related helpers

// Returns array of bookings (each with tanggal, jam_mulai, jam_selesai) between $from and $to for lapangan
function get_bookings_for_lapangan($conn, $lapangan_id, $from = null, $to = null) {
    $lapangan_id = intval($lapangan_id);
    $from = $from ? mysqli_real_escape_string($conn, $from) : date('Y-m-d');
    $to = $to ? mysqli_real_escape_string($conn, $to) : date('Y-m-d', strtotime('+7 days'));

    $sql = "SELECT tanggal, jam_mulai, jam_selesai FROM booking WHERE lapangan_id=$lapangan_id AND tanggal BETWEEN '$from' AND '$to' AND status != 'canceled' ORDER BY tanggal, jam_mulai";
    $res = mysqli_query($conn, $sql);
    $out = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $out[] = $r;
        }
    }
    return $out;
}

// Check whether given time range conflicts with existing bookings
// Returns array with keys: conflict => bool, rows => array of conflicting rows
function check_booking_conflict($conn, $lapangan_id, $tanggal, $jam_mulai, $jam_selesai, $excludeId = null) {
    $lapangan_id = intval($lapangan_id);
    $tanggal = mysqli_real_escape_string($conn, $tanggal);
    $jm = mysqli_real_escape_string($conn, $jam_mulai);
    $js = mysqli_real_escape_string($conn, $jam_selesai);

    $excludeSql = '';
    if ($excludeId) {
        $excludeSql = ' AND id != ' . intval($excludeId);
    }

    $sql = "SELECT * FROM booking WHERE lapangan_id=$lapangan_id AND tanggal='$tanggal' AND status!='canceled' AND NOT (jam_selesai <= '$jm' OR jam_mulai >= '$js')" . $excludeSql;
    $res = mysqli_query($conn, $sql);
    $conflicts = [];
    if ($res && mysqli_num_rows($res) > 0) {
        while ($r = mysqli_fetch_assoc($res)) $conflicts[] = $r;
    }
    return ['conflict' => count($conflicts) > 0, 'rows' => $conflicts];
}

function create_booking($conn, $user_id, $lapangan_id, $tanggal, $jam_mulai, $jam_selesai, $total_harga) {
    $user_id = intval($user_id);
    $lapangan_id = intval($lapangan_id);
    $tanggal = mysqli_real_escape_string($conn, $tanggal);
    $jm = mysqli_real_escape_string($conn, $jam_mulai);
    $js = mysqli_real_escape_string($conn, $jam_selesai);
    $total = intval($total_harga);

    $sql = "INSERT INTO booking (user_id, lapangan_id, tanggal, jam_mulai, jam_selesai, total_harga, status) VALUES ($user_id, $lapangan_id, '$tanggal', '$jm', '$js', $total, 'pending')";
    return mysqli_query($conn, $sql);
}
