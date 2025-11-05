<?php
include 'includes/auth.php';
include 'includes/db_connect.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// Filter tanggal
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';

// Ambil data jadwal + nama provider, hanya yang tanggal keberangkatan >= hari ini dan sesuai filter
$where_clauses = ["j.tanggal_keberangkatan >= CURDATE()"];
if (!empty($tanggal_mulai)) {
    $where_clauses[] = "j.tanggal_keberangkatan >= '" . mysqli_real_escape_string($conn, $tanggal_mulai) . "'";
}
if (!empty($tanggal_akhir)) {
    $where_clauses[] = "j.tanggal_keberangkatan <= '" . mysqli_real_escape_string($conn, $tanggal_akhir) . "'";
}
$where_sql = implode(' AND ', $where_clauses);

$query = "
    SELECT j.*, p.nama_provider 
    FROM jadwal j 
    LEFT JOIN providers p ON j.provider_id = p.provider_id
    WHERE $where_sql
    ORDER BY j.tanggal_keberangkatan ASC, j.jam_keberangkatan ASC
";
$result = mysqli_query($conn, $query);
?>

<div class="dashboard-title">Data Jadwal / Bus</div>

<a href="tambah_bus.php" class="btn-tambah">+ Tambah Jadwal</a>

<!-- Form Filter Tanggal -->
<div class="filter-form">
<form method="GET" action="">
<label>Tanggal Mulai:
    <input type="text" name="tanggal_mulai" placeholder="hh/bb/tttt" value="<?php echo htmlspecialchars($tanggal_mulai); ?>" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
</label>
<label>Tanggal Akhir:
    <input type="text" name="tanggal_akhir" placeholder="hh/bb/tttt" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
</label>
<button type="submit">Filter</button>
<a href="bus.php" class="btn-edit">Reset</a>
</form>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>No</th>
            <th>Provider</th>
            <th>Asal</th>
            <th>Tujuan</th>
            <th>Keberangkatan</th>
            <th>Kelas</th>
            <th>Harga</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) : ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= $row['nama_provider']; ?></td>
            <td><?= $row['asal']; ?></td>
            <td><?= $row['tujuan']; ?></td>
            <td><?= $row['tanggal_keberangkatan'] . ' ' . $row['jam_keberangkatan']; ?></td>
            <td><?= ucfirst($row['kelas_layanan']); ?></td>
            <td>Rp <?= number_format($row['harga_perkursi'],0,',','.'); ?></td>
            <td><?= ucfirst($row['status']); ?></td>
            <td>
                <a href="edit_bus.php?id=<?= $row['jadwal_id']; ?>" class="btn-edit">Edit</a>
                <a href="hapus_bus.php?id=<?= $row['jadwal_id']; ?>" class="btn-delete" onclick="return confirm('Yakin hapus?')">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<style>
.filter-form {
    margin-bottom: 20px;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
    font-family: Arial, sans-serif;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filter-form label {
    display: flex;
    flex-direction: column;
    font-weight: 600;
    font-size: 14px;
    color: #333;
    margin: 0;
}

.filter-form input[type="text"],
.filter-form input[type="date"] {
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 5px;
    font-size: 14px;
    width: 180px;
    box-sizing: border-box;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    background: #fff;
}

.filter-form input[type="text"]:focus,
.filter-form input[type="date"]:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.filter-form button {
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.filter-form button:hover {
    background-color: #0056b3;
    transform: translateY(-1px);
}

.filter-form .btn-edit {
    padding: 10px 20px;
    background-color: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-left: 10px;
}

.filter-form .btn-edit:hover {
    background-color: #545b62;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-form label {
        margin-bottom: 5px;
    }

    .filter-form input[type="text"],
    .filter-form input[type="date"] {
        width: 100%;
    }

    .filter-form button,
    .filter-form .btn-edit {
        align-self: stretch;
        margin-left: 0;
        margin-top: 10px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
