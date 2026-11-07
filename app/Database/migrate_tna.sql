-- ============================================================
-- MIGRASI TABEL TNA — Sesuaikan dengan Struktur CI4 Baru
-- Jalankan di phpMyAdmin atau MySQL CLI
-- Database: pegawaibgtk
-- ============================================================

-- Langkah 1: Backup data lama jika ada
-- CREATE TABLE tna_backup AS SELECT * FROM tna;

-- Langkah 2: Hapus tabel lama dan buat yang baru
DROP TABLE IF EXISTS `tna`;

CREATE TABLE `tna` (
    `id_tna`                 INT(11)         NOT NULL AUTO_INCREMENT,
    `nip`                    VARCHAR(20)     NOT NULL,
    `kompetensi_dibutuhkan`  TEXT            NOT NULL,
    `kondisi_saat_ini`       TEXT            NOT NULL,
    `kategori_kesenjangan`   VARCHAR(100)    DEFAULT '',
    `usulan_pelatihan`       VARCHAR(255)    NOT NULL,
    `materi_pelatihan`       TEXT            DEFAULT NULL,
    `indikator_keberhasilan` TEXT            DEFAULT NULL,
    `metode_pelaksanaan`     VARCHAR(100)    DEFAULT '',
    `penyelenggara`          VARCHAR(255)    DEFAULT '',
    `jadwal_pelaksanaan`     VARCHAR(100)    DEFAULT '',
    `evaluasi_pasca`         TEXT            DEFAULT NULL,
    `catatan_rekomendasi`    TEXT            DEFAULT NULL,
    `prioritas`              ENUM('Tinggi','Sedang','Rendah')  NOT NULL DEFAULT 'Sedang',
    `status_pelaksanaan`     ENUM('Belum','Sedang Berjalan','Selesai') NOT NULL DEFAULT 'Belum',
    `status_persetujuan`     VARCHAR(50)     NOT NULL DEFAULT 'Menunggu Persetujuan',
    `dokumen_pendukung`      TEXT            DEFAULT NULL,
    `created_at`             DATETIME        DEFAULT NULL,
    `updated_at`             DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id_tna`),
    KEY `idx_nip` (`nip`),
    KEY `idx_status_persetujuan` (`status_persetujuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Selesai. Tabel TNA siap digunakan dengan aplikasi CI4.
