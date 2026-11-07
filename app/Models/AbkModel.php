<?php

namespace App\Models;

use CodeIgniter\Model;

class AbkModel extends Model
{
    protected $table      = 'abk';
    protected $primaryKey = 'id_abk';

    protected $allowedFields = [
        'nip', 'nama_kegiatan', 'satuan', 'volume', 'norma_waktu',
        'waktu_penyelesaian', 'keterangan', 'file',
    ];

    public function getByNip(string $nip): array
    {
        return $this->where('nip', $nip)->findAll();
    }
}
