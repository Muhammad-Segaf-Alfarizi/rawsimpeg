<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatStatusModel extends Model
{
    protected $table      = 'riwayat_status';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nip', 'status', 'keterangan', 'tanggal_mulai',
        'tanggal_selesai', 'no_spt', 'file', 'set_by',
    ];

    public function getStatusHariIni(string $nip): ?array
    {
        $today = date('Y-m-d');
        return $this->where('nip', $nip)
                    ->where('tanggal_mulai <=', $today)
                    ->where('tanggal_selesai >=', $today)
                    ->first();
    }

    public function getKalender(string $nip): array
    {
        return $this->where('nip', $nip)
                    ->orderBy('tanggal_mulai', 'ASC')
                    ->findAll();
    }
}
