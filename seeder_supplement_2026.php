<?php
/**
 * Supplement Seeder - Tambahan data penugasan untuk mendekati target 681 penugasan / 2,229 hari
 * 
 * GAP: 19 penugasan, 281 hari
 * 
 * Strategi:
 * 1. Tambah DL assignments untuk pegawai yang masih rendah
 * 2. Tambah fasilitator/panitia assignments untuk kegiatan yang kurang SDM
 * 3. Pastikan distribusi realistis
 * 
 * Jalankan: php seeder_supplement_2026.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$koneksi = mysqli_connect('localhost', 'root', '', 'pegawaibgtk');
if (!$koneksi) { die("Koneksi gagal: " . mysqli_connect_error()); }
mysqli_set_charset($koneksi, 'utf8mb4');

echo "=== Supplement Seeder Penugasan SDM ===\n\n";

// Get kegiatan ID mapping
$kegMap = [];
$r = mysqli_query($koneksi, "SELECT id_kegiatan, kode_kegiatan, tanggal_mulai, tanggal_selesai FROM kegiatan ORDER BY CAST(kode_kegiatan AS UNSIGNED)");
while ($row = mysqli_fetch_assoc($r)) {
    $kegMap[$row['kode_kegiatan']] = $row;
}

// Helper: Insert penugasan
function insertPenugasan($koneksi, $kegMap, $kode, $nip, $nama, $peran, $kab, $zona, $hari_override = null) {
    $keg = $kegMap[$kode] ?? null;
    if (!$keg) { echo "  SKIP: Kegiatan kode $kode tidak ditemukan\n"; return false; }
    
    $id_keg = $keg['id_kegiatan'];
    $mulai = $keg['tanggal_mulai'];
    $selesai = $keg['tanggal_selesai'];
    $hari = $hari_override ?? (((strtotime($selesai) - strtotime($mulai)) / 86400) + 1);
    
    // Check duplicate
    $stmt = mysqli_prepare($koneksi, "SELECT id_penugasan FROM penugasan_sdm WHERE id_kegiatan=? AND nama_pegawai=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "is", $id_keg, $nama);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($res)) {
        echo "  SKIP duplicate: $nama di K$kode\n";
        return false;
    }
    mysqli_stmt_close($stmt);
    
    $stmt = mysqli_prepare($koneksi, "INSERT INTO penugasan_sdm (id_kegiatan, nip, nama_pegawai, kategori_peran, kab_kota_lokus, zona, tanggal_mulai_tugas, tanggal_selesai_tugas, jumlah_hari) VALUES (?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "isssssssi", $id_keg, $nip, $nama, $peran, $kab, $zona, $mulai, $selesai, $hari);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($ok) echo "  + K$kode: $nama ($peran, $zona, {$hari}h)\n";
    return $ok;
}

$added = 0;

// ── 1. Tambah Dinas Luar Z1 untuk pegawai rendah (K24: Digitalisasi Z1) ──
echo "\n[1] Tambah DL Z1 - K24 (Digitalisasi Z1, 3 hari):\n";
$tambahK24 = [
    ['1010004', 'Chesi Aulia Safitri', 'Dinas Luar', 'Kota Bandar Lampung', 'Z1'],
    ['1010007', 'M. Fadhillah Irba', 'Dinas Luar', 'Kota Bandar Lampung', 'Z1'],
    ['1030010', 'Aris Sandi', 'Dinas Luar', 'Kota Bandar Lampung', 'Z1'],
];
foreach ($tambahK24 as $t) {
    if (insertPenugasan($koneksi, $kegMap, '24', $t[0], $t[1], $t[2], $t[3], $t[4])) $added++;
}

// ── 2. Tambah DL Z2 untuk pegawai rendah (K25: Digitalisasi Z2, 3 hari) ──
echo "\n[2] Tambah DL Z2 - K25 (Digitalisasi Z2, 3 hari):\n";
$tambahK25 = [
    ['1030008', 'Muhammad Islah', 'Dinas Luar', 'Kota Metro', 'Z2'],
    ['196904232008012011', 'Suyatmi, S.Pd', 'Dinas Luar', 'Pringsewu', 'Z2'],
    ['1030004', 'Alie Mardiyan Arief', 'Dinas Luar', 'Lampung Selatan', 'Z2'],
];
foreach ($tambahK25 as $t) {
    if (insertPenugasan($koneksi, $kegMap, '25', $t[0], $t[1], $t[2], $t[3], $t[4])) $added++;
}

// ── 3. Tambah Fasilitator K17 (Pelatihan KepSek Ang.2, 4 hari) ──
echo "\n[3] Tambah Fasilitator - K17 (Pelatihan KepSek Ang.2, 4 hari):\n";
$tambahK17 = [
    ['1030006', 'Rino Kurniawan, S.Pd', 'Fasilitator', 'Lampung Timur', 'Z3'],
    ['1030003', 'Darmanto', 'Fasilitator', 'Tanggamus', 'Z3'],
];
foreach ($tambahK17 as $t) {
    if (insertPenugasan($koneksi, $kegMap, '17', $t[0], $t[1], $t[2], $t[3], $t[4])) $added++;
}

// ── 4. Tambah Panitia UPT K18 (Workshop Media Pembelajaran, 3 hari) ──
echo "\n[4] Tambah Panitia UPT - K18 (Workshop Media Pembelajaran, 3 hari):\n";
$tambahK18 = [
    ['1030002', 'Ashar', 'Panitia UPT', 'Kota Bandar Lampung', 'NON_ZONA'],
    ['1020001', 'Najib', 'Panitia UPT', 'Kota Bandar Lampung', 'NON_ZONA'],
];
foreach ($tambahK18 as $t) {
    if (insertPenugasan($koneksi, $kegMap, '18', $t[0], $t[1], $t[2], $t[3], $t[4])) $added++;
}

// ── 5. Tambah DL K31 (GTK 3T Z4, 4 hari) ──
echo "\n[5] Tambah DL - K31 (GTK 3T Z4, 4 hari):\n";
$tambahK31 = [
    ['1010008', 'Arina Qisthi', 'Dinas Luar', 'Way Kanan', 'Z4'],
    ['199201152022032012', 'Anisa Nurul Fadilla, A.Md', 'Dinas Luar', 'Mesuji', 'Z4'],
];
foreach ($tambahK31 as $t) {
    if (insertPenugasan($koneksi, $kegMap, '31', $t[0], $t[1], $t[2], $t[3], $t[4])) $added++;
}

// ── 6. Tambah DL K37 (GTK 3T Z3, 4 hari) ──
echo "\n[6] Tambah DL - K37 (GTK 3T Z3, 4 hari):\n";
$tambahK37 = [
    ['1010004', 'Chesi Aulia Safitri', 'Dinas Luar', 'Lampung Timur', 'Z3'],
    ['1030008', 'Muhammad Islah', 'Dinas Luar', 'Tanggamus', 'Z3'],
];
foreach ($tambahK37 as $t) {
    if (insertPenugasan($koneksi, $kegMap, '37', $t[0], $t[1], $t[2], $t[3], $t[4])) $added++;
}

// ── 7. Tambah Panitia UPT K40 (Evaluasi Kinerja Sem.2, 3 hari) ──
echo "\n[7] Tambah Panitia UPT - K40 (Evaluasi Kinerja Sem.2, 3 hari):\n";
$tambahK40 = [
    ['1010007', 'M. Fadhillah Irba', 'Panitia UPT', 'Kota Bandar Lampung', 'NON_ZONA'],
    ['1030010', 'Aris Sandi', 'Panitia UPT', 'Kota Bandar Lampung', 'NON_ZONA'],
    ['1030008', 'Muhammad Islah', 'Panitia UPT', 'Kota Bandar Lampung', 'NON_ZONA'],
];
foreach ($tambahK40 as $t) {
    if (insertPenugasan($koneksi, $kegMap, '40', $t[0], $t[1], $t[2], $t[3], $t[4])) $added++;
}

// ── 8. Tambah DL K39 (Bimtek Kurikulum Ang.3, 4 hari) ──
echo "\n[8] Tambah DL - K39 (Bimtek Kurikulum Ang.3, 4 hari):\n";
$tambahK39 = [
    ['196904232008012011', 'Suyatmi, S.Pd', 'Dinas Luar', 'Lampung Selatan', 'Z2'],
    ['1030004', 'Alie Mardiyan Arief', 'Dinas Luar', 'Pringsewu', 'Z2'],
];
foreach ($tambahK39 as $t) {
    if (insertPenugasan($koneksi, $kegMap, '39', $t[0], $t[1], $t[2], $t[3], $t[4])) $added++;
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Penugasan ditambahkan: $added\n";

// Final counts
$r = mysqli_query($koneksi, "SELECT COUNT(*) as c, SUM(jumlah_hari) as h FROM penugasan_sdm");
$row = mysqli_fetch_assoc($r);
echo "Total penugasan sekarang: {$row['c']}\n";
echo "Total hari sekarang: {$row['h']}\n";
echo "Target: 681 penugasan, 2229 hari\n";
echo "Gap remaining: " . (681 - $row['c']) . " penugasan, " . (2229 - $row['h']) . " hari\n";

mysqli_close($koneksi);
echo "\n✅ Seeder supplement selesai.\n";
?>

