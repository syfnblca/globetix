<?php
// admin/hapus_bus.php
include 'includes/db_connect.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// Ambil id dari query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='dashboard-title'>Invalid request.</div>";
    echo "<p><a href='bus.php' class='btn-tambah'>Kembali ke Data Jadwal</a></p>";
    include 'includes/footer.php';
    exit;
}

$jadwal_id = intval($_GET['id']);

// 1) Cek apakah jadwal ada
$stmt = mysqli_prepare($conn, "SELECT jadwal_id, asal, tujuan, tanggal_keberangkatan, jam_keberangkatan FROM jadwal WHERE jadwal_id = ?");
mysqli_stmt_bind_param($stmt, "i", $jadwal_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) === 0) {
    // jadwal tidak ditemukan
    mysqli_stmt_close($stmt);
    echo "<div class='dashboard-title'>Jadwal tidak ditemukan.</div>";
    echo "<p><a href='bus.php' class='btn-tambah'>Kembali ke Data Jadwal</a></p>";
    include 'includes/footer.php';
    exit;
}
mysqli_stmt_bind_result($stmt, $jid, $asal, $tujuan, $tgl, $jam);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// 2) Cek apakah ada booking untuk jadwal ini
$stmt2 = mysqli_prepare($conn, "SELECT COUNT(*) FROM booking WHERE jadwal_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $jadwal_id);
mysqli_stmt_execute($stmt2);
mysqli_stmt_bind_result($stmt2, $countBooking);
mysqli_stmt_fetch($stmt2);
mysqli_stmt_close($stmt2);

if ($countBooking > 0) {
    // Ada booking, tidak boleh dihapus
    echo "<div class='dashboard-title'>Tidak bisa menghapus Jadwal</div>";
    echo "<div style='background:#ffecec;border:1px solid #ffb3b3;padding:15px;border-radius:6px;margin-top:12px;'>";
    echo "<strong>Gagal:</strong> Jadwal ini memiliki <strong>{$countBooking}</strong> pemesanan. Hapus tidak diizinkan.</div>";
    echo "<p style='margin-top:15px;'><a href='bus.php' class='btn-tambah'>Kembali ke Data Jadwal</a></p>";
    include 'includes/footer.php';
    exit;
}

// 3) Tidak ada booking → lakukan penghapusan (transaksi)
mysqli_begin_transaction($conn);

try {
    // Hapus kursi (opsional jika FK ON DELETE CASCADE sudah ada, ini lebih eksplisit)
    $delSeats = mysqli_prepare($conn, "DELETE FROM kursi WHERE jadwal_id = ?");
    mysqli_stmt_bind_param($delSeats, "i", $jadwal_id);
    if (!mysqli_stmt_execute($delSeats)) {
        throw new Exception("Gagal menghapus kursi: " . mysqli_error($conn));
    }
    mysqli_stmt_close($delSeats);

    // Hapus jadwal
    $delJadwal = mysqli_prepare($conn, "DELETE FROM jadwal WHERE jadwal_id = ?");
    mysqli_stmt_bind_param($delJadwal, "i", $jadwal_id);
    if (!mysqli_stmt_execute($delJadwal)) {
        throw new Exception("Gagal menghapus jadwal: " . mysqli_error($conn));
    }
    mysqli_stmt_close($delJadwal);

    // Commit
    mysqli_commit($conn);

    // Redirect kembali ke bus.php dengan flag sukses
    header("Location: bus.php?deleted=1");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<div class='dashboard-title'>Terjadi kesalahan</div>";
    echo "<div style='background:#ffecec;border:1px solid #ffb3b3;padding:15px;border-radius:6px;margin-top:12px;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p style='margin-top:15px;'><a href='bus.php' class='btn-tambah'>Kembali ke Data Jadwal</a></p>";
    include 'includes/footer.php';
    exit();
}
