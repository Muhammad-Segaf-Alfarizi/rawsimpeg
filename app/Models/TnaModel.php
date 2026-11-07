<?php

namespace App\Models;

use CodeIgniter\Model;

class TnaModel extends Model
{
    protected $table      = 'tna';
    protected $primaryKey = 'id_tna';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'nip',
        'kompetensi_dibutuhkan',
        'kondisi_saat_ini',
        'kategori_kesenjangan',
        'usulan_pelatihan',
        'materi_pelatihan',
        'indikator_keberhasilan',
        'metode_pelaksanaan',
        'penyelenggara',
        'jadwal_pelaksanaan',
        'evaluasi_pasca',
        'catatan_rekomendasi',
        'prioritas',
        'status_pelaksanaan',
        'status_persetujuan',
        'dokumen_pendukung',
    ];

    public function getByNip(string $nip): array
    {
        return $this->where('nip', $nip)->orderBy('id_tna', 'DESC')->findAll();
    }

    public function countPendingByNip(string $nip): int
    {
        return $this->where('nip', $nip)
                    ->where('status_persetujuan', 'Menunggu Persetujuan')
                    ->countAllResults();
    }
}
