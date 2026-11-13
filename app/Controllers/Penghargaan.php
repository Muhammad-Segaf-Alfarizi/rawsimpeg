<?php

namespace App\Controllers;

use App\Models\PenghargaanModel;
use App\Models\PegawaiModel;

class Penghargaan extends BaseController
{
    protected PenghargaanModel $penghargaanModel;
    protected PegawaiModel $pegawaiModel;

    public function __construct()
    {
        $this->penghargaanModel = new PenghargaanModel();
        $this->pegawaiModel     = new PegawaiModel();
    }

    public function index(string $nip)
    {
        $this->checkAccess($nip);
        $pegawai = $this->pegawaiModel->findByNip($nip);

        return view('penghargaan/index', [
            'nip'             => $nip,
            'pegawai'         => $pegawai,
            'penghargaanList' => $this->penghargaanModel->getByNip($nip),
            'back_url'        => 'pegawai/profil/' . urlencode($nip),
            'page_title'      => 'Penghargaan — ' . ($pegawai['nama'] ?? $nip),
            'title_icon'      => 'fas fa-award',
        ]);
    }

    public function tambah(string $nip)
    {
        $this->checkAccess($nip);
        return view('penghargaan/form', ['nip' => $nip, 'pegawai' => $this->pegawaiModel->findByNip($nip), 'aksi' => 'tambah']);
    }

    public function simpan(string $nip)
    {
        $this->checkAccess($nip);

        $rules = [
            'nama_penghargaan' => 'required',
            'tahun'            => 'required|numeric',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/penghargaan/tambah/' . $nip)->with('error', implode(', ', $this->validator->getErrors()));
        }

        $this->penghargaanModel->save([
            'nip'              => $nip,
            'nama_penghargaan' => $this->request->getPost('nama_penghargaan'),
            'tahun'            => $this->request->getPost('tahun'),
            'pemberi'          => $this->request->getPost('pemberi'),
        ]);

        return redirect()->to('/penghargaan/' . $nip)->with('success', 'Penghargaan berhasil ditambahkan.');
    }

    public function edit(string $nip, int $id)
    {
        $this->checkAccess($nip);
        return view('penghargaan/form', [
            'nip'      => $nip,
            'pegawai'  => $this->pegawaiModel->findByNip($nip),
            'dataEdit' => $this->penghargaanModel->find($id),
            'aksi'     => 'edit',
        ]);
    }

    public function update(string $nip, int $id)
    {
        $this->checkAccess($nip);
        $this->penghargaanModel->update($id, [
            'nama_penghargaan' => $this->request->getPost('nama_penghargaan'),
            'tahun'            => $this->request->getPost('tahun'),
            'pemberi'          => $this->request->getPost('pemberi'),
        ]);
        return redirect()->to('/penghargaan/' . $nip)->with('success', 'Penghargaan berhasil diperbarui.');
    }

    public function hapus(string $nip, int $id)
    {
        $this->checkAccess($nip);
        $this->penghargaanModel->delete($id);
        return redirect()->to('/penghargaan/' . $nip)->with('success', 'Penghargaan berhasil dihapus.');
    }

    private function checkAccess(string $nip): void
    {
        $role = session()->get('role');
        if ($role === 'pegawai' && $nip !== session()->get('nip')) {
            redirect()->to('/penghargaan/' . session()->get('nip'))->send();
            exit;
        }
    }
}
