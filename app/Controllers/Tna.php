<?php

namespace App\Controllers;

use App\Models\TnaModel;
use App\Models\PegawaiModel;

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
        if ($redirect = $this->guardEmployeeViewAccess($nip, 'tna')) {
            return $redirect;
        }
        $role    = session()->get('role');
        $pegawai = $this->pegawaiModel->findByNip($nip);

        // URL kembali berdasarkan role
        $backUrl = match($role) {
            'super_admin' => 'dashboard/super-admin',
            'admin'       => 'dashboard/admin',
            default       => 'pegawai/profil/' . urlencode($nip),
        };

        return view('tna/index', [
            'nip'        => $nip,
            'pegawai'    => $pegawai,
            'tnaList'    => $this->tnaModel->getByNip($nip),
            'can_edit'   => $this->canModifyEmployeeData($nip),
            'back_url'   => $backUrl,
            'page_title' => 'TNA — ' . ($pegawai['nama'] ?? $nip),
            'title_icon' => 'fas fa-brain',
        ]);
    }

    // ─── SIMPAN (TAMBAH) ───────────────────────────────────────────────────────
    public function simpan(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'tna')) {
            return $redirect;
        }

        $rules = [
            'kompetensi_dibutuhkan' => 'required',
            'kondisi_saat_ini'      => 'required',
            'usulan_pelatihan'      => 'required',
            'prioritas'             => 'required|in_list[Tinggi,Sedang,Rendah]',
            'status_pelaksanaan'    => 'required|in_list[Belum,Sedang Berjalan,Selesai]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/tna/' . urlencode($nip))
                ->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $role    = session()->get('role');
        $fileUrl = $this->uploadEmployeeAttachment($this->pegawaiModel, $nip, 'dokumen_pendukung', 'TNA');

        // Pegawai → menunggu, Kepala/Admin/Super Admin → langsung Disetujui
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

        return redirect()->to('/tna/' . urlencode($nip))->with('success', $msg);
    }

    // ─── UPDATE (EDIT) ─────────────────────────────────────────────────────────
    public function update(string $nip)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'tna')) {
            return $redirect;
        }

        $id  = (int) $this->request->getPost('id_tna');
        $row = $this->findOwnedRecord($this->tnaModel, $id, $nip);

        if (! $row) {
            return redirect()->to('/tna/' . urlencode($nip))->with('error', 'Data tidak ditemukan.');
        }

        $role    = session()->get('role');
        $fileUrl = $this->uploadEmployeeAttachment($this->pegawaiModel, $nip, 'dokumen_pendukung', 'TNA', $row['dokumen_pendukung'] ?? null);

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

        return redirect()->to('/tna/' . urlencode($nip))->with('success', 'Data TNA berhasil diperbarui.');
    }

    // ─── HAPUS ─────────────────────────────────────────────────────────────────
    public function hapus(string $nip, int $id)
    {
        if ($redirect = $this->guardEmployeeModifyAccess($nip, 'tna')) {
            return $redirect;
        }

        $row = $this->findOwnedRecord($this->tnaModel, $id, $nip);
        if (! $row) {
            return redirect()->to('/tna/' . urlencode($nip))->with('error', 'Data tidak ditemukan.');
        }

        if (! empty($row['dokumen_pendukung'])) {
            $this->createGDrive()->delete($row['dokumen_pendukung']);
        }

        $this->tnaModel->delete($id);
        return redirect()->to('/tna/' . urlencode($nip))->with('success', 'Data TNA berhasil dihapus.');
    }

    // ─── PERSETUJUAN (hanya Super Admin & Admin) ───────────────────────────────
    public function persetujuan(string $nip, int $id)
    {
        $role = session()->get('role');
        if (! in_array($role, ['super_admin', 'admin'])) {
            return redirect()->to('/tna/' . urlencode($nip))->with('error', 'Akses ditolak.');
        }

        if (! $this->findOwnedRecord($this->tnaModel, $id, $nip)) {
            return redirect()->to('/tna/' . urlencode($nip))->with('error', 'Data tidak ditemukan.');
        }

        $aksi = $this->request->getPost('aksi'); // 'Disetujui' atau 'Ditolak'
        if (! in_array($aksi, ['Disetujui', 'Ditolak'])) {
            return redirect()->to('/tna/' . urlencode($nip))->with('error', 'Aksi tidak valid.');
        }

        $this->tnaModel->update($id, ['status_persetujuan' => $aksi]);

        return redirect()->to('/tna/' . urlencode($nip))
            ->with('success', 'Status persetujuan diubah menjadi: ' . $aksi);
    }
}

