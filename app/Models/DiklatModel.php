<?php

namespace App\Models;

use CodeIgniter\Model;

class DiklatModel extends Model
{
    protected $table      = 'daftar_diklat';
    protected $primaryKey = 'id_daftar_diklat';

    protected $allowedFields = ['nip', 'nama_diklat'];

    public function getByNip(string $nip): array
    {
        return $this->where('nip', $nip)->orderBy('id_daftar_diklat', 'ASC')->findAll();
    }
}
