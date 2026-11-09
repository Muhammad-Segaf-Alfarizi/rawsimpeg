<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Penugasan2026Seeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->table('penugasan_sdm')->truncate();
        $db->table('kegiatan')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // 1. Update urutan & data 1..54 pegawai resmi BGTK Provinsi Lampung Tahun 2026
        $pegawaiResmi = [
            [1, 'Hendra Apriawan, S.T.', '198404092008011005', 'Penata Tingkat I / III.d', 'Kepala BGTK Provinsi Lampung', 'PNS'],
            [2, 'Dr. Agus Supriyanto, S.T., M.T.', '197202232005011001', 'Pembina Tingkat I / IV.b', 'Kasubbag Umum BGTK Provinsi Lampung', 'PNS'],
            [3, 'Nuryulinda, S.E., M.Pd.', '196807161994032002', 'Pembina / IV.a', 'PTP Ahli Madya', 'PNS'],
            [4, 'Ferismayanti, M.Pd.', '197001182003122001', 'Penata Tingkat I / III.d', 'PTP Ahli Muda', 'PNS'],
            [5, 'Tentrem Desilowati, M.Pd.', '197112072001122001', 'Penata Tingkat I / III.d', 'PTP Ahli Muda', 'PNS'],
            [6, 'Cecilia Yuliana, S.E., M.M', '197507062002122002', 'Penata Tingkat I / III.d', 'PTP Ahli Muda', 'PNS'],
            [7, 'Emilia Zulaiha Zahara, S.Si., M.M', '197508172005012001', 'Penata Tingkat I / III.d', 'PTP Ahli Muda', 'PNS'],
            [8, 'Wiwi Fitriyani, S.Psi., M.Pd.', '197509142005012001', 'Penata Tingkat I / III.d', 'PTP Ahli Muda', 'PNS'],
            [9, 'Tri Wibawanto, S.Si., M.Si', '197707042003121003', 'Penata Tingkat I / III.d', 'PTP Ahli Muda', 'PNS'],
            [10, 'Diana Sari Dj., S.Si., M.T', '198107172003122002', 'Penata Tingkat I / III.d', 'PTP Ahli Muda', 'PNS'],
            [11, 'Kamil, S.S., M.Pd.', '197106012001121002', 'Penata / III.c', 'PTP Ahli Muda', 'PNS'],
            [12, 'Yudi Setiadi, S.T., M.Pd.', '197506132002121002', 'Penata / III.c', 'PTP Ahli Muda', 'PNS'],
            [13, 'Nurningsih, S.T., M.Stat.', '197701112003122002', 'Penata / III.c', 'PTP Ahli Muda', 'PNS'],
            [14, 'Tommy Marsan, S.Kom., M.I.P.', '198003052008011012', 'Penata / III.c', 'PTP Ahli Muda', 'PNS'],
            [15, 'Erma Listiana, M.T', '198005042002122001', 'Pembina / IV.a', 'PTP Ahli Pertama', 'PNS'],
            [16, 'Dr. Agung Sedayu, S.Si., M.T.', '198101012003121003', 'Penata / III.c', 'PTP Ahli Muda', 'PNS'],
            [17, 'Lilis Setya Rini, S.Kom, M.T.I.', '198412302019022005', 'Penata Muda Tingkat I / III.b', 'PTP Ahli Pertama', 'PNS'],
            [18, 'Nasib Wasito, S.E., M.Pd', '198407072019021003', 'Penata Muda Tingkat I / III.b', 'PTP Ahli Pertama', 'PNS'],
            [19, 'Destri Melyanda, M.I.Kom', '198812262019022008', 'Penata Muda Tingkat I / III.b', 'PTP Ahli Pertama', 'PNS'],
            [20, 'Nur Indah Sari, S.I.Kom', '199512312019022004', 'Penata Muda Tingkat I / III.b', 'PTP Ahli Pertama', 'PNS'],
            [21, 'Agus Sohar, S.Pd', '197608132002121004', 'Penata Tingkat I / III.d', 'Penelaah Teknis Kebijakan', 'PNS'],
            [22, 'Andi Priyantoko, S.Pd., M.M.', '198704272011011005', 'Penata Tingkat I / III.d', 'Penelaah Teknis Kebijakan', 'PNS'],
            [23, 'Suyatmi, S.Pd', '196904232008012011', 'Penata Tingkat I / III.d', 'Penelaah Teknis Kebijakan', 'PNS'],
            [24, 'Ursida Rahman, S.Kom', '197601012008011023', 'Penata Muda Tingkat I / III.b', 'Penelaah Teknis Kebijakan', 'PNS'],
            [25, 'Ade Ridwan Maulana, S.T', '198703062018011001', 'Penata Muda Tingkat I / III.b', 'Penelaah Teknis Kebijakan', 'PNS'],
            [26, 'Putri Arum Lestari, S.Psi, M.Pd.', '198709292019022005', 'Penata Muda Tingkat I / III.b', 'Penelaah Teknis Kebijakan', 'PNS'],
            [27, 'Kartika Nawati, S.Pd., M.M', '198109062010012034', 'Penata Muda Tingkat I / III.b', 'Penelaah Teknis Kebijakan', 'PNS'],
            [28, 'Yeni Marlia Kartika Sari, SE', '199211222025062005', 'Penata Muda / III.a', 'Analis Anggaran Ahli Pertama', 'PNS'],
            [29, 'Vidhya Deseva, S.E.', '199512312022032018', 'Penata Muda / III.a', 'Penelaah Teknis Kebijakan', 'PNS'],
            [30, 'Muhammad Irfan Fakhri, S.IP.', '199605072025061003', 'Penata Muda / III.a', 'Analis Sumber Daya Manusia Aparatur Ahli Pertama', 'PNS'],
            [31, 'Annisa Awid Pramitha, S.I.Pus.', '199609152025062008', 'Penata Muda / III.a', 'Arsiparis Ahli Pertama', 'PNS'],
            [32, 'Bambang Sujito', '198609122010011003', 'Pengatur Tingkat I / II.d', 'Pengadministrasi Perkantoran', 'PNS'],
            [33, 'Sahri', '197405232008011002', 'Pengatur Tingkat I / II.d', 'Operator Layanan Operasional', 'PNS'],
            [34, 'Anisa Nurul Fadilla, A.Md', '199201152022032012', 'Pengatur / II.c', 'Pengolah Data dan Informasi', 'PNS'],
            [35, 'Fajar Meiwaldi, S.Kom', '198805232025211039', 'Gol. IX', 'Penata Layanan Operasional', 'PPPK'],
            [36, 'Febrina Primasari Lubis, S.Pd', '199002272025212030', 'Gol. IX', 'Penata Layanan Operasional', 'PPPK'],
            [37, 'Chesi Aulia Safitri, S.A.P.', '199805172025212026', 'Gol. VII', 'Pengelola Layanan Operasional', 'PPPK'],
            [38, 'Raisha Makhlida Ramadhanty', '199901062025212027', 'Gol. VII', 'Pengelola Layanan Operasional', 'PPPK'],
            [39, 'Bursadanur Arsyad, A.Md', '199102112025211046', 'Gol. VII', 'Pengelola Layanan Operasional', 'PPPK'],
            [40, 'M. Fadhillah Irba', '199306302025211048', 'Gol. V', 'Operator Layanan Operasional', 'PPPK'],
            [41, 'Najib', '197307102025211030', 'Gol. V', 'Operator Layanan Operasional', 'PPPK'],
            [42, 'Zulkarnain', '197705142025211024', 'Gol. V', 'Operator Layanan Operasional', 'PPPK'],
            [43, 'Ashar', '198205102025211029', 'Gol. V', 'Operator Layanan Operasional', 'PPPK'],
            [44, 'Darmanto', '198412122025211068', 'Gol. V', 'Operator Layanan Operasional', 'PPPK'],
            [45, 'Alie Mardiyan Arief', '198501252025211032', 'Gol. V', 'Operator Layanan Operasional', 'PPPK'],
            [46, 'Triono, S.A.P.', '199301032025211054', 'Gol. VII', 'Pengelola Layanan Operasional', 'PPPK'],
            [47, 'Rino Kurniawan, S.Pd', '199710312025211023', 'Gol. IX', 'Penata Layanan Operasional', 'PPPK'],
            [48, 'Santi', '197802282025212025', 'Gol. I', 'Pengadministrasi Perkantoran', 'PPPK'],
            [49, 'Muhammad Islah', '198105072025211033', 'Gol. I', 'Pengadministrasi Perkantoran', 'PPPK'],
            [50, 'Adi Saputra', '198208062025211055', 'Gol. I', 'Pengadministrasi Perkantoran', 'PPPK'],
            [51, 'Aris Sandi', '199611172025211080', 'Gol. I', 'Pengadministrasi Perkantoran', 'PPPK'],
            [52, 'Yuliana Dewi', '199403072025212039', 'Gol. I', 'Pengadministrasi Perkantoran', 'PPPK'],
            [53, 'Sukri', '199411032025211030', 'Gol. I', 'Pengadministrasi Perkantoran', 'PPPK'],
            [54, 'Syahrial', '198204222025211033', 'Gol. I', 'Pengadministrasi Perkantoran', 'PPPK'],
        ];

        foreach ($pegawaiResmi as [$no, $nama, $nip, $gol, $jab, $stat]) {
            $db->table('pegawai')->where('nip', $nip)->update([
                'no_urut_rekap'  => $no,
                'nama'           => $nama,
                'golongan'       => $gol,
                'jabatan'        => $jab,
                'status_pegawai' => $stat,
            ]);
        }

        // 2. Load dataset master dari sql/bgtk_2026_dataset.json
        $jsonPath = ROOTPATH . 'sql/bgtk_2026_dataset.json';
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("File dataset tidak ditemukan: {$jsonPath}");
        }

        $dataset = json_decode(file_get_contents($jsonPath), true);
        if (empty($dataset['kegiatan']) || empty($dataset['penugasan_sdm'])) {
            throw new \RuntimeException("Dataset JSON kosong atau tidak valid.");
        }

        // 3. Insert Master Kegiatan
        $kodeToId = [];
        foreach ($dataset['kegiatan'] as $keg) {
            $kode = $keg['kode_kegiatan'];
            unset($keg['id_kegiatan']);
            $db->table('kegiatan')->insert($keg);
            $kodeToId[$kode] = $db->insertID();
        }

        // 4. Insert Penugasan SDM
        $batch = [];
        foreach ($dataset['penugasan_sdm'] as $ps) {
            $kode = $ps['kode_kegiatan'] ?? null;
            if (!$kode || !isset($kodeToId[$kode])) continue;
            unset($ps['id_penugasan'], $ps['kode_kegiatan']);
            $ps['id_kegiatan'] = $kodeToId[$kode];
            $batch[] = $ps;
        }

        if (!empty($batch)) {
            $db->table('penugasan_sdm')->insertBatch($batch, null, 200);
        }
    }
}
