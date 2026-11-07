<?php

namespace App\Models;

use CodeIgniter\Model;

class PegawaiModel extends Model
{
    protected $table      = 'pegawai';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'nip', 'nama', 'status_pegawai', 'golongan', 'jabatan', 'no_urut_rekap',
        'tanggal_lahir', 'tmt_awal', 'pendidikan_terakhir', 'lulusan_pendidikan',
        'sk_cpns', 'sk_pns', 'sk_pak', 'sk_tukin', 'sk_jabatan_terakhir', 'skp_terakhir',
        'created_at', 'updated_at',
    ];

    protected $useTimestamps = true;

    /**
     * Cari pegawai berdasarkan NIP
     */
    public function findByNip(string $nip): ?array
    {
        return $this->where('nip', $nip)->first();
    }

    /**
     * Update pegawai berdasarkan NIP
     */
    public function updateByNip(string $nip, array $data): bool
    {
        return $this->where('nip', $nip)->set($data)->update();
    }

    /**
     * Hapus pegawai berdasarkan NIP
     */
    public function deleteByNip(string $nip): bool
    {
        return $this->where('nip', $nip)->delete();
    }

    /**
     * Ambil profil lengkap pegawai termasuk status kedinasan hari ini
     */
    public function getProfilLengkap(string $nip): ?array
    {
        $today = date('Y-m-d');

        $pegawai = $this->where('nip', $nip)->first();
        if (! $pegawai) {
            return null;
        }

        // Ambil status kedinasan aktif hari ini (atau terdekat)
        $db = \Config\Database::connect();
        $status = $db->query("
            SELECT * FROM riwayat_status
            WHERE nip = ?
            AND (
                CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai
                OR (NOT EXISTS (
                    SELECT 1 FROM riwayat_status WHERE nip = ? AND CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai
                ) AND tanggal_mulai > CURDATE())
            )
            ORDER BY CASE WHEN CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai THEN 0
                          ELSE DATEDIFF(tanggal_mulai, CURDATE()) END ASC
            LIMIT 1
        ", [$nip, $nip])->getRowArray();

        if ($status) {
            $pegawai = array_merge($pegawai, $status);
        } else {
            $pegawai['status']          = 'DK';
            $pegawai['keterangan']      = '';
            $pegawai['tanggal_mulai']   = '';
            $pegawai['tanggal_selesai'] = '';
            $pegawai['no_spt']          = '';
            $pegawai['file']            = null;
        }

        return $pegawai;
    }

    /**
     * Ambil semua pegawai + statistik ABK, TNA, dan status hari ini
     */
    public function getAllWithStats(): array
    {
        $today = date('Y-m-d');

        return $this->db->query("
            SELECT
                p.nip, p.nama, p.golongan, p.jabatan, p.no_urut_rekap,
                (SELECT COUNT(id_abk) FROM abk WHERE nip = p.nip) AS jml_abk,
                (SELECT COUNT(id_tna) FROM tna WHERE nip = p.nip) AS jml_tna,
                (SELECT COUNT(id_tna) FROM tna WHERE nip = p.nip AND status_pelaksanaan = 'Belum') AS tna_belum,
                (SELECT COUNT(id_tna) FROM tna WHERE nip = p.nip AND status_pelaksanaan = 'Sedang Berjalan') AS tna_berjalan
            FROM pegawai p
            ORDER BY COALESCE(p.no_urut_rekap, 999) ASC, p.nama ASC
        ")->getResultArray();
    }

    /**
     * Hitung KGB berdasarkan TMT Awal
     */
    public function hitungKgb(?string $tmtAwal): array
    {
        $result = ['terakhir' => '-', 'berikutnya' => '-'];
        if (empty($tmtAwal) || $tmtAwal === '0000-00-00') return $result;

        $today    = new \DateTime();
        $start    = new \DateTime($tmtAwal);
        $interval = new \DateInterval('P2Y');
        $kgbDates = [];

        while ($start <= $today) {
            $clone = clone $start;
            $clone->setDate((int) $clone->format('Y'), (int) $clone->format('m'), 1);
            $kgbDates[] = clone $clone;
            $start->add($interval);
        }

        if (! empty($kgbDates)) {
            $result['terakhir'] = end($kgbDates)->format('01 F Y');
        }
        $start->setDate((int) $start->format('Y'), (int) $start->format('m'), 1);
        $result['berikutnya'] = $start->format('01 F Y');

        return $result;
    }
}
