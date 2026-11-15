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
        if ($redirect = $this->guardEmployeeViewAccess($nip, 'diklat')) {
            return $redirect;
        }
        $role = session()->get('role');

        $pegawai = $this->pegawaiModel->findByNip($nip);
        if (! $pegawai) {
            $dash = match ($role) {
                'admin'  => '/dashboard/admin',
                'kepala' => '/dashboard/kepala',
                default  => '/dashboard/super-admin',
            };
            return redirect()->to($dash);
        }

        return view('diklat/index', [
            'nip'        => $nip,
            'pegawai'    => $pegawai,
            'diklatList' => $this->diklatModel->getByNip($nip),
            'can_edit'   => $this->canModifyEmployeeData($nip),
            'back_url'   => 'pegawai/profil/' . urlencode($nip),
            'page_title' => 'Daftar Diklat — ' . ($pegawai['nama'] ?? $nip),
            'title_icon' => 'fas fa-graduation-cap',
        ]);
    }

    public function tambah(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }
        $pegawai = $this->pegawaiModel->findByNip($nip);

        return view('diklat/form', [
            'nip'     => $nip,
            'pegawai' => $pegawai,
            'aksi'    => 'tambah',
        ]);
    }

    public function simpan(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }

        $namaDiklat = trim($this->request->getPost('nama_diklat') ?? '');
        if (empty($namaDiklat)) {
            return redirect()->to('/diklat/tambah/' . urlencode($nip))->with('error', 'Nama diklat tidak boleh kosong.');
        }

        $this->diklatModel->save(['nip' => $nip, 'nama_diklat' => $namaDiklat]);

        return redirect()->to('/diklat/' . urlencode($nip))->with('success', 'Data diklat berhasil ditambahkan.');
    }

    public function edit(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }

        $dataEdit = $this->findOwnedRecord($this->diklatModel, $id, $nip);
        if (! $dataEdit) {
            return redirect()->to('/diklat/' . urlencode($nip))->with('error', 'Data diklat tidak ditemukan.');
        }

        return view('diklat/form', [
            'nip'      => $nip,
            'pegawai'  => $this->pegawaiModel->findByNip($nip),
            'dataEdit' => $dataEdit,
            'aksi'     => 'edit',
        ]);
    }

    public function update(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }

        if (! $this->findOwnedRecord($this->diklatModel, $id, $nip)) {
            return redirect()->to('/diklat/' . urlencode($nip))->with('error', 'Data diklat tidak ditemukan.');
        }

        $namaDiklat = trim($this->request->getPost('nama_diklat') ?? '');
        if (empty($namaDiklat)) {
            return redirect()->to('/diklat/edit/' . urlencode($nip) . '/' . $id)->with('error', 'Nama diklat tidak boleh kosong.');
        }

        $this->diklatModel->update($id, ['nama_diklat' => $namaDiklat]);

        return redirect()->to('/diklat/' . urlencode($nip))->with('success', 'Data diklat berhasil diperbarui.');
    }

    public function hapus(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }

        if (! $this->findOwnedRecord($this->diklatModel, $id, $nip)) {
            return redirect()->to('/diklat/' . urlencode($nip))->with('error', 'Data diklat tidak ditemukan.');
        }

        $this->diklatModel->delete($id);

        return redirect()->to('/diklat/' . urlencode($nip))->with('success', 'Data diklat berhasil dihapus.');
    }
}
