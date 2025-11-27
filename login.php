<?php
session_start();
include "includes/config.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");
    $user  = mysqli_fetch_assoc($query);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user'] = $user;

        if ($user['role'] == 'admin') {
            header("Location: dashboard_admin.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Email atau password salah";
    }
}
?>

<?php include "includes/header.php"; ?>

<main class="min-h-screen flex items-center justify-center p-6">

    <div class="bg-white/95 backdrop-blur-sm p-8 lg:p-10 rounded-3xl shadow-2xl w-full max-w-md border border-gray-200">

        <div class="flex items-center justify-center mb-4">
            <div class="w-14 h-14 bg-blue-600 rounded-full flex items-center justify-center text-white text-xl font-bold">F</div>
        </div>

        <h2 class="text-2xl lg:text-3xl font-extrabold mb-4 text-center text-gray-800">Selamat Datang</h2>
        <p class="text-center text-sm text-gray-500 mb-6">Masuk untuk melanjutkan pemesanan lapangan futsal</p>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 mb-4 rounded-lg text-center font-medium shadow">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">

            <div>
                <label class="font-medium text-sm text-gray-700">Email</label>
                <div class="flex items-center border rounded-lg p-2 bg-gray-50 focus-within:ring-2 focus-within:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m8 4H8m8-8H8m-2 0a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H8a2 2 0 01-2-2V6z"/>
                    </svg>
                    <input type="email" name="email" required autocomplete="email" placeholder="name@contoh.com"
                           class="w-full bg-transparent outline-none ml-2 text-sm">
                </div>
            </div>

            <div>
                <label class="font-medium text-sm text-gray-700">Password</label>
                <div class="flex items-center border rounded-lg p-2 bg-gray-50 focus-within:ring-2 focus-within:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.1.9-2 2-2s2 .9 2 2v4c0 1.1-.9 2-2 2s-2-.9-2-2v-4zm-6 0c0-1.1.9-2 2-2s2 .9 2 2v4c0 1.1-.9 2-2 2s-2-.9-2-2v-4zm6-7a7 7 0 017 7v4a7 7 0 01-14 0v-4a7 7 0 017-7z"/>
                    </svg>
                    <input type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password"
                           class="w-full bg-transparent outline-none ml-2 text-sm">
                </div>
            </div>

            <div class="flex items-center justify-between text-sm">
                <a href="#" class="text-blue-600 hover:underline">Lupa password?</a>
                <a href="#" class="text-gray-500">Belum punya akun?</a>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded-lg text-base font-semibold hover:bg-blue-700 transition shadow-md">
                Masuk
            </button>
        </form>

    </div>

</main>

<?php include "includes/footer.php"; ?>
