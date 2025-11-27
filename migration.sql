-- Tambah kolom gambar dan deskripsi ke tabel lapangan jika belum ada
ALTER TABLE lapangan ADD COLUMN gambar VARCHAR(255) AFTER status;
ALTER TABLE lapangan ADD COLUMN deskripsi TEXT AFTER gambar;
ALTER TABLE lapangan ADD COLUMN fasilitas VARCHAR(500) AFTER deskripsi;
