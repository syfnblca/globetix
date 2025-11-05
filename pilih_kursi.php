<?php
// pilih_kursi.php (FINAL auto-layout horizontal A1,B1,C1,D1 ...)
// Requirements: session_start(); include 'db.php' -> provides $conn (mysqli).
// CSS: existing pilih_kursi.css; add .seat.selected snippet (see below).
// Behavior: multi-select up to MAX_SEATS_PER_BOOKING, POST to detail_pemesanan.php.

session_start();
include 'db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: masuk.php");
    exit();
}

// config
define('LOCK_MINUTES', 10); // informational only

// cleanup expired locks (safety)
$conn->query("
    UPDATE kursi
    SET status = 'tersedia', locked_until = NULL
    WHERE status = 'dipesan' AND locked_until IS NOT NULL AND locked_until < NOW()
");

// validate jadwal
if (!isset($_REQUEST['jadwal_id'])) {
    echo "<script>alert('Jadwal tidak ditemukan!'); window.location='dashboard.php';</script>";
    exit;
}
$jadwal_id = intval($_REQUEST['jadwal_id']);
$jadwal_pulang_id = isset($_REQUEST['jadwal_pulang_id']) ? intval($_REQUEST['jadwal_pulang_id']) : null;
$trip = $_REQUEST['trip'] ?? 'sekali';
$penumpang = intval($_REQUEST['penumpang'] ?? 1);
$penumpang_pulang = intval($_REQUEST['penumpang_pulang'] ?? $penumpang);

// fetch jadwal info
$stmt = $conn->prepare("SELECT * FROM jadwal WHERE jadwal_id = ? LIMIT 1");
$stmt->bind_param("i", $jadwal_id);
$stmt->execute();
$jadwal = $stmt->get_result()->fetch_assoc();
if (!$jadwal) {
    echo "<script>alert('Jadwal tidak ditemukan di database.'); window.location='dashboard.php';</script>";
    exit;
}

$jadwal_pulang = null;
if ($trip == 'pp' && $jadwal_pulang_id) {
    $stmt2 = $conn->prepare("SELECT * FROM jadwal WHERE jadwal_id = ? LIMIT 1");
    $stmt2->bind_param("i", $jadwal_pulang_id);
    $stmt2->execute();
    $jadwal_pulang = $stmt2->get_result()->fetch_assoc();
    if (!$jadwal_pulang) {
        echo "<script>alert('Jadwal pulang tidak ditemukan di database.'); window.location='dashboard.php';</script>";
        exit;
    }
} elseif ($trip == 'pp' && !$jadwal_pulang_id) {
    $trip = 'sekali'; // Fallback to one-way if return schedule not provided
}

// Function to fetch seats for a jadwal
function fetchSeats($conn, $jadwal_id) {
    $q = $conn->prepare("
        SELECT seat_id, nomor_kursi, status, locked_until,
          CASE
            WHEN status = 'terjual' THEN 'dipesan'
            WHEN status = 'dibatalkan' THEN 'tersedia'
            WHEN locked_until IS NOT NULL AND locked_until > NOW() THEN 'locked'
            ELSE 'tersedia'
          END AS current_status
        FROM kursi
        WHERE jadwal_id = ?
    ");
    $q->bind_param("i", $jadwal_id);
    $q->execute();
    $res = $q->get_result();

    $seats = [];
    $letters_set = [];
    $numbers_set = [];
    while ($r = $res->fetch_assoc()) {
        $nomor = $r['nomor_kursi'];
        $letter = preg_replace('/[^A-Za-z]/', '', $nomor);
        $number = intval(preg_replace('/[^0-9]/', '', $nomor));
        if ($letter === '') $letter = 'A';
        $r['_letter'] = strtoupper($letter);
        $r['_number'] = $number;
        $seats[] = $r;
        $letters_set[$r['_letter']] = true;
        $numbers_set[$number] = true;
    }

    $letters = array_keys($letters_set);
    sort($letters, SORT_STRING);
    $numbers = array_keys($numbers_set);
    sort($numbers, SORT_NUMERIC);

    $seat_lookup = [];
    foreach ($seats as $s) {
        $key = $s['_letter'] . '_' . $s['_number'];
        $seat_lookup[$key] = $s;
    }

    $total_letters = count($letters);
    $left_count = (int)ceil($total_letters / 2);
    $left_letters = array_slice($letters, 0, $left_count);
    $right_letters = array_slice($letters, $left_count);

    return [
        'seats' => $seats,
        'letters' => $letters,
        'numbers' => $numbers,
        'seat_lookup' => $seat_lookup,
        'left_letters' => $left_letters,
        'right_letters' => $right_letters
    ];
}

// Fetch for pergi
$pergi_data = fetchSeats($conn, $jadwal_id);

// Fetch for pulang if pp
$pulang_data = null;
if ($trip == 'pp') {
    $pulang_data = fetchSeats($conn, $jadwal_pulang_id);
}

// If there are less than 2 columns per side, still works (auto adapts).

// HTML output
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Pilih Kursi</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="pilih_kursi.css">
  <style>
    /* small inline styles to ensure layout bus-like horizontal order + step/tab consistent */
    body { background: #ffffff; }
    .steps { display:flex; gap:12px; justify-content:center; margin:18px 0; }
    .steps .step { padding:10px 18px; border-radius:10px; background:#eef7fb; color:#0b6b86; font-weight:700; }
    .steps .step.active { background:#0b6b86; color:#fff; box-shadow: 0 6px 20px rgba(7,50,75,0.12); transform:translateY(-2px); }
    .info-line { text-align:center; margin-bottom:8px; color:#334; }
    #counterPergi, #counterPulang { text-align:center; margin:8px 0 14px; font-weight:800; color:#0b6b86; }
    .layout-seat { max-width:980px; margin:0 auto; padding:10px; }
    .seat-map { margin-top:6px; }
    .bus-row { display:flex; align-items:center; justify-content:center; gap:18px; margin-bottom:12px; }
    .group-side { display:flex; gap:8px; align-items:center; }
    .aisle { width:48px; } /* lorong tengah */
    .seat { min-width:46px; height:46px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; user-select:none; font-weight:700; }
    .seat.tersedia { background:#000; color:#fff; border:1px solid rgba(0,0,0,0.12); }
    .seat.dipesan { background:#bfc6cc; color:#333; cursor:not-allowed; }
    .seat.locked { background:#cfe6ff; color:#063d4b; cursor:not-allowed; }
    .seat.selected { background:#ff8800 !important; color:#fff !important; border:1px solid #e69500 !important; }
    .legend { text-align:center; margin-top:12px; color:#556; }
    .pesan-wrap { text-align:center; margin-top:14px; }
    #btnPesan { padding:10px 16px; border-radius:8px; border:none; background:#0b6b86; color:#fff; font-weight:700; cursor:pointer; }
    #btnPesan:disabled { opacity:0.6; cursor:not-allowed; }
    @media (max-width:760px){ .aisle{width:20px;} .seat{min-width:38px;height:38px;} .steps{flex-wrap:wrap;} }
  </style>
</head>
<body>
  <header style="background:#fff;padding:10px 12px;border-bottom:1px solid #eef6fa;">
    <div style="max-width:980px;margin:0 auto;display:flex;align-items:center;gap:12px;">
      <img src="logo.png" alt="logo" style="height:40px;">
      <div style="font-weight:800;color:#063d4b;">GlobeTix</div>
    </div>
  </header>

  <div class="steps" style="max-width:980px;margin:18px auto;">
    <div class="step active">1. Pilih Kursi</div>
    <div class="step">2. Isi Penumpang</div>
    <div class="step">3. Pembayaran</div>
  </div>

  <div style="max-width:980px;margin:0 auto;">
    <?php if ($trip == 'pp'): ?>
      <div class="tabs" style="display:flex;justify-content:center;margin-bottom:12px;">
        <button id="tabPergi" class="tab-btn active" style="padding:8px 16px;border-radius:8px 0 0 8px;border:none;background:#0b6b86;color:#fff;font-weight:700;">Pergi</button>
        <button id="tabPulang" class="tab-btn" style="padding:8px 16px;border-radius:0 8px 8px 0;border:none;background:#eef7fb;color:#0b6b86;font-weight:700;">Pulang</button>
      </div>
    <?php endif; ?>

    <div id="infoPergi" class="info-line">
      <strong><?= htmlspecialchars($jadwal['asal']) ?> → <?= htmlspecialchars($jadwal['tujuan']) ?></strong>
      &nbsp;•&nbsp; <?= htmlspecialchars($jadwal['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($jadwal['jam_keberangkatan']) ?>
      &nbsp;•&nbsp; Pilih <?= $penumpang ?> kursi
    </div>

    <?php if ($trip == 'pp'): ?>
      <div id="infoPulang" class="info-line" style="display:none;">
        <strong><?= htmlspecialchars($jadwal_pulang['asal']) ?> → <?= htmlspecialchars($jadwal_pulang['tujuan']) ?></strong>
        &nbsp;•&nbsp; <?= htmlspecialchars($jadwal_pulang['tanggal_keberangkatan']) ?> • <?= htmlspecialchars($jadwal_pulang['jam_keberangkatan']) ?>
        &nbsp;•&nbsp; Pilih <?= $penumpang_pulang ?> kursi
      </div>
    <?php endif; ?>

    <div id="countdownCenter" style="text-align:center;font-weight:700;color:#c24444;margin-bottom:6px;">
      <span>Waktu kunci akan aktif setelah Anda mengisi data penumpang</span>
    </div>

    <div id="counterPergi">✅ Kursi pergi terpilih: 0 dari <?= $penumpang ?></div>
    <?php if ($trip == 'pp'): ?>
      <div id="counterPulang" style="display:none;">✅ Kursi pulang terpilih: 0 dari <?= $penumpang_pulang ?></div>
    <?php endif; ?>
  </div>

  <main class="layout-seat">
    <div id="seatMapPergi" class="seat-map" role="application" aria-label="Peta kursi bus pergi">
      <?php
        // Render pergi seats
        $data = $pergi_data;
        $numbers = $data['numbers'];
        $left_letters = $data['left_letters'];
        $right_letters = $data['right_letters'];
        $seat_lookup = $data['seat_lookup'];
        foreach ($numbers as $num) {
            echo '<div class="bus-row" aria-label="Baris ' . htmlspecialchars($num) . '">';
            // left side
            echo '<div class="group-side left-side">';
            foreach ($left_letters as $L) {
                $key = $L . '_' . $num;
                if (isset($seat_lookup[$key])) {
                    $s = $seat_lookup[$key];
                    $cls = ($s['current_status'] === 'tersedia') ? 'tersedia' : (($s['current_status'] === 'locked') ? 'locked' : 'dipesan');
                    echo '<div class="seat pergi ' . $cls . '" role="' . ($cls==='tersedia'?'button':'img') . '" tabindex="' . ($cls==='tersedia'?'0':'-1') . '" data-seat-id="' . htmlspecialchars($s['seat_id']) . '" data-seat-number="' . htmlspecialchars($s['nomor_kursi']) . '">' . htmlspecialchars($s['nomor_kursi']) . '</div>';
                } else {
                    echo '<div style="min-width:46px;height:46px;"></div>';
                }
            }
            echo '</div>'; // left-side

            // aisle
            echo '<div class="aisle" aria-hidden="true"></div>';

            // right side
            echo '<div class="group-side right-side">';
            foreach ($right_letters as $R) {
                $key = $R . '_' . $num;
                if (isset($seat_lookup[$key])) {
                    $s = $seat_lookup[$key];
                    $cls = ($s['current_status'] === 'tersedia') ? 'tersedia' : (($s['current_status'] === 'locked') ? 'locked' : 'dipesan');
                    echo '<div class="seat pergi ' . $cls . '" role="' . ($cls==='tersedia'?'button':'img') . '" tabindex="' . ($cls==='tersedia'?'0':'-1') . '" data-seat-id="' . htmlspecialchars($s['seat_id']) . '" data-seat-number="' . htmlspecialchars($s['nomor_kursi']) . '">' . htmlspecialchars($s['nomor_kursi']) . '</div>';
                } else {
                    echo '<div style="min-width:46px;height:46px;"></div>';
                }
            }
            echo '</div>'; // right-side

            echo '</div>'; // bus-row
        }
      ?>
    </div>

    <?php if ($trip == 'pp'): ?>
      <div id="seatMapPulang" class="seat-map" role="application" aria-label="Peta kursi bus pulang" style="display:none;">
        <?php
          // Render pulang seats
          $data = $pulang_data;
          $numbers = $data['numbers'];
          $left_letters = $data['left_letters'];
          $right_letters = $data['right_letters'];
          $seat_lookup = $data['seat_lookup'];
          foreach ($numbers as $num) {
              echo '<div class="bus-row" aria-label="Baris ' . htmlspecialchars($num) . '">';
              // left side
              echo '<div class="group-side left-side">';
              foreach ($left_letters as $L) {
                  $key = $L . '_' . $num;
                  if (isset($seat_lookup[$key])) {
                      $s = $seat_lookup[$key];
                      $cls = ($s['current_status'] === 'tersedia') ? 'tersedia' : (($s['current_status'] === 'locked') ? 'locked' : 'dipesan');
                      echo '<div class="seat pulang ' . $cls . '" role="' . ($cls==='tersedia'?'button':'img') . '" tabindex="' . ($cls==='tersedia'?'0':'-1') . '" data-seat-id="' . htmlspecialchars($s['seat_id']) . '" data-seat-number="' . htmlspecialchars($s['nomor_kursi']) . '">' . htmlspecialchars($s['nomor_kursi']) . '</div>';
                  } else {
                      echo '<div style="min-width:46px;height:46px;"></div>';
                  }
              }
              echo '</div>'; // left-side

              // aisle
              echo '<div class="aisle" aria-hidden="true"></div>';

              // right side
              echo '<div class="group-side right-side">';
              foreach ($right_letters as $R) {
                  $key = $R . '_' . $num;
                  if (isset($seat_lookup[$key])) {
                      $s = $seat_lookup[$key];
                      $cls = ($s['current_status'] === 'tersedia') ? 'tersedia' : (($s['current_status'] === 'locked') ? 'locked' : 'dipesan');
                      echo '<div class="seat pulang ' . $cls . '" role="' . ($cls==='tersedia'?'button':'img') . '" tabindex="' . ($cls==='tersedia'?'0':'-1') . '" data-seat-id="' . htmlspecialchars($s['seat_id']) . '" data-seat-number="' . htmlspecialchars($s['nomor_kursi']) . '">' . htmlspecialchars($s['nomor_kursi']) . '</div>';
                  } else {
                      echo '<div style="min-width:46px;height:46px;"></div>';
                  }
              }
              echo '</div>'; // right-side

              echo '</div>'; // bus-row
          }
        ?>
      </div>
    <?php endif; ?>

    <div class="legend" style="max-width:980px;margin:8px auto;">
      <span class="item"><span class="box hitam"></span> Tersedia</span>
      <span class="item"><span class="box biru"></span> Terkunci</span>
      <span class="item"><span class="box abu"></span> Sudah dipesan</span>
      <span class="item"><span class="box orange"></span> Dipilih</span>
    </div>

    <div class="pesan-wrap">
      <button id="btnPesan" disabled>Pesan Kursi</button>
    </div>

    <div style="max-width:980px;margin:10px auto;text-align:center;color:#556;font-size:13px;">
      <em>Catatan: kunci kursi akan disimpan selama <?= LOCK_MINUTES ?> menit setelah Anda mengisi data penumpang pada halaman berikut.</em>
    </div>
  </main>

<script>
(function(){
  'use strict';
  const MAX_SEATS = <?= json_encode($penumpang) ?>;
  const MAX_SEATS_PULANG = <?= json_encode($penumpang_pulang) ?>;
  const trip = <?= json_encode($trip) ?>;
  const selectedPergi = new Set();
  const selectedPulang = new Set();
  const counterPergiEl = document.getElementById('counterPergi');
  const counterPulangEl = document.getElementById('counterPulang');
  const btnPesan = document.getElementById('btnPesan');

  function refreshCounter() {
    if (counterPergiEl) {
      counterPergiEl.textContent = `✅ Kursi pergi terpilih: ${selectedPergi.size} dari ${MAX_SEATS}`;
    }
    if (counterPulangEl) {
      counterPulangEl.textContent = `✅ Kursi pulang terpilih: ${selectedPulang.size} dari ${MAX_SEATS_PULANG}`;
    }
    const totalSelected = selectedPergi.size + selectedPulang.size;
    if (trip === 'pp') {
      btnPesan.disabled = (selectedPergi.size !== parseInt(MAX_SEATS) || selectedPulang.size !== parseInt(MAX_SEATS_PULANG));
    } else {
      btnPesan.disabled = (selectedPergi.size !== parseInt(MAX_SEATS));
    }
  }

  function isClickable(el) {
    return el.classList.contains('tersedia');
  }

  // Tab switching for round-trip
  if (trip === 'pp') {
    const tabPergi = document.getElementById('tabPergi');
    const tabPulang = document.getElementById('tabPulang');
    const seatMapPergi = document.getElementById('seatMapPergi');
    const seatMapPulang = document.getElementById('seatMapPulang');
    const infoPergi = document.getElementById('infoPergi');
    const infoPulang = document.getElementById('infoPulang');

    tabPergi.addEventListener('click', function(){
      tabPergi.classList.add('active');
      tabPulang.classList.remove('active');
      seatMapPergi.style.display = 'block';
      seatMapPulang.style.display = 'none';
      infoPergi.style.display = 'block';
      infoPulang.style.display = 'none';
      counterPergiEl.style.display = 'block';
      counterPulangEl.style.display = 'none';
    });

    tabPulang.addEventListener('click', function(){
      tabPulang.classList.add('active');
      tabPergi.classList.remove('active');
      seatMapPulang.style.display = 'block';
      seatMapPergi.style.display = 'none';
      infoPulang.style.display = 'block';
      infoPergi.style.display = 'none';
      counterPulangEl.style.display = 'block';
      counterPergiEl.style.display = 'none';
    });
  }

  // Seat selection
  const seats = Array.from(document.querySelectorAll('.seat'));
  seats.forEach(function(el){
    if (!isClickable(el)) {
      el.setAttribute('aria-disabled', 'true');
      el.style.cursor = 'not-allowed';
      return;
    }

    el.addEventListener('click', function(){
      const sid = el.dataset.seatId;
      if (!sid) return;
      const isPergi = el.classList.contains('pergi');
      const selectedSet = isPergi ? selectedPergi : selectedPulang;
      const maxSeats = isPergi ? MAX_SEATS : MAX_SEATS_PULANG;

      if (selectedSet.has(sid)) {
        selectedSet.delete(sid);
        el.classList.remove('selected');
        el.setAttribute('aria-pressed','false');
      } else {
        if (selectedSet.size >= maxSeats) {
          alert('Anda harus memilih tepat ' + maxSeats + ' kursi untuk perjalanan ini.');
          return;
        }
        selectedSet.add(sid);
        el.classList.add('selected');
        el.setAttribute('aria-pressed','true');
      }
      refreshCounter();
    });

    el.addEventListener('keydown', function(ev){
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        el.click();
      }
    });
  });

  // POST helper
  function postTo(url, params) {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = url;
    form.style.display = 'none';
    for (const k in params) {
      if (!Object.prototype.hasOwnProperty.call(params, k)) continue;
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = k;
      input.value = params[k];
      form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
  }

  btnPesan.addEventListener('click', function(){
    const totalSelected = selectedPergi.size + selectedPulang.size;
    if (totalSelected === 0) {
      alert('Pilih kursi terlebih dahulu.');
      return;
    }
    if (trip === 'pp' && (selectedPergi.size === 0 || selectedPulang.size === 0)) {
      alert('Pilih kursi untuk pergi dan pulang.');
      return;
    }
    const kursiPergiStr = Array.from(selectedPergi).join(',');
    const kursiPulangStr = Array.from(selectedPulang).join(',');
    postTo('detail_pemesanan.php', {
      jadwal_id: <?= json_encode($jadwal_id) ?>,
      jadwal_pulang_id: <?= json_encode($jadwal_pulang_id) ?>,
      kursi: kursiPergiStr,
      kursi_pulang: kursiPulangStr,
      penumpang: selectedPergi.size,
      penumpang_pulang: selectedPulang.size,
      trip: <?= json_encode($trip) ?>
    });
  });

  // init
  refreshCounter();
})();
</script>
</body>
</html>
