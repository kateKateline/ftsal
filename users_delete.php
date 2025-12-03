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

// Prevent deleting yourself
if ($id == current_user_id()) {
    header('Location: dashboard_admin.php?error=Anda tidak bisa menghapus akun sendiri', true, 303);
    exit;
}

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: dashboard_admin.php?success=User berhasil dihapus', true, 303);
} else {
    header('Location: dashboard_admin.php?error=Gagal menghapus user', true, 303);
}
exit;
