<?php
// admin/edit_bus.php
include 'includes/auth.php';
include 'includes/db_connect.php';
include 'includes/header.php';
include 'includes/sidebar.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='dashboard-title'>Invalid request.</div>";
    include 'includes/footer.php'; exit;
}

$jadwal_id = intval($_GET['id']);

// Ambil data jadwal
$q = mysqli_prepare($conn, "SELECT provider_id, asal, tujuan, tanggal_keberangkatan, jam_keberangkatan, estimasi_durasi, kelas_layanan, harga_perkursi FROM jadwal WHERE jadwal_id=?");
mysqli_stmt_bind_param($q, "i", $jadwal_id);
mysqli_stmt_execute($q);
mysqli_stmt_store_result($q);

if (mysqli_stmt_num_rows($q) === 0) {
    echo "<div class='dashboard-title'>Jadwal tidak ditemukan.</div>";
    include 'includes/footer.php'; exit;
}
mysqli_stmt_bind_result($q, $provider_id, $asal, $tujuan, $tgl, $jam, $durasi, $kelas, $harga);
mysqli_stmt_fetch($q);
mysqli_stmt_close($q);

// Hitung kapasitas (jumlah kursi saat ini)
$resKursi = mysqli_query($conn, "SELECT COUNT(*) FROM kursi WHERE jadwal_id=$jadwal_id");
$kapasitas = mysqli_fetch_row($resKursi)[0];

// Hitung jumlah booking
$resBook = mysqli_query($conn, "SELECT COUNT(*) FROM booking WHERE jadwal_id=$jadwal_id");
$jumlah_booking = mysqli_fetch_row($resBook)[0];

// Jika POST (submit edit)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider_select = $_POST['provider_select'];
    $asal = trim($_POST['asal']);
    $tujuan = trim($_POST['tujuan']);
    $tgl = $_POST['tanggal_keberangkatan'];
    $jam = $_POST['jam_keberangkatan'];
    $durasi = trim($_POST['estimasi_durasi']);
    $kelas = $_POST['kelas_layanan'];
    $harga = floatval($_POST['harga_perkursi']);
    $kapasitas_new = intval($_POST['kapasitas']);

    // Tambah provider baru?
    if ($provider_select === 'new') {
        $new_provider_name = trim($_POST['new_provider_name']);
        if ($new_provider_name === '') $errors[] = "Nama provider baru wajib diisi.";
        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO providers (nama_provider, status) VALUES (?, 'aktif')");
            mysqli_stmt_bind_param($stmt, "s", $new_provider_name);
            mysqli_stmt_execute($stmt);
            $provider_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        }
    } else {
        $provider_id = intval($provider_select);
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            // Update jadwal
            $upd = mysqli_prepare($conn, "UPDATE jadwal SET provider_id=?, asal=?, tujuan=?, tanggal_keberangkatan=?, jam_keberangkatan=?, estimasi_durasi=?, kelas_layanan=?, harga_perkursi=? WHERE jadwal_id=?");
            mysqli_stmt_bind_param($upd, "issssssdi", $provider_id, $asal, $tujuan, $tgl, $jam, $durasi, $kelas, $harga, $jadwal_id);
            if (!mysqli_stmt_execute($upd)) throw new Exception("Gagal update jadwal: " . mysqli_error($conn));
            mysqli_stmt_close($upd);

            // Kapasitas hanya boleh diubah jika belum ada booking
            if ($jumlah_booking == 0) {
                // Hapus kursi lama
                mysqli_query($conn, "DELETE FROM kursi WHERE jadwal_id=$jadwal_id");
                // Generate seats based on kelas_layanan
                $seats = [];
                if ($kelas == 'reguler') {
                    // 40 seats: Baris 1: A1 B1 C1 D1, Baris 2: A2 B2 C2 D2, etc.
                    for ($num = 1; $num <= 10; $num++) {
                        for ($row = 'A'; $row <= 'D'; $row++) {
                            $seats[] = $row . $num;
                        }
                    }
                } elseif ($kelas == 'premium') {
                    // 30 seats: Baris 1: A1 B1 C1, Baris 2: A2 B2 C2, etc.
                    for ($num = 1; $num <= 10; $num++) {
                        for ($row = 'A'; $row <= 'C'; $row++) {
                            $seats[] = $row . $num;
                        }
                    }
                } elseif ($kelas == 'bisnis') {
                    // 15 seats: Baris 1: A1 B1 C1, Baris 2: A2 B2 C2, etc.
                    for ($num = 1; $num <= 5; $num++) {
                        for ($row = 'A'; $row <= 'C'; $row++) {
                            $seats[] = $row . $num;
                        }
                    }
                }

                if (!empty($seats)) {
                    $stmtSeat = mysqli_prepare($conn, "INSERT INTO kursi (jadwal_id, nomor_kursi, status) VALUES (?, ?, 'tersedia')");
                    foreach ($seats as $nomor) {
                        mysqli_stmt_bind_param($stmtSeat, "is", $jadwal_id, $nomor);
                        mysqli_stmt_execute($stmtSeat);
                    }
                    mysqli_stmt_close($stmtSeat);
                }
            }

            mysqli_commit($conn);
            header("Location: bus.php?updated=1");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

// Ambil semua provider untuk dropdown
$provRes = mysqli_query($conn, "SELECT provider_id, nama_provider FROM providers WHERE status='aktif'");
$providers = [];
while ($p = mysqli_fetch_assoc($provRes)) $providers[] = $p;
?>

<div class="dashboard-title">Edit Jadwal</div>

<?php if (!empty($errors)) : ?>
    <div style="background:#ffe6e6;border:1px solid #ffcccc;padding:10px;margin-bottom:15px;border-radius:6px;">
        <strong>Pesan Kesalahan:</strong>
        <ul>
        <?php foreach ($errors as $err) : ?>
            <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="" id="formEditJadwal">
    <div class="form-2cols">
        <!-- Left Column -->
        <div class="col-left">
            <label for="provider_select"><strong>Provider</strong></label>
            <select name="provider_select" id="provider_select">
                <?php foreach ($providers as $p): ?>
                    <option value="<?= $p['provider_id'] ?>" <?= $p['provider_id']==$provider_id?'selected':'' ?>>
                        <?= htmlspecialchars($p['nama_provider']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="new">-- Tambah Provider Baru --</option>
            </select>

            <div id="new_provider_fields" style="display:none;margin-top:12px;padding:10px;border:1px dashed #ddd;border-radius:6px;background:#fff;">
                <h4 style="margin-bottom:8px;color:#0a1f44;">Tambah Provider Baru</h4>
                <label>Nama Provider Baru</label>
                <input type="text" name="new_provider_name" id="new_provider_name" placeholder="Nama provider">
            </div>

            <label for="asal"><strong>Asal</strong></label>
            <select name="asal" id="asal" required>
                <option value="">-- Pilih Kota Asal --</option>
                <optgroup label="Jawa Barat & DKI Jakarta">
                    <option value="Jakarta" <?= $asal == 'Jakarta' ? 'selected' : '' ?>>Jakarta</option>
                    <option value="Bekasi" <?= $asal == 'Bekasi' ? 'selected' : '' ?>>Bekasi</option>
                    <option value="Bandung" <?= $asal == 'Bandung' ? 'selected' : '' ?>>Bandung</option>
                    <option value="Tasikmalaya" <?= $asal == 'Tasikmalaya' ? 'selected' : '' ?>>Tasikmalaya</option>
                    <option value="Cirebon" <?= $asal == 'Cirebon' ? 'selected' : '' ?>>Cirebon</option>
                </optgroup>
                <optgroup label="Jawa Tengah & Yogyakarta">
                    <option value="Semarang" <?= $asal == 'Semarang' ? 'selected' : '' ?>>Semarang</option>
                    <option value="Solo (Surakarta)" <?= $asal == 'Solo (Surakarta)' ? 'selected' : '' ?>>Solo (Surakarta)</option>
                    <option value="Yogyakarta" <?= $asal == 'Yogyakarta' ? 'selected' : '' ?>>Yogyakarta</option>
                    <option value="Magelang" <?= $asal == 'Magelang' ? 'selected' : '' ?>>Magelang</option>
                    <option value="Purwokerto" <?= $asal == 'Purwokerto' ? 'selected' : '' ?>>Purwokerto</option>
                    <option value="Kudus / Jepara" <?= $asal == 'Kudus / Jepara' ? 'selected' : '' ?>>Kudus / Jepara</option>
                </optgroup>
                <optgroup label="Jawa Timur">
                    <option value="Surabaya" <?= $asal == 'Surabaya' ? 'selected' : '' ?>>Surabaya</option>
                    <option value="Malang" <?= $asal == 'Malang' ? 'selected' : '' ?>>Malang</option>
                    <option value="Kediri" <?= $asal == 'Kediri' ? 'selected' : '' ?>>Kediri</option>
                    <option value="Tulungagung" <?= $asal == 'Tulungagung' ? 'selected' : '' ?>>Tulungagung</option>
                    <option value="Madiun" <?= $asal == 'Madiun' ? 'selected' : '' ?>>Madiun</option>
                    <option value="Banyuwangi (gerbang ke Bali)" <?= $asal == 'Banyuwangi (gerbang ke Bali)' ? 'selected' : '' ?>>Banyuwangi (gerbang ke Bali)</option>
                </optgroup>
                <optgroup label="Bali">
                    <option value="Denpasar" <?= $asal == 'Denpasar' ? 'selected' : '' ?>>Denpasar</option>
                    <option value="Gianyar (Ubud)" <?= $asal == 'Gianyar (Ubud)' ? 'selected' : '' ?>>Gianyar (Ubud)</option>
                    <option value="Tabanan" <?= $asal == 'Tabanan' ? 'selected' : '' ?>>Tabanan</option>
                    <option value="Negara (Gilimanuk – pelabuhan masuk dari Jawa)" <?= $asal == 'Negara (Gilimanuk – pelabuhan masuk dari Jawa)' ? 'selected' : '' ?>>Negara (Gilimanuk – pelabuhan masuk dari Jawa)</option>
                </optgroup>
            </select>

            <label for="tujuan"><strong>Tujuan</strong></label>
            <select name="tujuan" id="tujuan" required>
                <option value="">-- Pilih Kota Tujuan --</option>
                <optgroup label="Jawa Barat & DKI Jakarta">
                    <option value="Jakarta" <?= $tujuan == 'Jakarta' ? 'selected' : '' ?>>Jakarta</option>
                    <option value="Bekasi" <?= $tujuan == 'Bekasi' ? 'selected' : '' ?>>Bekasi</option>
                    <option value="Bandung" <?= $tujuan == 'Bandung' ? 'selected' : '' ?>>Bandung</option>
                    <option value="Tasikmalaya" <?= $tujuan == 'Tasikmalaya' ? 'selected' : '' ?>>Tasikmalaya</option>
                    <option value="Cirebon" <?= $tujuan == 'Cirebon' ? 'selected' : '' ?>>Cirebon</option>
                </optgroup>
                <optgroup label="Jawa Tengah & Yogyakarta">
                    <option value="Semarang" <?= $tujuan == 'Semarang' ? 'selected' : '' ?>>Semarang</option>
                    <option value="Solo (Surakarta)" <?= $tujuan == 'Solo (Surakarta)' ? 'selected' : '' ?>>Solo (Surakarta)</option>
                    <option value="Yogyakarta" <?= $tujuan == 'Yogyakarta' ? 'selected' : '' ?>>Yogyakarta</option>
                    <option value="Magelang" <?= $tujuan == 'Magelang' ? 'selected' : '' ?>>Magelang</option>
                    <option value="Purwokerto" <?= $tujuan == 'Purwokerto' ? 'selected' : '' ?>>Purwokerto</option>
                    <option value="Kudus / Jepara" <?= $tujuan == 'Kudus / Jepara' ? 'selected' : '' ?>>Kudus / Jepara</option>
                </optgroup>
                <optgroup label="Jawa Timur">
                    <option value="Surabaya" <?= $tujuan == 'Surabaya' ? 'selected' : '' ?>>Surabaya</option>
                    <option value="Malang" <?= $tujuan == 'Malang' ? 'selected' : '' ?>>Malang</option>
                    <option value="Kediri" <?= $tujuan == 'Kediri' ? 'selected' : '' ?>>Kediri</option>
                    <option value="Tulungagung" <?= $tujuan == 'Tulungagung' ? 'selected' : '' ?>>Tulungagung</option>
                    <option value="Madiun" <?= $tujuan == 'Madiun' ? 'selected' : '' ?>>Madiun</option>
                    <option value="Banyuwangi (gerbang ke Bali)" <?= $tujuan == 'Banyuwangi (gerbang ke Bali)' ? 'selected' : '' ?>>Banyuwangi (gerbang ke Bali)</option>
                </optgroup>
                <optgroup label="Bali">
                    <option value="Denpasar" <?= $tujuan == 'Denpasar' ? 'selected' : '' ?>>Denpasar</option>
                    <option value="Gianyar (Ubud)" <?= $tujuan == 'Gianyar (Ubud)' ? 'selected' : '' ?>>Gianyar (Ubud)</option>
                    <option value="Tabanan" <?= $tujuan == 'Tabanan' ? 'selected' : '' ?>>Tabanan</option>
                    <option value="Negara (Gilimanuk – pelabuhan masuk dari Jawa)" <?= $tujuan == 'Negara (Gilimanuk – pelabuhan masuk dari Jawa)' ? 'selected' : '' ?>>Negara (Gilimanuk – pelabuhan masuk dari Jawa)</option>
                </optgroup>
            </select>
        </div>

        <!-- Right Column -->
        <div class="col-right">
            <label for="tanggal_keberangkatan"><strong>Tanggal Keberangkatan</strong></label>
            <input type="date" name="tanggal_keberangkatan" id="tanggal_keberangkatan" value="<?= $tgl ?>" required>

            <label for="jam_keberangkatan"><strong>Jam Keberangkatan</strong></label>
            <input type="time" name="jam_keberangkatan" id="jam_keberangkatan" value="<?= $jam ?>" required>

            <label for="estimasi_durasi"><strong>Estimasi Durasi</strong></label>
            <input type="text" name="estimasi_durasi" id="estimasi_durasi" value="<?= htmlspecialchars($durasi) ?>" placeholder="mis: 3 jam">

            <label for="kelas_layanan"><strong>Kelas Layanan</strong></label>
            <select name="kelas_layanan" id="kelas_layanan">
                <option value="reguler" <?= $kelas=='reguler'?'selected':'' ?>>Reguler</option>
                <option value="premium" <?= $kelas=='premium'?'selected':'' ?>>Premium</option>
                <option value="bisnis" <?= $kelas=='bisnis'?'selected':'' ?>>Bisnis</option>
            </select>

            <label for="harga_perkursi"><strong>Harga Per Kursi (Rp)</strong></label>
            <input type="number" name="harga_perkursi" id="harga_perkursi" min="0" step="1000" value="<?= $harga ?>" required placeholder="150000">

            <label for="kapasitas"><strong>Kapasitas (jumlah kursi)</strong></label>
            <input type="number" name="kapasitas" id="kapasitas" min="0" step="1" value="<?= $kapasitas ?>" readonly placeholder="Jumlah kursi (otomatis)">
            <?php if ($jumlah_booking>0): ?>
                <small style="color:red;">Tidak bisa ubah kapasitas karena sudah ada booking.</small>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top:15px;">
        <button type="submit" class="btn-tambah">Update Jadwal</button>
        <a href="bus.php" class="btn-delete" style="margin-left:8px;padding:10px 12px;display:inline-block;background:#7f8c8d;">Batal</a>
    </div>
</form>

<!-- Small inline CSS for the 2-col form (ke style.css juga bisa ditambahkan) -->
<style>
.form-2cols {
    display: flex;
    gap: 20px;
    margin-top: 15px;
}
.col-left, .col-right {
    flex: 1;
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.col-left label, .col-right label { display:block; margin-top:10px; font-weight:600; color:#0a1f44; }
.col-left input[type="text"], .col-right input[type="text"],
.col-left input[type="date"], .col-right input[type="time"],
.col-left input[type="number"], .col-left textarea,
.col-right input[type="number"], .col-right textarea,
.col-left select, .col-right select {
    width:100%; padding:8px; margin-top:6px; border:1px solid #ddd; border-radius:4px;
}
</style>

<script>
// Toggle new provider fields
document.getElementById('provider_select').addEventListener('change', function() {
    var val = this.value;
    var newFields = document.getElementById('new_provider_fields');
    if (val === 'new') {
        newFields.style.display = 'block';
        // make new provider fields required
        document.getElementById('new_provider_name').required = true;
    } else {
        newFields.style.display = 'none';
        document.getElementById('new_provider_name').required = false;
    }
});

// Set kapasitas based on kelas_layanan
document.getElementById('kelas_layanan').addEventListener('change', function() {
    var kelas = this.value;
    var kapasitasInput = document.getElementById('kapasitas');
    if (kelas === 'reguler') {
        kapasitasInput.value = 40;
    } else if (kelas === 'premium') {
        kapasitasInput.value = 30;
    } else if (kelas === 'bisnis') {
        kapasitasInput.value = 15;
    } else {
        kapasitasInput.value = '';
    }
});

// Set default kapasitas on page load
document.addEventListener('DOMContentLoaded', function() {
    var kelasSelect = document.getElementById('kelas_layanan');
    var kapasitasInput = document.getElementById('kapasitas');
    var kelas = kelasSelect.value;
    if (kelas === 'reguler') {
        kapasitasInput.value = 40;
    } else if (kelas === 'premium') {
        kapasitasInput.value = 30;
    } else if (kelas === 'bisnis') {
        kapasitasInput.value = 15;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
