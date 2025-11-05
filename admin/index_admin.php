<?php
// =============================
// Dashboard Admin (Final Version)
// =============================
include 'includes/auth.php';
include 'includes/db_connect.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// =============================
// Ambil filter periode (default: 30 hari)
// =============================
$days = isset($_GET['days']) ? (int)$_GET['days'] : 365;
if (!in_array($days, [7, 30, 90, 365])) $days = 30;

$date_from = date('Y-m-d', strtotime('-10 years'));
$date_to = date('Y-m-d', strtotime('+10 years'));

$num_weeks = ceil($days / 7);

// =============================
// Data Statistik
// =============================
$total_jadwal = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM jadwal"))[0];
$total_booking = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM booking WHERE status_booking='confirmed' AND tanggal_booking BETWEEN '$date_from' AND '$date_to'"))[0];
$booking_confirmed = $total_booking;
$booking_cancelled = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM booking WHERE status_booking='cancelled' AND tanggal_booking BETWEEN '$date_from' AND '$date_to'"))[0];
$total_refund_pending = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM refund WHERE status_refund='diproses'"))[0];
$total_income = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(SUM(jumlah_bayar),0) FROM pembayaran WHERE status_pembayaran='sukses' AND tanggal_bayar BETWEEN '$date_from' AND '$date_to'"))[0];

// =============================
// Data Grafik: Tren Booking
// =============================
$qTrend = mysqli_query($conn, "
    SELECT DATE(b.tanggal_booking) AS tgl, COUNT(DISTINCT b.booking_id) AS jumlah
    FROM booking b
    JOIN pembayaran p ON b.booking_id = p.booking_id
    WHERE p.status_pembayaran='sukses' AND p.tanggal_bayar BETWEEN '$date_from' AND '$date_to'
    GROUP BY DATE(b.tanggal_booking)
    ORDER BY DATE(b.tanggal_booking)
");
$trend_labels = [];
$trend_values = [];
while ($r = mysqli_fetch_assoc($qTrend)) {
    $trend_labels[] = date('d/m', strtotime($r['tgl']));
    $trend_values[] = (int)$r['jumlah'];
}

// =============================
// Data Grafik: Pendapatan Harian
// =============================
$qRevenue = mysqli_query($conn, "
    SELECT DATE(tanggal_bayar) AS tgl, SUM(jumlah_bayar) AS total
    FROM pembayaran
    WHERE status_pembayaran='sukses' AND tanggal_bayar BETWEEN '$date_from' AND '$date_to'
    GROUP BY DATE(tanggal_bayar)
    ORDER BY DATE(tanggal_bayar)
");
$rev_labels = [];
$rev_values = [];
while ($r = mysqli_fetch_assoc($qRevenue)) {
    $rev_labels[] = date('d/m', strtotime($r['tgl']));
    $rev_values[] = (int)$r['total'];
}

// =============================
// Data Grafik: Status Booking
// =============================
$status_data = [
    'confirmed' => $booking_confirmed,
    'cancelled' => $booking_cancelled,
    'pending'   => mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM booking WHERE status_booking='pending' AND tanggal_booking BETWEEN '$date_from' AND '$date_to'"))[0]
];

// =============================
// Data Grafik: Jadwal Populer (Top 5)
// =============================
$qPopular = mysqli_query($conn, "
    SELECT CONCAT(j.asal, ' - ', j.tujuan) AS rute, COUNT(DISTINCT b.booking_id) AS jumlah
    FROM booking b
    JOIN jadwal j ON b.jadwal_id = j.jadwal_id
    JOIN pembayaran p ON b.booking_id = p.booking_id
    WHERE p.status_pembayaran='sukses' AND p.tanggal_bayar BETWEEN '$date_from' AND '$date_to'
    GROUP BY j.asal, j.tujuan
    ORDER BY jumlah DESC
    LIMIT 5
");


$pop_labels = [];
$pop_values = [];
while ($r = mysqli_fetch_assoc($qPopular)) {
    $pop_labels[] = $r['rute'];
    $pop_values[] = (int)$r['jumlah'];
}

// =============================
// Pengguna Baru (dummy jika tidak ada tabel user)
// =============================
if (mysqli_query($conn, "SHOW TABLES LIKE 'users'")->num_rows > 0) {
    $qUsers = mysqli_query($conn, "
        SELECT DATE(tanggal_daftar) AS tgl, COUNT(*) AS jumlah
        FROM users
        WHERE tanggal_daftar BETWEEN '$date_from' AND '$date_to'
        GROUP BY DATE(tanggal_daftar)
    ");
    $user_labels = [];
    $user_values = [];
    while ($r = mysqli_fetch_assoc($qUsers)) {
        $user_labels[] = date('d/m', strtotime($r['tgl']));
        $user_values[] = (int)$r['jumlah'];
    }
} else {
    // data dummy 30 hari
    $user_labels = $trend_labels;
    $user_values = array_map(fn() => rand(10, 30), $trend_labels);
}

// =============================
// Hitung Summary
// =============================
$avg_booking = $days > 0 ? round($total_booking / $days, 1) : 0;
$occupancy_rate = $total_booking > 0 ? round(($booking_confirmed / $total_booking) * 100, 1) : 0;
$avg_revenue = $booking_confirmed > 0 ? round($total_income / $booking_confirmed) : 0;
$customer_satisfaction = "4.7/5"; // statis untuk sekarang
?>

<!doctype html>
<html lang="id" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pemesanan Bus</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
.card-hover { transition: all 0.3s ease; }
.card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.gradient-bg { background: #1e272e; }
.chart-container { position: relative; height: 300px; }
</style>
</head>
<body class="bg-gray-50 h-full">

<!-- Header -->
<header class="gradient-bg shadow-lg">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex justify-between items-center">
    <div>
      <h1 class="text-2xl font-bold text-white">Dashboard Pemesanan Bus</h1>
      <p class="text-blue-100">Globetix</p>
    </div>
    <form method="get">
      <select name="days" class="bg-white bg-opacity-20 text-white border border-white border-opacity-30 rounded-lg px-4 py-2">
        <option value="7" <?= $days==7?'selected':'' ?>>7 Hari Terakhir</option>
        <option value="30" <?= $days==30?'selected':'' ?>>30 Hari Terakhir</option>
        <option value="90" <?= $days==90?'selected':'' ?>>90 Hari Terakhir</option>
        <option value="365" <?= $days==365?'selected':'' ?>>1 Tahun Terakhir</option>
      </select>
      <button class="bg-white bg-opacity-20 text-white px-3 py-2 rounded-lg ml-2 hover:bg-opacity-30">Terapkan</button>
    </form>
  </div>
</header>

<!-- Main Content -->
<main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

<!-- Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
  <?php
  $cards = [
    ['Total Jadwal/Bus', $total_jadwal, 'blue'],
    ['Total Booking', $total_booking, 'green'],
    ['Dikonfirmasi', $booking_confirmed, 'emerald'],
    ['Dibatalkan', $booking_cancelled, 'red'],
    ['Refund Pending', $total_refund_pending, 'yellow'],
    ['Total Pendapatan', "Rp ".number_format($total_income,0,',','.'), 'purple']
  ];
  foreach ($cards as $c) {
    echo "<div class='bg-white overflow-hidden shadow-lg rounded-lg card-hover'>
            <div class='p-5'>
              <h4 class='text-sm text-gray-500 mb-1'>{$c[0]}</h4>
              <p class='text-lg font-semibold text-gray-900'>{$c[1]}</p>
            </div>
          </div>";
  }
  ?>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
  <div class="bg-white overflow-hidden shadow-lg rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900">Jadwal Populer</h3></div>
    <div class="p-6"><div class="chart-container"><canvas id="popularChart"></canvas></div></div>
  </div>

  <div class="bg-white overflow-hidden shadow-lg rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900">Tren Booking</h3></div>
    <div class="p-6"><div class="chart-container"><canvas id="trendChart"></canvas></div></div>
  </div>

  <div class="bg-white overflow-hidden shadow-lg rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900">Status Booking</h3></div>
    <div class="p-6"><div class="chart-container"><canvas id="statusChart"></canvas></div></div>
  </div>

  <div class="bg-white overflow-hidden shadow-lg rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900">Pengguna Baru</h3></div>
    <div class="p-6"><div class="chart-container"><canvas id="usersChart"></canvas></div></div>
  </div>
</div>

<!-- Revenue Chart -->
<div class="bg-white overflow-hidden shadow-lg rounded-lg mb-8">
  <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900">Pendapatan Harian</h3></div>
  <div class="p-6"><div style="position:relative;height:400px"><canvas id="revenueChart"></canvas></div></div>
</div>

<!-- Summary Report -->
<div class="bg-white overflow-hidden shadow-lg rounded-lg">
  <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900">Ringkasan Laporan</h3></div>
  <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
    <div><div class="text-3xl font-bold text-blue-600"><?= $avg_booking ?></div><div class="text-sm text-gray-500">Rata-rata Booking/Hari</div></div>
    <div><div class="text-3xl font-bold text-green-600"><?= $occupancy_rate ?>%</div><div class="text-sm text-gray-500">Tingkat Okupansi</div></div>
    <div><div class="text-3xl font-bold text-purple-600">Rp <?= number_format($avg_revenue,0,',','.') ?></div><div class="text-sm text-gray-500">Rata-rata Pendapatan/Booking</div></div>
  </div>
</div>

</main>

<script>
// --- Data from PHP ---
const popularLabels = <?= json_encode($pop_labels) ?>;
const popularValues = <?= json_encode($pop_values) ?>;
const trendLabels = <?= json_encode($trend_labels) ?>;
const trendValues = <?= json_encode($trend_values) ?>;
const statusValues = <?= json_encode(array_values($status_data)) ?>;
const userLabels = <?= json_encode($user_labels) ?>;
const userValues = <?= json_encode($user_values) ?>;
const revLabels = <?= json_encode($rev_labels) ?>;
const revValues = <?= json_encode($rev_values) ?>;

// --- Charts ---
new Chart(document.getElementById('popularChart'), {
  type: 'bar',
  data: { labels: popularLabels, datasets: [{ data: popularValues, backgroundColor: 'rgba(59,130,246,0.8)' }] },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: { labels: trendLabels, datasets: [{ data: trendValues, borderColor: 'rgba(16,185,129,1)', backgroundColor: 'rgba(16,185,129,0.1)', fill:true, tension:0.4 }] },
  options: { responsive: true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});

new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: { labels: ['Dikonfirmasi','Dibatalkan','Pending'], datasets:[{ data: statusValues, backgroundColor:['#10B981','#EF4444','#F59E0B'] }] },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('usersChart'), {
  type: 'bar',
  data: { labels: userLabels, datasets:[{ data: userValues, backgroundColor:'rgba(147,51,234,0.8)' }] },
  options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});

new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: { labels: revLabels, datasets:[{ data: revValues, borderColor:'rgba(147,51,234,1)', backgroundColor:'rgba(147,51,234,0.1)', fill:true, tension:0.4 }] },
  options: {
    responsive:true,
    plugins:{legend:{display:false}},
    scales:{y:{beginAtZero:true,ticks:{callback:v=>'Rp '+v.toLocaleString('id-ID')}}}
  }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
