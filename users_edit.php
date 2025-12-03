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

$result = $conn->query("SELECT * FROM users WHERE id = $id");
$user = $result->fetch_assoc();

if (!$user) {
    echo "User tidak ditemukan";
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($name)) {
        $error = 'Nama harus diisi';
    } elseif (empty($email)) {
        $error = 'Email harus diisi';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sesuai';
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = 'Password harus minimal 6 karakter';
    } else {
        // Check if email is changed and already exists
        if ($email !== $user['email']) {
            $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
            if ($check->num_rows > 0) {
                $error = 'Email sudah terdaftar';
            }
        }

        if (!$error) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $email, $hashed_password, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $role, $id);
            }
            
            if ($stmt->execute()) {
                $success = 'User berhasil diperbarui';
                header("Location: dashboard_admin.php", true, 303);
                exit;
            } else {
                $error = 'Gagal memperbarui user: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
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
        input[type="email"],
        input[type="password"],
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
            background: #d1ecf1;
            color: #0c5460;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit User</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name">Nama *</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($user['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>

            <div class="info-note">
                💡 Kosongkan field password jika tidak ingin mengubah password
            </div>

            <div class="form-group">
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password Baru</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6">
            </div>

            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
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
