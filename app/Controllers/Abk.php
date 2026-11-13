<?php

namespace App\Controllers;

use App\Models\AbkModel;
use App\Models\PegawaiModel;
use App\Libraries\GDriveLibrary;

class Abk extends BaseController
{
    protected AbkModel $abkModel;
    protected PegawaiModel $pegawaiModel;

    public function __construct()
    {
        $this->abkModel     = new AbkModel();
        $this->pegawaiModel = new PegawaiModel();
    }

    public function index(string $nip)
    {
        $this->checkAccess($nip);
        $pegawai = $this->pegawaiModel->findByNip($nip);

        return view('abk/index', [
            'nip'        => $nip,
            'pegawai'    => $pegawai,
            'abkList'    => $this->abkModel->getByNip($nip),
            'back_url'   => 'pegawai/profil/' . urlencode($nip),
            'page_title' => 'Analisis Beban Kerja — ' . ($pegawai['nama'] ?? $nip),
            'title_icon' => 'fas fa-briefcase',
        ]);
    }

    public function simpan(string $nip)
    {
        $this->checkAccess($nip);

        $uraianTugas = trim((string) ($this->request->getPost('uraian_tugas') ?? $this->request->getPost('nama_kegiatan') ?? ''));
        $satuanHasil = trim((string) ($this->request->getPost('satuan_hasil') ?? $this->request->getPost('satuan') ?? ''));
        $waktuMenit  = $this->request->getPost('waktu_penyelesaian') ?? $this->request->getPost('norma_waktu');
        $volumeKerja = $this->request->getPost('volume_kerja') ?? $this->request->getPost('volume');

        if ($uraianTugas === '' || $satuanHasil === '' || ! is_numeric($waktuMenit) || ! is_numeric($volumeKerja)) {
            return redirect()->to('/abk/' . $nip)->with('error', 'Uraian tugas pokok, satuan hasil, waktu (menit), dan volume kerja wajib diisi.');
        }

        $fileUrl = null;
        $file    = $this->request->getFile('file');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if (GDriveLibrary::validateFile($file)) {
                $gdrive  = new GDriveLibrary();
                $fileUrl = $gdrive->upload(
                    $file->getTempName(),
                    $nip . '_abk_' . time() . '.' . $file->getClientExtension(),
                    $file->getMimeType(),
                    env('gdrive.folderIdAbk')
                );
            }
        }

        $this->abkModel->save([
            'nip'                => $nip,
            'uraian_tugas'       => $uraianTugas,
            'nama_kegiatan'      => $uraianTugas,
            'satuan_hasil'       => $satuanHasil,
            'satuan'             => $satuanHasil,
            'waktu_penyelesaian' => (float) $waktuMenit,
            'norma_waktu'        => (float) $waktuMenit,
            'volume_kerja'       => (int) $volumeKerja,
            'volume'             => (float) $volumeKerja,
            'keterangan'         => $this->request->getPost('keterangan') ?? '',
            'file'               => $fileUrl,
        ]);

        return redirect()->to('/abk/' . $nip)->with('success', 'Data ABK berhasil ditambahkan.');
    }

    public function update(string $nip)
    {
        $this->checkAccess($nip);

        $id  = (int) $this->request->getPost('id_abk');
        $row = $this->abkModel->find($id);

        $uraianTugas = trim((string) ($this->request->getPost('uraian_tugas') ?? $this->request->getPost('nama_kegiatan') ?? ''));
        $satuanHasil = trim((string) ($this->request->getPost('satuan_hasil') ?? $this->request->getPost('satuan') ?? ''));
        $waktuMenit  = $this->request->getPost('waktu_penyelesaian') ?? $this->request->getPost('norma_waktu');
        $volumeKerja = $this->request->getPost('volume_kerja') ?? $this->request->getPost('volume');

        $fileUrl = $row['file'] ?? null;
        $file    = $this->request->getFile('file');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if (GDriveLibrary::validateFile($file)) {
                $gdrive = new GDriveLibrary();
                // Hapus file lama
                if ($fileUrl) $gdrive->delete($fileUrl);
                $fileUrl = $gdrive->upload(
                    $file->getTempName(),
                    $nip . '_abk_' . time() . '.' . $file->getClientExtension(),
                    $file->getMimeType(),
                    env('gdrive.folderIdAbk')
                );
            }
        }

        $this->abkModel->update($id, [
            'uraian_tugas'       => $uraianTugas,
            'nama_kegiatan'      => $uraianTugas,
            'satuan_hasil'       => $satuanHasil,
            'satuan'             => $satuanHasil,
            'waktu_penyelesaian' => (float) $waktuMenit,
            'norma_waktu'        => (float) $waktuMenit,
            'volume_kerja'       => (int) $volumeKerja,
            'volume'             => (float) $volumeKerja,
            'keterangan'         => $this->request->getPost('keterangan') ?? ($row['keterangan'] ?? ''),
            'file'               => $fileUrl,
        ]);

        return redirect()->to('/abk/' . $nip)->with('success', 'Data ABK berhasil diperbarui.');
    }

    public function hapus(string $nip, int $id)
    {
        $this->checkAccess($nip);

        $row = $this->abkModel->find($id);
        if ($row && $row['file']) {
            (new GDriveLibrary())->delete($row['file']);
        }

        $this->abkModel->delete($id);
        return redirect()->to('/abk/' . $nip)->with('success', 'Data ABK berhasil dihapus.');
    }

    private function checkAccess(string $nip): void
    {
        $role = session()->get('role');
        if ($role === 'pegawai' && $nip !== session()->get('nip')) {
            redirect()->to('/abk/' . session()->get('nip'))->send();
            exit;
        }
    }
}
