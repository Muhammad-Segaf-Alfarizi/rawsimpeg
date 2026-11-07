<?php

namespace App\Controllers;

use App\Models\PegawaiModel;
use App\Models\RiwayatStatusModel;
use App\Libraries\GDriveLibrary;

class Pegawai extends BaseController
{
    protected PegawaiModel $pegawaiModel;
    protected RiwayatStatusModel $riwayatModel;

    public function __construct()
    {
        $this->pegawaiModel  = new PegawaiModel();
        $this->riwayatModel  = new RiwayatStatusModel();
    }

    public function profil(string $nip)
    {
        $role       = session()->get('role');
        $nipSession = session()->get('nip');

        // Proteksi: pegawai hanya bisa lihat profil sendiri
        if ($role === 'pegawai' && $nip !== $nipSession) {
            return redirect()->to('/pegawai/profil/' . $nipSession);
        }

        $data = $this->pegawaiModel->getProfilLengkap($nip);
        if (! $data) {
            return redirect()->to($this->getDashboardUrl($role))->with('error', 'Data pegawai tidak ditemukan.');
        }

        // Hitung usia
        $usia = '-';
        if (! empty($data['tanggal_lahir']) && $data['tanggal_lahir'] !== '0000-00-00') {
            $usia = (new \DateTime())->diff(new \DateTime($data['tanggal_lahir']))->y . ' tahun';
        }

        // Hitung KGB
        $kgb = $this->pegawaiModel->hitungKgb($data['tmt_awal'] ?? null);

        // Kalender kedinasan dari riwayat_status
        $kalender = $this->riwayatModel->getKalender($nip);

        // Kalender & daftar penugasan dari Kode dan SDM 2026 (penugasan_sdm + kegiatan)
        $penugasanModel = new \App\Models\PenugasanModel();
        $penugasanList  = $penugasanModel->getDetailByPegawai($nip);

        // Format kalender untuk FullCalendar (end bersifat eksklusif di FullCalendar, tambah +1 hari)
        $events = [];
        foreach ($kalender as $k) {
            if (empty($k['tanggal_mulai']) || empty($k['tanggal_selesai'])) {
                continue;
            }
            $events[] = [
                'title'     => $k['status'] . ($k['keterangan'] ? ' - ' . $k['keterangan'] : ''),
                'start'     => $k['tanggal_mulai'],
                'end'       => date('Y-m-d', strtotime($k['tanggal_selesai'] . ' +1 day')),
                'color'     => match ($k['status']) {
                    'DL'    => '#6366f1',
                    'Cuti'  => '#f59e0b',
                    default => '#10b981',
                },
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'source'      => 'riwayat_status',
                    'status'      => $k['status'],
                    'description' => ($k['status'] ?? '') . ($k['keterangan'] ? ' — ' . $k['keterangan'] : '') . ' (' . $k['tanggal_mulai'] . ' s.d. ' . $k['tanggal_selesai'] . ')',
                ],
            ];
        }

        // Tambahkan seluruh jadwal penugasan dari Kode dan SDM 2026 ke Kalender Kedinasan
        $today = date('Y-m-d');
        foreach ($penugasanList as $ps) {
            if (empty($ps['tanggal_mulai_tugas']) || empty($ps['tanggal_selesai_tugas'])) {
                continue;
            }
            $kode   = $ps['kode_kegiatan'] ?? '-';
            $namaK  = $ps['nama_kegiatan'] ?? '-';
            $peran  = $ps['kategori_peran'] ?? 'Penugasan';
            $peranD = ! empty($ps['peran_detail']) ? $ps['peran_detail'] : $peran;
            $kab    = $ps['kab_kota_lokus'] ?? '-';
            $detLok = $ps['detail_lokus'] ?? '-';
            $lokus  = ($detLok && $detLok !== '-' && $detLok !== $kab) ? "{$kab} ({$detLok})" : $kab;
            $warna  = ! empty($ps['warna_hex']) ? $ps['warna_hex'] : '#E5B8B7';
            $hari   = (int) ($ps['jumlah_hari'] ?? 1);
            $tahap  = ! empty($ps['tahap']) ? ' · ' . $ps['tahap'] : '';
            $zona   = (! empty($ps['zona']) && $ps['zona'] !== 'NON_ZONA') ? ' [' . $ps['zona'] . ']' : '';

            $events[] = [
                'title'           => "[Kode {$kode}] {$namaK} ({$peran}{$zona})",
                'start'           => $ps['tanggal_mulai_tugas'],
                'end'             => date('Y-m-d', strtotime($ps['tanggal_selesai_tugas'] . ' +1 day')),
                'backgroundColor' => $warna,
                'borderColor'     => 'rgba(15,23,42,0.35)',
                'textColor'       => '#0f172a',
                'extendedProps'   => [
                    'source'        => 'kode_sdm',
                    'id_kegiatan'   => $ps['id_kegiatan'] ?? null,
                    'kode'          => (string) $kode,
                    'nama_kegiatan' => $namaK,
                    'tim_kerja'     => $ps['tim_kerja'] ?? '-',
                    'peran'         => $peran,
                    'peran_detail'  => $peranD,
                    'lokus'         => $lokus,
                    'zona'          => $ps['zona'] ?? 'NON_ZONA',
                    'tahap'         => $ps['tahap'] ?? '',
                    'tanggal_mulai' => $ps['tanggal_mulai_tugas'],
                    'tanggal_selesai' => $ps['tanggal_selesai_tugas'],
                    'jumlah_hari'   => $hari,
                    'warna'         => $warna,
                    'description'   => "[Kode {$kode}] {$namaK} | {$peranD}{$tahap}{$zona} | Lokus: {$lokus} | {$ps['tanggal_mulai_tugas']} s.d. {$ps['tanggal_selesai_tugas']} ({$hari} hari)",
                ],
            ];

            // Jika hari ini pegawai sedang dalam masa tugas Kode & SDM dan status masih DK/kosong
            if ((empty($data['status']) || $data['status'] === 'DK')
                && $today >= $ps['tanggal_mulai_tugas']
                && $today <= $ps['tanggal_selesai_tugas']
            ) {
                $data['status']          = 'DL';
                $data['keterangan']      = "[Kode {$kode}] {$namaK} — {$peranD} ({$lokus})";
                $data['tanggal_mulai']   = $ps['tanggal_mulai_tugas'];
                $data['tanggal_selesai'] = $ps['tanggal_selesai_tugas'];
            }
        }

        return view('pegawai/profil', [
            'data'          => $data,
            'nip'           => $nip,
            'usia'          => $usia,
            'kgb'           => $kgb,
            'events'        => json_encode($events, JSON_UNESCAPED_UNICODE),
            'penugasanList' => $penugasanList,
            'role'          => $role,
            'can_edit'      => ($role === 'super_admin'),
            'back_url'      => $this->getDashboardUrl($role),
        ]);
    }

    public function tambah()
    {
        return view('pegawai/form_tambah');
    }

    public function simpan()
    {
        $rules = [
            'nama'           => 'required|min_length[3]',
            'nip'            => 'required|is_unique[pegawai.nip]',
            'status_pegawai' => 'required',
            'tmt_awal'       => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return view('pegawai/form_tambah', ['errors' => $this->validator->getErrors()]);
        }

        $skFields = ['sk_cpns', 'sk_pns', 'sk_tukin', 'sk_jabatan_terakhir', 'sk_pak', 'skp_terakhir'];
        $paths    = [];

        foreach ($skFields as $field) {
            $file = $this->request->getFile($field);
            if ($file && $file->isValid() && ! $file->hasMoved()) {
                $uploadPath = WRITEPATH . 'uploads/dokumen_pegawai/';
                if (! is_dir($uploadPath)) mkdir($uploadPath, 0777, true);

                $newName = $this->request->getPost('nip') . '_' . $field . '.' . $file->getClientExtension();
                $file->move($uploadPath, $newName);
                $paths[$field] = 'uploads/dokumen_pegawai/' . $newName;
            } else {
                $paths[$field] = null;
            }
        }

        $nipBaru  = trim((string) $this->request->getPost('nip'));
        $namaBaru = trim((string) $this->request->getPost('nama'));

        $this->pegawaiModel->save([
            'nip'                 => $nipBaru,
            'nama'                => $namaBaru,
            'status_pegawai'      => $this->request->getPost('status_pegawai'),
            'golongan'            => $this->request->getPost('golongan'),
            'jabatan'             => $this->request->getPost('jabatan'),
            'tanggal_lahir'       => $this->request->getPost('tgl_lahir') ?: null,
            'tmt_awal'            => $this->request->getPost('tmt_awal'),
            'pendidikan_terakhir' => $this->request->getPost('pendidikan_terakhir') ?: null,
            'lulusan_pendidikan'  => $this->request->getPost('lulusan_pendidikan') ?: null,
            'sk_cpns'             => $paths['sk_cpns'],
            'sk_pns'              => $paths['sk_pns'],
            'sk_tukin'            => $paths['sk_tukin'],
            'sk_jabatan_terakhir' => $paths['sk_jabatan_terakhir'],
            'sk_pak'              => $paths['sk_pak'],
            'skp_terakhir'        => $paths['skp_terakhir'],
        ]);

        // Otomatis buat akun user dengan password default (NIP) yang ter-hash Bcrypt jika belum ada
        $userModel = new \App\Models\UserModel();
        if (! $userModel->findByNip($nipBaru)) {
            $userModel->save([
                'nip'      => $nipBaru,
                'nama'     => $namaBaru,
                'role'     => 'pegawai',
                'password' => $userModel->hashPassword($nipBaru),
            ]);
        }

        return redirect()->to('/dashboard/super-admin')->with('success', 'Data pegawai & akun login (ter-hash Bcrypt) berhasil ditambahkan.');
    }

    public function edit(string $nip)
    {
        $data = $this->pegawaiModel->getProfilLengkap($nip);
        if (! $data) {
            return redirect()->to('/dashboard/super-admin')->with('error', 'Data pegawai tidak ditemukan.');
        }

        return view('pegawai/form_edit', ['data' => $data, 'nip' => $nip]);
    }

    public function update(string $nip)
    {
        $data = $this->pegawaiModel->findByNip($nip);
        if (! $data) {
            return redirect()->to('/dashboard/super-admin');
        }

        $updateData = [
            'nama'                => $this->request->getPost('nama'),
            'status_pegawai'      => $this->request->getPost('status_pegawai'),
            'golongan'            => $this->request->getPost('golongan'),
            'jabatan'             => $this->request->getPost('jabatan'),
            'tanggal_lahir'       => $this->request->getPost('tgl_lahir') ?: null,
            'tmt_awal'            => $this->request->getPost('tmt_awal'),
            'pendidikan_terakhir' => $this->request->getPost('pendidikan_terakhir'),
            'lulusan_pendidikan'  => $this->request->getPost('lulusan_pendidikan'),
        ];

        // Proses upload file SK
        $skFields = ['sk_cpns', 'sk_pns', 'sk_tukin', 'sk_jabatan_terakhir', 'sk_pak', 'skp_terakhir'];
        foreach ($skFields as $field) {
            $file = $this->request->getFile($field);
            if ($file && $file->isValid() && ! $file->hasMoved()) {
                $uploadPath = WRITEPATH . 'uploads/dokumen_pegawai/';
                if (! is_dir($uploadPath)) mkdir($uploadPath, 0777, true);

                $newName = $nip . '_' . $field . '.' . $file->getClientExtension();
                $file->move($uploadPath, $newName);
                $updateData[$field] = 'uploads/dokumen_pegawai/' . $newName;
            }
        }

        // Update status kedinasan jika ada
        $newStatus = $this->request->getPost('status');
        if ($newStatus) {
            $existingStatus = $this->riwayatModel->getStatusHariIni($nip);
            $statusData = [
                'nip'             => $nip,
                'status'          => $newStatus,
                'keterangan'      => $this->request->getPost('keterangan') ?? '',
                'tanggal_mulai'   => $this->request->getPost('tanggal_mulai'),
                'tanggal_selesai' => $this->request->getPost('tanggal_selesai'),
                'no_spt'          => $this->request->getPost('no_spt') ?? '',
                'set_by'          => session()->get('nip'),
            ];

            if ($existingStatus) {
                $this->riwayatModel->update($existingStatus['id'], $statusData);
            } else {
                $this->riwayatModel->save($statusData);
            }
        }

        $this->pegawaiModel->updateByNip($nip, $updateData);

        return redirect()->to('/dashboard/super-admin')->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function hapus(string $nip)
    {
        $this->pegawaiModel->deleteByNip($nip);
        return redirect()->to('/dashboard/super-admin')->with('success', 'Data pegawai berhasil dihapus.');
    }

    // ─── UPLOAD SK MANDIRI (Pegawai/Admin/Super Admin) ────────────────────────
    public function uploadSk(string $nip)
    {
        $role       = session()->get('role');
        $nipSession = session()->get('nip');

        // Proteksi: pegawai hanya bisa upload SK miliknya sendiri
        if ($role === 'pegawai' && $nip !== $nipSession) {
            return redirect()->to('/pegawai/profil/' . $nipSession)
                ->with('error', 'Anda tidak memiliki izin mengupload SK pegawai lain.');
        }

        $allowedFields = ['sk_cpns', 'sk_pns', 'sk_pak', 'sk_tukin', 'sk_jabatan_terakhir', 'skp_terakhir'];
        $fieldUpload   = $this->request->getPost('field_sk');

        if (! in_array($fieldUpload, $allowedFields)) {
            return redirect()->to('/pegawai/profil/' . urlencode($nip))
                ->with('error', 'Jenis dokumen tidak valid.');
        }

        $file = $this->request->getFile('file_sk');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to('/pegawai/profil/' . urlencode($nip))
                ->with('error', 'Tidak ada file yang dipilih atau file tidak valid.');
        }

        // Validasi ekstensi & ukuran (max 5MB)
        $allowedExt = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $ext = strtolower($file->getClientExtension());
        if (! in_array($ext, $allowedExt)) {
            return redirect()->to('/pegawai/profil/' . urlencode($nip))
                ->with('error', 'Format file tidak diizinkan. Gunakan PDF, DOC, JPG, atau PNG.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->to('/pegawai/profil/' . urlencode($nip))
                ->with('error', 'Ukuran file melebihi batas 5MB.');
        }

        // Ambil data pegawai untuk cek URL lama
        $pegawai  = $this->pegawaiModel->findByNip($nip);
        $oldUrl   = $pegawai[$fieldUpload] ?? null;

        // Upload ke Google Drive
        try {
            $gdrive  = new GDriveLibrary();

            // Hapus file lama di GDrive jika ada dan bukan path lokal
            if ($oldUrl && str_starts_with($oldUrl, 'https://')) {
                $gdrive->delete($oldUrl);
            }

            $fileName = $nip . '_' . $fieldUpload . '_' . time() . '.' . $ext;
            $fileUrl  = $gdrive->upload(
                $file->getTempName(),
                $fileName,
                $file->getMimeType(),
                env('gdrive.folderIdPegawai')
            );

            if ($fileUrl === false) {
                return redirect()->to('/pegawai/profil/' . urlencode($nip))
                    ->with('error', 'Gagal mengunggah file ke Google Drive. Coba lagi.');
            }
        } catch (\Exception $e) {
            log_message('error', 'uploadSk Error: ' . $e->getMessage());
            return redirect()->to('/pegawai/profil/' . urlencode($nip))
                ->with('error', 'Terjadi kesalahan saat upload: ' . $e->getMessage());
        }

        // Simpan URL ke database
        $this->pegawaiModel->updateByNip($nip, [$fieldUpload => $fileUrl]);

        $labelMap = [
            'sk_cpns'             => 'SK CPNS',
            'sk_pns'              => 'SK PNS',
            'sk_pak'              => 'SK PAK',
            'sk_tukin'            => 'SK TUKIN',
            'sk_jabatan_terakhir' => 'SK Jabatan Terakhir',
            'skp_terakhir'        => 'SKP Terakhir',
        ];

        return redirect()->to('/pegawai/profil/' . urlencode($nip))
            ->with('success', ($labelMap[$fieldUpload] ?? 'Dokumen SK') . ' berhasil diunggah.');
    }


    private function getDashboardUrl(string $role): string
    {
        return match ($role) {
            'super_admin' => '/dashboard/super-admin',
            'admin'       => '/dashboard/admin',
            'kepala'      => '/dashboard/kepala',
            default       => '/pegawai/profil/' . session()->get('nip'),
        };
    }
}
