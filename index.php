<?php
include 'db.php';

/*
   Ambil data asal & tujuan langsung dari tabel jadwal
   sesuai struktur database globetix.
   Kolom: asal | tujuan
*/
$cities = [];
$sql = "SELECT DISTINCT asal, tujuan FROM jadwal";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    if (!empty($row['asal']))   $cities[] = $row['asal'];
    if (!empty($row['tujuan'])) $cities[] = $row['tujuan'];
  }
}

$cities = array_unique($cities);
sort($cities);

// Calculate date restrictions: users can book from today up to 10 days ahead for one-way, 20 days for round-trip
$today = date('Y-m-d');
$max_date_oneway = date('Y-m-d', strtotime('+10 days'));
$max_date_roundtrip = date('Y-m-d', strtotime('+20 days'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>GlobeTix Travel Booking</title>
  <link rel="stylesheet" href="index.css">

</head>
<body>
  <header>
    <div class="logo">
      <img src="logo.png" alt="GlobeTix Logo">
    </div>
    <div class="nav-buttons">
      <a href="masuk.php">Masuk</a>
      <a href="daftar.php" class="btn-daftar">Daftar</a>
    </div>
  </header>

  <section class="hero">
    <h1>Pesan Tiket Online Lebih Cepat, Mudah, dan Real-time!!</h1>
  </section>

  <!-- CAROUSEL -->
  <section class="carousel">
    <div class="carousel-container">
      <div class="carousel-slide">
        <img src="bus (1).jpg" alt="Bus 1">
      </div>
      <div class="carousel-slide">
        <img src="bus (2).jpg" alt="Bus 2">
      </div>
      <div class="carousel-slide">
        <img src="bus (3).jpg" alt="Bus 3">
      </div>
    </div>
  </section>

  <section class="search-box">
    <form action="hasil_pencarian.php" method="GET" style="width:100%;display:flex;flex-direction:column;gap:15px;">
      
      <div class="search-top">
        <div class="trip-type">
          <input type="radio" name="trip" id="sekali" value="sekali" checked>
          <label for="sekali">Sekali Jalan</label>
          <input type="radio" name="trip" id="pp" value="pp">
          <label for="pp">Pulang Pergi</label>
        </div>
      </div>

      <!-- FORM PERGI -->
      <div class="form-row" id="formPergi">
        <div class="form-group">
          <label>Berangkat Dari</label>
          <select name="asal" id="asalPergi" required>
            <option value="">Pilih Asal</option>
            <?php foreach ($cities as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="swap-icon" id="swapPergi">↔</div>

        <div class="form-group">
          <label>Untuk Tujuan</label>
          <select name="tujuan" id="tujuanPergi" required>
            <option value="">Pilih Tujuan</option>
            <?php foreach ($cities as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Tanggal Berangkat</label>
          <input type="date" name="tanggal" id="tglPergi" min="<?= $today ?>" max="<?= $max_date_oneway ?>" required>
        </div>

        <div class="form-group">
          <label>Jumlah Penumpang</label>
          <select name="penumpang" id="penumpangPergi" required>
            <?php for($i=1;$i<=8;$i++): ?>
              <option value="<?= $i ?>"><?= $i ?> Orang</option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Kelas</label>
          <select name="kelas" id="kelasPergi">
            <option value="">Pilih Kelas</option>
            <option value="reguler">Reguler</option>
            <option value="bisnis">Bisnis</option>
            <option value="premium">Premium</option>
          </select>
        </div>
      </div>

      <!-- FORM PULANG -->
      <div class="form-row" id="formPulang">
        <div class="form-group">
          <label>Berangkat Dari</label>
          <select name="asal_pulang" id="asalPulang">
            <option value="">Pilih Asal Pulang</option>
            <?php foreach ($cities as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="swap-icon disabled">↔</div>

        <div class="form-group">
          <label>Untuk Tujuan</label>
          <select name="tujuan_pulang" id="tujuanPulang">
            <option value="">Pilih Tujuan Pulang</option>
            <?php foreach ($cities as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Tanggal Pulang</label>
          <input type="date" name="tanggal_pulang" id="tglPulang" min="<?= $today ?>" max="<?= $max_date_roundtrip ?>">
        </div>

        <div class="form-group">
          <label>Jumlah Penumpang</label>
          <select name="penumpang_pulang" id="penumpangPulang">
            <?php for($i=1;$i<=8;$i++): ?>
              <option value="<?= $i ?>"><?= $i ?> Orang</option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Kelas</label>
          <select name="kelas_pulang" id="kelasPulang">
            <option value="">Pilih Kelas</option>
            <option value="reguler">Reguler</option>
            <option value="bisnis">Bisnis</option>
            <option value="premium">Premium</option>
          </select>
        </div>
      </div>

      <button type="submit" class="btn-cari">🔍 Cari Tiket</button>
    </form>
  </section>

  <footer>
    <div class="links">
      <a href="https://wa.me/6289519515332" target="_blank">Kontak</a>
      <a href="bantuan.php">Bantuan/Laporan</a>
    </div>
    <p>© 2025 GlobeTix Travel Booking</p>
  </footer>

  <script>
    const rPP = document.getElementById('pp');
    const rSekali = document.getElementById('sekali');
    const formPulang = document.getElementById('formPulang');
    const swapBtn = document.getElementById('swapPergi');
    const asalPergi = document.getElementById('asalPergi');
    const tujuanPergi = document.getElementById('tujuanPergi');
    const asalPulang = document.getElementById('asalPulang');
    const tujuanPulang = document.getElementById('tujuanPulang');

    // Default: hide form pulang
    formPulang.style.display = 'none';

    rPP.addEventListener('change', () => {
      formPulang.style.display = 'flex';
      asalPulang.value = tujuanPergi.value;
      tujuanPulang.value = asalPergi.value;
    });
    rSekali.addEventListener('change', () => {
      formPulang.style.display = 'none';
    });

    swapBtn.addEventListener('click', () => {
      const tmp = asalPergi.value;
      asalPergi.value = tujuanPergi.value;
      tujuanPergi.value = tmp;
      if (rPP.checked) {
        asalPulang.value = tujuanPergi.value;
        tujuanPulang.value = asalPergi.value;
      }
    });

  </script>
</body>
</html>
