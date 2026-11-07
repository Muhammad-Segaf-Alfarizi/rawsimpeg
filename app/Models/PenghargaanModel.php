<?php

namespace App\Models;

use CodeIgniter\Model;

class PenghargaanModel extends Model
{
    protected $table      = 'penghargaan';
    protected $primaryKey = 'id_penghargaan';

    protected $allowedFields = ['nip', 'nama_penghargaan', 'tahun', 'pemberi'];

    public function getByNip(string $nip): array
    {
        return $this->where('nip', $nip)->orderBy('tahun', 'DESC')->findAll();
    }
}
