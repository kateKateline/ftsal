<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin')
    header("Location: login.php");
?>

<?php include "includes/header.php"; ?>

<body class="p-6">
    <h1 class="text-3xl">Dashboard Admin</h1>
    <p>Halo admin, <?= $_SESSION['user']['name']; ?></p>

    <a href="logout.php" class="text-blue-600 underline">Logout</a>
</body>
