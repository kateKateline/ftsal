<?php
session_start();
include "includes/config.php";
include "includes/header.php";

require_once __DIR__ . '/function/peralatan.php';
require_once __DIR__ . '/function/auth.php';
require_once __DIR__ . '/function/helpers.php'; // Pastikan file helpers.php ada (misalnya untuk koneksi/escape)

// Ambil semua peralatan
$peralatan_list = get_all_peralatan($conn);

// Ambil booking user (Diperlukan di dalam modal untuk dropdown)
$my_bookings = [];
if (is_logged_in()) {
    $user_id = intval($_SESSION['user']['id']); // <<< BARIS 16 (Sudah Diperbaiki)
    // Filter booking yang statusnya 'confirmed' ATAU 'pending' dan belum terlewat
    $today = date('Y-m-d');
    $sql_booking = "SELECT id, tanggal, jam_mulai, jam_selesai, status 
                    FROM booking 
                    WHERE user_id=$user_id AND (status='confirmed' OR status='pending') AND tanggal >= '$today'
                    ORDER BY tanggal ASC";
    $res_booking = mysqli_query($conn, $sql_booking);
    if ($res_booking) {
        while ($b = mysqli_fetch_assoc($res_booking)) {
            // Tambahkan tanggal ke array booking untuk digunakan di JS (penting!)
            $b['tanggal'] = date('Y-m-d', strtotime($b['tanggal']));
            $my_bookings[] = $b;
        }
    }
}

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4">

        <?php if ($msg): ?>
            <div class="mb-6 bg-green-100 text-green-800 p-3 rounded">
                <?= $msg ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-6 bg-red-100 text-red-800 p-3 rounded">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <h1 class="text-2xl font-bold mb-4">Sewa Peralatan Futsal</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($peralatan_list as $p): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="h-48 bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center">
                        <?php if (!empty($p['gambar'])): ?>
                            <img src="assets/img/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama_peralatan']) ?>" class="object-cover h-full w-full">
                        <?php else: ?>
                            <svg class="w-16 h-16 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4z"></path>
                            </svg>
                        <?php endif; ?>
                    </div>

                    <div class="p-4">
                        <div class="mb-2">
                            <h3 class="font-semibold text-lg"><?= htmlspecialchars($p['nama_peralatan']) ?></h3>
                            <p class="text-sm text-gray-500">Rp <?= number_format($p['harga_sewa'],0,',','.') ?>/<?= htmlspecialchars($p['satuan']) ?></p>
                        </div>

                        <div class="mb-3">
                            <?php $stok_real = intval($p['stok']); ?>
                            <span class="text-xs px-2 py-1 rounded-full <?= $stok_real > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                Stok: <?= $stok_real ?>
                            </span>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <?php if ($stok_real > 0): ?>
                                <button type="button" class="open-rental-modal px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition" 
                                    data-id="<?= $p['id'] ?>" 
                                    data-nama="<?= htmlspecialchars($p['nama_peralatan'], ENT_QUOTES) ?>" 
                                    data-harga="<?= intval($p['harga_sewa']) ?>" 
                                    data-stok="<?= $stok_real ?>" 
                                    data-satuan="<?= htmlspecialchars($p['satuan'], ENT_QUOTES) ?>">
                                    Sewa
                                </button>
                            <?php else: ?>
                                <button class="px-4 py-2 bg-gray-300 text-gray-700 rounded" disabled>Stok Habis</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<div id="rentalModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 id="modalTitle" class="text-lg font-semibold">Sewa Peralatan</h3>
            <button id="closeModal" class="text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="p-4">
            <div id="modalContent">
                </div>
        </div>
    </div>
</div>

<script>
    // MY_BOOKINGS sekarang berisi array objek booking, termasuk 'tanggal'
    const MY_BOOKINGS = <?= json_encode($my_bookings) ?>;
    
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    (function(){
        const modal = document.getElementById('rentalModal');
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
        document.querySelectorAll('.open-rental-modal').forEach(btn => {
            btn.addEventListener('click', function(){
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');
                const harga = parseInt(this.getAttribute('data-harga'));
                const stok = parseInt(this.getAttribute('data-stok'));
                const satuan = this.getAttribute('data-satuan');

                const isLoggedIn = <?= is_logged_in() ? 'true' : 'false' ?>;

                if (!isLoggedIn) {
                    const html = `<div class="bg-yellow-50 p-4 rounded text-sm text-yellow-800">Silakan <a href="login.php" class="underline text-blue-600">login</a> terlebih dahulu untuk melakukan penyewaan.</div>`;
                    modalTitle.textContent = 'Login Diperlukan';
                    openModal(html);
                    return;
                }
                
                if (MY_BOOKINGS.length === 0) {
                     const html = `<div class="bg-yellow-50 p-4 rounded text-sm text-yellow-800">Anda tidak memiliki booking lapangan yang sedang **Pending** atau **Confirmed** dan belum terlewat. Sewa peralatan terikat pada booking lapangan.</div>`;
                    modalTitle.textContent = 'Booking Diperlukan';
                    openModal(html);
                    return;
                }

                modalTitle.textContent = 'Sewa - ' + nama;
                
                // Build the booking options
                const bookingOptions = MY_BOOKINGS.map(b => {
                    const statusStyle = b.status === 'pending' ? ' (PENDING)' : ' (CONFIRMED)';
                    // Tambahkan data tanggal ke option
                    return `<option value="${b.id}" data-tanggal="${b.tanggal}">Booking #${b.id} - ${b.tanggal} (${b.jam_mulai} - ${b.jam_selesai})${statusStyle}</option>`;
                }).join('');


                // Dapatkan tanggal booking pertama sebagai default
                const defaultBookingDate = MY_BOOKINGS.length > 0 ? MY_BOOKINGS[0].tanggal : '';

                const formHtml = `
                    <form id="rentalForm" method="POST" action="process_sewa.php" class="space-y-4">
                        <input type="hidden" name="peralatan_id" value="${id}">
                        <input type="hidden" name="harga_satuan" value="${harga}">
                        
                        <input type="hidden" id="rental_tanggal_sewa" name="tanggal_sewa" value="${defaultBookingDate}">
                        <input type="hidden" id="rental_tanggal_kembali" name="tanggal_kembali" value="${defaultBookingDate}">
                        
                        <div>
                            <label for="booking_id" class="block text-sm font-medium text-gray-700">Pilih Booking Lapangan</label>
                            <select id="booking_id" name="booking_id" class="mt-1 p-2 border border-gray-300 rounded w-full" required>
                                ${bookingOptions}
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Tanggal Sewa akan mengikuti tanggal booking yang dipilih: <span id="selectedBookingDate" class="font-semibold text-blue-600">${defaultBookingDate}</span></p>
                        </div>
                        
                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700">Jumlah (${satuan}) - Stok: ${stok}</label>
                            <input type="number" id="quantity" name="quantity" min="1" max="${stok}" value="1" 
                                class="mt-1 p-2 border border-gray-300 rounded w-full" required>
                        </div>
                        
                        <div class="p-3 bg-blue-50 border-l-4 border-blue-400 text-blue-800">
                            Total Harga (per 1 sesi booking): **Rp <span id="totalPrice">0</span>**
                        </div>

                        <button type="submit" name="sewa" class="w-full py-2 px-4 bg-green-600 text-white font-semibold rounded hover:bg-green-700 transition">
                            Proses Sewa
                        </button>
                    </form>
                `;

                openModal(formHtml);

                // Add change handler after form renders
                setTimeout(() => {
                    const qtyInput = modalContent.querySelector('input[name="quantity"]');
                    const bookingSelect = modalContent.querySelector('select[name="booking_id"]');
                    const totalSpan = modalContent.querySelector('#totalPrice');
                    const selectedBookingDateSpan = modalContent.querySelector('#selectedBookingDate');
                    const tanggalSewaHidden = modalContent.querySelector('#rental_tanggal_sewa');
                    const tanggalKembaliHidden = modalContent.querySelector('#rental_tanggal_kembali');


                    function updateBookingDate() {
                        const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
                        const selectedDate = selectedOption.getAttribute('data-tanggal');

                        // Update Tampilan
                        selectedBookingDateSpan.textContent = selectedDate;
                        
                        // Update Input Hidden (Ini yang dikirim ke PHP)
                        tanggalSewaHidden.value = selectedDate;
                        tanggalKembaliHidden.value = selectedDate; // Asumsi sewa 1 hari/sesi booking

                        updateTotal();
                    }

                    function updateTotal() {
                        // Karena disewa bareng game/booking, kita asumsikan 1 hari sewa (sesuai booking)
                        const qty = parseInt(qtyInput.value) || 0;
                        const days = 1; // Disetel menjadi 1 karena terikat dengan 1 sesi booking
                        const total = qty * harga * days; 
                        totalSpan.textContent = formatRupiah(total);
                    }

                    // Event Listeners
                    qtyInput.addEventListener('change', updateTotal);
                    qtyInput.addEventListener('input', updateTotal);
                    bookingSelect.addEventListener('change', updateBookingDate);

                    updateBookingDate(); // Initial call to set date and total
                }, 0);
            });
        });
    })();
</script>

<?php include "includes/footer.php"; ?>