<?php

namespace App\Models;

use CodeIgniter\Model;

class DiklatModel extends Model
{
    public const MODEL_OPTIONS = [
        'Luring'  => 'Luring (Tatap Muka)',
        'Daring'  => 'Daring (Online)',
        'Blended' => 'Blended (Luring & Daring)',
    ];

    protected $table      = 'daftar_diklat';
    protected $primaryKey = 'id_daftar_diklat';

    protected $allowedFields = [
        'nip',
        'nama_diklat',
        'tanggal_mulai',
        'tanggal_selesai',
        'tempat_pelaksanaan',
        'model_pelatihan',
        'file_sertifikat',
    ];

    private static bool $schemaChecked = false;

    /**
     * Pastikan kolom tambahan pada tabel daftar_diklat sudah tersedia di database.
     */
    public function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            $db     = \Config\Database::connect();
            $fields = array_map('strtolower', $db->getFieldNames($this->table));

            $alterations = [];
            if (! in_array('tanggal_mulai', $fields, true)) {
                $alterations[] = 'ADD COLUMN `tanggal_mulai` DATE NULL DEFAULT NULL AFTER `nama_diklat`';
            }
            if (! in_array('tanggal_selesai', $fields, true)) {
                $alterations[] = 'ADD COLUMN `tanggal_selesai` DATE NULL DEFAULT NULL AFTER `tanggal_mulai`';
            }
            if (! in_array('tempat_pelaksanaan', $fields, true)) {
                $alterations[] = 'ADD COLUMN `tempat_pelaksanaan` VARCHAR(255) NULL DEFAULT NULL AFTER `tanggal_selesai`';
            }
            if (! in_array('model_pelatihan', $fields, true)) {
                $alterations[] = 'ADD COLUMN `model_pelatihan` VARCHAR(30) NULL DEFAULT NULL AFTER `tempat_pelaksanaan`';
            }
            if (! in_array('file_sertifikat', $fields, true)) {
                $alterations[] = 'ADD COLUMN `file_sertifikat` VARCHAR(255) NULL DEFAULT NULL AFTER `model_pelatihan`';
            }

            if (! empty($alterations)) {
                $db->query("ALTER TABLE `{$this->table}` " . implode(', ', $alterations));
            }
        } catch (\Throwable $e) {
            log_message('error', 'DiklatModel::ensureSchema error: ' . $e->getMessage());
        }
    }

    public function getByNip(string $nip): array
    {
        $this->ensureSchema();

        return $this->where('nip', $nip)
            ->orderBy("COALESCE(NULLIF(tanggal_mulai, '0000-00-00'), '0000-00-00')", 'DESC', false)
            ->orderBy('id_daftar_diklat', 'DESC')
            ->findAll();
    }
}
