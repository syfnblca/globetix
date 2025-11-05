<?php
// detail_pemesanan.php (Upgrade B, UI style C)
// Pastikan file ini menggantikan file lama. Backup file lama dulu jika perlu.
session_start();
include 'db.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location='masuk.php';</script>";
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Ambil jadwal_id dan kursi (bisa dari GET/POST)
$jadwal_id = intval($_REQUEST['jadwal_id'] ?? 0);
$jadwal_pulang_id = intval($_REQUEST['jadwal_pulang_id'] ?? 0);
$kursi_raw = $_REQUEST['kursi_pergi'] ?? $_REQUEST['kursi'] ?? '';
$kursi_pulang_raw = $_REQUEST['kursi_pulang'] ?? '';
$trip = $_REQUEST['trip'] ?? 'sekali';

if ($jadwal_id <= 0 || trim($kursi_raw) === '') {
    echo "<script>alert('Data tidak lengkap.'); window.location='dashboard.php';</script>";
    exit;
}

$kursi_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $kursi_raw)))));
$jumlah_penumpang = count($kursi_ids);

if ($trip == 'pp' && trim($kursi_pulang_raw) === '') {
    echo "<script>alert('Data kursi pulang tidak lengkap.'); window.location='dashboard.php';</script>";
    exit;
}

$kursi_pulang_ids = [];
if ($trip == 'pp') {
    $kursi_pulang_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $kursi_pulang_raw)))));
    if (count($kursi_pulang_ids) !== $jumlah_penumpang) {
        echo "<script>alert('Jumlah kursi pulang tidak sesuai.'); window.location='dashboard.php';</script>";
        exit;
    }
}

// Untuk pulang pergi, jadwal sama, harga digandakan

// Ambil data jadwal
$stmt = $conn->prepare("SELECT * FROM jadwal WHERE jadwal_id = ? LIMIT 1");
$stmt->bind_param("i", $jadwal_id);
$stmt->execute();
$jadwal = $stmt->get_result()->fetch_assoc();
if (!$jadwal) {
    echo "<script>alert('Jadwal tidak ditemukan.'); window.location='dashboard.php';</script>";
    exit;
}

$jadwal_pulang = null;
if ($trip == 'pp' && $jadwal_pulang_id > 0) {
    $stmt2 = $conn->prepare("SELECT * FROM jadwal WHERE jadwal_id = ? LIMIT 1");
    $stmt2->bind_param("i", $jadwal_pulang_id);
    $stmt2->execute();
    $jadwal_pulang = $stmt2->get_result()->fetch_assoc();
    if (!$jadwal_pulang) {
        echo "<script>alert('Jadwal pulang tidak ditemukan.'); window.location='dashboard.php';</script>";
        exit;
    }
}

// Jika form disubmit: proses penyimpanan booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_data'])) {

    // Ambil data penumpang dari form
    $nama_arr = $_POST['nama_penumpang'] ?? [];
    $ident_arr = $_POST['no_identitas'] ?? [];
    $hp_arr = $_POST['no_hp'] ?? [];
    $email_arr = $_POST['email'] ?? [];

    // Validasi jumlah penumpang sama dengan jumlah kursi
    if (count($nama_arr) !== $jumlah_penumpang || count($ident_arr) !== $jumlah_penumpang) {
        echo "<script>alert('Jumlah data penumpang tidak sesuai jumlah kursi.'); window.history.back();</script>";
        exit;
    }

    // Basic validation for each penumpang (nama + ident)
    for ($i = 0; $i < $jumlah_penumpang; $i++) {
        if (trim($nama_arr[$i]) === '' || trim($ident_arr[$i]) === '') {
            echo "<script>alert('Mohon isi semua data penumpang dengan lengkap.'); window.history.back();</script>";
            exit;
        }
    }

    // Begin transaction untuk mencegah race condition
    $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
    try {
        // 1) Pastikan semua kursi masih tersedia (lock rows)
        foreach ($kursi_ids as $sid) {
            $sid = intval($sid);
            // LOCK the row
            $r = $conn->query("SELECT status, locked_until FROM kursi WHERE seat_id = {$sid} FOR UPDATE");
            $row = $r->fetch_assoc();
            if (!$row) {
                throw new Exception("Kursi (ID: {$sid}) tidak ditemukan.");
            }
            if ($row['status'] !== 'tersedia') {
                throw new Exception("Kursi dengan ID {$sid} tidak tersedia (status: {$row['status']}).");
            }
        }

        if ($trip == 'pp') {
            foreach ($kursi_pulang_ids as $sid) {
                $sid = intval($sid);
                $r = $conn->query("SELECT status, locked_until FROM kursi WHERE seat_id = {$sid} FOR UPDATE");
                $row = $r->fetch_assoc();
                if (!$row) {
                    throw new Exception("Kursi pulang (ID: {$sid}) tidak ditemukan.");
                }
                if ($row['status'] !== 'tersedia') {
                    throw new Exception("Kursi pulang dengan ID {$sid} tidak tersedia (status: {$row['status']}).");
                }
            }
        }

    // 2) Buat booking(s) - one booking per passenger per trip
        $booking_ids = [];
        $kode_bookings = [];

        // Prepare statements
        $insBookingOutbound = $conn->prepare("INSERT INTO booking (user_id, jadwal_id, jadwal_pulang_id, total_harga, status_booking, kode_booking, seat_locked_until) VALUES (?, ?, ?, ?, 'pending', ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
        $insBookingReturn = $conn->prepare("INSERT INTO booking (user_id, jadwal_id, total_harga, status_booking, kode_booking, seat_locked_until) VALUES (?, ?, ?, 'pending', ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
        $insPen = $conn->prepare("INSERT INTO penumpang (booking_id, nama_penumpang, no_identitas, no_hp, email) VALUES (?, ?, ?, ?, ?)");
        $insSeat = $conn->prepare("INSERT INTO booking_seat (booking_id, seat_id) VALUES (?, ?)");

        // For each passenger, create separate booking(s)
        for ($i = 0; $i < $jumlah_penumpang; $i++) {
            $nama = trim($nama_arr[$i]);
            $ident = trim($ident_arr[$i]);
            $hp = trim($hp_arr[$i] ?? '');
            $email = trim($email_arr[$i] ?? '');

            // Booking for outbound
            $total_harga_outbound = floatval($jadwal['harga_perkursi']);
            $kode_booking_outbound = 'TRAVEL-' . rand(100000, 999999);
            $insBookingOutbound->bind_param("iiids", $user_id, $jadwal_id, $jadwal_pulang_id, $total_harga_outbound, $kode_booking_outbound);
            $insBookingOutbound->execute();
            $booking_id_outbound = $insBookingOutbound->insert_id;
            $booking_ids[] = $booking_id_outbound;
            $kode_bookings[] = $kode_booking_outbound;

            // Insert passenger for outbound
            $insPen->bind_param("issss", $booking_id_outbound, $nama, $ident, $hp, $email);
            $insPen->execute();

            // Insert seat for outbound
            $sid_outbound = intval($kursi_ids[$i]);
            $insSeat->bind_param("ii", $booking_id_outbound, $sid_outbound);
            $insSeat->execute();
            $conn->query("UPDATE kursi SET status='dipesan', locked_until = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE seat_id = {$sid_outbound}");

            // Booking for return if round-trip
            if ($trip == 'pp') {
                $total_harga_return = floatval($jadwal_pulang['harga_perkursi']);
                $kode_booking_return = 'TRAVEL-' . rand(100000, 999999);
                $insBookingReturn->bind_param("iids", $user_id, $jadwal_pulang_id, $total_harga_return, $kode_booking_return);
                $insBookingReturn->execute();
                $booking_id_return = $insBookingReturn->insert_id;
                $booking_ids[] = $booking_id_return;
                $kode_bookings[] = $kode_booking_return;

                // Insert passenger for return
                $insPen->bind_param("issss", $booking_id_return, $nama, $ident, $hp, $email);
                $insPen->execute();

                // Insert seat for return
                $sid_return = intval($kursi_pulang_ids[$i]);
                $insSeat->bind_param("ii", $booking_id_return, $sid_return);
                $insSeat->execute();
                $conn->query("UPDATE kursi SET status='dipesan', locked_until = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE seat_id = {$sid_return}");
            }
        }

        $conn->commit();

        // Redirect to pembayaran.php with both booking_ids if round-trip
        $redirect_url = "pembayaran.php?booking_ids=" . implode(',', $booking_ids);
        header("Location: $redirect_url");
        exit;

    } catch (Exception $ex) {
        // rollback dan beri pesan
        $conn->rollback();
        $msg = htmlspecialchars($ex->getMessage());
        echo "<script>alert('Gagal membuat booking: {$msg}'); window.history.back();</script>";
        exit;
    }
}

// Jika bukan POST -> tampilkan halaman form
// Kita tampilkan UI profesional (style sederhana + stepper)
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Detail Pemesanan — GlobeTix</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="detail_pemesanan.css"> <!-- optional: kamu bisa tambahkan CSS file -->
  <style>
    /* Minimal styling professional */
    body { font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:#f5f7fb; color:#222; margin:0; padding:0; }
    header { background:#0b6b86; padding:14px 24px; color:#fff; display:flex; align-items:center; gap:16px; }
    header img { height:36px; }
    .container { max-width:980px; margin:28px auto; padding:20px; background:#fff; border-radius:12px; box-shadow:0 8px 30px rgba(10,20,30,0.06); }
    h1 { margin:0 0 8px 0; font-size:20px; color:#063d4b; }
    .meta { display:flex; gap:18px; margin:10px 0 22px 0; color:#556; }
    .meta.two-column { display: flex; gap: 20px; flex-wrap: wrap; }
    .meta.two-column .route { flex: 1; }
    /* Step Progress Bar - Centered and Professional */
.stepper {
  display: flex;
  justify-content: center;   /* <-- bikin ke tengah */
  align-items: center;
  gap: 12px;
  margin-bottom: 25px;
}

.step {
  padding: 10px 18px;
  border-radius: 10px;
  background: #eef7fb;
  color: #0b6b86;
  font-weight: 700;
  transition: 0.3s;
}

.step.active {
  background: #0b6b86;
  color: white;
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.step:hover {
  transform: scale(1.03);
}

    .panel { display:grid; grid-template-columns: 1fr 360px; gap:18px; align-items:start; }
    .left { padding:8px 0; }
    .right { padding:8px; background:#fbfdff; border-radius:8px; border:1px solid #eef6fa; }
    .penumpang-box { border:1px solid #e8f1f4; padding:12px; border-radius:8px; margin-bottom:12px; background:#fff; }
    label { display:block; margin-bottom:6px; color:#3a5563; font-size:13px; }
    input[type=text], input[type=email] { width:100%; padding:10px; border:1px solid #d7e6eb; border-radius:8px; margin-bottom:8px; }
    .btn-lanjut { background:#0b6b86; color:#fff; padding:12px 18px; border-radius:10px; border:none; cursor:pointer; font-weight:700; }
    .right .summary { font-size:15px; }
    .summary .row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px dashed #eef6fa; }
    .small { font-size:13px; color:#667; }
    .note { font-size:13px; color:#8aa; margin-top:8px; }
    @media (max-width:900px){ .panel{ grid-template-columns: 1fr; } .right{ order:-1 } }
  </style>
</head>
<body>
  <header>
    <img src="logo.png" alt="GlobeTix">
    <div style="font-weight:700;">GlobeTix — Detail Pemesanan</div>
  </header>

  <main class="container">
    <div class="stepper">
  <div class="step">1. Pilih Kursi</div>
  <div class="step active">2. Isi Penumpang</div>
  <div class="step">3. Pembayaran</div>
</div>


    <div class="panel">
      <div class="left">
        <h1>Isi Data Penumpang</h1>
        <?php if ($trip == 'sekali'): ?>
        <div class="meta small">
          <div><strong><?= htmlspecialchars($jadwal['asal']) ?></strong> → <strong><?= htmlspecialchars($jadwal['tujuan']) ?></strong></div>
          <div><?= htmlspecialchars($jadwal['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($jadwal['jam_keberangkatan']) ?></div>
          <div>Jumlah Kursi: <?= $jumlah_penumpang ?></div>
        </div>
        <?php else: ?>
        <div class="meta small two-column">
          <div class="route">
            <div><strong>Rute Pergi:</strong> <?= htmlspecialchars($jadwal['asal']) ?> → <?= htmlspecialchars($jadwal['tujuan']) ?></div>
            <div><strong>Tanggal / Jam:</strong> <?= htmlspecialchars($jadwal['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($jadwal['jam_keberangkatan']) ?></div>
          </div>
          <?php if ($jadwal_pulang): ?>
          <div class="route">
            <div><strong>Rute Pulang:</strong> <?= htmlspecialchars($jadwal_pulang['asal']) ?> → <?= htmlspecialchars($jadwal_pulang['tujuan']) ?></div>
            <div><strong>Tanggal / Jam:</strong> <?= htmlspecialchars($jadwal_pulang['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($jadwal_pulang['jam_keberangkatan']) ?></div>
          </div>
          <?php endif; ?>
          <div>Jumlah Kursi: <?= $jumlah_penumpang ?></div>
        </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
          <input type="hidden" name="jadwal_pulang_id" value="<?= $jadwal_pulang_id ?>">
          <input type="hidden" name="kursi" value="<?= implode(',', $kursi_ids) ?>">
          <input type="hidden" name="kursi_pulang" value="<?= implode(',', $kursi_pulang_ids) ?>">
          <input type="hidden" name="trip" value="<?= $trip ?>">
          <input type="hidden" name="simpan_data" value="1">

          <?php for ($i = 0; $i < $jumlah_penumpang; $i++): ?>
            <div class="penumpang-box">
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <strong>Penumpang <?= $i+1 ?></strong>
                <span class="small">Nomor Kursi: <em><?= htmlspecialchars($kursi_ids[$i]) ?></em></span>
              </div>

              <label>Nama Lengkap</label>
              <input type="text" name="nama_penumpang[]" placeholder="Nama sesuai identitas" required>

              <label>Nomor Identitas (KTP / Paspor)</label>
              <input type="text" name="no_identitas[]" placeholder="Contoh: 3301XXXXXXXXXXXX" required>

              <div style="display:flex; gap:10px;">
                <div style="flex:1;">
                  <label>No HP</label>
                  <input type="text" name="no_hp[]" placeholder="08xx..." required>
                </div>
                <div style="flex:1;">
                  <label>Email</label>
                  <input type="email" name="email[]" placeholder="nama@contoh.com" required>
                </div>
              </div>
            </div>
          <?php endfor; ?>

          <div style="display:flex; align-items:center; gap:10px; margin:14px 0;">
            <input type="checkbox" id="agree" name="agree" required>
            <label for="agree" class="small">Saya setuju dengan <a href="#" onclick="openModal(); return false;">syarat & ketentuan</a> GlobeTix</label>
          </div>

          <button type="submit" class="btn-lanjut" onclick="return validateForm()">Lanjut ke Pembayaran</button>
        </form>

        <script>
        function validateForm() {
            const checkbox = document.getElementById('agree');
            if (!checkbox.checked) {
                alert('Anda harus menyetujui syarat & ketentuan GlobeTix untuk melanjutkan.');
                return false;
            }
            return true;
        }

        function openModal() {
            window.open('syarat.php', 'syaratModal', 'width=700,height=600,scrollbars=yes,resizable=yes');
        }
        </script>
      </div>

      <aside class="right">
        <div class="summary">
          <div style="font-weight:700; margin-bottom:8px;">Ringkasan Pesanan</div>
          <div class="row"><div>Kode Booking (akan dibuat):</div><div><em>otomatis</em></div></div>
          <div class="row"><div>Rute Pergi:</div><div><?= htmlspecialchars($jadwal['asal']) ?> → <?= htmlspecialchars($jadwal['tujuan']) ?></div></div>
          <div class="row"><div>Tanggal / Jam:</div><div><?= htmlspecialchars($jadwal['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($jadwal['jam_keberangkatan']) ?></div></div>
          <?php if ($trip == 'pp' && $jadwal_pulang): ?>
            <div class="row"><div>Rute Pulang:</div><div><?= htmlspecialchars($jadwal_pulang['asal']) ?> → <?= htmlspecialchars($jadwal_pulang['tujuan']) ?></div></div>
            <div class="row"><div>Tanggal / Jam:</div><div><?= htmlspecialchars($jadwal_pulang['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($jadwal_pulang['jam_keberangkatan']) ?></div></div>
          <?php endif; ?>
          <div class="row"><div>Jumlah Kursi:</div><div><?= $jumlah_penumpang ?></div></div>
          <div class="row"><div>Harga / Kursi:</div><div>Rp<?= number_format($jadwal['harga_perkursi'],0,',','.') ?></div></div>
          <?php if ($trip == 'pp' && $jadwal_pulang): ?>
            <div class="row"><div>Harga Pulang / Kursi:</div><div>Rp<?= number_format($jadwal_pulang['harga_perkursi'],0,',','.') ?></div></div>
          <?php endif; ?>
          <div class="row" style="font-weight:800;"><div>Total:</div><div>Rp<?= number_format($total_harga = $jumlah_penumpang * $jadwal['harga_perkursi'] + ($trip == 'pp' && $jadwal_pulang ? $jumlah_penumpang * $jadwal_pulang['harga_perkursi'] : 0),0,',','.') ?></div></div>
          <div class="note">Setelah klik Lanjut, kursi akan dikunci untuk 10 menit untuk menyelesaikan pembayaran.</div>
        </div>
      </aside>
    </div>
  </main>

</body>
</html>
