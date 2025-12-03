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

$result = $conn->query("SELECT * FROM lapangan WHERE id = $id");
$lapangan = $result->fetch_assoc();

if (!$lapangan) {
    echo "Lapangan tidak ditemukan";
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $harga_per_jam = $_POST['harga_per_jam'] ?? 0;
    $status = $_POST['status'] ?? 'ready';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $fasilitas = $_POST['fasilitas'] ?? '';

    if (empty($nama)) {
        $error = 'Nama lapangan harus diisi';
    } elseif ($harga_per_jam <= 0) {
        $error = 'Harga per jam harus lebih dari 0';
    } else {
        $stmt = $conn->prepare("UPDATE lapangan SET nama = ?, harga_per_jam = ?, status = ?, deskripsi = ?, fasilitas = ? WHERE id = ?");
        $stmt->bind_param("sisssi", $nama, $harga_per_jam, $status, $deskripsi, $fasilitas, $id);
        
        if ($stmt->execute()) {
            $success = 'Lapangan berhasil diperbarui';
            header("Location: dashboard_admin.php", true, 303);
            exit;
        } else {
            $error = 'Gagal memperbarui lapangan: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Lapangan</title>
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
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Lapangan</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nama">Nama Lapangan *</label>
                <input type="text" id="nama" name="nama" required value="<?= htmlspecialchars($lapangan['nama'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="harga_per_jam">Harga Per Jam (Rp) *</label>
                <input type="number" id="harga_per_jam" name="harga_per_jam" required min="1" value="<?= htmlspecialchars($lapangan['harga_per_jam'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="ready" <?= ($lapangan['status'] ?? '') === 'ready' ? 'selected' : '' ?>>Ready</option>
                    <option value="maintenance" <?= ($lapangan['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                </select>
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi"><?= htmlspecialchars($lapangan['deskripsi'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="fasilitas">Fasilitas</label>
                <textarea id="fasilitas" name="fasilitas"><?= htmlspecialchars($lapangan['fasilitas'] ?? '') ?></textarea>
            </div>

            <div class="button-group">
                <button type="submit">Simpan Perubahan</button>
                <a href="dashboard_admin.php" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>
