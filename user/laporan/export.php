<?php
require_once '../auth.php';
$dari=$_GET['dari']??date('Y-m-01');$sampai=$_GET['sampai']??date('Y-m-t');$status=strtolower(trim($_GET['status']??''));
$where=["b.id_pengguna={$id_user}","b.check_in <= '".mysqli_real_escape_string($con,$sampai)."'","b.check_out >= '".mysqli_real_escape_string($con,$dari)."'"];
if(valid_booking_status($status))$where[]="LOWER(TRIM(b.status))='".mysqli_real_escape_string($con,$status)."'";
$ws=implode(' AND ',$where);
$list=mysqli_query($con,"SELECT b.kode_booking,t.nama,k.nomor_kamar,tk.nama_tipe,b.check_in,b.check_out,LOWER(TRIM(b.status)) status,b.total,COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) terbayar FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe WHERE {$ws} ORDER BY b.check_in ASC,b.id_booking ASC");
header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="laporan_pemesanan_saya_'.date('Ymd_His').'.csv"');echo "\xEF\xBB\xBF";$out=fopen('php://output','w');fputcsv($out,['Kode Booking','Nama Tamu','Tipe Kamar','Nomor Kamar','Check-in','Check-out','Status','Total','Terbayar','Sisa']);if($list){while($r=mysqli_fetch_assoc($list)){fputcsv($out,[$r['kode_booking'],$r['nama'],$r['nama_tipe'],$r['nomor_kamar'],$r['check_in'],$r['check_out'],status_booking_label($r['status']),$r['total'],$r['terbayar'],max(0,(float)$r['total']-(float)$r['terbayar'])]);}}fclose($out);exit;
