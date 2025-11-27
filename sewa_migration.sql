-- Create table for equipment rental records
-- Run this SQL in your database to set up the rental tracking

CREATE TABLE IF NOT EXISTS sewa_peralatan_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    peralatan_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    tanggal_sewa DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    total_harga INT NOT NULL,
    status ENUM('pending','confirmed','completed','canceled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (peralatan_id) REFERENCES sewa_peralatan(id)
);
