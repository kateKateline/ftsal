<?php 
session_start();
include "includes/config.php";
include "includes/header.php"; 
?>

<!-- HERO / INTRO -->
<section class="max-w-6xl mx-auto p-8 text-center">

    <h1 class="text-5xl font-bold text-blue-600 mb-4">
        Selamat Datang di Booking Futsal
    </h1>

    <p class="text-gray-700 text-lg mb-6">
        Temukan dan booking lapangan futsal favoritmu dengan mudah dan cepat.
        Tidak perlu antre, cukup pilih jadwal, bayar di tempat, dan langsung main!
    </p>

    <a href="login.php" 
       class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
       Mulai Booking
    </a>

</section>

<!-- Section Lapangan -->
<section id="lapangan" class="max-w-6xl mx-auto p-8">

    <h2 class="text-3xl font-bold mb-4">Kenapa Harus Booking Disini?</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="bg-white p-6 shadow rounded">
            <h3 class="text-xl font-bold mb-2">Mudah & Cepat</h3>
            <p class="text-gray-600">Proses booking hanya beberapa klik.</p>
        </div>

        <div class="bg-white p-6 shadow rounded">
            <h3 class="text-xl font-bold mb-2">Jadwal Lengkap</h3>
            <p class="text-gray-600">Pilih waktu kosong yang kamu inginkan.</p>
        </div>

        <div class="bg-white p-6 shadow rounded">
            <h3 class="text-xl font-bold mb-2">Tempat Terpercaya</h3>
            <p class="text-gray-600">Lapangan futsal berkualitas dan nyaman.</p>
        </div>

    </div>
</section>

<!-- Kontak -->
<section id="kontak" class="max-w-6xl mx-auto p-8 text-center">
    <h2 class="text-3xl font-bold mb-4">Kontak Kami</h2>
    <p class="text-gray-700">Hubungi admin di WhatsApp: <b>0812-3456-7890</b></p>
</section>

<?php include "includes/footer.php"; ?>
