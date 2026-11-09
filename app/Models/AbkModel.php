<?php

namespace App\Models;

use CodeIgniter\Model;

class AbkModel extends Model
{
    protected $table      = 'abk';
    protected $primaryKey = 'id_abk';

    protected $allowedFields = [
        'nip', 'nama_kegiatan', 'uraian_tugas', 'satuan', 'satuan_hasil',
        'volume', 'volume_kerja', 'norma_waktu', 'waktu_penyelesaian',
        'uraian_tahapan_kerja', 'kategori_tugas', 'peralatan_kerja',
        'catatan_norma_waktu', 'dokumen_pendukung', 'keterangan', 'file',
    ];

    public function getByNip(string $nip): array
    {
        return $this->where('nip', $nip)->findAll();
    }
}
