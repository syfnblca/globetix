<?php
session_start();
include 'db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: masuk.php");
    exit();
}

$user_id = $_SESSION['user_id'];



// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause for search
$where = "WHERE b.user_id = ?";
if ($search) {
    $where .= " AND (b.kode_booking LIKE ? OR u.nama_lengkap LIKE ?)";
}

// Ambil data booking user
$query = "
    SELECT
        b.booking_id,
        b.kode_booking,
        b.total_harga,
        b.tanggal_booking,
        b.status_booking,
        u.nama_lengkap,
        j.asal,
        j.tujuan,
        j.tanggal_keberangkatan,
        j.jam_keberangkatan,
        j.estimasi_durasi,
        COALESCE(p.nama_provider, 'N/A') AS nama_provider,
        GROUP_CONCAT(k.nomor_kursi ORDER BY k.nomor_kursi SEPARATOR ', ') AS seats,
        (SELECT status_pembayaran FROM pembayaran WHERE booking_id = b.booking_id LIMIT 1) AS status_pembayaran,
        (SELECT bukti_bayar FROM pembayaran WHERE booking_id = b.booking_id LIMIT 1) AS bukti_bayar,
        CASE WHEN b.jadwal_pulang_id IS NOT NULL THEN 'Round-trip' ELSE 'One-way' END AS trip_type
    FROM booking b
    JOIN users u ON b.user_id = u.user_id
    JOIN jadwal j ON b.jadwal_id = j.jadwal_id
    LEFT JOIN providers p ON j.provider_id = p.provider_id
    LEFT JOIN booking_seat bs ON b.booking_id = bs.booking_id
    LEFT JOIN kursi k ON bs.seat_id = k.seat_id
    $where
    GROUP BY b.booking_id
    ORDER BY b.tanggal_booking DESC
";

$stmt = $conn->prepare($query);
if ($search) {
    $search_param = "%$search%";
    $stmt->bind_param("iss", $user_id, $search_param, $search_param);
} else {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();
$conn->close();

// Separate active tickets (confirmed and future)
$active_tickets = array_filter($bookings, function($b) {
    return $b['status_booking'] === 'confirmed' && strtotime($b['tanggal_keberangkatan']) >= time();
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Pemesanan - GlobeTix</title>
  <link rel="stylesheet" href="riwayat.css">
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
  <style>
    .barcode-modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.8);
      justify-content: center;
      align-items: center;
    }
    .barcode-modal-content {
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 90%;
      max-height: 90%;
      overflow: auto;
      position: relative;
    }
    .barcode-modal-close {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 24px;
      cursor: pointer;
      color: #333;
    }
    .barcode-container {
      cursor: pointer;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 5px;
      background: white;
      display: inline-block;
    }
    .barcode-container:hover {
      border-color: #3498db;
    }

  </style>
</head>
<body>
  <!-- Navbar -->
  <header>
    <div class="logo">
      <img src="logo.png" alt="GlobeTix Logo">
    </div>
    <nav class="nav-menu">
      <a href="dashboard.php">Beranda</a>
      <a href="riwayat.php" class="active">Riwayat</a>
      <a href="profil.php">Profil</a>
      <a href="keluar.php">Keluar</a>
    </nav>
  </header>

  <!-- Cari Transaksi -->
  <section class="search-container">
    <h2>🔍 Cari Transaksi</h2>
    <form method="GET" action="riwayat.php">
      <input type="text" name="search" placeholder="Masukkan kode booking atau nama..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Cari</button>
    </form>
  </section>

  <!-- Tiket Aktif -->
  <?php if (count($active_tickets) > 0): ?>
  <section class="active-tickets">
    <h2>🎫 Tiket Aktif</h2>
    <?php foreach ($active_tickets as $ticket): ?>
    <div class="ticket-card">
      <div class="ticket-info">
        <h3>Kode Booking: <?= htmlspecialchars($ticket['kode_booking']) ?></h3>
        <p><strong>Rute Perjalanan:</strong> <?= htmlspecialchars($ticket['asal']) ?> – <?= htmlspecialchars($ticket['tujuan']) ?></p>
        <p><strong>Tanggal & Jam Keberangkatan:</strong> <?= date('d F Y', strtotime($ticket['tanggal_keberangkatan'])) ?> (<?= htmlspecialchars($ticket['jam_keberangkatan']) ?>–<?= date('H:i', strtotime($ticket['jam_keberangkatan']) + (intval(explode(':', $ticket['estimasi_durasi'])[0]) * 3600)) ?>)</p>
        <p><strong>Nama Bus/Provider:</strong> <?= htmlspecialchars($ticket['nama_provider']) ?></p>
      </div>
      <div class="ticket-qr">
        <div class="barcode-container" onclick="showBarcodeModal('<?= $ticket['booking_id'] ?>', '<?= htmlspecialchars($ticket['kode_booking']) ?>')">
          <svg id="barcode-small-<?= $ticket['booking_id'] ?>"></svg>
        </div>
        <p class="seat-label">Kursi: <?= htmlspecialchars($ticket['seats'] ?: 'N/A') ?></p>
      </div>
    </div>
    <script>
      JsBarcode("#barcode-small-<?= $ticket['booking_id'] ?>", "<?= htmlspecialchars($ticket['kode_booking']) ?>", {
        format: "CODE128",
        width: 1,
        height: 30,
        displayValue: false,
        margin: 0
      });
    </script>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <!-- Tabel Tiket -->
  <section class="tickets-table">
    <h2>📅 Daftar Tiket</h2>
    <table>
      <thead>
        <tr>
          <th>Kode Booking</th>
          <th>Rute Perjalanan</th>
          <th>Tanggal & Jam Keberangkatan</th>
          <th>Nama Bus/Provider</th>
          <th>Status</th>
          <th>Batalkan Tiket</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($bookings) > 0): ?>
          <?php foreach ($bookings as $booking): ?>
          <tr>
            <td><?= htmlspecialchars($booking['kode_booking']) ?></td>
            <td><?= htmlspecialchars($booking['asal']) ?>–<?= htmlspecialchars($booking['tujuan']) ?></td>
            <td><?= date('d F Y', strtotime($booking['tanggal_keberangkatan'])) ?> (<?= htmlspecialchars($booking['jam_keberangkatan']) ?>)</td>
            <td><?= htmlspecialchars($booking['nama_provider']) ?></td>
            <td>
              <span class="status-badge <?= $booking['status_booking'] === 'confirmed' ? 'active' : ($booking['status_booking'] === 'cancelled' ? 'cancelled' : 'pending') ?>">
                <?= $booking['status_booking'] === 'confirmed' ? 'Aktif' : ($booking['status_booking'] === 'cancelled' ? 'Dibatalkan' : 'Pending') ?>
              </span>
            </td>
            <td>
              <?php if ($booking['status_booking'] === 'confirmed' && strtotime($booking['tanggal_keberangkatan']) >= time()): ?>
                <a href="batalkan_pemesanan.php?booking_id=<?= $booking['booking_id'] ?>" class="cancel-btn">Batalkan</a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align:center;">Belum ada riwayat pemesanan.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <!-- Modal for enlarged barcode -->
  <div id="barcodeModal" class="barcode-modal">
    <div class="barcode-modal-content">
      <span class="barcode-modal-close" onclick="closeBarcodeModal()">&times;</span>
      <h3>Barcode Tiket</h3>
      <svg id="barcode-large"></svg>
      <p id="barcode-text"></p>
    </div>
  </div>



  <!-- Footer -->
  <footer>
    <div class="links">
      <a href="https://wa.me/6289519515332" target="_blank">Contact Person</a>
      <a href="bantuan.php">Bantuan/Laporan</a>
    </div>
    <p>© 2025 GlobeTix Travel Booking</p>
  </footer>

  <script>
    function showBarcodeModal(bookingId, kodeBooking) {
      const modal = document.getElementById('barcodeModal');
      const largeBarcode = document.getElementById('barcode-large');
      const barcodeText = document.getElementById('barcode-text');

      // Clear previous barcode
      largeBarcode.innerHTML = '';
      barcodeText.textContent = kodeBooking;

      // Generate large barcode
      JsBarcode("#barcode-large", kodeBooking, {
        format: "CODE128",
        width: 3,
        height: 100,
        displayValue: true,
        fontSize: 16,
        margin: 10
      });

      modal.style.display = 'flex';
    }

    function closeBarcodeModal() {
      document.getElementById('barcodeModal').style.display = 'none';
    }



    // Close modal when clicking outside
    window.onclick = function(event) {
      const barcodeModal = document.getElementById('barcodeModal');
      if (event.target == barcodeModal) {
        barcodeModal.style.display = 'none';
      }
    }
  </script>
</body>
</html>
