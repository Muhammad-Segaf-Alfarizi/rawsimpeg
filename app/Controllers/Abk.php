<?php

namespace App\Controllers;

use App\Models\AbkModel;
use App\Models\PegawaiModel;

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
        if ($redirect = $this->guardEmployeeViewAccess($nip, 'abk')) {
            return $redirect;
        }
        $pegawai = $this->pegawaiModel->findByNip($nip);

        return view('abk/index', [
            'nip'        => $nip,
            'pegawai'    => $pegawai,
            'abkList'    => $this->abkModel->getByNip($nip),
            'can_edit'   => $this->canModifyEmployeeData($nip),
            'back_url'   => 'pegawai/profil/' . urlencode($nip),
            'page_title' => 'Analisis Beban Kerja — ' . ($pegawai['nama'] ?? $nip),
            'title_icon' => 'fas fa-briefcase',
        ]);
    }

    public function simpan(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'abk')) {
            return $redirect;
        }

        $uraianTugas = trim((string) ($this->request->getPost('uraian_tugas') ?? $this->request->getPost('nama_kegiatan') ?? ''));
        $satuanHasil = trim((string) ($this->request->getPost('satuan_hasil') ?? $this->request->getPost('satuan') ?? ''));
        $waktuMenit  = $this->request->getPost('waktu_penyelesaian') ?? $this->request->getPost('norma_waktu');
        $volumeKerja = $this->request->getPost('volume_kerja') ?? $this->request->getPost('volume');

        if ($uraianTugas === '' || $satuanHasil === '' || ! is_numeric($waktuMenit) || ! is_numeric($volumeKerja)) {
            return redirect()->to('/abk/' . urlencode($nip))->with('error', 'Uraian tugas pokok, satuan hasil, waktu (menit), dan volume kerja wajib diisi.');
        }

        $fileUrl = $this->uploadEmployeeAttachment($this->pegawaiModel, $nip, 'file', 'abk');

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

        return redirect()->to('/abk/' . urlencode($nip))->with('success', 'Data ABK berhasil ditambahkan.');
    }

    public function update(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'abk')) {
            return $redirect;
        }

        $id  = (int) $this->request->getPost('id_abk');
        $row = $this->findOwnedRecord($this->abkModel, $id, $nip);
        if (! $row) {
            return redirect()->to('/abk/' . urlencode($nip))->with('error', 'Data ABK tidak ditemukan.');
        }

        $uraianTugas = trim((string) ($this->request->getPost('uraian_tugas') ?? $this->request->getPost('nama_kegiatan') ?? ''));
        $satuanHasil = trim((string) ($this->request->getPost('satuan_hasil') ?? $this->request->getPost('satuan') ?? ''));
        $waktuMenit  = $this->request->getPost('waktu_penyelesaian') ?? $this->request->getPost('norma_waktu');
        $volumeKerja = $this->request->getPost('volume_kerja') ?? $this->request->getPost('volume');

        $fileUrl = $this->uploadEmployeeAttachment($this->pegawaiModel, $nip, 'file', 'abk', $row['file'] ?? null);

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

        return redirect()->to('/abk/' . urlencode($nip))->with('success', 'Data ABK berhasil diperbarui.');
    }

    public function hapus(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'abk')) {
            return $redirect;
        }

        $row = $this->findOwnedRecord($this->abkModel, $id, $nip);
        if (! $row) {
            return redirect()->to('/abk/' . urlencode($nip))->with('error', 'Data ABK tidak ditemukan.');
        }

        if (! empty($row['file'])) {
            $this->createGDrive()->delete($row['file']);
        }

        $this->abkModel->delete($id);
        return redirect()->to('/abk/' . urlencode($nip))->with('success', 'Data ABK berhasil dihapus.');
    }
}
