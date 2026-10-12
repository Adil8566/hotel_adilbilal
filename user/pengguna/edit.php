<?php
require_once '../auth.php';
$q=mysqli_query($con,"SELECT username,nama FROM tbl_pengguna WHERE id_pengguna={$id_user} LIMIT 1");$u=$q?mysqli_fetch_assoc($q):null;if(!$u){session_unset();session_destroy();header('Location: ../../login.php?error=3');exit;}
$error='';
if(isset($_POST['simpan'])){
 $nama=trim($_POST['nama']??'');$password=(string)($_POST['password']??'');$konfirmasi=(string)($_POST['konfirmasi_password']??'');
 if($nama==='')$error='Nama wajib diisi.';
 elseif($password!==''&&strlen($password)<6)$error='Password baru minimal 6 karakter.';
 elseif($password!==''&&$password!==$konfirmasi)$error='Konfirmasi password tidak sama.';
 else{
  if($password!==''){
   $hash=password_hash($password,PASSWORD_DEFAULT);$stmt=mysqli_prepare($con,'UPDATE tbl_pengguna SET nama=?,password=? WHERE id_pengguna=?');mysqli_stmt_bind_param($stmt,'ssi',$nama,$hash,$id_user);
  }else{$stmt=mysqli_prepare($con,'UPDATE tbl_pengguna SET nama=? WHERE id_pengguna=?');mysqli_stmt_bind_param($stmt,'si',$nama,$id_user);}
  if(mysqli_stmt_execute($stmt)){mysqli_stmt_close($stmt);$_SESSION['nama']=$nama;header('Location: index.php?pesan=edit');exit;}
  $error='Gagal memperbarui profil.';mysqli_stmt_close($stmt);
 }
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Edit Profil - Grand Dian Hotel</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css"></head>
<body class="bg-light"><div class="container py-5"><div class="card shadow-sm mx-auto border-0" style="max-width:650px"><div class="card-header bg-white"><h5 class="mb-0">Edit Profil</h5></div><div class="card-body p-4"><?php if($error): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?><form method="POST"><div class="mb-3"><label class="form-label">Username</label><input class="form-control" value="<?= e($u['username']); ?>" readonly></div><div class="mb-3"><label class="form-label">Nama</label><input name="nama" class="form-control" value="<?= e($_POST['nama']??$u['nama']); ?>" required></div><div class="mb-3"><label class="form-label">Password Baru</label><input type="password" name="password" class="form-control" minlength="6"><small class="text-muted">Kosongkan jika password tidak diubah.</small></div><div class="mb-3"><label class="form-label">Konfirmasi Password</label><input type="password" name="konfirmasi_password" class="form-control" minlength="6"></div><button name="simpan" class="btn btn-primary">Simpan Perubahan</button><a href="index.php" class="btn btn-secondary ms-2">Kembali</a></form></div></div></div></body></html>
