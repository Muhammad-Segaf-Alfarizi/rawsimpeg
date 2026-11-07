<?php
/**
 * Seeder Otomatis Data Asli Kegiatan, Jadwal & Rekap Penugasan SDM BGTK Lampung 2026
 */
$mysqli = new mysqli('localhost', 'root', '', 'pegawaibgtk');
if ($mysqli->connect_error) {
    die('Koneksi gagal: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

// Jalankan DDL skema terbaru
$schemaSql = file_get_contents(__DIR__ . '/sql/schema_penugasan_2026.sql');
if ($mysqli->multi_query($schemaSql)) {
    do {
        if ($res = $mysqli->store_result()) { $res->free(); }
    } while ($mysqli->more_results() && $mysqli->next_result());
}

$dataset = json_decode(file_get_contents(__DIR__ . '/sql/bgtk_2026_dataset.json'), true);
if (!$dataset) { die('Gagal membaca dataset JSON.'); }

// 1. Update no_urut_rekap 1..56
$stmtPeg = $mysqli->prepare('UPDATE pegawai SET no_urut_rekap = ? WHERE nip = ?');
foreach ($dataset['pegawai_urut'] as $p) {
    $stmtPeg->bind_param('is', $p['no_urut'], $p['nip']);
    $stmtPeg->execute();
}
$stmtPeg->close();

// 2. Insert Kegiatan 1..43
$kodeToId = [];
$stmtKeg = $mysqli->prepare('INSERT INTO kegiatan (kode_kegiatan, nama_kegiatan, tim_kerja, warna_hex, kuota_keterangan, tanggal_mulai, tanggal_selesai, rentang_tanggal_teks, tempat_lokus, status_kegiatan, catatan_ket) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
foreach ($dataset['kegiatan'] as $k) {
    $stmtKeg->bind_param('sssssssssss', $k['kode'], $k['nama'], $k['tim_kerja'], $k['warna_hex'], $k['kuota'], $k['tgl_mulai'], $k['tgl_selesai'], $k['rentang_teks'], $k['tempat'], $k['status'], $k['ket']);
    $stmtKeg->execute();
    $kodeToId[$k['kode']] = $mysqli->insert_id;
}
$stmtKeg->close();

// 3. Insert Penugasan SDM
$stmtPen = $mysqli->prepare('INSERT INTO penugasan_sdm (id_kegiatan, nip, nama_pegawai, peran_detail, kategori_peran, kab_kota_lokus, detail_lokus, zona, tahap, tanggal_mulai_tugas, tanggal_selesai_tugas, jumlah_hari) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$insertedPen = 0;
foreach ($dataset['penugasan'] as $a) {
    $idKeg = $kodeToId[$a['kode']] ?? null;
    if (!$idKeg) continue;
    $stmtPen->bind_param('issssssssssi', $idKeg, $a['nip'], $a['nama_pegawai'], $a['peran_detail'], $a['kategori_peran'], $a['kab_kota_lokus'], $a['detail_lokus'], $a['zona'], $a['tahap'], $a['tgl_mulai'], $a['tgl_selesai'], $a['jumlah_hari']);
    $stmtPen->execute();
    $insertedPen++;
}
$stmtPen->close();

echo "BERHASIL SEEDING DATA ASLI BGTK 2026: " . count($kodeToId) . " Kegiatan & {$insertedPen} Penugasan SDM.\n";
