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

$result = $conn->query("SELECT * FROM sewa_peralatan WHERE id = $id");
$peralatan = $result->fetch_assoc();

if (!$peralatan) {
    echo "Peralatan tidak ditemukan";
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_peralatan = $_POST['nama_peralatan'] ?? '';
    $stok = $_POST['stok'] ?? 0;
    $harga_sewa = $_POST['harga_sewa'] ?? 0;
    $satuan = $_POST['satuan'] ?? '';
    $status = $_POST['status'] ?? 'tersedia';

    if (empty($nama_peralatan)) {
        $error = 'Nama peralatan harus diisi';
    } elseif ($stok < 0) {
        $error = 'Stok tidak boleh negatif';
    } elseif ($harga_sewa <= 0) {
        $error = 'Harga sewa harus lebih dari 0';
    } elseif (empty($satuan)) {
        $error = 'Satuan harus diisi';
    } else {
        $stmt = $conn->prepare("UPDATE sewa_peralatan SET nama_peralatan = ?, stok = ?, harga_sewa = ?, satuan = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sisssi", $nama_peralatan, $stok, $harga_sewa, $satuan, $status, $id);
        
        if ($stmt->execute()) {
            $success = 'Peralatan berhasil diperbarui';
            header("Location: dashboard_admin.php", true, 303);
            exit;
        } else {
            $error = 'Gagal memperbarui peralatan: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Peralatan</title>
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
        <h1>Edit Peralatan</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nama_peralatan">Nama Peralatan *</label>
                <input type="text" id="nama_peralatan" name="nama_peralatan" required value="<?= htmlspecialchars($peralatan['nama_peralatan'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="stok">Stok *</label>
                <input type="number" id="stok" name="stok" required min="0" value="<?= htmlspecialchars($peralatan['stok'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="harga_sewa">Harga Sewa (Rp) *</label>
                <input type="number" id="harga_sewa" name="harga_sewa" required min="1" value="<?= htmlspecialchars($peralatan['harga_sewa'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="satuan">Satuan *</label>
                <input type="text" id="satuan" name="satuan" required placeholder="Contoh: unit, buah, set" value="<?= htmlspecialchars($peralatan['satuan'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="tersedia" <?= ($peralatan['status'] ?? '') === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="habis" <?= ($peralatan['status'] ?? '') === 'habis' ? 'selected' : '' ?>>Habis</option>
                </select>
            </div>

            <div class="button-group">
                <button type="submit">Simpan Perubahan</button>
                <a href="dashboard_admin.php" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>
