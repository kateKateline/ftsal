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

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: dashboard_admin.php');
    exit;
}

$result = $conn->query("SELECT * FROM booking WHERE id = $id");
$booking = $result->fetch_assoc();

if (!$booking) {
    echo "Booking tidak ditemukan";
    exit;
}

// Get all users and lapangan for dropdown
$users_list = $conn->query("SELECT id, name FROM users WHERE role = 'user' ORDER BY name");
$lapangan_list = $conn->query("SELECT id, nama, harga_per_jam FROM lapangan ORDER BY nama");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $lapangan_id = $_POST['lapangan_id'] ?? null;
    $tanggal = $_POST['tanggal'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $status = $_POST['status'] ?? 'pending';

    if (!$user_id) {
        $error = 'User harus dipilih';
    } elseif (!$lapangan_id) {
        $error = 'Lapangan harus dipilih';
    } elseif (empty($tanggal)) {
        $error = 'Tanggal harus diisi';
    } elseif (empty($jam_mulai) || empty($jam_selesai)) {
        $error = 'Jam mulai dan jam selesai harus diisi';
    } elseif (strtotime($jam_selesai) <= strtotime($jam_mulai)) {
        $error = 'Jam selesai harus lebih besar dari jam mulai';
    } else {
        // Calculate duration in minutes
        $start_minutes = time_to_minutes($jam_mulai);
        $end_minutes = time_to_minutes($jam_selesai);
        $duration_minutes = $end_minutes - $start_minutes;
        $duration_hours = ceil($duration_minutes / 60);

        // Get lapangan price
        $lap_result = $conn->query("SELECT harga_per_jam FROM lapangan WHERE id = $lapangan_id");
        $lap_data = $lap_result->fetch_assoc();
        $total_harga = $lap_data['harga_per_jam'] * $duration_hours;

        $stmt = $conn->prepare("UPDATE booking SET user_id = ?, lapangan_id = ?, tanggal = ?, jam_mulai = ?, jam_selesai = ?, total_harga = ?, status = ? WHERE id = ?");
        $stmt->bind_param("iisssssi", $user_id, $lapangan_id, $tanggal, $jam_mulai, $jam_selesai, $total_harga, $status, $id);
        
        if ($stmt->execute()) {
            $success = 'Booking berhasil diperbarui';
            header("Location: dashboard_admin.php", true, 303);
            exit;
        } else {
            $error = 'Gagal memperbarui booking: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Booking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        h1 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }

        input[type="text"],
        input[type="date"],
        input[type="time"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button, a {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }

        button {
            background: #3498db;
            color: white;
            flex: 1;
        }

        button:hover {
            background: #2980b9;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            flex: 1;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .info-note {
            background: #e7f3ff;
            color: #004085;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Booking</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="user_id">User *</label>
                <select id="user_id" name="user_id" required>
                    <option value="">-- Pilih User --</option>
                    <?php 
                    $users_list->data_seek(0);
                    while($u = $users_list->fetch_assoc()): ?>
                        <option value="<?= $u['id'] ?>" <?= ($booking['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="lapangan_id">Lapangan *</label>
                <select id="lapangan_id" name="lapangan_id" required>
                    <option value="">-- Pilih Lapangan --</option>
                    <?php 
                    $lapangan_list->data_seek(0);
                    while($l = $lapangan_list->fetch_assoc()): ?>
                        <option value="<?= $l['id'] ?>" <?= ($booking['lapangan_id'] ?? '') == $l['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['nama']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tanggal">Tanggal *</label>
                <input type="date" id="tanggal" name="tanggal" required value="<?= htmlspecialchars($booking['tanggal'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="jam_mulai">Jam Mulai *</label>
                <input type="time" id="jam_mulai" name="jam_mulai" required value="<?= htmlspecialchars(substr($booking['jam_mulai'] ?? '', 0, 5)) ?>">
            </div>

            <div class="form-group">
                <label for="jam_selesai">Jam Selesai *</label>
                <input type="time" id="jam_selesai" name="jam_selesai" required value="<?= htmlspecialchars(substr($booking['jam_selesai'] ?? '', 0, 5)) ?>">
            </div>

            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="pending" <?= ($booking['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= ($booking['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="canceled" <?= ($booking['status'] ?? '') === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>

            <div class="info-note">
                💡 Total harga akan dihitung otomatis berdasarkan durasi dan harga lapangan
            </div>

            <div class="button-group">
                <button type="submit">Simpan Perubahan</button>
                <a href="dashboard_admin.php" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>
