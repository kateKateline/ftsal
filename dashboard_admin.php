<?php
session_start();
require_once "includes/config.php";
require_once "function/auth.php";
require_once __DIR__ . '/function/helpers.php';

require_login();

if (current_user()['role'] !== 'admin') {
    echo "<h3>Access denied.</h3>";
    exit;
}

$lap = $conn->query("SELECT * FROM lapangan ORDER BY id DESC");
$alat = $conn->query("SELECT * FROM sewa_peralatan ORDER BY id DESC");
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
$booking = $conn->query("SELECT b.*, u.name as user_name, l.nama as lapangan_name FROM booking b JOIN users u ON b.user_id = u.id JOIN lapangan l ON b.lapangan_id = l.id ORDER BY b.id DESC");
$sewa_detail = $conn->query("SELECT sd.*, u.name as user_name, p.nama_peralatan FROM sewa_peralatan_detail sd JOIN users u ON sd.user_id = u.id JOIN sewa_peralatan p ON sd.peralatan_id = p.id ORDER BY sd.id DESC");

// Get totals
$total_lapangan = $conn->query("SELECT COUNT(*) as total FROM lapangan")->fetch_assoc()['total'];
$total_peralatan = $conn->query("SELECT COUNT(*) as total FROM sewa_peralatan")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_booking = $conn->query("SELECT COUNT(*) as total FROM booking")->fetch_assoc()['total'];
$total_sewa_detail = $conn->query("SELECT COUNT(*) as total FROM sewa_peralatan_detail")->fetch_assoc()['total'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            background: #f5f5f5;
            font-family: Arial;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            width: 230px;
            height: 100%;
            background: #1e1e2d;
            padding-top: 20px;
            color: #fff;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            padding: 14px 20px;
            color: #cfcfcf;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }

        .sidebar a.active {
            background: #27293d;
            color: #fff;
        }

        .sidebar a:hover {
            background: #2f3147;
        }

        /* Content */
        .content {
            margin-left: 230px;
            padding: 25px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 40px;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
            animation: fade 0.3s;
        }

        @keyframes fade {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 14px;
        }

        table th {
            background: #fafafa;
        }

        .btn {
            padding: 7px 12px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            background: #3498db;
            font-size: 13px;
        }

        .btn-red { background: #e74c3c; }
        .btn-green { background: #2ecc71; }
        .btn-yellow { background: #f1c40f; color:black; }
        .btn-blue { background: #3498db; }
        .btn-purple { background: #9b59b6; }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .total-info {
            background: #ecf0f1;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 15px;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        /* --- Tambahkan di dalam <style> di <head> --- */

/* Grid untuk menampung kartu-kartu */
.dashboard-grid {
    display: flex; /* Untuk tata letak horizontal */
    gap: 20px; /* Jarak antar kartu */
    flex-wrap: wrap;
    margin-bottom: 25px; /* Jarak dengan konten di bawahnya */
}

/* Tampilan setiap kartu statistik */
.dashboard-card {
    background: white;
    padding: 20px;
    width: 250px; /* Sesuaikan lebar kartu */
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
    text-align: left; /* Teks rata kiri */
    flex-grow: 1; /* Biarkan kartu tumbuh mengisi ruang */
    min-width: 200px; /* Batasan lebar minimum */
    transition: transform 0.2s;
}

.dashboard-card:hover {
    transform: translateY(-3px); /* Efek hover ringan */
}

.dashboard-card h3 {
    font-size: 16px;
    color: #7f8c8d; /* Warna abu-abu yang lebih tenang */
    margin: 0 0 5px 0;
    font-weight: normal;
}

.dashboard-card p {
    font-size: 38px;
    font-weight: bold;
    margin: 0;
    color: #2c3e50; /* Warna biru gelap */
}

/* Warna latar belakang untuk membedakan kartu (opsional) */
.card-lapangan { border-bottom: 4px solid #3498db; } /* Biru */
.card-peralatan { border-bottom: 4px solid #2ecc77; } /* Hijau */
.card-users { border-bottom: 4px solid #f1c40f; } /* Kuning */
    </style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>Admin</h2>

    <a class="tab-btn active" data-target="home">🏠 Dashboard</a>
    <a class="tab-btn" data-target="lapangan">📌 Data Lapangan</a>
    <a class="tab-btn" data-target="peralatan">🧰 Data Peralatan</a>
    <a class="tab-btn" data-target="booking">📅 Data Booking</a>
    <a class="tab-btn" data-target="sewa_detail">🎁 Data Sewa Peralatan</a>
    <a class="tab-btn" data-target="users">👥 Data User</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="content">

    <!-- HOME -->
<div class="tab-content" id="home">
    <div class="card" style="margin-bottom: 25px;">
        <h1>Dashboard Admin</h1>
        <p>Selamat datang, <?= current_user()['name'] ?></p>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card card-lapangan">
            <h3>Total Lapangan</h3>
            <p><?= $total_lapangan ?></p>
        </div>

        <div class="dashboard-card card-peralatan">
            <h3>Total Sewa Peralatan</h3>
            <p><?= $total_peralatan ?></p>
        </div>

        <div class="dashboard-card card-users">
            <h3>Total User</h3>
            <p><?= $total_users ?></p>
        </div>
    </div>
    
    <div class="card" style="width: 100%;">
        <h2>Ringkasan Data</h2>
        <canvas id="ringkasanChart" style="max-height: 400px;"></canvas>
    </div>

</div>

    <!-- LAPANGAN -->
    <div class="tab-content" id="lapangan" style="display: none;">
        <div class="card">
            <div class="header-section">
                <div>
                    <h2>Data Lapangan</h2>
                    <div class="total-info">Total Lapangan: <?= $total_lapangan ?></div>
                </div>
                <div class="button-group">
                    <a href="lapangan_add.php" class="btn btn-green">+ Tambah Lapangan</a>
                    <a href="pdf/lapangan_pdf.php" class="btn btn-purple" target="_blank">📄 Cetak PDF</a>
                </div>
            </div>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Harga/Jam</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                <?php while($l = $lap->fetch_assoc()): ?>
                <tr>
                    <td><?= $l['id'] ?></td>
                    <td><?= $l['nama'] ?></td>
                    <td><?= format_rupiah($l['harga_per_jam']) ?></td>
                    <td><?= $l['status'] ?></td>
                    <td>
                        <a class="btn" href="lapangan_edit.php?id=<?= $l['id'] ?>">Edit</a>
                        <a class="btn btn-red" href="lapangan_delete.php?id=<?= $l['id'] ?>"
                           onclick="return confirm('Yakin hapus?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- PERALATAN -->
    <div class="tab-content" id="peralatan" style="display: none;">
        <div class="card">
            <div class="header-section">
                <div>
                    <h2>Data Peralatan</h2>
                    <div class="total-info">Total Peralatan: <?= $total_peralatan ?></div>
                </div>
                <div class="button-group">
                    <a href="peralatan_add.php" class="btn btn-green">+ Tambah Peralatan</a>
                    <a href="pdf/peralatan_pdf.php" class="btn btn-purple" target="_blank">📄 Cetak PDF</a>
                </div>
            </div>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Stok</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                <?php while($a = $alat->fetch_assoc()): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td><?= $a['nama_peralatan'] ?></td>
                    <td><?= $a['stok'] ?></td>
                    <td><?= format_rupiah($a['harga_sewa']) ?></td>
                    <td><?= $a['status'] ?></td>
                    <td>
                        <a class="btn" href="peralatan_edit.php?id=<?= $a['id'] ?>">Edit</a>
                        <a class="btn btn-red" href="peralatan_delete.php?id=<?= $a['id'] ?>"
                           onclick="return confirm('Hapus?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- BOOKING -->
    <div class="tab-content" id="booking" style="display: none;">
        <div class="card">
            <div class="header-section">
                <div>
                    <h2>Data Booking</h2>
                    <div class="total-info">Total Booking: <?= $total_booking ?></div>
                </div>
                <div class="button-group">
                    <a href="booking_add.php" class="btn btn-green">+ Tambah Booking</a>
                    <a href="pdf/booking_pdf.php" class="btn btn-purple" target="_blank">📄 Cetak PDF</a>
                </div>
            </div>

            <table>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Lapangan</th>
                    <th>Tanggal</th>
                    <th>Jam Mulai</th>
                    <th>Jam Selesai</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                <?php while($b = $booking->fetch_assoc()): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><?= $b['user_name'] ?></td>
                    <td><?= $b['lapangan_name'] ?></td>
                    <td><?= $b['tanggal'] ?></td>
                    <td><?= $b['jam_mulai'] ?></td>
                    <td><?= $b['jam_selesai'] ?></td>
                    <td><?= format_rupiah($b['total_harga']) ?></td>
                    <td><?= $b['status'] ?></td>
                    <td>
                        <a class="btn" href="booking_edit.php?id=<?= $b['id'] ?>">Edit</a>
                        <a class="btn btn-red" href="booking_delete.php?id=<?= $b['id'] ?>"
                           onclick="return confirm('Yakin hapus booking?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- SEWA PERALATAN DETAIL -->
    <div class="tab-content" id="sewa_detail" style="display: none;">
        <div class="card">
            <div class="header-section">
                <div>
                    <h2>Data Sewa Peralatan</h2>
                    <div class="total-info">Total Sewa: <?= $total_sewa_detail ?></div>
                </div>
                <div class="button-group">
                    <a href="sewa_detail_add.php" class="btn btn-green">+ Tambah Sewa</a>
                    <a href="pdf/sewa_detail_pdf.php" class="btn btn-purple" target="_blank">📄 Cetak PDF</a>
                </div>
            </div>

            <table>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Peralatan</th>
                    <th>Quantity</th>
                    <th>Tanggal Sewa</th>
                    <th>Tanggal Kembali</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                <?php while($sd = $sewa_detail->fetch_assoc()): ?>
                <tr>
                    <td><?= $sd['id'] ?></td>
                    <td><?= $sd['user_name'] ?></td>
                    <td><?= $sd['nama_peralatan'] ?></td>
                    <td><?= $sd['quantity'] ?></td>
                    <td><?= $sd['tanggal_sewa'] ?></td>
                    <td><?= $sd['tanggal_kembali'] ?></td>
                    <td><?= format_rupiah($sd['total_harga']) ?></td>
                    <td><?= $sd['status'] ?></td>
                    <td>
                        <a class="btn" href="sewa_detail_edit.php?id=<?= $sd['id'] ?>">Edit</a>
                        <a class="btn btn-red" href="sewa_detail_delete.php?id=<?= $sd['id'] ?>"
                           onclick="return confirm('Yakin hapus sewa?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- USERS -->
    <div class="tab-content" id="users" style="display: none;">
        <div class="card">
            <div class="header-section">
                <div>
                    <h2>Data User</h2>
                    <div class="total-info">Total User: <?= $total_users ?></div>
                </div>
                <div class="button-group">
                    <a href="users_add.php" class="btn btn-green">+ Tambah User</a>
                    <a href="pdf/users_pdf.php" class="btn btn-purple" target="_blank">📄 Cetak PDF</a>
                </div>
            </div>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
                <?php while($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= $u['name'] ?></td>
                    <td><?= $u['email'] ?></td>
                    <td><?= $u['role'] ?></td>
                    <td>
                        <a class="btn" href="users_edit.php?id=<?= $u['id'] ?>">Edit</a>
                        <a class="btn btn-red" href="users_delete.php?id=<?= $u['id'] ?>"
                           onclick="return confirm('Yakin hapus user?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

</div>

<script>
    // Semua tombol tab
    const tabs = document.querySelectorAll(".tab-btn");
    const contents = document.querySelectorAll(".tab-content");

    tabs.forEach(btn => {
        btn.addEventListener("click", () => {

            // Hilangkan active di semua tab
            tabs.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            // Hide all content
            contents.forEach(c => c.style.display = "none");

            // Show selected content
            const target = document.getElementById(btn.dataset.target);
            target.style.display = "block";
        });
    });
</script>
<script>
    // ... Script tab sebelumnya ...

    // --- Skrip Chart.js ---
    
    // Ambil nilai total dari PHP
    const totalLap = <?= $total_lapangan ?>;
    const totalAlat = <?= $total_peralatan ?>;
    const totalUser = <?= $total_users ?>;

    const ctx = document.getElementById('ringkasanChart').getContext('2d');
    
    const ringkasanChart = new Chart(ctx, {
        type: 'bar', // Jenis chart: bar (batang)
        data: {
            labels: ['Total Lapangan', 'Total Peralatan', 'Total User'],
            datasets: [{
                label: 'Jumlah Total Data',
                data: [totalLap, totalAlat, totalUser], // Data dari variabel PHP
                backgroundColor: [
                    'rgba(52, 152, 219, 0.7)',  // Biru untuk Lapangan
                    'rgba(46, 204, 119, 0.7)',  // Hijau untuk Peralatan
                    'rgba(241, 196, 15, 0.7)'   // Kuning untuk User
                ],
                borderColor: [
                    'rgba(52, 152, 219, 1)',
                    'rgba(46, 204, 119, 1)',
                    'rgba(241, 196, 15, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Penting agar chart berukuran sesuai container
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah'
                    },
                    ticks: {
                        // Memastikan hanya angka bulat yang ditampilkan
                        callback: function(value) {if (value % 1 === 0) {return value;}}
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Sembunyikan legenda
                },
                title: {
                    display: true,
                    text: 'Perbandingan Jumlah Item Data Utama'
                }
            }
        }
    });

</script>
</body>
</html>
