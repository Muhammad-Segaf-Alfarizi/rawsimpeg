<?php

namespace App\Models;

use CodeIgniter\Model;

class PenugasanModel extends Model
{
    protected $table            = 'penugasan_sdm';
    protected $primaryKey       = 'id_penugasan';
    protected $allowedFields    = [
        'id_kegiatan',
        'nip',
        'nama_pegawai',
        'peran_detail',
        'kategori_peran',
        'kab_kota_lokus',
        'detail_lokus',
        'zona',
        'tahap',
        'tanggal_mulai_tugas',
        'tanggal_selesai_tugas',
        'jumlah_hari'
    ];

    /**
     * Ambil matriks rekapitulasi 56 pegawai sesuai struktur Multi-Header 4 Tingkat Excel "Rekap"
     */
    public function getMatriksRekap(): array
    {
        $db = \Config\Database::connect();

        $query = $db->query("
            SELECT 
                p.no_urut_rekap,
                p.nip,
                p.nama AS nama_pegawai,
                p.jabatan,
                -- Dinas Luar: Zona 1 (Bandar Lampung)
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' AND ps.zona = 'Z1' THEN 1 ELSE 0 END), 0) AS z1_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' AND ps.zona = 'Z1' THEN ps.jumlah_hari ELSE 0 END), 0) AS z1_hari,
                -- Dinas Luar: Zona 2 (Lamsel, Lamteng, Pringsewu, Pesawaran, Metro)
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' AND ps.zona = 'Z2' THEN 1 ELSE 0 END), 0) AS z2_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' AND ps.zona = 'Z2' THEN ps.jumlah_hari ELSE 0 END), 0) AS z2_hari,
                -- Dinas Luar: Zona 3 (Lampura, Lamtim, Tanggamus, Tuba, Tubaba)
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' AND ps.zona = 'Z3' THEN 1 ELSE 0 END), 0) AS z3_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' AND ps.zona = 'Z3' THEN ps.jumlah_hari ELSE 0 END), 0) AS z3_hari,
                -- Dinas Luar: Zona 4 (WK, Lambar, Pesbar, Mesuji)
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' AND ps.zona = 'Z4' THEN 1 ELSE 0 END), 0) AS z4_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' AND ps.zona = 'Z4' THEN ps.jumlah_hari ELSE 0 END), 0) AS z4_hari,
                -- Panitia: Hotel
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Panitia Hotel' THEN 1 ELSE 0 END), 0) AS pan_hotel_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Panitia Hotel' THEN ps.jumlah_hari ELSE 0 END), 0) AS pan_hotel_hari,
                -- Panitia: UPT Kemendikdasmen (BGTK/BPMP/BBPL)
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Panitia UPT' THEN 1 ELSE 0 END), 0) AS pan_upt_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Panitia UPT' THEN ps.jumlah_hari ELSE 0 END), 0) AS pan_upt_hari,
                -- Fasilitator
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Fasilitator' THEN 1 ELSE 0 END), 0) AS fasil_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Fasilitator' THEN ps.jumlah_hari ELSE 0 END), 0) AS fasil_hari,
                -- Peserta: Hotel
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Peserta Hotel' THEN 1 ELSE 0 END), 0) AS pes_hotel_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Peserta Hotel' THEN ps.jumlah_hari ELSE 0 END), 0) AS pes_hotel_hari,
                -- Peserta: UPT Kemendikdasmen (BGTK/BPMP/BBPL)
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Peserta UPT' THEN 1 ELSE 0 END), 0) AS pes_upt_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Peserta UPT' THEN ps.jumlah_hari ELSE 0 END), 0) AS pes_upt_hari,
                -- Grup JUMLAH (4 Kategori Utama)
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Fasilitator' THEN 1 ELSE 0 END), 0) AS jml_fasil_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Fasilitator' THEN ps.jumlah_hari ELSE 0 END), 0) AS jml_fasil_hari,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' OR ps.zona = 'K38_ALL' THEN 1 ELSE 0 END), 0) AS jml_dl_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran = 'Dinas Luar' OR ps.zona = 'K38_ALL' THEN ps.jumlah_hari ELSE 0 END), 0) AS jml_dl_hari,
                COALESCE(SUM(CASE WHEN ps.kategori_peran IN ('Panitia Hotel', 'Panitia UPT') THEN 1 ELSE 0 END), 0) AS jml_panitia_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran IN ('Panitia Hotel', 'Panitia UPT') THEN ps.jumlah_hari ELSE 0 END), 0) AS jml_panitia_hari,
                COALESCE(SUM(CASE WHEN ps.kategori_peran IN ('Peserta Hotel', 'Peserta UPT') THEN 1 ELSE 0 END), 0) AS jml_peserta_tugas,
                COALESCE(SUM(CASE WHEN ps.kategori_peran IN ('Peserta Hotel', 'Peserta UPT') THEN ps.jumlah_hari ELSE 0 END), 0) AS jml_peserta_hari,
                -- Grand Total per Pegawai
                COUNT(ps.id_penugasan) AS total_kegiatan,
                COALESCE(SUM(ps.jumlah_hari), 0) AS total_hari
            FROM pegawai p
            LEFT JOIN penugasan_sdm ps ON p.nip = ps.nip
            GROUP BY p.nip, p.nama, p.no_urut_rekap, p.jabatan, p.id
            ORDER BY p.no_urut_rekap ASC, p.id ASC
        ");

        return $query->getResultArray();
    }

    /**
     * Ambil matriks kalender Jadwal Tahunan (Gantt Chart) seperti Sheet "Jadwal" di Excel
     */
    public function getJadwalMatriks(?int $bulanFilter = null, ?string $nipFilter = null, ?string $kodeFilter = null): array
    {
        $db = \Config\Database::connect();

        // 1. Ambil 56 pegawai urut no_urut_rekap
        $pegBuilder = $db->table('pegawai')
            ->select('no_urut_rekap, nip, nama, jabatan')
            ->orderBy('no_urut_rekap', 'ASC')
            ->orderBy('id', 'ASC');

        if (!empty($nipFilter)) {
            $pegBuilder->where('nip', $nipFilter);
        }
        $pegawaiList = $pegBuilder->get()->getResultArray();

        // 2. Ambil seluruh penugasan beserta atribut kegiatan
        $penBuilder = $db->table('penugasan_sdm ps')
            ->select('ps.*, k.kode_kegiatan, k.nama_kegiatan, k.warna_hex, k.tim_kerja, k.tempat_lokus, k.status_kegiatan')
            ->join('kegiatan k', 'k.id_kegiatan = ps.id_kegiatan', 'inner')
            ->where('ps.tanggal_mulai_tugas IS NOT NULL')
            ->where('ps.tanggal_selesai_tugas IS NOT NULL')
            ->orderBy('ps.tanggal_mulai_tugas', 'ASC')
            ->orderBy('CAST(k.kode_kegiatan AS UNSIGNED)', 'ASC');

        if (!empty($nipFilter)) {
            $penBuilder->where('ps.nip', $nipFilter);
        }
        if (!empty($kodeFilter)) {
            $penBuilder->where('k.kode_kegiatan', $kodeFilter);
        }

        $allPenugasan = $penBuilder->get()->getResultArray();

        // 3. Map penugasan per NIP & Tanggal ('YYYY-MM-DD' => array of tasks)
        $calendarMap = [];
        $totalKegByNip = [];

        foreach ($allPenugasan as $row) {
            $nip = $row['nip'];
            $totalKegByNip[$nip] = ($totalKegByNip[$nip] ?? 0) + 1;

            $startTs = strtotime($row['tanggal_mulai_tugas']);
            $endTs   = strtotime($row['tanggal_selesai_tugas']);
            if ($startTs === false || $endTs === false || $endTs < $startTs) {
                continue;
            }

            // Batasi maksimal 60 hari per rentang untuk keamanan loop
            $maxDays = 60;
            $dCount = 0;
            for ($ts = $startTs; $ts <= $endTs && $dCount < $maxDays; $ts += 86400, $dCount++) {
                if ($bulanFilter !== null && (int)date('n', $ts) !== $bulanFilter) {
                    continue;
                }
                $dateKey = date('Y-m-d', $ts);
                $calendarMap[$nip][$dateKey][] = [
                    'id_penugasan'   => $row['id_penugasan'],
                    'id_kegiatan'    => $row['id_kegiatan'],
                    'kode_kegiatan'  => $row['kode_kegiatan'],
                    'nama_kegiatan'  => $row['nama_kegiatan'],
                    'warna_hex'      => $row['warna_hex'] ?: '#E5B8B7',
                    'kategori_peran' => $row['kategori_peran'],
                    'peran_detail'   => $row['peran_detail'],
                    'kab_kota_lokus' => $row['kab_kota_lokus'],
                    'detail_lokus'   => $row['detail_lokus'],
                    'zona'           => $row['zona'],
                    'tahap'          => $row['tahap'],
                    'tgl_mulai'      => $row['tanggal_mulai_tugas'],
                    'tgl_selesai'    => $row['tanggal_selesai_tugas'],
                    'jumlah_hari'    => $row['jumlah_hari'],
                ];
            }
        }

        return [
            'pegawai'        => $pegawaiList,
            'calendar_map'   => $calendarMap,
            'total_keg_nip'  => $totalKegByNip,
        ];
    }

    /**
     * Ambil rincian audit trail per pegawai untuk modal detail di Rekap & Jadwal
     */
    public function getDetailByPegawai(string $nipOrNama): array
    {
        return $this->select('penugasan_sdm.*, kegiatan.kode_kegiatan, kegiatan.nama_kegiatan, kegiatan.warna_hex, kegiatan.tim_kerja, kegiatan.tempat_lokus, kegiatan.status_kegiatan')
            ->join('kegiatan', 'kegiatan.id_kegiatan = penugasan_sdm.id_kegiatan', 'left')
            ->groupStart()
                ->where('penugasan_sdm.nip', $nipOrNama)
                ->orWhere('penugasan_sdm.nama_pegawai', $nipOrNama)
            ->groupEnd()
            ->orderBy('penugasan_sdm.tanggal_mulai_tugas', 'ASC')
            ->orderBy('CAST(kegiatan.kode_kegiatan AS UNSIGNED)', 'ASC')
            ->findAll();
    }
}
