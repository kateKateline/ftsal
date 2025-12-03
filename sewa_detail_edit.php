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

$result = $conn->query("SELECT * FROM sewa_peralatan_detail WHERE id = $id");
$sewa = $result->fetch_assoc();

if (!$sewa) {
    echo "Sewa peralatan tidak ditemukan";
    exit;
}

// Get all users, bookings, and peralatan for dropdown
$users_list = $conn->query("SELECT id, name FROM users WHERE role = 'user' ORDER BY name");
$booking_list = $conn->query("SELECT id, CONCAT('Booking #', id) as label FROM booking ORDER BY id DESC");
$peralatan_list = $conn->query("SELECT id, nama_peralatan, harga_sewa FROM sewa_peralatan ORDER BY nama_peralatan");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $booking_id = $_POST['booking_id'] ?? null;
    $peralatan_id = $_POST['peralatan_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 0;
    $tanggal_sewa = $_POST['tanggal_sewa'] ?? '';
    $tanggal_kembali = $_POST['tanggal_kembali'] ?? '';
    $status = $_POST['status'] ?? 'pending';

    if (!$user_id) {
        $error = 'User harus dipilih';
    } elseif (!$booking_id) {
        $error = 'Booking harus dipilih';
    } elseif (!$peralatan_id) {
        $error = 'Peralatan harus dipilih';
    } elseif ($quantity <= 0) {
        $error = 'Quantity harus lebih dari 0';
    } elseif (empty($tanggal_sewa) || empty($tanggal_kembali)) {
        $error = 'Tanggal sewa dan tanggal kembali harus diisi';
    } elseif (strtotime($tanggal_kembali) < strtotime($tanggal_sewa)) {
        $error = 'Tanggal kembali harus lebih besar atau sama dengan tanggal sewa';
    } else {
        // Get peralatan price
        $p_result = $conn->query("SELECT harga_sewa FROM sewa_peralatan WHERE id = $peralatan_id");
        $p_data = $p_result->fetch_assoc();
        $harga_per_unit = $p_data['harga_sewa'];
        
        // Calculate days
        $start = strtotime($tanggal_sewa);
        $end = strtotime($tanggal_kembali);
        $days = max(1, ceil(($end - $start) / 86400));
        
        $total_harga = $harga_per_unit * $quantity * $days;

        $stmt = $conn->prepare("UPDATE sewa_peralatan_detail SET user_id = ?, booking_id = ?, peralatan_id = ?, quantity = ?, tanggal_sewa = ?, tanggal_kembali = ?, total_harga = ?, status = ? WHERE id = ?");
        $stmt->bind_param("iiiisssi", $user_id, $booking_id, $peralatan_id, $quantity, $tanggal_sewa, $tanggal_kembali, $total_harga, $status, $id);
        
        if ($stmt->execute()) {
            $success = 'Sewa peralatan berhasil diperbarui';
            header("Location: dashboard_admin.php", true, 303);
            exit;
        } else {
            $error = 'Gagal memperbarui sewa: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Sewa Peralatan</title>
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
        input[type="number"],
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
        <h1>Edit Sewa Peralatan</h1>

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
                        <option value="<?= $u['id'] ?>" <?= ($sewa['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="booking_id">Booking *</label>
                <select id="booking_id" name="booking_id" required>
                    <option value="">-- Pilih Booking --</option>
                    <?php 
                    $booking_list->data_seek(0);
                    while($b = $booking_list->fetch_assoc()): ?>
                        <option value="<?= $b['id'] ?>" <?= ($sewa['booking_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['label']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="peralatan_id">Peralatan *</label>
                <select id="peralatan_id" name="peralatan_id" required>
                    <option value="">-- Pilih Peralatan --</option>
                    <?php 
                    $peralatan_list->data_seek(0);
                    while($p = $peralatan_list->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= ($sewa['peralatan_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nama_peralatan']) ?> (Rp <?= number_format($p['harga_sewa'], 0, ',', '.') ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity *</label>
                <input type="number" id="quantity" name="quantity" required min="1" value="<?= htmlspecialchars($sewa['quantity'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="tanggal_sewa">Tanggal Sewa *</label>
                <input type="date" id="tanggal_sewa" name="tanggal_sewa" required value="<?= htmlspecialchars($sewa['tanggal_sewa'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="tanggal_kembali">Tanggal Kembali *</label>
                <input type="date" id="tanggal_kembali" name="tanggal_kembali" required value="<?= htmlspecialchars($sewa['tanggal_kembali'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="pending" <?= ($sewa['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= ($sewa['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="canceled" <?= ($sewa['status'] ?? '') === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>

            <div class="info-note">
                💡 Total harga dihitung dari: Harga Peralatan × Quantity × Jumlah Hari
            </div>

            <div class="button-group">
                <button type="submit">Simpan Perubahan</button>
                <a href="dashboard_admin.php" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>
