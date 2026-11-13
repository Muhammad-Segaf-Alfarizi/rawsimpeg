<?php

namespace App\Controllers;

use App\Models\PegawaiModel;
use App\Models\RiwayatStatusModel;

class Dashboard extends BaseController
{
    protected PegawaiModel $pegawaiModel;
    protected RiwayatStatusModel $riwayatModel;

    public function __construct()
    {
        $this->pegawaiModel  = new PegawaiModel();
        $this->riwayatModel  = new RiwayatStatusModel();
    }

    public function superAdmin()
    {
        $today = date('Y-m-d');
        $db    = \Config\Database::connect();

        $stats = [
            'total_pegawai'   => $this->pegawaiModel->countAll(),
            'total_dl'        => $db->query("SELECT COUNT(DISTINCT nip) AS c FROM penugasan_sdm WHERE ? BETWEEN tanggal_mulai_tugas AND tanggal_selesai_tugas", [$today])->getRow()->c ?? 0,
            'total_cuti'      => $db->query("SELECT COUNT(DISTINCT nip) AS c FROM riwayat_status WHERE status='Cuti' AND ? BETWEEN tanggal_mulai AND tanggal_selesai", [$today])->getRow()->c ?? 0,
            'total_abk_belum' => $db->query("SELECT COUNT(*) AS c FROM pegawai WHERE nip NOT IN (SELECT DISTINCT nip FROM abk)")->getRow()->c ?? 0,
        ];

        $pegawaiList = $this->pegawaiModel->getAllWithStats();

        foreach ($pegawaiList as &$peg) {
            $status = $this->riwayatModel->getStatusHariIni($peg['nip']);
            $peg['status_hari_ini'] = $status;
        }

        return view('dashboard/super_admin', [
            'stats'       => $stats,
            'pegawaiList' => $pegawaiList,
            'today'       => $today,
        ]);
    }

    public function admin()
    {
        try {
            $today = date('Y-m-d');
            $db    = \Config\Database::connect();

            $stats = [
                'total_pegawai'   => $this->pegawaiModel->countAll(),
                'total_dl'        => $db->query("SELECT COUNT(DISTINCT nip) AS c FROM penugasan_sdm WHERE ? BETWEEN tanggal_mulai_tugas AND tanggal_selesai_tugas", [$today])->getRow()->c ?? 0,
                'total_cuti'      => $db->query("SELECT COUNT(DISTINCT nip) AS c FROM riwayat_status WHERE status='Cuti' AND ? BETWEEN tanggal_mulai AND tanggal_selesai", [$today])->getRow()->c ?? 0,
                'total_abk_belum' => $db->query("SELECT COUNT(*) AS c FROM pegawai WHERE nip NOT IN (SELECT DISTINCT nip FROM abk)")->getRow()->c ?? 0,
            ];

            $pegawaiList = $this->pegawaiModel->getAllWithStats();

            foreach ($pegawaiList as &$peg) {
                $status = $this->riwayatModel->getStatusHariIni($peg['nip']);
                $peg['status_hari_ini'] = $status;
            }

            return view('dashboard/admin', [
                'stats'       => $stats,
                'pegawaiList' => $pegawaiList,
                'today'       => $today,
                'page_title'  => 'Dashboard Admin',
                'title_icon'  => 'fas fa-th-large',
            ]);

        } catch (\Throwable $e) {
            echo "<div style='background:#1e2130; color:#f87171; padding:20px; font-family:monospace; margin:20px; border-radius:8px;'>";
            echo "<h3>TERJADI ERROR DI ADMIN DASHBOARD:</h3>";
            echo "<p><strong>Pesan:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . " (Baris " . $e->getLine() . ")</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
            echo "</div>";
            exit;
        }
    }

    public function kepala()
    {
        $today = date('Y-m-d');
        $db    = \Config\Database::connect();

        $stats = [
            'total_pegawai' => $this->pegawaiModel->countAll(),
            'total_dl'      => $db->query("SELECT COUNT(DISTINCT nip) AS c FROM penugasan_sdm WHERE ? BETWEEN tanggal_mulai_tugas AND tanggal_selesai_tugas", [$today])->getRow()->c ?? 0,
            'total_cuti'    => $db->query("SELECT COUNT(DISTINCT nip) AS c FROM riwayat_status WHERE status='Cuti' AND ? BETWEEN tanggal_mulai AND tanggal_selesai", [$today])->getRow()->c ?? 0,
        ];

        $pegawaiList = $this->pegawaiModel->orderBy('no_urut_rekap', 'ASC')->orderBy('nama', 'ASC')->findAll();

        foreach ($pegawaiList as &$peg) {
            $peg['status_hari_ini'] = $this->riwayatModel->getStatusHariIni($peg['nip']);
        }

        return view('dashboard/kepala', [
            'stats'       => $stats,
            'pegawaiList' => $pegawaiList,
            'today'       => $today,
            'page_title'  => 'Dashboard Pimpinan',
            'title_icon'  => 'fas fa-th-large',
        ]);
    }
}