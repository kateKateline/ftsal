<?php
// Helper utilities reused across pages

function format_rupiah($value) {
    return 'Rp ' . number_format($value, 0, ',', '.');
}

function friendly_date_id($date) {
    // expects YYYY-MM-DD
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj) return $date;
    $months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $day = $dateObj->format('j');
    $monthName = $months[intval($dateObj->format('n')) - 1];
    return $day . ' ' . $monthName;
}

function time_to_minutes($timeStr) {
    // expects HH:MM or HH:MM:SS
    $parts = explode(':', $timeStr);
    $h = intval($parts[0]);
    $m = intval($parts[1] ?? 0);
    return $h * 60 + $m;
}

function minutes_to_time($minutes) {
    $h = floor($minutes / 60);
    $m = $minutes % 60;
    return sprintf('%02d:%02d:00', $h, $m);
}
