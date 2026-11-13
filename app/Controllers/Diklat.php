<?php

namespace App\Controllers;

use App\Models\DiklatModel;
use App\Models\PegawaiModel;

class Diklat extends BaseController
{
    protected DiklatModel $diklatModel;
    protected PegawaiModel $pegawaiModel;

    public function __construct()
    {
        $this->diklatModel  = new DiklatModel();
        $this->pegawaiModel = new PegawaiModel();
    }

    public function index(string $nip)
    {
        $this->checkAccess($nip);

        $pegawai = $this->pegawaiModel->findByNip($nip);
        if (! $pegawai) {
            return redirect()->to('/dashboard/super-admin');
        }

        return view('diklat/index', [
            'nip'        => $nip,
            'pegawai'    => $pegawai,
            'diklatList' => $this->diklatModel->getByNip($nip),
            'back_url'   => 'pegawai/profil/' . urlencode($nip),
            'page_title' => 'Daftar Diklat — ' . ($pegawai['nama'] ?? $nip),
            'title_icon' => 'fas fa-graduation-cap',
        ]);
    }

    public function tambah(string $nip)
    {
        $this->checkAccess($nip);
        $pegawai = $this->pegawaiModel->findByNip($nip);

        return view('diklat/form', [
            'nip'     => $nip,
            'pegawai' => $pegawai,
            'aksi'    => 'tambah',
        ]);
    }

    public function simpan(string $nip)
    {
        $this->checkAccess($nip);

        $namaDiklat = trim($this->request->getPost('nama_diklat') ?? '');
        if (empty($namaDiklat)) {
            return redirect()->to('/diklat/tambah/' . $nip)->with('error', 'Nama diklat tidak boleh kosong.');
        }

        $this->diklatModel->save(['nip' => $nip, 'nama_diklat' => $namaDiklat]);

        return redirect()->to('/diklat/' . $nip)->with('success', 'Data diklat berhasil ditambahkan.');
    }

    public function edit(string $nip, int $id)
    {
        $this->checkAccess($nip);

        $pegawai   = $this->pegawaiModel->findByNip($nip);
        $dataEdit  = $this->diklatModel->find($id);

        return view('diklat/form', [
            'nip'      => $nip,
            'pegawai'  => $pegawai,
            'dataEdit' => $dataEdit,
            'aksi'     => 'edit',
        ]);
    }

    public function update(string $nip, int $id)
    {
        $this->checkAccess($nip);

        $namaDiklat = trim($this->request->getPost('nama_diklat') ?? '');
        if (empty($namaDiklat)) {
            return redirect()->to('/diklat/edit/' . $nip . '/' . $id)->with('error', 'Nama diklat tidak boleh kosong.');
        }

        $this->diklatModel->update($id, ['nama_diklat' => $namaDiklat]);

        return redirect()->to('/diklat/' . $nip)->with('success', 'Data diklat berhasil diperbarui.');
    }

    public function hapus(string $nip, int $id)
    {
        $this->checkAccess($nip);
        $this->diklatModel->delete($id);

        return redirect()->to('/diklat/' . $nip)->with('success', 'Data diklat berhasil dihapus.');
    }

    private function checkAccess(string $nip): void
    {
        $role = session()->get('role');
        if ($role === 'pegawai' && $nip !== session()->get('nip')) {
            redirect()->to('/diklat/' . session()->get('nip'))->send();
            exit;
        }
    }
}
