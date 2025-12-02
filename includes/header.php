<?php
// Pastikan session_start() sudah dipanggil di file yang memuat kode ini
// session_start(); 

// --- Logika Penentuan Dashboard Link ---
$dashboard_link = 'dashboard.php';
$dashboard_text = 'Dashboard';
$user_role = $_SESSION['user']['role'] ?? 'guest'; // Asumsi: Role tersimpan di $_SESSION['user']['role']

// Cek apakah user adalah Admin
if ($user_role === 'admin') {
    $dashboard_link = 'dashboard_admin.php';
    // Opsional: Anda bisa mengubah teks tampilan
    // $dashboard_text = 'Admin Panel'; 
}
// ----------------------------------------

?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Futsal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<nav class="sticky top-0 z-50 bg-white shadow-md">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">

            <div class="flex-shrink-0 text-2xl font-bold text-blue-600">
                FUTSAL
            </div>

            <div class="hidden md:flex space-x-6">
                <a href="index.php" class="text-gray-700 hover:text-blue-600">Beranda</a>
                <a href="lapangan.php" class="text-gray-700 hover:text-blue-600">Lapangan</a>
                <a href="sewa.php" class="text-gray-700 hover:text-blue-600">Sewa</a>
                
                <?php if (isset($_SESSION['user']) && !empty($_SESSION['user'])): ?>
                    <a href="<?= $dashboard_link ?>" class="text-gray-700 hover:text-blue-600">
                        <?= $dashboard_text ?>
                    </a>
                <?php endif; ?>
                </div>

            <div class="hidden md:block">
                <?php if (isset($_SESSION['user']) && !empty($_SESSION['user'])): ?>
                    <div class="flex items-center space-x-2 px-3 py-2 bg-gray-50 rounded-lg">
                        <?php 
                            $user_name = isset($_SESSION['user']['name']) && !empty($_SESSION['user']['name']) 
                                ? $_SESSION['user']['name'] 
                                : (isset($_SESSION['user']['email']) ? substr($_SESSION['user']['email'], 0, strpos($_SESSION['user']['email'], '@')) : 'User');

                            // Ambil inisial huruf pertama dari nama depan
                            $initial = '';
                            if (!empty($user_name)) {
                                $parts = preg_split('/\s+/', trim($user_name));
                                $first = $parts[0] ?? $user_name;
                                $initial = mb_strtoupper(mb_substr($first, 0, 1, 'UTF-8'), 'UTF-8');
                            }
                            
                            // Tambahkan indikator Admin di profil
                            $role_indicator = ($user_role === 'admin') ? '<span class="text-xs text-red-500 font-bold ml-1">(Admin)</span>' : '';
                        ?>
                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                            <?= htmlspecialchars($initial) ?>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-gray-700">
                                <?= htmlspecialchars($user_name) ?>
                                <?= $role_indicator ?>
                            </span>
                            <a href="logout.php" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php"
                       class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>