<?php
session_start();
include 'db.php';

// 🔒 Otomatis lepaskan kursi expired sebelum apapun
$conn->query("
    UPDATE kursi
    SET status = 'tersedia', locked_until = NULL
    WHERE status = 'locked' AND locked_until < NOW()
");

// Pastikan parameter ada
if (!isset($_GET['jadwal_id']) || !isset($_GET['kursi'])) {
    echo "<script>alert('Data tidak lengkap!'); window.location='dashboard.php';</script>";
    exit;
}

$jadwal_id = intval($_GET['jadwal_id']);
$kursi_raw = $_GET['kursi'];
$kursi_ids = array_filter(array_map('intval', explode(',', $kursi_raw)));

// ✅ Ambil data jadwal dari database
$stmt = $conn->prepare("SELECT * FROM jadwal WHERE jadwal_id = ?");
$stmt->bind_param("i", $jadwal_id);
$stmt->execute();
$jadwal = $stmt->get_result()->fetch_assoc();

if (!$jadwal) {
    echo "<script>alert('Data jadwal tidak ditemukan!'); window.location='dashboard.php';</script>";
    exit;
}

// Ambil nomor kursi yang dipilih
$kursi_in = implode(',', $kursi_ids);
$q = $conn->query("SELECT nomor_kursi FROM kursi WHERE seat_id IN ($kursi_in)");
$kursi_terpilih = [];
while ($row = $q->fetch_assoc()) {
    $kursi_terpilih[] = $row['nomor_kursi'];
}
$kursi_list = implode(', ', $kursi_terpilih);

// Hitung total harga
$total_harga = count($kursi_terpilih) * $jadwal['harga_perkursi'];

// -------------------------
// SERVER-SIDE: Buat booking sementara & lock kursi 10 menit
// Cek ketersediaan kursi satu per satu (hindari race condition sederhana)
$conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
$unavailable = false;
foreach ($kursi_ids as $sid) {
    $sid = intval($sid);
    $sres = $conn->query("SELECT status, locked_until FROM kursi WHERE seat_id = $sid FOR UPDATE");
    $s = $sres->fetch_assoc();
    if (!$s || ($s['status'] != 'tersedia' && !is_null($s['locked_until']))) {
        $unavailable = true;
        break;
    }
}
if ($unavailable) {
    $conn->rollback();
    echo "<script>alert('Salah satu kursi sudah tidak tersedia. Silakan pilih kursi lain.'); window.location='pilih_kursi.php?jadwal_id={$jadwal_id}';</script>";
    exit;
}

// Generate kode booking: TRAVEL-XXXXXX
$kode_booking = 'TRAVEL-' . rand(100000,999999);

// Insert booking (pending) dengan seat_locked_until 10 menit dari sekarang
$stmt_ins = $conn->prepare("INSERT INTO booking (user_id, jadwal_id, total_harga, status_booking, kode_booking, seat_locked_until) VALUES (?, ?, ?, 'pending', ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
$user_id = intval($_SESSION['user_id'] ?? 0);
$stmt_ins->bind_param("iids", $user_id, $jadwal_id, $total_harga, $kode_booking);
if (!$stmt_ins->execute()) {
    $conn->rollback();
    echo "<script>alert('Gagal membuat booking. Coba lagi.'); window.location='pilih_kursi.php?jadwal_id={$jadwal_id}';</script>";
    exit;
}
$booking_id = $stmt_ins->insert_id;

// Lock each kursi: update kursi.status -> 'locked', locked_until, insert into booking_seat
foreach ($kursi_ids as $sid) {
    $sid = intval($sid);
    $conn->query("UPDATE kursi SET status='locked', locked_until = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE seat_id = $sid");
    $conn->query("INSERT INTO booking_seat (booking_id, seat_id) VALUES ($booking_id, $sid)");
}

// Commit transaction
$conn->commit();

// Ambil waktu locked_until dari booking untuk countdown
$res_lock = $conn->query("SELECT seat_locked_until FROM booking WHERE booking_id = $booking_id");
$row_lock = $res_lock->fetch_assoc();
$locked_until = $row_lock['seat_locked_until'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Konfirmasi Booking - GlobeTix</title>
  <link rel="stylesheet" href="konfirmasi_booking.css">
  <style>
    /* kecilkan style lokal bila perlu */
    .countdown { font-weight: bold; color: #d32f2f; margin-top: 20px; }
  </style>
</head>
<body>
  <header>
    <div class="logo"><img src="assets/logo.png" alt="GlobeTix"></div>
    <nav>
      <a href="dashboard.php">Beranda</a>
      <a href="riwayat.php">Riwayat</a>
      <a href="profil.php">Profil</a>
      <a href="keluar.php">Keluar</a>
    </nav>
  </header>

  <div class="confirm-container">
    <h2>Konfirmasi Pemesanan</h2>
    <p><strong>Rute:</strong> <?= htmlspecialchars($jadwal['asal']) ?> → <?= htmlspecialchars($jadwal['tujuan']) ?></p>
    <p><strong>Tanggal:</strong> <?= htmlspecialchars($jadwal['tanggal_keberangkatan']) ?></p>
    <p><strong>Jam:</strong> <?= htmlspecialchars($jadwal['jam_keberangkatan']) ?></p>
    <p><strong>Kelas:</strong> <?= ucfirst($jadwal['kelas_layanan']) ?></p>

    <p><strong>Kursi Terpilih:</strong> <?= $kursi_list ?></p>
    <p><strong>Total Pembayaran:</strong> Rp<?= number_format($total_harga,0,',','.') ?></p>

    <!-- FORM: Kirim ke pembayaran.php dengan booking_id (penting untuk server-side check) -->
    <form action="pembayaran.php" method="post">
      <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
      <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
      <input type="hidden" name="kode_booking" value="<?= htmlspecialchars($kode_booking) ?>">
      <button type="submit" class="btn-lanjut">Lanjut ke Pembayaran</button>
    </form>

    <!-- COUNTDOWN: ditampilkan sebagai mm:ss, basis waktu dari server ($locked_until) -->
    <p class="countdown">Waktu tersisa untuk konfirmasi: <span id="timer">--:--</span></p>
  </div>

  <script>
    // =========================
    // Client-side countdown yang bergantung pada waktu server (locked_until)
    // Kode penting: menggunakan waktu server sehingga refresh tidak mengakali countdown
    // =========================
    const timerEl = document.getElementById('timer');
    // server provides locked_until as ISO string
    const lockedUntil = new Date("<?= $locked_until ?>");
    function updateTimer() {
      const now = new Date();
      let diff = Math.floor((lockedUntil - now) / 1000); // seconds
      if (diff <= 0) {
        timerEl.textContent = '00:00';
        alert('Waktu pembayaran habis. Kursi dilepaskan.');
        window.location = 'pilih_kursi.php?jadwal_id=<?= $jadwal_id ?>';
        return;
      }
      const minutes = Math.floor(diff / 60).toString().padStart(2,'0');
      const seconds = (diff % 60).toString().padStart(2,'0');
      timerEl.textContent = `${minutes}:${seconds}`;
    }
    updateTimer();
    setInterval(updateTimer, 1000);
  </script>
</body>
</html>
