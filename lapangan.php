<?php
session_start();
include "includes/config.php";
include "includes/header.php";

// messages
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

$lapangan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// fetch lapangan
$res = mysqli_query($conn, "SELECT * FROM lapangan ORDER BY nama ASC");
$lapangans = [];
while ($row = mysqli_fetch_assoc($res)) {
    $lapangans[] = $row;
}

?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4">

        <?php if ($msg): ?>
            <div class="mb-6 bg-green-100 text-green-800 p-3 rounded">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <h1 class="text-2xl font-bold mb-4">Daftar Lapangan</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($lapangans as $lap): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <!-- Gambar -->
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <?php if (!empty($lap['gambar']) && file_exists('assets/images/' . $lap['gambar'])): ?>
                            <img src="assets/images/<?= htmlspecialchars($lap['gambar']) ?>" alt="<?= htmlspecialchars($lap['nama']) ?>" class="w-full h-full object-cover hover:scale-110 transition duration-300">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="font-semibold text-lg"><?= htmlspecialchars($lap['nama']) ?></h3>
                                <p class="text-sm text-gray-500">Rp <?= number_format($lap['harga_per_jam'],0,',','.') ?>/jam</p>
                            </div>
                            <div class="text-sm px-3 py-1 rounded-full <?= $lap['status']=='ready' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= htmlspecialchars($lap['status']) ?>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <?php if ($lap['status'] == 'ready'): ?>
                                <button type="button" class="open-booking-modal px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition" 
                                    data-id="<?= $lap['id'] ?>" data-nama="<?= htmlspecialchars($lap['nama'], ENT_QUOTES) ?>" data-harga="<?= intval($lap['harga_per_jam']) ?>">
                                    Booking
                                </button>
                            <?php else: ?>
                                <button class="px-4 py-2 bg-gray-300 text-gray-700 rounded" disabled>Tidak Tersedia</button>
                            <?php endif; ?>
                            <a href="lapangan_detail.php?id=<?= $lap['id'] ?>" class="text-sm text-blue-600 hover:underline">Lihat detail</a>
                        </div>
                    </div>

                    <!-- Inline booking form removed: modal will be used instead -->

                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8">
            <a href="my_bookings.php" class="text-blue-600 underline">Lihat Booking Saya</a>
        </div>

    </div>
</main>

<?php include "includes/footer.php"; ?>

<!-- Booking Modal -->
<div id="bookingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 id="modalTitle" class="text-lg font-semibold">Pesan Lapangan</h3>
            <button id="closeModal" class="text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="p-4">
            <div id="modalContent">
                <!-- Content filled by JS -->
            </div>
        </div>
    </div>
</div>

<script>
    (function(){
        const modal = document.getElementById('bookingModal');
        const modalContent = document.getElementById('modalContent');
        const modalTitle = document.getElementById('modalTitle');
        const closeModal = document.getElementById('closeModal');

        function openModal(html) {
            modalContent.innerHTML = html;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function hideModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modalContent.innerHTML = '';
        }

        closeModal.addEventListener('click', hideModal);
        modal.addEventListener('click', function(e){ if(e.target === modal) hideModal(); });

        // Attach click handlers
        document.querySelectorAll('.open-booking-modal').forEach(btn => {
            btn.addEventListener('click', function(){
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');
                const harga = this.getAttribute('data-harga');

                // Check if user is logged in (server provides JS var)
                const isLoggedIn = <?= isset($_SESSION['user']) ? 'true' : 'false' ?>;

                if (!isLoggedIn) {
                    const html = `
                        <div class="bg-yellow-50 p-4 rounded text-sm text-yellow-800">Silakan <a href="login.php" class="underline text-blue-600">login</a> terlebih dahulu untuk melakukan booking.</div>
                    `;
                    modalTitle.textContent = 'Login Diperlukan';
                    openModal(html);
                    return;
                }

                modalTitle.textContent = 'Booking - ' + nama;
                const formHtml = `
                    <form method="POST" action="process_booking.php" class="space-y-3">
                        <input type="hidden" name="lapangan_id" value="${id}">
                        <div>
                            <label class="block text-sm text-gray-700">Tanggal</label>
                            <input type="date" name="tanggal" required min="<?= date('Y-m-d') ?>" class="mt-1 p-2 border rounded w-full">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">Jam Mulai</label>
                            <input type="time" name="jam_mulai" required value="09:00" class="mt-1 p-2 border rounded w-full">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">Durasi</label>
                            <select name="durasi" required class="mt-1 p-2 border rounded w-full">
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
                        <div class="text-right">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Pesan Sekarang</button>
                        </div>
                    </form>
                `;

                openModal(formHtml);
            });
        });
    })();
</script>
