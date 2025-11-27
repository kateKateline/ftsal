<?php
session_start();
include "includes/config.php";
include "includes/header.php";

$lapangan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$lapangan_id) {
    header('Location: lapangan.php');
    exit;
}

// fetch lapangan detail
$q = mysqli_query($conn, "SELECT * FROM lapangan WHERE id=$lapangan_id LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    header('Location: lapangan.php');
    exit;
}
$lapangan = mysqli_fetch_assoc($q);

// include helpers and booking functions
require_once __DIR__ . '/function/helpers.php';
require_once __DIR__ . '/function/booking.php';

// fetch booking untuk lapangan ini (7 hari ke depan)
$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$bookings = get_bookings_for_lapangan($conn, $lapangan_id, $today, $nextWeek);

?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4">

        <!-- Breadcrumb -->
        <div class="mb-6">
            <a href="lapangan.php" class="text-blue-600 hover:underline">&larr; Kembali ke Daftar Lapangan</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Gambar -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                    <?php if (!empty($lapangan['gambar']) && file_exists('assets/images/' . $lapangan['gambar'])): ?>
                        <img src="assets/images/<?= htmlspecialchars($lapangan['gambar']) ?>" alt="<?= htmlspecialchars($lapangan['nama']) ?>" class="w-full h-96 object-cover">
                    <?php else: ?>
                        <div class="w-full h-96 bg-gray-200 flex items-center justify-center text-gray-400">
                            <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info Lapangan -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="mb-4">
                        <h1 class="text-3xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($lapangan['nama']) ?></h1>
                        <p class="text-2xl text-blue-600 font-semibold">Rp <?= number_format($lapangan['harga_per_jam'],0,',','.') ?>/jam</p>
                    </div>

                    <div class="mb-4">
                        <span class="inline-block px-4 py-2 rounded-full <?= $lapangan['status']=='ready' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                            Status: <?= htmlspecialchars($lapangan['status']) ?>
                        </span>
                    </div>

                    <!-- Deskripsi -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Deskripsi</h3>
                        <p class="text-gray-600 leading-relaxed">
                            <?= htmlspecialchars($lapangan['deskripsi'] ?? 'Lapangan futsal berkualitas dengan kondisi prima.') ?>
                        </p>
                    </div>

                    <!-- Fasilitas -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Fasilitas</h3>
                        <div class="text-gray-600">
                            <?php 
                                $fasilitas = $lapangan['fasilitas'] ?? 'Lapangan - Tempat Parkir - Toilet - Kantin';
                                $items = array_map('trim', explode(',', $fasilitas));
                            ?>
                            <ul class="space-y-2">
                                <?php foreach ($items as $item): ?>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?= htmlspecialchars($item) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Jadwal Terisi -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Jadwal Terisi (7 Hari Ke Depan)</h3>
                        <?php if (count($bookings) > 0): ?>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                <?php $priority = 1; foreach ($bookings as $b): ?>
                                    <div class="bg-gradient-to-r from-red-50 to-orange-50 p-3 rounded border-l-4 border-red-500 relative" data-priority="<?= $priority ?>">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars(friendly_date_id($b['tanggal'])) ?></p>
                                                <p class="text-sm text-gray-600">Jam <?= htmlspecialchars(substr($b['jam_mulai'],0,5)) ?> - <?= htmlspecialchars(substr($b['jam_selesai'],0,5)) ?></p>
                                            </div>
                                            <div class="ml-2">
                                                <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 rounded-full"><?= $priority ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $priority++; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm">Tidak ada jadwal terisi untuk 7 hari ke depan</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar Booking -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-xl font-bold mb-4">Pesan Lapangan</h3>

                    <?php if (!isset($_SESSION['user'])): ?>
                        <div class="bg-blue-50 p-4 rounded-lg mb-4 text-center">
                            <p class="text-sm text-gray-600 mb-3">Silakan login untuk melakukan booking</p>
                            <a href="login.php" class="block w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Login</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="process_booking.php" class="space-y-3" id="bookingForm">
                            <input type="hidden" name="lapangan_id" value="<?= $lapangan_id ?>">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                <input type="date" name="tanggal" id="tanggal" required min="<?= date('Y-m-d') ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jam Mulai</label>
                                <input type="time" name="jam_mulai" id="jam_mulai" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="09:00">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Durasi</label>
                                <select name="durasi" id="durasi" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">-- Pilih Durasi --</option>
                                    <option value="1">1 Jam</option>
                                    <option value="1.5">1.5 Jam</option>
                                    <option value="2">2 Jam</option>
                                    <option value="2.5">2.5 Jam</option>
                                    <option value="3">3 Jam</option>
                                    <option value="4">4 Jam</option>
                                    <option value="5">5 Jam</option>
                                </select>
                            </div>

                            <div id="conflictAlert" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                ⚠️ <strong>Waktu tidak tersedia!</strong><br>
                                <span id="conflictMessage">Sudah ada booking pada jam tersebut. Silakan pilih waktu lain.</span>
                            </div>

                            <div class="bg-gray-50 p-3 rounded text-sm">
                                <p class="text-gray-600">Total: <span class="font-bold text-blue-600">Rp <span id="total">0</span></span></p>
                            </div>

                            <button type="submit" id="submitBtn" class="w-full bg-green-600 text-white py-3 rounded hover:bg-green-700 transition font-semibold">Pesan Sekarang</button>
                        </form>

                        <script>
                            const bookings = <?= json_encode($bookings) ?>;
                            const hargaPerJam = <?= intval($lapangan['harga_per_jam']) ?>;
                            const tanggalInput = document.getElementById('tanggal');
                            const jamMulaiInput = document.getElementById('jam_mulai');
                            const durasiSelect = document.getElementById('durasi');
                            const totalSpan = document.getElementById('total');
                            const conflictAlert = document.getElementById('conflictAlert');
                            const submitBtn = document.getElementById('submitBtn');
                            const bookingForm = document.getElementById('bookingForm');

                            function timeToMinutes(timeStr) {
                                const [hours, minutes] = timeStr.split(':').map(Number);
                                return hours * 60 + minutes;
                            }

                            function checkTimeConflict() {
                                const selectedDate = tanggalInput.value;
                                const selectedTime = jamMulaiInput.value;
                                const durasi = parseFloat(durasiSelect.value);

                                if (!selectedDate || !selectedTime || !durasi) {
                                    conflictAlert.classList.add('hidden');
                                    submitBtn.disabled = false;
                                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                                    return true;
                                }

                                const startMinutes = timeToMinutes(selectedTime);
                                const endMinutes = startMinutes + (durasi * 60);

                                // Cek konflik dengan booking yang ada dan track priority
                                let conflictingPriority = null;
                                const hasConflict = bookings.some((booking, index) => {
                                    if (booking.tanggal !== selectedDate) return false;
                                    
                                    const bookingStart = timeToMinutes(booking.jam_mulai);
                                    const bookingEnd = timeToMinutes(booking.jam_selesai);

                                    // Cek overlap: selected time tidak boleh tumpang tindih dengan booking
                                    const isConflict = !(endMinutes <= bookingStart || startMinutes >= bookingEnd);
                                    if (isConflict) {
                                        conflictingPriority = index + 1; // Priority dimulai dari 1
                                    }
                                    return isConflict;
                                });

                                if (hasConflict) {
                                    const conflictMsg = document.getElementById('conflictMessage');
                                    const conflictBooking = bookings.find(b => 
                                        b.tanggal === selectedDate && 
                                        timeToMinutes(b.jam_mulai) < endMinutes && 
                                        timeToMinutes(b.jam_selesai) > startMinutes
                                    );
                                    if (conflictBooking) {
                                        conflictMsg.innerHTML = `Bentrok dengan <strong>Booking #${conflictingPriority}</strong> (${conflictBooking.jam_mulai.substr(0,5)} - ${conflictBooking.jam_selesai.substr(0,5)}). Silakan pilih waktu lain.`;
                                    }
                                    conflictAlert.classList.remove('hidden');
                                    submitBtn.disabled = true;
                                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                                    return false;
                                } else {
                                    conflictAlert.classList.add('hidden');
                                    submitBtn.disabled = false;
                                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                                    return true;
                                }
                            }

                            function updateTotal() {
                                const durasi = parseFloat(durasiSelect.value);
                                if (durasi) {
                                    const total = Math.round(hargaPerJam * durasi);
                                    totalSpan.textContent = total.toLocaleString('id-ID');
                                } else {
                                    totalSpan.textContent = '0';
                                }
                                checkTimeConflict();
                            }

                            // Event listeners
                            tanggalInput.addEventListener('change', checkTimeConflict);
                            jamMulaiInput.addEventListener('change', checkTimeConflict);
                            durasiSelect.addEventListener('change', updateTotal);

                            // Cek saat form submit
                            bookingForm.addEventListener('submit', function(e) {
                                if (!checkTimeConflict()) {
                                    e.preventDefault();
                                    alert('❌ Waktu yang Anda pilih sudah dibooking! Silakan pilih waktu lain.');
                                }
                            });

                            // Initial check
                            checkTimeConflict();
                        </script>
                    <?php endif; ?>

                    <div class="mt-4 pt-4 border-t">
                        <a href="my_bookings.php" class="text-sm text-blue-600 hover:underline">Lihat Booking Saya</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include "includes/footer.php"; ?>
