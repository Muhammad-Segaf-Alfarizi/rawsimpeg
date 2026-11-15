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
        if ($redirect = $this->guardEmployeeViewAccess($nip, 'penghargaan')) {
            return $redirect;
        }
        $pegawai = $this->pegawaiModel->findByNip($nip);

        return view('penghargaan/index', [
            'nip'             => $nip,
            'pegawai'         => $pegawai,
            'penghargaanList' => $this->penghargaanModel->getByNip($nip),
            'can_edit'        => $this->canModifyEmployeeData($nip),
            'back_url'        => 'pegawai/profil/' . urlencode($nip),
            'page_title'      => 'Penghargaan — ' . ($pegawai['nama'] ?? $nip),
            'title_icon'      => 'fas fa-award',
        ]);
    }

    public function tambah(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'penghargaan')) {
            return $redirect;
        }
        return view('penghargaan/form', ['nip' => $nip, 'pegawai' => $this->pegawaiModel->findByNip($nip), 'aksi' => 'tambah']);
    }

    public function simpan(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'penghargaan')) {
            return $redirect;
        }

        $rules = [
            'nama_penghargaan' => 'required',
            'tahun'            => 'required|numeric',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/penghargaan/tambah/' . urlencode($nip))->with('error', implode(', ', $this->validator->getErrors()));
        }

        $this->penghargaanModel->save([
            'nip'              => $nip,
            'nama_penghargaan' => $this->request->getPost('nama_penghargaan'),
            'tahun'            => $this->request->getPost('tahun'),
            'pemberi'          => $this->request->getPost('pemberi'),
        ]);

        return redirect()->to('/penghargaan/' . urlencode($nip))->with('success', 'Penghargaan berhasil ditambahkan.');
    }

    public function edit(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'penghargaan')) {
            return $redirect;
        }

        $dataEdit = $this->findOwnedRecord($this->penghargaanModel, $id, $nip);
        if (! $dataEdit) {
            return redirect()->to('/penghargaan/' . urlencode($nip))->with('error', 'Data penghargaan tidak ditemukan.');
        }

        return view('penghargaan/form', [
            'nip'      => $nip,
            'pegawai'  => $this->pegawaiModel->findByNip($nip),
            'dataEdit' => $dataEdit,
            'aksi'     => 'edit',
        ]);
    }

    public function update(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'penghargaan')) {
            return $redirect;
        }

        if (! $this->findOwnedRecord($this->penghargaanModel, $id, $nip)) {
            return redirect()->to('/penghargaan/' . urlencode($nip))->with('error', 'Data penghargaan tidak ditemukan.');
        }

        $this->penghargaanModel->update($id, [
            'nama_penghargaan' => $this->request->getPost('nama_penghargaan'),
            'tahun'            => $this->request->getPost('tahun'),
            'pemberi'          => $this->request->getPost('pemberi'),
        ]);
        return redirect()->to('/penghargaan/' . urlencode($nip))->with('success', 'Penghargaan berhasil diperbarui.');
    }

    public function hapus(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'penghargaan')) {
            return $redirect;
        }

        if (! $this->findOwnedRecord($this->penghargaanModel, $id, $nip)) {
            return redirect()->to('/penghargaan/' . urlencode($nip))->with('error', 'Data penghargaan tidak ditemukan.');
        }

        $this->penghargaanModel->delete($id);
        return redirect()->to('/penghargaan/' . urlencode($nip))->with('success', 'Penghargaan berhasil dihapus.');
    }
}
