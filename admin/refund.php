<?php
include 'includes/auth.php';
include 'includes/db_connect.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="container mt-4 refund-container">
    <h2 class="refund-title">Daftar Refund</h2>
    <table class="table table-bordered refund-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Booking</th>
                <th>Nama User</th>
                <th>Jumlah Refund</th>
                <th>Alasan</th>
                <th>Rekening Refund</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        $query = mysqli_query($conn, "
            SELECT r.refund_id, b.kode_booking, u.nama_lengkap,
                   r.jumlah_refund, r.alasan, r.rekening_refund, r.status_refund, r.tanggal_request
            FROM refund r
            JOIN booking b ON r.booking_id = b.booking_id
            JOIN users u ON b.user_id = u.user_id
            ORDER BY r.tanggal_request DESC
        ");
        while($row = mysqli_fetch_assoc($query)) {
            // Badge warna
            switch($row['status_refund']) {
                case 'disetujui': $badge = 'success'; break;
                case 'ditolak': $badge = 'danger'; break;
                case 'selesai': $badge = 'primary'; break;
                default: $badge = 'warning'; // diproses
            }
            echo "<tr>
                <td>".$no++."</td>
                <td>".$row['kode_booking']."</td>
                <td>".$row['nama_lengkap']."</td>
                <td>Rp ".number_format($row['jumlah_refund'],0,',','.')."</td>
                <td>".$row['alasan']."</td>
                <td>".$row['rekening_refund']."</td>
                <td><span class='badge bg-".$badge."'>".$row['status_refund']."</span></td>
                <td>";
            if($row['status_refund']=='diproses'){
                echo "
                  <a href='refund_update.php?id=".$row['refund_id']."&aksi=setuju' class='btn btn-success btn-sm'>✔ Setujui</a>
                  <a href='refund_update.php?id=".$row['refund_id']."&aksi=tolak' class='btn btn-danger btn-sm'>✖ Tolak</a>";
            } elseif($row['status_refund']=='disetujui'){
                echo "
                  <a href='refund_update.php?id=".$row['refund_id']."&aksi=selesai' class='btn btn-primary btn-sm'>🔄 Tandai Selesai</a>";
            } else {
                echo "-";
            }
            echo "</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
