<?php
/**
 * penugasan_helper.php
 * Helper functions untuk Modul Manajemen Kegiatan, Jadwal & Rekap Penugasan SDM
 * BGTK Provinsi Lampung — 2026
 */

// ============================================================
// 1. PEMETAAN ZONA DINAS LUAR
// ============================================================

/**
 * Daftar kabupaten/kota per zona di Provinsi Lampung
 */
function getZonaMapping(): array {
    return [
        'Z1' => ['Kota Bandar Lampung'],
        'Z2' => ['Lampung Selatan', 'Lampung Tengah', 'Pringsewu', 'Pesawaran', 'Kota Metro'],
        'Z3' => ['Lampung Utara', 'Lampung Timur', 'Tanggamus', 'Tulang Bawang', 'Tulang Bawang Barat'],
        'Z4' => ['Way Kanan', 'Lampung Barat', 'Pesisir Barat', 'Mesuji'],
    ];
}

/**
 * Daftar semua kabupaten/kota (flat) untuk dropdown
 */
function getAllKabKota(): array {
    $all = [];
    foreach (getZonaMapping() as $zona => $list) {
        foreach ($list as $kab) {
            $all[$kab] = $zona;
        }
    }
    return $all;
}

/**
 * Auto-detect zona dari nama kabupaten/kota
 */
function detectZona(string $kabKota): string {
    $kabKota = trim($kabKota);
    if ($kabKota === '') return 'NON_ZONA';
    $lower = mb_strtolower($kabKota);
    if (strpos($lower, 'bandar lampung') !== false) return 'Z1';
    if (strpos($lower, 'lampung selatan') !== false || strpos($lower, 'lamsel') !== false ||
        strpos($lower, 'lampung tengah') !== false || strpos($lower, 'lamteng') !== false ||
        strpos($lower, 'pringsewu') !== false || strpos($lower, 'pesawaran') !== false ||
        strpos($lower, 'metro') !== false) {
        return 'Z2';
    }
    if (strpos($lower, 'lampung utara') !== false || strpos($lower, 'lampura') !== false ||
        strpos($lower, 'lampung timur') !== false || strpos($lower, 'lamtim') !== false ||
        strpos($lower, 'tanggamus') !== false || strpos($lower, 'tulang bawang barat') !== false ||
        strpos($lower, 'tubaba') !== false || strpos($lower, 'tulang bawang') !== false ||
        strpos($lower, 'tuba') !== false) {
        return 'Z3';
    }
    if (strpos($lower, 'way kanan') !== false || strpos($lower, 'lampung barat') !== false ||
        strpos($lower, 'lambar') !== false || strpos($lower, 'pesisir barat') !== false ||
        strpos($lower, 'pesbar') !== false || strpos($lower, 'mesuji') !== false) {
        return 'Z4';
    }
    return 'NON_ZONA';
}

// ============================================================
// 2. PERHITUNGAN HARI
// ============================================================

/**
 * Hitung jumlah hari penuh (inklusif mulai & selesai)
 */
function hitungHariPenuh(?string $mulai, ?string $selesai): int {
    if (!$mulai || !$selesai) return 1;
    $ts_mulai   = strtotime($mulai);
    $ts_selesai = strtotime($selesai);
    if ($ts_mulai === false || $ts_selesai === false) return 1;
    $diff = (int)round(($ts_selesai - $ts_mulai) / 86400) + 1;
    return max($diff, 1);
}

// ============================================================
// 3. PEMETAAN WARNA TIM KERJA & KODE KEGIATAN
// ============================================================

function getTimKerjaOptions(): array {
    return [
        'Tim Kerja 1 : Peningkatan Kompetensi' => '#E5B8B7',
        'Tim Kerja 2 : Data dan Evaluasi'      => '#FFFF00',
        'Tim Kerja 3 : Kemitraan dan Publikasi'=> '#92D050',
        'Subbag Umum'                          => '#B6DDE8',
        'Lainnya'                              => '#D9D9D9',
    ];
}

// ============================================================
// 4. PEMETAAN NAMA PANGGILAN (ALIAS LOOKUP BGTK 2026)
// ============================================================

/**
 * Array alias → nama lengkap pegawai (sesuai dokumen BGTK 2026)
 */
function getAliasPegawai(): array {
    return [
        'kepala'        => 'Hendra Apriawan',
        'pak kepala'    => 'Hendra Apriawan',
        'hendra'        => 'Hendra Apriawan',
        'agus sup'      => 'Agus Supriyanto',
        'pak agus'      => 'Agus Supriyanto',
        'linda'         => 'Nuryulinda',
        'bu linda'      => 'Nuryulinda',
        'nur yulinda'   => 'Nuryulinda',
        'feris'         => 'Ferismayanti',
        'ferris'        => 'Ferismayanti',
        'bu feris'      => 'Ferismayanti',
        'bu wati'       => 'Tentrem Desilowati',
        'wati'          => 'Tentrem Desilowati',
        'tentrem'       => 'Tentrem Desilowati',
        'cecil'         => 'Cecilia Yuliana',
        'bu cecil'      => 'Cecilia Yuliana',
        'emil'          => 'Emilia Zulaiha Zahara',
        'emilia'        => 'Emilia Zulaiha Zahara',
        'wiwi'          => 'Wiwi Fitriyani',
        'wibi'          => 'Tri Wibawanto',
        'pak wibi'      => 'Tri Wibawanto',
        'tri'           => 'Tri Wibawanto',
        'diana'         => 'Diana Sari',
        'kamil'         => 'Kamil',
        'pak yudi'      => 'Yudi Setiadi',
        'yudi'          => 'Yudi Setiadi',
        'nurning'       => 'Nurningsih',
        'nurningsih'    => 'Nurningsih',
        'tomy'          => 'Tommy Marsan',
        'tomi'          => 'Tommy Marsan',
        'tommy'         => 'Tommy Marsan',
        'agung'         => 'Agung Sedayu',
        'erma'          => 'Erma Listiana',
        'orin'          => 'Lilis Setya Rini',
        'bu orin'       => 'Lilis Setya Rini',
        'lilis'         => 'Lilis Setya Rini',
        'nasib'         => 'Nasib Wasito',
        'echi'          => 'Destri Melyanda',
        'bu echi'       => 'Destri Melyanda',
        'destri'        => 'Destri Melyanda',
        'indah'         => 'Nur Indah Sari',
        'nur indah'     => 'Nur Indah Sari',
        'sohar'         => 'Agus Sohar',
        'agus sohar'    => 'Agus Sohar',
        'andi'          => 'Andi Priyantoko',
        'yatmi'         => 'Suyatmi',
        'suyatmi'       => 'Suyatmi',
        'rahman'        => 'Ursida Rahman',
        'ursida'        => 'Ursida Rahman',
        'ade'           => 'Ade Ridwan Maulana',
        'putri'         => 'Putri Arum Lestari',
        'tika'          => 'Kartika Nawati',
        'kartika'       => 'Kartika Nawati',
        'yeni'          => 'Yeni Marlia Kartika Sari',
        'pipit'         => 'Vidhya Deseva',
        'vidhya'        => 'Vidhya Deseva',
        'vidya'         => 'Vidhya Deseva',
        'irfan'         => 'Muhammad Irfan Fakhri',
        'awid'          => 'Annisa Awid Pramitha',
        'bambang'       => 'Bambang Sujito',
        'sahri'         => 'Sahri',
        'nisa'          => 'Anisa Nurul Fadilla',
        'anisa'         => 'Anisa Nurul Fadilla',
        'anisa nurul'   => 'Anisa Nurul Fadilla',
        'ial'           => 'Syahrial',
        'iyal'          => 'Syahrial',
        'syahrial'      => 'Syahrial',
        'fajar'         => 'Fajar Meiwaldi',
        'rina'          => 'Febrina Primasari Lubis',
        'febrina'       => 'Febrina Primasari Lubis',
        'chesi'         => 'Chesi Aulia Safitri',
        'chessi'        => 'Chesi Aulia Safitri',
        'sasa'          => 'Raisha Makhlida Ramadhanty',
        'raisa'         => 'Raisha Makhlida Ramadhanty',
        'raisha'        => 'Raisha Makhlida Ramadhanty',
        'danur'         => 'Bursadanur Arsyad',
        'bursa'         => 'Bursadanur Arsyad',
        'fadil'         => 'M. Fadhillah Irba',
        'fadhil'        => 'M. Fadhillah Irba',
        'najib'         => 'Najib',
        'zul'           => 'Zulkarnain',
        'zulkarnain'    => 'Zulkarnain',
        'ashar'         => 'Ashar',
        'darmanto'      => 'Darmanto',
        'ali'           => 'Alie Mardiyan Arief',
        'alie'          => 'Alie Mardiyan Arief',
        'triono'        => 'Triono',
        'rino'          => 'Rino Kurniawan',
        'santi'         => 'Santi',
        'susanti'       => 'Santi',
        'islah'         => 'Muhammad Islah',
        'adi'           => 'Adi Saputra',
        'adi s'         => 'Adi Saputra',
        'aris'          => 'Aris Sandi',
        'yuli'          => 'Yuliana Dewi',
        'yuliana'       => 'Yuliana Dewi',
        'sukri'         => 'Sukri',
    ];
}

/**
 * Resolve alias → nama lengkap pegawai
 */
function resolveAlias(string $input): ?string {
    $lower = mb_strtolower(trim($input));
    $aliases = getAliasPegawai();
    return $aliases[$lower] ?? null;
}

