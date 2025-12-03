<?php
session_start();
require_once "includes/config.php";
require_once "function/auth.php";

require_login();

if (current_user()['role'] !== 'admin') {
    echo "<h3>Access denied.</h3>";
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: dashboard_admin.php');
    exit;
}

// Delete booking
$stmt = $conn->prepare("DELETE FROM booking WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: dashboard_admin.php?success=Booking berhasil dihapus', true, 303);
} else {
    header('Location: dashboard_admin.php?error=Gagal menghapus booking', true, 303);
}
exit;
