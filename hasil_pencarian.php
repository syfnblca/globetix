<?php
session_start();
include 'db.php';

$trip = $_GET['trip'] ?? 'sekali';
$selected_jadwal_id = $_GET['selected_jadwal_id'] ?? null;
$selected_kursi = $_GET['selected_kursi'] ?? null;

if ($trip == 'sekali') {
  if (!isset($_GET['asal']) || !isset($_GET['tujuan']) || !isset($_GET['tanggal'])) {
    echo "<script>alert('Lengkapi form pencarian!'); window.location='dashboard.php';</script>";
    exit;
  }
  $asal = $_GET['asal'];
  $tujuan = $_GET['tujuan'];
  $tanggal = $_GET['tanggal'];
  $kelas = $_GET['kelas'] ?? '';
  $penumpang = intval($_GET['penumpang'] ?? 1);
} elseif ($trip == 'pp') {
  if (!isset($_GET['asal']) || !isset($_GET['tujuan']) || !isset($_GET['tanggal']) ||
      !isset($_GET['asal_pulang']) || !isset($_GET['tujuan_pulang']) || !isset($_GET['tanggal_pulang'])) {
    echo "<script>alert('Lengkapi form pencarian pulang pergi!'); window.location='dashboard.php';</script>";
    exit;
  }
  // Pergi
  $asal = $_GET['asal'];
  $tujuan = $_GET['tujuan'];
  $tanggal = $_GET['tanggal'];
  $kelas = $_GET['kelas'] ?? '';
  $penumpang = intval($_GET['penumpang'] ?? 1);
  // Pulang
  $asal_pulang = $_GET['asal_pulang'];
  $tujuan_pulang = $_GET['tujuan_pulang'];
  $tanggal_pulang = $_GET['tanggal_pulang'];
  $kelas_pulang = $_GET['kelas_pulang'] ?? '';
  $penumpang_pulang = intval($_GET['penumpang_pulang'] ?? $penumpang);
}

// Validate that origin and destination are not the same
if ($asal == $tujuan) {
  echo "<script>alert('Asal dan tujuan tidak boleh sama!'); window.location='dashboard.php';</script>";
  exit;
}
if ($trip == 'pp' && $asal_pulang == $tujuan_pulang) {
  echo "<script>alert('Asal dan tujuan pulang tidak boleh sama!'); window.location='dashboard.php';</script>";
  exit;
}

// Validate date restrictions: one-way up to 10 days, round-trip up to 20 days
$today = date('Y-m-d');
$max_date_oneway = date('Y-m-d', strtotime('+10 days'));
$max_date_roundtrip = date('Y-m-d', strtotime('+20 days'));

if (strtotime($tanggal) < strtotime($today) || strtotime($tanggal) > strtotime($max_date_oneway)) {
  echo "<script>alert('Tanggal keberangkatan harus antara hari ini dan 10 hari ke depan.'); window.location='dashboard.php';</script>";
  exit;
}
if ($trip == 'pp') {
  if (strtotime($tanggal_pulang) < strtotime($today) || strtotime($tanggal_pulang) > strtotime($max_date_roundtrip)) {
    echo "<script>alert('Tanggal pulang harus antara hari ini dan 20 hari ke depan.'); window.location='dashboard.php';</script>";
    exit;
  }
}

// Fungsi untuk query jadwal
function getJadwal($conn, $asal, $tujuan, $tanggal, $kelas, $penumpang) {
  $params = [$asal, $tujuan, $tanggal];
  $types = "sss";

  $sql = "
  SELECT j.*,
         COUNT(k.seat_id) AS kursi_tersedia
  FROM jadwal j
  LEFT JOIN kursi k
    ON j.jadwal_id = k.jadwal_id
    AND k.status = 'tersedia'
  WHERE j.asal = ?
    AND j.tujuan = ?
    AND j.tanggal_keberangkatan = ?
    AND j.status = 'tersedia'
  ";

  // Filter kelas (jika dipilih)
  if (!empty($kelas)) {
    $sql .= " AND j.kelas_layanan = ?";
    $params[] = $kelas;
    $types .= "s";
  }

  $sql .= "
  GROUP BY j.jadwal_id
  HAVING kursi_tersedia >= ?
  ";

  // Tambahkan jumlah penumpang
  $params[] = $penumpang;
  $types .= "i";

  $stmt = $conn->prepare($sql);
  if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
  }

  $bind_names = [];
  $bind_names[] = $types;
  for ($i = 0; $i < count($params); $i++) {
    $bind_names[] = &$params[$i];
  }
  call_user_func_array([$stmt, 'bind_param'], $bind_names);

  $stmt->execute();
  return $stmt->get_result();
}

// Query jadwal pergi
$result_pergi = getJadwal($conn, $asal, $tujuan, $tanggal, $kelas, $penumpang);

// Query jadwal pulang jika pulang pergi
$result_pulang = null;
if ($trip == 'pp') {
  $result_pulang = getJadwal($conn, $asal_pulang, $tujuan_pulang, $tanggal_pulang, $kelas_pulang, $penumpang_pulang);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Hasil Pencarian - GlobeTix</title>
  <link rel="stylesheet" href="hasil_pencarian.css">
</head>
<body>

  <!-- Header -->
  <header>
    <div class="logo">
      <img src="logo.png" alt="Logo GlobeTix">
    </div>
    <nav class="nav-menu">
      <a href="dashboard.php">Beranda</a>
      <a href="riwayat.php">Riwayat</a>
      <a href="profil.php">Profil</a>
      <a href="keluar.php">Keluar</a>
    </nav>
  </header>

  <!-- Hero / Judul -->
  <section class="hero">
    <h2>Hasil pencarian tujuan <?= htmlspecialchars($asal) ?> - <?= htmlspecialchars($tujuan) ?> <?= !empty($kelas) ? "kelas ".ucfirst($kelas) : "" ?></h2>
  </section>

  <!-- Hasil pencarian -->
  <section class="search-results">
    <?php if ($trip == 'sekali'): ?>
      <!-- Jadwal Pergi -->
      <h3>Jadwal Pergi</h3>
      <?php if ($result_pergi && $result_pergi->num_rows > 0): ?>
        <?php while($row = $result_pergi->fetch_assoc()): ?>
          <div class="result-card">
            <h4><?= $row['asal']; ?> - <?= $row['tujuan']; ?></h4>
            <p><?= date('d F Y', strtotime($row['tanggal_keberangkatan'])); ?> | <?= $row['jam_keberangkatan']; ?></p>
            <p><?= ucfirst($row['kelas_layanan']); ?> | Durasi: <?= $row['estimasi_durasi']; ?></p>
            <p><strong>Rp<?= number_format($row['harga_perkursi'],0,',','.'); ?></strong></p>
            <a href="pilih_kursi.php?jadwal_id=<?= $row['jadwal_id']; ?>&penumpang=<?= $penumpang; ?>&trip=<?= $trip; ?>" class="btn-pesan">Pilih</a>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="text-align:center; margin-top:30px;">Tidak ada jadwal pergi ditemukan.</p>
      <?php endif; ?>
    <?php elseif ($trip == 'pp'): ?>
      <form action="pilih_kursi.php" method="get">
        <input type="hidden" name="trip" value="<?= $trip; ?>">
        <input type="hidden" name="penumpang" value="<?= $penumpang; ?>">
        <input type="hidden" name="penumpang_pulang" value="<?= $penumpang_pulang; ?>">

        <!-- Jadwal Pergi -->
        <h3>Pilih Jadwal Pergi</h3>
        <?php if ($result_pergi && $result_pergi->num_rows > 0): ?>
          <?php while($row = $result_pergi->fetch_assoc()): ?>
            <div class="result-card">
              <input type="radio" name="jadwal_id" value="<?= $row['jadwal_id']; ?>" required>
              <h4><?= $row['asal']; ?> - <?= $row['tujuan']; ?></h4>
              <p><?= date('d F Y', strtotime($row['tanggal_keberangkatan'])); ?> | <?= $row['jam_keberangkatan']; ?></p>
              <p><?= ucfirst($row['kelas_layanan']); ?> | Durasi: <?= $row['estimasi_durasi']; ?></p>
              <p><strong>Rp<?= number_format($row['harga_perkursi'],0,',','.'); ?></strong></p>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="text-align:center; margin-top:30px;">Tidak ada jadwal pergi ditemukan.</p>
        <?php endif; ?>

        <!-- Jadwal Pulang -->
        <h3>Pilih Jadwal Pulang</h3>
        <?php if ($result_pulang && $result_pulang->num_rows > 0): ?>
          <?php while($row = $result_pulang->fetch_assoc()): ?>
            <div class="result-card">
              <input type="radio" name="jadwal_pulang_id" value="<?= $row['jadwal_id']; ?>" required>
              <h4><?= $row['asal']; ?> - <?= $row['tujuan']; ?></h4>
              <p><?= date('d F Y', strtotime($row['tanggal_keberangkatan'])); ?> | <?= $row['jam_keberangkatan']; ?></p>
              <p><?= ucfirst($row['kelas_layanan']); ?> | Durasi: <?= $row['estimasi_durasi']; ?></p>
              <p><strong>Rp<?= number_format($row['harga_perkursi'],0,',','.'); ?></strong></p>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="text-align:center; margin-top:30px;">Tidak ada jadwal pulang ditemukan.</p>
        <?php endif; ?>

        <div style="text-align:center; margin-top:20px;">
          <button type="submit" class="btn-pesan">Pilih Kursi</button>
        </div>
      </form>
    <?php endif; ?>
  </section>

  <!-- Footer -->
  <footer>
    <div class="links">
      <a href="https://wa.me/6289519515332" target="_blank">Kontak</a>
      <a href="bantuan.php">Bantuan/Laporan</a>
    </div>
    <p>© 2025 GlobeTix Travel Booking</p>
  </footer>

</body>
</html>
