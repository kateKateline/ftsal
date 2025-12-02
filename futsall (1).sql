-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Des 2025 pada 18.03
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `futsall`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `booking`
--

CREATE TABLE `booking` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lapangan_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `total_harga` int(11) NOT NULL,
  `status` enum('pending','confirmed','canceled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `booking`
--

INSERT INTO `booking` (`id`, `user_id`, `lapangan_id`, `tanggal`, `jam_mulai`, `jam_selesai`, `total_harga`, `status`, `created_at`) VALUES
(1, 1, 1, '2025-11-28', '00:00:00', '01:00:00', 120000, 'canceled', '2025-11-27 09:50:01'),
(2, 1, 1, '2025-11-28', '20:00:00', '22:00:00', 240000, 'canceled', '2025-11-27 10:39:25'),
(3, 1, 1, '2025-11-29', '17:40:00', '21:19:00', 438000, 'canceled', '2025-11-27 10:41:06'),
(4, 2, 1, '2025-11-28', '09:58:00', '11:28:00', 180000, 'canceled', '2025-11-27 11:38:37'),
(5, 2, 1, '2025-11-27', '09:00:00', '10:00:00', 120000, 'confirmed', '2025-11-27 11:43:21'),
(6, 1, 1, '2025-11-30', '09:00:00', '11:00:00', 240000, 'pending', '2025-11-27 11:49:06'),
(7, 1, 2, '2025-11-27', '07:00:00', '11:00:00', 400000, 'pending', '2025-11-27 11:49:34'),
(8, 1, 1, '2025-11-28', '09:00:00', '13:00:00', 480000, 'pending', '2025-11-27 12:08:26'),
(9, 1, 2, '2025-12-05', '09:00:00', '11:00:00', 200000, 'confirmed', '2025-12-02 16:27:20'),
(10, 2, 1, '2025-12-18', '09:00:00', '10:30:00', 180000, 'pending', '2025-12-02 16:33:21'),
(11, 2, 1, '2025-12-05', '09:00:00', '10:00:00', 120000, 'pending', '2025-12-02 16:54:53'),
(12, 1, 1, '2025-12-13', '09:00:00', '10:00:00', 120000, 'pending', '2025-12-02 16:59:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lapangan`
--

CREATE TABLE `lapangan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `harga_per_jam` int(11) NOT NULL,
  `status` enum('ready','maintenance') DEFAULT 'ready',
  `gambar` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `fasilitas` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lapangan`
--

INSERT INTO `lapangan` (`id`, `nama`, `harga_per_jam`, `status`, `gambar`, `deskripsi`, `fasilitas`) VALUES
(1, 'Lapangan A', 120000, 'ready', 'lapangan_a.jpg', NULL, NULL),
(2, 'Lapangan B', 100000, 'ready', 'lapangan_b.jpg', NULL, NULL),
(3, 'Lapangan C', 150000, 'maintenance', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sewa_peralatan`
--

CREATE TABLE `sewa_peralatan` (
  `id` int(11) NOT NULL,
  `nama_peralatan` varchar(100) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `harga_sewa` int(11) NOT NULL,
  `satuan` varchar(50) NOT NULL,
  `status` enum('tersedia','habis') NOT NULL DEFAULT 'tersedia',
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sewa_peralatan`
--

INSERT INTO `sewa_peralatan` (`id`, `nama_peralatan`, `stok`, `harga_sewa`, `satuan`, `status`, `gambar`) VALUES
(1, 'Bola Futsal Standar', 3, 5000, 'buah', 'tersedia', 'bola_futsal.jpg'),
(2, 'Gawang Portable Kecil', 0, 25000, 'unit', 'tersedia', 'gawang_kecil.jpg'),
(3, 'Rompi Tim Merah', 20, 1000, 'unit', 'tersedia', 'rompi_merah.jpg'),
(4, 'Rompi Tim Biru', 20, 1000, 'unit', 'tersedia', 'rompi_biru.jpg'),
(5, 'Kerucut Penanda (Cone)', 28, 500, 'unit', 'tersedia', 'kerucut.jpg'),
(6, 'Set Pelindung Shin Guard', 5, 8000, 'set', 'tersedia', 'shin_guard.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sewa_peralatan_detail`
--

CREATE TABLE `sewa_peralatan_detail` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `peralatan_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `tanggal_sewa` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `total_harga` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `tanggal_transaksi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sewa_peralatan_detail`
--

INSERT INTO `sewa_peralatan_detail` (`id`, `user_id`, `booking_id`, `peralatan_id`, `quantity`, `tanggal_sewa`, `tanggal_kembali`, `total_harga`, `status`, `tanggal_transaksi`) VALUES
(1, 1, 7, 1, 1, '2025-11-27', '2025-11-27', 5000, 'pending', '2025-11-27 14:28:50'),
(2, 1, 6, 2, 3, '2025-11-27', '2025-11-27', 75000, 'pending', '2025-11-27 15:56:47'),
(3, 1, 7, 1, 1, '2025-11-27', '2025-11-27', 5000, 'pending', '2025-11-27 16:00:05'),
(4, 1, 7, 1, 1, '2025-11-27', '2025-11-27', 5000, 'pending', '2025-11-27 16:00:12'),
(5, 1, 6, 5, 1, '2025-11-27', '2025-11-27', 500, 'pending', '2025-11-27 16:01:13'),
(6, 1, 7, 1, 1, '2025-11-27', '2025-11-27', 5000, 'pending', '2025-11-27 16:08:47'),
(7, 2, 10, 1, 3, '2025-12-18', '2025-12-18', 15000, 'pending', '2025-12-02 16:42:48'),
(8, 1, 9, 5, 1, '2025-12-05', '2025-12-05', 500, 'pending', '2025-12-02 17:02:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$t4lUxEkWm7JPjfqXO20s/e0MzeRri/JR.8BTEFfcpcQbFnKzIlMiu', 'admin'),
(2, 'User Biasa', 'user@gmail.com', '$2y$10$WeZMIIpfXj9CpYYC9E7nfOJl0VH1NZVvlLRK8ozJF4IVfD2p2ztXm', 'user');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `lapangan_id` (`lapangan_id`);

--
-- Indeks untuk tabel `lapangan`
--
ALTER TABLE `lapangan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sewa_peralatan`
--
ALTER TABLE `sewa_peralatan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sewa_peralatan_detail`
--
ALTER TABLE `sewa_peralatan_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `lapangan`
--
ALTER TABLE `lapangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `sewa_peralatan`
--
ALTER TABLE `sewa_peralatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `sewa_peralatan_detail`
--
ALTER TABLE `sewa_peralatan_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`lapangan_id`) REFERENCES `lapangan` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
