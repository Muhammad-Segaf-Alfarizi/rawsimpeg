<?php

namespace App\Controllers;

use App\Libraries\GDriveLibrary;
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
            'nip'          => $nip,
            'pegawai'      => $pegawai,
            'diklatList'   => $this->diklatModel->getByNip($nip),
            'modelOptions' => DiklatModel::MODEL_OPTIONS,
            'can_edit'     => $this->canModifyEmployeeData($nip),
            'back_url'     => 'pegawai/profil/' . urlencode($nip),
            'page_title'   => 'Daftar Diklat — ' . ($pegawai['nama'] ?? $nip),
            'title_icon'   => 'fas fa-graduation-cap',
        ]);
    }

    public function tambah(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }
        $this->diklatModel->ensureSchema();
        $pegawai = $this->pegawaiModel->findByNip($nip);

        return view('diklat/form', [
            'nip'          => $nip,
            'pegawai'      => $pegawai,
            'aksi'         => 'tambah',
            'modelOptions' => DiklatModel::MODEL_OPTIONS,
            'back_url'     => 'diklat/' . urlencode($nip),
            'page_title'   => 'Tambah Diklat — ' . ($pegawai['nama'] ?? $nip),
            'title_icon'   => 'fas fa-graduation-cap',
        ]);
    }

    public function simpan(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }
        $this->diklatModel->ensureSchema();

        $namaDiklat = trim((string) ($this->request->getPost('nama_diklat') ?? ''));
        if ($namaDiklat === '') {
            return redirect()->to('/diklat/tambah/' . urlencode($nip))
                ->withInput()
                ->with('error', 'Nama diklat / pelatihan wajib diisi.');
        }

        $tglMulai   = trim((string) ($this->request->getPost('tanggal_mulai') ?? '')) ?: null;
        $tglSelesai = trim((string) ($this->request->getPost('tanggal_selesai') ?? '')) ?: $tglMulai;
        $tempat     = trim((string) ($this->request->getPost('tempat_pelaksanaan') ?? '')) ?: null;
        $model      = trim((string) ($this->request->getPost('model_pelatihan') ?? '')) ?: null;

        if ($tglMulai && $tglSelesai && $tglSelesai < $tglMulai) {
            return redirect()->to('/diklat/tambah/' . urlencode($nip))
                ->withInput()
                ->with('error', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai pelatihan.');
        }

        if ($model !== null && ! array_key_exists($model, DiklatModel::MODEL_OPTIONS)) {
            $model = null;
        }

        [$uploadOk, $sertifikatUrl, $uploadErr] = $this->handleSertifikatUpload($nip, null, $tglMulai);
        if (! $uploadOk) {
            return redirect()->to('/diklat/tambah/' . urlencode($nip))
                ->withInput()
                ->with('error', $uploadErr);
        }

        $this->diklatModel->save([
            'nip'                => $nip,
            'nama_diklat'        => $namaDiklat,
            'tanggal_mulai'      => $tglMulai,
            'tanggal_selesai'    => $tglSelesai,
            'tempat_pelaksanaan' => $tempat,
            'model_pelatihan'    => $model,
            'file_sertifikat'    => $sertifikatUrl,
        ]);

        return redirect()->to('/diklat/' . urlencode($nip))
            ->with('success', 'Data diklat berhasil ditambahkan.');
    }

    public function edit(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }
        $this->diklatModel->ensureSchema();

        $dataEdit = $this->findOwnedRecord($this->diklatModel, $id, $nip);
        if (! $dataEdit) {
            return redirect()->to('/diklat/' . urlencode($nip))->with('error', 'Data diklat tidak ditemukan.');
        }

        $pegawai = $this->pegawaiModel->findByNip($nip);

        return view('diklat/form', [
            'nip'          => $nip,
            'pegawai'      => $pegawai,
            'dataEdit'     => $dataEdit,
            'aksi'         => 'edit',
            'modelOptions' => DiklatModel::MODEL_OPTIONS,
            'back_url'     => 'diklat/' . urlencode($nip),
            'page_title'   => 'Edit Diklat — ' . ($pegawai['nama'] ?? $nip),
            'title_icon'   => 'fas fa-graduation-cap',
        ]);
    }

    public function update(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }
        $this->diklatModel->ensureSchema();

        $existing = $this->findOwnedRecord($this->diklatModel, $id, $nip);
        if (! $existing) {
            return redirect()->to('/diklat/' . urlencode($nip))->with('error', 'Data diklat tidak ditemukan.');
        }

        $namaDiklat = trim((string) ($this->request->getPost('nama_diklat') ?? ''));
        if ($namaDiklat === '') {
            return redirect()->to('/diklat/edit/' . urlencode($nip) . '/' . $id)
                ->withInput()
                ->with('error', 'Nama diklat / pelatihan wajib diisi.');
        }

        $tglMulai   = trim((string) ($this->request->getPost('tanggal_mulai') ?? '')) ?: null;
        $tglSelesai = trim((string) ($this->request->getPost('tanggal_selesai') ?? '')) ?: $tglMulai;
        $tempat     = trim((string) ($this->request->getPost('tempat_pelaksanaan') ?? '')) ?: null;
        $model      = trim((string) ($this->request->getPost('model_pelatihan') ?? '')) ?: null;

        if ($tglMulai && $tglSelesai && $tglSelesai < $tglMulai) {
            return redirect()->to('/diklat/edit/' . urlencode($nip) . '/' . $id)
                ->withInput()
                ->with('error', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai pelatihan.');
        }

        if ($model !== null && ! array_key_exists($model, DiklatModel::MODEL_OPTIONS)) {
            $model = null;
        }

        [$uploadOk, $sertifikatUrl, $uploadErr] = $this->handleSertifikatUpload(
            $nip,
            $existing['file_sertifikat'] ?? null,
            $tglMulai
        );
        if (! $uploadOk) {
            return redirect()->to('/diklat/edit/' . urlencode($nip) . '/' . $id)
                ->withInput()
                ->with('error', $uploadErr);
        }

        $this->diklatModel->update($id, [
            'nama_diklat'        => $namaDiklat,
            'tanggal_mulai'      => $tglMulai,
            'tanggal_selesai'    => $tglSelesai,
            'tempat_pelaksanaan' => $tempat,
            'model_pelatihan'    => $model,
            'file_sertifikat'    => $sertifikatUrl,
        ]);

        return redirect()->to('/diklat/' . urlencode($nip))
            ->with('success', 'Data diklat berhasil diperbarui.');
    }

    public function uploadSertifikat(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }
        $this->diklatModel->ensureSchema();

        $existing = $this->findOwnedRecord($this->diklatModel, $id, $nip);
        if (! $existing) {
            return redirect()->to('/diklat/' . urlencode($nip))->with('error', 'Data diklat tidak ditemukan.');
        }

        $file = $this->request->getFile('file_sertifikat');
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return redirect()->to('/diklat/' . urlencode($nip))->with('error', 'Silakan pilih file sertifikat terlebih dahulu.');
        }

        [$uploadOk, $sertifikatUrl, $uploadErr] = $this->handleSertifikatUpload(
            $nip,
            $existing['file_sertifikat'] ?? null,
            $existing['tanggal_mulai'] ?? null
        );
        if (! $uploadOk) {
            return redirect()->to('/diklat/' . urlencode($nip))->with('error', $uploadErr);
        }

        $this->diklatModel->update($id, ['file_sertifikat' => $sertifikatUrl]);

        return redirect()->to('/diklat/' . urlencode($nip))
            ->with('success', 'Sertifikat diklat berhasil diunggah ke Google Drive.');
    }

    public function hapus(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'diklat')) {
            return $redirect;
        }
        $this->diklatModel->ensureSchema();

        $existing = $this->findOwnedRecord($this->diklatModel, $id, $nip);
        if (! $existing) {
            return redirect()->to('/diklat/' . urlencode($nip))->with('error', 'Data diklat tidak ditemukan.');
        }

        if (! empty($existing['file_sertifikat'])) {
            $this->createGDrive()->delete($existing['file_sertifikat']);
        }

        $this->diklatModel->delete($id);

        return redirect()->to('/diklat/' . urlencode($nip))->with('success', 'Data diklat berhasil dihapus.');
    }

    /**
     * Proses upload sertifikat (opsional) ke Google Drive:
     * Folder Induk (gdrive.folderIdPegawai) -> Nama Pegawai -> Tahun Pelatihan/Berjalan.
     *
     * @return array{0: bool, 1: ?string, 2: string}
     */
    private function handleSertifikatUpload(string $nip, ?string $existingUrl, ?string $tglMulai): array
    {
        $file = $this->request->getFile('file_sertifikat');
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return [true, $existingUrl, ''];
        }

        if (! $file->isValid() || $file->hasMoved()) {
            return [false, $existingUrl, 'File sertifikat tidak valid atau gagal diunggah.'];
        }

        if (! GDriveLibrary::validateFile($file, 5)) {
            return [false, $existingUrl, 'Format file tidak didukung atau ukuran melebihi 5 MB (gunakan PDF, JPG, PNG, atau DOC/DOCX).'];
        }

        $tahun = null;
        if (! empty($tglMulai) && preg_match('/^(\d{4})-/', $tglMulai, $m)) {
            $tahun = $m[1];
        }

        $newUrl = $this->uploadEmployeeAttachment(
            $this->pegawaiModel,
            $nip,
            'file_sertifikat',
            'sertifikat_diklat',
            $existingUrl,
            $tahun
        );

        if (! $newUrl || ($existingUrl === null && $newUrl === null) || ($existingUrl !== null && $newUrl === $existingUrl)) {
            return [false, $existingUrl, 'Gagal mengunggah sertifikat ke Google Drive. Silakan coba lagi.'];
        }

        return [true, $newUrl, ''];
    }
}
