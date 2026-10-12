<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../../login.php');
    exit;
}

if (($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$admin_pos = strpos($script_path, '/admin/');
$admin_url = $admin_pos !== false
    ? substr($script_path, 0, $admin_pos) . '/admin'
    : '/admin';
$admin_url = rtrim($admin_url, '/');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(dirname($admin_url) . '/assets_mantis/css/mantis-hotel.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<nav class="navbar navbar-light mantis-topbar">
    <div class="container-fluid">
        <a href="<?= htmlspecialchars($admin_url . '/index.php', ENT_QUOTES, 'UTF-8'); ?>" class="navbar-brand">
            <i class="fas fa-hotel me-2"></i>Grand Dian Hotel Bumiayu
        </a>
        <div>
            <span class="me-3 small"><i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="<?= htmlspecialchars(dirname($admin_url) . '/logout.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary">Logout</a>
        </div>
    </div>
</nav>
