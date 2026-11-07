<?php

namespace App\Controllers;

use App\Models\TnaModel;
use App\Models\PegawaiModel;
use App\Libraries\GDriveLibrary;

class Tna extends BaseController
{
    protected TnaModel $tnaModel;
    protected PegawaiModel $pegawaiModel;

    public function __construct()
    {
        $this->tnaModel     = new TnaModel();
        $this->pegawaiModel = new PegawaiModel();
    }

    // ─── INDEX ─────────────────────────────────────────────────────────────────
    public function index(string $nip)
    {
        $this->checkAccess($nip);
        $role    = session()->get('role');
        $pegawai = $this->pegawaiModel->findByNip($nip);

        // URL kembali berdasarkan role
        $backUrl = match($role) {
            'super_admin' => base_url('dashboard/super-admin'),
            'admin'       => base_url('dashboard/admin'),
            'kepala'      => base_url('dashboard/kepala'),
            default       => base_url('pegawai/profil/' . urlencode($nip)),
        };

        return view('tna/index', [
            'nip'        => $nip,
            'pegawai'    => $pegawai,
            'tnaList'    => $this->tnaModel->getByNip($nip),
            'back_url'   => $backUrl,
            'page_title' => 'TNA — ' . ($pegawai['nama'] ?? $nip),
            'title_icon' => 'fas fa-brain',
        ]);
    }

    // ─── SIMPAN (TAMBAH) ───────────────────────────────────────────────────────
    public function simpan(string $nip)
    {
        $this->checkAccess($nip);

        $rules = [
            'kompetensi_dibutuhkan' => 'required',
            'kondisi_saat_ini'      => 'required',
            'usulan_pelatihan'      => 'required',
            'prioritas'             => 'required|in_list[Tinggi,Sedang,Rendah]',
            'status_pelaksanaan'    => 'required|in_list[Belum,Sedang Berjalan,Selesai]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/tna/' . $nip)
                ->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $role    = session()->get('role');
        $fileUrl = $this->handleUpload($nip, 'dokumen_pendukung');

        // Pegawai → menunggu, Admin/Super Admin → langsung Disetujui
        $statusPersetujuan = ($role === 'pegawai') ? 'Menunggu Persetujuan' : 'Disetujui';

        $this->tnaModel->save([
            'nip'                    => $nip,
            'kompetensi_dibutuhkan'  => $this->request->getPost('kompetensi_dibutuhkan'),
            'kondisi_saat_ini'       => $this->request->getPost('kondisi_saat_ini'),
            'kategori_kesenjangan'   => $this->request->getPost('kategori_kesenjangan') ?? '',
            'usulan_pelatihan'       => $this->request->getPost('usulan_pelatihan'),
            'materi_pelatihan'       => $this->request->getPost('materi_pelatihan') ?? '',
            'indikator_keberhasilan' => $this->request->getPost('indikator_keberhasilan') ?? '',
            'metode_pelaksanaan'     => $this->request->getPost('metode_pelaksanaan') ?? '',
            'penyelenggara'          => $this->request->getPost('penyelenggara') ?? '',
            'jadwal_pelaksanaan'     => $this->request->getPost('jadwal_pelaksanaan') ?? '',
            'evaluasi_pasca'         => $this->request->getPost('evaluasi_pasca') ?? '',
            'catatan_rekomendasi'    => $this->request->getPost('catatan_rekomendasi') ?? '',
            'prioritas'              => $this->request->getPost('prioritas'),
            'status_pelaksanaan'     => $this->request->getPost('status_pelaksanaan'),
            'status_persetujuan'     => $statusPersetujuan,
            'dokumen_pendukung'      => $fileUrl,
        ]);

        $msg = ($role === 'pegawai')
            ? 'Usulan TNA berhasil diajukan dan menunggu persetujuan.'
            : 'Data TNA berhasil ditambahkan.';

        return redirect()->to('/tna/' . $nip)->with('success', $msg);
    }

    // ─── UPDATE (EDIT) ─────────────────────────────────────────────────────────
    public function update(string $nip)
    {
        $this->checkAccess($nip);

        $id  = (int) $this->request->getPost('id_tna');
        $row = $this->tnaModel->find($id);

        if (! $row) {
            return redirect()->to('/tna/' . $nip)->with('error', 'Data tidak ditemukan.');
        }

        $role    = session()->get('role');
        $fileUrl = $this->handleUpload($nip, 'dokumen_pendukung', $row['dokumen_pendukung'] ?? null);

        // Reset ke Menunggu Persetujuan jika pegawai edit
        $statusPersetujuan = ($role === 'pegawai') ? 'Menunggu Persetujuan' : ($row['status_persetujuan'] ?? 'Disetujui');

        $this->tnaModel->update($id, [
            'kompetensi_dibutuhkan'  => $this->request->getPost('kompetensi_dibutuhkan'),
            'kondisi_saat_ini'       => $this->request->getPost('kondisi_saat_ini'),
            'kategori_kesenjangan'   => $this->request->getPost('kategori_kesenjangan') ?? '',
            'usulan_pelatihan'       => $this->request->getPost('usulan_pelatihan'),
            'materi_pelatihan'       => $this->request->getPost('materi_pelatihan') ?? '',
            'indikator_keberhasilan' => $this->request->getPost('indikator_keberhasilan') ?? '',
            'metode_pelaksanaan'     => $this->request->getPost('metode_pelaksanaan') ?? '',
            'penyelenggara'          => $this->request->getPost('penyelenggara') ?? '',
            'jadwal_pelaksanaan'     => $this->request->getPost('jadwal_pelaksanaan') ?? '',
            'evaluasi_pasca'         => $this->request->getPost('evaluasi_pasca') ?? '',
            'catatan_rekomendasi'    => $this->request->getPost('catatan_rekomendasi') ?? '',
            'prioritas'              => $this->request->getPost('prioritas'),
            'status_pelaksanaan'     => $this->request->getPost('status_pelaksanaan'),
            'status_persetujuan'     => $statusPersetujuan,
            'dokumen_pendukung'      => $fileUrl,
        ]);

        return redirect()->to('/tna/' . $nip)->with('success', 'Data TNA berhasil diperbarui.');
    }

    // ─── HAPUS ─────────────────────────────────────────────────────────────────
    public function hapus(string $nip, int $id)
    {
        $this->checkAccess($nip);

        $row = $this->tnaModel->find($id);
        if ($row && ! empty($row['dokumen_pendukung'])) {
            (new GDriveLibrary())->delete($row['dokumen_pendukung']);
        }

        $this->tnaModel->delete($id);
        return redirect()->to('/tna/' . $nip)->with('success', 'Data TNA berhasil dihapus.');
    }

    // ─── PERSETUJUAN (hanya Super Admin & Admin) ───────────────────────────────
    public function persetujuan(string $nip, int $id)
    {
        $role = session()->get('role');
        if (! in_array($role, ['super_admin', 'admin'])) {
            return redirect()->to('/tna/' . $nip)->with('error', 'Akses ditolak.');
        }

        $aksi = $this->request->getPost('aksi'); // 'Disetujui' atau 'Ditolak'
        if (! in_array($aksi, ['Disetujui', 'Ditolak'])) {
            return redirect()->to('/tna/' . $nip)->with('error', 'Aksi tidak valid.');
        }

        $this->tnaModel->update($id, ['status_persetujuan' => $aksi]);

        return redirect()->to('/tna/' . $nip)
            ->with('success', 'Status persetujuan diubah menjadi: ' . $aksi);
    }

    // ─── HELPER: Upload File GDrive ────────────────────────────────────────────
    private function handleUpload(string $nip, string $inputName, ?string $existingUrl = null): ?string
    {
        $file = $this->request->getFile($inputName);

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $existingUrl; // Kembalikan file lama jika tidak ada upload baru
        }

        if (! GDriveLibrary::validateFile($file)) {
            return $existingUrl; // File tidak valid, kembalikan file lama
        }

        $gdrive = new GDriveLibrary();

        // Hapus file lama sebelum upload baru
        if ($existingUrl) {
            $gdrive->delete($existingUrl);
        }

        $url = $gdrive->upload(
            $file->getTempName(),
            $nip . '_TNA_' . time() . '.' . $file->getClientExtension(),
            $file->getMimeType(),
            env('gdrive.folderIdTna')
        );

        return ($url !== false) ? $url : $existingUrl;
    }

    // ─── HELPER: Cek Akses ────────────────────────────────────────────────────
    private function checkAccess(string $nip): void
    {
        $role = session()->get('role');
        if ($role === 'pegawai' && $nip !== session()->get('nip')) {
            redirect()->to('/tna/' . session()->get('nip'))->send();
            exit;
        }
    }
}

