<?php

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rupiah')) {
    function rupiah($angka): string
    {
        return 'Rp ' . number_format((float) $angka, 0, ',', '.');
    }
}

if (!function_exists('tanggal_id')) {
    function tanggal_id(?string $tanggal): string
    {
        if (!$tanggal) {
            return '-';
        }

        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $dt = new DateTime($tanggal);
        return (int) $dt->format('d') . ' ' . $bulan[(int) $dt->format('m')] . ' ' . $dt->format('Y');
    }
}

if (!function_exists('status_booking_label')) {
    function status_booking_label(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        return match ($status) {
            'pending' => 'Pending',
            'dikonfirmasi' => 'Dikonfirmasi',
            'checkin' => 'Check-in',
            'checkout' => 'Check-out',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($status ?: 'Tidak diketahui'),
        };
    }
}

if (!function_exists('status_booking_badge')) {
    function status_booking_badge(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        return match ($status) {
            'pending' => 'bg-warning text-dark',
            'dikonfirmasi' => 'bg-info text-dark',
            'checkin' => 'bg-primary',
            'checkout' => 'bg-success',
            'dibatalkan' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}

if (!function_exists('normalize_hotel_statuses')) {
    function normalize_hotel_statuses(mysqli $con): void
    {
        // Menyatukan nilai lama seperti "Dikonfirmasi" menjadi "dikonfirmasi".
        @mysqli_query($con, "UPDATE tbl_booking SET status = LOWER(TRIM(status)) WHERE status IS NOT NULL");
        @mysqli_query($con, "UPDATE tbl_kamar SET status = LOWER(TRIM(status)) WHERE status IS NOT NULL");
    }
}

if (!function_exists('sync_room_statuses')) {
    function sync_room_statuses(mysqli $con): void
    {
        // Kamar maintenance tidak disentuh oleh sinkronisasi otomatis.
        @mysqli_query($con, "
            UPDATE tbl_kamar k
            SET k.status = 'terisi'
            WHERE LOWER(TRIM(k.status)) <> 'maintenance'
              AND EXISTS (
                  SELECT 1
                  FROM tbl_booking b
                  WHERE b.id_kamar = k.id_kamar
                    AND LOWER(TRIM(b.status)) = 'checkin'
                    AND b.check_in <= CURDATE()
                    AND b.check_out > CURDATE()
              )
        ");

        @mysqli_query($con, "
            UPDATE tbl_kamar k
            SET k.status = 'tersedia'
            WHERE LOWER(TRIM(k.status)) <> 'maintenance'
              AND NOT EXISTS (
                  SELECT 1
                  FROM tbl_booking b
                  WHERE b.id_kamar = k.id_kamar
                    AND LOWER(TRIM(b.status)) = 'checkin'
                    AND b.check_in <= CURDATE()
                    AND b.check_out > CURDATE()
              )
        ");
    }
}

if (!function_exists('sync_single_room_status')) {
    function sync_single_room_status(mysqli $con, int $id_kamar): void
    {
        $id_kamar = (int) $id_kamar;
        if ($id_kamar <= 0) {
            return;
        }

        $room = mysqli_query($con, "SELECT status FROM tbl_kamar WHERE id_kamar = {$id_kamar} LIMIT 1");
        $data_room = $room ? mysqli_fetch_assoc($room) : null;
        if (!$data_room) {
            return;
        }

        if (strtolower(trim((string) $data_room['status'])) === 'maintenance') {
            return;
        }

        $active = mysqli_query($con, "
            SELECT id_booking
            FROM tbl_booking
            WHERE id_kamar = {$id_kamar}
              AND LOWER(TRIM(status)) = 'checkin'
              AND check_in <= CURDATE()
              AND check_out > CURDATE()
            LIMIT 1
        ");

        $status = ($active && mysqli_num_rows($active) > 0) ? 'terisi' : 'tersedia';
        mysqli_query($con, "UPDATE tbl_kamar SET status = '{$status}' WHERE id_kamar = {$id_kamar}");
    }
}

if (!function_exists('booking_overlaps')) {
    function booking_overlaps(mysqli $con, int $id_kamar, string $check_in, string $check_out, int $exclude_id = 0): bool
    {
        $id_kamar = (int) $id_kamar;
        $exclude_id = (int) $exclude_id;

        $stmt = mysqli_prepare($con, "
            SELECT id_booking
            FROM tbl_booking
            WHERE id_kamar = ?
              AND id_booking != ?
              AND LOWER(TRIM(status)) IN ('pending','dikonfirmasi','checkin')
              AND check_in < ?
              AND check_out > ?
            LIMIT 1
        ");

        if (!$stmt) {
            return true;
        }

        mysqli_stmt_bind_param($stmt, 'iiss', $id_kamar, $exclude_id, $check_out, $check_in);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $found = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        return $found;
    }
}

if (!function_exists('total_terbayar')) {
    function total_terbayar(mysqli $con, int $id_booking): float
    {
        $id_booking = (int) $id_booking;
        $q = mysqli_query($con, "SELECT COALESCE(SUM(jumlah_bayar), 0) AS total_bayar FROM tbl_pembayaran WHERE id_booking = {$id_booking}");
        $d = $q ? mysqli_fetch_assoc($q) : null;
        return (float) ($d['total_bayar'] ?? 0);
    }
}

if (!function_exists('status_pembayaran')) {
    function status_pembayaran(float $total, float $terbayar): string
    {
        if ($terbayar <= 0) {
            return 'Belum Bayar';
        }
        if ($terbayar + 0.009 >= $total) {
            return 'Lunas';
        }
        return 'DP';
    }
}

if (!function_exists('status_pembayaran_badge')) {
    function status_pembayaran_badge(string $status): string
    {
        return match ($status) {
            'Lunas' => 'bg-success',
            'DP' => 'bg-warning text-dark',
            default => 'bg-danger',
        };
    }
}

if (!function_exists('valid_booking_status')) {
    function valid_booking_status(string $status): bool
    {
        return in_array(strtolower(trim($status)), ['pending', 'dikonfirmasi', 'checkin', 'checkout', 'dibatalkan'], true);
    }
}
?>