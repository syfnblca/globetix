<?php
// pembayaran.php (Final - E-commerce style, stepper centered, no payer NIK input, seats locked, 10-min countdown)
// Requirements:
// - session_start()
// - include 'db.php' provides $conn (mysqli)
// - konfirmasi_pembayaran.php harus menangani upload, validasi file, dan update booking->waiting_verification
// Notes:
// - This page only displays read-only penumpang & seats and allows upload of proof.
// - If seat_locked_until expired, booking cancelled and seats released (handled on page load).

session_start();
include 'db.php';

// helper
function abort_alert($msg, $redirect = 'dashboard.php') {
    $js = "<script>alert(" . json_encode($msg) . "); window.location=" . json_encode($redirect) . ";</script>";
    echo $js;
    exit;
}

// Handle cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $booking_ids_str = $_POST['booking_ids'] ?? '';
    $booking_ids = array_filter(array_map('intval', explode(',', $booking_ids_str)));
    if (!empty($booking_ids)) {
        $conn->begin_transaction();
        try {
            foreach ($booking_ids as $bid) {
                // Update booking status to cancelled
                $upd = $conn->prepare("UPDATE booking SET status_booking = 'cancelled' WHERE booking_id = ?");
                $upd->bind_param("i", $bid);
                $upd->execute();

                // Release seats
                $rel = $conn->prepare("
                    UPDATE kursi k
                    JOIN booking_seat bs ON k.seat_id = bs.seat_id
                    SET k.status = 'tersedia', k.locked_until = NULL
                    WHERE bs.booking_id = ?
                ");
                $rel->bind_param("i", $bid);
                $rel->execute();
            }
            $conn->commit();
            abort_alert('Booking dibatalkan. Kursi telah dikembalikan.', 'riwayat.php');
        } catch (Exception $ex) {
            $conn->rollback();
            abort_alert('Gagal membatalkan booking. Silakan coba lagi.', 'riwayat.php');
        }
    } else {
        abort_alert('Data booking tidak valid.', 'riwayat.php');
    }
}

// get booking ids (comma-separated for round-trip)
$booking_ids_str = $_POST['booking_ids'] ?? $_GET['booking_ids'] ?? '';
$booking_ids = array_filter(array_map('intval', explode(',', $booking_ids_str)));
if (empty($booking_ids)) {
    abort_alert('Data pembayaran tidak ditemukan!', 'dashboard.php');
}

// fetch all bookings
$bookings = [];
$total_harga = 0;
$jadwal_pulang = null;
foreach ($booking_ids as $bid) {
    $stmt = $conn->prepare("
        SELECT
            b.booking_id,
            b.jadwal_id,
            b.jadwal_pulang_id,
            b.total_harga,
            b.status_booking,
            b.kode_booking,
            b.seat_locked_until,
            j.asal,
            j.tujuan,
            j.tanggal_keberangkatan,
            j.jam_keberangkatan,
            j.harga_perkursi,
            j.kelas_layanan
        FROM booking b
        JOIN jadwal j ON b.jadwal_id = j.jadwal_id
        WHERE b.booking_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $bid);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    if (!$data) {
        abort_alert('Data booking tidak ditemukan!', 'dashboard.php');
    }
    $bookings[] = $data;
    $total_harga += $data['total_harga'];

    // Assume first booking has jadwal_pulang if round-trip
    if (!$jadwal_pulang && intval($data['jadwal_pulang_id'] ?? 0) > 0) {
        $stmt2 = $conn->prepare("SELECT * FROM jadwal WHERE jadwal_id = ? LIMIT 1");
        $stmt2->bind_param("i", $data['jadwal_pulang_id']);
        $stmt2->execute();
        $jadwal_pulang = $stmt2->get_result()->fetch_assoc();
    }
}

// Use first booking for display
$data = $bookings[0];
$booking_id = $data['booking_id'];

// only pending allowed for all bookings
foreach ($bookings as $b) {
    if ($b['status_booking'] !== 'pending') {
        abort_alert('Booking sudah tidak dalam status pending.', 'riwayat.php');
    }
}

// check expired lock: if expired, cancel booking and release seats
if (!is_null($data['seat_locked_until']) && strtotime($data['seat_locked_until']) < time()) {
    // transactionally cancel and release seats
    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("UPDATE booking SET status_booking = 'cancelled' WHERE booking_id = ?");
        $upd->bind_param("i", $booking_id);
        $upd->execute();

        $rel = $conn->prepare("
            UPDATE kursi k
            JOIN booking_seat bs ON k.seat_id = bs.seat_id
            SET k.status = 'tersedia', k.locked_until = NULL
            WHERE bs.booking_id = ?
        ");
        $rel->bind_param("i", $booking_id);
        $rel->execute();

        $conn->commit();
    } catch (Exception $ex) {
        $conn->rollback();
        abort_alert('Gagal memproses timeout booking. Silakan coba lagi.', 'dashboard.php');
    }

    abort_alert('Waktu pembayaran habis. Booking dibatalkan.', 'dashboard.php');
}

// fetch penumpang (read-only) - collect unique passengers from all bookings (avoid duplicates for round-trip)
$penumpang_list = [];
$seen_identitas = [];
foreach ($booking_ids as $bid) {
    $q = $conn->prepare("SELECT nama_penumpang, no_identitas, no_hp, email FROM penumpang WHERE booking_id = ? ORDER BY penumpang_id ASC");
    $q->bind_param("i", $bid);
    $q->execute();
    $res_pen = $q->get_result();
    while ($r = $res_pen->fetch_assoc()) {
        if (!in_array($r['no_identitas'], $seen_identitas)) {
            $penumpang_list[] = $r;
            $seen_identitas[] = $r['no_identitas'];
        }
    }
}

// fetch seats linked to all bookings (to show labels + locked status)
$seat_list = [];
$jumlah_kursi_pergi = 0;
$jumlah_kursi_pulang = 0;
foreach ($booking_ids as $index => $bid) {
    $q2 = $conn->prepare("
        SELECT k.seat_id, k.nomor_kursi, k.status, k.locked_until
        FROM kursi k
        JOIN booking_seat bs ON k.seat_id = bs.seat_id
        WHERE bs.booking_id = ?
        ORDER BY k.nomor_kursi ASC
    ");
    $q2->bind_param("i", $bid);
    $q2->execute();
    $res_seat = $q2->get_result();
    $count = 0;
    while ($r = $res_seat->fetch_assoc()) {
        $seat_list[] = $r;
        $count++;
    }
    if ($index == 0) {
        $jumlah_kursi_pergi = $count;
    } elseif ($index == 1) {
        $jumlah_kursi_pulang = $count;
    }
}

$total_display = "Rp" . number_format($total_harga,0,',','.');

?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pembayaran — GlobeTix</title>
<link rel="stylesheet" href="pembayaran.css">
<style>
  /* E-COMMERCE STYLE: two-column modern layout */
  :root{
    --primary:#0b6b86;
    --muted:#eef7fb;
    --accent:#ff8800;
    --surface:#ffffff;
    --shadow: 0 8px 30px rgba(10,20,30,0.06);
  }
  body{font-family:Inter,system-ui,Arial; background:#f5f7fb; margin:0; color:#223;}
  header{background:var(--surface);border-bottom:1px solid #eef6fa;padding:14px 18px;}
  .brand{max-width:1100px;margin:0 auto;display:flex;align-items:center;gap:14px}
  .brand img{height:36px}
  .container{max-width:1100px;margin:22px auto;padding:18px}
  .stepper{display:flex;justify-content:center;gap:12px;margin-bottom:18px}
  .step{padding:10px 16px;border-radius:10px;background:var(--muted);color:var(--primary);font-weight:700}
  .step.active{background:var(--primary);color:#fff;box-shadow:0 6px 20px rgba(7,50,75,0.12);transform:translateY(-2px)}
  .grid{display:grid;grid-template-columns:1fr 360px;gap:18px}
  .card{background:var(--surface);border-radius:12px;padding:16px;box-shadow:var(--shadow)}
  h2{margin:0 0 8px;color:var(--primary)}
  .muted{color:#667;font-size:14px}
  .seat-list{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
  .chip{padding:6px 10px;border-radius:8px;font-weight:700;border:1px solid #eaeaea;background:#fafafa}
  .chip.locked{background:#cfe6ff;color:#063d4b;border-color:#c2e0ff}
  .chip.tersedia{background:#000;color:#fff;border-color:#000}
  .chip.dipesan{background:#bfc6cc;color:#333}
  .penumpang-table{width:100%;border-collapse:collapse;margin-top:8px}
  .penumpang-table th,.penumpang-table td{padding:10px;border-bottom:1px solid #f1f5f7;text-align:left}
  .right-summary .row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px dashed #eef6fa}
  .summary-label{font-weight:700;}
  .countdown{font-weight:800;color:#c62828;font-size:18px;text-align:center;padding:8px 0}
  .btn-primary{display:inline-block;background:var(--accent);color:#fff;padding:10px 16px;border-radius:8px;border:none;font-weight:800;cursor:pointer}
  .btn-secondary{display:inline-block;background:#f4f6f8;color:var(--primary);padding:8px 12px;border-radius:8px;border:none;cursor:pointer}
  .bank-instr{font-size:14px;margin-top:8px}
  label.inline{display:inline-flex;gap:10px;align-items:center;margin-right:14px}
  input[type=file]{margin-top:8px}
  @media (max-width:980px){ .grid{grid-template-columns:1fr; } .right-summary{order:-1} .brand{padding:0 8px} }
</style>
</head>
<body>
<header>
  <div class="brand">
    <img src="logo.png" alt="GlobeTix">
    <div style="font-weight:800;color:var(--primary)">GlobeTix — Pembayaran</div>
  </div>
</header>

<main class="container">
  <!-- stepper -->
  <div class="stepper">
    <div class="step">1. Pilih Kursi</div>
    <div class="step">2. Isi Penumpang</div>
    <div class="step active">3. Pembayaran</div>
  </div>

  <div class="grid">
    <!-- LEFT: payment form & info -->
    <section class="card">
      <h2>Bayar Sekarang</h2>
      <div class="muted" style="margin-bottom:12px">
        Kode Booking: <strong><?= htmlspecialchars($data['kode_booking']) ?></strong> &nbsp;•&nbsp;
        <?= htmlspecialchars($data['asal']) ?> → <?= htmlspecialchars($data['tujuan']) ?> &nbsp;•&nbsp;
        <?= htmlspecialchars($data['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($data['jam_keberangkatan']) ?>
        <?php if ($jadwal_pulang): ?>
          &nbsp;•&nbsp; Pulang: <?= htmlspecialchars($jadwal_pulang['asal']) ?> → <?= htmlspecialchars($jadwal_pulang['tujuan']) ?> • <?= htmlspecialchars($jadwal_pulang['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($jadwal_pulang['jam_keberangkatan']) ?>
        <?php endif; ?>
      </div>

      <div style="margin-top:12px">
        <div style="font-weight:700;margin-bottom:6px">Metode Pembayaran</div>
        <label class="inline"><input type="radio" name="metode" value="transfer_bank" form="payForm" checked onchange="toggleQrisImage()"> Transfer Bank</label>
        <label class="inline"><input type="radio" name="metode" value="qris" form="payForm" onchange="toggleQrisImage()"> QRIS</label>
      </div>

      <form id="payForm" action="konfirmasi_pembayaran.php" method="post" enctype="multipart/form-data" style="margin-top:14px">
        <input type="hidden" name="booking_ids" value="<?= htmlspecialchars($booking_ids_str) ?>">

        <!-- read-only penumpang -->
        <div style="margin-top:6px">
          <div style="font-weight:700;margin-bottom:8px">Penumpang</div>
          <table class="penumpang-table" aria-readonly="true">
            <thead>
              <tr><th>No</th><th>Nama</th><th>Identitas</th><th>HP</th></tr>
            </thead>
            <tbody>
              <?php foreach ($penumpang_list as $i => $p): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($p['nama_penumpang']) ?></td>
                  <td><?= htmlspecialchars($p['no_identitas']) ?></td>
                  <td><?= htmlspecialchars($p['no_hp']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- selected seats (read-only) -->
        <div style="margin-top:12px">
          <div style="font-weight:700;margin-bottom:8px">Kursi Terpilih</div>
          <div class="seat-list" aria-live="polite">
            <?php foreach ($seat_list as $s):
                $cls = $s['status'];
                $label = htmlspecialchars($s['nomor_kursi']);
                $chip = ($cls === 'locked') ? 'locked' : (($cls === 'dipesan') ? 'dipesan' : (($cls === 'tersedia') ? 'tersedia' : 'tersedia'));
            ?>
              <div class="chip <?= $chip ?>"><?= $label ?><?php if($cls==='locked') echo ' · Terkunci'; elseif($cls==='dipesan') echo ' · Dipesan'; ?></div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- upload proof -->
        <div style="margin-top:14px">
          <div id="upload-label" style="font-weight:700;margin-bottom:6px">Upload Bukti Transfer (JPG/PNG/PDF - max 5MB)</div>
          <input type="file" name="bukti_tf" id="bukti_tf" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>

        <div id="bank-instr" class="bank-instr card" style="margin-top:14px">
          <div style="font-weight:700">Instruksi - Transfer Bank</div>
          <ul style="margin:8px 0 0 18px;padding:0;">
            <li>BCA: 1234567890 (a.n. PT GlobeTix Travel)</li>
            <li>MANDIRI: 0987654321 (a.n. PT GlobeTix Travel)</li>
          </ul>
          <div style="margin-top:8px;font-size:13px;color:#556">Pastikan jumlah transfer sesuai <strong><?= $total_display ?></strong>. Upload bukti untuk melanjutkan proses verifikasi.</div>
        </div>

        <div id="qris-instr" class="bank-instr card" style="margin-top:14px;display:none;">
          <div style="font-weight:700">Instruksi - QRIS</div>
          <img src="qris.jpg" alt="QRIS Code" style="width:200px;height:auto;margin-top:8px;">
          <div style="margin-top:8px;font-size:13px;color:#556">Scan QRIS di atas menggunakan aplikasi e-wallet Anda. Pastikan jumlah transfer sesuai <strong><?= $total_display ?></strong>. Upload bukti untuk melanjutkan proses verifikasi.</div>
        </div>

        <div style="margin-top:14px">
          <button type="submit" class="btn-primary">Kirim Bukti Pembayaran &amp; Minta Verifikasi</button>
          <button type="button" class="btn-secondary" onclick="document.getElementById('cancelForm').submit()" style="margin-left:8px">Batal</button>
        </div>
      </form>

      <!-- Hidden form for cancel action -->
      <form id="cancelForm" action="" method="post" style="display:none;">
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="booking_ids" value="<?= htmlspecialchars($booking_ids_str) ?>">
      </form>
    </section>

    <!-- RIGHT: summary & countdown -->
    <aside class="right-summary card">
      <div style="font-weight:700;font-size:18px">Ringkasan Pemesanan</div>
      <div class="summary-label" style="margin-top:12px">Jumlah Penumpang</div>
      <div><?= count($penumpang_list) ?></div>
      <?php if ($jadwal_pulang): ?>
        <div class="summary-label" style="margin-top:12px">Rute Pergi</div>
        <div><?= htmlspecialchars($data['asal']) ?> → <?= htmlspecialchars($data['tujuan']) ?></div>
        <div class="summary-label" style="margin-top:12px">Tanggal / Jam</div>
        <div><?= htmlspecialchars($data['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($data['jam_keberangkatan']) ?></div>
        <div class="summary-label" style="margin-top:12px">Rute Pulang</div>
        <div><?= htmlspecialchars($jadwal_pulang['asal']) ?> → <?= htmlspecialchars($jadwal_pulang['tujuan']) ?></div>
        <div class="summary-label" style="margin-top:12px">Tanggal / Jam</div>
        <div><?= htmlspecialchars($jadwal_pulang['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($jadwal_pulang['jam_keberangkatan']) ?></div>
        <div class="summary-label" style="margin-top:12px">Jumlah Kursi</div>
        <div><?= count($seat_list) ?></div>
        <div class="summary-label" style="margin-top:12px">Harga / Kursi Pergi</div>
        <div>Rp<?= number_format($data['harga_perkursi'],0,',','.') ?></div>
        <div class="summary-label" style="margin-top:12px">Harga / Kursi Pulang</div>
        <div>Rp<?= number_format($jadwal_pulang['harga_perkursi'],0,',','.') ?></div>
      <?php else: ?>
        <div class="summary-label" style="margin-top:12px">Rute</div>
        <div><?= htmlspecialchars($data['asal']) ?> → <?= htmlspecialchars($data['tujuan']) ?></div>
        <div class="summary-label" style="margin-top:12px">Tanggal / Jam</div>
        <div><?= htmlspecialchars($data['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($data['jam_keberangkatan']) ?></div>
        <div class="summary-label" style="margin-top:12px">Kelas Layanan</div>
        <div><?= htmlspecialchars($data['kelas_layanan']) ?></div>
        <div class="summary-label" style="margin-top:12px">Jumlah Kursi</div>
        <div><?= count($seat_list) ?></div>
        <div class="summary-label" style="margin-top:12px">Harga / Kursi</div>
        <div>Rp<?= number_format($data['harga_perkursi'],0,',','.') ?></div>
      <?php endif; ?>
      <div style="margin-top:12px;font-weight:800">Total</div>
      <div><?= $total_display ?></div>

      <div style="margin-top:12px">
        <div style="font-weight:700;margin-bottom:6px">Waktu sisa kunci kursi</div>
        <div class="countdown" id="countdown">--:--</div>
      </div>

      <div style="margin-top:10px;font-size:13px;color:#556">Catatan: Jika tidak mengunggah bukti dalam waktu yang diberikan, booking akan dibatalkan otomatis dan kursi dikembalikan.</div>
    </aside>
  </div>
</main>

<script>
// countdown client-side based on server seat_locked_until
const countdownEl = document.getElementById('countdown');
const lockedUntilStr = <?= json_encode($data['seat_locked_until'] ?? '') ?>;
let lockedUntil = lockedUntilStr ? new Date(lockedUntilStr) : null;

function pad(n){return String(n).padStart(2,'0');}
function updateCountdown(){
  if (!lockedUntil) { countdownEl.textContent = '--:--'; return; }
  const now = new Date();
  let diff = Math.floor((lockedUntil - now) / 1000);
  if (diff <= 0) {
    countdownEl.textContent = "00:00";
    alert("Waktu pembayaran habis. Booking dibatalkan.");
    // redirect to dashboard
    window.location = "dashboard.php";
    return;
  }
  const m = Math.floor(diff/60);
  const s = diff % 60;
  countdownEl.textContent = pad(m) + ':' + pad(s);
}
updateCountdown();
setInterval(updateCountdown, 1000);

// optional: client-side validate file size (prevent big uploads)
const buktiEl = document.getElementById('bukti_tf');
if (buktiEl) {
  document.getElementById('payForm').addEventListener('submit', function(e){
    const f = buktiEl.files[0];
    if (!f) { alert('Silakan unggah bukti pembayaran.'); e.preventDefault(); return; }
    const max = 5 * 1024 * 1024;
    if (f.size > max) { alert('Ukuran file terlalu besar. Maksimum 5 MB.'); e.preventDefault(); return; }
  });
}

// Toggle QRIS image display
function toggleQrisImage() {
  const qrisRadio = document.querySelector('input[name="metode"][value="qris"]');
  const bankInstr = document.getElementById('bank-instr');
  const qrisInstr = document.getElementById('qris-instr');
  const uploadLabel = document.getElementById('upload-label');
  if (qrisRadio.checked) {
    bankInstr.style.display = 'none';
    qrisInstr.style.display = 'block';
    uploadLabel.textContent = 'Upload Bukti Pembayaran (JPG/PNG/PDF - max 5MB)';
  } else {
    bankInstr.style.display = 'block';
    qrisInstr.style.display = 'none';
    uploadLabel.textContent = 'Upload Bukti Transfer (JPG/PNG/PDF - max 5MB)';
  }
}

// Initial toggle on page load
toggleQrisImage();

// Confirmation on page close before upload
let formSubmitted = false;
document.getElementById('payForm').addEventListener('submit', () => { formSubmitted = true; });
document.getElementById('cancelForm').addEventListener('submit', () => { formSubmitted = true; });

window.addEventListener('beforeunload', (e) => {
  if (!formSubmitted) {
    e.preventDefault();
    e.returnValue = 'Apakah Anda yakin ingin meninggalkan halaman ini? Pembayaran belum selesai dan booking akan dibatalkan.';
  }
});
</script>
</body>
</html>
