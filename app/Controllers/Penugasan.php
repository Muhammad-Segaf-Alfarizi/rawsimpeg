<?php

namespace App\Controllers;

use App\Models\KegiatanModel;
use App\Models\PenugasanModel;
use App\Models\PegawaiModel;

require_once APPPATH . 'Helpers/penugasan_helper.php';

class Penugasan extends BaseController
{
    protected KegiatanModel $kegiatanModel;
    protected PenugasanModel $penugasanModel;
    protected PegawaiModel $pegawaiModel;

    public function __construct()
    {
        $this->kegiatanModel  = new KegiatanModel();
        $this->penugasanModel = new PenugasanModel();
        $this->pegawaiModel   = new PegawaiModel();
    }

    // ─── 1. HALAMAN KODE DAN SDM (/kegiatan) ────────────────────────────────────
    public function index()
    {
        $role         = (string) session()->get('role');
        $kegiatanList = $this->kegiatanModel->getAllWithPenugasan();
        $pegawaiList  = $this->pegawaiModel->orderBy('no_urut_rekap', 'ASC')->orderBy('id', 'ASC')->findAll();

        return view('penugasan/kode_sdm', [
            'page_title'      => 'Kode dan SDM Kegiatan BGTK 2026',
            'title_icon'      => 'fas fa-clipboard-list',
            'active_tab'      => 'kode_sdm',
            'kegiatanList'    => $kegiatanList,
            'pegawaiList'     => $pegawaiList,
            'aliasMap'        => getAliasPegawai(),
            'zonaMap'         => getZonaMapping(),
            'kabKotaMap'      => getAllKabKota(),
            'timKerjaOptions' => getTimKerjaOptions(),
            'role'            => $role,
            'back_url'        => $this->getDashboardUrl($role),
        ]);
    }

    // ─── 2. HALAMAN JADWAL MATRIKS KALENDER (/jadwal-penugasan) ─────────────────
    public function jadwal()
    {
        $role        = (string) session()->get('role');
        $bulanParam  = $this->request->getGet('bulan');
        $nipFilter   = trim((string) $this->request->getGet('nip'));
        $kodeFilter  = trim((string) $this->request->getGet('kode'));

        $bulanFilter = ($bulanParam !== null && $bulanParam !== '' && $bulanParam !== 'all')
            ? (int) $bulanParam
            : null;

        $jadwalData   = $this->penugasanModel->getJadwalMatriks($bulanFilter, $nipFilter ?: null, $kodeFilter ?: null);
        $kegiatanList = $this->kegiatanModel->orderBy('CAST(kode_kegiatan AS UNSIGNED)', 'ASC')->findAll();
        $pegawaiAll   = $this->pegawaiModel->orderBy('no_urut_rekap', 'ASC')->orderBy('id', 'ASC')->findAll();

        return view('penugasan/jadwal', [
            'page_title'   => 'Matriks Jadwal Penugasan SDM 2026',
            'title_icon'   => 'fas fa-calendar-check',
            'active_tab'   => 'jadwal',
            'jadwalData'   => $jadwalData,
            'kegiatanList' => $kegiatanList,
            'pegawaiAll'   => $pegawaiAll,
            'kabKotaMap'   => getAllKabKota(),
            'aliasMap'     => getAliasPegawai(),
            'bulanFilter'  => $bulanFilter,
            'nipFilter'    => $nipFilter,
            'kodeFilter'   => $kodeFilter,
            'role'         => $role,
            'back_url'     => $this->getDashboardUrl($role),
        ]);
    }

    // ─── 3. HALAMAN REKAP PENUGASAN 4-LEVEL HEADER (/rekap-penugasan) ───────────
    public function rekap()
    {
        $role         = (string) session()->get('role');
        $matriks      = $this->penugasanModel->getMatriksRekap();
        $kegiatanList = $this->kegiatanModel->orderBy('CAST(kode_kegiatan AS UNSIGNED)', 'ASC')->findAll();

        return view('penugasan/rekap', [
            'page_title'   => 'Rekapitulasi Penugasan SDM BGTK 2026',
            'title_icon'   => 'fas fa-chart-bar',
            'active_tab'   => 'rekap',
            'matriks'      => $matriks,
            'kegiatanList' => $kegiatanList,
            'kabKotaMap'   => getAllKabKota(),
            'role'         => $role,
            'back_url'     => $this->getDashboardUrl($role),
        ]);
    }

    // ─── CRUD MASTER KEGIATAN ───────────────────────────────────────────────────
    public function simpan()
    {
        $kode        = trim((string) $this->request->getPost('kode_kegiatan'));
        $nama        = trim((string) $this->request->getPost('nama_kegiatan'));
        $timKerja    = trim((string) $this->request->getPost('tim_kerja')) ?: 'Tim Kerja 1 : Peningkatan Kompetensi';
        $warnaHex    = trim((string) $this->request->getPost('warna_hex')) ?: '#E5B8B7';
        $kuota       = trim((string) $this->request->getPost('kuota_keterangan'));
        $tglMulai    = $this->request->getPost('tanggal_mulai') ?: null;
        $tglSelesai  = $this->request->getPost('tanggal_selesai') ?: null;
        $rentangTeks = trim((string) $this->request->getPost('rentang_tanggal_teks'));
        $tempat      = trim((string) $this->request->getPost('tempat_lokus'));
        $status      = trim((string) $this->request->getPost('status_kegiatan')) ?: 'TERJADWAL';
        $catatanKet  = trim((string) $this->request->getPost('catatan_ket')) ?: $status;

        if ($kode === '' || $nama === '') {
            return redirect()->back()->with('error', 'Kode Kegiatan dan Nama Kegiatan wajib diisi.');
        }

        $existing = $this->kegiatanModel->where('kode_kegiatan', $kode)->first();
        if ($existing) {
            return redirect()->back()->with('error', "Kode Kegiatan {$kode} sudah terdaftar.");
        }

        $this->kegiatanModel->insert([
            'kode_kegiatan'        => $kode,
            'nama_kegiatan'        => $nama,
            'tim_kerja'            => $timKerja,
            'warna_hex'            => $warnaHex,
            'kuota_keterangan'     => $kuota,
            'tanggal_mulai'        => $tglMulai,
            'tanggal_selesai'      => $tglSelesai,
            'rentang_tanggal_teks' => $rentangTeks ?: ($tglMulai && $tglSelesai ? "{$tglMulai} s.d. {$tglSelesai}" : '-'),
            'tempat_lokus'         => $tempat,
            'status_kegiatan'      => $status,
            'catatan_ket'          => $catatanKet,
        ]);

        return redirect()->to(base_url('kegiatan'))->with('success', "Kegiatan Kode {$kode} ({$nama}) berhasil ditambahkan.");
    }

    public function update()
    {
        $idKegiatan  = (int) $this->request->getPost('id_kegiatan');
        $kode        = trim((string) $this->request->getPost('kode_kegiatan'));
        $nama        = trim((string) $this->request->getPost('nama_kegiatan'));
        $timKerja    = trim((string) $this->request->getPost('tim_kerja')) ?: 'Tim Kerja 1 : Peningkatan Kompetensi';
        $warnaHex    = trim((string) $this->request->getPost('warna_hex')) ?: '#E5B8B7';
        $kuota       = trim((string) $this->request->getPost('kuota_keterangan'));
        $tglMulai    = $this->request->getPost('tanggal_mulai') ?: null;
        $tglSelesai  = $this->request->getPost('tanggal_selesai') ?: null;
        $rentangTeks = trim((string) $this->request->getPost('rentang_tanggal_teks'));
        $tempat      = trim((string) $this->request->getPost('tempat_lokus'));
        $status      = trim((string) $this->request->getPost('status_kegiatan')) ?: 'TERJADWAL';
        $catatanKet  = trim((string) $this->request->getPost('catatan_ket')) ?: $status;

        if (!$idKegiatan || $kode === '' || $nama === '') {
            return redirect()->back()->with('error', 'Data kegiatan tidak lengkap.');
        }

        $this->kegiatanModel->update($idKegiatan, [
            'kode_kegiatan'        => $kode,
            'nama_kegiatan'        => $nama,
            'tim_kerja'            => $timKerja,
            'warna_hex'            => $warnaHex,
            'kuota_keterangan'     => $kuota,
            'tanggal_mulai'        => $tglMulai,
            'tanggal_selesai'      => $tglSelesai,
            'rentang_tanggal_teks' => $rentangTeks ?: ($tglMulai && $tglSelesai ? "{$tglMulai} s.d. {$tglSelesai}" : '-'),
            'tempat_lokus'         => $tempat,
            'status_kegiatan'      => $status,
            'catatan_ket'          => $catatanKet,
        ]);

        return redirect()->to(base_url('kegiatan'))->with('success', "Kegiatan Kode {$kode} berhasil diperbarui.");
    }

    public function hapus(int $idKegiatan)
    {
        $keg = $this->kegiatanModel->find($idKegiatan);
        if (!$keg) {
            return redirect()->back()->with('error', 'Kegiatan tidak ditemukan.');
        }

        $this->penugasanModel->where('id_kegiatan', $idKegiatan)->delete();
        $this->kegiatanModel->delete($idKegiatan);

        return redirect()->to(base_url('kegiatan'))->with('success', "Kegiatan Kode {$keg['kode_kegiatan']} beserta seluruh penugasannya berhasil dihapus.");
    }

    // ─── CRUD PENUGASAN SDM ─────────────────────────────────────────────────────
    public function rincianKegiatan(int $idKegiatan)
    {
        $keg = $this->kegiatanModel->find($idKegiatan);
        if (!$keg) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kegiatan tidak ditemukan']);
        }

        $db = \Config\Database::connect();
        $list = $db->table('penugasan_sdm ps')
            ->select('ps.*, p.no_urut_rekap')
            ->join('pegawai p', 'p.nip = ps.nip', 'left')
            ->where('ps.id_kegiatan', $idKegiatan)
            ->orderBy('ps.tahap', 'ASC')
            ->orderBy('ps.kategori_peran', 'ASC')
            ->orderBy('ps.kab_kota_lokus', 'ASC')
            ->orderBy('p.no_urut_rekap', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'    => 'ok',
            'kegiatan'  => $keg,
            'penugasan' => $list,
        ]);
    }

    public function simpanPenugasan()
    {
        $idKegiatan   = (int) $this->request->getPost('id_kegiatan');
        $nip          = trim((string) $this->request->getPost('nip'));
        $katPeran     = trim((string) $this->request->getPost('kategori_peran')) ?: 'Dinas Luar';
        $peranDetail  = trim((string) $this->request->getPost('peran_detail')) ?: $katPeran;
        $kabKota      = trim((string) $this->request->getPost('kab_kota_lokus'));
        $detailLokus  = trim((string) $this->request->getPost('detail_lokus'));
        $tahap        = trim((string) $this->request->getPost('tahap')) ?: null;
        $tglMulai     = $this->request->getPost('tanggal_mulai_tugas') ?: null;
        $tglSelesai   = $this->request->getPost('tanggal_selesai_tugas') ?: null;
        $redirectBack = $this->request->getPost('redirect_to') ?: 'kegiatan';

        if (!$idKegiatan || $nip === '') {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Kegiatan dan Pegawai wajib dipilih.']);
            }
            return redirect()->back()->with('error', 'Kegiatan dan Pegawai wajib dipilih.');
        }

        $keg = $this->kegiatanModel->find($idKegiatan);
        $peg = $this->pegawaiModel->where('nip', $nip)->first();
        if (!$keg || !$peg) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Data kegiatan atau pegawai tidak valid.']);
            }
            return redirect()->back()->with('error', 'Data kegiatan atau pegawai tidak valid.');
        }

        if (!$tglMulai) $tglMulai = $keg['tanggal_mulai'];
        if (!$tglSelesai) $tglSelesai = $keg['tanggal_selesai'] ?: $tglMulai;
        $jumlahHari = hitungHariPenuh($tglMulai, $tglSelesai);

        $zona = ($katPeran === 'Dinas Luar')
            ? detectZona($kabKota ?: $detailLokus)
            : 'NON_ZONA';

        if ($detailLokus === '') {
            $detailLokus = $keg['tempat_lokus'] ?: ($kabKota ?: '-');
        }
        if ($kabKota === '' && $katPeran !== 'Dinas Luar') {
            $kabKota = 'Kota Bandar Lampung';
        }

        $insertId = $this->penugasanModel->insert([
            'id_kegiatan'           => $idKegiatan,
            'nip'                   => $peg['nip'],
            'nama_pegawai'          => $peg['nama'],
            'peran_detail'          => $peranDetail,
            'kategori_peran'        => $katPeran,
            'kab_kota_lokus'        => $kabKota,
            'detail_lokus'          => $detailLokus,
            'zona'                  => $zona,
            'tahap'                 => $tahap,
            'tanggal_mulai_tugas'   => $tglMulai,
            'tanggal_selesai_tugas' => $tglSelesai,
            'jumlah_hari'           => $jumlahHari,
        ]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => 'ok',
                'id_penugasan' => $insertId,
                'message'      => "Penugasan {$peg['nama']} pada Kode {$keg['kode_kegiatan']} ({$jumlahHari} hari) berhasil ditambahkan.",
            ]);
        }

        return redirect()->to(base_url($redirectBack))->with('success', "Penugasan {$peg['nama']} pada Kode {$keg['kode_kegiatan']} berhasil ditambahkan.");
    }

    public function updatePenugasan()
    {
        $idPenugasan = (int) $this->request->getPost('id_penugasan');
        $existing    = $this->penugasanModel->find($idPenugasan);
        if (!$existing) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Data penugasan tidak ditemukan.']);
            }
            return redirect()->back()->with('error', 'Data penugasan tidak ditemukan.');
        }

        $idKegiatan  = (int) ($this->request->getPost('id_kegiatan') ?: $existing['id_kegiatan']);
        $katPeran    = trim((string) $this->request->getPost('kategori_peran')) ?: $existing['kategori_peran'];
        $peranDetail = trim((string) $this->request->getPost('peran_detail')) ?: $katPeran;
        $kabKota     = trim((string) $this->request->getPost('kab_kota_lokus'));
        $detailLokus = trim((string) $this->request->getPost('detail_lokus'));
        $tahap       = trim((string) $this->request->getPost('tahap')) ?: null;
        $tglMulai    = $this->request->getPost('tanggal_mulai_tugas') ?: $existing['tanggal_mulai_tugas'];
        $tglSelesai  = $this->request->getPost('tanggal_selesai_tugas') ?: $existing['tanggal_selesai_tugas'];
        $jumlahHari  = hitungHariPenuh($tglMulai, $tglSelesai);

        $zona = ($katPeran === 'Dinas Luar')
            ? detectZona($kabKota ?: $detailLokus)
            : 'NON_ZONA';

        $this->penugasanModel->update($idPenugasan, [
            'id_kegiatan'           => $idKegiatan,
            'peran_detail'          => $peranDetail,
            'kategori_peran'        => $katPeran,
            'kab_kota_lokus'        => $kabKota,
            'detail_lokus'          => $detailLokus,
            'zona'                  => $zona,
            'tahap'                 => $tahap,
            'tanggal_mulai_tugas'   => $tglMulai,
            'tanggal_selesai_tugas' => $tglSelesai,
            'jumlah_hari'           => $jumlahHari,
        ]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'ok',
                'message' => 'Data penugasan berhasil diperbarui.',
            ]);
        }

        $redirectBack = $this->request->getPost('redirect_to') ?: 'kegiatan';
        return redirect()->to(base_url($redirectBack))->with('success', 'Data penugasan berhasil diperbarui.');
    }

    public function hapusPenugasan(int $idPenugasan)
    {
        $row = $this->penugasanModel->find($idPenugasan);
        if (!$row) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Data penugasan tidak ditemukan.']);
            }
            return redirect()->back()->with('error', 'Data penugasan tidak ditemukan.');
        }

        $this->penugasanModel->delete($idPenugasan);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'ok',
                'message' => "Penugasan {$row['nama_pegawai']} berhasil dihapus.",
            ]);
        }

        return redirect()->back()->with('success', "Penugasan {$row['nama_pegawai']} berhasil dihapus.");
    }

    // ─── BULK ASSIGNMENT (PLOT SELURUH PEGAWAI) ─────────────────────────────────
    public function plotBulk()
    {
        $idKegiatan  = (int) $this->request->getPost('id_kegiatan');
        $mode        = trim((string) $this->request->getPost('mode')) ?: 'peserta_selain_panitia';
        $kabKota     = trim((string) $this->request->getPost('kab_kota_lokus')) ?: 'Kota Bandar Lampung';
        $detailLokus = trim((string) $this->request->getPost('detail_lokus'));
        $tglMulai    = $this->request->getPost('tanggal_mulai_tugas');
        $tglSelesai  = $this->request->getPost('tanggal_selesai_tugas');

        $keg = $this->kegiatanModel->find($idKegiatan);
        if (!$keg) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kegiatan tidak ditemukan.']);
        }

        if (!$tglMulai) $tglMulai = $keg['tanggal_mulai'] ?: date('Y-m-d');
        if (!$tglSelesai) $tglSelesai = $keg['tanggal_selesai'] ?: $tglMulai;
        $jumlahHari = hitungHariPenuh($tglMulai, $tglSelesai);
        if ($detailLokus === '') {
            $detailLokus = $keg['tempat_lokus'] ?: $kabKota;
        }

        // Ambil NIP yang sudah terdaftar di kegiatan ini (atau di kegiatan kembar tanggal sama seperti Kode 2 & 3)
        $existingRows = $this->penugasanModel->where('id_kegiatan', $idKegiatan)->findAll();
        $existingNips = array_column($existingRows, 'nip');

        // Khusus Kode 3 (Peserta Rakor), kecualikan juga yang sudah jadi Panitia di Kode 2
        if ($keg['kode_kegiatan'] === '3') {
            $keg2 = $this->kegiatanModel->where('kode_kegiatan', '2')->first();
            if ($keg2) {
                $panKeg2 = $this->penugasanModel->where('id_kegiatan', $keg2['id_kegiatan'])->findAll();
                $existingNips = array_unique(array_merge($existingNips, array_column($panKeg2, 'nip')));
            }
        }

        $allPegawai = $this->pegawaiModel->orderBy('no_urut_rekap', 'ASC')->findAll();
        $inserted = 0;

        foreach ($allPegawai as $peg) {
            if (in_array($peg['nip'], $existingNips, true)) {
                continue;
            }

            if ($mode === 'seluruh_dinas_luar') {
                $katPeran = 'Dinas Luar';
                $peranDet = 'Petugas Monev / Dinas Luar';
                $zona     = ($keg['kode_kegiatan'] === '38') ? 'K38_ALL' : detectZona($kabKota);
            } elseif ($mode === 'seluruh_peserta_upt') {
                $katPeran = 'Peserta UPT';
                $peranDet = 'Peserta UPT';
                $zona     = 'NON_ZONA';
            } else {
                $katPeran = 'Peserta Hotel';
                $peranDet = 'Peserta';
                $zona     = 'NON_ZONA';
            }

            $this->penugasanModel->insert([
                'id_kegiatan'           => $idKegiatan,
                'nip'                   => $peg['nip'],
                'nama_pegawai'          => $peg['nama'],
                'peran_detail'          => $peranDet,
                'kategori_peran'        => $katPeran,
                'kab_kota_lokus'        => $kabKota,
                'detail_lokus'          => $detailLokus,
                'zona'                  => $zona,
                'tahap'                 => null,
                'tanggal_mulai_tugas'   => $tglMulai,
                'tanggal_selesai_tugas' => $tglSelesai,
                'jumlah_hari'           => $jumlahHari,
            ]);
            $inserted++;
        }

        return $this->response->setJSON([
            'status'   => 'ok',
            'inserted' => $inserted,
            'message'  => "Berhasil mem-plot {$inserted} pegawai ke Kode {$keg['kode_kegiatan']} ({$keg['nama_kegiatan']}).",
        ]);
    }

    // ─── DETAIL / AUDIT TRAIL PER PEGAWAI (JSON UNTUK MODAL REKAP & JADWAL) ─────
    public function detailPegawai($nip)
    {
        $nip = urldecode((string) $nip);
        $peg = $this->pegawaiModel->where('nip', $nip)->orWhere('nama', $nip)->first();
        $rincian = $this->penugasanModel->getDetailByPegawai($nip);

        // Ringkasan kode per kategori (contoh: Zona 2: K4(4h), K8(3h)...)
        $ringkasanKategori = [
            'Dinas Luar - Zona 1' => [],
            'Dinas Luar - Zona 2' => [],
            'Dinas Luar - Zona 3' => [],
            'Dinas Luar - Zona 4' => [],
            'Panitia Hotel'       => [],
            'Panitia UPT'         => [],
            'Fasilitator'         => [],
            'Peserta Hotel'       => [],
            'Peserta UPT'         => [],
        ];

        foreach ($rincian as $r) {
            $badge = "K{$r['kode_kegiatan']} ({$r['jumlah_hari']}h)";
            if ($r['kategori_peran'] === 'Dinas Luar') {
                $zKey = match ($r['zona']) {
                    'Z1' => 'Dinas Luar - Zona 1',
                    'Z2' => 'Dinas Luar - Zona 2',
                    'Z3' => 'Dinas Luar - Zona 3',
                    'Z4' => 'Dinas Luar - Zona 4',
                    default => 'Dinas Luar - Zona 1',
                };
                $ringkasanKategori[$zKey][] = $badge;
            } else {
                $ringkasanKategori[$r['kategori_peran']][] = $badge;
            }
        }

        return $this->response->setJSON([
            'status'    => 'ok',
            'pegawai'   => $peg,
            'rincian'   => $rincian,
            'ringkasan' => array_filter($ringkasanKategori),
        ]);
    }

    private function getDashboardUrl(string $role): string
    {
        return match ($role) {
            'super_admin' => 'dashboard/super-admin',
            'admin'       => 'dashboard/admin',
            'kepala'      => 'dashboard/kepala',
            default       => 'pegawai/profil/' . session()->get('nip'),
        };
    }
}
