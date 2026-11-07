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
            'nip'     => $nip,
            'pegawai' => $pegawai,
            'abkList' => $this->abkModel->getByNip($nip),
        ]);
    }

    public function simpan(string $nip)
    {
        $this->checkAccess($nip);

        $rules = [
            'nama_kegiatan' => 'required',
            'satuan'        => 'required',
            'volume'        => 'required|numeric',
            'norma_waktu'   => 'required|numeric',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/abk/' . $nip)->with('error', implode(', ', $this->validator->getErrors()));
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

        $wt = (float) $this->request->getPost('volume') * (float) $this->request->getPost('norma_waktu');

        $this->abkModel->save([
            'nip'                  => $nip,
            'nama_kegiatan'        => $this->request->getPost('nama_kegiatan'),
            'satuan'               => $this->request->getPost('satuan'),
            'volume'               => $this->request->getPost('volume'),
            'norma_waktu'          => $this->request->getPost('norma_waktu'),
            'waktu_penyelesaian'   => $wt,
            'keterangan'           => $this->request->getPost('keterangan') ?? '',
            'file'                 => $fileUrl,
        ]);

        return redirect()->to('/abk/' . $nip)->with('success', 'Data ABK berhasil ditambahkan.');
    }

    public function update(string $nip)
    {
        $this->checkAccess($nip);

        $id  = (int) $this->request->getPost('id_abk');
        $row = $this->abkModel->find($id);

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

        $wt = (float) $this->request->getPost('volume') * (float) $this->request->getPost('norma_waktu');

        $this->abkModel->update($id, [
            'nama_kegiatan'      => $this->request->getPost('nama_kegiatan'),
            'satuan'             => $this->request->getPost('satuan'),
            'volume'             => $this->request->getPost('volume'),
            'norma_waktu'        => $this->request->getPost('norma_waktu'),
            'waktu_penyelesaian' => $wt,
            'keterangan'         => $this->request->getPost('keterangan') ?? '',
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
