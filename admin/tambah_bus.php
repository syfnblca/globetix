<?php
// admin/tambah_bus.php
include 'includes/auth.php';
include 'includes/db_connect.php';
include 'includes/header.php';
include 'includes/sidebar.php';

/*
 Behavior:
 - Show form (2 columns)
 - Provider dropdown loads existing providers + option "-- Tambah Provider Baru --"
 - If admin selects "Tambah Provider Baru", additional inputs appear (nama, kontak, alamat, deskripsi)
 - On submit:
    - If new provider provided -> INSERT into providers (status 'aktif') and get provider_id
    - INSERT into jadwal (provider_id, asal, tujuan, tanggal_keberangkatan, jam_keberangkatan, estimasi_durasi, kelas_layanan, harga_perkursi, status='tersedia')
    - If kapasitas > 0 -> generate kursi rows (nomor '1','2',... up to kapasitas)
 - Use mysqli procedural with prepared statements to avoid SQL injection
*/

// Handle POST submit
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitize (we'll use prepared statements)
    $provider_select = isset($_POST['provider_select']) ? $_POST['provider_select'] : '';
    $new_provider_flag = ($provider_select === 'new'); // if 'new' then read new provider fields
    $provider_id = null;

    // New provider fields
    $new_provider_name = trim($_POST['new_provider_name'] ?? '');
    $new_provider_kontak = trim($_POST['new_provider_kontak'] ?? '');
    $new_provider_alamat = trim($_POST['new_provider_alamat'] ?? '');
    $new_provider_deskripsi = trim($_POST['new_provider_deskripsi'] ?? '');

    // Jadwal fields
    $asal = trim($_POST['asal'] ?? '');
    $tujuan = trim($_POST['tujuan'] ?? '');
    $tanggal_keberangkatan = trim($_POST['tanggal_keberangkatan'] ?? '');
    $jam_keberangkatan = trim($_POST['jam_keberangkatan'] ?? '');
    $estimasi_durasi = trim($_POST['estimasi_durasi'] ?? '');
    $kelas_layanan = trim($_POST['kelas_layanan'] ?? 'reguler');
    $harga_perkursi = trim($_POST['harga_perkursi'] ?? '0');
    $kapasitas = intval($_POST['kapasitas'] ?? 0);

    // Validation
    if ($new_provider_flag) {
        if ($new_provider_name === '') $errors[] = "Nama provider baru wajib diisi.";
    } else {
        if (!is_numeric($provider_select) || intval($provider_select) <= 0) $errors[] = "Pilih provider atau pilih Tambah Provider Baru.";
    }

    if ($asal === '') $errors[] = "Field asal wajib diisi.";
    if ($tujuan === '') $errors[] = "Field tujuan wajib diisi.";
    if ($tanggal_keberangkatan === '') $errors[] = "Tanggal keberangkatan wajib diisi.";
    $today = date('Y-m-d');
    $max_date = date('Y-m-d', strtotime('+30 days'));
    if (strtotime($tanggal_keberangkatan) < strtotime($today) || strtotime($tanggal_keberangkatan) > strtotime($max_date)) {
        $errors[] = "Tanggal keberangkatan harus antara hari ini dan 30 hari ke depan.";
    }
    if ($jam_keberangkatan === '') $errors[] = "Jam keberangkatan wajib diisi.";
    if (!in_array($kelas_layanan, ['reguler','premium','bisnis'])) $errors[] = "Kelas layanan tidak valid.";
    if (!is_numeric($harga_perkursi) || floatval($harga_perkursi) <= 0) $errors[] = "Harga per kursi harus lebih besar dari 0.";

    if (empty($errors)) {
        mysqli_begin_transaction($conn);

        try {
            // If new provider -> insert it
            if ($new_provider_flag) {
                $stmt = mysqli_prepare($conn, "INSERT INTO providers (nama_provider, kontak, alamat, deskripsi, status) VALUES (?, ?, ?, ?, 'aktif')");
                mysqli_stmt_bind_param($stmt, "ssss", $new_provider_name, $new_provider_kontak, $new_provider_alamat, $new_provider_deskripsi);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Gagal insert provider: " . mysqli_error($conn));
                }
                $provider_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            } else {
                $provider_id = intval($provider_select);
                // Optional: check provider exists
                $q = mysqli_prepare($conn, "SELECT provider_id FROM providers WHERE provider_id = ?");
                mysqli_stmt_bind_param($q, "i", $provider_id);
                mysqli_stmt_execute($q);
                mysqli_stmt_store_result($q);
                if (mysqli_stmt_num_rows($q) === 0) {
                    mysqli_stmt_close($q);
                    throw new Exception("Provider terpilih tidak ditemukan.");
                }
                mysqli_stmt_close($q);
            }

            // Insert jadwal
            $insert = mysqli_prepare($conn, "INSERT INTO jadwal (provider_id, asal, tujuan, tanggal_keberangkatan, jam_keberangkatan, estimasi_durasi, kelas_layanan, harga_perkursi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'tersedia')");
            mysqli_stmt_bind_param($insert, "issssssd", $provider_id, $asal, $tujuan, $tanggal_keberangkatan, $jam_keberangkatan, $estimasi_durasi, $kelas_layanan, $harga_perkursi);
            // Note: harga_perkursi is decimal; binding as 'd' could be used but PHP mysqli uses 'd' for double; we'll cast to float
            // But above binding uses 's' for strings; adjust: use 'sd' pattern: change binding types accordingly.
            // To keep types correct, reprepare with proper types:
            mysqli_stmt_close($insert);

            $harga_float = floatval($harga_perkursi);
            $insert = mysqli_prepare($conn, "INSERT INTO jadwal (provider_id, asal, tujuan, tanggal_keberangkatan, jam_keberangkatan, estimasi_durasi, kelas_layanan, harga_perkursi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'tersedia')");
            mysqli_stmt_bind_param($insert, "issssssd", $provider_id, $asal, $tujuan, $tanggal_keberangkatan, $jam_keberangkatan, $estimasi_durasi, $kelas_layanan, $harga_float);
            if (!mysqli_stmt_execute($insert)) {
                throw new Exception("Gagal insert jadwal: " . mysqli_error($conn));
            }
            $jadwal_id = mysqli_insert_id($conn);
            mysqli_stmt_close($insert);

            // Generate seats based on kelas_layanan
            $seats = [];
            if ($kelas_layanan == 'reguler') {
                // 40 seats: Baris 1: A1 B1 C1 D1, Baris 2: A2 B2 C2 D2, etc.
                for ($num = 1; $num <= 10; $num++) {
                    for ($row = 'A'; $row <= 'D'; $row++) {
                        $seats[] = $row . $num;
                    }
                }
            } elseif ($kelas_layanan == 'premium') {
                // 30 seats: Baris 1: A1 B1 C1, Baris 2: A2 B2 C2, etc.
                for ($num = 1; $num <= 10; $num++) {
                    for ($row = 'A'; $row <= 'C'; $row++) {
                        $seats[] = $row . $num;
                    }
                }
            } elseif ($kelas_layanan == 'bisnis') {
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
                    if (!mysqli_stmt_execute($stmtSeat)) {
                        throw new Exception("Gagal insert kursi: " . mysqli_error($conn));
                    }
                }
                mysqli_stmt_close($stmtSeat);
            }

            mysqli_commit($conn);
            $success = true;
            // Redirect to bus list after success
            header("Location: bus.php?added=1");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

// Load providers for dropdown (existing)
$providers_res = mysqli_query($conn, "SELECT provider_id, nama_provider FROM providers WHERE status='aktif' ORDER BY nama_provider ASC");
$providers = [];
while ($p = mysqli_fetch_assoc($providers_res)) {
    $providers[] = $p;
}

?>

<div class="dashboard-title">Tambah Jadwal (2 Kolom)</div>

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

<form method="post" action="tambah_bus.php" id="formTambahJadwal">
    <div class="form-2cols">
        <!-- Left Column -->
        <div class="col-left">
            <label for="provider_select"><strong>Provider</strong></label>
            <select name="provider_select" id="provider_select" required>
                <option value="">-- Pilih Provider --</option>
                <?php foreach ($providers as $p): ?>
                    <option value="<?= $p['provider_id'] ?>"><?= htmlspecialchars($p['nama_provider']) ?></option>
                <?php endforeach; ?>
                <option value="new">-- Tambah Provider Baru --</option>
            </select>

            <div id="new_provider_fields" style="display:none;margin-top:12px;padding:10px;border:1px dashed #ddd;border-radius:6px;background:#fff;">
                <h4 style="margin-bottom:8px;color:#0a1f44;">Tambah Provider Baru</h4>
                <label>Nama Provider</label>
                <input type="text" name="new_provider_name" id="new_provider_name" placeholder="Nama provider">

                <label>Kontak</label>
                <input type="text" name="new_provider_kontak" id="new_provider_kontak" placeholder="No. HP / Telepon">

                <label>Alamat</label>
                <textarea name="new_provider_alamat" id="new_provider_alamat" rows="3" placeholder="Alamat lengkap"></textarea>

                <label>Deskripsi</label>
                <textarea name="new_provider_deskripsi" id="new_provider_deskripsi" rows="3" placeholder="Deskripsi singkat"></textarea>
            </div>

            <label for="asal"><strong>Asal</strong></label>
            <select name="asal" id="asal" required>
                <option value="">-- Pilih Kota Asal --</option>
                <optgroup label="Jawa Barat & DKI Jakarta">
                    <option value="Jakarta">Jakarta</option>
                    <option value="Bekasi">Bekasi</option>
                    <option value="Bandung">Bandung</option>
                    <option value="Tasikmalaya">Tasikmalaya</option>
                    <option value="Cirebon">Cirebon</option>
                </optgroup>
                <optgroup label="Jawa Tengah & Yogyakarta">
                    <option value="Semarang">Semarang</option>
                    <option value="Solo (Surakarta)">Solo (Surakarta)</option>
                    <option value="Yogyakarta">Yogyakarta</option>
                    <option value="Magelang">Magelang</option>
                    <option value="Purwokerto">Purwokerto</option>
                    <option value="Kudus / Jepara">Kudus / Jepara</option>
                </optgroup>
                <optgroup label="Jawa Timur">
                    <option value="Surabaya">Surabaya</option>
                    <option value="Malang">Malang</option>
                    <option value="Kediri">Kediri</option>
                    <option value="Tulungagung">Tulungagung</option>
                    <option value="Madiun">Madiun</option>
                    <option value="Banyuwangi (gerbang ke Bali)">Banyuwangi (gerbang ke Bali)</option>
                </optgroup>
                <optgroup label="Bali">
                    <option value="Denpasar">Denpasar</option>
                    <option value="Gianyar (Ubud)">Gianyar (Ubud)</option>
                    <option value="Tabanan">Tabanan</option>
                    <option value="Negara (Gilimanuk – pelabuhan masuk dari Jawa)">Negara (Gilimanuk – pelabuhan masuk dari Jawa)</option>
                </optgroup>
            </select>

            <label for="tujuan"><strong>Tujuan</strong></label>
            <select name="tujuan" id="tujuan" required>
                <option value="">-- Pilih Kota Tujuan --</option>
                <optgroup label="Jawa Barat & DKI Jakarta">
                    <option value="Jakarta">Jakarta</option>
                    <option value="Bekasi">Bekasi</option>
                    <option value="Bandung">Bandung</option>
                    <option value="Tasikmalaya">Tasikmalaya</option>
                    <option value="Cirebon">Cirebon</option>
                </optgroup>
                <optgroup label="Jawa Tengah & Yogyakarta">
                    <option value="Semarang">Semarang</option>
                    <option value="Solo (Surakarta)">Solo (Surakarta)</option>
                    <option value="Yogyakarta">Yogyakarta</option>
                    <option value="Magelang">Magelang</option>
                    <option value="Purwokerto">Purwokerto</option>
                    <option value="Kudus / Jepara">Kudus / Jepara</option>
                </optgroup>
                <optgroup label="Jawa Timur">
                    <option value="Surabaya">Surabaya</option>
                    <option value="Malang">Malang</option>
                    <option value="Kediri">Kediri</option>
                    <option value="Tulungagung">Tulungagung</option>
                    <option value="Madiun">Madiun</option>
N                    <option value="Banyuwangi (gerbang ke Bali)">Banyuwangi (gerbang ke Bali)</option>
                </optgroup>
                <optgroup label="Bali">
                    <option value="Denpasar">Denpasar</option>
                    <option value="Gianyar (Ubud)">Gianyar (Ubud)</option>
                    <option value="Tabanan">Tabanan</option>
                    <option value="Negara (Gilimanuk – pelabuhan masuk dari Jawa)">Negara (Gilimanuk – pelabuhan masuk dari Jawa)</option>
                </optgroup>
            </select>
        </div>

        <!-- Right Column -->
        <div class="col-right">
            <label for="tanggal_keberangkatan"><strong>Tanggal Keberangkatan</strong></label>
            <input type="date" name="tanggal_keberangkatan" id="tanggal_keberangkatan" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>

            <label for="jam_keberangkatan"><strong>Jam Keberangkatan</strong></label>
            <input type="time" name="jam_keberangkatan" id="jam_keberangkatan" required>

            <label for="estimasi_durasi"><strong>Estimasi Durasi</strong></label>
            <input type="text" name="estimasi_durasi" id="estimasi_durasi" placeholder="mis: 3 jam">

            <label for="kelas_layanan"><strong>Kelas Layanan</strong></label>
            <select name="kelas_layanan" id="kelas_layanan">
                <option value="reguler">Reguler</option>
                <option value="premium">Premium</option>
                <option value="bisnis">Bisnis</option>
            </select>

            <label for="harga_perkursi"><strong>Harga Per Kursi (Rp)</strong></label>
            <input type="number" name="harga_perkursi" id="harga_perkursi" min="0" step="1000" required placeholder="150000">

            <label for="kapasitas"><strong>Kapasitas (jumlah kursi)</strong></label>
            <input type="number" name="kapasitas" id="kapasitas" min="0" step="1" readonly placeholder="Jumlah kursi (otomatis)">
        </div>
    </div>

    <div style="margin-top:15px;">
        <button type="submit" class="btn-tambah">Simpan Jadwal</button>
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

<?php
include 'includes/footer.php';
?>
