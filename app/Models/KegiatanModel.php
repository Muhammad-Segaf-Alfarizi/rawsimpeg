<?php

namespace App\Models;

use CodeIgniter\Model;

class KegiatanModel extends Model
{
    protected $table            = 'kegiatan';
    protected $primaryKey       = 'id_kegiatan';
    protected $allowedFields    = [
        'kode_kegiatan',
        'nama_kegiatan',
        'tim_kerja',
        'warna_hex',
        'kuota_keterangan',
        'tanggal_mulai',
        'tanggal_selesai',
        'rentang_tanggal_teks',
        'tempat_lokus',
        'status_kegiatan',
        'catatan_ket'
    ];
    protected $useTimestamps    = false;

    /**
     * Ambil seluruh kegiatan urut kode numerik (1 s.d. 43+) beserta rincian penugasan SDM-nya
     */
    public function getAllWithPenugasan(): array
    {
        $db = \Config\Database::connect();

        $kegiatan = $db->table('kegiatan')
            ->orderBy('CAST(kode_kegiatan AS UNSIGNED)', 'ASC')
            ->orderBy('id_kegiatan', 'ASC')
            ->get()
            ->getResultArray();

        $penugasanRows = $db->table('penugasan_sdm ps')
            ->select('ps.*, p.no_urut_rekap')
            ->join('pegawai p', 'p.nip = ps.nip', 'left')
            ->orderBy('ps.tahap', 'ASC')
            ->orderBy('ps.kategori_peran', 'ASC')
            ->orderBy('ps.kab_kota_lokus', 'ASC')
            ->orderBy('ps.detail_lokus', 'ASC')
            ->orderBy('p.no_urut_rekap', 'ASC')
            ->get()
            ->getResultArray();

        $byKegiatan = [];
        foreach ($penugasanRows as $r) {
            $byKegiatan[$r['id_kegiatan']][] = $r;
        }

        foreach ($kegiatan as &$k) {
            $list = $byKegiatan[$k['id_kegiatan']] ?? [];
            $k['penugasan'] = $list;
            $k['jumlah_sdm'] = count($list);

            // Kelompokkan SDM berdasarkan Tahap -> Peran/Kabupaten/Detail Lokus untuk tampilan seperti Excel "Kode dan SDM"
            $groups = [];
            foreach ($list as $item) {
                $tahapPrefix = !empty($item['tahap']) ? $item['tahap'] . ' — ' : '';
                if ($item['kategori_peran'] === 'Dinas Luar') {
                    $kab = $item['kab_kota_lokus'] ?: 'Lokus';
                    $det = $item['detail_lokus'] && $item['detail_lokus'] !== $kab && $item['detail_lokus'] !== '-'
                        ? " ({$item['detail_lokus']})"
                        : '';
                    $groupKey = $tahapPrefix . $kab . $det . " [{$item['zona']}]";
                } else {
                    $det = $item['detail_lokus'] && $item['detail_lokus'] !== '-'
                        ? " — {$item['detail_lokus']}"
                        : '';
                    $groupKey = $tahapPrefix . $item['kategori_peran'] . $det;
                }
                $groups[$groupKey][] = $item;
            }
            $k['grouped_sdm'] = $groups;
        }

        return $kegiatan;
    }
}
