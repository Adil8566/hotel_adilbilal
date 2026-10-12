<?php
require_once '../auth.php';
require_once 'SimplePDF.php';
$id_booking=(int)($_GET['id']??0);
if($id_booking<=0){die('ID booking tidak valid.');}
$q=mysqli_query($con,"SELECT b.*,t.nama,t.no_identitas,t.no_hp,t.email,t.alamat,k.nomor_kamar,tk.nama_tipe,COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) terbayar FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe WHERE b.id_booking={$id_booking} AND b.id_pengguna={$id_user} LIMIT 1");
$data=$q?mysqli_fetch_assoc($q):null; if(!$data){http_response_code(404);die('Pemesanan tidak ditemukan.');}
$pdf=new SimplePDF();
$pdf->text(42,60,'GRAND DIAN HOTEL BUMIAYU',16,true);
$pdf->text(42,82,'BUKTI PEMESANAN',12,true);
$pdf->line(42,92,553,92);
$y=120;
foreach([
 'Kode Booking'=>$data['kode_booking'],'Nama Tamu'=>$data['nama'],'Tipe Kamar'=>$data['nama_tipe'],'Nomor Kamar'=>$data['nomor_kamar'],'Check-in'=>tanggal_id($data['check_in']),'Check-out'=>tanggal_id($data['check_out']),'Jumlah Tamu'=>$data['jumlah_tamu'].' orang','Status'=>status_booking_label($data['status']),'Total'=>rupiah($data['total']),'Terbayar'=>rupiah($data['terbayar']),'Sisa'=>rupiah(max(0,(float)$data['total']-(float)$data['terbayar']))
] as $k=>$v){$pdf->text(42,$y,$k,10,true);$pdf->text(180,$y,(string)$v,10,false);$y+=24;}
$y+=10;$pdf->line(42,$y,553,$y);$y+=26;$pdf->text(42,$y,'Harap membawa bukti pemesanan saat datang ke hotel.',9,false);
$pdf->output('bukti_'.$data['kode_booking'].'.pdf');
