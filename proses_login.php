<?php
session_start();
require_once 'database/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['btn_login'])) {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: login.php?error=2');
    exit;
}

$stmt = mysqli_prepare(
    $con,
    'SELECT id_pengguna, username, password, nama, peran
     FROM tbl_pengguna
     WHERE username = ?
     LIMIT 1'
);

if (!$stmt) {
    die('Query login gagal: ' . mysqli_error($con));
}

mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $id_pengguna, $username_db, $password_db, $nama, $peran);

$berhasil = false;
$legacy_sha1 = false;

if (mysqli_stmt_fetch($stmt)) {
   
    if (password_get_info((string) $password_db)['algo'] !== 0 && password_verify($password, (string) $password_db)) {
        $berhasil = true;
    
    } elseif (hash_equals((string) $password_db, sha1($password))) {
        $berhasil = true;
        $legacy_sha1 = true;
    }
}

mysqli_stmt_close($stmt);

if (!$berhasil) {
    header('Location: login.php?error=1');
    exit;
}

if ($legacy_sha1) {
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    $up = mysqli_prepare($con, 'UPDATE tbl_pengguna SET password = ? WHERE id_pengguna = ?');
    if ($up) {
        mysqli_stmt_bind_param($up, 'si', $new_hash, $id_pengguna);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }
}

session_regenerate_id(true);
$_SESSION['id_pengguna'] = $id_pengguna;
$_SESSION['username'] = $username_db;
$_SESSION['nama'] = $nama;
$_SESSION['peran'] = $peran;

if ($peran === 'admin') {
    header('Location: admin/index.php');
    exit;
}

if ($peran === 'user') {
    header('Location: user/index.php');
    exit;
}

session_unset();
session_destroy();
header('Location: login.php?error=1');
exit;
?>