-- =========================================================================
-- SKEMA DATABASE MANAJEMEN KEGIATAN, JADWAL & REKAP PENUGASAN SDM BGTK 2026
-- Sesuai Dokumen Asli: Rencana Kegiatan Balai GTK Lampung 2026.xlsx
-- =========================================================================

CREATE DATABASE IF NOT EXISTS `pegawaibgtk` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `pegawaibgtk`;

-- Pastikan tabel pegawai memiliki kolom no_urut_rekap (Urutan 1 s.d. 56 sesuai dokumen Excel)
ALTER TABLE `pegawai` ADD COLUMN IF NOT EXISTS `no_urut_rekap` INT(11) DEFAULT 999;

-- Drop & Recreate untuk memastikan struktur sesuai dengan atribut asli Excel BGTK 2026
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `penugasan_sdm`;
DROP TABLE IF EXISTS `kegiatan`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabel Master Kegiatan (Sheet: Kode dan SDM)
CREATE TABLE `kegiatan` (
  `id_kegiatan` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_kegiatan` VARCHAR(10) NOT NULL COMMENT 'Kode angka 1 s.d. 43+',
  `nama_kegiatan` VARCHAR(255) NOT NULL,
  `tim_kerja` VARCHAR(100) DEFAULT 'Tim Kerja 1 : Peningkatan Kompetensi' COMMENT 'Tim Kerja 1 / Tim Kerja 2 / Tim Kerja 3 / Subbag Umum / Lainnya',
  `warna_hex` VARCHAR(10) DEFAULT '#E5B8B7' COMMENT 'Kode warna hex sel di tabel Jadwal & Kode',
  `kuota_keterangan` TEXT DEFAULT NULL COMMENT 'Rincian jumlah panitia/peserta/pendamping/fasil',
  `tanggal_mulai` DATE DEFAULT NULL,
  `tanggal_selesai` DATE DEFAULT NULL,
  `rentang_tanggal_teks` VARCHAR(150) DEFAULT NULL COMMENT 'Teks tanggal asli seperti di Excel (termasuk Tahap 1 & 2)',
  `tempat_lokus` VARCHAR(255) DEFAULT NULL,
  `status_kegiatan` ENUM('DONE', 'TERJADWAL', 'DARING') NOT NULL DEFAULT 'TERJADWAL',
  `catatan_ket` VARCHAR(150) DEFAULT NULL COMMENT 'Keterangan tambahan seperti 7 Kelas 6 lokus / DONE',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kegiatan`),
  UNIQUE KEY `idx_kode_kegiatan` (`kode_kegiatan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Tabel Transaksi Penugasan SDM (Sheet: Kode dan SDM, Jadwal, & Rekap)
CREATE TABLE `penugasan_sdm` (
  `id_penugasan` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_kegiatan` INT(11) UNSIGNED NOT NULL,
  `nip` VARCHAR(30) NOT NULL COMMENT 'Relasi ke pegawai.nip',
  `nama_pegawai` VARCHAR(150) NOT NULL COMMENT 'Snapshot nama pegawai',
  `peran_detail` VARCHAR(100) DEFAULT NULL COMMENT 'Jabatan kepanitiaan misal PJ, Ketua, Sekretaris, Pemantau, Pendamping',
  `kategori_peran` ENUM('Dinas Luar', 'Panitia Hotel', 'Panitia UPT', 'Fasilitator', 'Peserta Hotel', 'Peserta UPT') NOT NULL DEFAULT 'Dinas Luar',
  `kab_kota_lokus` VARCHAR(150) DEFAULT NULL COMMENT 'Kabupaten/Kota lokus tujuan',
  `detail_lokus` VARCHAR(255) DEFAULT NULL COMMENT 'Sekolah / Hotel / Instansi lokus spesifik (misal SDN 3 Gunung Sugih Pasar)',
  `zona` ENUM('Z1', 'Z2', 'Z3', 'Z4', 'K38_ALL', 'NON_ZONA') NOT NULL DEFAULT 'NON_ZONA',
  `tahap` VARCHAR(50) DEFAULT NULL COMMENT 'Tahap 1 / Tahap 2 (opsional)',
  `tanggal_mulai_tugas` DATE DEFAULT NULL,
  `tanggal_selesai_tugas` DATE DEFAULT NULL,
  `jumlah_hari` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_penugasan`),
  KEY `idx_id_kegiatan` (`id_kegiatan`),
  KEY `idx_nip` (`nip`),
  KEY `idx_kategori_zona` (`kategori_peran`, `zona`),
  KEY `idx_tanggal_tugas` (`tanggal_mulai_tugas`, `tanggal_selesai_tugas`),
  CONSTRAINT `fk_penugasan_kegiatan_2026` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id_kegiatan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
