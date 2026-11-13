<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatStatusModel extends Model
{
    public const JENIS_CUTI_OPTIONS = [
        'Cuti Sakit',
        'Cuti Izin',
        'Cuti Alasan Penting',
        'Cuti Tahunan',
        'Cuti Melahirkan',
        'Tugas Kedinasan Lain',
    ];

    protected $table      = 'riwayat_status';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nip', 'status', 'jenis_cuti', 'keterangan', 'tanggal_mulai',
        'tanggal_selesai', 'no_spt', 'file', 'set_by',
    ];

    private static bool $schemaChecked = false;

    public function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            $db = \Config\Database::connect();
            if (! $db->fieldExists('jenis_cuti', $this->table)) {
                $db->query("ALTER TABLE {$this->table} ADD COLUMN jenis_cuti VARCHAR(100) NULL DEFAULT NULL AFTER status");
            }
        } catch (\Throwable $e) {
            log_message('error', 'RiwayatStatusModel::ensureSchema error: ' . $e->getMessage());
        }
    }

    public function getStatusHariIni(string $nip): ?array
    {
        if ($nip === '') {
            return null;
        }

        $this->ensureSchema();
        $today = date('Y-m-d');

        // 1. Cek Cuti / Tugas Kedinasan Lain aktif hari ini di riwayat_status
        $row = $this->where('nip', $nip)
            ->where('status !=', '')
            ->where('tanggal_mulai !=', '0000-00-00')
            ->where('tanggal_mulai <=', $today)
            ->where('tanggal_selesai >=', $today)
            ->orderBy('id', 'DESC')
            ->first();

        if ($row) {
            $row['keterangan_asli'] = $row['keterangan'] ?? '';
            if (! empty($row['jenis_cuti'])) {
                $row['keterangan'] = $row['jenis_cuti'] . (! empty($row['keterangan']) ? ' — ' . $row['keterangan'] : '');
            }
            return $row;
        }

        // 2. Jika tidak sedang cuti, cek apakah sedang ada penugasan aktif hari ini pada Kode & SDM
        $db = \Config\Database::connect();
        $ps = $db->table('penugasan_sdm ps')
            ->select('ps.*, k.kode_kegiatan, k.nama_kegiatan')
            ->join('kegiatan k', 'k.id_kegiatan = ps.id_kegiatan', 'inner')
            ->where('ps.nip', $nip)
            ->where('ps.tanggal_mulai_tugas <=', $today)
            ->where('ps.tanggal_selesai_tugas >=', $today)
            ->orderBy('ps.id_penugasan', 'DESC')
            ->get()
            ->getRowArray();

        if ($ps) {
            return [
                'id'              => null,
                'nip'             => $nip,
                'status'          => 'DL',
                'jenis_cuti'      => null,
                'keterangan'      => "[Kode {$ps['kode_kegiatan']}] {$ps['nama_kegiatan']} — {$ps['kategori_peran']}",
                'tanggal_mulai'   => $ps['tanggal_mulai_tugas'],
                'tanggal_selesai' => $ps['tanggal_selesai_tugas'],
                'no_spt'          => '',
                'file'            => null,
            ];
        }

        return null;
    }

    public function getKalender(string $nip): array
    {
        $this->ensureSchema();
        return $this->where('nip', $nip)
            ->where('tanggal_mulai !=', '0000-00-00')
            ->orderBy('tanggal_mulai', 'ASC')
            ->findAll();
    }

    /**
     * Ambil seluruh daftar Cuti & Tugas Kedinasan Lain beserta identitas pegawai
     */
    public function getDaftarCuti(?int $bulanFilter = null, ?string $nipFilter = null): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();

        $builder = $db->table('riwayat_status rs')
            ->select('rs.*, p.nama AS nama_pegawai, p.no_urut_rekap, p.jabatan')
            ->join('pegawai p', 'p.nip = rs.nip', 'inner')
            ->where('rs.status', 'Cuti')
            ->where('rs.tanggal_mulai IS NOT NULL')
            ->where('rs.tanggal_mulai !=', '0000-00-00')
            ->where('rs.tanggal_selesai IS NOT NULL')
            ->where('rs.tanggal_selesai !=', '0000-00-00');

        if (! empty($nipFilter)) {
            $builder->where('rs.nip', $nipFilter);
        }

        if ($bulanFilter !== null && $bulanFilter >= 1 && $bulanFilter <= 12) {
            $mStart = sprintf('2026-%02d-01', $bulanFilter);
            $mEnd   = date('Y-m-t', strtotime($mStart));
            $builder->where('rs.tanggal_mulai <=', $mEnd)
                    ->where('rs.tanggal_selesai >=', $mStart);
        }

        $rows = $builder
            ->orderBy('rs.tanggal_mulai', 'DESC')
            ->orderBy('p.no_urut_rekap', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$r) {
            if (empty($r['jenis_cuti'])) {
                $r['jenis_cuti'] = 'Cuti Tahunan';
            }
            $sTs = strtotime($r['tanggal_mulai']);
            $eTs = strtotime($r['tanggal_selesai']);
            $r['jumlah_hari'] = ($sTs && $eTs && $eTs >= $sTs)
                ? (int) round(($eTs - $sTs) / 86400) + 1
                : 1;
        }
        unset($r);

        return $rows;
    }

    /**
     * Cek apakah seorang pegawai sedang cuti pada rentang tanggal [tglMulai, tglSelesai]
     */
    public function cekBentrokCuti(string $nip, ?string $tglMulai, ?string $tglSelesai, ?int $excludeId = null): ?array
    {
        if ($nip === '' || empty($tglMulai) || empty($tglSelesai)) {
            return null;
        }

        $this->ensureSchema();
        $builder = $this->where('nip', $nip)
            ->where('status', 'Cuti')
            ->where('tanggal_mulai !=', '0000-00-00')
            ->where('tanggal_selesai !=', '0000-00-00')
            ->where('tanggal_mulai <=', $tglSelesai)
            ->where('tanggal_selesai >=', $tglMulai);

        if ($excludeId !== null && $excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }

        $row = $builder->orderBy('tanggal_mulai', 'ASC')->first();
        if ($row && empty($row['jenis_cuti'])) {
            $row['jenis_cuti'] = 'Cuti';
        }

        return $row;
    }

    /**
     * Peta NIP => data cuti untuk seluruh pegawai yang sedang cuti pada rentang [tglMulai, tglSelesai]
     */
    public function getMapCutiBentrok(?string $tglMulai, ?string $tglSelesai): array
    {
        if (empty($tglMulai) || empty($tglSelesai)) {
            return [];
        }

        $this->ensureSchema();
        $rows = $this->where('status', 'Cuti')
            ->where('tanggal_mulai !=', '0000-00-00')
            ->where('tanggal_selesai !=', '0000-00-00')
            ->where('tanggal_mulai <=', $tglSelesai)
            ->where('tanggal_selesai >=', $tglMulai)
            ->findAll();

        $map = [];
        foreach ($rows as $r) {
            if (empty($r['jenis_cuti'])) {
                $r['jenis_cuti'] = 'Cuti';
            }
            $map[$r['nip']] = $r;
        }

        return $map;
    }
}
