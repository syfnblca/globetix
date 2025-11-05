<?php
// admin/pemesanan.php
include 'includes/auth.php';
include 'includes/db_connect.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// Handle approve payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_payment']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    // Update status pembayaran ke sukses dan set tanggal_bayar ke waktu sekarang
    $update_payment = $conn->prepare("UPDATE pembayaran SET status_pembayaran='sukses', tanggal_bayar=NOW() WHERE booking_id=?");
    $update_payment->bind_param("i", $booking_id);
    $update_payment->execute();
    // Update status booking ke confirmed
    $update_booking = $conn->prepare("UPDATE booking SET status_booking='confirmed' WHERE booking_id=?");
    $update_booking->bind_param("i", $booking_id);
    $update_booking->execute();
    echo "<script>alert('Pembayaran berhasil dikonfirmasi!'); window.location.href = 'pemesanan.php';</script>";
    exit;
}

// Handle disapprove payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disapprove_payment']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    // Update status pembayaran ke ditolak
    $update_payment = $conn->prepare("UPDATE pembayaran SET status_pembayaran='ditolak' WHERE booking_id=?");
    $update_payment->bind_param("i", $booking_id);
    $update_payment->execute();
    // Update status booking ke cancelled
    $update_booking = $conn->prepare("UPDATE booking SET status_booking='cancelled' WHERE booking_id=?");
    $update_booking->bind_param("i", $booking_id);
    $update_booking->execute();
    // Release seats
    $rel = $conn->prepare("
        UPDATE kursi k
        JOIN booking_seat bs ON k.seat_id = bs.seat_id
        SET k.status = 'tersedia', k.locked_until = NULL
        WHERE bs.booking_id = ?
    ");
    $rel->bind_param("i", $booking_id);
    $rel->execute();
    echo "<script>alert('Pembayaran berhasil ditolak!'); window.location.href = 'pemesanan.php';</script>";
    exit;
}

// Get filters
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
$bus = isset($_GET['bus']) ? mysqli_real_escape_string($conn, $_GET['bus']) : '';

// Query providers for dropdown
$providers_query = "SELECT provider_id, nama_provider FROM providers ORDER BY nama_provider";
$providers_res = mysqli_query($conn, $providers_query);

// Build WHERE clause
$where = "WHERE 1=1";
if ($date_from && $date_to) {
    $where .= " AND b.tanggal_booking BETWEEN '$date_from' AND '$date_to'";
} elseif ($date_from) {
    $where .= " AND b.tanggal_booking >= '$date_from'";
} elseif ($date_to) {
    $where .= " AND b.tanggal_booking <= '$date_to'";
}
if ($bus) {
    $where .= " AND prov.provider_id = '$bus'";
}

/*
 Query:
 - ambil data booking + user + jadwal + provider
 - ambil seats via GROUP_CONCAT dari booking_seat -> kursi
 - ambil phone dari penumpang pertama (subquery)
*/

// Query utama
$query = "
SELECT
    b.booking_id,
    b.kode_booking,
    b.total_harga,
    b.tanggal_booking,
    b.status_booking,
    pen.nama_penumpang,
    pen.no_identitas,
    pen.no_hp,
    pen.email,
    COALESCE(prov.nama_provider, '-') AS nama_provider,
    j.asal,
    j.tujuan,
    j.tanggal_keberangkatan,
    j.jam_keberangkatan,
    j.estimasi_durasi,
    IFNULL(GROUP_CONCAT(DISTINCT k.nomor_kursi ORDER BY k.nomor_kursi SEPARATOR ', '), '') AS seats,
    IFNULL(COUNT(DISTINCT bs.booking_seat_id), 0) AS seat_count,
    (SELECT status_pembayaran FROM pembayaran WHERE booking_id = b.booking_id LIMIT 1) AS status_pembayaran,
    (SELECT bukti_bayar FROM pembayaran WHERE booking_id = b.booking_id LIMIT 1) AS bukti_bayar
FROM booking b
LEFT JOIN penumpang pen ON b.booking_id = pen.booking_id
LEFT JOIN jadwal j ON b.jadwal_id = j.jadwal_id
LEFT JOIN providers prov ON j.provider_id = prov.provider_id
LEFT JOIN booking_seat bs ON b.booking_id = bs.booking_id
LEFT JOIN kursi k ON bs.seat_id = k.seat_id
" . $where . "
GROUP BY b.booking_id, pen.nama_penumpang, pen.no_identitas, pen.no_hp, pen.email
ORDER BY b.tanggal_booking DESC
";

$res = mysqli_query($conn, $query);

// Store data in array
$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}

?>

<!-- Filter Form -->
<div class="filter-form">
<form method="GET" action="">
<label>Tanggal Dari:
    <input type="text" name="date_from" placeholder="hh/bb/tttt" value="<?php echo htmlspecialchars($date_from); ?>" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
</label>
<label>Tanggal Sampai:
    <input type="text" name="date_to" placeholder="hh/bb/tttt" value="<?php echo htmlspecialchars($date_to); ?>" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
</label>
<label>Bus:
    <select name="bus">
        <option value="">Semua</option>
        <?php mysqli_data_seek($providers_res, 0); while ($prov = mysqli_fetch_assoc($providers_res)) { ?>
        <option value="<?php echo $prov['provider_id']; ?>" <?php if ($bus == $prov['provider_id']) echo 'selected'; ?>><?php echo htmlspecialchars($prov['nama_provider']); ?></option>
        <?php } ?>
    </select>
</label>
<button type="submit">Filter</button>
<a href="#" onclick="generatePDF()" class="btn-download">Download PDF</a>
</form>
</div>

<style>
.filter-form {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
    font-family: Arial, sans-serif;
}

.filter-form label {
    display: flex;
    flex-direction: column;
    font-weight: 600;
    font-size: 14px;
    color: #333;
}

.filter-form input[type="text"],
.filter-form input[type="date"],
.filter-form select {
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
    width: 160px;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
}

.filter-form input[type="text"]:focus,
.filter-form input[type="date"]:focus,
.filter-form select:focus {
    border-color: #007bff;
    outline: none;
}

.filter-form button {
    padding: 9px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.filter-form button:hover {
    background-color: #0056b3;
}

.btn-download {
    padding: 9px 20px;
    background-color: #28a745;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-left: 10px;
}

.btn-download:hover {
    background-color: #218838;
}
</style>

<!-- Additional small CSS for table/card look (ke style.css juga bisa dipindah) -->
<style>
/* Pemesanan table style */
.pemesanan-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
.pemesanan-table th, .pemesanan-table td {
    padding: 12px;
    border: 1px solid #e1e1e1;
    vertical-align: top;
}
.pemesanan-table th {
    background:#fff;
    color:#0a1f44;
    text-align: left;
    font-size:14px;
    border-bottom:2px solid #0a1f44;
}
.col-no {
    width:60px;
    text-align:center;
    font-size:18px;
    background:#f0f0f0;
    font-weight:700;
}
.user-detail .order-badge {
    display:inline-block;
    background:#2ecc71;
    color:#fff;
    padding:4px 8px;
    border-radius:4px;
    font-size:12px;
    margin-bottom:8px;
}
.user-detail .order-badge.cancel { background:#e74c3c; }
.user-detail p { margin:4px 0; font-size:14px; color:#333; }
.bus-detail p { margin:4px 0; font-size:14px; color:#333; }
.amount-box { text-align:right; font-size:14px; color:#333; }
.amount-box .amount { font-weight:700; font-size:16px; display:block; margin-bottom:6px; }
.badge {
    display:inline-block;
    padding:6px 10px;
    border-radius:12px;
    color:#fff;
    font-weight:600;
    font-size:13px;
}
.badge-success { background:#27ae60; }
.badge-danger { background:#e74c3c; }
.badge-warning { background:#f1c40f; color:#000; }
.table-row-separator td { background:#f7f7f7; padding:0; height:8px; border:none; }
/* Filter form styles */
.filter-form { margin-bottom:20px; }
.filter-form form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.filter-form label { display:flex; align-items:center; gap:5px; font-weight:500; }
.filter-form input, .filter-form select { padding:8px; border:1px solid #ccc; border-radius:4px; }
.filter-form button { padding:8px 16px; background:#007bff; color:#fff; border:none; border-radius:4px; cursor:pointer; }
.filter-form button:hover { background:#0056b3; }
.btn-download { padding:8px 16px; background:#28a745; color:#fff; text-decoration:none; border-radius:4px; margin-left:10px; }
.btn-download:hover { background:#218838; }
.btn-confirm-payment {
    display: inline-block;
    padding: 4px 8px;
    background-color: #28a745;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 12px;
    margin-top: 5px;
}
.btn-confirm-payment:hover { background-color: #218838; }
.btn-disapprove-payment {
    display: inline-block;
    padding: 4px 8px;
    background-color: #dc3545;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 12px;
    margin-top: 5px;
    margin-left: 5px;
    border: none;
    cursor: pointer;
}
.btn-disapprove-payment:hover { background-color: #c82333; }
</style>

<div class="dashboard-title">Data Pemesanan</div>

<div id="pdf-content">
<table class="pemesanan-table">
    <thead>
        <tr>
            <th class="col-no">No</th>
            <th>Detail Penumpang</th>
            <th>Detail Bus</th>
            <th style="width:160px;">Jumlah & Tanggal</th>
            <th style="width:140px;">Status</th>
            <th style="width:140px;">Approve</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        if (count($data) > 0):
            foreach ($data as $row):
                // Prepare display values
                $kode = htmlspecialchars($row['kode_booking']);
                $nama = htmlspecialchars($row['nama_penumpang'] ?? '-');
                $identitas = htmlspecialchars($row['no_identitas'] ?? '-');
                $phone = htmlspecialchars($row['no_hp'] ?? '-');
                $email = htmlspecialchars($row['email'] ?? '-');
                $provider = htmlspecialchars($row['nama_provider'] ?? '-');
                $asal = htmlspecialchars($row['asal'] ?? '-');
                $tujuan = htmlspecialchars($row['tujuan'] ?? '-');
                $seats = $row['seats'] ? htmlspecialchars($row['seats']) : '-';
                $seat_count = intval($row['seat_count']);
                $travel_date = $row['tanggal_keberangkatan'] ? date('Y-m-d', strtotime($row['tanggal_keberangkatan'])) : '-';
                $departure_time = $row['jam_keberangkatan'] ?? '';
                $durasi = $row['estimasi_durasi'] ?? '';
                $total = floatval($row['total_harga'] ?? 0);
                $tanggal_booking = $row['tanggal_booking'] ? date('d-m-Y', strtotime($row['tanggal_booking'])) : '-';
                $status = $row['status_booking'];

                // Calculate arrival time: depart + durasi (durasi format HH:MM)
                $arrival_time = '-';
                if ($departure_time && $durasi) {
                    // ensure durasi has format HH:MM
                    $parts = explode(':', $durasi);
                    if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                        $dep = DateTime::createFromFormat('H:i:s', $departure_time);
                        if (!$dep) {
                            // try H:i
                            $dep = DateTime::createFromFormat('H:i', $departure_time);
                        }
                        if ($dep) {
                            $hours = intval($parts[0]);
                            $minutes = intval($parts[1]);
                            $dep->modify("+{$hours} hours");
                            $dep->modify("+{$minutes} minutes");
                            $arrival_time = $dep->format('H:i');
                        }
                    } else {
                        // fallback: if durasi like '3 jam' try parse
                        if (preg_match('/(\d+)\s*jam/i', $durasi, $m)) {
                            $hours = intval($m[1]);
                            $dep = DateTime::createFromFormat('H:i:s', $departure_time) ?: DateTime::createFromFormat('H:i', $departure_time);
                            if ($dep) {
                                $dep->modify("+{$hours} hours");
                                $arrival_time = $dep->format('H:i');
                            }
                        }
                    }
                }

                // Badge class
                $badgeClass = 'badge-warning';
                $statusText = ucfirst($status);
                if ($status === 'confirmed') {
                    $badgeClass = 'badge-success';
                    $statusText = 'Dikonfirmasi';
                } elseif ($status === 'cancelled') {
                    $badgeClass = 'badge-danger';
                    $statusText = 'Dibatalkan';
                } elseif ($status === 'pending') {
                    $badgeClass = 'badge-warning';
                    $statusText = 'Menunggu Pembayaran';
                }

                // Status pembayaran text
                $statusPembayaran = $row['status_pembayaran'];
                if ($statusPembayaran == 'sukses') {
                    $statusPembayaranText = 'Berhasil';
                } elseif ($statusPembayaran == 'pending' && !empty($row['bukti_bayar'])) {
                    $statusPembayaranText = 'Telah Mengunggah Bukti';
                } else {
                    $statusPembayaranText = 'Pending';
                }
        ?>
        <tr>
            <td class="col-no"><?= $no++ ?></td>
            <td class="user-detail">
                <div class="order-badge <?= $status==='cancelled' ? 'cancel' : '' ?>">Order ID: <?= $kode ?></div>
                <p><strong>Nama:</strong> <?= $nama ?></p>
                <p><strong>No Identitas:</strong> <?= $identitas ?></p>
                <p><strong>No HP:</strong> <?= $phone ?></p>
                <p><strong>Email:</strong> <?= $email ?></p>
            </td>
            <td class="bus-detail">
                <p><strong>Nama Bus:</strong> <?= $provider ?></p>
                <p><strong>Asal:</strong> <?= $asal ?></p>
                <p><strong>Tujuan:</strong> <?= $tujuan ?></p>
                <p><strong>Nomor Kursi:</strong> <?= $seats !== '-' ? $seats : ($seat_count > 0 ? $seat_count : '-') ?></p>
                <p><strong>Tanggal Berangkat:</strong> <?= $travel_date ?></p>
                <?php if ($arrival_time !== '-') : ?>
                    <p><strong>Waktu Tiba:</strong> <?= $arrival_time ?></p>
                <?php endif; ?>
                <?php if ($departure_time) : ?>
                    <p><strong>Waktu Berangkat:</strong> <?= substr($departure_time,0,5) ?></p>
                <?php endif; ?>
            </td>
            <td class="amount-box">
                <span class="amount">Rp <?= number_format($total,0,',','.') ?></span>
                <span>Tanggal: <?= $tanggal_booking ?></span>
            </td>
            <td style="text-align:center;">
                <div class="badge <?= $badgeClass ?>"><?= $statusText ?></div>
                <div style="margin-top:5px; font-size:12px; color:#666; <?php if ($statusPembayaranText == 'Telah Mengunggah Bukti') echo 'font-weight:bold; color:#007bff;'; ?>"><?= $statusPembayaranText ?></div>
            </td>
            <td style="text-align:center;">
                <?php if ($status === 'pending'): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                        <button type="submit" name="approve_payment" class="btn-confirm-payment">Approve</button>
                        <?php if (!empty($row['bukti_bayar'])): ?>
                            <button type="submit" name="disapprove_payment" class="btn-disapprove-payment">Tolak</button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    Sudah Dikonfirmasi
                <?php endif; ?>
            </td>
        </tr>
        <tr class="table-row-separator"><td colspan="6"></td></tr>
        <?php
            endforeach;
        else:
        ?>
        <tr>
            <td colspan="6" style="text-align:center;padding:20px;color:#666;">
                Belum ada pemesanan.
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<script>
function generatePDF() {
    const element = document.getElementById('pdf-content');
    html2pdf().from(element).save('pemesanan.pdf');
}
</script>
<?php
include 'includes/footer.php';
?>

